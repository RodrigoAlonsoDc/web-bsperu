/* =========================
   CONFIG
   ========================= */
if (!window.WHATSAPP_NUMBER) {
  window.WHATSAPP_NUMBER = '51914776669';
}
if (!window.productos) {
  window.productos = [];
}
const itemsPorPagina = 12;

/* =========================
   PROMOCIONES
   ========================= */
let promoIndice = 0;
let promociones = [];

function cargarYRenderizarPromociones() {
  fetch('/assets/Data/promociones.json')
    .then(response => response.json())
    .then(data => {
      promociones = data;
      if (promociones.length === 0) return;
      
      renderizarPromo();
      configurarBotonesPromo();
      configurarIndicadoresPromo();
    })
    .catch(err => console.error('Error cargando promociones:', err));
}

function renderizarPromo() {
  const contenedor = document.getElementById('promoItems');
  if (!contenedor || promociones.length === 0) return;
  
  const item = promociones[promoIndice];
  
  contenedor.innerHTML = `
    <div class="promo-item">
      <div class="promo-item-content">
        <div class="promo-badge">
          <i class="bi bi-shield-check"></i> ${item.badge}
        </div>
        <h2 class="promo-titulo">${item.titulo}</h2>
        <p class="promo-descripcion">${item.descripcion}</p>
      </div>
      
      <div class="promo-item-image">
        <img src="${item.imagen}" alt="${item.titulo}" onerror="this.src='https://via.placeholder.com/280x280?text=Promoción'">
      </div> 
    </div>
  `;
  
  actualizarIndicadores();
}

function actualizarIndicadores() {
  const indicadores = document.querySelectorAll('.promo-indicator');
  indicadores.forEach((ind, i) => {
    ind.classList.toggle('active', i === promoIndice);
  });
}

function configurarBotonesPromo() {
  const btnPrev = document.querySelector('.promo-prev');
  const btnNext = document.querySelector('.promo-next');
  
  btnPrev?.addEventListener('click', () => {
    promoIndice = (promoIndice - 1 + promociones.length) % promociones.length;
    renderizarPromo();
  });
  
  btnNext?.addEventListener('click', () => {
    promoIndice = (promoIndice + 1) % promociones.length;
    renderizarPromo();
  });
}

function configurarIndicadoresPromo() {
  const contenedor = document.getElementById('promoIndicators');
  if (!contenedor) return;
  
  contenedor.innerHTML = promociones.map((_, i) => 
    `<div class="promo-indicator ${i === 0 ? 'active' : ''}" data-index="${i}"></div>`
  ).join('');
  
  contenedor.querySelectorAll('.promo-indicator').forEach(ind => {
    ind.addEventListener('click', (e) => {
      promoIndice = parseInt(e.target.getAttribute('data-index'));
      renderizarPromo();
    });
  });
}

/* =========================
   TIENDAS ONLINE
   ========================= */
let tiendasIndice = 0;


/* =========================
   CARGAR Y RENDERIZAR CATEGORÍAS
   ========================= */
function cargarYRenderizarCategorias() {
  fetch('/assets/Data/categorias.json')
    .then(response => response.json())
    .then(categorias => {
      // Renderizar para ambos contenedores
      const htmlCategorias = categorias.map(cat => {
        return `
          <a href="${cat.link}" class="campo-aplicacion-item">
            <div class="campo-aplicacion-img-wrapper">
              <img src="${cat.img}" alt="${cat.nombre}" class="campo-aplicacion-img">
            </div>
            <h4 class="campo-aplicacion-nombre">${cat.nombre}</h4>
          </a>
        `;
      }).join('');

      // Desktop version
      const desktopContenedor = document.querySelector('#campoAplicacionDesktop');
      if (desktopContenedor) {
        desktopContenedor.innerHTML = htmlCategorias;
      }

      // Swiper version (envueltos en swiper-slide)
      const swiperContenedor = document.querySelector('#campoAplicacionContenedor');
      if (swiperContenedor) {
        swiperContenedor.innerHTML = categorias.map(cat => {
          return `
            <div class="swiper-slide" style="width: auto;">
              <a href="${cat.link}" class="campo-aplicacion-item">
                <div class="campo-aplicacion-img-wrapper">
                  <img src="${cat.img}" alt="${cat.nombre}" class="campo-aplicacion-img">
                </div>
                <h4 class="campo-aplicacion-nombre">${cat.nombre}</h4>
              </a>
            </div>
          `;
        }).join('');
      }

      // Agregar eventos de clic con pantalla de carga a TODOS
      const items = document.querySelectorAll('.campo-aplicacion-item');
      items.forEach(item => {
        item.addEventListener('click', function(e) {
          e.preventDefault();
          const url = this.getAttribute('href');
          const loadingScreen = document.getElementById('loadingScreen');
          
          if (loadingScreen) {
            loadingScreen.classList.add('show');
            
            setTimeout(() => {
              window.location.href = url;
            }, 500);
          } else {
            window.location.href = url;
          }
        });
      });

      // Inicializar Swiper solo en tablet/móvil
      inicializarSwiperCampoAplicacion();
    })
    .catch(err => console.error('Error cargando categorías:', err));
}

// Inicializar Swiper para desktop, tablet y mobile (RESPONSIVE)
let swiperInstance = null;

function inicializarSwiperCampoAplicacion() {
  // Destruir Swiper anterior si existe
  if (swiperInstance) {
    swiperInstance.destroy();
    swiperInstance = null;
  }

  // Contar el número de slides disponibles
  const slidesElement = document.querySelector('.campo-aplicacion-swiper');
  const slidesCount = slidesElement ? slidesElement.querySelectorAll('.swiper-slide').length : 0;

  // Activar loop solo si hay suficientes slides (mínimo 8 para seguridad)
  const enableLoop = slidesCount >= 8;

  // Crear nueva instancia de Swiper (funcionará en TODOS los tamaños)
  swiperInstance = new Swiper('.campo-aplicacion-swiper', {
    slidesPerView: 'auto',
    spaceBetween: 15,
    grabCursor: true,
    loop: enableLoop,
    pagination: {
      el: '.swiper-pagination',
      clickable: true,
      dynamicBullets: true,
    },
    navigation: {
      nextEl: '#mobileBtnNext',
      prevEl: '#mobileBtnPrev',
    },
    breakpoints: {
      320: {
        slidesPerView: 1.8,
        spaceBetween: 12,
      },
      480: {
        slidesPerView: 2.5,
        spaceBetween: 12,
      },
      768: {
        slidesPerView: 3.5,
        spaceBetween: 15,
      },
      1024: {
        slidesPerView: 4.5,
        spaceBetween: 15,
      },
      1280: {
        slidesPerView: 5.5,
        spaceBetween: 15,
      },
      1536: {
        slidesPerView: 6,
        spaceBetween: 15,
      },
    }
  });
}

