# 📋 Resumen de URLs Externas en el Proyecto

## 🔴 CRÍTICO - Causa "No es seguro" (Mixed Content)

### 1. **assets/Data/marcas-tiendas.json**
- `https://www.falabella.com.pe/favicon.ico` ← Falabella logo
- `https://http2.mlstatic.com/frontend-assets/ml-web-navigation/ui-navigation/7.24.0/mercadolibre/logo_large_plus@2x.webp` ← Mercado Libre logo
- `https://logosenvector.com/logo/img/rappi-37277.png` ← Rappi logo
- `https://www.facebook.com/favicon.ico` ← Facebook logo

⚠️ **PROBLEMA:** Se cargan dinámicamente con JavaScript. Esto causa advertencia de contenido mixto.

---

## 🟠 SECUNDARIO - No causa Mixed Content pero son externos
 
⚠️ **PROBLEMA:** Imágenes de Unsplash. Carga lenta y depende de terceros.

### 3. **assets/Data/productos.json** (Muchas líneas)
- `https://www.zaditivos.com.pe/files/zaditivos/...` (PDFs de fichas técnicas)
- `https://drive.google.com/file/d/...` (PDFs en Google Drive)

⚠️ **PROBLEMA:** PDFs externos. Si el servidor se cae, se pierden los PDFs.

### 4. **index.html**
- `https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css` ← Bootstrap CSS
- `https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css` ← Bootstrap Icons
- `https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css` ← Swiper CSS
- `https://www.instagram.com/bsp.peru/` ← Link a Instagram
- `https://respondo.pe/libro/building-systems-peru-s-a-c` ← Libro de reclamaciones
- `https://www.facebook.com/` ← Link a Facebook

⚠️ **NOTA:** Los CDN (Bootstrap, Swiper) están bien, son necesarios. Los links externos también están bien.

---

## ✅ SOLUCIÓN RECOMENDADA

**Paso 1:** Crear `.htaccess` para forzar HTTPS (resuelve el "No es seguro")
**Paso 2:** Reemplazar logos de marcas-tiendas.json por versiones locales
**Paso 3:** Descargar imágenes de beneficios.json localmente
**Paso 4:** Descargar o guardar PDFs localmente (opcional)

---

## 📊 Resumen
- **Archivos afectados:** 4
- **URLs críticas:** 4 (logos)
- **URLs secundarias:** 40+
