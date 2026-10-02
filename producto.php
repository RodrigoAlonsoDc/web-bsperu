<?php
// Cargar JSON de productos
$productosJson = @file_get_contents(__DIR__ . '/assets/Data/productos.json');
$productos = $productosJson ? json_decode($productosJson, true) : [];

// Helper para generar slugs limpios
function slugify_val($text) {
    if (!$text) return '';
    $text = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
    if (!$text) {
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    }
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = trim($text, '-');
    $text = strtolower($text);
    return preg_replace('~-+~', '-', $text);
}

// Obtener SLUG o SKU de la URL
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$sku = isset($_GET['sku']) ? trim($_GET['sku']) : '';
$productoActual = null;

if ($productos) {
    // 1. Buscar por slug exacto
    if (!empty($slug)) {
        foreach ($productos as $p) {
            if (isset($p['slug']) && strtolower($p['slug']) === strtolower($slug)) {
                $productoActual = $p;
                break;
            }
        }
        // Fallback: buscar si el slug coincide con slugify(nombre)
        if (!$productoActual) {
            foreach ($productos as $p) {
                if (isset($p['nombre']) && slugify_val($p['nombre']) === strtolower($slug)) {
                    $productoActual = $p;
                    break;
                }
            }
        }
        // O si pasaron SKU en el parámetro slug (ej. /producto/110014319)
        if (!$productoActual) {
            foreach ($productos as $p) {
                if (isset($p['sku']) && (string)$p['sku'] === $slug) {
                    $productoActual = $p;
                    break;
                }
            }
        }
    }

    // 2. Buscar por SKU si no se encontró por slug
    if (!$productoActual && !empty($sku)) {
        foreach ($productos as $p) {
            if (isset($p['sku']) && (string)$p['sku'] === $sku) {
                $productoActual = $p;
                break;
            }
        }
    }
}

// Variables por defecto
$title = "Catálogo de Productos | Building Systems Perú (BS Perú)";
$description = "Encuentra los mejores aditivos, impermeabilizantes y productos químicos para la construcción en BS Perú. Catálogo oficial.";
$image = "https://bsperu.pe/img/impermeabilizantes.jpg";
$url = "https://bsperu.pe/producto.html";
$jsonLd = "";

