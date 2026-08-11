// ╔════════════════════════════════════════════════════════════════════════════╗
// ║                   LOYOUT.JS - SISTEMA DE NAVEGACIÓN Y CARRITO               ║
// ║                                                                              ║
// ║ Este archivo gestiona:                                                       ║
// ║ • Navbar principal con búsqueda y carrito                                    ║
// ║ • Drawer/menú móvil deslizable                                              ║
// ║ • Búsqueda de productos en navbar y drawer                                   ║
// ║ • Sistema completo de carrito y checkout                                     ║
// ║ • Modales de autorización y notificaciones                                   ║
// ╚════════════════════════════════════════════════════════════════════════════╝

// ════════════════════════════════════════════════════════════════════════════
// SECCIÓN 1: VARIABLES GLOBALES E INICIALIZACIÓN
// ════════════════════════════════════════════════════════════════════════════

/** Número de WhatsApp para contacto - Se usa en todos los formularios de compra */
if (!window.WHATSAPP_NUMBER) {
  window.WHATSAPP_NUMBER = "51914776669";
}

/** Detecta si la página actual es la homepage para comportamientos específicos */
const isHomePage = window.location.pathname === '/' || window.location.pathname.endsWith('/index.html');

// ════════════════════════════════════════════════════════════════════════════
// SECCIÓN 1.5: FUNCIONES PARA GESTIONAR MODALES
// ════════════════════════════════════════════════════════════════════════════
// Nota: Estrategia ÚNICA - aria-hidden solo cuando CERRADO
// openLayoutModal() abre sin aria-hidden
// closeLayoutModal() cierra con aria-hidden='true'

// ════════════════════════════════════════════════════════════════════════════
// SECCIÓN 2: UTILIDAD - NORMALIZAR DATOS
// ════════════════════════════════════════════════════════════════════════════

/**
 * Convierte strings a sus tipos de datos correctos
 * Útil para procesar parámetros URL y datos localStorage
 * 
 * Conversiones:
 * - 'true'/'false' → booleanos
 * - '123' → números
 * - '' o 'null' → null
 * - JSON strings → objetos/arrays
 */
function normalizeData(value) {
  if (value === 'true') return true;
  if (value === 'false') return false;
  if (value === Number(value).toString()) return Number(value);
  if (value === '' || value === 'null') return null;
  if (typeof value !== 'string') return value;

  try {
    return JSON.parse(decodeURIComponent(value));
  } catch (e) {
    return value;
  }
}

// ════════════════════════════════════════════════════════════════════════════
// SECCIÓN 3: NAVBAR - INICIALIZACIÓN
// ════════════════════════════════════════════════════════════════════════════

/** Contenedor donde se inyecta el navbar principal */
let menuContainer;

/**
 * FUNCIÓN PRINCIPAL: Crea e inyecta la barra de navegación superior
 * 
 * Incluye:
 * - Logo y enlace a inicio
 * - Enlaces de categorías (Productos, Marcas, Sucursales, Contacto)
 * - Buscador de productos con sugerencias
 * - Botón de carrito con badge de cantidad
 * - Botones hamburguesa para menú móvil
 * 
 * Se ejecuta al cargar el DOM si el elemento .barrera-menu existe
 */
function inicializarNavbar() {
  menuContainer = document.querySelector(".barrera-menu");
  if (!menuContainer) {
    setTimeout(inicializarNavbar, 100);
    return;
  }
  
  const layoutMenuLinks = `
    <li class="navbar-bs__item-with-submenu">
      <a href="/views/catalogo.html" class="navbar-bs__productos-link" id="productosLink">Productos</a>
      <div class="navbar-bs__submenu" id="productosSubmenu">
        <div class="navbar-bs__submenu-content">
          <div class="submenu-loading">Cargando categorías...</div>
        </div>
      </div>
    </li>
    <li><a href="/views/NuestrasMarcas.html">Nuestras Marcas</a></li> 
    <li><a href="/views/ubicacion.html">Sucursales</a></li>
    <li><a href="/views/contacto.html">Contáctanos</a></li>
  `;

  const navbarHTML = `
    <nav class="navbar-bs">
      <div class="navbar-bs__logo">
        <a href="/index.html">
          <img src="/assets/img principales/logo.png" alt="B S PERÚ" height="47"/>
        </a>
      </div>
      <div style="display: flex; flex-direction: row; gap: 10px;">
        <div class="navbar-bs__center">
          <ul class="navbar-bs__links" id="navbar-bs-links">
            ${layoutMenuLinks}
          </ul>
        </div>
        
        <!-- BUSCADOR DE PRODUCTOS -->
        <div class="navbar-search-wrapper" style="position: relative; display: flex; align-items: center; margin-left: auto;">
          <form class="navbar-bs__search-form" id="layoutProductSearchForm" action="#" method="get">
            <input type="text" placeholder="Buscar..." id="layoutProductSearch" autocomplete="off" />
            <button type="submit" aria-label="Buscar"><i class="bi bi-search"></i></button>
          </form>
          <div id="layoutSearchSuggestions" class="list-group position-absolute layout-search-suggestions" style="display: none; position: absolute; background: white; max-height: 350px; overflow-y: auto; z-index: 1000; border-radius: 0 0 8px 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);"></div>
        </div>
        
        <!-- BOTÓN DE CARRITO -->
        <button id="btnCarritoNav" class="navbar-bs__cart-btn" type="button" aria-label="Abrir carrito">
          <i class="bi bi-cart"></i>
          <span id="cartBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display:none">0</span>
        </button>
      </div>
      
      <!-- BOTONES HAMBURGUESA (para menú móvil) -->
      <button class="navbar-bs__toggle navbar-bs__toggle--mobile" id="navbar-bs-toggle-mobile" aria-label="Abrir menú">
        <i class="bi bi-list"></i>
      </button>
      <button class="navbar-bs__toggle navbar-bs__toggle--desktop" id="navbar-bs-toggle-desktop" aria-label="Abrir menú">
        <i class="bi bi-list"></i>
      </button>
    </nav>
  `;

  menuContainer.innerHTML = navbarHTML;

  // Setup: Botones hamburguesa para toggle del menú
  const toggleBtnMobile = document.getElementById('navbar-bs-toggle-mobile');
  const toggleBtnDesktop = document.getElementById('navbar-bs-toggle-desktop');
  const navLinks = document.getElementById('navbar-bs-links');
  
  function handleToggleClick() {
    navLinks.classList.toggle('active');
  }
  
  if (toggleBtnMobile && navLinks) toggleBtnMobile.addEventListener('click', handleToggleClick);
  if (toggleBtnDesktop && navLinks) toggleBtnDesktop.addEventListener('click', handleToggleClick);

  if (navLinks) {
    // Cerrar menú al hacer clic en un enlace
    navLinks.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', () => navLinks.classList.remove('active'));
    });

    // Cerrar menú al hacer clic fuera de él
    document.addEventListener('click', (e) => {
      const isClickOnToggle = (toggleBtnMobile && toggleBtnMobile.contains(e.target)) ||
                             (toggleBtnDesktop && toggleBtnDesktop.contains(e.target));
      if (!navLinks.contains(e.target) && !isClickOnToggle) {
        navLinks.classList.remove('active');
      }
    });
  }
  
  // Cargar categorías y setup de eventos
  cargarCategoriasSubmenu();
  setTimeout(() => setupProductosLink(), 100);
}

