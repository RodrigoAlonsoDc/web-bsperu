<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../admin/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM - BS Peru</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body {
            height: 100vh;
            width: 100vw;
            background: var(--bg);
            color: var(--text-primary);
            margin: 0;
            overflow: hidden;
            font-family: 'Montserrat', sans-serif;
            transition: background 0.3s;
        }

        /* Top Bar flotante */
        .topbar {
            position: fixed;
            top: 20px;
            right: 40px;
            color: white;
            z-index: 100;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .topbar h1 { margin: 0; font-size: 20px; font-weight: 300; letter-spacing: 2px; }
        .btn-logout { color: var(--primary); text-decoration: none; border: 1px solid var(--primary); padding: 8px 15px; border-radius: 20px; font-size: 12px; transition: 0.3s; }
        .btn-logout:hover { background: var(--primary); color: var(--text-inverse); }

        #image_track {
            display: flex;
            gap: 4vmin;
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(0%, -50%);
            user-select: none;
        }

        .module-card {
            position: relative;
            cursor: pointer;
            overflow: hidden;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .module-card:hover {
            transform: scale(1.02);
            box-shadow: 0 10px 40px rgba(255,255,255,0.1);
        }

        .module-card .image {
            width: 45vmin;
            height: 65vmin;
            object-fit: cover;
            object-position: 100% center;
            -webkit-filter: brightness(0.75);
            -o-filter: brightness(0.75);
            -moz-filter: brightness(0.75);
            -ms-filter: brightness(0.75);
            filter: brightness(0.75);
            display: block;
            pointer-events: none; /* Para no interferir con el click del card */
        }

        .module-card:hover .image {
            filter: brightness(1);
        }

        .module-title {
            position: absolute;
            bottom: 30px;
            left: 30px;
            color: white;
            font-size: 3vmin;
            font-weight: 600;
            text-shadow: 2px 2px 10px rgba(0,0,0,0.8);
            pointer-events: none;
            letter-spacing: 1px;
            background: rgba(0,0,0,0.3);
            padding: 10px 20px;
            border-radius: 5px;
            backdrop-filter: blur(5px);
            border-left: 4px solid var(--primary);
        }

        .helper-text {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            color: var(--text-secondary);
            font-size: 12px;
            letter-spacing: 3px;
            pointer-events: none;
        }
    </style>
</head>
<body>

    <?php include 'theme_switcher.php'; ?>

    <div class="topbar">
        <h1>BS PERU</h1>
        <a href="../admin/logout.php" class="btn-logout">Cerrar Sesión</a>
    </div>

    <div id="image_track" data-mouse-down-at="0" data-prev-percentage="0">
        
        <div class="module-card" data-url="#">
            <img class="image" src="https://images.unsplash.com/photo-1504275107627-0c2ba7a43dba?q=80&w=1974&auto=format&fit=crop&ixlib=rb-4.0.3" alt="Comercial" draggable="false" />
            <div class="module-title">COMERCIAL</div>
        </div>

        <div class="module-card" data-url="pedidos.php">
            <img class="image" src="https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?q=80&w=1740&auto=format&fit=crop&ixlib=rb-4.0.3" alt="Pedidos" draggable="false" />
            <div class="module-title">PEDIDOS</div>
        </div>

        <div class="module-card" data-url="#">
            <img class="image" src="https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?q=80&w=1740&auto=format&fit=crop&ixlib=rb-4.0.3" alt="Despachos" draggable="false" />
            <div class="module-title">DESPACHOS</div>
        </div>

        <div class="module-card" data-url="#">
            <img class="image" src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?q=80&w=1740&auto=format&fit=crop&ixlib=rb-4.0.3" alt="Reportes" draggable="false" />
            <div class="module-title">REPORTES</div>
        </div>

    </div>

    <div class="helper-text">MANTÉN PRESIONADO Y ARRASTRA PARA NAVEGAR</div>

    <script>
        const track = document.getElementById("image_track");
        let isDragging = false;
        let startX = 0;

        const handleOnDown = (e) => {
            isDragging = false;
            startX = e.clientX || (e.touches && e.touches[0].clientX);
            track.dataset.mouseDownAt = startX;
        };

        const handleOnUp = (e) => {
            track.dataset.mouseDownAt = "0";
            track.dataset.prevPercentage = track.dataset.percentage;
        };

        const handleOnMove = (e) => {
            if (track.dataset.mouseDownAt === "0") return;

            const currentX = e.clientX || (e.touches && e.touches[0].clientX);
            const mouseDelta = parseFloat(track.dataset.mouseDownAt) - currentX;
            const maxDelta = window.innerWidth / 2;

            if (Math.abs(mouseDelta) > 10) {
                isDragging = true; // Si se movió más de 10px, es un arrastre, no un clic
            }

            const percentage = (mouseDelta / maxDelta) * -100,
                  nextPercentageUnconstrained = parseFloat(track.dataset.prevPercentage) + percentage,
                  nextPercentage = Math.max(Math.min(nextPercentageUnconstrained, 0), -100);

            track.dataset.percentage = nextPercentage;

            track.animate(
                { transform: `translate(${nextPercentage}%, -50%)` },
                { duration: 1200, fill: "forwards" }
            );

            for (const image of track.getElementsByClassName("image")) {
                image.animate(
                    { objectPosition: `${100 + nextPercentage}% center` },
                    { duration: 1200, fill: "forwards" }
                );
            }
        };

        window.onmousedown = e => handleOnDown(e);
        window.ontouchstart = e => handleOnDown(e.touches[0]);

        window.onmouseup = e => handleOnUp(e);
        window.ontouchend = e => handleOnUp(e.touches[0]);

        window.onmousemove = e => handleOnMove(e);
        window.ontouchmove = e => handleOnMove(e.touches[0]);

        // Lógica de clics seguros (no se dispara si el usuario estaba arrastrando)
        document.querySelectorAll('.module-card').forEach(card => {
            card.addEventListener('click', (e) => {
                if (!isDragging) {
                    const url = card.getAttribute('data-url');
                    if(url && url !== '#') {
                        window.location.href = url;
                    }
                }
            });
        });
    </script>

</body>
</html>
