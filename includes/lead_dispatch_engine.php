<?php
// includes/lead_dispatch_engine.php
// Extracted common logic to dispatch leads to businesses

function dispatchPublicLead($pdo, $name, $phone, $email, $leadQuery, $leadCat, $leadCity) {
    if (empty($name) || empty($phone)) {
        return ['success' => false, 'error' => 'Name and Phone are required.'];
    }

    try {
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $managedTransaction = true;
        } else {
            $managedTransaction = false;
        }

        // Geocoding for Lead
        $city_coords = ['Hyderabad'=>[17.3850,78.4867],'Mumbai'=>[19.0760,72.8777],'Delhi'=>[28.6139,77.2090],'Bangalore'=>[12.9716,77.5946],'Pune'=>[18.5204,73.8567],'Chennai'=>[13.0827,80.2707],'Kolkata'=>[22.5726,88.3639],'Ahmedabad'=>[23.0225,72.5714]];
        $lat = null; $lng = null;
        foreach($city_coords as $cn=>$cc) { if(stripos($leadCity, $cn)!==false) { $lat=$cc[0]; $lng=$cc[1]; break; } }

        // Insert into public_leads
        $stmt = $pdo->prepare("INSERT INTO public_leads (name, phone, email, query, intent, category, city, status, total_members_notified, created_at, lat, lng, assigned_at) VALUES (?, ?, ?, ?, 'buy', ?, ?, 'new', 0, NOW(), ?, ?, NOW())");
        $stmt->execute([$name, $phone, $email, $leadQuery, $leadCat, $leadCity, $lat, $lng]);
        $leadId = $pdo->lastInsertId();

        // Reward: Send a New Lead (+25 🪙) if logged in
        if (isset($_SESSION['user_id'])) {
            require_once __DIR__ . '/../includes_functions.php';
            awardCoins($pdo, $_SESSION['user_id'], 25, "Sent a New Lead: ID $leadId");
            sendNotification($pdo, $_SESSION['user_id'], "Lead Sent!", "You earned 25 VooCoins for sharing a new business lead.", 'coins');
        }

        // Find matching members - Strictly Category-Based
        $matchSql = "
            SELECT u.id, u.name, u.email as user_email, u.plan, bp.business_name, bp.whatsapp, bp.category, bp.city,
                   (SELECT MAX(notified_at) FROM lead_dispatches WHERE member_id = u.id) as last_lead_time
            FROM users u 
            JOIN business_profiles bp ON u.id = bp.user_id 
            WHERE u.status = 'active' AND u.email NOT LIKE '%@example.com' AND bp.category = ?
        ";
        
        $stmtMatching = $pdo->prepare($matchSql);
        $stmtMatching->execute([$leadCat]);
        $allMatches = $stmtMatching->fetchAll(PDO::FETCH_ASSOC);

        // --- Slot Allocation Logic ---
        $matchedMembers = [];
        if (!empty($allMatches)) {
            $tierValue = ['platinum' => 4, 'gold' => 3, 'silver' => 2, 'free' => 1];
            
            // Primary Sort: Tier DESC, then Last Lead ASC
            usort($allMatches, function($a, $b) use ($tierValue) {
                $tvA = $tierValue[$a['plan']] ?? 1;
                $tvB = $tierValue[$b['plan']] ?? 1;
                if ($tvA !== $tvB) return $tvB <=> $tvA;
                $timeA = $a['last_lead_time'] ?? '1970-01-01';
                $timeB = $b['last_lead_time'] ?? '1970-01-01';
                return $timeA <=> $timeB;
            });
            
            // Slots 1 and 2: Top purely by Tier/Algorithm
            if (isset($allMatches[0])) $matchedMembers[] = $allMatches[0];
            if (isset($allMatches[1])) $matchedMembers[] = $allMatches[1];
            
            $remaining = array_slice($allMatches, 2);
            
            if (!empty($remaining)) {
                // Slot 3: Fair Rotation (Longest waiting member, regardless of tier)
                usort($remaining, function($a, $b) {
                        $timeA = $a['last_lead_time'] ?? '1970-01-01';
                        $timeB = $b['last_lead_time'] ?? '1970-01-01';
                        return $timeA <=> $timeB;
                });
                
                $matchedMembers[] = $remaining[0];
                
                // Slots 4-10
                $rest = array_slice($remaining, 1);
                usort($rest, function($a, $b) use ($tierValue) {
                    $tvA = $tierValue[$a['plan']] ?? 1;
                    $tvB = $tierValue[$b['plan']] ?? 1;
                    if ($tvA !== $tvB) return $tvB <=> $tvA;
                    return ($a['last_lead_time'] ?? '1970-01-01') <=> ($b['last_lead_time'] ?? '1970-01-01');
                });
                
                for ($i = 0; $i < 7; $i++) {
                    if (isset($rest[$i])) $matchedMembers[] = $rest[$i];
                }
            }
        }

        $totalNotified = count($matchedMembers);

        // Insert into lead_dispatches and Notify members
        $rank = 1;
        foreach ($matchedMembers as $member) {
            // Insert tracking row
            $dispatchStmt = $pdo->prepare("INSERT INTO lead_dispatches (lead_id, member_id, member_name, business_name, category, city, whatsapp, dispatch_rank, slot_number, status, notified_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
            $dispatchStmt->execute([
                $leadId, 
                $member['id'], 
                $member['name'], 
                $member['business_name'], 
                $member['category'], 
                $member['city'], 
                $member['whatsapp'] ?? '', 
                $rank,
                $rank
            ]);

            // Send Email Notification if email config is ready
            if (file_exists(__DIR__ . '/emails/lead_notify.php')) {
                require_once __DIR__ . '/emails/lead_notify.php';
                sendLeadEmail($member['user_email'], $member['name'], $leadCat, $leadCity, $leadQuery, $name, $phone);
            }

            // App Notification
            require_once __DIR__ . '/../includes_functions.php';
            sendNotification($pdo, $member['id'], "⚡ Hot Lead Received", "New matching user searching for $leadCat. Query: $leadQuery", "lead");

            $rank++;
        }

        // Update public_leads with total_notified count
        $updateLead = $pdo->prepare("UPDATE public_leads SET total_members_notified = ? WHERE id = ?");
        $updateLead->execute([$totalNotified, $leadId]);

        if ($managedTransaction) {
            $pdo->commit();
        }
        
        return [
            'success' => true,
            'lead_id' => $leadId,
            'matched_count' => $totalNotified,
            'matched_members' => $matchedMembers
        ];

    } catch (Exception $e) {
        if (isset($managedTransaction) && $managedTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