// Ejecutar cuando el DOM esté listo
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', inicializarNavbar);
} else {
  inicializarNavbar();
}

// ════════════════════════════════════════════════════════════════════════════
// SECCIÓN 4: DRAWER - MENÚ MÓVIL DESLIZABLE
// ════════════════════════════════════════════════════════════════════════════

/**
 * HTML del drawer (menú móvil deslizable desde la izquierda)
 * Incluye:
 * - Búsqueda de productos
 * - Menú de navegación
 * - Submenu expandible de productos
 */
const drawerHTML = `
  <div class="navbar-bs__drawer-overlay" id="drawerOverlay"></div>
  <div class="navbar-bs__drawer" id="navbarDrawer">
    <!-- Header del Drawer -->
    <div class="navbar-bs__drawer-header">
      <img src="/assets/img principales/logo.png" alt="B S PERÚ" class="navbar-bs__drawer-logo">
      <button type="button" class="navbar-bs__drawer-close" id="drawerCloseBtn" aria-label="Cerrar menú">
        <i class="bi bi-x"></i>
      </button>
    </div>

    <!-- Búsqueda en Drawer -->
    <form class="navbar-bs__drawer-search" id="drawerSearchForm" role="search">
      <input id="drawerProductSearch" class="navbar-bs__drawer-search-input" type="search" placeholder="Buscar producto o SKU..." autocomplete="off" aria-label="Buscar productos">
      <div id="drawerSearchSuggestions" class="navbar-bs__drawer-search-suggestions" style="display: none;"></div>
    </form>

    <!-- Menú de navegación -->
    <ul class="navbar-bs__drawer-nav">
      <li><a href="/views/NuestrasMarcas.html" class="navbar-bs__drawer-link">Nuestras Marcas</a></li>
      <li><a href="/views/ubicacion.html" class="navbar-bs__drawer-link">Sucursales</a></li>
      
      <!-- Productos con submenu expandible -->
      <li class="navbar-bs__drawer-submenu">
        <button type="button" class="navbar-bs__drawer-link navbar-bs__drawer-submenu-toggle" id="drawerProductosToggle">
          Productos
          <i class="bi bi-chevron-right"></i>
        </button>
        <div class="navbar-bs__drawer-submenu-panel" id="drawerProductosPanel">
          <a href="/views/catalogo.html" class="navbar-bs__drawer-submenu-item">Todos</a>
          <div class="navbar-bs__drawer-submenu-divider"></div>
          <a href="/views/catalogo.html?cat=ADHESIVO%20EPOXICO" class="navbar-bs__drawer-submenu-item">Adhesivo Epóxico</a>
          <a href="/views/catalogo.html?cat=CAPUCHONES" class="navbar-bs__drawer-submenu-item">Capuchones</a>
          <a href="/views/catalogo.html?cat=CURADORES" class="navbar-bs__drawer-submenu-item">Curadores</a>
          <a href="/views/catalogo.html?cat=DESMOLDANTES" class="navbar-bs__drawer-submenu-item">Desmoldantes</a>
          <a href="/views/catalogo.html?cat=FRAGUAS" class="navbar-bs__drawer-submenu-item">Fraguas</a>
          <a href="/views/catalogo.html?cat=IMPERMEABILIZANTES" class="navbar-bs__drawer-submenu-item">Impermeabilizantes</a>
          <a href="/views/catalogo.html?cat=INCORPORADOR%20DE%20AIRE" class="navbar-bs__drawer-submenu-item">Incorporador de Aire</a>
          <a href="/views/catalogo.html?cat=MORTEROS%20DE%20REPARACION" class="navbar-bs__drawer-submenu-item">Morteros de Reparación</a>
          <a href="/views/catalogo.html?cat=OTROS" class="navbar-bs__drawer-submenu-item">Otros</a>
          <a href="/views/catalogo.html?cat=PEGAMENTOS" class="navbar-bs__drawer-submenu-item">Pegamentos</a>
          <a href="/views/catalogo.html?cat=PLASTIFICANTE" class="navbar-bs__drawer-submenu-item">Plastificante</a>
          <a href="/views/catalogo.html?cat=REMOVEDORES" class="navbar-bs__drawer-submenu-item">Removedores</a>
          <a href="/views/catalogo.html?cat=RESINAS" class="navbar-bs__drawer-submenu-item">Resinas</a>
          <a href="/views/catalogo.html?cat=SELLADORES" class="navbar-bs__drawer-submenu-item">Selladores</a>
          <a href="/views/catalogo.html?cat=SEPARADORES" class="navbar-bs__drawer-submenu-item">Separadores</a>
          <a href="/views/catalogo.html?cat=SOLVENTES" class="navbar-bs__drawer-submenu-item">Solventes</a>
          <a href="/views/catalogo.html?cat=WATER%20STOPS" class="navbar-bs__drawer-submenu-item">Water Stops</a>
        </div>
      </li>

      <li><a href="/views/contacto.html" class="navbar-bs__drawer-link">Contactanos</a></li>
    </ul>
  </div>
`;

/**
 * FUNCIÓN: Inyecta el drawer en el body
 * Se ejecuta en todas las páginas que tienen .barrera-menu
 * Solo inyecta UNA VEZ (evita duplicados)
 */
function inyectarDrawer() {
  const barreraMenu = document.querySelector('.barrera-menu');
  if (!barreraMenu) return; // No inyectar si no existe barrera-menu
  
  if (document.getElementById('navbarDrawer')) return; // Evitar duplicados
  
  document.body.insertAdjacentHTML('afterbegin', drawerHTML);
  
  // Setup de event listeners del drawer
  setTimeout(() => {
    const toggleBtnMobile = document.getElementById('navbar-bs-toggle-mobile');
    const toggleBtnDesktop = document.getElementById('navbar-bs-toggle-desktop');
    const drawer = document.getElementById('navbarDrawer');
    const drawerOverlay = document.getElementById('drawerOverlay');
    const drawerCloseBtn = document.getElementById('drawerCloseBtn');
    const drawerProductosToggle = document.getElementById('drawerProductosToggle');
    const drawerProductosPanel = document.getElementById('drawerProductosPanel');
    const navbar = document.querySelector('.navbar-bs');
    
    // Función para abrir/cerrar drawer
    function toggleDrawer() {
      drawer?.classList.toggle('active');
      drawerOverlay?.classList.toggle('active');
      navbar?.classList.toggle('navbar-hidden');
    }
    
    // Click en botones hamburguesa
    if (toggleBtnMobile && drawer) toggleBtnMobile.addEventListener('click', toggleDrawer);
    if (toggleBtnDesktop && drawer) toggleBtnDesktop.addEventListener('click', toggleDrawer);
    
    // Click en botón cerrar del drawer
    if (drawerCloseBtn) drawerCloseBtn.addEventListener('click', toggleDrawer);
    
    // Click en overlay para cerrar
    if (drawerOverlay) drawerOverlay.addEventListener('click', toggleDrawer);
    
    // Toggle submenu de Productos
    if (drawerProductosToggle && drawerProductosPanel) {
      drawerProductosToggle.addEventListener('click', (e) => {
        e.preventDefault();
        drawerProductosPanel.classList.toggle('active');
        drawerProductosToggle.classList.toggle('active');
      });
    }
    
    // Cerrar drawer al hacer clic en un enlace
    if (drawer) {
      drawer.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => {
          drawer.classList.remove('active');
          drawerOverlay.classList.remove('active');
          navbar.classList.remove('navbar-hidden');
          drawerProductosPanel?.classList.remove('active');
          drawerProductosToggle?.classList.remove('active');
        });
      });
    }
  }, 200);
}

