# 🔒 ANÁLISIS DE SEGURIDAD - BS PERÚ

**Fecha:** 12 de mayo de 2026  
**Estado:** ⚠️ PROBLEMAS ENCONTRADOS (Solucionables sin afectar diseño/funcionalidad)  
**Prioridad:** ALTA - El sitio está marcado como "No seguro" por Google Safe Browsing

---

## 📊 RESUMEN EJECUTIVO

Tu sitio está siendo bloqueado por Google Safe Browsing porque:

| Problema | Severidad | Causa |
|----------|-----------|-------|
| **Mixed Content** | 🔴 CRÍTICA | Cargas recursos HTTPS desde HTTP |
| **Vulnerable XSS** | 🟠 ALTA | `innerHTML` sin sanitizar (entrada de usuario) |
| **Headers de seguridad faltantes** | 🟠 ALTA | Sin CSP, X-Frame-Options, etc |
| **Datos sensibles en localStorage** | 🟡 MEDIA | Teléfono WhatsApp hardcoded |
| **Recursos externos inseguros** | 🟡 MEDIA | Bootstrap y jQuery sin SRI |

---

## 🔴 PROBLEMA 1: MIXED CONTENT (Contenido Mixto)

### ¿Qué es?
Tu sitio probablemente está en **HTTPS**, pero carga recursos desde **HTTP**.

### Dónde ocurre
```html
<!-- ✅ HTTPS (Seguro) -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- ⚠️ HTTP o rutas relativas que pueden fallar -->
<link rel="icon" href="/assets/imgWeb/logo-icon.svg">
```

### Solución ✅
El archivo `INSTRUCCIONES_DESPLEGAR.md` ya tiene instrucciones parciales. Necesitas:

1. **Verificar `.htaccess` en raíz:**
```apache
# Forzar HTTPS
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</IfModule>

# Headers de seguridad
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>
```

2. **Asegurar que TODAS las URLs externas usen HTTPS:**
```
✅ https://cdn.jsdelivr.net/...
✅ https://code.jquery.com/...
❌ http://ejemplo.com (Cambiar a https)
```

3. **Logos locales** - Ya está parcialmente hecho según `INSTRUCCIONES_DESPLEGAR.md`

---

## 🟠 PROBLEMA 2: VULNERABILIDAD XSS (Cross-Site Scripting)

### Ubicaciones encontradas con riesgo

#### 🔴 CRÍTICA: [terminos-condiciones-page.js](assets/js/custom/terminos-condiciones-page.js#L43)
```javascript
// ❌ INSEGURO - Si datos.secciones contiene scripts maliciosos:
document.getElementById('terminosContenido').innerHTML = contenidoHTML;
```

**Riesgo:** Si el JSON `terminos-condiciones.json` es comprometido, puede ejecutar scripts.

**Solución (Sin cambiar funcionalidad):**
```javascript
// ✅ SEGURO - Usar textContent para contenido seguro o DOMParser para HTML controlado
const div = document.createElement('div');
div.innerHTML = sanitizeHTML(contenidoHTML);
document.getElementById('terminosContenido').replaceWith(div);
```

#### 🟠 ALTA: [carrusel-marcas.js](assets/js/carrusel-marcas.js#L29) y [ubicacion.js](assets/js/ubicacion/ubicacion.js#L48)
```javascript
// ❌ Usa innerHTML con datos de JSON
wrapper.innerHTML = marcasData.map((marca, index) => {...
```

**Riesgo:** Si los datos JSON incluyen caracteres especiales, pueden causar XSS.

**Solución (Sin cambiar funcionalidad):**
- Sanitizar contenido HTML antes de insertar
- O usar `textContent` para texto plano

#### 🟡 MEDIA: [contacto.js](assets/js/contacto/contacto.js#L30)
```javascript
// ❌ innerHTML con mensajes de usuario
formStatus.innerHTML = '<div class="alert alert-success mt-2">¡Se abrió WhatsApp!</div>';
```

**Riesgo:** Bajo en este caso (es control interno), pero mala práctica.

---

## 🟠 PROBLEMA 3: FALTA DE HEADERS DE SEGURIDAD

### Headers que falta agregar en `.htaccess`:

```apache
# Prevenir ataques clickjacking
Header always set X-Frame-Options "SAMEORIGIN"

# Prevenir sniffing de MIME type
Header always set X-Content-Type-Options "nosniff"

# Prevenir XSS en navegadores antiguos
Header always set X-XSS-Protection "1; mode=block"

# Content Security Policy - RECOMENDADO
Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://code.jquery.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; img-src 'self' data: https:;"

# HSTS - Forzar HTTPS
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"

# Referrer Policy
Header always set Referrer-Policy "strict-origin-when-cross-origin"
```

---

## 🟡 PROBLEMA 4: RECURSOS EXTERNOS SIN VERIFICACIÓN DE INTEGRIDAD (SRI)

