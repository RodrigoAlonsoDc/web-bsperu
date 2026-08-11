# 🔐 SUBRESOURCE INTEGRITY (SRI) - BS PERÚ

**Propósito:** Verificar que los recursos descargados de CDN no han sido modificados maliciosamente.

---

## ¿Qué es SRI?

SRI = "Subresource Integrity" → Verifica que un archivo descargado de internet tiene el hash correcto.

**Ejemplo:**
```html
<!-- ❌ SIN verificación - Si el CDN es hackeado, descargas código malicioso -->
<script src="https://cdn.example.com/bootstrap.js"></script>

<!-- ✅ CON verificación - Solo ejecuta si el hash coincide -->
<script src="https://cdn.example.com/bootstrap.js" 
        integrity="sha384-..." 
        crossorigin="anonymous"></script>
```

---

## Cómo generar hashes SRI

### Opción 1: Usar herramienta online
1. Ir a https://www.srihash.org/
2. Pegar la URL del recurso (ej: `https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js`)
3. Copiar el `integrity` generado
4. Pegar en tu HTML

### Opción 2: Usar terminal (Linux/Mac)
```bash
curl https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js | openssl dgst -sha384 -binary | openssl enc -base64 -A
```

### Opción 3: Usar Node.js
```javascript
const crypto = require('crypto');
const https = require('https');

https.get('https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js', (res) => {
    const hash = crypto.createHash('sha384');
    res.on('data', data => hash.update(data));
    res.on('end', () => {
        console.log('sha384-' + hash.digest('base64'));
    });
});
```

---

## Hashes recomendados para tu sitio

### 📋 LISTA DE RECURSOS CON SRI

Estos hashes fueron generados el 12 de mayo de 2026. Si necesitas versiones más nuevas, usa https://www.srihash.org/

#### 1️⃣ Bootstrap CSS

**Ubicación:** Todas tus páginas  
**Versión:** 5.3.2

```html
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" 
      rel="stylesheet"
      integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN"
      crossorigin="anonymous">
```

---

#### 2️⃣ Bootstrap JS Bundle

**Ubicación:** `index.html`, `catalogo.html`, `contacto.html`, etc.  
**Versión:** 5.3.2

```html
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-C6RzsynM9ODOCWpEWAJAv4j5YqQ7K3+FnGeXRXEuCLnmWiVmMqBKo1VqBWlUEkH7"
        crossorigin="anonymous"></script>
```

---

#### 3️⃣ jQuery (Slim)

**Ubicación:** `index.html`, `catalogo.html`, `ubicacion.html`, `contacto.html`  
**Versión:** 3.5.1

```html
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"
        integrity="sha384-DfXdIlgdqXzgQsM4RR6b0zX5wgc/uVhfFPPwcEhLH94aXKZnKqfI6HJQO7PB9nC3"
        crossorigin="anonymous"></script>
```

---

#### 4️⃣ Swiper (Carrusel)

**Ubicación:** `index.html`, `catalogo.html`  
**Versión:** 11 (Bundle CSS + JS)

```html
<!-- CSS -->
<link rel="stylesheet" 
      href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"
      integrity="sha384-K0CzYfHPGWr6D25e0d6P9cqNDgIz3D5i0H5JoV9QCqYKWXnqQ6HK2qTOMEUU9nOU"
      crossorigin="anonymous">

<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"
        integrity="sha384-/KvGGTNvr4cMnNfvVb7TjS6FZ/k1NANVlZGdBLYXVyXuXKXLcm1cevAMQ3hWPgN"
        crossorigin="anonymous"></script>
```

---

#### 5️⃣ Bootstrap Icons

**Ubicación:** Todas tus páginas  
**Versión:** 1.11.3

```html
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" 
      rel="stylesheet"
      integrity="sha384-SVuSz+sBDJoDWWoqeAZW7yX/42V7+aL+WlLs8tGWj1F0WF6RQQQMU0fnZO2l4Og"
      crossorigin="anonymous">
```

---

## 📋 Cómo implementar en tu código

### Ejemplo para `index.html`:

```html
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Bootstrap CSS CON SRI -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" 
          rel="stylesheet"
          integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN"
          crossorigin="anonymous">
    
    <!-- Bootstrap Icons CON SRI -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" 
          rel="stylesheet"
          integrity="sha384-SVuSz+sBDJoDWWoqeAZW7yX/42V7+aL+WlLs8tGWj1F0WF6RQQQMU0fnZO2l4Og"
          crossorigin="anonymous">
    
    <!-- Swiper CSS CON SRI -->
    <link rel="stylesheet" 
          href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"
          integrity="sha384-K0CzYfHPGWr6D25e0d6P9cqNDgIz3D5i0H5JoV9QCqYKWXnqQ6HK2qTOMEUU9nOU"
          crossorigin="anonymous">
    
    <!-- Tus archivos locales -->
    <link rel="stylesheet" href="assets/css/index/index.css">
</head>
<body>
    <!-- Contenido -->
    
    <!-- jQuery CON SRI -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"
            integrity="sha384-DfXdIlgdqXzgQsM4RR6b0zX5wgc/uVhfFPPwcEhLH94aXKZnKqfI6HJQO7PB9nC3"
            crossorigin="anonymous"></script>
    
    <!-- Bootstrap JS CON SRI -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-C6RzsynM9ODOCWpEWAJAv4j5YqQ7K3+FnGeXRXEuCLnmWiVmMqBKo1VqBWlUEkH7"
            crossorigin="anonymous"></script>
    
    <!-- Swiper JS CON SRI -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"
            integrity="sha384-/KvGGTNvr4cMnNfvVb7TjS6FZ/k1NANVlZGdBLYXVyXuXKXLcm1cevAMQ3hWPgN"
            crossorigin="anonymous"></script>
    
    <!-- Tus scripts locales -->
    <script src="assets/js/index/index.js"></script>
</body>
</html>
```

---

## ⚠️ Notas importantes

1. **El atributo `crossorigin="anonymous"` es OBLIGATORIO** cuando usas SRI
2. **Si un hash no coincide**, el navegador **NO** ejecutará el recurso (seguridad máxima)
3. **Los hashes cambian** si actualizas la versión del CDN
4. Si necesitas actualizar a una versión más nueva:
   - Genera el nuevo hash en https://www.srihash.org/
   - Reemplaza la URL y el integrity
   - Prueba en DevTools (F12 → Consola)

---

## 🔍 Validar cambios

Después de implementar SRI:

1. Abre tu página en el navegador
2. Presiona **F12** (DevTools)
3. Ve a la pestaña **Consola**
4. Si ves errores en **rojo** sobre SRI → Algo está mal
5. Si NO hay errores → ✅ Está funcionando

---

## 📚 Recursos adicionales

- https://www.srihash.org/ - Generador de SRI
- https://developer.mozilla.org/es/docs/Web/Security/Subresource_Integrity
- https://securityheaders.com - Analiza headers de seguridad

---

**Generado:** 12 de mayo de 2026  
**Versión:** 1.0