// Ejecutar inyección del drawer cuando el DOM esté listo
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', inyectarDrawer);
} else {
  inyectarDrawer();
}

// ════════════════════════════════════════════════════════════════════════════
// SECCIÓN 5: BÚSQUEDA DE PRODUCTOS - DRAWER
// ════════════════════════════════════════════════════════════════════════════

/** Cache de productos para búsqueda rápida */
let productosCache = [];

/**
 * Carga todos los productos desde el JSON
 * Se ejecuta al iniciar la página para llenar el cache
 */
async function cargarProductosSearch() {
  try {
    const response = await fetch('/assets/Data/productos.json');
    if (response.ok) {
      productosCache = await response.json();      
    }
  } catch (error) {
    console.error('Error al cargar productos:', error);
  }
}

cargarProductosSearch();

/**
 * Busca productos por nombre, SKU o marca
 * Retorna máximo 10 resultados
 */
function buscarProductos(query) {
  if (!query || query.trim().length < 1) return [];
  
  const searchTerm = query.toLowerCase().trim();
  
  return productosCache.filter(producto => 
    String(producto.nombre || '').toLowerCase().includes(searchTerm) ||
    String(producto.sku || '').toLowerCase().includes(searchTerm) ||
    (producto.marca && String(producto.marca).toLowerCase().includes(searchTerm))
  ).slice(0, 10);
}

/**
 * Muestra sugerencias de búsqueda en el drawer
 * Incluye imagen, nombre del producto y enlace
 */
function mostrarSugerenciasDrawer(productos, query) {
  const suggestionsDiv = document.getElementById('drawerSearchSuggestions');
  
  if (!suggestionsDiv) return;
  
  if (!productos.length) {
    suggestionsDiv.innerHTML = `
      <div style="padding: 15px 12px; text-align: center; color: #999; font-size: 0.9rem;">
        No hay productos con "${query}"
      </div>
    `;
    suggestionsDiv.style.display = 'block';
    return;
  }
  
  suggestionsDiv.innerHTML = productos.map(producto => `
    <a href="/views/producto.html?sku=${encodeURIComponent(producto.sku)}" 
       class="navbar-bs__drawer-suggestion-item"
       style="display: flex; gap: 12px; padding: 10px 12px; border-bottom: 1px solid #f0f0f0; text-decoration: none; color: inherit; transition: background 0.2s; align-items: center;">
      <img src="${producto.imagen}" alt="${producto.nombre}" 
           style="width: 60px; height: 60px; object-fit: contain; background: #f5f5f5; padding: 4px; border-radius: 4px; flex-shrink: 0;">
      <div style="flex: 1; min-width: 0;">
        <div style="font-weight: 500; color: #333; margin-bottom: 3px; font-size: 0.9rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
          ${producto.nombre}
        </div>
      </div>
    </a>
  `).join('');
  
  // Efecto hover
  suggestionsDiv.querySelectorAll('a').forEach(link => {
    link.addEventListener('mouseenter', () => link.style.background = '#f9f9f9');
    link.addEventListener('mouseleave', () => link.style.background = 'transparent');
  });
  
  suggestionsDiv.style.display = 'block';
}

/** Oculta las sugerencias del drawer */
function ocultarSugerenciasDrawer() {
  const suggestionsDiv = document.getElementById('drawerSearchSuggestions');
  if (suggestionsDiv) {
    suggestionsDiv.style.display = 'none';
    suggestionsDiv.innerHTML = '';
  }
}

/**
 * Setup: Configura el buscador en el drawer
 * Búsqueda instantánea sin debounce
 */
function setupBuscadorDrawer() {
  const searchInput = document.getElementById('drawerProductSearch');
  
  if (!searchInput) return;
  
  // Evento de entrada - búsqueda instantánea
  searchInput.addEventListener('input', (e) => {
    const query = e.target.value;
    
    if (!query || query.trim().length < 1) {
      ocultarSugerenciasDrawer();
      return;
    }
    
    const resultados = buscarProductos(query);
    mostrarSugerenciasDrawer(resultados, query);
  });
  
  // Mostrar sugerencias al recibir foco
  searchInput.addEventListener('focus', (e) => {
    if (e.target.value.trim().length >= 1) {
      const resultados = buscarProductos(e.target.value);
      mostrarSugerenciasDrawer(resultados, e.target.value);
    }
  });
  
  // Ocultar sugerencias al perder foco
  searchInput.addEventListener('blur', () => {
    setTimeout(() => ocultarSugerenciasDrawer(), 200);
  });
}

setTimeout(() => setupBuscadorDrawer(), 500);

// ════════════════════════════════════════════════════════════════════════════
// SECCIÓN 6: BÚSQUEDA DE PRODUCTOS - NAVBAR
// ════════════════════════════════════════════════════════════════════════════

/**
 * Muestra sugerencias en el navbar
 * Misma funcionalidad que el drawer pero en el navbar
 */
function mostrarSugerenciasNavbar(productos, query) {
  const suggestionsDiv = document.getElementById('layoutSearchSuggestions');
  
  if (!suggestionsDiv) return;
  
  if (!productos.length) {
    suggestionsDiv.innerHTML = `
      <div style="padding: 15px 12px; text-align: center; color: #999; font-size: 0.9rem;">
        No hay productos con "${query}"
      </div>
    `;
    suggestionsDiv.style.display = 'block';
    return;
  }
  
  suggestionsDiv.innerHTML = productos.map(producto => `
    <a href="/views/producto.html?sku=${encodeURIComponent(producto.sku)}" 
       class="navbar-suggestion-item"
       style="display: flex; gap: 12px; padding: 10px 12px; border-bottom: 1px solid #f0f0f0; text-decoration: none; color: inherit; transition: background 0.2s; align-items: center;">
      <img src="${producto.imagen}" alt="${producto.nombre}" 
           style="width: 60px; height: 60px; object-fit: contain; background: #f5f5f5; padding: 4px; border-radius: 4px; flex-shrink: 0;">
      <div style="flex: 1; min-width: 0;">
        <div style="font-weight: 500; color: #333; margin-bottom: 3px; font-size: 0.9rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
          ${producto.nombre}
        </div> 
      </div>
    </a>
  `).join('');
  
  suggestionsDiv.querySelectorAll('a').forEach(link => {
    link.addEventListener('mouseenter', () => link.style.background = '#f9f9f9');
    link.addEventListener('mouseleave', () => link.style.background = 'transparent');
  });
  
  suggestionsDiv.style.display = 'block';
}