if ($productoActual) {
    $nombre = htmlspecialchars($productoActual['nombre'] ?? '');
    $title = $nombre . " | Z Aditivos Oficial - BS Perú";
    
    // Descripción truncada a ~160 caracteres
    $descRaw = $productoActual['descripcion_larga'] ?? ($productoActual['descripcion'] ?? $description);
    $descRaw = strip_tags($descRaw);
    $description = htmlspecialchars(mb_substr($descRaw, 0, 160) . (mb_strlen($descRaw) > 160 ? '...' : ''));

    // Imagen
    if (isset($productoActual['imagen'])) {
        $img = $productoActual['imagen'];
        if (strpos($img, 'http') !== 0) {
            if (strpos($img, '/') !== 0) {
                $img = '/' . $img;
            }
            $image = "https://bsperu.pe" . $img;
        } else {
            $image = $img;
        }
    }

    $canonicalSlug = !empty($productoActual['slug']) ? $productoActual['slug'] : slugify_val($productoActual['nombre'] ?? ($sku ?: 'catalogo'));
    $url = "https://bsperu.pe/producto/" . urlencode($canonicalSlug);
    
    // Generar JSON-LD estático
    $prodSku = $productoActual['sku'] ?? $sku;
    $prodPrice = "0.00";
    if (isset($productoActual['precio']) && is_numeric($productoActual['precio']) && $productoActual['precio'] > 0) {
        $prodPrice = number_format($productoActual['precio'], 2, '.', '');
    } elseif (isset($productoActual['presentaciones']) && is_array($productoActual['presentaciones'])) {
        foreach ($productoActual['presentaciones'] as $pres) {
            if (isset($pres['precio']) && is_numeric($pres['precio']) && $pres['precio'] > 0) {
                $prodPrice = number_format($pres['precio'], 2, '.', '');
                break;
            }
        }
    }

    $jsonLdArr = [
        "@context" => "https://schema.org/",
        "@type" => "Product",
        "name" => $nombre,
        "image" => [$image],
        "description" => $description,
        "sku" => $prodSku,
        "mpn" => $prodSku,
        "brand" => [
            "@type" => "Brand",
            "name" => "Z Aditivos"
        ],
        "category" => $productoActual['categoria'] ?? "Soluciones Químicas para la Construcción",
        "offers" => [
            "@type" => "Offer",
            "url" => $url,
            "priceCurrency" => "PEN",
            "price" => $prodPrice,
            "priceValidUntil" => "2027-12-31",
            "itemCondition" => "https://schema.org/NewCondition",
            "availability" => "https://schema.org/InStock",
            "seller" => [
                "@type" => "Organization",
                "name" => "Building Systems Perú",
                "url" => "https://bsperu.pe"
            ]
        ],
        "aggregateRating" => [
            "@type" => "AggregateRating",
            "ratingValue" => "4.9",
            "reviewCount" => "18",
            "bestRating" => "5",
            "worstRating" => "1"
        ]
    ];
    $jsonLd = json_encode($jsonLdArr, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}
?>
<!DOCTYPE html>
<html lang="es" data-theme="corporate">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="/">
    <title id="pageTitle"><?php echo $title; ?></title>
    <meta name="description" id="metaDescription" content="<?php echo $description; ?>">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <link rel="canonical" id="canonicalLink" href="<?php echo $url; ?>">
    <link rel="icon" href="/favicon.ico">

    <!-- Open Graph / Facebook / WhatsApp -->
    <meta property="og:type" content="product">
    <meta property="og:locale" content="es_PE">
    <meta property="og:site_name" content="Building Systems Perú (BS Perú)">
    <meta property="og:title" id="ogTitle" content="<?php echo $title; ?>">
    <meta property="og:description" id="ogDesc" content="<?php echo $description; ?>">
    <meta property="og:url" id="ogUrl" content="<?php echo $url; ?>">
    <meta property="og:image" id="ogImage" content="<?php echo $image; ?>">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" id="twTitle" content="<?php echo $title; ?>">
    <meta name="twitter:description" id="twDesc" content="<?php echo $description; ?>">
    <meta name="twitter:image" id="twImage" content="<?php echo $image; ?>">

    <!-- Schema.org BreadcrumbList -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "BreadcrumbList",
      "itemListElement": [
        { "@type": "ListItem", "position": 1, "name": "Inicio", "item": "https://bsperu.pe/" },
        { "@type": "ListItem", "position": 2, "name": "Catálogo", "item": "https://bsperu.pe/productos.html" },
        { "@type": "ListItem", "position": 3, "name": "Producto", "item": "<?php echo $url; ?>" }
      ]
    }
    </script>

    <!-- Schema.org JSON-LD para Producto Dinámico (SSR) -->
    <?php if ($jsonLd): ?>
    <script type="application/ld+json" id="productSchemaJson">
    <?php echo $jsonLd; ?>
    </script>
    <?php else: ?>
    <script type="application/ld+json" id="productSchemaJson"></script>
    <?php endif; ?>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/fill/style.css">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --radius-sm: 12px;
            --radius-md: 20px;
            --radius-lg: 30px;
            --radius-xl: 40px;
            --maxw: 1240px;
        }

        /* 1. MOCHA GOLD (Default) */
        [data-theme="mocha"] {
            --hero-img: url('img/banner_1.jpg');
            --bg-dark: #1a1410;
            --bg-medium: #2a2118;
            --bg-card: #332a20;
            --bg-card-hover: #3d3226;
            --accent: #c8a96e;
            --accent-light: #e0c992;
            --accent-glow: rgba(200, 169, 110, 0.2);
            --title-color: #f5f0e8;
            --text: #e8e0d4;
            --text-muted: #b3a595;
            --nav-bg: rgba(26, 20, 16, 0.72);
            --nav-bg-scroll: rgba(26, 20, 16, 0.96);
            --border-color: rgba(255,255,255,0.08);
            --placeholder-icon: rgba(200,169,110,0.35);
            --img-pad: #ffffff;
            --logo-plate: transparent;
        }

        /* 2. INDUSTRIAL */
        [data-theme="industrial"] {
            --hero-img: url('img/banner_1.jpg');
            --bg-dark: #0f172a;
            --bg-medium: #1e293b;
            --bg-card: #334155;
            --bg-card-hover: #475569;
            --accent: #f97316;
            --accent-light: #fb923c;
            --accent-glow: rgba(249, 115, 22, 0.2);
            --title-color: #ffffff;
            --text: #f8fafc;
            --text-muted: #a8b6c8;
            --nav-bg: rgba(15, 23, 42, 0.72);
            --nav-bg-scroll: rgba(15, 23, 42, 0.96);
            --border-color: rgba(255,255,255,0.08);
            --placeholder-icon: rgba(249,115,22,0.35);
            --img-pad: #ffffff;
            --logo-plate: transparent;
        }

        /* 3. ECOLOGICO */
        [data-theme="eco"] {
            --hero-img: url('img/banner_1.jpg');
            --bg-dark: #062412;
            --bg-medium: #0d361c;
            --bg-card: #154728;
            --bg-card-hover: #1e5935;
            --accent: #4ade80;
            --accent-light: #86efac;
            --accent-glow: rgba(74, 222, 128, 0.2);
            --title-color: #ffffff;
            --text: #f0fdf4;
            --text-muted: #9dd6b1;
            --nav-bg: rgba(6, 36, 18, 0.72);
            --nav-bg-scroll: rgba(6, 36, 18, 0.96);
            --border-color: rgba(255,255,255,0.08);
            --placeholder-icon: rgba(74,222,128,0.35);
            --img-pad: #ffffff;
            --logo-plate: transparent;
        }

        /* 4. HIGH-TECH */
        [data-theme="hightech"] {
            --hero-img: url('img/banner_1.jpg');
            --bg-dark: #000000;
            --bg-medium: #111111;
            --bg-card: #1f1f1f;
            --bg-card-hover: #2e2e2e;
            --accent: #06b6d4;
            --accent-light: #22d3ee;
            --accent-glow: rgba(6, 182, 212, 0.2);
            --title-color: #ffffff;
            --text: #fafafa;
            --text-muted: #b0b0b0;
            --nav-bg: rgba(0, 0, 0, 0.72);
            --nav-bg-scroll: rgba(0, 0, 0, 0.96);
            --border-color: rgba(255,255,255,0.08);
            --placeholder-icon: rgba(6,182,212,0.35);
            --img-pad: #ffffff;
            --logo-plate: transparent;
        }

        /* 5. CORPORATIVO (Light Mode) */
        [data-theme="corporate"] {
            --hero-img: url('img/banner_1.jpg');
            --bg-dark: #f8fafc;
            --bg-medium: #f1f5f9;
            --bg-card: #ffffff;
            --bg-card-hover: #eef2f7;
            --accent: #2563eb;
            --accent-light: #3b82f6;
            --accent-glow: rgba(37, 99, 235, 0.15);
            --title-color: #0f172a;
            --text: #1e293b;
            --text-muted: #52627a;
            --nav-bg: rgba(248, 250, 252, 0.8);
            --nav-bg-scroll: rgba(248, 250, 252, 0.97);
            --border-color: rgba(15,23,42,0.12);
            --placeholder-icon: rgba(37,99,235,0.35);
            --img-pad: #ffffff;
            --logo-plate: #12233d;
        }

        html { scroll-behavior: smooth; scroll-padding-top: 90px; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-dark);
            color: var(--text);
            min-height: 100vh;
            overflow-x: hidden;
            transition: background 0.4s, color 0.4s;
        }
        body.no-scroll { overflow: hidden; }

        img { max-width: 100%; }

        /* Foco visible en todo lo interactivo */
        a:focus-visible, button:focus-visible, select:focus-visible,
        input:focus-visible, textarea:focus-visible, [tabindex]:focus-visible {
            outline: 3px solid var(--accent);
            outline-offset: 3px;
            border-radius: 4px;
        }

        .skip-link {
            position: absolute; left: -9999px; top: 0; z-index: 3000;
            background: var(--accent); color: var(--bg-dark);
            padding: 12px 20px; font-weight: 700; border-radius: 0 0 8px 0;
        }
        .skip-link:focus { left: 0; }

        .sr-only {
            position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px;
            overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0;
        }

        .shell { max-width: var(--maxw); margin: 0 auto; padding: 0 24px; }

        .section-head { text-align: center; margin-bottom: 40px; }
        .section-head .eyebrow {
            display: inline-flex; align-items: center; gap: 8px;
            font-size: 11px; letter-spacing: 3px; text-transform: uppercase;
            color: var(--accent); font-weight: 700; margin-bottom: 12px;
        }
        .section-head h2 {
            font-size: clamp(26px, 3.6vw, 40px); font-weight: 900;
            text-transform: uppercase; letter-spacing: -0.5px;
            color: var(--title-color); line-height: 1.1;
        }
        .section-head h2 span { color: var(--accent); }
        .section-head p {
            color: var(--text-muted); font-size: 15px; margin-top: 12px;
            max-width: 620px; margin-left: auto; margin-right: auto; line-height: 1.6;
        }

        /* ============ NAVBAR ============ */
        .navbar {
            position: fixed; top: 0; left: 0; right: 0; z-index: 100;
            display: flex; align-items: center; gap: 20px;
            padding: 12px 28px;
            background: var(--nav-bg);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border-bottom: 1px solid transparent;
            transition: background 0.3s, border-color 0.3s;
        }
        .navbar.scrolled { background: var(--nav-bg-scroll); border-bottom-color: var(--border-color); }

        .nav-logo { display: flex; align-items: center; flex-shrink: 0; }
        /* Todos los logos de BS son claros: en el tema corporativo necesitan placa oscura */
        .brand-logo { background: var(--logo-plate); border-radius: 10px; padding: 4px 10px; transition: background 0.4s; }
        .nav-logo img { height: 46px; width: auto; object-fit: contain; }

        .nav-links { display: flex; gap: 26px; list-style: none; margin-left: auto; }
        .nav-links a {
            color: var(--text-muted); text-decoration: none; font-size: 13px;
            font-weight: 600; letter-spacing: 0.8px; text-transform: uppercase;
            transition: color 0.3s; white-space: nowrap;
        }
        .nav-links a:hover, .nav-links a.active { color: var(--title-color); }

        .nav-actions { display: flex; align-items: center; gap: 12px; margin-left: 20px; }
        .nav-cta {
            background: var(--accent); color: var(--bg-dark);
            padding: 11px 22px; border-radius: var(--radius-sm);
            font-weight: 700; font-size: 13px; text-decoration: none;
            transition: all 0.3s; white-space: nowrap;
            display: inline-flex; align-items: center; gap: 7px;
        }
        .nav-cta:hover { background: var(--accent-light); transform: translateY(-2px); }

        .nav-hamburger {
            display: none; background: none; border: 1px solid var(--border-color);
            color: var(--text); font-size: 24px; cursor: pointer;
            width: 44px; height: 44px; border-radius: 12px;
            align-items: center; justify-content: center; margin-left: auto;
        }

        /* Drawer móvil */
        .drawer-overlay {
            position: fixed; inset: 0; z-index: 200;
            background: rgba(0,0,0,0.6);
            opacity: 0; pointer-events: none; transition: opacity 0.3s;
        }
        .drawer-overlay.open { opacity: 1; pointer-events: all; }
        .drawer {
            position: fixed; top: 0; right: 0; bottom: 0; z-index: 201;
            width: min(320px, 85vw);
            background: var(--bg-medium);
            border-left: 1px solid var(--border-color);
            transform: translateX(100%); transition: transform 0.35s cubic-bezier(0.4,0,0.2,1);
            display: flex; flex-direction: column; padding: 20px;
            overflow-y: auto;
        }
        .drawer.open { transform: translateX(0); }
        .drawer-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
        .drawer-head img { height: 42px; }
        .drawer-close {
            background: none; border: 1px solid var(--border-color); color: var(--text);
            width: 40px; height: 40px; border-radius: 10px; font-size: 22px; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
        }
        .drawer-nav { list-style: none; display: flex; flex-direction: column; gap: 4px; }
        .drawer-nav a {
            display: flex; align-items: center; gap: 12px;
            padding: 14px 12px; border-radius: 10px;
            color: var(--text); text-decoration: none; font-size: 15px; font-weight: 600;
            transition: background 0.25s;
        }
        .drawer-nav a:hover { background: var(--bg-card); }
        .drawer-nav a i { color: var(--accent); font-size: 20px; }
        .drawer-foot { margin-top: auto; padding-top: 24px; display: flex; flex-direction: column; gap: 10px; }

        /* ============ HERO ============ */
        .hero {
            position: relative;
            min-height: 88vh;
            display: flex; align-items: center;
            overflow: hidden;
            padding: 130px 0 80px;
        }
        .hero-bg {
            position: absolute; inset: 0;
            background-image: var(--hero-img);
            background-color: var(--bg-medium);
            background-size: cover;
            background-position: 70% center;
            transition: background-image 0.4s, background-color 0.4s;
        }
        .hero-bg::after {
            content: ''; position: absolute; inset: 0;
            background: linear-gradient(90deg,
                rgba(10, 16, 26, 0.96) 0%,
                rgba(10, 16, 26, 0.92) 22%,
                rgba(10, 16, 26, 0.65) 32%,
                rgba(10, 16, 26, 0.15) 40%,
                rgba(10, 16, 26, 0) 46%
            );
        }
        .hero .shell {
            position: relative; z-index: 2;
            max-width: 100%;
            margin: 0;
            padding-left: clamp(24px, 5vw, 68px);
            padding-right: 24px;
        }
        .hero-content {
            max-width: 510px;
            position: relative;
        }
        .hero-content::before {
            content: '';
            position: absolute;
            top: -60px;
            left: -60px;
            width: 480px;
            height: 480px;
            background: radial-gradient(circle, var(--accent-glow) 0%, transparent 68%);
            pointer-events: none;
            z-index: -1;
            border-radius: 50%;
            animation: heroGlowPulse 7s ease-in-out infinite alternate;
        }
        @keyframes heroGlowPulse {
            0% { transform: scale(0.92); opacity: 0.3; }
            100% { transform: scale(1.18); opacity: 0.65; }
        }

        @keyframes heroFadeSlide {
            from {
                opacity: 0;
                transform: translateY(22px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        @keyframes lineExpand {
            from { width: 0; opacity: 0; }
            to { width: 34px; opacity: 1; }
        }

        .hero-tag {
            display: inline-flex; align-items: center; gap: 10px;
            font-size: 11px; letter-spacing: 3px; text-transform: uppercase;
            color: rgba(255,255,255,0.92); margin-bottom: 18px; font-weight: 700;
            animation: heroFadeSlide 0.7s cubic-bezier(0.16, 1, 0.3, 1) 0.05s both;
        }
        .hero-tag::before {
            content: ''; width: 34px; height: 2px; background: var(--accent);
            animation: lineExpand 0.7s cubic-bezier(0.16, 1, 0.3, 1) 0.15s both;
        }
        .hero h1 {
            font-size: clamp(32px, 4.4vw, 56px);
            font-weight: 900; line-height: 1.05;
            color: #fff; margin-bottom: 18px;
            text-transform: uppercase; letter-spacing: -1.2px;
            text-shadow: 0 2px 20px rgba(0,0,0,0.65);
            animation: heroFadeSlide 0.8s cubic-bezier(0.16, 1, 0.3, 1) 0.18s both;
        }
        .hero h1 span { color: var(--accent); }
        .hero-desc {
            font-size: 15.5px; color: rgba(255,255,255,0.9); line-height: 1.65;
            margin-bottom: 28px; max-width: 480px;
            text-shadow: 0 1px 8px rgba(0,0,0,0.7);
            animation: heroFadeSlide 0.8s cubic-bezier(0.16, 1, 0.3, 1) 0.28s both;
        }
        .hero-btns {
            display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 34px;
            animation: heroFadeSlide 0.8s cubic-bezier(0.16, 1, 0.3, 1) 0.38s both;
        }
        .btn-hero-primary {
            display: inline-flex; align-items: center; gap: 10px;
            background: var(--accent); color: var(--bg-dark);
            padding: 14px 26px; border-radius: var(--radius-lg);
            font-size: 14.5px; font-weight: 700; text-decoration: none;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            border: none; cursor: pointer; font-family: inherit;
        }
        .btn-hero-primary:hover { background: var(--accent-light); transform: translateY(-3px); }
        .btn-hero-ghost {
            display: inline-flex; align-items: center; gap: 10px;
            background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.35);
            padding: 14px 26px; border-radius: var(--radius-lg);
            color: #fff; font-size: 14.5px; font-weight: 600;
            cursor: pointer; transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); text-decoration: none;
            backdrop-filter: blur(10px); font-family: inherit;
        }
        .btn-hero-ghost:hover { background: rgba(255,255,255,0.22); border-color: var(--accent); transform: translateY(-2px); }

        /* Barra de datos reales del catálogo */
        .hero-stats {
            display: flex; flex-wrap: wrap; gap: 10px; max-width: 490px;
            animation: heroFadeSlide 0.8s cubic-bezier(0.16, 1, 0.3, 1) 0.48s both;
        }
        .hero-stat {
            background: rgba(0,0,0,0.52); border: 1px solid rgba(255,255,255,0.22);
            backdrop-filter: blur(12px);
            border-radius: var(--radius-sm); padding: 11px 16px; min-width: 110px;
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.3s, background 0.3s;
        }
        .hero-stat:hover {
            transform: translateY(-3px);
            border-color: var(--accent);
            background: rgba(0,0,0,0.72);
        }
        .hero-stat .num { font-size: 22px; font-weight: 900; color: var(--accent); line-height: 1; }
        .hero-stat .lbl { font-size: 10px; letter-spacing: 1.2px; text-transform: uppercase; color: rgba(255,255,255,0.85); margin-top: 5px; font-weight: 700; }

        /* ============ FAMILIAS ============ */
        .familias-section { padding: 80px 0 40px; }
        .familias-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
        .familia-card {
            background: var(--bg-card); border: 1px solid var(--border-color);
            border-radius: var(--radius-md); padding: 24px 20px;
            display: flex; flex-direction: column; gap: 11px;
            cursor: pointer; text-align: left;
            transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1),
                        box-shadow 0.35s cubic-bezier(0.16, 1, 0.3, 1),
                        border-color 0.3s ease, background 0.3s ease;
            font-family: inherit; color: var(--text);
            position: relative;
        }
        .familia-card:hover {
            background: var(--bg-card-hover); transform: translateY(-6px);
            border-color: var(--accent);
            box-shadow: 0 16px 36px var(--accent-glow), 0 4px 14px rgba(0,0,0,0.12);
        }
        .familia-card.active { border-color: var(--accent); background: var(--bg-card-hover); }
        .familia-card .icon-box {
            width: 50px; height: 50px; border-radius: var(--radius-sm);
            background: var(--accent); color: var(--bg-dark);
            display: flex; align-items: center; justify-content: center; font-size: 26px;
            transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .familia-card:hover .icon-box {
            transform: scale(1.1) rotate(4deg);
        }
        .familia-card h3 {
            font-size: 15px; font-weight: 800; color: var(--title-color);
            text-transform: uppercase; letter-spacing: 0.4px; line-height: 1.25;
        }
        .familia-card p { font-size: 12.5px; color: var(--text-muted); line-height: 1.5; }
        .familia-card .count {
            margin-top: auto; padding-top: 6px;
            font-size: 12px; font-weight: 700; color: var(--accent);
            display: inline-flex; align-items: center; gap: 6px;
        }

        /* ============ FILTROS ============ */
        .catalog-section { padding: 60px 0 90px; }

        .filter-bar {
            position: sticky; top: 74px; z-index: 50;
            background: var(--bg-medium);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 18px;
            margin-bottom: 22px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.18);
        }
        .filter-row { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 12px; align-items: end; }
        .field label {
            display: flex; align-items: center; gap: 6px;
            font-size: 10.5px; text-transform: uppercase; letter-spacing: 1.2px;
            color: var(--text-muted); font-weight: 700; margin-bottom: 7px;
        }
        .field input, .field select {
            width: 100%; font-family: inherit; font-size: 14px; font-weight: 600;
            color: var(--text); background: var(--bg-dark);
            border: 1px solid var(--border-color); border-radius: 10px;
            padding: 12px 14px; outline: none; transition: border-color 0.25s;
        }
        .field input::placeholder { color: var(--text-muted); font-weight: 400; }
        .field input:focus, .field select:focus { border-color: var(--accent); }
        .field select option { background: var(--bg-dark); color: var(--text); }

        .filter-clear {
            background: transparent; color: var(--text-muted);
            border: 1px solid var(--border-color); border-radius: 10px;
            padding: 12px 16px; font-size: 13px; font-weight: 600; cursor: pointer;
            font-family: inherit; white-space: nowrap; transition: all 0.25s;
            display: inline-flex; align-items: center; gap: 7px;
        }
        .filter-clear:hover { color: var(--title-color); border-color: var(--accent); }

        .filter-extras {
            display: flex; align-items: center; justify-content: space-between;
            gap: 16px; flex-wrap: wrap; margin-top: 14px;
            padding-top: 14px; border-top: 1px solid var(--border-color);
        }
        .switch { display: inline-flex; align-items: center; gap: 9px; cursor: pointer; font-size: 13px; color: var(--text); font-weight: 600; }
        .switch input { width: 17px; height: 17px; accent-color: var(--accent); cursor: pointer; }
        .result-count { font-size: 13px; color: var(--text-muted); font-weight: 600; }
        .result-count strong { color: var(--accent); font-size: 15px; }

        /* Chips de subcategoría */
        .subcat-chips { display: flex; gap: 9px; flex-wrap: wrap; margin-bottom: 26px; }
        .chip {
            padding: 8px 16px; border-radius: var(--radius-lg);
            font-size: 12px; font-weight: 700; letter-spacing: 0.4px;
            cursor: pointer; font-family: inherit;
            color: var(--text-muted); background: var(--bg-card);
            border: 1px solid var(--border-color);
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .chip:hover {
            color: var(--title-color); border-color: var(--accent);
            transform: translateY(-2px); box-shadow: 0 4px 14px rgba(0,0,0,0.1);
        }
        .chip.active {
            color: var(--bg-dark); background: var(--accent);
            border-color: var(--accent); transform: translateY(-1px);
        }
        .chip .n { opacity: 0.65; margin-left: 5px; font-weight: 600; }

        /* ============ GRID DE PRODUCTOS ============ */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(255px, 1fr));
            gap: 20px;
        }
        .product-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            overflow: hidden;
            display: flex; flex-direction: column;
            transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1),
                        box-shadow 0.35s cubic-bezier(0.16, 1, 0.3, 1),
                        border-color 0.3s ease;
            will-change: transform;
        }
        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 42px var(--accent-glow), 0 6px 18px rgba(0,0,0,0.12);
            border-color: var(--accent);
        }
        .product-card.unavailable .p-img img { filter: grayscale(0.55); }

        .p-img {
            width: 100%; height: 210px; position: relative;
            background: var(--img-pad);
            display: flex; align-items: center; justify-content: center;
            overflow: hidden;
        }
        .p-img img {
            width: 100%; height: 100%; object-fit: contain; padding: 14px;
            transition: transform 0.45s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .product-card:hover .p-img img { transform: scale(1.08); }
        .p-img .fallback {
            display: none; flex-direction: column; align-items: center; gap: 8px;
            color: var(--placeholder-icon);
        }
        .p-img .fallback i { font-size: 54px; }
        .p-img .fallback span { font-size: 10px; letter-spacing: 1.5px; text-transform: uppercase; font-weight: 700; }
        .p-img.no-image { background: linear-gradient(145deg, var(--bg-medium), var(--bg-dark)); }
        .p-img.no-image img { display: none; }
        .p-img.no-image .fallback { display: flex; }

        .p-badge {
            position: absolute; top: 11px; left: 11px; z-index: 2;
            padding: 5px 12px; border-radius: 8px;
            font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.6px;
        }
        .p-badge.ok { background: var(--accent); color: var(--bg-dark); }
        .p-badge.off { background: #64748b; color: #fff; }

        .p-body { padding: 16px 18px; display: flex; flex-direction: column; gap: 8px; flex: 1; }
        .p-cat { font-size: 10px; font-weight: 800; letter-spacing: 1.2px; text-transform: uppercase; color: var(--accent); }
        .p-body h3 { font-size: 14.5px; font-weight: 800; color: var(--title-color); line-height: 1.32; }
        .p-body .p-desc { font-size: 12.5px; color: var(--text-muted); line-height: 1.55; }
        .p-meta {
            display: flex; gap: 14px; flex-wrap: wrap; margin-top: auto; padding-top: 10px;
            font-size: 11px; color: var(--text-muted); font-weight: 600;
        }
        .p-meta span { display: inline-flex; align-items: center; gap: 5px; }

        .p-actions { display: flex; gap: 8px; padding: 0 18px 18px; }
        .p-btn {
            flex: 1; min-width: 0;
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
            padding: 10px; border-radius: 10px;
            font-size: 12px; font-weight: 700; cursor: pointer;
            text-decoration: none; font-family: inherit;
            border: 1px solid var(--border-color);
            background: transparent; color: var(--text);
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1); white-space: nowrap;
        }
        .p-btn:hover { border-color: var(--accent); color: var(--accent); transform: translateY(-2px); }
        .p-btn.primary { background: var(--accent); color: var(--bg-dark); border-color: var(--accent); }
        .p-btn.primary:hover { background: var(--accent-light); color: var(--bg-dark); }
        .p-btn.icon-only { flex: 0 0 42px; min-width: 42px; font-size: 16px; }

        .empty-state { text-align: center; padding: 70px 20px; color: var(--text-muted); grid-column: 1 / -1; }
        .empty-state i { font-size: 60px; color: var(--placeholder-icon); margin-bottom: 16px; display: block; }
        .empty-state h3 { font-size: 20px; color: var(--title-color); margin-bottom: 8px; font-weight: 800; }
        .empty-state p { font-size: 14px; margin-bottom: 20px; }

        .load-more-wrap { text-align: center; margin-top: 40px; }

        .sk-card {
            height: 380px; border-radius: var(--radius-md);
            background: linear-gradient(100deg, var(--bg-card) 30%, var(--bg-card-hover) 50%, var(--bg-card) 70%);
            background-size: 240% 100%;
            animation: shimmer 1.3s linear infinite;
        }
        @keyframes shimmer { from { background-position: 240% 0; } to { background-position: -40% 0; } }

        /* ============ MODAL DETALLE ============ */
        .modal-overlay {
            position: fixed; inset: 0; z-index: 500;
            background: rgba(0,0,0,0.8);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            display: flex; align-items: center; justify-content: center; padding: 20px;
            opacity: 0; pointer-events: none;
            transition: opacity 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .modal-overlay.open { opacity: 1; pointer-events: all; }
        .modal {
            background: var(--bg-medium); border: 1px solid var(--border-color);
            border-radius: var(--radius-md); width: 100%; max-width: 920px;
            max-height: 90vh; overflow-y: auto; position: relative;
            box-shadow: 0 25px 60px rgba(0,0,0,0.45);
            transform: translateY(28px) scale(0.96);
            transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .modal-overlay.open .modal { transform: translateY(0) scale(1); }
        .modal-close {
            position: absolute; top: 14px; right: 14px; z-index: 3;
            width: 40px; height: 40px; border-radius: 50%;
            background: var(--bg-dark); color: var(--text);
            border: 1px solid var(--border-color); font-size: 22px; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
        }
        .modal-close:hover { background: var(--accent); color: var(--bg-dark); }
        .modal-grid { display: grid; grid-template-columns: 1fr 1fr; }
        .modal-media { background: var(--img-pad); padding: 24px; display: flex; flex-direction: column; gap: 14px; }
        .modal-media .main-img { flex: 1; min-height: 280px; display: flex; align-items: center; justify-content: center; }
        .modal-media .main-img img { max-height: 320px; object-fit: contain; }
        .modal-media .main-img .fallback { color: #cbd5e1; text-align: center; }
        .modal-media .main-img .fallback i { font-size: 72px; }
        .modal-media .main-img .fallback span { display: block; font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase; font-weight: 700; margin-top: 8px; }
        .modal-thumbs { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
        .modal-thumbs button {
            width: 58px; height: 58px; border-radius: 10px; padding: 4px;
            background: #fff; border: 2px solid #e2e8f0; cursor: pointer; overflow: hidden;
        }
        .modal-thumbs button.active { border-color: var(--accent); }
        .modal-thumbs img { width: 100%; height: 100%; object-fit: contain; }

        .modal-info { padding: 34px 30px; display: flex; flex-direction: column; gap: 13px; }
        .modal-info .m-cat { font-size: 10.5px; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase; color: var(--accent); }
        .modal-info h2 { font-size: 23px; font-weight: 900; color: var(--title-color); line-height: 1.22; }
        .modal-info .m-short { font-size: 14px; color: var(--text); font-weight: 600; }
        .modal-info .m-long { font-size: 13.5px; color: var(--text-muted); line-height: 1.7; }
        .modal-specs { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-top: 4px; }
        .spec { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 10px; padding: 11px 14px; }
        .spec .k { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); font-weight: 700; }
        .spec .v { font-size: 14px; font-weight: 800; color: var(--title-color); margin-top: 3px; word-break: break-word; }
        .modal-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: auto; padding-top: 10px; }
        .modal-actions .p-btn { flex: 1 1 140px; padding: 13px 14px; font-size: 13px; }

        /* ============ SLIDER DESTACADOS ============ */
        .ad-section { padding: 80px 0; background: var(--bg-medium); border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); }
        .slider-container {
            display: flex; overflow-x: auto; scroll-snap-type: x mandatory;
            gap: 22px; padding: 8px 4px 22px;
            scrollbar-width: none; -webkit-overflow-scrolling: touch;
        }
        .slider-container::-webkit-scrollbar { display: none; }
        .slider-item { flex: 0 0 calc((100% - 44px) / 3); scroll-snap-align: center; perspective: 1200px; }
        .tilt-card {
            width: 100%; border-radius: var(--radius-md);
            box-shadow: 0 26px 55px rgba(0,0,0,0.45);
            transform-style: preserve-3d; position: relative;
            border: 1px solid var(--border-color);
            transition: transform 0.25s ease-out;
            overflow: hidden;
        }
        .tilt-card img { width: 100%; border-radius: var(--radius-md); display: block; }
        .glare {
            position: absolute; inset: 0; border-radius: var(--radius-md);
            pointer-events: none; opacity: 0;
            background: radial-gradient(circle at 50% 50%, rgba(255,255,255,0.25) 0%, transparent 60%);
            transition: opacity 0.3s;
        }
        .slider-indicators { display: flex; justify-content: center; gap: 10px; }
        .indicator {
            width: 11px; height: 11px; border: none; padding: 0;
            background: var(--text-muted); border-radius: 50%;
            transition: all 0.3s; cursor: pointer; opacity: 0.5;
        }
        .indicator.active { background: var(--accent); opacity: 1; width: 30px; border-radius: 6px; }

        /* ============ CONSULTA WHATSAPP ============ */
        .consult-section {
            padding: 80px 0;
            background: linear-gradient(135deg, var(--bg-card) 0%, var(--bg-medium) 100%);
            border-bottom: 1px solid var(--border-color);
        }
        .consult-box { max-width: 720px; margin: 0 auto; text-align: center; }
        .consult-box textarea {
            width: 100%; padding: 16px; border-radius: var(--radius-sm);
            border: 1px solid var(--border-color); background: var(--bg-dark);
            color: var(--text); font-family: inherit; font-size: 15px;
            resize: vertical; outline: none; transition: border-color 0.3s; margin-bottom: 16px;
        }
        .consult-box textarea:focus { border-color: var(--accent); }
        .consult-hint { font-size: 12px; color: var(--text-muted); margin-top: 14px; }
        .consult-feedback { display: none; margin-top: 18px; font-size: 15px; font-weight: 700; }
        .consult-feedback.show { display: block; }
        .consult-feedback.ok { color: var(--accent); }
        .consult-feedback.err { color: #ef5350; }

        /* ============ CTA + FOOTER ============ */
        .cta-section { padding: 80px 0; }
        .cta-box {
            background: linear-gradient(135deg, var(--bg-card), var(--accent-glow));
            border: 1px solid var(--border-color);
            border-radius: var(--radius-xl);
            padding: 60px 40px; text-align: center;
            position: relative; overflow: hidden;
        }
        .cta-box::before {
            content: ''; position: absolute; top: -90px; right: -90px;
            width: 240px; height: 240px;
            background: radial-gradient(circle, var(--accent-glow), transparent 70%);
            border-radius: 50%;
        }
        .cta-box h2 { font-size: clamp(22px, 3vw, 32px); font-weight: 900; margin-bottom: 12px; position: relative; color: var(--title-color); }
        .cta-box p { color: var(--text-muted); margin-bottom: 30px; max-width: 520px; margin-inline: auto; position: relative; line-height: 1.6; }
        .cta-buttons { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; position: relative; }
        .btn-gold {
            background: var(--accent); color: var(--bg-dark);
            padding: 15px 30px; border-radius: var(--radius-sm); border: none;
            font-size: 14px; font-weight: 700; cursor: pointer;
            text-decoration: none; display: inline-flex; align-items: center; gap: 9px;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); font-family: inherit;
        }
        .btn-gold:hover { background: var(--accent-light); transform: translateY(-3px); }
        .btn-outline {
            background: transparent; color: var(--text);
            padding: 15px 30px; border-radius: var(--radius-sm);
            border: 1px solid var(--border-color);
            font-size: 14px; font-weight: 600; cursor: pointer;
            text-decoration: none; display: inline-flex; align-items: center; gap: 9px;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); font-family: inherit;
        }
        .btn-outline:hover { border-color: var(--accent); color: var(--accent); transform: translateY(-2px); }

        /* ============ BOTONES SHIMMER (Haz de luz) ============ */
        .btn-hero-primary, .btn-gold, .nav-cta, .p-btn.primary {
            position: relative;
            overflow: hidden;
        }
        .btn-hero-primary::after, .btn-gold::after, .nav-cta::after, .p-btn.primary::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -80%;
            width: 45%;
            height: 200%;
            background: linear-gradient(
                60deg,
                transparent,
                rgba(255, 255, 255, 0.38),
                transparent
            );
            transform: rotate(25deg);
            pointer-events: none;
        }
        .btn-hero-primary:hover::after, .btn-gold:hover::after, .nav-cta:hover::after, .p-btn.primary:hover::after {
            animation: btnShine 0.85s ease-in-out forwards;
        }
        @keyframes btnShine {
            0% { left: -80%; }
            100% { left: 140%; }
        }

        .footer { border-top: 1px solid var(--border-color); padding: 60px 0 40px; }
        .footer-grid { display: grid; grid-template-columns: 1.4fr 1fr 1fr; gap: 40px; margin-bottom: 40px; }
        .footer-brand img { height: 54px; margin-bottom: 16px; }
        .footer-col h4 {
            font-size: 12px; text-transform: uppercase; letter-spacing: 1.5px;
            color: var(--title-color); font-weight: 800; margin-bottom: 16px;
        }
        .footer p, .footer li { color: var(--text-muted); font-size: 13px; line-height: 1.8; }
        .footer-col ul { list-style: none; }
        .footer-col a { color: var(--text-muted); text-decoration: none; font-size: 13px; transition: color 0.3s; }
        .footer-col a:hover { color: var(--accent); }
        .footer-social { display: flex; gap: 11px; margin-top: 18px; }
        .footer-social a {
            width: 40px; height: 40px; border-radius: 11px;
            background: var(--bg-card); border: 1px solid var(--border-color);
            display: flex; align-items: center; justify-content: center;
            color: var(--text-muted); font-size: 19px; text-decoration: none; transition: all 0.3s;
        }
        .footer-social a:hover { background: var(--accent); color: var(--bg-dark); border-color: var(--accent); transform: translateY(-2px); }
        .footer-bottom {
            border-top: 1px solid var(--border-color); padding-top: 24px;
            text-align: center; font-size: 12px; color: var(--text-muted);
        }

        /* ============ WHATSAPP FLOTANTE ============ */
        .wa-float {
            position: fixed; bottom: 30px; left: 30px; z-index: 900;
            width: 56px; height: 56px; border-radius: 50%;
            background: #25d366; color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 30px; text-decoration: none;
            box-shadow: 0 10px 26px rgba(0,0,0,0.35);
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.3s;
        }
        .wa-float:hover {
            transform: scale(1.12);
            box-shadow: 0 14px 34px rgba(37, 211, 102, 0.45);
        }
        .wa-float::before {
            content: '';
            position: absolute;
            inset: -5px;
            border-radius: 50%;
            border: 2px solid #25d366;
            opacity: 0.85;
            animation: waRadar 2.5s cubic-bezier(0.2, 0.8, 0.4, 1) infinite;
            pointer-events: none;
        }
        @keyframes waRadar {
            0% { transform: scale(0.95); opacity: 0.85; }
            70% { transform: scale(1.35); opacity: 0; }
            100% { transform: scale(1.35); opacity: 0; }
        }

        /* ============ ANIMACIONES ============ */
        .fade-up { opacity: 0; transform: translateY(22px); transition: opacity 0.6s ease, transform 0.6s ease; }
        .fade-up.visible { opacity: 1; transform: translateY(0); }

        @keyframes cardEntrance {
            from {
                opacity: 0;
                transform: translateY(22px) scale(0.97);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        .product-card.card-entrance {
            animation: cardEntrance 0.45s cubic-bezier(0.16, 1, 0.3, 1) both;
            animation-delay: calc(var(--i, 0) * 32ms);
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
            html { scroll-behavior: auto; }
            .fade-up { opacity: 1; transform: none; }
            .hero-tag, .hero h1, .hero-desc, .hero-btns, .hero-stats { animation: none !important; }
            .hero-tag::before { width: 34px !important; animation: none !important; }
            .hero-content::before { display: none; }
            .wa-float::before { display: none; }
            .product-card.card-entrance { animation: none !important; opacity: 1; transform: none; }
            .btn-hero-primary::after, .btn-gold::after, .nav-cta::after, .p-btn.primary::after { display: none; }
        }

        /* ============ RESPONSIVE ============ */
        @media (max-width: 1100px) {
            .familias-grid { grid-template-columns: repeat(3, 1fr); }
            .filter-row { grid-template-columns: 1fr 1fr; }
            .filter-row .field:first-child { grid-column: 1 / -1; }
            .filter-clear { grid-column: 1 / -1; justify-content: center; }
            .slider-item { flex: 0 0 calc((100% - 22px) / 2); }
        }
        @media (max-width: 980px) {
            .nav-links, .nav-actions { display: none; }
            .nav-hamburger { display: flex; }
            .modal-grid { grid-template-columns: 1fr; }
            .footer-grid { grid-template-columns: 1fr 1fr; }
            .footer-brand { grid-column: 1 / -1; }
        }
        @media (max-width: 760px) {
            .shell { padding: 0 18px; }
            .hero { min-height: auto; padding: 110px 0 60px; }
            .familias-grid { grid-template-columns: repeat(2, 1fr); }
            .slider-item { flex: 0 0 100%; }
            .filter-bar { position: static; }
            .modal-specs { grid-template-columns: 1fr; }
            .footer-grid { grid-template-columns: 1fr; text-align: center; }
            .footer-social { justify-content: center; }
            .wa-float { bottom: 22px; left: 22px; width: 50px; height: 50px; font-size: 26px; }
        }
        @media (max-width: 470px) {
            .familias-grid { grid-template-columns: 1fr; }
            .filter-row { grid-template-columns: 1fr; }
            .products-grid { grid-template-columns: 1fr; }
            .hero-btns .btn-hero-primary, .hero-btns .btn-hero-ghost { width: 100%; justify-content: center; }
        }
    /* ============ BREADCRUMB ============ */
        .breadcrumb-wrap {
            padding-top: 100px;
            padding-bottom: 16px;
        }
        .breadcrumb {
            display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
            font-size: 13px; color: var(--text-muted); list-style: none;
        }
        .breadcrumb a {
            color: var(--text-muted); text-decoration: none; transition: color 0.2s;
        }
        .breadcrumb a:hover { color: var(--accent); }
        .breadcrumb i { font-size: 11px; opacity: 0.6; }
        .breadcrumb .current { color: var(--title-color); font-weight: 600; }

        /* ============ PRODUCT LAYOUT (ESTILO Z ADITIVOS) ============ */
        .product-section {
            padding: 10px 0 50px;
        }

        /* TÍTULO PRINCIPAL (Como la imagen: GRANDE, NEGRO, BOLD ARRIBA) */
        .product-main-title {
            font-size: clamp(26px, 3.8vw, 40px);
            font-weight: 900;
            letter-spacing: -0.5px;
            text-transform: uppercase;
            color: #000000;
            line-height: 1.15;
            margin-bottom: 28px;
        }

        /* Grid superior de 2 columnas */
        .product-top-grid {
            display: grid;
            grid-template-columns: 380px 1fr;
            gap: 40px;
            align-items: start;
            margin-bottom: 40px;
        }

        /* Caja de imagen (Izquierda) */
        .product-media-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 24px;
            box-shadow: 0 4px 16px -2px rgba(0,0,0,0.04);
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .product-img-stage {
            width: 100%;
            height: 330px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        .product-img-stage img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            transition: transform 0.3s ease;
        }
        .product-img-stage:hover img {
            transform: scale(1.04);
        }

        /* Galería de miniaturas */
        .product-thumbnails {
            display: flex;
            gap: 10px;
            margin-top: 18px;
            width: 100%;
            justify-content: center;
        }
        .thumb-btn {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            border: 1.5px solid var(--border-color);
            background: #ffffff;
            padding: 4px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .thumb-btn img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .thumb-btn:hover, .thumb-btn.active {
            border-color: var(--accent);
            box-shadow: 0 0 0 2px var(--accent-glow);
        }

        /* Info derecha */
        .product-info-col {
            display: flex;
            flex-direction: column;
        }

        /* Subtítulo categoría (negrita como imagen) */
        .product-category-sub {
            font-size: 1.15rem;
            font-weight: 800;
            color: #000000;
            margin-bottom: 14px;
            letter-spacing: -0.2px;
        }

        /* Párrafo descripción */
        .product-desc-p {
            font-size: 1.02rem;
            line-height: 1.65;
            color: #334155;
            margin-bottom: 24px;
        }

        /* Badges de características destacadas */
        .product-highlights {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 26px;
        }
        .highlight-pill {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 6px 14px;
            font-size: 12.5px;
            font-weight: 600;
            color: #1e293b;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .highlight-pill i {
            color: var(--accent);
            font-size: 14px;
        }

        /* Presentaciones / Selector */
        .envases-selector-title {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            margin-bottom: 10px;
        }
        .envases-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 28px;
        }
        .chip-envase {
            border: 1.5px solid var(--border-color);
            background: #ffffff;
            border-radius: var(--radius-sm);
            padding: 10px 16px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .chip-envase .peso {
            font-size: 11px;
            font-weight: 500;
            color: var(--text-muted);
        }
        .chip-envase:hover, .chip-envase.active {
            border-color: var(--accent);
            background: #eff6ff;
            color: var(--accent);
        }
        .chip-envase.active .peso { color: var(--accent-light); }

        /* Botones de acción */
        .product-actions-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            align-items: center;
        }
        .btn-wa-cotizar {
            background: var(--wa-color);
            color: #ffffff;
            font-weight: 700;
            font-size: 14px;
            padding: 13px 26px;
            border-radius: var(--radius-sm);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 9px;
            transition: all 0.25s;
            box-shadow: 0 4px 14px rgba(37, 211, 102, 0.28);
        }
        .btn-wa-cotizar:hover {
            background: var(--wa-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(37, 211, 102, 0.35);
        }
        .btn-ficha-pdf {
            background: #ffffff;
            color: #1e293b;
            font-weight: 600;
            font-size: 13.5px;
            padding: 12px 20px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border-color);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        .btn-ficha-pdf:hover {
            border-color: #ef4444;
            color: #ef4444;
            background: #fff5f5;
        }
        .btn-ficha-pdf i { font-size: 17px; }

        /* ============ ACORDEÓN TÉCNICO (IGUAL A LA IMAGEN) ============ */
        .accordion-wrapper {
            border-top: 1px solid #e5e7eb;
            margin-top: 20px;
        }
        .accordion-item {
            border-bottom: 1px solid #e5e7eb;
        }
        .accordion-header {
            width: 100%;
            background: none;
            border: none;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 22px 0;
            cursor: pointer;
            text-align: left;
            font-family: inherit;
            color: #000000;
            transition: color 0.2s;
        }
        .accordion-header:hover {
            color: var(--accent);
        }
        .accordion-title {
            font-size: 1.15rem;
            font-weight: 800;
            letter-spacing: -0.2px;
        }
        .accordion-icon {
            font-size: 22px;
            font-weight: 400;
            line-height: 1;
            transition: transform 0.28s ease, color 0.2s;
            user-select: none;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
        }
        .accordion-item.active .accordion-icon {
            transform: rotate(45deg);
            color: var(--accent);
        }

        /* Contenido del acordeón */
        .accordion-body {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.35s cubic-bezier(0.4, 0, 0.2, 1), padding 0.3s ease;
            padding: 0 4px;
        }
        .accordion-item.active .accordion-body {
            max-height: 800px;
            padding: 0 4px 26px 4px;
        }
        .accordion-inner {
            font-size: 0.98rem;
            color: #334155;
            line-height: 1.7;
        }
        .accordion-inner ul {
            list-style: none;
            padding-left: 0;
        }
        .accordion-inner li {
            position: relative;
            padding-left: 24px;
            margin-bottom: 10px;
        }
        .accordion-inner li::before {
            content: "•";
            position: absolute;
            left: 6px;
            color: var(--accent);
            font-weight: bold;
            font-size: 18px;
            line-height: 1;
        }

        /* Tabla de especificaciones técnicas dentro del acordeón */
        .specs-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            border-radius: var(--radius-sm);
            overflow: hidden;
            border: 1px solid var(--border-color);
        }
        .specs-table tr:nth-child(even) {
            background: #f8fafc;
        }
        .specs-table td {
            padding: 12px 18px;
            font-size: 13.5px;
            border-bottom: 1px solid var(--border-color);
        }
        .specs-table td.spec-prop {
            font-weight: 700;
            color: #0f172a;
            width: 40%;
        }
        .specs-table td.spec-val {
            color: #475569;
        }

        /* Tarjeta de descarga de documentos */
        .doc-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 16px;
            margin-top: 10px;
        }
        .doc-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 14px;
            text-decoration: none;
            color: inherit;
            transition: all 0.25s;
        }
        .doc-card:hover {
            border-color: var(--accent);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.08);
        }
        .doc-icon {
            width: 42px;
            height: 42px;
            border-radius: 8px;
            background: #eff6ff;
            color: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
        }
        .doc-icon.pdf {
            background: #fef2f2;
            color: #dc2626;
        }
        .doc-meta h4 {
            font-size: 13.5px;
            font-weight: 700;
            color: var(--title-color);
            margin-bottom: 2px;
        }
        .doc-meta span {
            font-size: 11.5px;
            color: var(--text-muted);
        }

        /* Banner de asesoría / llamada a la acción */
        .product-cta-banner {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-radius: var(--radius-md);
            padding: 36px 40px;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 30px;
            margin-top: 60px;
            margin-bottom: 70px;
            box-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.15);
        }
        .product-cta-banner h3 {
            font-size: 1.4rem;
            font-weight: 800;
            margin-bottom: 6px;
        }
        .product-cta-banner p {
            color: #94a3b8;
            font-size: 14px;
            max-width: 540px;
            line-height: 1.5;
        }
        .btn-banner-wa {
            background: #ffffff;
            color: #0f172a;
            font-weight: 800;
            font-size: 13.5px;
            padding: 13px 24px;
            border-radius: var(--radius-sm);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
            transition: all 0.25s;
        }
        .btn-banner-wa:hover {
            background: var(--wa-color);
            color: #ffffff;
            transform: translateY(-2px);
        }

        /* ============ STACK FLOTANTE: REDES SOCIALES + WHATSAPP ============ */
        .floating-contact-stack {
            position: fixed;
            bottom: 26px;
            right: 26px;
            z-index: 999;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .floating-social-group {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }

        .social-float-btn {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 20px;
            text-decoration: none;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25);
            transition: transform 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275), box-shadow 0.25s;
            position: relative;
        }
        .social-float-btn:hover {
            transform: scale(1.15) translateX(-4px);
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.35);
        }

        /* Colores de marca */
        .social-float-btn.fb { background: #1877f2; }
        .social-float-btn.ig { background: radial-gradient(circle at 30% 107%, #fdf497 0%, #fdf497 5%, #fd5949 45%, #d6249f 60%, #285aeb 90%); }
        .social-float-btn.tk { background: #000000; border: 1.5px solid rgba(255,255,255,0.25); }
        .social-float-btn.yt { background: #ff0000; }
        .social-float-btn.li { background: #0077b5; }

        .social-float-btn .tooltip-label {
            position: absolute;
            right: 52px;
            background: #0f172a;
            color: #ffffff;
            font-size: 11px;
            font-weight: 600;
            padding: 4px 9px;
            border-radius: 6px;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transform: translateX(6px);
            transition: opacity 0.2s, transform 0.2s;
            box-shadow: 0 3px 8px rgba(0,0,0,0.25);
        }
        .social-float-btn:hover .tooltip-label {
            opacity: 1;
            transform: translateX(0);
        }

        .wa-float {
            position: relative;
            bottom: auto;
            right: auto;
            width: 58px;
            height: 58px;
            border-radius: 50%;
            background: var(--wa-color);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            text-decoration: none;
            box-shadow: 0 6px 20px rgba(37, 211, 102, 0.45);
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275), box-shadow 0.3s;
        }
        .wa-float:hover {
            transform: scale(1.12);
            box-shadow: 0 8px 26px rgba(37, 211, 102, 0.55);
        }
        .wa-float::before {
            content: '';
            position: absolute;
            inset: -4px;
            border-radius: 50%;
            border: 2px solid #25d366;
            opacity: 0.8;
            animation: waRadarPulse 2.4s cubic-bezier(0.2, 0.8, 0.4, 1) infinite;
            pointer-events: none;
        }
        @keyframes waRadarPulse {
            0% { transform: scale(0.95); opacity: 0.85; }
            70% { transform: scale(1.3); opacity: 0; }
            100% { transform: scale(1.3); opacity: 0; }
        }

        /* ============ FILA DE REDES SOCIALES EN FICHA ============ */
        .product-social-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 22px;
            padding-top: 14px;
            border-top: 1px dashed var(--border-color);
        }
        .social-row-label {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .social-row-badges {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .social-row-badge {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 17px;
            text-decoration: none;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .social-row-badge:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.18);
        }
        .social-row-badge.fb { background: #1877f2; }
        .social-row-badge.ig { background: radial-gradient(circle at 30% 107%, #fdf497 0%, #fdf497 5%, #fd5949 45%, #d6249f 60%, #285aeb 90%); }
        .social-row-badge.tk { background: #000000; }
        .social-row-badge.yt { background: #ff0000; }
        .social-row-badge.li { background: #0077b5; }

        /* Responsive */
        @media (max-width: 900px) {
            .product-top-grid {
                grid-template-columns: 1fr;
                gap: 28px;
            }
            .product-media-card {
                max-width: 440px;
                margin: 0 auto;
                width: 100%;
            }
            .product-cta-banner {
                flex-direction: column;
                text-align: center;
                padding: 30px 20px;
            }
            .btn-banner-wa { width: 100%; justify-content: center; }
            .footer-grid { grid-template-columns: 1fr; gap: 30px; }
            .nav-links, .nav-actions { display: none; }
            .nav-hamburger { display: flex; }
        }
    
</style>

</head>
<body>

    <!-- ============ NAVBAR ============ -->
    <nav class="navbar" id="mainNav" aria-label="Navegación principal">
        <a href="/" class="nav-logo" aria-label="BS Perú - Inicio">
            <img class="brand-logo" src="img/logo_bs.png" alt="BS Perú" width="200" height="46">
        </a>
        <ul class="nav-links">
            <li><a href="/">Inicio</a></li>
            <li><a href="/#familias">Familias</a></li>
            <li><a href="/productos.html">Catálogo</a></li>
            <li><a href="/sucursales.html">Sucursales</a></li>
            <li><a href="/#consulta">Asesoría</a></li>
            <li><a href="/#contacto">Contacto</a></li>
        </ul>
        <div class="nav-actions">
            <a href="#cotizar" class="nav-cta"><i class="ph ph-chat-circle-text" aria-hidden="true"></i> Cotizar Ahora</a>
        </div>
        <button class="nav-hamburger" id="hamburgerBtn" aria-label="Abrir menú" aria-expanded="false" aria-controls="mobileDrawer">
            <i class="ph ph-list" aria-hidden="true"></i>
        </button>
    </nav>

    <!-- Drawer móvil -->
    <div class="drawer-overlay" id="drawerOverlay"></div>
    <aside class="drawer" id="mobileDrawer" aria-label="Menú móvil" aria-hidden="true">
        <div class="drawer-head">
            <img class="brand-logo" src="img/logo_bs.png" alt="BS Perú">
            <button class="drawer-close" id="drawerCloseBtn" aria-label="Cerrar menú"><i class="ph ph-x" aria-hidden="true"></i></button>
        </div>
        <ul class="drawer-nav">
            <li><a href="/"><i class="ph ph-house" aria-hidden="true"></i> Inicio</a></li>
            <li><a href="/#familias"><i class="ph ph-squares-four" aria-hidden="true"></i> Familias de producto</a></li>
            <li><a href="/productos.html"><i class="ph ph-list-magnifying-glass" aria-hidden="true"></i> Catálogo completo</a></li>
            <li><a href="/sucursales.html"><i class="ph ph-storefront" aria-hidden="true"></i> Sucursales</a></li>
            <li><a href="/#consulta"><i class="ph ph-chats-circle" aria-hidden="true"></i> Asesoría técnica</a></li>
            <li><a href="/#contacto"><i class="ph ph-map-pin" aria-hidden="true"></i> Contacto</a></li>
        </ul>
        <div class="drawer-foot">
            <a href="#cotizar" class="btn-gold" style="justify-content:center;"><i class="ph ph-chat-circle-text" aria-hidden="true"></i> Cotizar Ahora</a>
        </div>
    </aside>


    <div class="shell breadcrumb-wrap">
        <nav aria-label="Ruta de navegación">
            <ul class="breadcrumb">
                <li><a href="/productos.html">Inicio</a></li>
                <li><i class="ph ph-caret-right" aria-hidden="true"></i></li>
                <li><a href="/productos.html#catalogo">Catálogo</a></li>
                <li><i class="ph ph-caret-right" aria-hidden="true"></i></li>
                <li><a href="/productos.html#familias" id="breadCategoria">Inhibidores de corrosión y removedores</a></li>
                <li><i class="ph ph-caret-right" aria-hidden="true"></i></li>
                <li class="current" id="breadProducto">Removedor de Óxido Z</li>
            </ul>
        </nav>
    </div>

    <!-- ============ CONTENIDO PRINCIPAL DEL PRODUCTO ============ -->
    <main class="shell product-section">
        
        <!-- TÍTULO PRINCIPAL (Exacto al diseño de la imagen) -->
        <h1 class="product-main-title" id="prodTitle">REMOVEDOR DE ÓXIDO Z</h1>

        <!-- SECCIÓN SUPERIOR: IMAGEN + DESCRIPCIÓN CORTA -->
        <div class="product-top-grid">
            
            <!-- Columna Izquierda: Imagen del producto y miniaturas -->
            <div class="product-media-card">
                <div class="product-img-stage" id="imgStage">
                    <img id="mainImg" src="/assets/img%20catalogo/REMOVEDOR%20DE%20OXIDO%20X%205%20GAL/REMOVEDOR%20DE%20OXIDO%20X%205%20GAL1.jpg" alt="Removedor de Óxido Z - Balde 5 Galones">
                </div>
                <div class="product-thumbnails" id="thumbsContainer">
                    <button class="thumb-btn active" data-src="/assets/img%20catalogo/REMOVEDOR%20DE%20OXIDO%20X%205%20GAL/REMOVEDOR%20DE%20OXIDO%20X%205%20GAL1.jpg" aria-label="Vista frontal">
                        <img src="/assets/img%20catalogo/REMOVEDOR%20DE%20OXIDO%20X%205%20GAL/REMOVEDOR%20DE%20OXIDO%20X%205%20GAL1.jpg" alt="">
                    </button>
                    <button class="thumb-btn" data-src="/assets/img%20catalogo/REMOVEDOR%20DE%20OXIDO%20X%205%20GAL/REMOVEDOR%20DE%20OXIDO%20X%205%20GAL2.jpg" aria-label="Vista lateral 1">
                        <img src="/assets/img%20catalogo/REMOVEDOR%20DE%20OXIDO%20X%205%20GAL/REMOVEDOR%20DE%20OXIDO%20X%205%20GAL2.jpg" alt="">
                    </button>
                    <button class="thumb-btn" data-src="/assets/img%20catalogo/REMOVEDOR%20DE%20OXIDO%20X%205%20GAL/REMOVEDOR%20DE%20OXIDO%20X%205%20GAL3.jpg" aria-label="Vista posterior">
                        <img src="/assets/img%20catalogo/REMOVEDOR%20DE%20OXIDO%20X%205%20GAL/REMOVEDOR%20DE%20OXIDO%20X%205%20GAL3.jpg" alt="">
                    </button>
                    <button class="thumb-btn" data-src="/assets/img%20catalogo/REMOVEDOR%20DE%20OXIDO%20X%205%20GAL/REMOVEDOR%20DE%20OXIDO%20X%205%20GAL4.jpg" aria-label="Vista lateral 2">
                        <img src="/assets/img%20catalogo/REMOVEDOR%20DE%20OXIDO%20X%205%20GAL/REMOVEDOR%20DE%20OXIDO%20X%205%20GAL4.jpg" alt="">
                    </button>
                </div>
            </div>

            <!-- Columna Derecha: Categoría, Descripción y Acciones -->
            <div class="product-info-col">
                <!-- Subtítulo en negrita (igual a la imagen) -->
                <h2 class="product-category-sub" id="prodSubtitle">Inhibidores de corrosión y removedores</h2>

                <!-- Párrafo descriptivo principal (igual a la imagen) -->
                <p class="product-desc-p" id="prodShortDesc">
                    Producto elaborado a base de tensoactivos y ácidos orgánicos muy eficaces contra la grasa y el óxido de las superficies metálicas. Es un excelente desengrasante, detergente y desoxidante.
                </p>

                <!-- Características clave -->
                <div class="product-highlights">
                    <span class="highlight-pill"><i class="ph-fill ph-lightning" aria-hidden="true"></i> Acción 3 en 1: Desengrasa, Limpia y Desoxida</span>
                    <span class="highlight-pill"><i class="ph-fill ph-shield-check" aria-hidden="true"></i> Efecto Fosfatizante Protector</span>
                    <span class="highlight-pill"><i class="ph-fill ph-timer" aria-hidden="true"></i> Actúa entre 10 a 20 min</span>
                    <span class="highlight-pill"><i class="ph-fill ph-check-circle" aria-hidden="true"></i> Garantía Z Aditivos Oficial</span>
                </div>

                <!-- Selector de Presentaciones Disponibles -->
                <div class="envases-selector-title">Presentaciones Disponibles:</div>
                <div class="envases-chips" id="chipsEnvases">
                    <button type="button" class="chip-envase" data-sku="110014470">
                        <span>1 Galón</span>
                        <span class="peso">Aprox. 4.63 kg</span>
                    </button>
                    <button type="button" class="chip-envase active" data-sku="110014455">
                        <span>5 Galones (Balde)</span>
                        <span class="peso">Aprox. 23.0 kg</span>
                    </button>
                    <button type="button" class="chip-envase" data-sku="110014511">
                        <span>55 Galones (Cilindro)</span>
                        <span class="peso">Aprox. 252 kg</span>
                    </button>
                </div>

                <!-- Redes Sociales / Síguenos (Encima del botón WhatsApp) -->
                <div class="product-social-row">
                    <span class="social-row-label"><i class="ph ph-share-network" aria-hidden="true"></i> Síguenos:</span>
                    <div class="social-row-badges">
                        <a href="https://www.facebook.com/people/Bsperu/100090456091171/" target="_blank" rel="noopener noreferrer" class="social-row-badge fb" aria-label="Facebook BS Perú" title="Facebook">
                            <i class="ph-fill ph-facebook-logo" aria-hidden="true"></i>
                        </a>
                        <a href="https://www.instagram.com/bsp.peru/" target="_blank" rel="noopener noreferrer" class="social-row-badge ig" aria-label="Instagram BS Perú" title="Instagram">
                            <i class="ph-fill ph-instagram-logo" aria-hidden="true"></i>
                        </a>
                        <a href="https://www.tiktok.com/@bs_peru?_t=ZS-90uDwonltem&_r=1" target="_blank" rel="noopener noreferrer" class="social-row-badge tk" aria-label="TikTok BS Perú" title="TikTok">
                            <i class="ph-fill ph-tiktok-logo" aria-hidden="true"></i>
                        </a>
                        <a href="https://www.youtube.com/@bsperu-BSP" target="_blank" rel="noopener noreferrer" class="social-row-badge yt" aria-label="YouTube BS Perú" title="YouTube">
                            <i class="ph-fill ph-youtube-logo" aria-hidden="true"></i>
                        </a>
                        <a href="https://www.linkedin.com/company/bs-per%C3%BA/?viewAsMember=true" target="_blank" rel="noopener noreferrer" class="social-row-badge li" aria-label="LinkedIn BS Perú" title="LinkedIn">
                            <i class="ph-fill ph-linkedin-logo" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="product-actions-bar">
                    <a href="https://wa.me/51914776669?text=Hola%20Building%20Systems%20Per%C3%BA,%20deseo%20cotizar%20el%20producto:%20REMOVEDOR%20DE%20%C3%93XIDO%20Z" target="_blank" rel="noopener" class="btn-wa-cotizar" id="btnCotizarWa">
                        <i class="ph-fill ph-whatsapp-logo" aria-hidden="true"></i> Cotizar por WhatsApp
                    </a>
                    <a href="https://drive.google.com/file/d/1_phLUi7wuY8ht2VyYpxBjjWL7E2grOfl/view?usp=drive_link" target="_blank" rel="noopener" class="btn-ficha-pdf" id="btnFichaPdf">
                        <i class="ph-fill ph-file-pdf" aria-hidden="true"></i> Descargar Ficha Técnica
                    </a>
                </div>
            </div>

        </div>

        <!-- ============ ACORDEÓN DE DETALLES Y CARACTERÍSTICAS (IGUAL A LA IMAGEN) ============ -->
        <section class="accordion-wrapper" aria-label="Detalles técnicos del producto">
            
            <!-- 1. USOS -->
            <article class="accordion-item" id="itemUsos">
                <button type="button" class="accordion-header" aria-expanded="false" aria-controls="contentUsos">
                    <span class="accordion-title">Usos</span>
                    <span class="accordion-icon" aria-hidden="true">+</span>
                </button>
                <div class="accordion-body" id="contentUsos" role="region">
                    <div class="accordion-inner" id="textUsos">
                        <ul>
                            <li>Limpieza y desoxidación de fierros de construcción y varillas corrugadas de acero estructural expuestas a la intemperie antes del vaciado de concreto.</li>
                            <li>Tratamiento de perfiles, vigas, columnas, planchas y carpintería metálica antes de la aplicación de pinturas, primers o recubrimientos anticorrosivos.</li>
                            <li>Limpieza profunda de encofrados metálicos, andamios, puntales y maquinaria de construcción afectada por óxido y grasa.</li>
                            <li>Remoción de herrumbre y suciedad en tanques de almacenamiento, tuberías industriales y piezas mecánicas automotrices.</li>
                            <li>Restauración y mantenimiento de elementos metálicos ornamentales, rejas, barandas y portones.</li>
                        </ul>
                    </div>
                </div>
            </article>

            <!-- 2. APLICACIÓN -->
            <article class="accordion-item" id="itemAplicacion">
                <button type="button" class="accordion-header" aria-expanded="false" aria-controls="contentAplicacion">
                    <span class="accordion-title">Aplicación</span>
                    <span class="accordion-icon" aria-hidden="true">+</span>
                </button>
                <div class="accordion-body" id="contentAplicacion" role="region">
                    <div class="accordion-inner" id="textAplicacion">
                        <ul>
                            <li><strong>1. Preparación de superficie:</strong> Retire las escamas sueltas de óxido, tierra o polvo empleando una escobilla de alambre, lija o espátula metálica.</li>
                            <li><strong>2. Modo de empleo:</strong> Aplique el producto puro (sin diluir) con brocha de cerdas de nylon, trapo industrial, aspersor resistente a químicos o por inmersión directa de las piezas.</li>
                            <li><strong>3. Tiempo de acción:</strong> Deje actuar el producto entre 10 a 20 minutos según el grado de oxidación. Notará que la superficie toma una coloración grisácea oscura (fosfatado).</li>
                            <li><strong>4. Limpieza / Neutralizado:</strong> Retire los residuos con agua limpia a presión o con un trapo húmedo hasta eliminar los restos del reactivo.</li>
                            <li><strong>5. Secado y protección:</strong> Seque rápidamente con aire o paño limpio. Se recomienda aplicar la pintura protectora o imprimante epóxico dentro de las primeras 24 a 48 horas.</li>
                        </ul>
                    </div>
                </div>
            </article>

            <!-- 3. CUIDADOS -->
            <article class="accordion-item" id="itemCuidados">
                <button type="button" class="accordion-header" aria-expanded="false" aria-controls="contentCuidados">
                    <span class="accordion-title">Cuidados</span>
                    <span class="accordion-icon" aria-hidden="true">+</span>
                </button>
                <div class="accordion-body" id="contentCuidados" role="region">
                    <div class="accordion-inner" id="textCuidados">
                        <ul>
                            <li><strong>Protección Personal (EPP):</strong> Utilice obligatoriamente guantes de jebe o nitrilo, lentes protectores panorámicos y mascarilla para vapores ácidos en áreas poco ventiladas.</li>
                            <li><strong>Primeros auxilios:</strong> Producto de pH ácido. En caso de salpicadura en ojos o piel, enjuague inmediatamente con abundante agua limpia durante 15 minutos continuos y consulte al médico.</li>
                            <li><strong>Compatibilidad:</strong> No mezcle con productos alcalinos (soda cáustica), hipoclorito de sodio (lejía) ni solventes inflamables.</li>
                            <li><strong>Almacenamiento:</strong> Consérvese en su envase plástico original bien tapado, en lugar fresco, bajo sombra y bien ventilado. Manténgase fuera del alcance de los niños.</li>
                        </ul>
                    </div>
                </div>
            </article>

            <!-- 4. ESPECIFICACIONES TÉCNICAS -->
            <article class="accordion-item" id="itemSpecs">
                <button type="button" class="accordion-header" aria-expanded="false" aria-controls="contentSpecs">
                    <span class="accordion-title">Especificaciones técnicas</span>
                    <span class="accordion-icon" aria-hidden="true">+</span>
                </button>
                <div class="accordion-body" id="contentSpecs" role="region">
                    <div class="accordion-inner" id="textSpecs">
                        <table class="specs-table">
                            <tbody>
                                <tr>
                                    <td class="spec-prop">Aspecto / Color</td>
                                    <td class="spec-val">Líquido translúcido ligeramente ámbar</td>
                                </tr>
                                <tr>
                                    <td class="spec-prop">Base química</td>
                                    <td class="spec-val">Tensoactivos, ácidos orgánicos e inhibidores de corrosión</td>
                                </tr>
                                <tr>
                                    <td class="spec-prop">Densidad (20 °C)</td>
                                    <td class="spec-val">1.05 ± 0.03 g/cm³</td>
                                </tr>
                                <tr>
                                    <td class="spec-prop">pH</td>
                                    <td class="spec-val">1.0 – 2.0 (Ácido activo)</td>
                                </tr>
                                <tr>
                                    <td class="spec-prop">Solubilidad</td>
                                    <td class="spec-val">100% miscible en agua</td>
                                </tr>
                                <tr>
                                    <td class="spec-prop">Rendimiento aprox.</td>
                                    <td class="spec-val">20 a 30 m² por galón (según rugosidad y grado de corrosión)</td>
                                </tr>
                                <tr>
                                    <td class="spec-prop">Tiempo de secado</td>
                                    <td class="spec-val">15 a 30 minutos al ambiente</td>
                                </tr>
                                <tr>
                                    <td class="spec-prop">Inflamabilidad</td>
                                    <td class="spec-val">No inflamable (Base acuosa)</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </article>

            <!-- 5. ENVASES -->
            <article class="accordion-item" id="itemEnvases">
                <button type="button" class="accordion-header" aria-expanded="false" aria-controls="contentEnvases">
                    <span class="accordion-title">Envases</span>
                    <span class="accordion-icon" aria-hidden="true">+</span>
                </button>
                <div class="accordion-body" id="contentEnvases" role="region">
                    <div class="accordion-inner" id="textEnvases">
                        <ul>
                            <li><strong>Frasco / Galonera x 1 Galón:</strong> Peso neto aprox. 4.63 kg. Ideal para mantenimiento menor, piezas de taller y acabados puntuales.</li>
                            <li><strong>Balde Plástico Industrial x 5 Galones:</strong> Peso neto aprox. 23.0 kg. Presentación estándar recomendada para frentes de obra y contratistas.</li>
                            <li><strong>Cilindro Metálico / Plástico x 55 Galones:</strong> Peso neto aprox. 252 kg. Presentación a granel para plantas de prefabricados, maestranzas y grandes proyectos de infraestructura.</li>
                        </ul>
                    </div>
                </div>
            </article>

            <!-- 6. DOCUMENTACIONES -->
            <article class="accordion-item" id="itemDocs">
                <button type="button" class="accordion-header" aria-expanded="false" aria-controls="contentDocs">
                    <span class="accordion-title">Documentaciones</span>
                    <span class="accordion-icon" aria-hidden="true">+</span>
                </button>
                <div class="accordion-body" id="contentDocs" role="region">
                    <div class="accordion-inner" id="textDocs">
                        <div class="doc-cards-grid">
                            <a href="https://drive.google.com/file/d/1_phLUi7wuY8ht2VyYpxBjjWL7E2grOfl/view?usp=drive_link" target="_blank" rel="noopener" class="doc-card" id="docLinkFicha">
                                <div class="doc-icon pdf"><i class="ph-fill ph-file-pdf" aria-hidden="true"></i></div>
                                <div class="doc-meta">
                                    <h4>Ficha Técnica Oficial (HT)</h4>
                                    <span>Descarga directa en PDF · Certificada</span>
                                </div>
                            </a>
                            <a href="https://wa.me/51914776669?text=Hola,%20solicito%20la%20Hoja%20de%20Seguridad%20(MSDS)%20de:%20REMOVEDOR%20DE%20ÓXIDO%20Z" target="_blank" rel="noopener" class="doc-card">
                                <div class="doc-icon"><i class="ph-fill ph-shield-warning" aria-hidden="true"></i></div>
                                <div class="doc-meta">
                                    <h4>Hoja de Seguridad (HDS / MSDS)</h4>
                                    <span>Solicitar copia oficial de seguridad</span>
                                </div>
                            </a>
                            <a href="https://wa.me/51914776669?text=Hola,%20solicito%20el%20Certificado%20de%20Calidad%20de%20lote%20de:%20REMOVEDOR%20DE%20ÓXIDO%20Z" target="_blank" rel="noopener" class="doc-card">
                                <div class="doc-icon"><i class="ph-fill ph-certificate" aria-hidden="true"></i></div>
                                <div class="doc-meta">
                                    <h4>Certificado de Calidad de Lote</h4>
                                    <span>Emisión por laboratorio de control</span>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </article>

        </section>

        <!-- BANNER DE ASESORÍA TÉCNICA -->
        <div class="product-cta-banner">
            <div>
                <h3>¿Necesitas asesoría técnica para tu obra?</h3>
                <p>Nuestros ingenieros especialistas te asesoran en la dosificación, rendimiento y aplicación directa de este y todos los productos de la línea Z Aditivos.</p>
            </div>
            <a href="https://wa.me/51914776669?text=Hola%20BS%20Per%C3%BA,%20necesito%20asesor%C3%ADa%20t%C3%A9cnica%20sobre:%20REMOVEDOR%20DE%20%C3%93XIDO%20Z" target="_blank" rel="noopener" class="btn-banner-wa">
                <i class="ph-fill ph-whatsapp-logo" aria-hidden="true"></i> Consultar a un Especialista
            </a>
        </div>

    </main>

    <!-- ============ FOOTER ============ -->
    <footer class="footer" id="contacto">
        <div class="shell">
            <div class="footer-grid">
                <div class="footer-col footer-brand">
                    <img class="brand-logo" src="img/logo_bs.png" alt="BS Perú" width="180">
                    <p>
                        <strong>BUILDING SYSTEMS PERÚ S.A.C.</strong><br>
                        RUC: 20609793806<br>
                        Av. Los Faisanes N° 675, Urb. La Campiña<br>
                        Chorrillos, Lima — Perú
                    </p>
                    <div class="footer-social">
                        <a href="https://www.instagram.com/bsp.peru/" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><i class="ph ph-instagram-logo" aria-hidden="true"></i></a>
                        <a href="https://www.facebook.com/people/Bsperu/100090456091171/" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><i class="ph ph-facebook-logo" aria-hidden="true"></i></a>
                        <a href="https://www.linkedin.com/company/bs-per%C3%BA/" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn"><i class="ph ph-linkedin-logo" aria-hidden="true"></i></a>
                        <a href="https://www.tiktok.com/@bs_peru" target="_blank" rel="noopener noreferrer" aria-label="TikTok"><i class="ph ph-tiktok-logo" aria-hidden="true"></i></a>
                        <a href="https://www.youtube.com/@bsperu-BSP" target="_blank" rel="noopener noreferrer" aria-label="YouTube"><i class="ph ph-youtube-logo" aria-hidden="true"></i></a>
                    </div>
                </div>

                <div class="footer-col">
                    <h4>Contáctanos</h4>
                    <ul>
                        <li><a href="https://wa.me/51914776669" target="_blank" rel="noopener">+51 914 776 669</a></li>
                        <li><a href="mailto:bs.peru.marketing@bsperu.pe">bs.peru.marketing@bsperu.pe</a></li>
                        <li>Lun a Vie · 8:00 – 17:30</li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>Servicio al cliente</h4>
                    <ul>
                        <li><a href="/views/terminos-condiciones.html#aviso-legal">Aviso legal</a></li>
                        <li><a href="/views/terminos-condiciones.html#politica-privacidad">Política de privacidad</a></li>
                        <li><a href="/views/terminos-condiciones.html#terminos-condiciones-uso">Términos y condiciones</a></li>
                        <li><a href="https://respondo.pe/libro/building-systems-peru-s-a-c" target="_blank" rel="noopener">Libro de reclamaciones</a></li>
                        <li><a href="/views/ubicacion.html">Sucursales</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                &copy; <span id="year">2026</span> Building Systems Perú S.A.C. — Todos los derechos reservados.
            </div>
        </div>
    </footer>

    <!-- Contacto Flotante: Redes Sociales encima del botón de WhatsApp -->
    <aside class="floating-contact-stack" aria-label="Canales de contacto y redes sociales">
        <div class="floating-social-group">
            <a href="https://www.facebook.com/people/Bsperu/100090456091171/" target="_blank" rel="noopener noreferrer" class="social-float-btn fb" aria-label="Facebook BS Perú" title="Facebook">
                <i class="ph-fill ph-facebook-logo" aria-hidden="true"></i>
                <span class="tooltip-label">Facebook</span>
            </a>
            <a href="https://www.instagram.com/bsp.peru/" target="_blank" rel="noopener noreferrer" class="social-float-btn ig" aria-label="Instagram BS Perú" title="Instagram">
                <i class="ph-fill ph-instagram-logo" aria-hidden="true"></i>
                <span class="tooltip-label">Instagram</span>
            </a>
            <a href="https://www.tiktok.com/@bs_peru?_t=ZS-90uDwonltem&_r=1" target="_blank" rel="noopener noreferrer" class="social-float-btn tk" aria-label="TikTok BS Perú" title="TikTok">
                <i class="ph-fill ph-tiktok-logo" aria-hidden="true"></i>
                <span class="tooltip-label">TikTok</span>
            </a>
            <a href="https://www.youtube.com/@bsperu-BSP" target="_blank" rel="noopener noreferrer" class="social-float-btn yt" aria-label="YouTube BS Perú" title="YouTube">
                <i class="ph-fill ph-youtube-logo" aria-hidden="true"></i>
                <span class="tooltip-label">YouTube</span>
            </a>
            <a href="https://www.linkedin.com/company/bs-per%C3%BA/?viewAsMember=true" target="_blank" rel="noopener noreferrer" class="social-float-btn li" aria-label="LinkedIn BS Perú" title="LinkedIn">
                <i class="ph-fill ph-linkedin-logo" aria-hidden="true"></i>
                <span class="tooltip-label">LinkedIn</span>
            </a>
        </div>
        <a class="wa-float" id="waFloat" href="https://wa.me/51914776669" target="_blank" rel="noopener" aria-label="Escribir por WhatsApp a BS Perú" title="WhatsApp Oficial">
            <i class="ph-fill ph-whatsapp-logo" aria-hidden="true"></i>
        </a>
    </aside>

    <!-- ============ LÓGICA JAVASCRIPT ============ -->
    <script>
    (function () {
        'use strict';

        var WA_NUMBER = '51914776669';

        /* 1. Lógica del Acordeón interactivo */
        var accHeaders = document.querySelectorAll('.accordion-header');
        accHeaders.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var item = this.closest('.accordion-item');
                var isExpanded = item.classList.contains('active');

                // Si se desea cerrar otros al abrir uno (estilo acordeón único):
                // accHeaders.forEach(function(b) { b.closest('.accordion-item').classList.remove('active'); b.setAttribute('aria-expanded', 'false'); });

                if (isExpanded) {
                    item.classList.remove('active');
                    this.setAttribute('aria-expanded', 'false');
                } else {
                    item.classList.add('active');
                    this.setAttribute('aria-expanded', 'true');
                }
            });
        });

        // Abrir el primer acordeón por defecto (Usos)
        var firstItem = document.getElementById('itemUsos');
        if (firstItem) {
            firstItem.classList.add('active');
            var firstBtn = firstItem.querySelector('.accordion-header');
            if (firstBtn) firstBtn.setAttribute('aria-expanded', 'true');
        }

        /* 2. Miniaturas de Galería */
        var mainImg = document.getElementById('mainImg');
        var thumbBtns = document.querySelectorAll('.thumb-btn');
        thumbBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                thumbBtns.forEach(function (b) { b.classList.remove('active'); });
                this.classList.add('active');
                var src = this.getAttribute('data-src');
                if (src && mainImg) {
                    mainImg.style.opacity = '0.3';
                    setTimeout(function () {
                        mainImg.src = src;
                        mainImg.style.opacity = '1';
                    }, 120);
                }
            });
        });

        /* 3. Menú móvil / Drawer */
        var hamburgerBtn = document.getElementById('hamburgerBtn');
        var drawerCloseBtn = document.getElementById('drawerCloseBtn');
        var mobileDrawer = document.getElementById('mobileDrawer');
        var drawerOverlay = document.getElementById('drawerOverlay');

        function openDrawer() {
            if (mobileDrawer) mobileDrawer.classList.add('open');
            if (drawerOverlay) drawerOverlay.classList.add('open');
            document.body.style.overflow = 'hidden';
        }
        function closeDrawer() {
            if (mobileDrawer) mobileDrawer.classList.remove('open');
            if (drawerOverlay) drawerOverlay.classList.remove('open');
            document.body.style.overflow = '';
        }
        if (hamburgerBtn) hamburgerBtn.addEventListener('click', openDrawer);
        if (drawerCloseBtn) drawerCloseBtn.addEventListener('click', closeDrawer);
        if (drawerOverlay) drawerOverlay.addEventListener('click', closeDrawer);

        /* 4. Navbar scroll shadow */
        var navbar = document.getElementById('mainNav');
        window.addEventListener('scroll', function () {
            if (navbar) {
                if (window.scrollY > 30) navbar.classList.add('scrolled');
                else navbar.classList.remove('scrolled');
            }
        });

        /* 5. Carga dinámica si viene un parámetro ?sku= o ?p= en la URL */
        var urlParams = new URLSearchParams(window.location.search);
        var skuParam = urlParams.get('sku');
        var slugParam = urlParams.get('slug');
        var nameParam = urlParams.get('p') || urlParams.get('nombre');

        // Extraer slug de la ruta /producto/este-es-el-slug
        var pathMatch = window.location.pathname.match(/\/producto\/([a-zA-Z0-9_-]+)/i);
        if (pathMatch && pathMatch[1]) {
            slugParam = pathMatch[1];
        }

        if (skuParam || slugParam || nameParam) {
            cargarDatosProducto(skuParam, slugParam, nameParam);
        }

        function cargarDatosProducto(sku, slug, nameQuery) {
            var urls = ['/assets/Data/productos.json', 'assets/Data/productos.json'];
            
            function intentarFetch(index) {
                if (index >= urls.length) return;
                fetch(urls[index])
                    .then(function (res) { return res.json(); })
                    .then(function (catalog) {
                        if (!Array.isArray(catalog)) return;
                        var prod = null;
                        if (slug) {
                            var cleanSlug = String(slug).toLowerCase().trim();
                            prod = catalog.find(function (p) {
                                return (p.slug && p.slug.toLowerCase() === cleanSlug) ||
                                       (p.sku && String(p.sku).trim() === cleanSlug);
                            });
                        }
                        if (!prod && sku) {
                            prod = catalog.find(function (p) { return String(p.sku).trim() === String(sku).trim(); });
                        }
                        if (!prod && nameQuery) {
                            var nq = nameQuery.toLowerCase().trim();
                            prod = catalog.find(function (p) {
                                return (p.nombre && p.nombre.toLowerCase().indexOf(nq) !== -1);
                            });
                        }
                        if (prod) {
                            aplicarProductoDinamico(prod);
                        }
                    })
                    .catch(function () {
                        intentarFetch(index + 1);
                    });
            }
            intentarFetch(0);
        }

        function aplicarProductoDinamico(p) {
            var prodTitle = document.getElementById('prodTitle');
            var prodSubtitle = document.getElementById('prodSubtitle');
            var prodShortDesc = document.getElementById('prodShortDesc');
            var mainImg = document.getElementById('mainImg');
            var breadProducto = document.getElementById('breadProducto');
            var breadCategoria = document.getElementById('breadCategoria');
            var btnCotizarWa = document.getElementById('btnCotizarWa');
            var btnFichaPdf = document.getElementById('btnFichaPdf');
            var pageTitle = document.getElementById('pageTitle');
            var thumbsContainer = document.getElementById('thumbsContainer');

            if (prodTitle) prodTitle.textContent = p.nombre;
            if (breadProducto) breadProducto.textContent = p.nombre;
            if (pageTitle) pageTitle.textContent = p.nombre + ' | Building Systems Perú';

            var catTexto = p.descripcion || p.categoria || 'Soluciones Químicas para la Construcción';
            if (prodSubtitle) prodSubtitle.textContent = catTexto;
            if (breadCategoria) breadCategoria.textContent = catTexto;

            if (p.descripcion_larga && prodShortDesc) {
                prodShortDesc.textContent = p.descripcion_larga;
            } else if (p.descripcion && prodShortDesc) {
                prodShortDesc.textContent = p.descripcion;
            }

            if (p.imagen && mainImg) {
                mainImg.src = p.imagen;
                mainImg.alt = p.nombre;
            }

            // Galería de miniaturas dinámicas
            if (thumbsContainer && p.sku !== '110014455') {
                var imgs = [];
                if (p.imagen) imgs.push(p.imagen);
                if (p.miniaturas) {
                    ['miniatura1', 'miniatura2', 'miniatura3', 'miniatura4'].forEach(function(k) {
                        if (p.miniaturas[k] && imgs.indexOf(p.miniaturas[k]) === -1) {
                            imgs.push(p.miniaturas[k]);
                        }
                    });
                }
                if (imgs.length > 1) {
                    thumbsContainer.innerHTML = '';
                    thumbsContainer.style.display = 'flex';
                    imgs.forEach(function(src, idx) {
                        var btn = document.createElement('button');
                        btn.className = 'thumb-btn' + (idx === 0 ? ' active' : '');
                        btn.setAttribute('data-src', src);
                        btn.setAttribute('aria-label', 'Vista ' + (idx + 1));
                        btn.innerHTML = '<img src="' + src + '" alt="">';
                        btn.addEventListener('click', function() {
                            thumbsContainer.querySelectorAll('.thumb-btn').forEach(function(b) { b.classList.remove('active'); });
                            this.classList.add('active');
                            if (mainImg) {
                                mainImg.style.opacity = '0.3';
                                setTimeout(function() {
                                    mainImg.src = src;
                                    mainImg.style.opacity = '1';
                                }, 120);
                            }
                        });
                        thumbsContainer.appendChild(btn);
                    });
                } else {
                    thumbsContainer.style.display = 'none';
                }
            }

            // Ficha técnica PDF
            if (p.ficha_pdf && btnFichaPdf) {
                btnFichaPdf.href = p.ficha_pdf;
                btnFichaPdf.style.display = 'inline-flex';
                var docFicha = document.getElementById('docLinkFicha');
                if (docFicha) docFicha.href = p.ficha_pdf;
            } else if (btnFichaPdf && !p.ficha_pdf) {
                btnFichaPdf.style.display = 'none';
            }

            // Presentación dinámica en envases
            if (p.peso2 && p.sku !== '110014455') {
                var chipsWrap = document.getElementById('chipsEnvases');
                if (chipsWrap) {
                    chipsWrap.innerHTML = '<button type="button" class="chip-envase active"><span>' + 
                        (p.nombre.indexOf(' X ') !== -1 ? p.nombre.split(' X ')[1] : 'Presentación estándar') + 
                        '</span><span class="peso">Aprox. ' + p.peso2 + ' kg</span></button>';
                }
            }

            // Acordeón Usos / Aplicación dinámico si el producto no es removedor
            if (p.sku !== '110014455') {
                var textUsos = document.getElementById('textUsos');
                if (textUsos && p.descripcion_larga) {
                    textUsos.innerHTML = '<ul><li>' + p.descripcion_larga + '</li><li>Apto para obras de construcción civil, acabados e infraestructura según especificación de catálogo.</li><li>Consulte a nuestro departamento técnico para requerimientos específicos de dosificación en obra.</li></ul>';
                }
            }

            // WhatsApp link personalizado
            var waMsg = 'Hola Building Systems Perú, deseo cotizar el producto: ' + p.nombre + (p.sku ? ' (SKU: ' + p.sku + ')' : '');
            var waUrl = 'https://wa.me/' + WA_NUMBER + '?text=' + encodeURIComponent(waMsg);
            if (btnCotizarWa) btnCotizarWa.href = waUrl;

            // Actualización completa de Metadatos SEO, Canonical, OpenGraph y Schema.org
            var tituloCompleto = p.nombre + ' | Z Aditivos Oficial - Building Systems Perú';
            document.title = tituloCompleto;
            if (pageTitle) pageTitle.textContent = tituloCompleto;

            var descLimpia = p.descripcion_larga || p.descripcion || 'Soluciones químicas especializadas para concreto y construcción en Perú.';
            var metaDesc = 'Venta de ' + p.nombre + (p.sku ? ' (' + p.sku + ')' : '') + ' original de Z Aditivos en Perú. ' + descLimpia.replace(/\s+/g, ' ').substring(0, 140) + '... Cotiza con Building Systems Perú.';
            var mDesc = document.getElementById('metaDescription') || document.querySelector('meta[name="description"]');
            if (mDesc) mDesc.setAttribute('content', metaDesc);

            var cleanSlug = p.slug || p.sku;
            var prodCanonicalUrl = 'https://bsperu.pe/producto/' + encodeURIComponent(cleanSlug);

            // Actualizar URL en el navegador a URL amigable limpia (sin recargar la pagina)
            if (p.slug && window.history && window.history.replaceState) {
                var cleanPath = '/producto/' + encodeURIComponent(p.slug);
                if (window.location.pathname !== cleanPath) {
                    window.history.replaceState(null, '', cleanPath);
                }
            }
            var canLink = document.getElementById('canonicalLink');
            if (canLink && p.sku) canLink.setAttribute('href', prodCanonicalUrl);

            var imgAbsoluta = 'https://bsperu.pe/img/impermeabilizantes.jpg';
            if (p.imagen) {
                imgAbsoluta = p.imagen.startsWith('http') ? p.imagen : 'https://bsperu.pe' + (p.imagen.startsWith('/') ? '' : '/') + p.imagen;
            }

            var setProp = function(id, val) {
                var el = document.getElementById(id);
                if (el) el.setAttribute('content', val);
            };
            setProp('ogTitle', p.nombre + ' | Z Aditivos Oficial - BS Perú');
            setProp('ogDesc', metaDesc);
            setProp('ogUrl', prodCanonicalUrl);
            setProp('ogImage', imgAbsoluta);
            setProp('twTitle', p.nombre + ' | Z Aditivos Oficial - BS Perú');
            setProp('twDesc', metaDesc);
            setProp('twImage', imgAbsoluta);

            var jsonLdEl = document.getElementById('productSchemaJson');
            if (jsonLdEl) {
                var schema = {
                    '@context': 'https://schema.org/',
                    '@type': 'Product',
                    'name': p.nombre,
                    'image': [imgAbsoluta],
                    'description': p.descripcion_larga || p.descripcion || metaDesc,
                    'sku': p.sku || '',
                    'mpn': p.sku || '',
                    'brand': {
                        '@type': 'Brand',
                        'name': p.marca || 'Z Aditivos'
                    },
                    'category': p.categoria || catTexto,
                    'offers': {
                        '@type': 'Offer',
                        'url': prodCanonicalUrl,
                        'priceCurrency': 'PEN',
                        'price': p.precio ? String(p.precio) : '0.00',
                        'priceValidUntil': '2027-12-31',
                        'itemCondition': 'https://schema.org/NewCondition',
                        'availability': (p.disponible !== false) ? 'https://schema.org/InStock' : 'https://schema.org/PreOrder',
                        'seller': {
                            '@type': 'Organization',
                            'name': 'Building Systems Perú',
                            'url': 'https://bsperu.pe'
                        }
                    },
                    'aggregateRating': {
                        '@type': 'AggregateRating',
                        'ratingValue': '4.9',
                        'reviewCount': '18',
                        'bestRating': '5',
                        'worstRating': '1'
                    }
                };
                jsonLdEl.textContent = JSON.stringify(schema, null, 2);
            }
        }

        // Año en footer
        var yearEl = document.getElementById('year');
        if (yearEl) yearEl.textContent = new Date().getFullYear();

    })();
    </script>
</body>
</html>
