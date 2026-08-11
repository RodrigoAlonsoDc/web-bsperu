# 📊 PLAN DE EJECUCIÓN - SEGURIDAD BS PERÚ

**Documento:** Resumen ejecutivo + pasos a seguir  
**Fecha:** 12 de mayo de 2026  
**Prioridad:** 🔴 CRÍTICA - Sitio bloqueado por Google Safe Browsing

---

## 🎯 OBJETIVO

Resolver el aviso **"La conexión con este sitio web no es segura"** de Google Chrome y mejorar la puntuación de seguridad del sitio.

---

## 📁 ARCHIVOS GENERADOS PARA TI

He creado **4 documentos de referencia** en la raíz de tu proyecto:

| Archivo | Propósito | Leer primero? |
|---------|-----------|--------------|
| **ANALISIS_SEGURIDAD.md** | Análisis detallado de todos los problemas encontrados | ✅ SÍ |
| **PLAN_EJECUCION.md** | Este documento (Guía paso a paso) | ✅ SÍ |
| **GUIA_SRI_SEGURIDAD.md** | Hashes de Subresource Integrity para CDN | Cuando implementes CDN |
| **SOLUCIONES_XSS.md** | Código seguro para mitigar vulnerabilidades XSS | Cuando implementes XSS |
| **.htaccess** | Configuración de seguridad de Apache (ACTUALIZADO) | Necesario subir a servidor |

---

## 🚨 PROBLEMA RAÍZ

Tu sitio está siendo bloqueado porque:

1. **Mixed Content (HTTP + HTTPS)** - Cargas recursos inseguros
2. **Falta de headers de seguridad** - No se comunica protección
3. **Posibles vulnerabilidades XSS** - Entrada de usuario no sanitizada
4. **Recursos externos sin verificación** - CDN podrían ser hackeados

---

## ⏱️ TIEMPO ESTIMADO

| Fase | Tareas | Tiempo | Criticidad |
|------|--------|--------|-----------|
| **1** | Activar HTTPS + Headers básicos | 30 min | 🔴 CRÍTICA |
| **2** | Agregar SRI a CDN | 45 min | 🟠 ALTA |
| **3** | Sanitizar XSS | 1-2 h | 🟠 ALTA |
| **TOTAL** | Sitio 100% seguro | **2-3 horas** | ✅ Recomendado |

---

## 🚀 FASE 1: CRÍTICA (30 minutos) - HACE ESTO PRIMERO

### Objetivo
Resolveremos el error **"No es seguro"** en Google Chrome.

### Paso 1.1: Verificar HTTPS está activo
```
1. Abre https://bsperu.pe en navegador
2. Busca el candado 🔒 en la barra de direcciones
   - Verde ✅ = HTTPS está bien
   - Rojo ❌ = Hay un problema
```

### Paso 1.2: Subir archivo `.htaccess` actualizado

**Dónde subir:**
- **Servidor:** cPanel → File Manager
- **Ubicación:** Raíz del dominio (bsperu.pe)
- **Archivo:** `.htaccess` (sí, comienza con un punto)

**Instrucciones:**
1. Abre cPanel
2. File Manager
3. Navega a la raíz donde está `index.html`
4. Busca si ya existe `.htaccess`
   - Si existe: Edítalo y reemplaza contenido
   - Si NO existe: Crea uno nuevo
5. Copia el contenido de tu archivo `.htaccess` (ya fue actualizado)
6. Guarda

### Paso 1.3: Verificar cambios en navegador

```javascript
// 1. Abre tu sitio: https://bsperu.pe
// 2. Presiona F12 (DevTools)
// 3. Ve a Network → Reload la página
// 4. Busca en la lista:
//    ✅ Todas las URLs deben ser HTTPS
//    ❌ Si hay HTTP: Hay problema
```

### Paso 1.4: Borrar caché de Google

1. Ir a: https://www.google.com/safebrowsing/
2. Ingresar: `https://bsperu.pe`
3. Click "SOLICITAR REVISIÓN"
4. Google revisará en **24-48 horas**

---

## 🟠 FASE 2: ALTA (45 minutos) - DESPUÉS DE FASE 1

### Objetivo
Agregar integridad a recursos CDN para prevenir hacks.

### Paso 2.1: Agregar hashes SRI a todas tus páginas

En cada página HTML (`index.html`, `catalogo.html`, `contacto.html`, `ubicacion.html`):

**Busca esto:**
```html
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
```

**Reemplaza por esto:**
```html
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" 
      rel="stylesheet"
      integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN"
      crossorigin="anonymous">
```

**Referencia de todos los hashes:**
- Abre: `GUIA_SRI_SEGURIDAD.md`
- Copia los atributos `integrity` indicados
- Péga en tus etiquetas `<link>` y `<script>`

### Paso 2.2: Probar en DevTools

```javascript
// F12 → Console
// Si NO hay errores rojos = ✅ Está bien
// Si hay errores = ❌ Un hash está mal, revisa
```

---

## 🟡 FASE 3: MEDIA (1-2 horas) - OPCIONAL PERO RECOMENDADA

### Objetivo
Prevenir inyecciones XSS (Cross-Site Scripting).

### Paso 3.1: Crear archivo de utilidades

1. Crear carpeta: `assets/js/utils/`
2. Crear archivo: `assets/js/utils/sanitize.js`
3. Copiar contenido de `SOLUCIONES_XSS.md` → Sección "Función `sanitizeHTML()`"
4. Guardar

### Paso 3.2: Incluir en tus páginas

En **cada página HTML**, antes del `</body>`:

```html
<!-- Agregar esta línea ANTES de tus otros scripts -->
<script src="/assets/js/utils/sanitize.js"></script>

<!-- Tus otros scripts aquí -->
<script src="/assets/js/index/index.js"></script>
```