// Inicializar al cargar la página
document.addEventListener('DOMContentLoaded', () => {
  inicializarSwiperCampoAplicacion();
});

// Reinicializar al cambiar tamaño de pantalla
window.addEventListener('resize', () => {
  inicializarSwiperCampoAplicacion();
});

// Función para carrusel infinito con touch/swipe - OPTIMIZADO
function inicializarCarruselTouch(contenedor) {
  let isDown = false;
  let startX = 0;
  let scrollLeft = 0;
  let velocidad = 0;
  let lastX = 0;
  let animationFrame = null;
  let isDragging = false;

  // Prevenir navegación en links durante drag
  const links = contenedor.querySelectorAll('a');
  links.forEach(link => {
    link.addEventListener('click', (e) => {
      if (isDragging) {
        e.preventDefault();
        isDragging = false;
      }
    });
  });

  // Mouse Down
  contenedor.addEventListener('mousedown', (e) => {
    isDragging = false;
    isDown = true;
    startX = e.pageX - contenedor.offsetLeft;
    scrollLeft = contenedor.scrollLeft;
    lastX = startX;
    contenedor.style.cursor = 'grabbing';
    velocidad = 0;
  }, { passive: true });

  // Mouse Leave
  contenedor.addEventListener('mouseleave', () => {
    isDown = false;
    isDragging = false;
    contenedor.style.cursor = 'grab';
  }, { passive: true });

  // Mouse Up
  contenedor.addEventListener('mouseup', () => {
    isDown = false;
    contenedor.style.cursor = 'grab';
    if (animationFrame) cancelAnimationFrame(animationFrame);
    aplicarMomentum();
  }, { passive: true });

  // Mouse Move
  contenedor.addEventListener('mousemove', (e) => {
    if (!isDown) return;
    
    const x = e.pageX - contenedor.offsetLeft;
    const movimiento = x - startX;
    
    if (Math.abs(movimiento) > 5) {
      isDragging = true;
    }
    
    velocidad = (x - lastX) * 0.5;
    lastX = x;
    
    contenedor.scrollLeft = scrollLeft - movimiento;
  }, { passive: true });

  // Touch Start
  contenedor.addEventListener('touchstart', (e) => {
    isDragging = false;
    isDown = true;
    startX = e.touches[0].pageX - contenedor.offsetLeft;
    scrollLeft = contenedor.scrollLeft;
    lastX = startX;
    velocidad = 0;
  }, { passive: true });

  // Touch End
  contenedor.addEventListener('touchend', () => {
    isDown = false;
    if (animationFrame) cancelAnimationFrame(animationFrame);
    aplicarMomentum();
  }, { passive: true });

  // Touch Move
  contenedor.addEventListener('touchmove', (e) => {
    if (!isDown) return;
    
    const x = e.touches[0].pageX - contenedor.offsetLeft;
    const movimiento = x - startX;
    
    if (Math.abs(movimiento) > 5) {
      isDragging = true;
    }
    
    velocidad = (x - lastX) * 0.5;
    lastX = x;
    
    contenedor.scrollLeft = scrollLeft - movimiento;
  }, { passive: true });

  // Aplicar momentum (deslizamiento suave)
  function aplicarMomentum() {
    if (Math.abs(velocidad) > 0.5) {
      contenedor.scrollLeft -= velocidad;
      velocidad *= 0.92;
      animationFrame = requestAnimationFrame(aplicarMomentum);
    }
  }

  // Hacer infinito: loop al scroll
  let scrollTimeout;
  contenedor.addEventListener('scroll', () => {
    clearTimeout(scrollTimeout);
    
    scrollTimeout = setTimeout(() => {
      const maxScroll = contenedor.scrollWidth - contenedor.clientWidth;
      const scrollPos = contenedor.scrollLeft;

      // Si llegamos casi al final, volver al inicio (sin animación)
      if (scrollPos > maxScroll - 150) {
        contenedor.scrollLeft = 50;
      }
      // Si llegamos casi al inicio, ir casi al final
      else if (scrollPos < 50) {
        contenedor.scrollLeft = maxScroll - 100;
      }
    }, 150);
  }, { passive: true });
}

function guardarFiltrosCatalogo(categorias = []) {
  try {
    sessionStorage.setItem(CATALOGO_FILTROS_KEY, JSON.stringify(categorias));
  } catch (err) {
    // ignore
  }
}

function obtenerFiltrosCatalogo() {
  try {
    const raw = sessionStorage.getItem(CATALOGO_FILTROS_KEY);
    const parsed = raw ? JSON.parse(raw) : [];
    return Array.isArray(parsed) ? parsed : [];
  } catch (err) {
    return [];
  }
}

function guardarPaginaCatalogo(pagina = 1) {
  try {
    const paginaValida = Number.isFinite(Number(pagina)) && Number(pagina) > 0
      ? Math.floor(Number(pagina))
      : 1;
    sessionStorage.setItem(CATALOGO_PAGINA_KEY, String(paginaValida));
  } catch (err) {
    // ignore
  }
}

function obtenerPaginaCatalogo() {
  try {
    const raw = sessionStorage.getItem(CATALOGO_PAGINA_KEY);
    const pagina = Number(raw);
    return Number.isFinite(pagina) && pagina > 0 ? Math.floor(pagina) : 1;
  } catch (err) {
    return 1;
  }
}

function guardarOrdenCatalogo(orden = { precio: 'none', alfabetico: 'none' }) {
  try {
    const payload = {
      precio: (orden?.precio || 'none').toString(),
      alfabetico: (orden?.alfabetico || 'none').toString()
    };
    sessionStorage.setItem(CATALOGO_ORDEN_KEY, JSON.stringify(payload));
  } catch (err) {
    // ignore
  }
}

function obtenerOrdenCatalogo() {
  try {
    const raw = sessionStorage.getItem(CATALOGO_ORDEN_KEY);
    const parsed = raw ? JSON.parse(raw) : {};
    return {
      precio: (parsed?.precio || 'none').toString(),
      alfabetico: (parsed?.alfabetico || 'none').toString()
    };
  } catch (err) {
    return { precio: 'none', alfabetico: 'none' };
  }
}

function productoEstaDisponible(producto) {
  const valor = producto?.producto_disponible ?? producto?.productos_disponibles;

  if (typeof valor === 'boolean') return valor;
  if (typeof valor === 'string') return valor.trim().toLowerCase() === 'true';
  if (typeof valor === 'number') return valor === 1;

  // Si no existe la propiedad, se considera visible para no romper datos antiguos.
  return true;
}

/* =========================
   HELPERS
   ========================= */
const $ = (sel, ctx = document) => ctx.querySelector(sel);
const $$ = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));