/** Oculta las sugerencias del navbar */
function ocultarSugerenciasNavbar() {
  const suggestionsDiv = document.getElementById('layoutSearchSuggestions');
  if (suggestionsDiv) {
    suggestionsDiv.style.display = 'none';
    suggestionsDiv.innerHTML = '';
  }
}

/**
 * Setup: Configura el buscador en el navbar
 * Búsqueda instantánea sin debounce
 */
function setupBuscadorNavbar() {
  let searchInput = document.querySelector('input[name="q"]');
  if (!searchInput) searchInput = document.getElementById('layoutProductSearch');
  
  if (!searchInput) return;
  
  const form = searchInput.closest('form');
  if (!form) return;
  
  const suggestionsDiv = document.getElementById('layoutSearchSuggestions');
  if (!suggestionsDiv) return;
  
  // Prevenir submit del formulario
  form.addEventListener('submit', (e) => e.preventDefault());
  
  // Evento de entrada - búsqueda instantánea
  searchInput.addEventListener('input', (e) => {
    const query = e.target.value;
    
    if (!query || query.trim().length < 1) {
      ocultarSugerenciasNavbar();
      return;
    }
    
    const resultados = buscarProductos(query);
    mostrarSugerenciasNavbar(resultados, query);
  });
  
  // Mostrar sugerencias al recibir foco
  searchInput.addEventListener('focus', (e) => {
    if (e.target.value.trim().length >= 1) {
      const resultados = buscarProductos(e.target.value);
      mostrarSugerenciasNavbar(resultados, e.target.value);
    }
  });
  
  // Ocultar sugerencias al perder foco
  searchInput.addEventListener('blur', () => {
    setTimeout(() => ocultarSugerenciasNavbar(), 200);
  });
  
  // Cerrar sugerencias al hacer clic en una
  suggestionsDiv.addEventListener('click', (e) => {
    const link = e.target.closest('a');
    if (link) {
      searchInput.value = '';
      ocultarSugerenciasNavbar();
    }
  });
}

setTimeout(() => setupBuscadorNavbar(), 500);

// ════════════════════════════════════════════════════════════════════════════
// SECCIÓN 7: CARRITO - VARIABLES Y FUNCIONES BÁSICAS
// ════════════════════════════════════════════════════════════════════════════

/** Array global que almacena los items del carrito */
let carritoLayout = JSON.parse(localStorage.getItem('carrito') || '[]');

/**
 * Sincroniza el carrito en memoria con localStorage
 * Se llama después de cualquier cambio al carrito
 */
function syncCarritoLayout() {
  localStorage.setItem('carrito', JSON.stringify(carritoLayout));
}

/**
 * Actualiza el badge del carrito (número rojo de cantidad)
 * Se muestra solo si hay items en el carrito
 */
function actualizarBadgeCarrito() {
  const cartBadge = document.getElementById('cartBadge');
  if (!cartBadge) return;
  
  carritoLayout = JSON.parse(localStorage.getItem('carrito') || '[]');
  const cantidadTotal = carritoLayout.reduce((sum, item) => sum + Number(item.cantidad || 0), 0);
  
  cartBadge.textContent = cantidadTotal;
  cartBadge.style.display = cantidadTotal > 0 ? 'inline-block' : 'none';
}

/**
 * Crea los modales del carrito y checkout si no existen
 * Incluye:
 * - Modal del carrito (ver items, aumentar/disminuir cantidad)
 * - Modal de checkout (formulario de datos de envío)
 */
function ensureCarritoLayoutUI() {
  if (!document.getElementById('carritoModal')) {
    document.body.insertAdjacentHTML('beforeend', `
      <div class="layout-cart-modal" id="carritoModal" aria-hidden="true">
        <div class="layout-cart-backdrop" data-close="carritoModal"></div>
        <div class="layout-cart-panel" role="dialog" aria-modal="true" aria-labelledby="carritoModalTitle">
          <div class="layout-cart-header">
            <h5 class="fw-bold mb-0" id="carritoModalTitle">Tu carrito</h5>
            <button type="button" class="btn-close" data-close="carritoModal" aria-label="Cerrar"></button>
          </div>
          <div class="layout-cart-body p-3" id="carritoBody"></div>
          <div class="layout-cart-footer d-flex flex-column gap-2">
            <div class="d-flex gap-2 w-100">
              <button id="btnCancelarCompra" class="btn btn-outline-danger w-100">Cancelar compra</button>
              <button id="btnFinalizarCompra" class="btn btn-success w-100">Finalizar compra</button>
            </div>
          </div>
        </div>
      </div>
    `);
  }

  if (!document.getElementById('checkoutModal')) {
    document.body.insertAdjacentHTML('beforeend', `
      <div class="layout-cart-modal" id="checkoutModal" aria-hidden="true">
        <div class="layout-cart-backdrop" data-close="checkoutModal"></div>
        <div class="layout-checkout-panel" role="dialog" aria-modal="true" aria-labelledby="checkoutModalTitle">
          <div class="layout-cart-header">
            <h5 class="fw-bold mb-0" id="checkoutModalTitle">Finalizar compra</h5>
            <button type="button" class="btn-close" data-close="checkoutModal" aria-label="Cerrar"></button>
          </div>
          <div class="layout-cart-body">
            <form id="checkoutForm" class="row g-3">
                <div class="col-md-6"><label class="form-label">Nombre</label><input class="form-control" name="nombre" required></div>
                <div class="col-md-6"><label class="form-label">Apellido</label><input class="form-control" name="apellido" required></div>
                <div class="col-md-6">
                  <label class="form-label">Tipo de documento <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <select class="form-select" name="tipodoc" id="tipodoc" style="max-width:90px;flex:0 0 90px" required>
                      <option value="">--</option>
                      <option value="DNI">DNI</option>
                      <option value="RUC">RUC</option>
                    </select>
                    <input class="form-control" name="numerodoc" id="numerodoc" type="text" inputmode="numeric" placeholder="Selecciona el tipo primero" readonly>
                  </div>
                </div>
                <div class="col-md-6"><label class="form-label">Teléfono</label><input class="form-control" name="telefono" required></div>
                <div class="col-12"><label class="form-label">Dirección</label><input class="form-control" name="direccion" required></div>
                <div class="col-12"><label class="form-label">Correo electrónico</label><input class="form-control" type="email" name="email" required></div>
                <div class="col-md-6">
                  <label class="form-label">Método de entrega</label>
                  <select class="form-select" name="metodoRecojo" id="metodoRecojo" required>
                    <option value="delivery">Delivery</option>
                    <option value="recojo">Recojo en tienda</option>
                  </select>
                </div>
                <div class="col-md-6" id="tiendaUbicacionWrapper">
                  <label class="form-label">Ubicación de las tiendas</label>
                  <select class="form-select" name="tiendaUbicacion" id="tiendaUbicacion">
                    <option value="">-- Selecciona una tienda (opcional) --</option>
                    <option value="LIMA , CHORRILLOS">Lima, Chorrillos</option>
                    <option value="LIMA , SAN BORJA">Lima, San Borja</option>
                    <option value="CALLAO , BELLAVISTA">Callao, Bellavista</option>
                    <option value="TRUJILLO , TRUJILLO">Trujillo</option>
                    <option value="PIURA , SULLANA">Piura, Sullana</option>
                    <option value="PIURA , PIURA">Piura</option>
                    <option value="CHICLAYO , CHICLAYO">Chiclayo</option>
                  </select>
                </div>
                <div class="col-12">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="autorizarFactura" name="autorizarFactura" required>
                    <label class="form-check-label" for="autorizarFactura">
                      Autorizo el uso de mis datos para emisión de factura <span class="text-danger">*</span>
                      <a href="#" style="color:#0066cc;cursor:pointer;text-decoration:underline;margin-left:5px;font-size:0.9em" onclick="event.preventDefault(); openLayoutModal('terminosCondicionesModal')">Ver más...</a>
                    </label>
                  </div>
                </div>
                <div class="col-12">
                  <label class="form-label">Método de pago</label>
                  <select class="form-select" name="metodoPago" required>
                    <option value="efectivo">Efectivo</option>
                    <option value="yape">Yape</option>
                    <option value="plin">Plin</option>
                    <option value="transferencia">Transferencia</option>
                  </select>
                </div>
                <div class="col-12 text-end">
                  <button type="submit" class="btn btn-success">Enviar pedido</button>
                </div>
            </form>
          </div>
        </div>
      </div>
    `);
  }
}

