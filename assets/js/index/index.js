const carrucelDiv = document.querySelector('.carrucel-portada');

const carruselHTML = `
<!-- CARRUSEL MÓVIL -->
<div id="carouselPortadaMobile" class="carousel slide prim-carrusel d-block d-md-none" data-bs-ride="carousel" data-bs-interval="4500" data-bs-pause="false">

  <!-- Indicadores Móvil -->
  <div class="carousel-indicators prim-indicadores">
    <button type="button" data-bs-target="#carouselPortadaMobile" data-bs-slide-to="0" class="active"></button>
    <button type="button" data-bs-target="#carouselPortadaMobile" data-bs-slide-to="1"></button>
  </div>

  <div class="carousel-inner prim-inner">

    <div class="carousel-item active">
      <img src="/assets/img portada/PORTADA-MOVIL/2.png"
           class="d-block w-100 prim-img" alt="Portada Mobile 1">
    </div>

    <div class="carousel-item">
      <img src="/assets/img portada/PORTADA-MOVIL/1.png"
           class="d-block w-100 prim-img" alt="Portada Mobile 2">
    </div> 

  </div>

  <!-- Controles Móvil -->
  <button class="carousel-control-prev prim-control" type="button" data-bs-target="#carouselPortadaMobile" data-bs-slide="prev">
    <span class="carousel-control-prev-icon"></span>
    <span class="visually-hidden">Anterior</span>
  </button>

  <button class="carousel-control-next prim-control" type="button" data-bs-target="#carouselPortadaMobile" data-bs-slide="next">
    <span class="carousel-control-next-icon"></span>
    <span class="visually-hidden">Siguiente</span>
  </button>

</div>

<!-- CARRUSEL DESKTOP/TABLET -->
<div id="carouselPortadaDesktop" class="carousel slide prim-carrusel d-none d-md-block" data-bs-ride="carousel" data-bs-interval="4500" data-bs-pause="false">

  <!-- Indicadores Desktop -->
  <div class="carousel-indicators prim-indicadores">
    <button type="button" data-bs-target="#carouselPortadaDesktop" data-bs-slide-to="0" class="active"></button>
    <button type="button" data-bs-target="#carouselPortadaDesktop" data-bs-slide-to="1"></button>
  </div>

  <div class="carousel-inner prim-inner">

    <div class="carousel-item active">
      <img src="/assets/img portada/PORTADA/2.jpg"
           class="d-block w-100 prim-img" alt="Portada 1">
    </div>

    <div class="carousel-item">
      <img src="/assets/img portada/PORTADA/1.jpg"
           class="d-block w-100 prim-img" alt="Portada 2">
    </div> 

  </div>

  <!-- Controles Desktop -->
  <button class="carousel-control-prev prim-control" type="button" data-bs-target="#carouselPortadaDesktop" data-bs-slide="prev">
    <span class="carousel-control-prev-icon"></span>
    <span class="visually-hidden">Anterior</span>
  </button>

  <button class="carousel-control-next prim-control" type="button" data-bs-target="#carouselPortadaDesktop" data-bs-slide="next">
    <span class="carousel-control-next-icon"></span>
    <span class="visually-hidden">Siguiente</span>
  </button>

</div>
`;

if (carrucelDiv) {
  carrucelDiv.innerHTML = carruselHTML;
}

let swiperSucursalesAuto = null;

function renderSucursalesSlides(sucursales) {
  const wrapper = document.getElementById('sucursales-swiper-wrapper');
  if (!wrapper) return;

  wrapper.innerHTML = sucursales.map((sucursal) => `
    <div class="swiper-slide">
      <div class="sucursal-auto-card" data-sucursal="${sucursal.key}">
        <div class="sucursal-auto-img-wrapper" style="object-fit: cover !important;">
          <img src="${sucursal.imagen}" alt="${sucursal.alt || sucursal.nombre}" class="sucursal-auto-img">
        </div>
        <div class="sucursal-auto-info">
          <h4>${sucursal.nombre}</h4>
          <p>${sucursal.direccion}</p>
          <p class="sucursal-auto-referencia">${sucursal.referencia}</p>
        </div>
      </div>
    </div>
  `).join(''); 
}