/* Notificaci�n bonita */
function mostrarNotificacion(mensaje, tipo = 'success') {
  const container = document.getElementById('toastContainer') || (() => {
    const div = document.createElement('div');
    div.id = 'toastContainer';
    div.className = 'position-fixed top-0 end-0 p-3';
    div.style.zIndex = '9999';
    document.body.appendChild(div);
    return div;
  })();

  const iconos = {
    success: 'bi-check-circle-fill',
    error: 'bi-exclamation-circle-fill',
    info: 'bi-info-circle-fill'
  };
  const colores = {
    success: 'text-success',
    error: 'text-danger',
    info: 'text-primary'
  };

  const id = 'toast-' + Date.now();
  const toast = `
    <div id="${id}" class="toast align-items-center border-0" role="alert">
      <div class="d-flex">
        <div class="toast-body d-flex align-items-center gap-2">
          <i class="bi ${iconos[tipo]} ${colores[tipo]} fs-5"></i>
          <span>${mensaje}</span>
        </div>
        <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
    </div>
  `;

  container.insertAdjacentHTML('beforeend', toast);
  const toastEl = document.getElementById(id);
  const bsToast = new bootstrap.Toast(toastEl, { autohide: true, delay: 3000 });
  bsToast.show();
  toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
}

function ensureBootstrapOnce() {
  if (!document.querySelector('link[href*="bootstrap.min.css"]')) {
    const link = document.createElement('link');
    link.rel = "stylesheet";
    link.href = "https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css";
    document.head.appendChild(link);
  }
  if (!document.querySelector('link[href*="bootstrap-icons"]')) {
    const link2 = document.createElement('link');
    link2.rel = "stylesheet";
    link2.href = "https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css";
    document.head.appendChild(link2);
  }
  if (!document.querySelector('script[src*="bootstrap.bundle.min.js"]')) {
    const s = document.createElement('script');
    s.src = "https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js";
    document.body.appendChild(s);
  }
}

/* =========================
   UI CONTAINERS: Toasts + Modales
   ========================= */
function setupUIContainers() {
  if (!$('#toastContainer')) {
    const toasts = document.createElement('div');
    toasts.id = 'toastContainer';
    toasts.className = 'position-fixed top-0 end-0 p-3';
    toasts.style.zIndex = 1080;
    document.body.appendChild(toasts);
  }

  // Si loyout.js ya creó los modales (ej: en catalogo.html), no crear duplicados
  const carritoModalExists = document.getElementById('carritoModal');
  if (!carritoModalExists) {
    document.body.insertAdjacentHTML('beforeend', `
      <div class="modal fade" id="carritoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-scrollable modal-dialog-end">
          <div class="modal-content rounded-4">
            <div class="modal-header">
              <h5 class="modal-title fw-bold">?? Tu carrito</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-3" id="carritoBody"></div>
            <div class="modal-footer d-flex flex-column gap-2">
              <div class="d-flex gap-2">
                <button id="btnCancelarCompra" class="btn btn-outline-danger w-100">Cancelar compra</button>
                <button id="btnFinalizarCompra" class="btn btn-success w-100">Finalizar compra</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    `);
  }

  // Si loyout.js ya creó el modal de checkout, no crear duplicado
  const checkoutModalExists = document.getElementById('checkoutModal');
  if (!checkoutModalExists) {
    document.body.insertAdjacentHTML('beforeend', `
      <div class="modal fade" id="checkoutModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
          <div class="modal-content rounded-4">
            <div class="modal-header">
              <h5 class="modal-title fw-bold">Finalizar compra</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
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
                <div class="col-md-6"><label class="form-label">Tel�fono</label><input class="form-control" name="telefono" required></div>
                <div class="col-12"><label class="form-label">Direcci�n</label><input class="form-control" name="direccion" required></div>
                <div class="col-12"><label class="form-label">Correo electr�nico</label><input class="form-control" type="email" name="email" required></div>

                <div class="col-md-6">
                  <label class="form-label">M�todo de entrega</label>
                  <select class="form-select" name="metodoRecojo" id="metodoRecojo" required>
                    <option value="delivery">Delivery</option>
                    <option value="recojo">Recojo en tienda</option>
                  </select>
                </div>

                <div class="col-md-6" id="tiendaUbicacionWrapper">
                  <label class="form-label">Ubicaci�n de las tiendas</label>
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
                    <input class="form-check-input" type="checkbox" id="autorizarFactura" name="autorizarFactura">
                    <label class="form-check-label" for="autorizarFactura">Autorizo el uso de mis datos para emisi�n de factura</label>
                  </div>
                </div>

                <div class="col-12">
                  <label class="form-label">M�todo de pago</label>
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
      </div>
    `);
  }
}
/* =========================
   TOAST
   ========================= */
function showToast(message, tipo = 'success', duration = 2500) {
  const id = 'toast-' + Date.now();
  $('#toastContainer').insertAdjacentHTML('beforeend', `
    <div id="${id}" class="toast align-items-center text-bg-${tipo} border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body">${message}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
      </div>
    </div>
  `);
  const el = document.getElementById(id);
  const bsToast = new bootstrap.Toast(el, { delay: duration });
  bsToast.show();
  el.addEventListener('hidden.bs.toast', () => el.remove());
}
function getCategoriaFromURL() {
  const params = new URLSearchParams(window.location.search);
  return params.get('cat'); // devuelve "Morteros", etc
}

/* =========================
   NAVBAR / MARCAS
   ========================= */
function renderCatalogoNavbar(selector = '.barrera-catalogo') {
  const menuLinks = `
          <li class="nav-item"><a class="nav-link" href="/views/NuestrasMarcas.html">Nuestras Marcas</a></li>
          <li class="nav-item"><a class="nav-link" href="/views/ubicacion.html">Sucursales</a></li>
      <li class="nav-item"><a class="nav-link" href="/views/catalogo.html">Productos</a></li>
          <li class="nav-item"><a class="nav-link" href="/views/contacto.html">Contactanos</a></li>
  `;
  const html = `
  <nav class="navbar navbar-expand-lg navbar-dark  ">
    <div class="container-fluid">
      <a class="navbar-brand d-flex align-items-center" href="/index.html">
        <img src="/assets/img principales/logo.png" alt="Logo" style="height:40px;">
      </a>

      <div class="d-flex align-items-center ms-2">
        <button id="btnCarritoNav" class="btn btn-outline-light position-relative" type="button">
          <i class="bi bi-cart"></i>
          <span id="cartBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display:none">0</span>
        </button>
      </div>

      <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <form class="d-flex position-relative mx-lg-auto" id="navbarSearchForm">
          <input id="navbarSearch" class="form-control me-2" type="search" placeholder="Buscar..." autocomplete="off">
          <button class="btn btn-outline-success search-btn" type="submit">
            <i class="bi bi-search"></i>
          </button>

          <div id="searchSuggestions"
               class="list-group position-absolute"
               style="z-index:1050; right:0; top:48px; display:none; width:320px;">
          </div>
        </form>
        <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
          ${menuLinks}
        </ul>
      </div>
    </div>
  </nav>`;
  const cont = document.querySelector(selector);
  if (cont) cont.innerHTML = html;
}

