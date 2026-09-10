<?php
// crm/login.php - BS Perú CRM: Portal de Acceso Seguro
session_start();

// Si ya tiene sesión activa, redirigir automáticamente a su módulo correspondiente
if (isset($_SESSION['crm_logged_in']) && $_SESSION['crm_logged_in'] === true) {
    $rol = $_SESSION['crm_rol'] ?? '';
    if ($rol === 'ventas') {
        header("Location: ventas.php");
        exit;
    } elseif ($rol === 'reporteria') {
        header("Location: reportes.php");
        exit;
    } else {
        header("Location: index.php");
        exit;
    }
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user = strtolower(trim($_POST['username'] ?? ''));
    $pass = trim($_POST['password'] ?? '');

    // Usuarios del CRM con sus respectivos roles y destinos
    $usuarios = [
        'endrina' => [
            'nombre' => 'Endrina',
            'rol' => 'ventas',
            'cargo' => 'Asesora Comercial & Ventas',
            'pass' => 'Ventas2026*',
            'redirect' => 'ventas.php'
        ],
        'nayeli' => [
            'nombre' => 'Nayeli',
            'rol' => 'reporteria',
            'cargo' => 'Especialista de Reportería & Finanzas',
            'pass' => 'Reportes2026*',
            'redirect' => 'reportes.php'
        ],
        'admin' => [
            'nombre' => 'Administrador',
            'rol' => 'admin',
            'cargo' => 'Administrador General CRM',
            'pass' => 'bsperu2026',
            'redirect' => 'index.php'
        ]
    ];

    if (isset($usuarios[$user]) && $usuarios[$user]['pass'] === $pass) {
        $_SESSION['crm_logged_in'] = true;
        $_SESSION['admin_logged_in'] = true; // Compatibilidad con panel admin
        $_SESSION['crm_user'] = $usuarios[$user]['nombre'];
        $_SESSION['crm_rol'] = $usuarios[$user]['rol'];
        $_SESSION['crm_cargo'] = $usuarios[$user]['cargo'];

        header("Location: " . $usuarios[$user]['redirect']);
        exit;
    } else {
        $error = 'Usuario o contraseña incorrectos. Por favor verifica tus credenciales.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso al CRM - BS Perú</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-dark: #0D0F12;
            --surface-card: #15181E;
            --surface-input: #1C2028;
            --border-soft: #2B313C;
            --accent-gold: #C79B58;
            --accent-gold-hover: #D8AA66;
            --accent-green: #10B981;
            --text-light: #F3F4F6;
            --text-muted: #9CA3AF;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Montserrat', sans-serif;
        }

        body {
            background-color: var(--bg-dark);
            color: var(--text-light);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-image: 
                radial-gradient(circle at 15% 25%, rgba(199, 155, 88, 0.12) 0%, transparent 45%),
                radial-gradient(circle at 85% 75%, rgba(16, 185, 129, 0.08) 0%, transparent 45%);
            padding: 20px;
        }

        .login-wrapper {
            width: 100%;
            max-width: 440px;
        }

        .login-card {
            background: var(--surface-card);
            border: 1px solid var(--border-soft);
            border-radius: 24px;
            padding: 40px 36px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--accent-gold), var(--accent-green));
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(199, 155, 88, 0.12);
            color: var(--accent-gold);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 14px;
            border: 1px solid rgba(199, 155, 88, 0.25);
        }

        .login-header h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: #FFFFFF;
            letter-spacing: -0.5px;
            margin-bottom: 6px;
        }

        .login-header p {
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 0.82rem;
            font-weight: 600;
            color: #E2E8F0;
            margin-bottom: 8px;
        }

        .input-box {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-box i.field-icon {
            position: absolute;
            left: 16px;
            color: #64748B;
            font-size: 1rem;
            transition: color 0.2s ease;
        }

        .input-box input {
            width: 100%;
            padding: 13px 44px 13px 44px;
            background: var(--surface-input);
            border: 1px solid var(--border-soft);
            border-radius: 14px;
            color: #FFFFFF;
            font-size: 0.92rem;
            outline: none;
            transition: all 0.25s ease;
        }

        .input-box input:focus {
            border-color: var(--accent-gold);
            box-shadow: 0 0 0 3px rgba(199, 155, 88, 0.2);
        }

        .input-box input:focus ~ i.field-icon {
            color: var(--accent-gold);
        }

        .toggle-password-btn {
            position: absolute;
            right: 14px;
            background: none;
            border: none;
            color: #64748B;
            cursor: pointer;
            padding: 4px;
            font-size: 0.95rem;
            transition: color 0.2s;
        }

        .toggle-password-btn:hover {
            color: var(--text-light);
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--accent-gold), #A97E3E);
            color: #111418;
            border: none;
            border-radius: 14px;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 14px rgba(199, 155, 88, 0.35);
            margin-top: 10px;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, var(--accent-gold-hover), var(--accent-gold));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(199, 155, 88, 0.45);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .error-card {
            background: rgba(220, 38, 38, 0.15);
            border: 1px solid rgba(220, 38, 38, 0.4);
            color: #FCA5A5;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 0.82rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .account-hint {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .hint-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.05);
            padding: 8px 12px;
            border-radius: 10px;
            font-size: 0.74rem;
            cursor: pointer;
            transition: background 0.2s;
        }

        .hint-item:hover {
            background: rgba(199, 155, 88, 0.1);
            border-color: rgba(199, 155, 88, 0.3);
        }

        .hint-role {
            font-weight: 700;
            color: var(--accent-gold);
        }

        .hint-user {
            color: var(--text-muted);
            font-family: monospace;
        }

        .footer-note {
            text-align: center;
            font-size: 0.72rem;
            color: #64748B;
            margin-top: 20px;
        }
    </style>