### Paso 3.3: Actualizar archivos JS vulnerables

Los archivos a actualizar (en orden de prioridad):

1. **`assets/js/custom/terminos-condiciones.js`** - Muy importante
2. **`assets/js/carrusel-marcas.js`** - Importante
3. **`assets/js/ubicacion/ubicacion.js`** - Importante
4. **`assets/js/contacto/contacto.js`** - Baja prioridad

**Instrucciones:**
- Abre `SOLUCIONES_XSS.md`
- Busca el nombre del archivo
- Copia la solución "Después (Seguro)"
- Reemplaza en tu archivo JS
- Prueba en DevTools (F12 → Console)

### Paso 3.4: Probar

```javascript
// F12 → Console
// Ejecutar:
console.log(sanitizeHTML('<script>alert("test")</script><h1>OK</h1>'));
// Debe mostrar: <h1>OK</h1> (sin script)
```

---

## 📋 CHECKLIST DE IMPLEMENTACIÓN

### ✅ Completar antes de desplegar

**FASE 1 (Crítica):**
- [ ] Verificar HTTPS activo (candado verde 🔒)
- [ ] Descargar `.htaccess` actualizado
- [ ] Subir `.htaccess` a cPanel
- [ ] Borrar caché (Ctrl+Shift+Supr)
- [ ] Solicitar revisión a Google Safe Browsing
- [ ] Esperar 24-48 horas para revisión

**FASE 2 (Alta):**
- [ ] Leer `GUIA_SRI_SEGURIDAD.md`
- [ ] Agregar hashes SRI en `index.html`
- [ ] Agregar hashes SRI en `catalogo.html`
- [ ] Agregar hashes SRI en `contacto.html`
- [ ] Agregar hashes SRI en `ubicacion.html`
- [ ] Probar en DevTools (no hay errores rojos)

**FASE 3 (Media):**
- [ ] Crear `assets/js/utils/sanitize.js`
- [ ] Incluir sanitize.js en todas las páginas
- [ ] Actualizar `terminos-condiciones.js`
- [ ] Actualizar `carrusel-marcas.js`
- [ ] Actualizar `ubicacion.js`
- [ ] Actualizar `contacto.js` (opcional)
- [ ] Probar funcionalidad completa

---

## 🔍 VALIDACIÓN FINAL

Después de completar TODO, verifica:

### Test 1: HTTPS
```
https://bsperu.pe
→ Debe mostrar candado verde 🔒
```

### Test 2: Headers de Seguridad
```
https://securityheaders.com
→ Ingresa: bsperu.pe
→ Debe mostrar calificación A o B
```

### Test 3: Console (DevTools)
```
F12 → Console
→ NO debe haber mensajes en ROJO
→ Los warnings en amarillo están OK
```

### Test 4: Funcionalidad
- ✅ Puedes buscar productos
- ✅ Puedes ver catálogo
- ✅ Puedes contactar por WhatsApp
- ✅ Puedes ver sucursales
- ✅ Puedes suscribirse a newsletter

---

## 💬 PREGUNTAS FRECUENTES

### P: ¿Necesito acceso FTP para esto?
**R:** No. Puedes hacerlo todo desde **cPanel → File Manager**.

### P: ¿Estos cambios afectan el diseño?
**R:** ✅ **NO**. Esto solo agrega seguridad.

### P: ¿Afecta la velocidad del sitio?
**R:** ✅ **NO**. De hecho, GZIP lo hace más rápido.

### P: ¿Cuánto tarda Google en revisar?
**R:** 24-48 horas normalmente. Algunos casos 7 días.

### P: ¿Qué pasa si el hash SRI no coincide?
**R:** El navegador **NO ejecuta** el recurso (seguridad máxima).

### P: ¿Necesito contratar a alguien?
**R:** No necesariamente. Si te atoras, puedo ayudarte.

---

## 📞 SOPORTE

Si necesitas ayuda:

1. **Documenta el error:**
   - Captura de pantalla
   - URL de tu sitio
   - Qué paso completaste

2. **Busca en:**
   - `ANALISIS_SEGURIDAD.md` → Explicación del problema
   - `SOLUCIONES_XSS.md` → Ejemplos de código
   - `GUIA_SRI_SEGURIDAD.md` → Hashes correctos

3. **DevTools (F12):**
   - Consola → Ver errores exactos
   - Network → Ver qué se carga y qué no
   - Application → Ver localStorage

---

## 📚 DOCUMENTACIÓN REFERENCIA

- [OWASP: Top 10 Web Application Risks](https://owasp.org/Top10/)
- [Google Safe Browsing](https://safebrowsing.google.com/)
- [Mozilla Observatory](https://observatory.mozilla.org/)
- [MDN Web Security](https://developer.mozilla.org/es/docs/Web/Security)

---

## ✨ BENEFICIOS FINALES

Después de implementar esto:

✅ Google dejará de bloquear tu sitio  
✅ Los usuarios verán candado verde 🔒  
✅ Mejora en ranking SEO  
✅ Protección contra ataques comunes  
✅ Cumplimiento de estándares de seguridad  
✅ Confianza del usuario  

---

## 🎯 PRÓXIMOS PASOS

**Ahora:**
1. Lee `ANALISIS_SEGURIDAD.md` (comprensión del problema)
2. Inicia FASE 1 (30 minutos)
3. Reporta a Google Safe Browsing

**Mañana:**
1. Completa FASE 2 (45 minutos)
2. Espera a que Google revise (24-48h)

**Esta semana:**
1. Completa FASE 3 (1-2 horas)
2. Verifica con securityheaders.com
3. Sitio 100% seguro ✅

---

**Documento generado:** 12 de mayo de 2026  
**Versión:** 1.0  
**Estado:** Listo para implementación
