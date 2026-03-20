<?php
session_start();

$redirect = $_GET['redirect'] ?? '/dashboard/index.php';
// basic safety: ensure redirect starts with /
if (strpos($redirect, '/') !== 0) {
    $redirect = '/dashboard/index.php';
}

if (isset($_SESSION['user_id'])) {
    header('Location: ' . $redirect);
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter your email and password.';
    } else {
        try {
            $pdo = new PDO(
                'mysql:host=localhost;dbname=u175452495_biznexus;charset=utf8mb4',
                'u175452495_bizuser',
                'Biz@9990',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            $stmt = $pdo->prepare('SELECT id, name, email, password, role, status FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] === 'inactive') {
                    $error = 'Your account has been deactivated. Please contact support.';
                } else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['name']    = $user['name'];
                    $_SESSION['email']   = $user['email'];
                    $_SESSION['role']    = $user['role'];

                    $updateStmt = $pdo->prepare('UPDATE users SET last_login = NOW(), last_active_at = NOW() WHERE id = ?');
                    $updateStmt->execute([$user['id']]);

                    header('Location: ' . $redirect);
                    exit;
                }
            } else {
                $error = 'Invalid email or password. Please try again.';
            }
        } catch (PDOException $e) {
            $error = 'Database error. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – BizNexus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg:       #0a0a0f;
            --card:     #13131a;
            --gold:     #FFD700;
            --green:    #00ff88;
            --border:   #2a2a3a;
            --muted:    #6c757d;
            --text:     #e0e0e0;
        }

        * { box-sizing: border-box; }

        body {
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', system-ui, sans-serif;
            padding: 1rem;
        }

        .auth-wrapper {
            width: 100%;
            max-width: 440px;
        }

        .brand {
            text-align: center;
            margin-bottom: 2rem;
        }

        .brand-logo {
            font-size: 2rem;
            font-weight: 800;
            color: var(--gold);
            letter-spacing: -1px;
        }

        .brand-logo span {
            color: var(--green);
        }

        .brand-sub {
            color: var(--muted);
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }

        .auth-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 2.25rem 2rem;
            box-shadow: 0 8px 40px rgba(0,0,0,0.5);
        }

        .auth-card h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 0.25rem;
        }

        .auth-card .subtitle {
            color: var(--muted);
            font-size: 0.9rem;
            margin-bottom: 1.75rem;
        }

        .form-label {
            color: #bbb;
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 0.4rem;
        }

        .form-control {
            background: #0d0d14;
            border: 1px solid var(--border);
            color: #fff;
            border-radius: 10px;
            padding: 0.65rem 1rem;
            font-size: 0.95rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-control:focus {
            background: #0d0d14;
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(255,215,0,0.12);
            color: #fff;
            outline: none;
        }

        .form-control::placeholder { color: #444; }

        .input-group .form-control {
            border-right: none;
            border-radius: 10px 0 0 10px;
        }

        .input-group .btn-eye {
            background: #0d0d14;
            border: 1px solid var(--border);
            border-left: none;
            color: var(--muted);
            border-radius: 0 10px 10px 0;
            padding: 0 0.9rem;
            cursor: pointer;
            transition: color 0.2s;
        }

        .input-group .btn-eye:hover { color: var(--gold); }

        .input-group:focus-within .btn-eye {
            border-color: var(--gold);
        }

        .btn-login {
            background: linear-gradient(135deg, var(--gold), #f0a500);
            color: #0a0a0f;
            border: none;
            border-radius: 10px;
            padding: 0.7rem 1rem;
            font-weight: 700;
            font-size: 1rem;
            width: 100%;
            transition: opacity 0.2s, transform 0.15s;
            letter-spacing: 0.3px;
        }

        .btn-login:hover {
            opacity: 0.9;
            transform: translateY(-1px);
            color: #0a0a0f;
        }

        .btn-login:active { transform: translateY(0); }

        .divider {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin: 1.5rem 0;
            color: var(--muted);
            font-size: 0.8rem;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        .alert-dark-danger {
            background: rgba(220,53,69,0.12);
            border: 1px solid rgba(220,53,69,0.35);
            color: #ff7b7b;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-size: 0.88rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.25rem;
        }

        .forgot-link {
            color: var(--gold);
            font-size: 0.83rem;
            text-decoration: none;
            float: right;
            line-height: 1;
        }

        .forgot-link:hover { color: #ffe44d; text-decoration: underline; }

        .register-link {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.88rem;
            color: var(--muted);
        }

        .register-link a {
            color: var(--green);
            text-decoration: none;
            font-weight: 600;
        }

        .register-link a:hover { text-decoration: underline; }

        .feature-pills {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 1.5rem;
        }

        .pill {
            background: rgba(255,215,0,0.08);
            border: 1px solid rgba(255,215,0,0.2);
            color: var(--gold);
            font-size: 0.72rem;
            padding: 0.25rem 0.65rem;
            border-radius: 20px;
            font-weight: 500;
        }

        @media (max-width: 480px) {
            .auth-card { padding: 1.75rem 1.25rem; }
        }
    </style>
</head>
<body>

<div class="auth-wrapper">

    <div class="brand">
        <div class="brand-logo">Biz<span>Nexus</span></div>
        <div class="brand-sub">India's Premier Business Network</div>
    </div>

    <div class="auth-card">
        <h2>Welcome back 👋</h2>
        <p class="subtitle">Sign in to your BizNexus account</p>

        <?php if ($error): ?>
        <div class="alert-dark-danger">
            <i class="fas fa-exclamation-circle"></i>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="" novalidate>

            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-control"
                    placeholder="you@example.com"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    required
                    autocomplete="email"
                >
            </div>

            <div class="mb-1">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label for="password" class="form-label mb-0">Password</label>
                    <a href="/auth/forgot.php" class="forgot-link">Forgot password?</a>
                </div>
                <div class="input-group">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="••••••••"
                        required
                        autocomplete="current-password"
                    >
                    <button type="button" class="btn-eye" onclick="togglePassword()" title="Show/Hide password">
                        <i class="fas fa-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>

            <div class="mb-3 mt-2 form-check">
                <input type="checkbox" class="form-check-input" id="remember" name="remember" style="background:#0d0d14;border-color:#2a2a3a;">
                <label class="form-check-label" for="remember" style="font-size:0.85rem;color:#888;">Keep me signed in</label>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt me-2"></i>Sign In
            </button>

        </form>

        <div class="divider">or</div>

        <div class="register-link">
            Don't have an account?
            <a href="/auth/register.php">Create one free</a>
        </div>

    </div>

    <div class="feature-pills">
        <span class="pill">🪙 VooCoins</span>
        <span class="pill">🤝 B2B Network</span>
        <span class="pill">📈 Growth Tools</span>
        <span class="pill">🔒 Secure</span>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePassword() {
    const pw  = document.getElementById('password');
    const ico = document.getElementById('eyeIcon');
    if (pw.type === 'password') {
        pw.type = 'text';
        ico.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        pw.type = 'password';
        ico.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>

</body>
</html>