const marcas = [
  {
    logo: "/assets/imgWeb/marcasIMG/image-removebg-preview (5).png",
    desc: " VOLCAN es l�der en soluciones constructivas sostenibles.",
    productos: "/assets/imgWeb/catalogoIMG/image-removebg-preview (24).png"
  }, 
  {
    logo: "/assets/imgWeb/marcasIMG/icon-werber.jpg",
    desc: "HUILA WEBER promueve valores a trav�s del arte, el deporte y el compromiso social.",
    productos: "/assets/imgWeb/catalogoIMG/image-removebg-preview (15).png"
  }
];

function renderMarcaCatalogo(selector = '.marcaCatalogo') {
  const cont = document.querySelector(selector);
  if (!cont) return;
  cont.innerHTML = `
    <div class="container py-3">
      <div class="row g-4 justify-content-center">
        ${marcas.map(m => `
        <div class="col-12 col-md-4">
          <div class="card border-0 shadow-sm marca-card h-100" style="background:#f4f9fb;">
            <div class="d-flex align-items-center gap-3 p-3">
              <img src="${m.logo}" class="marca-logo" style="width:54px;height:54px;object-fit:contain;">
              <div class="marca-desc text-secondary fw-semibold">
                ${m.desc}
              </div>
            </div>
            <div class="marca-img px-3 pb-3">
              <img src="${m.productos}" class="img-fluid rounded-3" style="height:80px;object-fit:cover;">
            </div>
          </div>
        </div>`).join('')}
      </div>
    </div>
  `;
}

/* =========================
   DROPDOWN (FILTRO)
   ========================= */
  function renderDropdownCatalogo(selector = '.dropdownCatalogo', opciones = null) {
    const normaliza = s => (s || '')
      .toString()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .trim()
      .toLowerCase();

    const defaultOpciones = [
      { id: "op1", label: "Desmoldantes" },
      { id: "op2", label: "Curadores" },
      { id: "op3", label: "Impermeabilizantes" },
      { id: "op4", label: "Selladores" },
      { id: "op5", label: "Selladores de junta" },
      { id: "op6", label: "Adhesivos Ep�xicos" },
      { id: "op7", label: "Separadores" },
      { id: "op8", label: "Morteros" },
      { id: "op9", label: "Aditivos complementarios" },
      { id: "op10", label: "Aditivos para concreto" }
    ];

  // NOTE: la auto-selecci�n desde URL se realiza m�s abajo,
  // despu�s de que se hayan renderizado los checkboxes.


  if (!opciones) {
    const categoriasDesdeProductos = [];
    const categoriasMap = new Map();

    window.productos.forEach(p => {
      const raw = (p?.categoria || '').toString().trim();
      const key = normaliza(raw);
      if (!key) return;
      if (!categoriasMap.has(key)) categoriasMap.set(key, raw);
    });

    categoriasMap.forEach(label => categoriasDesdeProductos.push(label));
    categoriasDesdeProductos.sort((a, b) => a.localeCompare(b, 'es', { sensitivity: 'base' }));

    opciones = categoriasDesdeProductos.length > 0
      ? categoriasDesdeProductos.map((label, i) => ({ id: `op${i + 1}`, label }))
      : defaultOpciones;
  }
  const cont = document.querySelector(selector);
  if (!cont) return;

  cont.innerHTML = `
    <div class="catalogo-extra-filtros">

      <div class="mb-3"> 
        <div class="dropdown">
          <button class="btn btn-primary dropdown-toggle w-100 text-start" type="button" data-bs-toggle="dropdown">
            Categorias (<span id="selected-count">0</span>)
          </button>

          <ul class="dropdown-menu p-3" data-bs-auto-close="outside" style="width:250px;max-height:300px;overflow-y:auto;">
            <li class="mb-2"><input type="text" class="form-control" id="searchBox" placeholder="Buscar..."></li>
            <li><hr class="dropdown-divider"></li>

            <li>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="selectAll">
                <label class="form-check-label" for="selectAll">Seleccionar todo</label>
              </div>
            </li>

            <li><hr class="dropdown-divider"></li>
            <div id="options-container"></div>
          </ul>
        </div>
      </div>

      <hr class="catalogo-extra-filtros__divider">

      

      <div>
        <label for="sortNombre" class="form-label mb-1 fw-semibold">Orden alfabético</label>
        <select id="sortNombre" class="form-select form-select-sm">
          <option value="none">Sin orden</option>
          <option value="az">A - Z</option>
          <option value="za">Z - A</option>
        </select>
      </div>

    </div>
  `;

  const container = $("#options-container", cont);
  opciones.forEach(op => {
    const labelUpper = (op.label || '').toString().toUpperCase();
    const li = document.createElement("li");
    li.innerHTML = `
      <div class="form-check">
        <input class="form-check-input option" type="checkbox" id="${op.id}" value="${labelUpper}">
        <label class="form-check-label" for="${op.id}">${labelUpper}</label>
      </div>`;
    container.appendChild(li);
  });

  const countSpan = $("#selected-count", cont);
  const selectAll = $("#selectAll", cont);
  const searchBox = $("#searchBox", cont);
  const menu = $('.dropdown-menu', cont);
  const sortPrecio = $('#sortPrecio', cont);
  const sortNombre = $('#sortNombre', cont);

  const ordenGuardado = obtenerOrdenCatalogo();
  if (sortPrecio) sortPrecio.value = ordenGuardado.precio;
  if (sortNombre) sortNombre.value = ordenGuardado.alfabetico;

  if (menu) {
    menu.addEventListener('click', (e) => {
      e.stopPropagation();
    });
  }

  function getSelectedCategories() {
    return $$(".option:checked", cont).map(cb => cb.value);
  }

  function updateCount() {
    const selectedCategories = getSelectedCategories();
    const selected = selectedCategories.length;
    const total = $$(".option", container).length;

    if (selected === total && total > 0) {
      guardarPaginaCatalogo(1);
      guardarFiltrosCatalogo([]);
      countSpan.textContent = "Todos";
      selectAll.checked = true;
      renderCatalogoProductos('.catalogoProductos', 1, []);
    } else {
      guardarPaginaCatalogo(1);
      guardarFiltrosCatalogo(selectedCategories);
      countSpan.textContent = selected;
      selectAll.checked = false;
      renderCatalogoProductos('.catalogoProductos', 1, selectedCategories);
    }
  }

  $$(".option", container).forEach(cb => cb.addEventListener('change', updateCount));

  selectAll.addEventListener('change', function () {
    $$(".option", container).forEach(cb => cb.checked = this.checked);
    updateCount();
  });

  searchBox.addEventListener('input', function () {
    const filter = this.value.toLowerCase();
    $$(".form-check-label", container).forEach(label => {
      const row = label.closest('li') || label.parentElement.parentElement;
      (row || label.closest('.form-check')).style.display =
        label.textContent.toLowerCase().includes(filter) ? '' : 'none';
    });
  });

  function aplicarOrdenSeleccionado() {
    const orden = {
      precio: sortPrecio?.value || 'none',
      alfabetico: sortNombre?.value || 'none'
    };
    guardarPaginaCatalogo(1);
    guardarOrdenCatalogo(orden);
    renderCatalogoProductos('.catalogoProductos', 1, getSelectedCategories());
  }

  if (sortPrecio) {
    sortPrecio.addEventListener('change', aplicarOrdenSeleccionado);
  }
  if (sortNombre) {
    sortNombre.addEventListener('change', aplicarOrdenSeleccionado);
  }

  const filtrosGuardados = obtenerFiltrosCatalogo();
  const paginaGuardada = obtenerPaginaCatalogo();
  if (!getCategoriaFromURL() && Array.isArray(filtrosGuardados) && filtrosGuardados.length > 0) {
    const setGuardados = new Set(filtrosGuardados.map(v => normaliza(v)));
    $$(".option", container).forEach(cb => {
      cb.checked = setGuardados.has(normaliza(cb.value));
    });
    const selected = getSelectedCategories();
    countSpan.textContent = selected.length;
    selectAll.checked = selected.length > 0 && selected.length === $$(".option", container).length;
    renderCatalogoProductos('.catalogoProductos', paginaGuardada, selected);
    return;
  }

  if (!getCategoriaFromURL()) {
    renderCatalogoProductos('.catalogoProductos', paginaGuardada, []);
  }

  // ===============================
  // AUTO-SELECCI�N DESDE URL (?cat=)
  // ===============================
  const categoriaURL = getCategoriaFromURL();

  if (categoriaURL) {
    const checkbox = Array.from(container.querySelectorAll('.option')).find(cb =>
      normaliza(cb.value) === normaliza(categoriaURL)
    );

    if (checkbox) {
      checkbox.checked = true;
      updateCount(); // actualiza contador y aplica el filtro
    } else {
      renderCatalogoProductos('.catalogoProductos', 1, []);
    }
  }
}
 