function bindSucursalesClick() {
  document.querySelectorAll('.sucursal-auto-card').forEach((card) => {
    card.addEventListener('click', () => {
      const key = card.dataset.sucursal;
      window.location.href = `/views/ubicacion.html?sucursal=${key}`;
    });
  });
}

function initSucursalesSwiper() {
  if (typeof Swiper === 'undefined') return;

  if (swiperSucursalesAuto) {
    swiperSucursalesAuto.destroy(true, true);
  }

  swiperSucursalesAuto = new Swiper('.carrucel-sucursales-automatico', {
    slidesPerView: 1,
    spaceBetween: 20,
    loop: true,
    navigation: {
      prevEl: '.sucursales-nav-btn.prev',
      nextEl: '.sucursales-nav-btn.next'
    },
    autoplay: {
      delay: 2000,
      disableOnInteraction: false,
    },
    breakpoints: {
      640: {
        slidesPerView: 2,
        spaceBetween: 15,
      },
      1024: {
        slidesPerView: 3,
        spaceBetween: 20,
      },
      1280: {
        slidesPerView: 5,
        spaceBetween: 25,
      }
    }
  });
}

function cargarSucursalesDesdeJSON() {
  return fetch('/assets/Data/sucursales.json')
    .then((res) => {
      if (!res.ok) throw new Error('No se pudo cargar sucursales.json');
      return res.json();
    })
    .then((data) => {
      const sucursales = Array.isArray(data) ? data : [];
      renderSucursalesSlides(sucursales);
      bindSucursalesClick();
      if (sucursales.length > 0) {
        initSucursalesSwiper();
      }
    })
    .catch((error) => {
      console.error(error);
    });
}

document.addEventListener('DOMContentLoaded', () => {
  cargarSucursalesDesdeJSON();
});


//////////////////////////
///CARUCEL DE MARCA//////
///////////////////////
// =============================================
// 1. DEFINICIÓN DE LA FUNCIÓN DEL CARRUSEL
// (Esta es la función que ya tenías)
// =============================================
function crearCarruselMarcas(selector, logos) {
  const container = document.querySelector(selector);
  if (!container) {
    console.error(`Error: No se encontró el contenedor con el selector "${selector}".`);
    return;
  }
  const styles = `
        .logo-carousel-container { max-width: 800px; margin: auto; padding: 2rem; background-color: white; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .logo-carousel-container .carousel-item img { max-width: 250px; height: auto; margin: 0 auto; }
        .logo-carousel-container .carousel-control-prev-icon, .logo-carousel-container .carousel-control-next-icon { background-color: rgba(0,0,0,0.5); border-radius: 50%; padding: 20px; }
        .logo-carousel-container h2 { margin-bottom: 1.5rem; }
    `;
  const styleSheet = document.createElement("style");
  styleSheet.innerText = styles;
  document.head.appendChild(styleSheet);
  const indicatorsHTML = logos.map((_, index) => `<button type="button" data-bs-target="#logoCarousel" data-bs-slide-to="${index}" class="${index === 0 ? 'active' : ''}" aria-current="${index === 0}" aria-label="Slide ${index + 1}"></button>`).join('');
  const itemsHTML = logos.map((logo, index) => `<div class="carousel-item ${index === 0 ? 'active' : ''}"><img src="${logo.src}" class="d-block w-100" alt="${logo.alt}"></div>`).join('');
  const carouselHTML = `
        <div class="logo-carousel-container">
            <h2 class="text-center">Nuestras Marcas</h2>
            <div id="logoCarousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-indicators">${indicatorsHTML}</div>
                <div class="carousel-inner">${itemsHTML}</div>
                <button class="carousel-control-prev" type="button" data-bs-target="#logoCarousel" data-bs-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Anterior</span></button>
                <button class="carousel-control-next" type="button" data-bs-target="#logoCarousel" data-bs-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">Siguiente</span></button>
            </div>
        </div>`;
  container.innerHTML = carouselHTML;
}

