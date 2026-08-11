# 🚀 INICIO RÁPIDO - SOLUCIONAR "NO ES SEGURO"

**Tiempo total:** 30 minutos para resolver el problema  
**Dificultad:** Muy fácil ✅

---

## 🔴 EL PROBLEMA

Tu navegador muestra:  
*"La conexión con este sitio web no es segura"*  
*"No deberías introducir información confidencial"*

---

## ✅ LA SOLUCIÓN (EN 3 PASOS)

### PASO 1: Subir archivo `.htaccess` (5 minutos)

1. Abre **cPanel de tu servidor**
2. Click en **File Manager**
3. Navega a la **carpeta raíz** donde está `index.html`
4. Busca un archivo llamado `.htaccess`
   - **Si existe:** Click derecho → Edit → Copiar el nuevo contenido
   - **Si NO existe:** Click derecho → Create New File → `.htaccess`
5. Guarda los cambios
6. ✅ Hecho

**El archivo `.htaccess` ya fue actualizado en tu proyecto.** Solo necesitas subirlo al servidor.

---

### PASO 2: Limpiar caché del navegador (2 minutos)

1. En tu sitio `https://bsperu.pe`
2. Presiona **Ctrl + Shift + Supr** (Windows)  
   O **Cmd + Shift + Supr** (Mac)
3. Selecciona "Todas las cookies" + "Caché"
4. Click "Limpiar"
5. ✅ Hecho

---

### PASO 3: Reportar a Google (3 minutos)

1. Ve a: https://www.google.com/safebrowsing/
2. Ingresa tu URL: `https://bsperu.pe`
3. Click "SOLICITAR REVISIÓN"
4. Espera 24-48 horas
5. ✅ Google revisará tu sitio

---

## 🎉 ¡ESO ES!

**En 24-48 horas:**
- ✅ Google levanta la advertencia
- ✅ Los usuarios ven candado verde 🔒
- ✅ Tu sitio vuelve a ser confiable

---

## 📚 PARA MAYOR SEGURIDAD (Recomendado)

Si quieres hacer tu sitio **aún más seguro**, lee:
- **`GUIA_SRI_SEGURIDAD.md`** - Proteger CDN de ataques
- **`SOLUCIONES_XSS.md`** - Prevenir inyecciones de código

Eso toma **1-2 horas adicionales** pero mejora la seguridad al máximo.

---

## 🆘 SI ALGO SALE MAL

### Error: "No puedo subir `.htaccess`"
**Solución:** 
- En cPanel, ve a **Settings** → Mostrar archivos ocultos
- Luego intenta crear/editar de nuevo

### Error: "El archivo no se sube"
**Solución:**
- Verifica que tienes permisos en cPanel
- Intenta por SFTP en lugar de File Manager

### El problema persiste después de 48 horas
**Solución:**
1. Abre DevTools (F12)
2. Ve a **Console**
3. Busca mensajes en **ROJO**
4. Toma una captura
5. Revisa la sección "Documentación" de arriba

---

## 📋 ARCHIVOS CREADOS PARA TI

En la **raíz de tu proyecto** tienes:

| Archivo | Para qué |
|---------|----------|
| **`.htaccess`** | Configuración de seguridad (NECESARIO subir) |
| **`PLAN_EJECUCION.md`** | Plan detallado paso a paso |
| **`ANALISIS_SEGURIDAD.md`** | Análisis completo de problemas |
| **`GUIA_SRI_SEGURIDAD.md`** | Proteger recursos CDN |
| **`SOLUCIONES_XSS.md`** | Código seguro contra hacks |

---

## ✨ ¿QUE OCURRIÓ EN TU SITIO?

Tu sitio intentaba cargar recursos seguros (HTTPS) + inseguros (HTTP).  
Google detectó esto y bloqueó el sitio por protección.

El archivo `.htaccess` fuerza que TODO sea HTTPS.  
Así Google deja de bloquear.

---

**Última actualización:** 12 de mayo de 2026  
**Estado:** ✅ Listo para implementar ahora mismo