/* =========================
   CAT�LOGO + PAGINACI�N
   ========================= */
function renderCatalogoProductos(selector = '.catalogoProductos', pagina = 1, categoriasFiltro = []) {
  const cont = document.querySelector(selector);
  if (!cont) return;

  const norm = s => (s || '')
    .toString()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .trim()
    .toLowerCase();

  let lista = productos;

  // Si venimos con ?q=... mostrar solo coincidencias de b�squeda
  try {
    const params = new URLSearchParams(window.location.search);
    const qParam = params.get('q');
    if (qParam && qParam.trim()) {
      const qlc = qParam.trim().toLowerCase();
      lista = lista.filter(p => (p.nombre || '').toLowerCase().includes(qlc) || (p.sku || '').toLowerCase().includes(qlc));
    }
  } catch (err) {
    // ignore
  }

  // FILTRO POR CATEGOR�AS
  if (Array.isArray(categoriasFiltro) && categoriasFiltro.length > 0) {
    const set = new Set(categoriasFiltro.map(c => norm(c)));
    lista = lista.filter(p => set.has(norm(p.categoria)));
  }

  // ORDENAMIENTO: alfab�tico y precio
  const ordenPrecio = (document.getElementById('sortPrecio')?.value || 'none').toLowerCase();
  const ordenAlfabetico = (document.getElementById('sortNombre')?.value || 'none').toLowerCase();

  if (ordenAlfabetico === 'az') {
    lista = [...lista].sort((a, b) => (a?.nombre || '').localeCompare((b?.nombre || ''), 'es', { sensitivity: 'base' }));
  } else if (ordenAlfabetico === 'za') {
    lista = [...lista].sort((a, b) => (b?.nombre || '').localeCompare((a?.nombre || ''), 'es', { sensitivity: 'base' }));
  }

  if (ordenPrecio === 'asc') {
    lista = [...lista].sort((a, b) => Number(a?.precio || 0) - Number(b?.precio || 0));
  } else if (ordenPrecio === 'desc') {
    lista = [...lista].sort((a, b) => Number(b?.precio || 0) - Number(a?.precio || 0));
  }

  // PAGINACI�N
  const totalPaginas = Math.ceil(Math.max(1, lista.length) / itemsPorPagina);
  const paginaActual = Math.min(Math.max(1, Number(pagina) || 1), totalPaginas);
  const inicio = (paginaActual - 1) * itemsPorPagina;
  const paginaProds = lista.slice(inicio, inicio + itemsPorPagina);
  guardarPaginaCatalogo(paginaActual);

  // RENDER
  cont.innerHTML = `
    <div class="catalogoProducto">
      <div class="container-fluid py-4">
        <div class="row g-3">

          ${paginaProds.map(prod => `
          <div class="col-12 col-sm-6 col-md-3">
            <div class="card border-0 shadow-sm p-2 h-100 rounded-4 producto-card" style="background:#f4f9fb;">

              <div class="d-flex justify-content-center align-items-center pb-2 pt-2 producto-media">
                ${prod.oferta_disponible ? '<span class="oferta-ribbon">OFERTA</span>' : ''}
                <img src="${prod.imagenes?.[0] || ''}"
                     alt="${prod.nombre}"
                class="producto-img">
              </div>

              <div class="px-2 pb-2">
                <span class="  fw-bold" style="font-size:SMALLER;">
                  ${prod.categoria}
                </span>

                <div class="fw-semibold mt-1 mb-1 text-dark" style="font-size:SMALLER;">
                  ${prod.nombre}
                </div>


              </div>

              <div class="d-flex justify-content-center gap-2 pb-2">
                <button class="btn btn-primary rounded-pill px-3 fw-bold btn-agregar"
                        data-sku="${prod.sku}"
                        style="font-size:.9em;">
                  Agregar
                </button>

                <button class="btn btn-outline-primary rounded-pill px-3 fw-bold btn-ver"
                        data-sku="${prod.sku}"
                        style="font-size:.9em;">
                  Ver
                </button>
              </div>

            </div>
          </div>
          `).join('')}

        </div>

        <!-- PAGINACI�N -->
        <nav aria-label="Paginaci�n" class="mt-4 mb-2">
          <ul class="pagination justify-content-center">
            ${(() => {
              const paginas = [];
              let inicio = 1;
              let fin = Math.min(6, totalPaginas);
              
              if (totalPaginas > 6 && paginaActual > 4) {
                inicio = paginaActual - 2;
                fin = paginaActual + 3;
                if (fin > totalPaginas) {
                  fin = totalPaginas;
                  inicio = Math.max(1, totalPaginas - 5);
                }
              }
              
              for (let i = inicio; i <= fin; i++) {
                paginas.push(`
                  <li class="page-item ${i === paginaActual ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                  </li>`);
              }
              
              if (totalPaginas > 6 && fin < totalPaginas) {
                paginas.push(`<li class="page-item disabled"><span class="page-link">...</span></li>`);
                paginas.push(`
                  <li class="page-item ${totalPaginas === paginaActual ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${totalPaginas}">${totalPaginas}</a>
                  </li>`);
              }
              
              return paginas.join('');
            })()}
          </ul>
        </nav>
      </div>
    </div>
  `;

  // EVENTOS DE PAGINACI�N
  $$('.pagination .page-link', cont).forEach(a => {
    a.addEventListener('click', e => {
      e.preventDefault();
      const pg = Number(a.dataset.page);
      if (!isNaN(pg)) {
        renderCatalogoProductos(selector, pg, categoriasFiltro);
        window.scrollTo({ top: cont.offsetTop - 20, behavior: 'smooth' });
      }
    });
  });

  // EVENTO AGREGAR
  $$('.btn-agregar', cont).forEach(btn => {
    btn.addEventListener('click', () => {
      const sku = btn.dataset.sku;
      const prod = window.productos.find(p => p.sku === sku);

      if (prod) {
        agregarAlCarrito(prod);
        showToast(`${prod.nombre} agregado al carrito.`, 'success');
      }
    });
  });

  // EVENTO VER
  $$('.btn-ver', cont).forEach(btn => {
    btn.addEventListener('click', () => {
      const sku = btn.dataset.sku;
      window.location.href = `/views/producto.html?sku=${sku}`;
    });
  });

  // Hacer la imagen clicable para ir al producto
  $$('.producto-img', cont).forEach(img => {
    img.style.cursor = 'pointer';
    img.addEventListener('click', (e) => {
      const card = img.closest('.producto-card');
      const sku = card ? card.querySelector('.btn-ver')?.dataset.sku : null;
      if (sku) {
        window.location.href = `/views/producto.html?sku=${sku}`;
      }
    });
  });
}

/* =========================
   CARRITO
   ========================= */
if (!window.carrito) {
  window.carrito = JSON.parse(localStorage.getItem("carrito") || "[]");
}

function syncCarrito() {
  localStorage.setItem("carrito", JSON.stringify(window.carrito));
}

function agregarAlCarrito(prod) {
  const idx = window.carrito.findIndex(i => i.sku === prod.sku);
  const addQty = prod.cantidad && prod.cantidad > 0 ? prod.cantidad : 1;
  
  // Obtener imagen: preferir imagenes (array), luego miniaturas (objeto)
  let imgUrl = "/assets/img principales/logo.png";
  if (prod.imagenes && prod.imagenes.length > 0) {
    imgUrl = prod.imagenes[0];
  } else if (prod.miniaturas && typeof prod.miniaturas === 'object') {
    const miniaturas = Object.values(prod.miniaturas).filter(m => m && m.trim());
    if (miniaturas.length > 0) {
      imgUrl = miniaturas[0];
    }
  }

  if (idx >= 0) {
    window.carrito[idx].cantidad += addQty;
  } else {
    window.carrito.push({
      sku: prod.sku,
      nombre: prod.nombre,
      precio: prod.precio,
      img: imgUrl,
      cantidad: addQty
    });
  }

  syncCarrito();
  renderCarrito();
}

function cambiarCantidad(index, delta) {
  if (!window.carrito[index]) return;

  window.carrito[index].cantidad += delta;
  if (window.carrito[index].cantidad <= 0) window.carrito.splice(index, 1);

  renderCarrito();
}

function eliminarItemCarrito(index) {
  window.carrito.splice(index, 1);
  syncCarrito();
  renderCarrito();
}
/* =========================
   CALCULAR TOTALES
   ========================= */
function calcularTotales() {
  let subtotalSinIgv = 0, igvTotal = 0, total = 0;

  window.carrito.forEach(item => {
    const totalConIgv = Number(item.precio) * item.cantidad;
    const precioSinIgv = totalConIgv / 1.18;
    const igv = totalConIgv - precioSinIgv;

    subtotalSinIgv += precioSinIgv;
    igvTotal += igv;
    total += totalConIgv;
  });

  return { subtotal: subtotalSinIgv, igv: igvTotal, total };
}

/* =========================
   RENDER CARRITO
   ========================= */
function renderCarrito() {
  const body = $('#carritoBody');
  if (!body) return;

  if (window.carrito.length === 0) {
    body.innerHTML = `<p class="text-center text-secondary">Tu carrito está vacío.</p>`;
  } else {
    body.innerHTML = window.carrito.map((item, i) => `
      <div class="cart-item d-flex align-items-center mb-3 pb-3 border-bottom">

        <img src="${item.img}"
             alt="${item.nombre}"
             class="cart-item-img me-3 rounded"
             onerror="this.src='/assets/img principales/logo.png'">

        <div class="flex-grow-1">
          <div class="fw-semibold small mb-1">${item.nombre}</div>
        </div>

        <div class="d-flex align-items-center gap-1">
          <button class="btn btn-sm btn-outline-secondary"
                  data-action="dec"
                  data-index="${i}">
            -
          </button>

          <span class="px-2 fw-bold">${item.cantidad}</span>

          <button class="btn btn-sm btn-outline-secondary"
                  data-action="inc"
                  data-index="${i}">
            +
          </button>

          <button class="btn btn-sm btn-outline-danger ms-2"
                  data-action="del"
                  data-index="${i}"
                  title="Eliminar">
            <i class="bi bi-trash"></i>
          </button>
        </div>

      </div>
    `).join('');
  }

  const badge = $('#cartBadge');
  const cantidadTotal = window.carrito.reduce((s, i) => s + i.cantidad, 0);

  if (badge) {
    badge.innerText = cantidadTotal;
    badge.style.display = cantidadTotal > 0 ? 'inline-block' : 'none';
  }
}

/* =========================
   EVENTOS GLOBALES
   ========================= */
function setupGlobalEvents() {
  // Si loyout.js ya está activo (detectar modal custom), no ejecutar handlers de Bootstrap aquí
  const isLoyoutActive = document.querySelector('.layout-cart-modal') !== null;
  if (isLoyoutActive) {
    // loyout.js ya está manejando los modales, no hacer nada aquí
    return;
  }

  // abrir/cerrar carrito (toggle)
  document.addEventListener('click', (e) => {
    if (e.target.closest('#btnCarritoNav')) {
      const carritoModalEl = $('#carritoModal');
      const modalInstance = bootstrap.Modal.getInstance(carritoModalEl);
      
      if (modalInstance && modalInstance._isShown) {
        // Si el modal está abierto, cerrarlo
        modalInstance.hide();
      } else if (modalInstance) {
        // Si existe la instancia pero está cerrado, abrirlo
        modalInstance.show();
        renderCarrito();
      } else {
        // Si no existe instancia, crear una nueva y abrir
        new bootstrap.Modal(carritoModalEl).show();
        renderCarrito();
      }
    }
  });

  // acciones del carrito
  const carritoBodyEl = $('#carritoBody');
  if (carritoBodyEl) {
    carritoBodyEl.addEventListener('click', (e) => {
      const btn = e.target.closest('button');
      if (!btn) return;

      const action = btn.dataset.action;
      const idx = Number(btn.dataset.index);

      if (action === 'dec') cambiarCantidad(idx, -1);
      if (action === 'inc') cambiarCantidad(idx, +1);
      if (action === 'del') {
        eliminarItemCarrito(idx);
        showToast('Producto eliminado del carrito', 'warning');
      }
    });
  }

  // cancelar compra
  const btnCancelar = $('#btnCancelarCompra');
  if (btnCancelar) {
    btnCancelar.addEventListener('click', () => {
      window.carrito = [];
      syncCarrito(); // ? Guardar en localStorage
      renderCarrito();
      mostrarNotificacion('Compra cancelada. Carrito vaciado.', 'info');

      const cartModal = bootstrap.Modal.getInstance($('#carritoModal'));
      if (cartModal) cartModal.hide();
    });
  }

  // finalizar compra
  const btnFinalizar = $('#btnFinalizarCompra');
  if (btnFinalizar) {
    btnFinalizar.addEventListener('click', () => {
      const carritoActual = JSON.parse(localStorage.getItem('carrito') || '[]');
      if (!carritoActual.length) {
        showToast('Tu carrito está vacío. Agrega productos antes de finalizar la compra.', 'warning', 3000);
        return;
      }
      const cartModal = bootstrap.Modal.getInstance($('#carritoModal'));
      if (cartModal) cartModal.hide();

      new bootstrap.Modal($('#checkoutModal')).show();
    });
  }

  // formulario de checkout
  const checkoutForm = $('#checkoutForm');
  if (checkoutForm) {
    const metodoRecojoSel = $('#metodoRecojo');
    const tiendaWrapper = $('#tiendaUbicacionWrapper');

    if (metodoRecojoSel && tiendaWrapper) {
      const toggleTienda = () =>
        tiendaWrapper.style.display = (metodoRecojoSel.value === 'recojo') ? '' : 'none';

      metodoRecojoSel.addEventListener('change', toggleTienda);
      toggleTienda();
    }

    const tipodocSel = document.getElementById('tipodoc');
    const numerodocInput = document.getElementById('numerodoc');
    if (tipodocSel && numerodocInput) {
      tipodocSel.addEventListener('change', () => {
        const tipo = tipodocSel.value;
        numerodocInput.value = '';
        if (tipo === 'DNI') {
          numerodocInput.maxLength = 8;
          numerodocInput.setAttribute('minlength', '8');
          numerodocInput.pattern = '[0-9]{8}';
          numerodocInput.placeholder = 'Ej: 12345678';
          numerodocInput.title = 'El DNI debe tener exactamente 8 dígitos';
          numerodocInput.removeAttribute('readonly');
        } else if (tipo === 'RUC') {
          numerodocInput.maxLength = 10;
          numerodocInput.setAttribute('minlength', '10');
          numerodocInput.pattern = '[0-9]{10}';
          numerodocInput.placeholder = 'Ej: 1234567890';
          numerodocInput.title = 'El RUC debe tener exactamente 10 dígitos';
          numerodocInput.removeAttribute('readonly');
        } else {
          numerodocInput.removeAttribute('pattern');
          numerodocInput.maxLength = 11;
          numerodocInput.removeAttribute('minlength');
          numerodocInput.placeholder = 'Selecciona el tipo primero';
          numerodocInput.setAttribute('readonly', '');
        }
      });
      numerodocInput.addEventListener('input', (e) => {
        const tipo = tipodocSel.value;
        const max = tipo === 'DNI' ? 8 : tipo === 'RUC' ? 10 : 10;
        e.target.value = e.target.value.replace(/[^\d]/g, '').slice(0, max);
      });
    }

    checkoutForm.addEventListener('submit', (e) => {
      e.preventDefault();

      const fd = new FormData(e.target);
      const data = Object.fromEntries(fd.entries());

      // Validar carrito no vacío
      if (!window.carrito.length) {
        showToast('Tu carrito está vacío. Agrega productos antes de finalizar la compra.', 'warning', 3000);
        return;
      }

      // Validar tipo y número de documento
      const tipoDoc = (data.tipodoc || '').trim();
      const numDoc = (data.numerodoc || '').trim();
      if (!tipoDoc) {
        showToast('Selecciona el tipo de documento (DNI o RUC).', 'warning', 3000);
        return;
      }
      if (tipoDoc === 'DNI' && !/^\d{8}$/.test(numDoc)) {
        showToast('El DNI debe contener exactamente 8 dígitos numéricos.', 'warning', 3000);
        return;
      }
      if (tipoDoc === 'RUC' && !/^\d{10}$/.test(numDoc)) {
        showToast('El RUC debe contener exactamente 10 dígitos numéricos.', 'warning', 3000);
        return;
      }

      if (data.metodoRecojo === 'recojo' &&
         (!data.tiendaUbicacion || data.tiendaUbicacion === '')) {

        showToast('Por favor, selecciona la tienda donde recoger�s tu pedido.', 'warning', 3000);
        return;
      }

      const autorizacion = fd.get('autorizarFactura') ? 'S�' : 'No';

      // mensaje para WhatsApp
      let mensaje = `*?? NUEVO PEDIDO*\n\n`;

      mensaje += `*Datos del cliente:*\n`;
      mensaje += `Nombre: ${data.nombre || '-'} ${data.apellido || '-'}\n`;
      mensaje += `${tipoDoc}: ${numDoc || '-'}\n`;
      mensaje += `Tel�fono: ${data.telefono || '-'}\n`;
      mensaje += `Direcci�n: ${data.direccion || '-'}\n`;
      mensaje += `Correo: ${data.email || '-'}\n`;
      mensaje += `Entrega: ${data.metodoRecojo === 'recojo' ? 'Recojo en tienda' : 'Delivery'}\n`;
      mensaje += `Tienda: ${data.tiendaUbicacion || 'No especificada'}\n`;
      mensaje += `Método de pago: ${data.metodoPago || '-'}\n`;
      mensaje += `Autorización de factura: ${autorizacion}\n`;
      mensaje += `----------------------\n`;
      mensaje += `*Productos:*\n`;

      if (window.carrito.length === 0) {
        mensaje += `- (carrito vacío)\n`;
      } else {
        window.carrito.forEach((item, i) => {
          mensaje += `${i + 1}. ${item.nombre} (x${item.cantidad})\n`;
        });
      }

      mensaje += `----------------------\n`;
      mensaje += `Gracias por su compra ??`;

      const url = `https://api.whatsapp.com/send?phone=${window.WHATSAPP_NUMBER}&text=${encodeURIComponent(mensaje)}`;
      const win = window.open(url, "_blank");
      if (!win) window.location.href = url;

      showToast('Pedido enviado correctamente.', 'success', 4000);

      window.carrito = [];
      renderCarrito();

      const checkoutModalInst = bootstrap.Modal.getInstance($('#checkoutModal'));
      if (checkoutModalInst) checkoutModalInst.hide();

      checkoutForm.reset();
      if (tiendaWrapper) tiendaWrapper.style.display = 'none';
    });
  }

  /* =========================
     BUSCADOR - MÚLTIPLES INPUTS
     ========================= */
  
  // Configurar búsqueda para navbar (desktop)
  setupCatalogSearch('#navbarSearch', '#navbarSearchForm', '#searchSuggestions');
  
  // Configurar búsqueda para drawer (mobile)
  setupCatalogSearch('#drawerProductSearch', '#drawerSearchForm', '#drawerSearchSuggestions');
}