// =============================================
// 2. DATOS Y LLAMADA A LA FUNCIÓN
// (Esto es lo que movimos desde el HTML)
// =============================================

// Lista de logos apuntando a las imágenes en tu repositorio de GitHub
const misLogos = [
  { src: 'assets/icon/icon-volcan.jpg', nombre: 'Logo Volcan' },
  { src: 'assets/icon/icon-norton.jpg', nombre: 'Logo Norton' },
  { src: 'assets/icon/icon-aditivos.jpg', nombre: 'Logo Aditivos' },
  { src: 'assets/icon/icon-werber.jpg', nombre: 'Logo Weber' },
  { src: 'assets/icon/icon-soudal.jpg', nombre: 'Logo Soudal' }
];

// Llama a la función para que construya el carrusel
// Usamos un 'DOMContentLoaded' para asegurarnos que el HTML está listo antes de ejecutar el script.
// COMENTADO: Este carrusel se usa en catalogo.html, no en index.html
// document.addEventListener('DOMContentLoaded', function () {
//   crearCarruselMarcas('.carrusel-marcas', misLogos);
// });


/////////////////////////////
/////CARRUCEL DE MARCA//////
///////////////////////////
// Configura aquí tus imágenes y nombres
// Configura aquí tus imágenes y nombres
const carrucelData = [
  { src: 'assets/icon/icon-volcan.jpg', nombre: 'Volcan' },
  { src: 'assets/imgWeb/marcasIMG/image-removebg-preview (6).png', nombre: 'Norton' },
  { src: 'assets/imgWeb/marcasIMG/image-removebg-preview (8).png', nombre: 'Aditivos' },
  { src: 'assets/imgWeb/marcasIMG/icon-werber.jpg', nombre: 'Weber' },
  { src: 'assets/imgWeb/marcasIMG/image-removebg-preview (9).png', nombre: 'Soudal' }
];

// Inserta el carrusel en todos los .carrucel-marca del DOM
document.querySelectorAll('.carrucel-marca').forEach((target) => {
  // 1. Crea el HTML (clases modificadas con "ss")
  target.innerHTML = `
    <div class="sscontainer_carru">
      <button style="display: none;" class="ssarrow left">&#9664;</button>
      <button style="display: none;" class="ssarrow right">&#9654;</button>
      <div class="sscarousel-items"></div>
    </div>
  `;

  // 2. Agrega los items
  const itemsDiv = target.querySelector('.sscarousel-items');
  carrucelData.forEach(item => {
    const itemDiv = document.createElement('div');
    itemDiv.className = 'sscarousel-item';
    itemDiv.innerHTML = `
      <img src="${item.src}" alt="${item.nombre}">
      <span class="sscarousel-nombre">${item.nombre}</span>
    `;
    itemsDiv.appendChild(itemDiv);
  });

  // 3. Funcionalidad JS (Carrusel + Autoplay + Swipe + Flechas)
  const items = itemsDiv.querySelectorAll('.sscarousel-item');
  let center = Math.floor(items.length / 2);

  function updateCarousel() {
    items.forEach((el, idx) => {
      el.classList.remove('center', 'left', 'right', 'hide');
      if (idx === center) {
        el.classList.add('center');
      } else if (idx === center - 1) {
        el.classList.add('left');
      } else if (idx === center + 1) {
        el.classList.add('right');
      } else {
        el.classList.add('hide');
      }
    });
  }

  function next() {
    center = (center + 1) % items.length;
    updateCarousel();
  }
  function prev() {
    center = (center - 1 + items.length) % items.length;
    updateCarousel();
  }

  // Autoplay cada 3 segundos
  let autoplayInterval = setInterval(next, 3000);

  // Reset autoplay al interactuar
  function resetAutoplay() {
    clearInterval(autoplayInterval);
    autoplayInterval = setInterval(next, 3000);
  }

  target.querySelector('.ssarrow.left').addEventListener('click', () => {
    prev();
    resetAutoplay();
  });
  target.querySelector('.ssarrow.right').addEventListener('click', () => {
    next();
    resetAutoplay();
  });

  // Swipe para móvil
  let startX = null;
  itemsDiv.addEventListener('touchstart', function (e) {
    startX = e.touches[0].clientX;
  });
  itemsDiv.addEventListener('touchend', function (e) {
    let endX = e.changedTouches[0].clientX;
    if (startX && Math.abs(endX - startX) > 40) {
      if (endX < startX) next();
      else prev();
      resetAutoplay();
    }
    startX = null;
  });

  // Inicializar
  updateCarousel();
});