</head>
<body>

    <div class="login-wrapper">
        <div class="login-card">
            
            <div class="login-header">
                <div class="brand-badge">
                    <i class="fa-solid fa-gem"></i> BS Perú ERP & CRM
                </div>
                <h1>Portal de Acceso</h1>
                <p>Ingresa tus credenciales para acceder a tu módulo</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="error-card">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="form-group">
                    <label for="inputUser">Usuario Asignado</label>
                    <div class="input-box">
                        <i class="fa-solid fa-user field-icon"></i>
                        <input type="text" id="inputUser" name="username" placeholder="Ej. endrina o nayeli" required autofocus autocomplete="username">
                    </div>
                </div>

                <div class="form-group">
                    <label for="inputPass">Contraseña de Seguridad</label>
                    <div class="input-box">
                        <i class="fa-solid fa-lock field-icon"></i>
                        <input type="password" id="inputPass" name="password" placeholder="••••••••••••" required autocomplete="current-password">
                        <button type="button" class="toggle-password-btn" onclick="togglePasswordVisibility()">
                            <i class="fa-regular fa-eye" id="iconEye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fa-solid fa-right-to-bracket"></i> Iniciar Sesión en el CRM
                </button>
            </form>

            <div class="account-hint">
                <span style="font-size:0.7rem; color:var(--text-muted); text-transform:uppercase; letter-spacing:1px; font-weight:700;">Acceso Rápido por Rol:</span>
                
                <div class="hint-item" onclick="llenarCredenciales('endrina', 'Ventas2026*')" title="Clic para rellenar">
                    <span class="hint-role"><i class="fa-solid fa-bag-shopping"></i> Ventas (Endrina)</span>
                    <span class="hint-user">endrina</span>
                </div>

                <div class="hint-item" onclick="llenarCredenciales('nayeli', 'Reportes2026*')" title="Clic para rellenar">
                    <span class="hint-role" style="color:#34D399;"><i class="fa-solid fa-chart-pie"></i> Reportería (Nayeli)</span>
                    <span class="hint-user">nayeli</span>
                </div>
            </div>

        </div>

        <div class="footer-note">
            BS Perú &copy; <?php echo date('Y'); ?> • Sistema Privado de Gestión Comercial & Conciliación
        </div>
    </div>

    <script>
        function togglePasswordVisibility() {
            const passInput = document.getElementById('inputPass');
            const iconEye = document.getElementById('iconEye');
            if (passInput.type === 'password') {
                passInput.type = 'text';
                iconEye.classList.remove('fa-eye');
                iconEye.classList.add('fa-eye-slash');
            } else {
                passInput.type = 'password';
                iconEye.classList.remove('fa-eye-slash');
                iconEye.classList.add('fa-eye');
            }
        }

        function llenarCredenciales(usuario, clave) {
            document.getElementById('inputUser').value = usuario;
            document.getElementById('inputPass').value = clave;
            document.getElementById('inputPass').focus();
        }
    </script>
</body>
</html>
