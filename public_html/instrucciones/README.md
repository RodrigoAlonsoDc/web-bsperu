# BS PERÚ — Catálogo Web

Maqueta de página web para **BS PERÚ**, distribuidora de productos de construcción de la marca **Z ADITIVOS**. Incluye catálogo de productos, fichas técnicas, páginas de detalle, sucursales, sección "Nosotros" y carrusel de portada, todo con diseño responsivo.

---

## Índice

1. [Descripción General](#descripción-general)
2. [Guía Rápida (Para No-Técnicos)](#guía-rápida-para-no-técnicos)
3. [Estructura del Proyecto Completa](#estructura-del-proyecto-completa)
4. [Vistas / Páginas](#vistas--páginas)
5. [Categorías de Productos](#categorías-de-productos)
6. [Sucursales](#sucursales)
7. [Tecnologías Utilizadas](#tecnologías-utilizadas)
8. [Archivos de Datos (JSON)](#archivos-de-datos-json)
9. [Estructura de Estilos (CSS)](#estructura-de-estilos-css)
10. [Estructura de Scripts (JS)](#estructura-de-scripts-js)
11. [Color Institucional](#color-institucional)
12. [Sistema de Carrito (localStorage)](#sistema-de-carrito-localstorage)
13. [Scroll Spy con Tabla de Contenidos](#scroll-spy-con-tabla-de-contenidos-patrón-profesional)
14. [Cambios Recientes Implementados](#cambios-recientes-implementados)
15. [Breakpoints Responsive](#breakpoints-responsive)
16. [Guía para Nuevos Desarrolladores](#guía-para-nuevos-desarrolladores)
17. [Troubleshooting](#troubleshooting)
18. [Cómo usar](#cómo-usar)
19. [Créditos](#créditos)

---

## Descripción General

El proyecto es una maqueta funcional de e-commerce/catálogo para una empresa peruana de materiales de construcción. Los datos de productos, categorías y sucursales se cargan dinámicamente desde archivos JSON. Tiene soporte para ofertas, fichas técnicas en PDF, carrito persistente (localStorage), galería de imágenes por producto, contacto directo vía WhatsApp y carruseles optimizados con Swiper.js.

---

## Guía Rápida (Para No-Técnicos)

Si solo quieres **USAR el sitio o mantenerlo**, aquí está lo esencial:

### Si eres VENDEDOR / GERENTE:
1. Lee [Vistas / Páginas](#vistas--páginas) para entender qué tiene el sitio
2. Lee [Cómo usar](#cómo-usar) para saber cómo abrir el sitio en tu computadora
3. Lee [Agregar Productos Nuevos](#agregar-productos-nuevos) para agregar productos
4. Cualquier duda → ve a [Troubleshooting](#troubleshooting)

### Si eres DESARROLLADOR:
1. Lee [Estructura del Proyecto Completa](#estructura-del-proyecto-completa) para entender cómo está organizado
2. Lee [Guía para Nuevos Desarrolladores](#guía-para-nuevos-desarrolladores) para empezar a modificar
3. Lee [Scroll Spy con Tabla de Contenidos](#scroll-spy-con-tabla-de-contenidos-patrón-profesional) si quieres entender patrones profesionales
4. Cualquier error → ve a [Troubleshooting](#troubleshooting)

### Si eres LEGAL / COMPLIANCE:
- La página de **Términos y Condiciones** está en `views/Términos y condiciones.html`
- Contiene: Aviso Legal, Política de Privacidad, Términos y Condiciones de Uso
- Cargada desde `assets/Data/terminos-condiciones.json` (puedes editarla directamente)

---

## Estructura del Proyecto Completa

```
index.html                              ← Página principal / Homepage

views/
  ├── catalogo.html                      ← Catálogo de productos con carrusel "Campo de Aplicación"
  ├── contacto.html                      ← Página de contacto con formulario vía WhatsApp
  ├── NuestrasMarcas.html                ← Marcas asociadas y tiendas online
  ├── producto.html                      ← Detalle individual de producto
  └── ubicacion.html                     ← Mapa interactivo y listado de sucursales

assets/
  ├── css/
  │   ├── loyout/
  │   │   └── loyout.css                 ← Navbar global, footer, layout base, modales custom
  │   ├── index/
  │   │   └── index.css                  ← Estilos página principal (carruseles, secciones)
  │   ├── catalogo/
  │   │   ├── catalogo.css               ← Estilos listado catálogo y filtros
  │   │   ├── Producto.css               ← Estilos página detalle producto (galería, tabs)
  │   │   └── campo-aplicacion-touch.css ← Estilos carrusel "Campo de Aplicación" (Swiper + desktop)
  │   ├── biografia/
  │   │   ├── biografia.css              ← Estilos sección Nosotros (tabs, misión/visión)
  │   │   └── nuestras-marcas.css        ← Estilos página Nuestras Marcas
  │   ├── ubicacion/
  │   │   └── ubicacion.css              ← Estilos mapa y listado de sucursales
  │   ├── custom/
  │   │   ├── checkout.css               ← Estilos formulario checkout (validaciones Bootstrap)
  │   │   └── contacto.css               ← Estilos página de contacto
  │   ├── carrusel-marcas.css            ← Carrusel de marcas
  │   ├── tiendas-beneficios.css         ← Sección promocional
  │   └── responsive.css                 ← Media queries transversales
  │
  ├── js/
  │   ├── carrusel-compartido.js         ← Funciones compartidas de carruseles
  │   ├── carrusel-marcas.js             ← Lógica carrusel marcas
  │   ├── filter.js                      ← Filtros globales
  │   ├── filtrosgaleria.js              ← Filtros galería de productos
  │   ├── script.js                      ← Script legacy (algunas funciones generales)
  │   │
  │   ├── loyout/
  │   │   ├── loyout.js                  ← ⭐ Navbar dinámico, menú, carrito GLOBAL, sincronización localStorage
  │   │   └── loyoutCatalogo.js          ← Navbar específica del catálogo
  │   │
  │   ├── index/
  │   │   ├── index.js                   ← Página principal (carruseles portada, secciones)
  │   │   └── delaunay.js                ← Efecto fondo triangulado
  │   │
  │   ├── catalogo/
  │   │   ├── catalogo.js                ← ⭐ Renderizado catálogo, carrusel campo-aplicación (Swiper)
  │   │   ├── Producto.js                ← Página detalle producto
  │   │   └── Producto.legacy.js         ← Versión anterior (backup)
  │   ├── contacto/
  │   │   └── contacto.js                ← Formulario contacto → envío vía WhatsApp
  │   └── ubicacion/
  │       └── ubicacion.js               ← Carga sucursales, mapa visual, búsqueda de sucursales
  │
  ├── Data/
  │   ├── productos.json                 ← 📦 Base de datos de productos
  │   ├── categorias.json                ← 📦 Categorías de productos
  │   ├── sucursales.json                ← 📦 Datos de sucursales (nombre, dirección, mapa)
  │   ├── beneficios.json                ← 📦 Cards promocionales (impermeabilizantes, etc.)
  │   ├── marcas-tiendas.json            ← 📦 Tiendas online (Falabella, Mercado Libre, Rappi…)
  │   ├── marcas-tiendas-backup.json     ← 📦 Backup anterior de marcas-tiendas
  │   ├── promociones.json               ← 📦 Productos destacados/en oferta para homepage
  │   ├── puntos-venta.json              ← 📦 Cards de beneficios de compra online
  │   └── tiendas-online.json            ← 📦 Distribuidores autorizados (EM Ingeniería, Bryg's…)
  │
  ├── icon/                              ← Íconos SVG/PNG
  │
  ├── img catalogo/                      ← Imágenes por categoría/producto (70+ carpetas)
  │   ├── AIRCON Z X 5GAL/
  │   ├── ALQUITRAN/
  │   ├── ASFALTO LIQUIDO/
  │   ├── ... (todas las categorías)
  │   └── Z1 POLVO X 1K G/
  │
  ├── img biografia/                     ← Imágenes sección Nosotros
  ├── img portada/
  │   ├── PORTADA/                       ← Carrusel desktop
  │   └── PORTADA-MOVIL/                 ← Carrusel móvil
  ├── img principales/                   ← Logo y recursos generales
  │   └── logo.png
  │
  ├── imgWeb/
  │   ├── marcasIMG/                     ← Logos de marcas asociadas (Weber, Z Aditivos, etc.)
  │   ├── promoIMG/                      ← Imágenes promocionales
  │   └── soloClick/                     ← Banners de llamada a la acción
  │
  └── video/
      └── biografia/                     ← Videos sección Nosotros
```

---

## Vistas / Páginas

| Archivo | Descripción |
|---|---|
| `index.html` | Portada con carrusel, marcas asociadas, categorías, sedes de distribución y sección de contacto |
| `views/catalogo.html` | Catálogo filtrable de productos con dropdown de categorías |
| `views/producto.html` | Detalle de producto: galería de imágenes, precio, oferta, ficha técnica PDF, hoja de seguridad y productos similares |
| `views/contacto.html` | Formulario de contacto (nombre, email, RUC, teléfono, mensaje) — envío vía WhatsApp |
| `views/NuestrasMarcas.html` | Marcas asociadas y tiendas online donde adquirir los productos |
| `views/ubicacion.html` | Mapa visual interactivo de Perú con puntos de sucursales y buscador |

---

## Categorías de Productos

Las categorías se cargan desde `assets/Data/categorias.json`:

- Adhesivo Epóxico
- Capuchones
- Curadores
- Desmoldantes
- Fraguas
- Impermeabilizantes
- Morteros de Reparación
- *(y más según el JSON de productos)*

---

## Sucursales

Datos en `assets/Data/sucursales.json`:

| Clave | Nombre | Dirección |
|---|---|---|
| fali | CALLAO | Av. Elmer Faucett 1631 |
| sanborja | SAN BORJA | Av. José de Lama 150 |
| piura | PIURA | A.H. Ex Av. Chulucanas |
| chorrillos | CHORRILLOS | Av. Los Faisales 575 |
| chiclayo | CHICLAYO | Rumbos 505 |
| sullana | SULLANA | Av. La Larma 344 |
| trujillo | TRUJILLO | Av. América Oeste 531 |

---

## Tecnologías Utilizadas

- **HTML5 / CSS3 / JavaScript (ES6+)**
- **Bootstrap 5.3.2** — `index.html`, `catalogo.html` (versión usada)
- **Bootstrap 5.3.3** — `producto.html`, `ubicacion.html`, `contacto.html`, `NuestrasMarcas.html`
  > ⚠️ **Inconsistencia:** se usan dos versiones distintas. Recomendable unificar en `5.3.3`.
- **Bootstrap Icons 1.11.3** — íconos (algunas páginas lo importan dos veces — ver Troubleshooting)
- **Swiper.js 11** — solo en `index.html` (carrusel sucursales) y `catalogo.html` (campo de aplicación)
- **JSON** — fuente de datos estática (sin backend)
- **WhatsApp API** — botón `<a href="https://wa.me/51914776669">` inline en cada página (sin JS externo)

> ❌ **Tailwind CSS NO está en el proyecto.** Todo el CSS custom es vanilla CSS + Bootstrap.

---

## Archivos de Datos (JSON)

### `assets/Data/productos.json`
Cada producto contiene:
```json
{
  "sku": "110014622",
  "marca": "Z ADITIVOS",
  "categoria": "MORTEROS DE REPARACION",
  "nombre": "Z GROUT X 30 KG",
  "descripcion": "Mortero autonivelante de alta resistencia.",
  "descripcion_larga": "...",
  "precio": 30.10,
  "peso": 30,
  "imagen": "/assets/img catalogo/...",
  "miniaturas": { "miniatura1": "...", ... },
  "ficha_pdf": "https://...",
  "hoja_seguridad_pdf": "https://...",
  "oferta_disponible": true,
  "producto_disponible": true
}
```

### `assets/Data/categorias.json`
Lista de categorías con nombre, imagen de portada y enlace al catálogo filtrado.

### `assets/Data/sucursales.json`
Lista de sucursales con nombre, dirección, imagen y clave identificadora.

### `assets/Data/beneficios.json`
Cards de beneficios mostradas en la homepage. Cada entrada tiene:
```json
{ "titulo": "...", "imagen": "https://...", "boton": "COMPRA ONLINE", "link": "/views/catalogo.html?cat=..." }
```

### `assets/Data/marcas-tiendas.json`
Tiendas online donde se venden los productos (Falabella, Mercado Libre, Rappi, etc.). Incluye nombre, logo, link y estilos de color.

### `assets/Data/marcas-tiendas-backup.json`
Backup de la versión anterior de `marcas-tiendas.json`.

### `assets/Data/promociones.json`
Productos destacados/en oferta para mostrar en la homepage. Cada entrada apunta a un producto por SKU:
```json
{ "titulo": "...", "descripcion": "...", "badge": "DESTACADO", "tienda": "BS Perú", "imagen": "...", "link": "/views/producto.html?sku=..." }
```

### `assets/Data/puntos-venta.json`
Cards de beneficios de compra online (compra segura, producto original, ahorra tiempo). Cada entry tiene icono Bootstrap Icons, título y descripción.

### `assets/Data/tiendas-online.json`
Distribuidores autorizados con logo, link y estilo visual (EM Ingeniería, Bryg's, ANCLAF, Sika Center, etc.).

---

## Estructura de Estilos (CSS)

### `/assets/css/loyout/loyout.css` - 🎨 STYLES GLOBALES
**Aplica a:** Todas las páginas

| Componente | Descripción |
|---|---|
| `.navbar-bs` | Navbar custom (logo, links, búsqueda, carrito) |
| `.navbar-bs__cart-btn` | Botón carrito con badge |
| `.mi-navbar` | Navbar Bootstrap alternativa |
| `.layout-cart-modal` | Modal carrito (custom, no-Bootstrap) |
| `.layout-cart-backdrop` | Fondo oscuro del modal |
| `.layout-cart-panel` | Panel principal del carrito |
| `.layout-modal-open` | Clase en body cuando modal abierto |
| `.whatsapp-btn` | Botón flotante WhatsApp |

**Responsivo:** Breakpoints en 576px, 768px, 992px, 1200px

---

### `/assets/css/index/index.css` - 🏠 PÁGINA PRINCIPAL
**Aplica a:** `index.html`

| Componente | Descripción |
|---|---|
| `.carrucel-portada` | Carrusel Swiper de portada (imágenes hero) |
| `.marcas-asociadas-section` | Sección de 2-3 logos de marcas |
| `.seccion-coloreada` | Sección decorativa de fondo |
| `.sucursales-carrusel-automatico` | Carrusel Swiper de sucursales |
| `.campos-aplicacion-index` | Carrusel "Campo de Aplicación" |
| `.boletin-card` | Card suscripción newsletter |
| `.boletin-form` | Formulario email |

**Carruseles:** Swiper.js v11 con momentes, paginación, responsivo

---

### `/assets/css/catalogo/catalogo.css` - 🛍️ CATÁLOGO Y PRODUCTOS
**Aplica a:** `catalogo.html`, `producto.html`

| Componente | Descripción |
|---|---|
| `.catalogoProductos` | Grid de productos en catálogo |
| `.dropdownCatalogo` | Filtro de categorías |
| `.modal-content.rounded-4` | Modales redondeados (carrito, checkout) |
| `.cart-item` | Item individual en carrito |
| `.cart-item-img` | Imagen producto en carrito |
| `#carritoModal` | Modal carrito Bootstrap (fallback) |
| `#checkoutModal` | Modal finalizar compra Bootstrap |
| `.tiendas-beneficios-section` | Sección de tiendas online |
| `.beneficio-card` | Cards de beneficios |
| `.punto-beneficio` | Puntos de venta (naranja/gris) |

**Nota:** Usa modales Bootstrap pero coordina con `loyout.js` para evitar conflictos

---

### `/assets/css/catalogo/Producto.css` - 📦 DETALLE PRODUCTO
**Aplica a:** `views/producto.html`

| Componente | Descripción |
|---|---|
| `.galeria-miniaturas` | Galería de miniaturas del producto |
| `.imagen-principal` | Imagen grande seleccionada |
| `.tabs-producto` | Tabs para fichas técnicas, seguridad |
| `.producto-info` | Panel información (precio, descripción, botones) |
| `.btn-agregar-carrito` | Botón "Agregar al Carrito" |
| `.productos-similares` | Carrusel de productos relacionados |

**Interactivo:** Click en miniaturas cambia imagen principal

---

### `/assets/css/catalogo/campo-aplicacion-touch.css` - 🎯 CARRUSEL CAMPO APLICACIÓN
**Aplica a:** Carrusel de categorías en catalogo.html

| Componente | Descripción |
|---|---|
| `.campo-aplicacion-wrapper` | Contenedor del carrusel |
| `.campo-aplicacion-swiper` | Contenedor Swiper (móvil/tablet ≤1024px) |
| `.campo-aplicacion-contenedor` | Fallback scroll (desktop >1024px) |
| `.campo-aplicacion-item` | Item individual (circular, transparente) |
| `.campo-aplicacion-item:hover` | Efecto hover (escala, transform) |
| `.campo-aplicacion-img-wrapper` | Wrapper de imagen (overflow:visible para no clipear) |
| `.campo-aplicacion-img` | Imagen del producto (no circular) |

**Responsive:** 
- Mobile (320px): 1.8 items/view
- Small (480px): 2.2 items/view
- Tablet (768px): 3 items/view
- Desktop (1024px): 4 items/view

**Diseño:** Círculos transparentes con imágenes cuadradas, sin bordes

---

### `/assets/css/biografia/biografia.css` - 👥 SECCIÓN NOSOTROS
**Aplica a:** `views/biografia.html`

| Componente | Descripción |
|---|---|
| `.section-nosotros` | Contenedor principal |
| `.tab-btn-nosotros` | Botones Misión/Visión |
| `.tab-btn-nosotros.active` | Botón activo |
| `.tab-content-nosotros` | Panel de contenido (oculto por defecto) |
| `.tab-content-nosotros.active-tab` | Panel visible |
| `.nosotros-descripcion` | Texto de historia/introducción |

**Interacción:** Click en botones muestra/oculta tabs (vanilla JS, no jQuery)

---

### `/assets/css/ubicacion/ubicacion.css` - 📍 MAPA Y SUCURSALES
**Aplica a:** `views/ubicacion.html`

| Componente | Descripción |
|---|---|
| `#map` | Imagen SVG/PNG del mapa de Perú |
| `.sucursal` | Card de cada sucursal en la lista |
| `#searchSucursal` | Input de búsqueda de sucursales |

---

### `/assets/css/custom/checkout.css` - 🧾 FORMULARIO CHECKOUT
**Aplica a:** Modal checkout en catálogo y producto

| Componente | Descripción |
|---|---|
| `#checkoutForm.was-validated` | Estilos validación Bootstrap |
| `.form-control:invalid` | Borde rojo + ícono error |
| `.form-control:valid` | Borde verde + ícono check |
| `.invalid-feedback` / `.valid-feedback` | Mensajes de error/éxito |

---

### `/assets/css/custom/contacto.css` - 📬 PÁGINA DE CONTACTO
**Aplica a:** `views/contacto.html`

Estilos del formulario de contacto (campos, layout, botones de envío).

---

### `/assets/css/carrusel-marcas.css` - 🏷️ CARRUSEL MARCAS
Carrusel de marcas online (Weber, Z Aditivos, etc.)

---

### `/assets/css/tiendas-beneficios.css` - 🎁 TIENDAS Y BENEFICIOS
Sección de tiendas online, cards, puntos de venta

---

### `/assets/css/responsive.css` - 📱 MEDIA QUERIES
Media queries globales para breakpoints:
- **430px:** Mobile devices
- **576px:** Small devices
- **700px:** Tablet vertical
- **768px:** Tablet horizontal
- **1024px:** Desktop (Swiper → scroll)
- **1200px+:** Large desktop

---

## Estructura de Scripts (JS)

### ⭐ `/assets/js/loyout/loyout.js` - 🎮 NAVBAR Y CARRITO GLOBAL
**MÁS IMPORTANTE:** Este archivo maneja:

1. **Navbar dinámico**
   - Menú responsivo (hamburguesa móvil)
   - Links a todas las páginas
   - Búsqueda de productos

2. **Carrito Persistente (localStorage)**
   - `carritoLayout` = Array en localStorage['carrito']
   - `syncCarritoLayout()` = Guardar a localStorage
   - `renderCarritoLayout()` = Renderizar items carrito
   - `openLayoutModal(id)` = Abrir modal carrito
   - `closeLayoutModal(id)` = Cerrar modal carrito
   - `cambiarCantidadLayout(index, delta)` = +/- cantidad
   - `eliminarItemCarritoLayout(index)` = Remover producto

3. **Listeners Clave**
   - `window.addEventListener('focus')` = Refresh carrito si cambió en otra pestaña
   - `window.addEventListener('storage')` = Sincroniza localStorage entre tabs
   - `document.addEventListener('click')` = Maneja botón carrito, X, acciones

4. **Modales Custom** (NO Bootstrap)
   - Usa clases `.layout-cart-modal.is-open`
   - `data-close="carritoModal"` o `data-close="checkoutModal"`
   - Backdrop = div con `.layout-cart-backdrop`

**Función Critical:**
```javascript
actualizarBadgeCarrito()  // Actualiza badge sin renderizar carrito completo
```

---

### `/assets/js/catalogo/catalogo.js` - 📊 CATÁLOGO Y CARRUSEL
**Funciones principales:**

| Función | Descripción |
|---|---|
| `cargarYRenderizarCategorias()` | Carga JSON categorias.json, renderiza carrusel |
| `inicializarSwiperCampoAplicacion()` | Detecta viewport, inicia Swiper o scroll |
| `inicializarCarruselTouch()` | Handler custom touch con passive listeners |
| `agregarAlCarrito(prod)` | Agrega producto a `carrito[]` y sincroniza localStorage |
| `cambiarCantidad(idx, delta)` | Modifica cantidad (+/-) |
| `eliminarItemCarrito(idx)` | Remueve producto |
| `syncCarrito()` | Guarda `carrito[]` a localStorage['carrito'] |
| `renderCarrito()` | Renderiza items en modal carrito |
| `setupUIContainers()` | Crea modales (fallback si no existen) |
| `setupGlobalEvents()` | Listeners click, submit, change |

**Swiper Config:**
```javascript
{
  slidesPerView: 'auto',
  spaceBetween: 15,
  grabCursor: true,
  loop: true,
  breakpoints: {
    320: { slidesPerView: 1.8, spaceBetween: 12 },
    480: { slidesPerView: 2.2 },
    768: { slidesPerView: 3 },
    1024: { slidesPerView: 4 }
  }
}
```

**Nota:** Detecta si `loyout.js` está activo y no crea handlers duplicados

---

### `/assets/js/catalogo/Producto.js` - 📄 DETALLE PRODUCTO
**Aplica a:** `views/producto.html`

| Función | Descripción |
|---|---|
| `cargarProductoDetalle()` | Carga producto por SKU de URL |
| `renderProductoGaleria()` | Renderiza miniaturas y imagen principal |
| `cambiarImagenPrincipal(src)` | Click miniatura cambia imagen |
| `setupProductoCartEvents()` | Listeners carrito en producto |
| `renderProductoCarrito()` | Renderiza carrito desde localStorage |
| `finalizarCompraProducto(form)` | Envía pedido vía WhatsApp |

---

### `/assets/js/index/index.js` - 🏠 PÁGINA PRINCIPAL
Carga y renderiza:
- Carrusel portada (Swiper)
- Secciones de sedes
- Categorías

---

### `/assets/js/contacto/contacto.js` - 📬 FORMULARIO DE CONTACTO
**Aplica a:** `views/contacto.html`

Escucha el submit del formulario y arma un mensaje estructurado con todos los campos para enviarlo vía WhatsApp:

| Campo | Descripción |
|---|---|
| `nombre` | Nombre del cliente |
| `email` | Correo electrónico |
| `razonSocial` | Razón social de la empresa |
| `ruc` | RUC de la empresa |
| `telefono` | Teléfono de contacto |
| `mensaje` | Mensaje libre |

Al enviar abre `https://wa.me/51914776669?text=...` en nueva pestaña y resetea el formulario.

---

### `/assets/js/ubicacion/ubicacion.js` - 📍 MAPA Y SUCURSALES
**Aplica a:** `views/ubicacion.html`

| Función / Variable | Descripción |
|---|---|
| `cargarSucursales()` | Fetch a `sucursales.json`, genera cards HTML dinámicamente |
| `POSICIONES_MAPA` | Objeto con coordenadas % (x, y) de cada sucursal en la imagen del mapa |
| `CIUDAD_DEPARTAMENTO` | Mapeo ciudad → departamento para mostrar en UI |
| `searchSucursal` | Input de búsqueda: filtra sucursales en tiempo real |

**Sucursales mapeadas:** CALLAO, CHORRILLOS, LIMA, SULLANA, PIURA, TRUJILLO, CHICLAYO, TACNA, AREQUIPA

---

### `/assets/js/carrusel-marcas.js` - 🏷️ CARRUSEL MARCAS

```javascript
scrollMarcasLeft()   // Scroll izquierda
scrollMarcasRight()  // Scroll derecha
```

---

### Botón WhatsApp flotante
No existe un archivo JS dedicado. El botón está definido **directamente en el HTML** de cada página:
```html
<a href="https://wa.me/51914776669" target="_blank" class="whatsapp-btn" title="WhatsApp">
    <i class="bi bi-whatsapp whatsapp-icon"></i>
</a>
```
Su estilo viene de `.whatsapp-btn` en `/assets/css/loyout/loyout.css`. Para cambiar el número, editar el `href` en cada HTML.

---

## Color Institucional

El color azul institucional de BS PERÚ es `#005587`. Se usa en:
- Navbar y encabezados
- Botones primarios (`btn-primary`, `bg-primary`)
- Bordes activos en galería de miniaturas
- Textos y acentos (`text-primary`)

---

## Cómo usar

### Setup Inicial
1. Clona o descarga el repositorio.
2. **Importante:** Abre con un servidor local (NO con `file://`) para evitar restricciones CORS con JSON.
   - Opción 1: Extensión **Live Server** de VS Code (botón derecho → Open with Live Server)
   - Opción 2: Terminal → `python -m http.server 8000` → accede a `http://localhost:8000`
   - Opción 3: Node.js → `npx http-server` → `http://localhost:8080`

3. Navega: **Inicio → Nosotros → Catálogo → Detalle de Producto → Sucursales**.

### Variables Configurables

**Número WhatsApp** — editar el atributo `href` en el botón flotante de cada HTML:
```html
<!-- En index.html, catalogo.html, producto.html, contacto.html, ubicacion.html, NuestrasMarcas.html -->
<a href="https://wa.me/51XXXXXXXXX" target="_blank" class="whatsapp-btn">
```

**Teléfonos de Sucursales** (`/assets/Data/sucursales.json`)
Editar directamente en JSON.

### Agregar Productos Nuevos

**Pasos:**
1. Abre `/assets/Data/productos.json`
2. Agrega un nuevo objeto al array JSON:
```json
{
  "sku": "110999999",
  "marca": "Z ADITIVOS",
  "categoria": "TU_CATEGORIA",
  "nombre": "NOMBRE DEL PRODUCTO X MEDIDA",
  "descripcion": "Breve descripción",
  "descripcion_larga": "Descripción completa de características",
  "precio": 45.50,
  "peso2": 30,
  "imagen": "/assets/img catalogo/CARPETA_DEL_PRODUCTO/imagen.jpg",
  "miniaturas": {
    "miniatura1": "/assets/img catalogo/CARPETA_DEL_PRODUCTO/imagen.jpg",
    "miniatura2": "",
    "miniatura3": "",
    "miniatura4": "",
    "miniatura5": ""
  },
  "ficha_pdf": "https://link-a-pdf.com",
  "hoja_seguridad_pdf": "https://link-a-pdf.com",
  "oferta_disponible": false,
  "producto_disponible": true
}
```

3. Crea la carpeta `/assets/img catalogo/CARPETA_DEL_PRODUCTO/` y sube las imágenes.
4. Guarda el JSON y recarga la página.

**Validaciones JSON:**
- Usa comillas dobles `"` (NO simples)
- Última propiedad NO tiene coma
- URLs sin espacios (usar `%20` si es necesario)

### Agregar Categorías Nuevas

1. Abre `/assets/Data/categorias.json`
2. Agrega entrada:
```json
{
  "nombre": "NUEVA_CATEGORIA",
  "img": "/assets/img catalogo/RUTA_IMAGEN/imagen.png",
  "link": "/views/catalogo.html?cat=NUEVA_CATEGORIA"
}
```

3. Los productos con `"categoria": "NUEVA_CATEGORIA"` aparecerán automáticamente al filtrar.

### Agregar Sucursales

1. Edita `/assets/Data/sucursales.json`
2. Agrega:
```json
{
  "key": "clave_unica",
  "nombre": "NOMBRE SUCURSAL",
  "direccion": "Av. Calle 123, Distrito",
  "urlMap": "https://www.google.com/maps?q=...",
  "telefono": "999999999",
  "lat": -12.0632,
  "lng": -77.1530,
  "imagen": "/assets/img sucursales/imagen.svg",
  "alt": "Descripción para accesibilidad"
}
```

3. Recarga la página. Aparecerá en carrusel y ubicación.


---

## Sistema de Carrito (localStorage)

### Cómo funciona

El carrito se **persiste automáticamente** en `localStorage['carrito']` como un array JSON. Cada item tiene esta estructura:

```javascript
{
  sku: "110014622",
  marca: "Z ADITIVOS",
  nombre: "Z GROUT X 30 KG",
  precio: 30.10,
  cantidad: 2,
  imagen: "/assets/img catalogo/...",
  categoria: "MORTEROS DE REPARACION"
}
```

### Funciones Clave del Carrito

**En `/assets/js/loyout/loyout.js`:**

| Función | Parámetros | Acción |
|---|---|---|
| `agregarAlCarrito(producto)` | `producto` (object) | Agrega o suma cantidad si ya existe |
| `cambiarCantidadLayout(index, delta)` | `index` (number), `delta` (±1) | Incrementa/decrementa cantidad |
| `eliminarItemCarritoLayout(index)` | `index` (number) | Remueve producto completamente |
| `renderCarritoLayout()` | — | Renderiza UI del carrito en modal |
| `syncCarritoLayout()` | — | Guarda carrito en localStorage |
| `actualizarBadgeCarrito()` | — | Actualiza número en badge (rápido) |
| `limpiarCarrito()` | — | Vacía todo el carrito |

### localStorage API

```javascript
// Leer carrito
const carrito = JSON.parse(localStorage.getItem('carrito') || '[]');

// Guardar carrito
localStorage.setItem('carrito', JSON.stringify(carrito));

// Limpiar
localStorage.removeItem('carrito');
```

### Sincronización entre Tabs

Cuando el usuario abre 2 tabs del sitio:
- Tab 1 hace cambios en carrito → se guarda en localStorage
- Tab 2 escucha evento `storage` → detecta cambio automáticamente
- Tab 2 sincroniza su carrito sin recargar

```javascript
window.addEventListener('storage', (e) => {
  if (e.key === 'carrito') {
    carritoLayout = JSON.parse(e.newValue || '[]');
    renderCarritoLayout();
  }
});
```

---

## Scroll Spy con Tabla de Contenidos (Patrón Profesional)

### ¿Qué es Scroll Spy?

**Scroll Spy** es un patrón de diseño/navegación que automáticamente **resalta la sección actual** mientras el usuario se desplaza por la página. Es muy utilizado en:
- Documentación técnica (Bootstrap, MDN, GitHub Docs)
- Páginas legales y términos (políticas de privacidad)
- Guías y tutoriales largos
- Portales de soporte

### Implementación en BS PERÚ

Esta funcionalidad está **integrada en la página "Términos y Condiciones"** (`views/Términos y condiciones.html`).

#### **Características principales:**

1. **Tabla de Contenidos Dinámica (Sidebar)**
   - Índice lateral generado automáticamente desde el JSON
   - Se mantiene visible (sticky) mientras desplazas
   - Muestra 3 secciones: Aviso Legal, Política de Privacidad, Términos y Condiciones

2. **Scroll Spy Automático**
   - El navegador **detecta automáticamente** qué sección estás viendo
   - Marca el link correspondiente como "activo" (color azul + subrayado)
   - Se actualiza en tiempo real sin necesidad de clicks

3. **Navegación Suave**
   - Click en cualquier enlace → desplazamiento suave hacia la sección
   - Cada sección tiene su propio ID para ser referenciable

4. **Responsive**
   - En desktop: sidebar fijo a la izquierda
   - En móvil: el índice se coloca arriba, full-width

#### **Archivos involucrados:**

| Archivo | Rol |
|---------|-----|
| `views/Términos y condiciones.html` | Estructura HTML con navbar, drawer y contenedor dinámico |
| `assets/js/custom/terminos-condiciones-page.js` | Lógica de Scroll Spy + renderizado JSON |
| `assets/css/custom/terminos-condiciones-page.css` | Estilos del sidebar y highlight automático |
| `assets/Data/terminos-condiciones.json` | Fuente de datos (3 secciones + subsecciones) |

#### **Código Clave - Scroll Spy (`terminos-condiciones-page.js`):**

```javascript
// Implementar scroll spy automático
window.addEventListener('scroll', () => {
    let currentSection = '';
    
    const secciones = document.querySelectorAll('.terminos-seccion');
    secciones.forEach(seccion => {
        const sectionTop = seccion.offsetTop;
        const sectionHeight = seccion.clientHeight;
        
        // Si el usuario scrolleó hasta esta sección
        if (window.pageYOffset >= sectionTop - 200) {
            currentSection = seccion.getAttribute('id');
        }
    });

    // Actualizar el nav link activo visualmente
    links.forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') === `#${currentSection}`) {
            link.classList.add('active'); // Color azul + border
        }
    });
});
```

#### **Código Clave - Navegación Suave (`terminos-condiciones-page.js`):**

```javascript
links.forEach(link => {
    link.addEventListener('click', (e) => {
        e.preventDefault();
        
        // Remover clase active de todos
        links.forEach(l => l.classList.remove('active'));
        
        // Marcar como activo el clickeado
        link.classList.add('active');
        
        // Scroll suave al elemento
        const target = document.querySelector(link.getAttribute('href'));
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});
```

#### **Estilos - Highlight Visual (`terminos-condiciones-page.css`):**

```css
.terminos-nav-link {
    color: #333;
    border-left: 3px solid transparent;
    padding-left: 12px;
    transition: all 0.3s ease;
}

.terminos-nav-link.active {
    color: #005587;                              /* Color azul BS */
    border-left-color: #005587;                  /* Borde izquierdo azul */
    font-weight: 600;                            /* Texto más grueso */
    background-color: rgba(0, 85, 135, 0.05);   /* Fondo sutil */
}
```

#### **Ventajas de este patrón:**

- **UX profesional** — Usuarios saben dónde están en el documento  
- **Accesibilidad** — Fácil navegación con teclado  
- **Escalable** — Funciona con cualquier número de secciones  
- **Dinámico** — Cargado desde JSON, sin hardcoding  
- **Responsive** — Funciona igual en desktop y móvil  
- **Performance** — Lightweight, sin librerías externas  

#### **Cómo ampliarlo:**

1. **Agregar más secciones:** Edita `terminos-condiciones.json` y agrega un nuevo objeto en `secciones[]`
2. **Cambiar estilos:** Modifica `terminos-condiciones-page.css` (colores, tamaño de fuente, etc.)
3. **Usar en otras páginas:** Copia `terminos-condiciones-page.js` y adapta el selector de secciones

---

## Cambios Recientes Implementados

### ✅ Fase 1: Correcciones JSON
- **Fecha:** 23 de abril de 2026
- **Cambio:** Agregadas 9 comas faltantes en `sucursales.json` (propiedad `urlMap`)
- **Impacto:** Corregido `SyntaxError: Expected ',' or '}' after property value in JSON`
- **Archivo:** `assets/Data/sucursales.json`

### ✅ Fase 2: Desactivación de Ofertas
- **Cambio:** Todos los `"oferta_disponible"` cambiados de `true` a `false` en `productos.json`
- **Impacto:** Las ofertas NO se muestran en catálogo ni producto
- **Método:** Script Python con encoding UTF-8
- **Archivo:** `assets/Data/productos.json`

### ✅ Fase 3: Botón WhatsApp Draggable con Doble Clic
- **Fecha:** 5 de mayo de 2026
- **Cambios:**
  - Convertido botón WhatsApp de `<a href>` a `<button>` (sin navegación automática)
  - Creado script `/assets/js/custom/whatsapp-draggable.js` con funcionalidad draggable
  - Implementado evento de **doble clic** para abrir WhatsApp (no al arrastrar)
  - Agregado soporte touch/móvil para arrastrar
  - Persistencia de posición en `localStorage['whatsapp_position']`
  - Script integrado en todas las páginas HTML
- **Impacto:** 
  - Usuarios pueden arrastrar el logo de WhatsApp a cualquier posición
  - La posición se guarda y persiste entre navegaciones y sesiones
  - Solo se abre WhatsApp con doble clic (no accidental al soltar después de arrastrar)
- **Archivos modificados:**
  - `index.html` — cambio botón HTML + agregado script
  - `views/catalogo.html` — agregado script
  - `views/Términos y condiciones.html` — agregado script
  - `views/ubicacion.html` — agregado script
  - `views/contacto.html` — agregado script
  - `views/NuestrasMarcas.html` — agregado script
  - `views/producto.html` — agregado script
  - Nuevo archivo: `assets/js/custom/whatsapp-draggable.js`

### 📋 TODO: Tareas Pendientes
- [ ] Implementar búsqueda de productos (filter.js)
- [ ] Agregar contacto por email desde formulario
- [ ] Integrar pago en línea (Stripe/PayPal)
- [ ] Agregar login de usuario
- [ ] Implementar wishlist
- [ ] Analytics con Google Tag Manager
- [ ] SEO: meta tags dinámicos por página

---

## Breakpoints Responsive

El proyecto usa Bootstrap 5.3 + media queries custom:

| Viewport | Breakpoint | Dispositivo | Uso |
|---|---|---|---|
| 320px — 480px | `xs` | Mobile pequeño | 1 columna, carruseles simples |
| 480px — 768px | `sm` | Mobile grande | 2 columnas, Swiper con 2.2 items |
| 768px — 1024px | `md` (tablet) | iPad vertical | 3 columnas, Swiper con 3 items |
| 1024px — 1200px | `lg` (desktop) | Laptop | 4 columnas, Swiper desactivo (scroll) |
| 1200px+ | `xl` (desktop grande) | Monitor grande | 6 columnas, hover effects |

### Puntos Clave

**En 1024px cambia:**
- Carrusel "Campo de Aplicación" de Swiper → scroll horizontal manual
- Navbar desktop aparece (no hamburguesa)
- Galería productos: 4 items/fila

**En 768px cambia:**
- Carrusel sucursales: 3 items visible
- Modales: ancho 90% (no full width)
- Tabs sección Nosotros: horizontal → vertical en móvil

---

## Guía para Nuevos Desarrolladores

### 1. Entender la Estructura del Carrito

**Archivo clave:** `/assets/js/loyout/loyout.js` (líneas 50-150)

Este archivo es el "cerebro" del sitio. Lee primero las funciones:
1. `agregarAlCarrito()` — cómo se agrega un producto
2. `syncCarritoLayout()` — cómo se guarda en localStorage
3. `renderCarritoLayout()` — cómo se pinta en HTML

### 2. Flujo de Datos: Producto → Carrito → Checkout

```
usuario ve producto en catalogo.html
         ↓
    clic "Agregar al Carrito"
         ↓
    catalogo.js llama agregarAlCarrito(producto)
         ↓
    loyout.js agrega a carritoLayout[] 
         ↓
    syncCarritoLayout() guarda en localStorage
         ↓
    actualizarBadgeCarrito() muestra número
         ↓
    usuario clic en ícono carrito
         ↓
    openLayoutModal('carritoModal') abre panel
         ↓
    renderCarritoLayout() pinta items en HTML
```

### 3. Editar JSON sin Errores

**Validar JSON:**
```bash
python -m json.tool assets/Data/productos.json
```

Si hay error, te mostrará la línea exacta.

### 4. Agregar Nueva Página

**Pasos:**
1. Crear archivo `views/nueva-pagina.html`
2. Copiar estructura de otra página (navbar, footer, etc.)
3. Importar CSS específica: `<link rel="stylesheet" href="../assets/css/nueva-pagina/nueva-pagina.css">`
4. Importar JS global al final: `<script src="../assets/js/loyout/loyout.js"></script>`
5. Agregar link en navbar (`loyout.html`)

### 5. Debuggear Carrito

Abre consola (F12) y ejecuta:

```javascript
// Ver carrito actual
console.log(JSON.parse(localStorage.getItem('carrito')));

// Ver si hay errores en localStorage
localStorage.getItem('carrito')

// Limpiar carrito (reset)
localStorage.removeItem('carrito');
window.location.reload();
```

---

## Troubleshooting

### ❌ JSON no carga en catálogo

**Síntomas:** Catálogo vacío, consola muestra "Failed to fetch"

**Solución:**
1. Verifica que estés en servidor local (`http://localhost:...`), NO `file:///`
2. Abre F12 → Console → busca errores rojos
3. Revisa que `assets/Data/productos.json` exista y sea válido:
   ```bash
   python -m json.tool assets/Data/productos.json
   ```

### ❌ Carrito se vacía al recargar

**Solución:**
1. Abre F12 → Application → LocalStorage
2. Busca key `carrito`
3. Si no existe, el localStorage puede estar deshabilitado
4. Prueba en navegador privado/incógnito

### ❌ Imágenes no cargan en producto

**Síntomas:** Placeholder o imagen rota

**Soluciones:**
1. Verifica ruta de imagen en `productos.json`:
   - ✅ `/assets/img catalogo/CATEGORIA/imagen.jpg`
   - ❌ `assets/img catalogo/CATEGORIA/imagen.jpg` (falta `/` inicial)
   - ❌ `/assets/img catalogo/CATEGOR%20A/imagen.jpg` (URL encoding innecesario)

2. Caso: Si carpeta tiene espacios, usa `%20`:
   ```
   /assets/img catalogo/Z GROUT X 30 KG/imagen.jpg
   → /assets/img catalogo/Z%20GROUT%20X%2030%20KG/imagen.jpg
   ```

### ❌ Modales no se cierren

**Causa:** Conflicto entre modalLayout custom y Bootstrap modals

**Solución:**
1. En `loyout.js`, verifica que `openLayoutModal()` use id correcto
2. Usa `data-close="carritoModal"` en botones close
3. Si es Bootstrap modal, usa `data-bs-dismiss="modal"`

### ❌ Swiper no funciona en móvil

**Síntomas:** Carrusel "congelado", no se desliza

**Solución:**
1. Verifica que Swiper.js esté cargado: F12 → Console → `typeof Swiper` (debe ser `'function'`)
2. Revisa que touch listeners estén activos (no `preventDefault()` sin razón)
3. En catalogo.js, verifica viewport width detect:
   ```javascript
   console.log('Viewport:', window.innerWidth, '→', typeof Swiper !== 'undefined' ? 'Swiper' : 'Scroll')
   ```

### ❌ Ofertas siguen mostrándose

**Solución:**
Abre `assets/Data/productos.json` y verifica que TODOS tengan:
```json
"oferta_disponible": false
```

No semisimple `true`. Si está en `true`, el producto se muestra en sección ofertas.

### ❌ Carrusel campo-aplicación se ve mal en tablet

**Causa:** Breakpoint en 1024px cambia de Swiper → scroll

**Solución:**
1. Abre `/assets/css/catalogo/campo-aplicacion-touch.css`
2. Busca media query `@media (max-width: 1023px)`
3. Ajusta `slidesPerView` o `spaceBetween` según necesite

### ❌ localStorage lleno (cuota superada)

**Síntomas:** Error "QuotaExceededError" en consola

**Solución:**
1. Abre F12 → Application → LocalStorage → derecho click → Clear all
2. O limita tamaño de carrito:
   ```javascript
   if (carritoLayout.length > 100) {
     carritoLayout.splice(0, 1); // Elimina primero
   }
   ```

### ❌ Navbar no responde en móvil

**Causa:** Hamburguesa no abre menú

**Solución:**
1. Verifica que `loyout.js` esté cargado antes del cierre `</body>`
2. Abre F12 → Console → ejecuta: `typeof agregarAlCarrito` (debe ser `'function'`)
3. Si sale `undefined`, loyout.js no se cargó

---

## ⚠️ Problemas Conocidos (detectados en auditoría)

### 1. Versión Bootstrap inconsistente entre páginas
| Página | Versión Bootstrap |
|---|---|
| `index.html` | 5.3.2 |
| `catalogo.html` | 5.3.2 |
| `producto.html` | 5.3.3 |
| `ubicacion.html` | 5.3.3 |
| `contacto.html` | 5.3.3 |
| `NuestrasMarcas.html` | 5.3.3 |

**Fix recomendado:** unificar todas las páginas a `bootstrap@5.3.3`.

### 2. Bootstrap Icons importado dos veces en algunas páginas
`index.html` y `catalogo.html` incluyen Bootstrap Icons dos veces:
```html
<!-- Duplicado: -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" ...>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" ...>
```
**Fix:** eliminar el primero (sin versión) y dejar solo `@1.11.3`.

### 3. `ubicacion.html` referencia un CSS inexistente
```html
<link rel="stylesheet" href="/css/style.css" />  ← ❌ El archivo no existe
```
**Fix:** eliminar esa línea o crear el archivo si hace falta.

### 4. Swiper solo está en 2 páginas
Swiper.js (CSS + JS) solo se carga en `index.html` y `catalogo.html`. Las demás páginas no lo necesitan — está bien así.

### 5. El botón WhatsApp no tiene JS dedicado
No existe `whatsapp-widget.js`. El botón es HTML inline en cada página con la clase `.whatsapp-btn` (estilos en `loyout.css`). Para cambiar el número hay que editarlo en los 6 archivos HTML.

---

## Créditos

- Diseño y desarrollo: **[Tu Nombre o Empresa]**
- Productos y marca: **Z ADITIVOS / BS PERÚ**
- Imágenes de productos: uso demostrativo
- Framework: **Bootstrap 5.3.3**, **Swiper.js 11**

---