function setupCatalogSearch(inputSelector, formSelector, suggestionsSelector) {
  const searchInput = $(inputSelector);
  const searchForm = $(formSelector);
  const suggestions = $(suggestionsSelector);

  if (!searchInput || !suggestions) return;

  const searchHandler = () => {
    const q = (searchInput.value || '').trim().toLowerCase();

    if (!q) {
      suggestions.style.display = 'none';
      suggestions.innerHTML = '';
      return;
    }

    const matches = window.productos
      .filter(productoEstaDisponible)
      .filter(p =>
        (p.nombre || '').toLowerCase().includes(q) || (p.sku || '').includes(q)
      );

    suggestions.innerHTML = matches.slice(0, 6).map(p => `
      <a href="/views/producto.html?sku=${p.sku}"
         class="list-group-item list-group-item-action d-flex align-items-center search-suggestion"
         data-sku="${p.sku}"
         style="padding: 10px 12px; border-bottom: 1px solid #eee;">
         
        <img src="${p.imagenes?.[0] || ''}"
             alt="${p.nombre}"
             style="width:40px;height:40px;object-fit:contain;"
             class="me-2">

        <div style="flex: 1;">
          <div class="small fw-semibold">${p.nombre}</div>
        </div>

      </a>
    `).join('');

    suggestions.style.display = matches.length ? 'block' : 'none';
  };

  searchInput.addEventListener('input', searchHandler);

  // Manejar submit (lupa)
  if (searchForm) {
    searchForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const q = (searchInput.value || '').trim().toLowerCase();
      if (!q) return;
      const matches = window.productos
        .filter(productoEstaDisponible)
        .filter(p =>
          (p.nombre || '').toLowerCase().includes(q) || (p.sku || '').toLowerCase().includes(q)
        );
      if (matches.length === 1) {
        // Ir directo al producto si hay una sola coincidencia
        window.location.href = `/views/producto.html?sku=${matches[0].sku}`;
      } else {
        // Ir al catálogo con query para mostrar resultados
        window.location.href = `/views/catalogo.html?q=${encodeURIComponent(q)}`;
      }
    });
  }

  suggestions.addEventListener('click', (e) => {
    const a = e.target.closest('.search-suggestion');
    if (!a) return;

    e.preventDefault();
    const sku = a.dataset.sku;

    window.location.href = `/views/producto.html?sku=${sku}`;
  });

  document.addEventListener('click', (e) => {
    if (!e.target.closest(inputSelector) &&
        !e.target.closest(suggestionsSelector)) {
      suggestions.style.display = 'none';
    }
  });
}