/////////////////////////////
/////     informes    //////
///////////////////////////

const diversosData = [
  {
    img: "assets/imgWeb/soloClick/image-removebg-preview (30).png",
    title: "RESPALDO TÉCNICO"
  },
  {
    img: "assets/imgWeb/soloClick/image-removebg-preview (1).png",
    title: "DESPACHOS MÁS ÁGILES"
  },
  {
    img: "assets/imgWeb/soloClick/image-removebg-preview (3).png",
    title: "SOLUCIONES INTEGRALES"
  },
  {
    img: "assets/imgWeb/soloClick/image-removebg-preview (2).png",
    title: "TRANSACCIONES RÁPIDAS"
  }
];

// Textos principales (puedes editar)
const sectionTitle = "OBTEN DE TODO EN UN SOLO LUGAR"; 

// Renderiza la sección en el div .diversos
function renderDiversosSection() {
  document.querySelectorAll('.diversos').forEach(container => {
    container.innerHTML = `
      <section class="py-4" style="background:#0866b1;">
        <div class="container">
          <div class="row mb-4 justify-content-center text-center">
            <div class="col-12 col-md-6">
              <h2 class="fw-bold text-white mb-1" style="font-size:2rem;">${sectionTitle}</h2> 
            </div>
          </div>
          <div class="row g-4 justify-content-center">
            ${diversosData.map(card => `
              <div class="col-6 col-md-3">
                <div class="d-flex flex-column align-items-center text-center">
                  <div class="ratio ratio-1x1 w-100 diversos-media rounded-4 overflow-hidden border border-3">
                    <img src="${card.img}" alt="${card.title}" class="w-100 h-100">
                  </div>
                  <h6 class="mt-3 fw-bold text-white text-uppercase" style="font-size:1rem; letter-spacing:1px;">${card.title}</h6>
                </div>
              </div>
            `).join('')}
          </div>
        </div>
      </section>
    `;
  });
}

// Ejecuta al cargar
renderDiversosSection();

// flecha inferior que desplaza al contenido de 'campos de aplicacion'
const scrollArrow = document.querySelector('.video-overlay .scroll-down');
if (scrollArrow) {
    scrollArrow.addEventListener('click', function(e) {
        e.preventDefault();
        const target = document.getElementById('sedes-distribucion');
        if (target) {
            target.scrollIntoView({ behavior: 'smooth' });
        }
    });
}

//////////////////////////
//////  CATEGORIA  //////
//////////////////////////

let ccCategorias = [
  { nombre: 'CURADORES', img: '/assets/img principales/logo.png', link: '/views/catalogo.html?cat=CURADORES' }
];

let ccInicio = 0;

function getCcMostrar() {
  const w = window.innerWidth;
  if (w >= 1200) return 6;
  if (w >= 992) return 4;
  if (w >= 768) return 3;
  if (w >= 480) return 2;
  return 1;
}

function ccRenderCarrucelCategoria() {
  const mainDiv = document.querySelector('.cc-carrucel-categoria');
  if (!mainDiv) return;

  if (!Array.isArray(ccCategorias) || ccCategorias.length === 0) {
    mainDiv.innerHTML = '';
    return;
  }

  mainDiv.innerHTML = `
    <div class="cc-carrucel-categoria-wrapper">
      <button class="cc-carrucel-categoria-btn left" aria-label="Anterior"></button>
      <div class="cc-carrucel-categoria-productos"></div>
      <button class="cc-carrucel-categoria-btn right" aria-label="Siguiente"></button>
    </div>
  `;
  ccRenderItems();
  mainDiv.querySelector('.cc-carrucel-categoria-btn.left').onclick = ccPrevCategoria;
  mainDiv.querySelector('.cc-carrucel-categoria-btn.right').onclick = ccNextCategoria;
}

