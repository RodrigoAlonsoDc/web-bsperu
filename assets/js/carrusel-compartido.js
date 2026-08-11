/* =========================
   FUNCIÓN COMPARTIDA PARA CARRUSELES
   ========================= */

/**
 * Renderiza un carrusel de categorías desde JSON
 * @param {Object} config - Configuración del carrusel
 * @param {string} config.containerId - ID del contenedor principal
 * @param {string} config.tipo - 'circular' o 'cuadrado' para aplicar estilos diferentes
 * @param {string} config.jsonUrl - URL del JSON a cargar (default: /assets/Data/categorias.json)
 */
function renderizarCarruselCategorias(config = {}) {
  const {
    containerId = '.campo-aplicacion-contenedor',
    tipo = 'circular', // 'circular' o 'cuadrado'
    jsonUrl = '/assets/Data/categorias.json'
  } = config;

  const contenedor = document.querySelector(containerId);
  if (!contenedor) {
    console.warn(`Contenedor no encontrado: ${containerId}`);
    return;
  }

  fetch(jsonUrl)
    .then(response => {
      if (!response.ok) throw new Error(`Error cargando ${jsonUrl}`);
      return response.json();
    })
    .then(categorias => {
      if (!Array.isArray(categorias) || categorias.length === 0) {
        console.warn('JSON vacío o inválido');
        return;
      }

      // Generar HTML según el tipo de carrusel
      const itemsHTML = categorias.map(cat => {
        const claseItem = tipo === 'circular' 
          ? 'campo-aplicacion-item' 
          : 'cc-categoria-item';
        
        const claseImg = tipo === 'circular' 
          ? 'campo-aplicacion-img-wrapper' 
          : 'cc-categoria-img-wrapper';
        
        const claseTitulo = tipo === 'circular' 
          ? 'campo-aplicacion-nombre' 
          : 'cc-categoria-titulo';

        // HTML basado en tipo
        if (tipo === 'circular') {
          return `
            <a href="${cat.link}" class="${claseItem}">
              <div class="${claseImg}">
                <img src="${cat.img}" alt="${cat.nombre}" class="campo-aplicacion-img">
              </div>
              <h4 class="${claseTitulo}">${cat.nombre}</h4>
            </a>
          `;
        } else {
          // Cuadrado (para index)
          return `
            <div class="${claseItem}">
              <div class="${claseImg}">
                <img src="${cat.img}" alt="${cat.nombre}" data-fallback="/assets/img catalogo/copia/${cat.img.split('/').pop()}">
              </div>
              <div class="cc-info-hover d-flex flex-column">
                <a class="cc-comprar-btn" href="${cat.link}">Comprar</a>
              </div>
              <div class="${claseTitulo}">${cat.nombre}</div>
            </div>
          `;
        }
      }).join('');

      contenedor.innerHTML = itemsHTML;

      // Agregar event listeners para imágenes con fallback (cuadrado)
      if (tipo === 'cuadrado') {
        contenedor.querySelectorAll('img[data-fallback]').forEach((img) => {
          img.addEventListener('error', () => {
            const fallback = img.getAttribute('data-fallback');
            if (fallback && img.src !== fallback) {
              img.src = fallback;
            }
          }, { once: true });
        });
      }
    })
    .catch(err => console.error('Error renderizando carrusel:', err));
}

/**
 * Configura la navegación (prev/next) para carruseles con scroll
 * @param {string} prevBtnSelector - Selector del botón anterior
 * @param {string} nextBtnSelector - Selector del botón siguiente
 * @param {string} containerSelector - Selector del contenedor scrollable
 * @param {number} scrollAmount - Cantidad de píxeles a desplazar (default: 210)
 */
function configurarNavegacionCarrusel(prevBtnSelector, nextBtnSelector, containerSelector, scrollAmount = 210) {
  const contenedor = document.querySelector(containerSelector);
  const btnPrev = document.querySelector(prevBtnSelector);
  const btnNext = document.querySelector(nextBtnSelector);

  if (!contenedor || !btnPrev || !btnNext) {
    console.warn('Selectores de navegación no válidos');
    return;
  }

  // Flag para prevenir clics múltiples durante la animación
  let isScrolling = false;

  // Función auxiliar para manejar el scroll
  function handleScroll(direction) {
    if (isScrolling) return; // Bloquear si ya está scrolleando
    
    isScrolling = true;
    const scrollAmount_ = direction === 'prev' ? -scrollAmount : scrollAmount;
    
    contenedor.scrollBy({
      left: scrollAmount_,
      behavior: 'smooth'
    });

    // Desbloquear después de que termina la animación (300ms es estándar para smooth scroll)
    setTimeout(() => {
      isScrolling = false;
    }, 300);
  }

  btnPrev.addEventListener('click', function(e) {
    e.preventDefault();
    handleScroll('prev');
  });

  btnNext.addEventListener('click', function(e) {
    e.preventDefault();
    handleScroll('next');
  });
}