/* =========================
   CARGA DE PRODUCTOS DESDE JSON
   ========================= */
function cargarProductosDesdeJSON() {
  return fetch('/assets/Data/productos.json')
    .then(res => {
      if (!res.ok) throw new Error('No se pudo cargar productos.json');
      return res.json();
    })
    .then(data => {

      // ADAPTAR A TU JSON
      window.productos = data.map(p => {

    const disponible = productoEstaDisponible(p);

  // convertir miniaturas (objeto) ? array
  const imagenes = p.miniaturas
    ? Object.values(p.miniaturas).filter(img => img !== null && img !== undefined)
    : [];

      const resenas = Array.isArray(p["rese\u00f1as"])
        ? p["rese\u00f1as"]
        : (Array.isArray(p.reseas) ? p.reseas : []);

      return {
        sku: String(p.sku),
        marca: p.marca || "",
        nombre: p.nombre,
        categoria: p.categoria,
        descripcion: p.descripcion || "",
        descripcion_larga: p.descripcion_larga || "",

        peso: p.peso || 0,
        oferta: Boolean(p.oferta),
        oferta_disponible: Boolean(p.oferta_disponible),

        // clave para que no se rompa la UI
        imagenes: imagenes,
        miniaturas: imagenes,

        ficha_pdf: p.ficha_pdf || "",
        hoja_seguridad_pdf: p.hoja_seguridad_pdf || "",

        "rese\u00f1as": resenas,
        similares: Array.isArray(p.similares) ? p.similares : [],
        producto_disponible: disponible
      };
    }).filter(p => p.producto_disponible);

    })
    .catch(err => {
      console.error('Error cargando productos:', err);
      showToast('No se pudo cargar el cat�logo de productos.', 'danger', 4000);
    });
}