function normalizeCatalogPath(path) {
  if (!path) return "";
  const cleaned = path.replace(/\\/g, "/").replace(/^\/+/, "");
  const withLeading = cleaned.startsWith("assets/") ? `/${cleaned}` : `/${cleaned}`;
  return encodeURI(withLeading);
}

function getCatalogFallback(path) {
  if (!path) return "";
  const cleaned = path.replace(/\\/g, "/");
  const fileName = cleaned.split("/").pop();
  if (!fileName) return "";
  return encodeURI(`/assets/img catalogo/copia/${fileName}`);
}

function ccRenderItems() {
  const ccMostrar = getCcMostrar();
  const prodDiv = document.querySelector('.cc-carrucel-categoria-productos');
  if (!prodDiv || !Array.isArray(ccCategorias) || ccCategorias.length === 0) return;
  prodDiv.innerHTML = '';
  for (let i = 0; i < ccMostrar; i++) {
    const idx = (ccInicio + i) % ccCategorias.length;
    const p = ccCategorias[idx];
    const imgSrc = normalizeCatalogPath(p.img);
    const fallbackSrc = getCatalogFallback(p.img);
    prodDiv.innerHTML += `
      <div class="cc-categoria-item">
        <img src="${imgSrc}" data-fallback="${fallbackSrc}" alt="${p.nombre}">
        <div class="cc-info-hover d-flex flex-column">
          <a class="cc-comprar-btn" href="${p.link}" target="_blank">Comprar</a>
        </div>
        <div class="cc-categoria-titulo">${p.nombre}</div>
      </div>
    `;
  }

  prodDiv.querySelectorAll('img[data-fallback]').forEach((img) => {
    img.addEventListener('error', () => {
      const fallback = img.getAttribute('data-fallback');
      if (fallback && img.src !== fallback) {
        img.src = fallback;
      }
    }, { once: true });
  });
}

function ccPrevCategoria() {
  if (!ccCategorias.length) return;
  ccInicio = (ccInicio - 1 + ccCategorias.length) % ccCategorias.length;
  ccRenderItems();
}

function ccNextCategoria() {
  if (!ccCategorias.length) return;
  ccInicio = (ccInicio + 1) % ccCategorias.length;
  ccRenderItems();
}

function ccConstruirCategoriasDesdeJSON(data) {
  const normaliza = (valor) => (valor || '')
    .toString()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .trim()
    .toLowerCase();

  const map = new Map();

  data.forEach((p) => {
    const categoria = (p?.categoria || '').toString().trim();
    const key = normaliza(categoria);
    if (!key) return;

    if (!map.has(key)) {
      const miniaturas = p?.miniaturas ? Object.values(p.miniaturas).filter(img => img !== null && img !== undefined) : [];
      const imagen = p?.imagen || miniaturas[0] || '/assets/img principales/logo.png';

      map.set(key, {
        nombre: categoria,
        img: imagen,
        link: `/views/catalogo.html?cat=${encodeURIComponent(categoria)}`
      });
    }
  });

  return Array.from(map.values())
    .sort((a, b) => a.nombre.localeCompare(b.nombre, 'es', { sensitivity: 'base' }));
}

function ccConstruirCategoriasDesdeConfig(data) {
  if (!Array.isArray(data)) return [];

  return data
    .map((item) => {
      const nombre = (item?.nombre || item?.categoria || '').toString().trim();
      const img = (item?.img || item?.imagen || '/assets/img principales/logo.png').toString();
      const link = (item?.link || `/views/catalogo.html?cat=${encodeURIComponent(nombre)}`).toString();

      if (!nombre) return null;

      return { nombre, img, link };
    })
    .filter(Boolean);
}