// ════════════════════════════════════════════════════════════════════════════
// SECCIÓN 8: MODALES - FUNCIONES DE CONTROL
// ════════════════════════════════════════════════════════════════════════════

/**
 * Abre un modal: añade clase is-open y remueve aria-hidden
 * Estrategia consistente: aria-hidden solo se usa cuando está CERRADO
 * @param {string} id - ID del modal a abrir
 */
function openLayoutModal(id) {
  const modal = document.getElementById(id);
  if (!modal) {
    console.warn(`⚠️ Modal "${id}" no encontrado`);
    return;
  }
  modal.classList.add('is-open');
  modal.removeAttribute('aria-hidden');
  document.body.style.overflow = 'hidden';
}

/**
 * Cierra un modal: remueve clase is-open y pone aria-hidden='true'
 * Solo oculta body scroll si NO hay NINGÚN modal abierto
 * @param {string} id - ID del modal a cerrar
 */
function closeLayoutModal(id) {
  const modal = document.getElementById(id);
  if (!modal) {
    console.warn(`⚠️ Modal "${id}" no encontrado`);
    return;
  }
  
  modal.classList.remove('is-open');
  modal.setAttribute('aria-hidden', 'true');
  
  // Solo restaura scroll si NO hay NINGÚN modal abierto
  const abiertosTotal = document.querySelectorAll('.layout-cart-modal.is-open').length;
  if (abiertosTotal === 0) {
    document.body.style.overflow = '';
  }
}

/**
 * Muestra notificación bonita cuando el usuario olvida marcar el checkbox
 * de autorización de datos para la factura
 * 
 * Incluye:
 * - Explicación clara de por qué es obligatorio
 * - Botón que enfoca el checkbox
 * - Diseño visual atractivo con gradiente
 */
function showAuthorizationNotification() {
  let notifModal = document.getElementById('authorizationNotificationModal');
  if (!notifModal) {
    document.body.insertAdjacentHTML('beforeend', `
      <div id="authorizationNotificationModal" class="layout-cart-modal" aria-hidden="true">
        <div class="layout-cart-backdrop" onclick="document.getElementById('authorizationNotificationModal').classList.remove('is-open')"></div>
        <div class="layout-cart-panel" style="max-width: 500px; background: linear-gradient(135deg, #005587 0%, #003d5c 100%); color: white; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
          <div style="padding: 30px 25px; text-align: center;">
            <div style="font-size: 48px; margin-bottom: 15px; animation: bounce 0.5s ease-in-out;">
              📋
            </div>
            <h5 style="margin-bottom: 15px; font-weight: 700; font-size: 1.3rem; color: white;">
              Autorización Necesaria
            </h5>
            <p style="margin-bottom: 20px; font-size: 0.95rem; line-height: 1.6; color: rgba(255,255,255,0.95);">
              Necesitamos tu autorización para usar tus datos personales en la emisión de tu factura, cumpliendo con la ley de protección de datos.
            </p>
            <div style="background: rgba(255,255,255,0.15); padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: left; font-size: 0.85rem;">
              <strong style="display: block; margin-bottom: 8px;">¿Por qué es obligatorio?</strong>
              <ul style="margin: 0; padding-left: 18px;">
                <li style="margin-bottom: 5px;">Emitir documentos tributarios válidos</li>
                <li style="margin-bottom: 5px;">Cumplir leyes fiscales y de protección</li>
                <li>Mantener tu compra registrada y segura</li>
              </ul>
            </div>
            <button onclick="document.getElementById('authorizationNotificationModal').classList.remove('is-open'); document.getElementById('autorizarFactura').focus();" style="background: white; color: #005587; border: none; padding: 12px 30px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 0.95rem; width: 100%; transition: all 0.3s ease;">
              Entendido, Marcar Autorización
            </button>
          </div>
        </div>
      </div>
      <style>
        @keyframes bounce {
          0%, 100% { transform: translateY(0); }
          50% { transform: translateY(-10px); }
        }
      </style>
    `);
    notifModal = document.getElementById('authorizationNotificationModal');
  }
  
  notifModal.classList.add('is-open');
  notifModal.removeAttribute('aria-hidden');
  document.body.style.overflow = 'hidden';
}

// ════════════════════════════════════════════════════════════════════════════
// SECCIÓN 9: CARRITO - RENDERIZADO Y MODIFICACIÓN
// ════════════════════════════════════════════════════════════════════════════

/**
 * Renderiza los items del carrito en el modal
 * Muestra imagen, nombre, cantidad y botones de acción
 */
function renderCarritoLayout() {
  carritoLayout = JSON.parse(localStorage.getItem('carrito') || '[]');

  const carritoBody = document.getElementById('carritoBody');

  if (carritoBody) {
    if (!carritoLayout.length) {
      carritoBody.innerHTML = '<p class="text-center text-secondary mb-0">Tu carrito está vacío.</p>';
    } else {
      carritoBody.innerHTML = carritoLayout.map((item, index) => `
        <div class="d-flex align-items-center gap-2 mb-3 pb-3 border-bottom">
          <img src="${item.img || '/assets/img principales/logo.png'}" alt="${item.nombre}" style="width:56px;height:56px;object-fit:contain;background:#fff;border-radius:.5rem;padding:.25rem;" onerror="this.src='/assets/img principales/logo.png'">
          <div class="flex-grow-1">
            <div class="fw-semibold small mb-1">${item.nombre}</div>
            <div class="small text-secondary">Cantidad: ${item.cantidad}</div>
          </div>
          <div class="d-flex align-items-center gap-1">
            <button class="btn btn-sm btn-outline-secondary" data-action="dec" data-index="${index}">-</button>
            <button class="btn btn-sm btn-outline-secondary" data-action="inc" data-index="${index}">+</button>
            <button class="btn btn-sm btn-outline-danger" data-action="del" data-index="${index}" title="Eliminar">
              <i class="bi bi-trash"></i>
            </button>
          </div>
        </div>
      `).join('');
    }
  }

  actualizarBadgeCarrito();
}