/* =========================
   INIT
   ========================= */
document.addEventListener('DOMContentLoaded', () => {

  ensureBootstrapOnce();
  setupUIContainers();
  renderCatalogoNavbar();
  renderMarcaCatalogo();
  cargarYRenderizarPromociones();
  cargarYRenderizarCategorias();
  // cargar productos reales
  cargarProductosDesdeJSON().then(() => {
    renderDropdownCatalogo();

    setTimeout(() => {
      setupGlobalEvents();
      renderCarrito();

      // Si venimos con ?q= mostramos el t�rmino en la barra de b�squeda
      try {
        const params = new URLSearchParams(window.location.search);
        const qParam = params.get('q');
        if (qParam && qParam.trim() && $('#navbarSearch')) {
          $('#navbarSearch').value = qParam;
        }
      } catch (err) { }

    }, 300);

  });
});

/* =========================
   CAMPO DE APLICACI�N CARRUSEL
   ========================= */
document.addEventListener('DOMContentLoaded', function() {
    const contenedor = document.querySelector('.campo-aplicacion-contenedor');
    const btnPrev = document.querySelector('.campo-aplicacion-prev');
    const btnNext = document.querySelector('.campo-aplicacion-next');
    
    if (!contenedor || !btnPrev || !btnNext) return;
    
    const scrollAmount = 210; // Ancho del item (180px) + gap (30px)
    
    btnPrev.addEventListener('click', function(e) {
        e.preventDefault();
        contenedor.scrollBy({
            left: -scrollAmount,
            behavior: 'smooth'
        });
    });
    
    btnNext.addEventListener('click', function(e) {
        e.preventDefault();
        contenedor.scrollBy({
            left: scrollAmount,
            behavior: 'smooth'
        });
    });
});

