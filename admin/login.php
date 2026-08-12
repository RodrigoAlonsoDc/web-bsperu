<?php
session_start();

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CAMBIA ESTOS DATOS POR LA CONTRASEÑA REAL QUE QUIERAS
    $admin_user = 'admin';
    $admin_pass = 'bsperu2026';

    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';

    if ($user === $admin_user && $pass === $admin_pass) {
        $_SESSION['admin_logged_in'] = true;
        header("Location: index.php");
        exit;
    } else {
        $error = 'Usuario o contraseña incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BS Peru - Admin Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        :root {
            --primary: #2563eb;
            --bg: #f8fafc;
            --surface: #ffffff;
            --text: #0f172a;
            --border: #e2e8f0;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg); display: flex; align-items: center; justify-content: center; height: 100vh; color: var(--text); }
        .login-card { background: var(--surface); padding: 2.5rem; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); width: 100%; max-width: 400px; }
        .login-header { text-align: center; margin-bottom: 2rem; }
        .login-header i { font-size: 3rem; color: var(--primary); margin-bottom: 1rem; }
        .login-header h1 { font-size: 1.5rem; font-weight: 600; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-size: 0.9rem; font-weight: 500; color: #475569; }
        .input-wrapper { position: relative; }
        .input-wrapper i { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 1.2rem; }
        .input-wrapper input { width: 100%; padding: 0.75rem 1rem 0.75rem 2.8rem; border: 1px solid var(--border); border-radius: 8px; font-size: 1rem; outline: none; transition: border-color 0.2s; }
        .input-wrapper input:focus { border-color: var(--primary); }
        .btn-submit { width: 100%; padding: 0.75rem; background: var(--primary); color: white; border: none; border-radius: 8px; font-size: 1rem; font-weight: 500; cursor: pointer; transition: background 0.2s; }
        .btn-submit:hover { background: #1d4ed8; }
        .error-msg { background: #fee2e2; color: #ef4444; padding: 0.75rem; border-radius: 8px; font-size: 0.9rem; margin-bottom: 1.5rem; text-align: center; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <i class="ph ph-shield-check"></i>
            <h1>Acceso Administrador</h1>
            <p style="color: #64748b; font-size: 0.9rem; margin-top: 0.5rem;">Gestor de Catálogo BS Peru</p>
        </div>

        <?php if ($error): ?>
            <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Usuario</label>
                <div class="input-wrapper">
                    <i class="ph ph-user"></i>
                    <input type="text" id="username" name="username" required autocomplete="off">
                </div>
            </div>
            <div class="form-group">
                <label for="password">Contraseña</label>
                <div class="input-wrapper">
                    <i class="ph ph-lock-key"></i>
                    <input type="password" id="password" name="password" required>
                </div>
            </div>
            <button type="submit" class="btn-submit">Ingresar al Panel</button>
        </form>
    </div>
</body>
</html>