/**
 * Cambia la cantidad de un item en el carrito
 * Si la cantidad llega a 0, elimina el item
 * @param {number} index - Índice del item
 * @param {number} delta - Cantidad a sumar/restar (-1, +1, etc)
 */
function cambiarCantidadLayout(index, delta) {
  if (!carritoLayout[index]) return;
  carritoLayout[index].cantidad += delta;

  if (carritoLayout[index].cantidad <= 0) {
    carritoLayout.splice(index, 1);
  }

  syncCarritoLayout();
  renderCarritoLayout();
}

/**
 * Elimina un item del carrito por su índice
 * @param {number} index - Índice del item a eliminar
 */
function eliminarItemCarritoLayout(index) {
  if (!carritoLayout[index]) return;
  carritoLayout.splice(index, 1);
  syncCarritoLayout();
  renderCarritoLayout();
}

// ════════════════════════════════════════════════════════════════════════════
// SECCIÓN 10: NOTIFICACIONES - SISTEMA DE TOASTS
// ════════════════════════════════════════════════════════════════════════════

/**
 * Muestra notificaciones toast (esquina superior derecha)
 * Desaparece automáticamente después de 3.5 segundos
 * 
 * Tipos: success, warning, danger, info
 * 
 * @param {string} mensaje - Texto a mostrar
 * @param {string} tipo - Tipo de notificación (success|warning|danger|info)
 */
function showLayoutToast(mensaje, tipo = 'warning') {
  const iconos = {
    success: 'bi-check-circle-fill',
    warning: 'bi-exclamation-triangle-fill',
    danger:  'bi-x-circle-fill',
    info:    'bi-info-circle-fill'
  };
  const colores = {
    success: '#198754',
    warning: '#f59e0b',
    danger:  '#dc3545',
    info:    '#0d6efd'
  };

  let container = document.getElementById('layoutToastContainer');
  if (!container) {
    container = document.createElement('div');
    container.id = 'layoutToastContainer';
    container.style.cssText = 'position:fixed;top:20px;right:20px;z-index:99999;display:flex;flex-direction:column;gap:10px;pointer-events:none;';
    document.body.appendChild(container);
  }

  const id = 'ltoast-' + Date.now();
  const color = colores[tipo] || colores.warning;
  const icon  = iconos[tipo]  || iconos.warning;

  const el = document.createElement('div');
  el.id = id;
  el.style.cssText = `
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.18);
    padding: 14px 18px;
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 280px;
    max-width: 360px;
    pointer-events: all;
    border-left: 4px solid ${color};
    animation: ltoastIn .25s ease;
    font-family: inherit;
    font-size: 0.92rem;
    color: #1a1a2e;
  `;
  el.innerHTML = `
    <i class="bi ${icon}" style="color:${color};font-size:1.3rem;flex-shrink:0"></i>
    <span style="flex:1;line-height:1.4">${mensaje}</span>
    <button onclick="this.closest('#${id}').remove()" style="background:none;border:none;cursor:pointer;color:#aaa;font-size:1.1rem;padding:0;line-height:1" title="Cerrar">×</button>
  `;

  if (!document.getElementById('ltoastStyles')) {
    const style = document.createElement('style');
    style.id = 'ltoastStyles';
    style.textContent = '@keyframes ltoastIn{from{opacity:0;transform:translateX(30px)}to{opacity:1;transform:translateX(0)}} @keyframes ltoastOut{from{opacity:1;transform:translateX(0)}to{opacity:0;transform:translateX(30px)}}';
    document.head.appendChild(style);
  }

  container.appendChild(el);
  setTimeout(() => {
    el.style.animation = 'ltoastOut .25s ease forwards';
    setTimeout(() => el.remove(), 260);
  }, 3500);
}

// ════════════════════════════════════════════════════════════════════════════
// SECCIÓN 11: CHECKOUT - PROCESAMIENTO DE COMPRA
// ════════════════════════════════════════════════════════════════════════════

/**
 * Procesa el envío de la compra a través de WhatsApp
 * Realiza validaciones antes de enviar:
 * - Checkbox de autorización marcado
 * - Carrito no vacío
 * - Documento válido (DNI: 8 dígitos, RUC: 10 dígitos)
 * - Método de entrega válido
 * 
 * Genera mensaje formateado y abre WhatsApp con la orden
 * 
 * @param {HTMLFormElement} formElement - El formulario de checkout
 * @returns {boolean} - True si se envió exitosamente
 */
function finalizarCompraLayout(formElement) {
  // VALIDACIÓN: Verificar checkbox de autorización
  const checkboxAutorizacion = document.getElementById('autorizarFactura');
  
  if (!checkboxAutorizacion) {
    showLayoutToast('ERROR: Campo de autorización de factura no encontrado.', 'danger');
    return;
  }

  if (!checkboxAutorizacion.checked) {
    showAuthorizationNotification();
    return;
  }

  const formData = new FormData(formElement);
  const data = Object.fromEntries(formData.entries());

  // Validar carrito no vacío
  if (!carritoLayout.length) {
    showLayoutToast('Tu carrito está vacío. Agrega productos antes de finalizar la compra.', 'warning');
    return;
  }

  // Validar tipo y número de documento
  const tipoDoc = (data.tipodoc || '').trim();
  const numDoc = (data.numerodoc || '').trim();
  if (!tipoDoc) {
    showLayoutToast('Selecciona el tipo de documento (DNI o RUC).', 'warning');
    return;
  }
  if (tipoDoc === 'DNI' && !/^\d{8}$/.test(numDoc)) {
    showLayoutToast('El DNI debe contener exactamente 8 dígitos numéricos.', 'danger');
    return;
  }
  if (tipoDoc === 'RUC' && !/^\d{10}$/.test(numDoc)) {
    showLayoutToast('El RUC debe contener exactamente 10 dígitos numéricos.', 'danger');
    return;
  }

  if (data.metodoRecojo === 'recojo' && !data.tiendaUbicacion) {
    showLayoutToast('Selecciona la tienda para el recojo.', 'warning');
    return;
  }

  // Construir mensaje para WhatsApp
  const autorizacion = formData.get('autorizarFactura') ? 'Sí' : 'No';
  let mensaje = '*NUEVO PEDIDO*\n\n';

  mensaje += '*Datos del cliente:*\n';
  mensaje += `Nombre: ${data.nombre || '-'} ${data.apellido || '-'}\n`;
  mensaje += `${tipoDoc}: ${numDoc || '-'}\n`;
  mensaje += `Teléfono: ${data.telefono || '-'}\n`;
  mensaje += `Dirección: ${data.direccion || '-'}\n`;
  mensaje += `Correo: ${data.email || '-'}\n`;
  mensaje += `Entrega: ${data.metodoRecojo === 'recojo' ? 'Recojo en tienda' : 'Delivery'}\n`;
  mensaje += `Tienda: ${data.tiendaUbicacion || 'No especificada'}\n`;
  mensaje += `Método de pago: ${data.metodoPago || '-'}\n`;
  mensaje += `Autorización de factura: ${autorizacion}\n`;
  mensaje += '----------------------\n';
  mensaje += '*Productos:*\n';

  if (!carritoLayout.length) {
    mensaje += '- (carrito vacío)\n';
  } else {
    carritoLayout.forEach((item, index) => {
      mensaje += `${index + 1}. ${item.nombre} (x${item.cantidad})\n`;
    });
  }

  mensaje += '----------------------\n';
  mensaje += 'Gracias por su compra';

  // Enviar a WhatsApp
  const url = `https://api.whatsapp.com/send?phone=${window.WHATSAPP_NUMBER}&text=${encodeURIComponent(mensaje)}`;
  const popupWindow = window.open(url, '_blank');
  if (!popupWindow) window.location.href = url;

  // Limpiar
  carritoLayout = [];
  syncCarritoLayout();
  renderCarritoLayout();
  closeLayoutModal('checkoutModal');
  formElement.reset();
  showLayoutToast('Pedido enviado correctamente.', 'success');
  return true;
}

