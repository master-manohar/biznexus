<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../includes/db.php';

function awardCoinsDebug($pdo, $user_id, $amount, $description) {
    try {
        echo "Updating users...\n";
        $pdo->prepare("UPDATE users SET coins = coins + ? WHERE id = ?")->execute([(int)$amount, (int)$user_id]);
        
        $stmt = $pdo->prepare("SELECT coins FROM users WHERE id = ?");
        $stmt->execute([(int)$user_id]);
        $new_balance = (int)$stmt->fetchColumn();
        echo "New Balance Fetched: $new_balance\n";

        echo "Checking voocoin_balances...\n";
        $chk = $pdo->prepare("SELECT id FROM voocoin_balances WHERE user_id = ?");
        $chk->execute([(int)$user_id]);
        $row = $chk->fetch();
        if ($row) {
            echo "Found row in voocoin_balances. Updating...\n";
            $upd = $pdo->prepare("UPDATE voocoin_balances SET balance = ?, updated_at = NOW() WHERE user_id = ?");
            $res = $upd->execute([$new_balance, (int)$user_id]);
            echo "Update Result: " . ($res ? "OK" : "FAIL") . " Rows: " . $upd->rowCount() . "\n";
        } else {
            echo "No row found. Inserting...\n";
        }
        
        return $new_balance;
    } catch (Exception $e) {
        echo "EXCEPTION: " . $e->getMessage() . "\n";
        return false;
    }
}

awardCoinsDebug($pdo, 1, 10, "Debug Call");
?>