function ccCargarCategoriasDesdeJSON() {
  return fetch('/assets/Data/categorias.json')
    .then((res) => {
      if (!res.ok) throw new Error('No se pudo cargar categorias.json');
      return res.json();
    })
    .then((data) => {
      const categorias = ccConstruirCategoriasDesdeConfig(data);
      if (categorias.length > 0) {
        ccCategorias = categorias;
        ccInicio = 0;
        ccRenderCarrucelCategoria();
        return;
      }

      throw new Error('categorias.json sin datos');
    })
    .catch(() => {
      return fetch('/assets/Data/productos.json')
        .then((res) => {
          if (!res.ok) throw new Error('No se pudo cargar productos.json');
          return res.json();
        })
        .then((data) => {
          const categorias = ccConstruirCategoriasDesdeJSON(Array.isArray(data) ? data : []);
          if (categorias.length > 0) {
            ccCategorias = categorias;
            ccInicio = 0;
            ccRenderCarrucelCategoria();
          }
        })
        .catch(() => {
          // Se mantiene fallback en memoria
        });
    });
}

window.addEventListener('resize', ccRenderItems);

document.addEventListener('DOMContentLoaded', () => {
  ccCargarCategoriasDesdeJSON().finally(() => {
    ccRenderCarrucelCategoria();
  });
});

/////////////////////////////////////////
/////////// BENEFICIOS /////////////////
///////////////////////////////////////

const ben_beneficios = [
  {
    titulo: "ENTREGA PROGRAMADA Y PUNTUAL",
    descripcion: "Recibe tus pedidos cuando y donde los necesites, sin retrasos.",
    img: "/assets/imgWeb/soloClick/image-removebg-preview (12).png",
    detalle: "Contamos con un sistema de logística que garantiza los tiempos de entrega de tus productos."
  },
  {
    titulo: "MÉTODOS DE PAGO SEGUROS",
    descripcion: "Todos los métodos de pago seguros para tu comodidad.",
    img: "/assets/imgWeb/soloClick/image-removebg-preview (10).png",
    detalle: "Aceptamos tarjetas, transferencias y pagos en efectivo en puntos autorizados."
  },
  {
    titulo: "PRODUCTOS CERTIFICADOS",
    descripcion: "Calidad garantizada para obras seguras y duraderas.",
    img: "/assets/imgWeb/soloClick/image-removebg-preview (13).png",
    detalle: "Nuestros productos cumplen normas internacionales de calidad y seguridad."
  },
  {
    titulo: "ATENCIÓN RÁPIDA Y PERSONALIZADA",
    descripcion: "Resolvemos tus consultas y pedidos sin demoras.",
    img: "/assets/imgWeb/soloClick/image-removebg-preview (11).png",
    detalle: "Nuestro equipo brinda soporte y atención personalizada en cada etapa del proceso."
  }
];

// COMENTADO: Sección de beneficios - elemento 'ben-root' no existe en index.html
/* 
const ben_root = document.getElementById('ben-root');

ben_root.innerHTML = `
  <h2 class="ben-title text-center mt-4 mb-4">BENEFICIOS</h2>
  <div class="container">
    <div class="row justify-content-center ben-row">
      ${ben_beneficios.map((b, i) => `
        <div class="col-12 col-sm-6 col-md-4 col-lg-2 mb-4">
          <div class="ben-card card text-center">
            <img src="${b.img}" alt="${b.titulo}" class="ben-img mb-3 mx-auto">
            <h6 class="ben-card-title">${b.titulo}</h6>
            <p class="ben-desc">${b.descripcion}</p>
            <a class="ben-link fw-bold text-primary" data-bs-toggle="modal" data-bs-target="#ben-modal" data-benindex="${i}">
              VER &gt;&gt;
            </a>
          </div>
        </div>
      `).join('')}
    </div>
  </div>

  <div class="modal fade" id="ben-modal" tabindex="-1" aria-labelledby="ben-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content ben-modal-content">
        <div class="modal-header">
          <h5 class="modal-title ben-modal-title" id="ben-modal-label"></h5>
          <button type="button" class="btn-close ben-modal-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body d-flex flex-column align-items-center">
          <img id="ben-modal-img" class="ben-modal-img mx-auto mb-3">
          <p id="ben-modal-desc" class="ben-modal-desc text-center"></p>
          <p id="ben-modal-detalle" class="ben-modal-detalle text-center"></p>
        </div>
      </div>
    </div>
  </div>
`;

document.querySelectorAll('.ben-link').forEach(link => {
  link.addEventListener('click', function () {
    const idx = this.getAttribute('data-benindex');
    const b = ben_beneficios[idx];
    document.getElementById('ben-modal-label').textContent = b.titulo;
    document.getElementById('ben-modal-img').src = b.img;
    document.getElementById('ben-modal-img').alt = b.titulo;
    document.getElementById('ben-modal-desc').textContent = b.descripcion;
    document.getElementById('ben-modal-detalle').textContent = b.detalle;
  });
});
*/