// ════════════════════════════════════════════════════════════════════════════
// SECCIÓN 12: CARRITO - EVENT LISTENERS
// ════════════════════════════════════════════════════════════════════════════

/**
 * Setup: Configura todos los event listeners del carrito y checkout
 * Incluye:
 * - Click en botón de carrito
 * - Aumentar/disminuir cantidad
 * - Eliminar items
 * - Cancelar/Finalizar compra
 * - Cambio de método de entrega
 * - Validación de documento
 */
function setupCarritoLayoutEvents() {
  document.addEventListener('click', (event) => {
    // Abrir modal del carrito
    if (event.target.closest('#btnCarritoNav')) {
      event.preventDefault();
      event.stopPropagation();
      renderCarritoLayout();
      openLayoutModal('carritoModal');
      return;
    }

    // Cerrar modal (botón X o backdrop)
    const closeBtn = event.target.closest('[data-close]');
    if (closeBtn) {
      event.preventDefault();
      event.stopPropagation();
      const modalId = closeBtn.getAttribute('data-close');
      if (modalId) closeLayoutModal(modalId);
      return;
    }

    // Manejar clics en botones de acción del carrito (inc/dec/del)
    const actionButton = event.target.closest('#carritoBody button[data-action]');
    if (actionButton) {
      event.preventDefault();
      event.stopPropagation();
      const action = actionButton.dataset.action;
      const index = Number(actionButton.dataset.index);

      if (action === 'dec') cambiarCantidadLayout(index, -1);
      if (action === 'inc') cambiarCantidadLayout(index, 1);
      if (action === 'del') eliminarItemCarritoLayout(index);
      return;
    }

    // Cancelar compra (vacía el carrito)
    if (event.target.closest('#btnCancelarCompra')) {
      event.preventDefault();
      event.stopPropagation();
      carritoLayout = [];
      syncCarritoLayout();
      renderCarritoLayout();
      closeLayoutModal('carritoModal');
      return;
    }

    // Finalizar compra (abre modal de checkout)
    if (event.target.closest('#btnFinalizarCompra')) {
      event.preventDefault();
      event.stopPropagation();
      const carritoActual = JSON.parse(localStorage.getItem('carrito') || '[]');
      if (!carritoActual.length) {
        showLayoutToast('Tu carrito está vacío. Agrega productos antes de finalizar la compra.', 'warning');
        return;
      }
      closeLayoutModal('carritoModal');
      openLayoutModal('checkoutModal');
      return;
    }
  }, false);

  // Submit del formulario de checkout
  document.addEventListener('submit', (event) => {
    if (event.target.id !== 'checkoutForm') return;
    event.preventDefault();
    finalizarCompraLayout(event.target);
  });

  // Cambio de método de entrega (mostrar/ocultar tienda)
  document.addEventListener('change', (event) => {
    if (event.target.id !== 'metodoRecojo') return;

    const tiendaWrapper = document.getElementById('tiendaUbicacionWrapper');
    if (tiendaWrapper) {
      tiendaWrapper.style.display = event.target.value === 'recojo' ? '' : 'none';
    }
  });

  // Cambio en el tipo de documento (DNI/RUC)
  document.addEventListener('change', (event) => {
    if (event.target.id !== 'tipodoc') return;
    const tipo = event.target.value;
    const numInput = document.getElementById('numerodoc');
    if (!numInput) return;
    numInput.value = '';
    if (tipo === 'DNI') {
      numInput.maxLength = 8;
      numInput.setAttribute('minlength', '8');
      numInput.pattern = '[0-9]{8}';
      numInput.placeholder = 'Ej: 12345678';
      numInput.title = 'El DNI debe tener exactamente 8 dígitos';
      numInput.removeAttribute('readonly');
    } else if (tipo === 'RUC') {
      numInput.maxLength = 10;
      numInput.setAttribute('minlength', '10');
      numInput.pattern = '[0-9]{10}';
      numInput.placeholder = 'Ej: 1234567890';
      numInput.title = 'El RUC debe tener exactamente 10 dígitos';
      numInput.removeAttribute('readonly');
    } else {
      numInput.removeAttribute('pattern');
      numInput.maxLength = 11;
      numInput.removeAttribute('minlength');
      numInput.placeholder = 'Selecciona el tipo primero';
      numInput.setAttribute('readonly', '');
    }
  });

  // Filtrar entrada de documento (solo números)
  document.addEventListener('input', (event) => {
    if (event.target.name !== 'numerodoc') return;
    const tipoSelect = document.getElementById('tipodoc');
    const tipo = tipoSelect ? tipoSelect.value : '';
    const max = tipo === 'DNI' ? 8 : tipo === 'RUC' ? 10 : 10;
    event.target.value = event.target.value.replace(/[^\d]/g, '').slice(0, max);
  });
}

// ════════════════════════════════════════════════════════════════════════════
// SECCIÓN 13: CATEGORÍAS Y MENÚ PRODUCTOS
// ════════════════════════════════════════════════════════════════════════════

/** Array para almacenar productos en caché */
let productosBuscador = [];

/** Categorías por defecto si no se carga el JSON */
const categoriasFallback = [
  "Desmoldantes",
  "Curadores",
  "IMPERMEABILIZANTES",
  "Selladores",
  "Selladores de junta",
  "Adhesivos Epóxicos",
  "Separadores",
  "Morteros",
  "Aditivos complementarios",
  "Aditivos para concreto"
];

/**
 * Normaliza categorías para comparación
 * Elimina acentos y convierte a minúsculas
 */
const normalizaCategoria = (valor) => (valor || '')
  .toString()
  .normalize('NFD')
  .replace(/[\u0300-\u036f]/g, '')
  .trim()
  .toLowerCase();

/**
 * Renderiza las categorías en el menú de dropdown
 * @param {Array} categorias - Array de categorías a mostrar
 */
function renderCategoriasMenu(categorias) {
  const menu = document.getElementById('menuCategoriasCatalogo');
  if (!menu) return;

  const items = (Array.isArray(categorias) && categorias.length)
    ? categorias
    : categoriasFallback;

  menu.innerHTML = items
    .map(cat => {
      const labelUpper = (cat || '').toString().toUpperCase();
      return `<li><a class="dropdown-item" href="/views/catalogo.html?cat=${encodeURIComponent(cat)}">${labelUpper}</a></li>`;
    })
    .join('');
}