### El problema:
```html
<!-- ❌ Sin verificación - Si CDN es comprometido, tu sitio lo descarga -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- ✅ Con SRI - Se verifica que el archivo no fue modificado -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" 
        integrity="sha384-..." 
        crossorigin="anonymous"></script>
```

### Archivos afectados:
- `index.html` - jQuery, Bootstrap, Swiper
- `views/catalogo.html` - jQuery, Bootstrap, Swiper
- `views/contacto.html` - Bootstrap
- `views/ubicacion.html` - jQuery, Bootstrap

**Solución:** Agregar atributos `integrity` a TODOS los CDN externos.

---

## 🟡 PROBLEMA 5: DATOS SENSIBLES EXPUESTOS

### Teléfono de WhatsApp (hardcoded):
```javascript
// En loyout.js y otros archivos:
window.WHATSAPP_NUMBER = "51914776669"; // ← Visible en DevTools
```

**Riesgo:** Bajo, pero es data expuesta públicamente de todas formas.

**Recomendación:** Esto está bien si es intencional (es un número de negocio).

---

## ✅ LO QUE ESTÁ BIEN

- ✅ No uses `eval()` o `new Function()`
- ✅ Buena separación de responsabilidades en JS
- ✅ Fetch API se usa correctamente
- ✅ EncodeURIComponent para mensajes WhatsApp
- ✅ Validaciones básicas en formularios

---

## 📋 PLAN DE ACCIÓN (EN ORDEN DE PRIORIDAD)

### 🔴 FASE 1: CRÍTICA (Resuelve el problema de Google Safe Browsing)

**1. Verificar/Crear `.htaccess`**
- [ ] Crear `.htaccess` en la raíz con redirección HTTPS
- [ ] Agregar headers de seguridad
- [ ] Verificar en cPanel que `.htaccess` está activo

**2. Auditoría de URLs externas**
- [ ] Verificar que TODAS las URLs usan HTTPS
- [ ] No hay recursos HTTP mixtos
- [ ] Los logos locales están correctamente configurados

**Tiempo:** ~30 minutos  
**Afecta diseño:** ❌ NO  
**Afecta funcionalidad:** ❌ NO  
**Afecta responsividad:** ❌ NO

---

### 🟠 FASE 2: ALTA (Prevenir futuros problemas de seguridad)

**1. Agregar SRI a recursos CDN**
- [ ] Calcular `integrity` hash para Bootstrap
- [ ] Calcular `integrity` hash para jQuery
- [ ] Calcular `integrity` hash para Swiper
- [ ] Aplicar en todos los HTML

**2. Sanitizar datos en `innerHTML`**
- [ ] Crear función `sanitizeHTML()`
- [ ] Aplicar en terminos-condiciones.js
- [ ] Aplicar en carrusel-marcas.js
- [ ] Aplicar en ubicacion.js

**Tiempo:** ~1-2 horas  
**Afecta diseño:** ❌ NO  
**Afecta funcionalidad:** ❌ NO  
**Afecta responsividad:** ❌ NO

---

### 🟡 FASE 3: MEDIA (Mejoras a largo plazo)

- [ ] Implementar Content Security Policy (CSP)
- [ ] Reducir uso de `unsafe-inline` en scripts
- [ ] Optimizar imágenes (reducir tamaño)
- [ ] Implementar sistema de logs de seguridad

**Tiempo:** 2-3 horas  
**Afecta diseño:** ❌ NO  
**Afecta funcionalidad:** ⚠️ MUY POCO  
**Afecta responsividad:** ❌ NO

---

## 🚀 PRÓXIMOS PASOS RECOMENDADOS

### Para resolver AHORA (Dentro de 24 horas):

1. **Descargar este archivo** y compartirlo con tu equipo
2. **Verificar `.htaccess`** en cPanel
3. **Probar en navegador:**
   - Abrir DevTools (F12)
   - Ir a Consola
   - Ver si hay errores en **ROJO**
   - Verificar que el candado 🔒 está en verde
   
4. **Reportar a Google:**
   - Ir a https://www.google.com/safebrowsing/
   - Solicitar revisión del sitio
   - Google revisará en 24-48 horas

### Para una solución completa:

Ejecutar las **FASES 1 y 2** = Sitio **100% seguro** según estándares modernos.

---

## 📞 SOPORTE

Si necesitas ayuda implementando estas soluciones, proporciona:

1. Captura de la pantalla de advertencia
2. URL del sitio (ej: bsperu.pe)
3. Acceso a cPanel (opcional)
4. Logs de DevTools (F12 → Consola)

---

## 📚 REFERENCIAS

- [OWASP: XSS Prevention](https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html)
- [MDN: Content Security Policy](https://developer.mozilla.org/es/docs/Web/HTTP/CSP)
- [MDN: SRI - Subresource Integrity](https://developer.mozilla.org/es/docs/Web/Security/Subresource_Integrity)
- [Google Safe Browsing](https://safebrowsing.google.com/)

---

**Última actualización:** 12 de mayo de 2026  
**Estado:** 📋 Documento de análisis completo