////////////////////////////////////////
///////////////  OBRAS  ///////////////
// Modular galería "Nuestras Obras", hover solo mitad
const obrasData = [
  { img: "assets/imgWeb/obrasIMG/obra1.png" },
  { img: "assets/imgWeb/obrasIMG/obra2.png" },
  { img: "assets/imgWeb/obrasIMG/obra3.png" },
  { img: "assets/imgWeb/obrasIMG/obra4.png" }
];

let randomInterval = null;

function renderObrasGaleria(selector = '.obras') {
  const cont = document.querySelector(selector);
  if (!cont) return;

  // Detecta si es móvil
  const isMobile = window.innerWidth < 768;

  // Limpia intervalo anterior
  if (randomInterval) {
    clearInterval(randomInterval);
    randomInterval = null;
  }

  if (isMobile) {
    function showRandomObra() {
      const randomIdx = Math.floor(Math.random() * obrasData.length);
      const ob = obrasData[randomIdx];
      cont.innerHTML = `
        <div class="obras-row obras-carrusel-mobile">
          <div class="obras-img-col">
            <img src="${ob.img}" alt="${ob.label}">
          </div>
        </div>
      `;
      // Efecto touch para agrandar imagen en móvil
      const obraCol = cont.querySelector('.obras-img-col');
      obraCol.addEventListener('touchstart', function () {
        obraCol.classList.add('active');
      });
      obraCol.addEventListener('touchend', function () {
        obraCol.classList.remove('active');
      });
    }
    showRandomObra();
    randomInterval = setInterval(showRandomObra, 2000);
  } else {
    // Galería normal
    cont.innerHTML = `
      <div class="obras-row">
        ${obrasData.map(ob => `
          <div class="obras-img-col">
            <img src="${ob.img}" alt="${ob.label}">
          </div>
        `).join('')}
      </div>
    `;

    // Efecto hover para agrandar imagen en escritorio
    const cols = cont.querySelectorAll('.obras-img-col');
    cols.forEach(div => {
      div.addEventListener('mouseenter', function () {
        cols.forEach(d => d.classList.remove('hover-half', 'active'));
        this.classList.add('hover-half', 'active');
      });
      div.addEventListener('mouseleave', function () {
        this.classList.remove('hover-half', 'active');
      });
    });
  }
}

window.addEventListener('resize', () => renderObrasGaleria());
document.addEventListener('DOMContentLoaded', () => renderObrasGaleria());
// Inicializa galería al cargar
// ===============================
// FORMULARIO DE BOLETÍN - INICIO
// Envía el correo del suscriptor al WhatsApp de BS PERÚ
// ===============================
const boletinForm = document.getElementById('boletinForm');
if (boletinForm) {
    boletinForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const emailInput = document.getElementById('boletinEmail');
        const email = emailInput.value.trim();
        
        if (!email) {
            alert('Por favor ingresa un correo válido');
            return;
        }
        
        const mensaje = `Hola, deseo suscribirme al boletín de BS PERÚ. Mi correo es: ${email}`;
        const mensajeCodificado = encodeURIComponent(mensaje);
        window.open(`https://wa.me/51914776669?text=${mensajeCodificado}`, '_blank');
        emailInput.value = '';
    });
}
// ===============================
// FORMULARIO DE BOLETÍN - FIN
// ===============================