/**
 * Carga las categorías desde JSON y las renderiza
 * También carga productos para búsqueda
 */
function cargarCategoriasMenu() {
  fetch('/assets/Data/productos.json')
    .then(res => {
      if (!res.ok) throw new Error('No se pudo cargar productos.json');
      return res.json();
    })
    .then(data => {
      const categoriasMap = new Map();

      // Almacenar productos para búsqueda
      productosBuscador = (Array.isArray(data) ? data : []).map(p => ({
        sku: String(p?.sku || '').trim(),
        nombre: String(p?.nombre || '').trim(),
        imagen: String(p?.imagen || '').trim(),
        miniaturas: p?.miniaturas ? Object.values(p.miniaturas).filter(img => img !== null && img !== undefined) : []
      }));

      // Extraer categorías únicas
      data.forEach(p => {
        const categoria = (p?.categoria || '').toString().trim();
        const key = normalizaCategoria(categoria);
        if (!key) return;
        if (!categoriasMap.has(key)) categoriasMap.set(key, categoria);
      });

      const categorias = Array.from(categoriasMap.values())
        .sort((a, b) => a.localeCompare(b, 'es', { sensitivity: 'base' }));

      renderCategoriasMenu(categorias);
    })
    .catch(() => {
      productosBuscador = [];
      renderCategoriasMenu(categoriasFallback);
    });
}

/**
 * Carga las categorías en el submenu de productos del navbar
 * Distribuye las categorías en 3 columnas
 */
function cargarCategoriasSubmenu() {
  fetch('/assets/Data/categorias.json')
    .then(res => {
      if (!res.ok) throw new Error('No se pudo cargar categorias.json');
      return res.json();
    })
    .then(categorias => {
      const submenu = document.getElementById('productosSubmenu');
      if (!submenu) return;
      
      // Dividir categorías en 3 columnas
      const totalCats = categorias.length;
      const itemsPorColumna = Math.ceil(totalCats / 3);
      
      const col1 = categorias.slice(0, itemsPorColumna);
      const col2 = categorias.slice(itemsPorColumna, itemsPorColumna * 2);
      const col3 = categorias.slice(itemsPorColumna * 2);
      
      const crearColumna = (items, titulo) => {
        if (!items.length) return '';
        
        const itemsHTML = items
          .map(cat => `
            <a href="/views/catalogo.html?cat=${encodeURIComponent(cat.nombre)}" class="navbar-bs__submenu-item">
              ${cat.nombre}
            </a>
          `)
          .join('');
        
        return `
          <div class="navbar-bs__submenu-column">
            ${itemsHTML}
          </div>
        `;
      };
      
      submenu.innerHTML = `
        <div class="navbar-bs__submenu-content">
          ${crearColumna(col1, 'Columna 1')}
          ${crearColumna(col2, 'Columna 2')}
          ${crearColumna(col3, 'Columna 3')}
        </div>
      `;
    })
    .catch(() => {
      const submenu = document.getElementById('productosSubmenu');
      if (submenu) {
        submenu.innerHTML = `
          <div class="navbar-bs__submenu-content">
            <div class="submenu-error">No se pudo cargar las categorías</div>
          </div>
        `;
      }
    });
}

/**
 * Setup: Hace que el enlace "Productos" sea clickeable en móvil
 * En desktop, el hover CSS se encarga de mostrar el menú
 */
let productosLinkInitialized = false;
function setupProductosLink() {
  if (productosLinkInitialized) return; // Evitar ejecutar múltiples veces
  
  const productosLink = document.getElementById('productosLink');
  const submenu = document.getElementById('productosSubmenu');
  const navItem = document.querySelector('.navbar-bs__item-with-submenu');
  
  if (productosLink && submenu && navItem) {
    // Solo interceptar click en móvil/tablet (< 1101px)
    if (window.innerWidth < 1101) {
      productosLink.addEventListener('click', (e) => {
        e.preventDefault();
        submenu.classList.toggle('active');
      });
    }
    
    // Cerrar submenu al hacer clic en el documento (solo en pantallas pequeñas)
    if (window.innerWidth < 1101) {
      document.addEventListener('click', (e) => {
        if (!navItem.contains(e.target)) {
          submenu.classList.remove('active');
        }
      });
    }
    
    productosLinkInitialized = true;
  }
}

// ════════════════════════════════════════════════════════════════════════════
// SECCIÓN 14: INICIALIZACIÓN Y SETUP FINAL
// ════════════════════════════════════════════════════════════════════════════

// Cargar categorías y configurar eventos
cargarCategoriasMenu();
setupProductosLink();
ensureCarritoLayoutUI();
setupCarritoLayoutEvents();
renderCarritoLayout();

/**
 * Actualizar carrito cuando la página recupera el foco
 * Útil cuando se vuelve de otra pestaña
 */
window.addEventListener('focus', () => {
  actualizarBadgeCarrito();
});

/**
 * Actualizar carrito cuando hay cambios en localStorage desde otra página
 * Mantiene sincronización entre pestañas
 */
window.addEventListener('storage', (event) => {
  if (event.key === 'carrito') {
    carritoLayout = JSON.parse(event.newValue || '[]');
    actualizarBadgeCarrito();
  }
});

// Ocultar tienda por defecto (se muestra solo si es entrega por recojo)
const tiendaUbicacionWrapper = document.getElementById('tiendaUbicacionWrapper');
if (tiendaUbicacionWrapper) {
  tiendaUbicacionWrapper.style.display = 'none';
}

// ════════════════════════════════════════════════════════════════════════════
// SECCIÓN 15: NAVEGACIÓN STICKY (Desktop Only)
// ════════════════════════════════════════════════════════════════════════════

/**
 * Variables para rastrear el scroll (aunque actualmente está desactivado)
 * Se puede activar si se desea comportamiento sticky en el navbar
 */
let lastScrollTop = 0;
const navbar = document.querySelector('.navbar-bs');
const scrollThreshold = 50; // Pixels para considerar scroll significativo

/**
 * Función para manejar scroll (actualmente no hace nada - navbar siempre visible)
 * Se puede modificar aquí para agregar comportamiento de sticky
 */
function handleStickyNavigation() {
  // La navbar siempre permanece visible
  return;
}

/**
 * Agregado evento de scroll con throttling para mejor rendimiento
 * Usa requestAnimationFrame para no bloquear el navegador
 */
let scrollTimeout;
window.addEventListener('scroll', () => {
  if (scrollTimeout) {
    window.cancelAnimationFrame(scrollTimeout);
  }
  
  scrollTimeout = window.requestAnimationFrame(() => {
    handleStickyNavigation();
  });
}, { passive: true });

/**
 * Manejar cambios de tamaño de ventana (responsivo)
 */
window.addEventListener('resize', () => {
  handleStickyNavigation();
}, { passive: true });

// ════════════════════════════════════════════════════════════════════════════
// FIN DEL ARCHIVO
// ════════════════════════════════════════════════════════════════════════════