// ===============================
// DRAWER MENÚ - INICIO (index.html)
// index.html tiene el navbar y drawer hardcodeados en el HTML,
// por eso necesita su propio setup (loyout.js no actúa sin barrera-menu)
// ===============================
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtnMobile = document.getElementById('navbar-bs-toggle-mobile');
    const toggleBtnDesktop = document.getElementById('navbar-bs-toggle-desktop');
    const drawer = document.getElementById('navbarDrawer');
    const drawerOverlay = document.getElementById('drawerOverlay');
    const drawerCloseBtn = document.getElementById('drawerCloseBtn');
    const drawerProductosToggle = document.getElementById('drawerProductosToggle');
    const drawerProductosPanel = document.getElementById('drawerProductosPanel');
    const navbar = document.querySelector('.navbar-bs');
    
    function toggleDrawer() {
        drawer.classList.toggle('active');
        drawerOverlay.classList.toggle('active');
        navbar.classList.toggle('navbar-hidden');
    }
    
    if (toggleBtnMobile) toggleBtnMobile.addEventListener('click', toggleDrawer);
    if (toggleBtnDesktop) toggleBtnDesktop.addEventListener('click', toggleDrawer);
    if (drawerCloseBtn) drawerCloseBtn.addEventListener('click', toggleDrawer);
    if (drawerOverlay) drawerOverlay.addEventListener('click', toggleDrawer);
    
    if (drawerProductosToggle && drawerProductosPanel) {
        drawerProductosToggle.addEventListener('click', (e) => {
            e.preventDefault();
            drawerProductosPanel.classList.toggle('active');
            drawerProductosToggle.classList.toggle('active');
        });
    }
    
    if (drawer) {
        drawer.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                drawer.classList.remove('active');
                drawerOverlay.classList.remove('active');
                navbar.classList.remove('navbar-hidden');
                drawerProductosPanel.classList.remove('active');
                drawerProductosToggle.classList.remove('active');
            });
        });
    }
    
    cargarCategoriasSubmenuIndex();
    
    const itemWithSubmenu = document.querySelector('.navbar-bs__item-with-submenu');
    const productosSubmenu = document.getElementById('productosSubmenu');
    const productosLink = document.querySelector('.navbar-bs__productos-link');
    
    if (itemWithSubmenu && productosSubmenu && productosLink) {
        productosLink.addEventListener('click', (e) => {
            // Permitir navegación normalmente - el hover CSS controla la visibilidad
        });
    }
});
// ===============================
// DRAWER MENÚ - FIN (index.html)
// ===============================

// ===============================
// CARGA DE CATEGORÍAS EN SUBMENU DESKTOP - INICIO
// Carga categorías desde categorias.json y las muestra en el submenu del navbar del index
// ===============================
function cargarCategoriasSubmenuIndex() {
    fetch('/assets/Data/categorias.json')
        .then(res => {
            if (!res.ok) throw new Error('No se pudo cargar categorias.json');
            return res.json();
        })
        .then(categorias => {
            const submenu = document.getElementById('productosSubmenu');
            if (!submenu) return;
            
            const totalCats = categorias.length;
            const itemsPorColumna = Math.ceil(totalCats / 3);
            
            const col1 = categorias.slice(0, itemsPorColumna);
            const col2 = categorias.slice(itemsPorColumna, itemsPorColumna * 2);
            const col3 = categorias.slice(itemsPorColumna * 2);
            
            const crearColumna = (items) => {
                if (!items.length) return '';
                const itemsHTML = items.map(cat => `
                    <a href="/views/catalogo.html?cat=${encodeURIComponent(cat.nombre)}" class="navbar-bs__submenu-item">
                        ${cat.nombre}
                    </a>
                `).join('');
                return `<div class="navbar-bs__submenu-column">${itemsHTML}</div>`;
            };
            
            submenu.innerHTML = `
                <div class="navbar-bs__submenu-content">
                    ${crearColumna(col1)}
                    ${crearColumna(col2)}
                    ${crearColumna(col3)}
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
// ===============================
// CARGA DE CATEGORÍAS EN SUBMENU DESKTOP - FIN
// ===============================
