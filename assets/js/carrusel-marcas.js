/* =========================
   CARRUSEL DE MARCAS - JAVASCRIPT
   ========================= */

let marcasData = [];

/**
 * Carga las marcas desde el JSON y las renderiza
 */
function cargarYRenderizarMarcas() {
  fetch('/assets/Data/marcas-tiendas.json')
    .then(response => response.json())
    .then(data => {
      marcasData = data;
      renderizarMarcas();
      crearIndicadores(data.length);
      setupMarcasEventListeners();
    })
    .catch(err => console.error('Error cargando marcas:', err));
}

/**
 * Renderiza los items de marcas
 */
function renderizarMarcas() {
  const wrapper = document.getElementById('marcasWrapper');
  if (!wrapper) return;
  
  wrapper.innerHTML = marcasData.map((marca, index) => {
    const estiloClass = `marca-item estilo-${marca.estilo}`;
    const logoHtml = marca.logo ? `<img src="${marca.logo}" alt="${marca.nombre}" onerror="this.style.display='none'">` : '';
    
    return `
      <a href="${marca.link}" target="_blank" class="${estiloClass}" data-index="${index}" style="${marca.gradient ? `background: ${marca.gradient}` : ''}">
        <div class="marca-item-logo">
          ${logoHtml}
        </div>
        <div class="marca-item-text">
          <strong>${marca.nombre}</strong>
          ${marca.subtitulo ? `<small>${marca.subtitulo}</small>` : ''}
        </div>
      </a>
    `;
  }).join('');
}

/**
 * Crea los indicadores (dots) del carrusel
 */
function crearIndicadores(cantidad) {
  const container = document.getElementById('marcasIndicadores');
  if (!container) return;
  
  let html = '';
  for (let i = 0; i < cantidad; i++) {
    const esActivo = i === 0 ? 'activo' : '';
    html += `<span class="marca-indicador ${esActivo}" data-index="${i}" onclick="scrollToMarca(${i})"></span>`;
  }
  container.innerHTML = html;
}

/**
 * Navega al item de marca especificado
 */
function scrollToMarca(index) {
  const wrapper = document.getElementById('marcasWrapper');
  if (!wrapper) return;
  
  const items = wrapper.querySelectorAll('.marca-item');
  if (items[index]) {
    items[index].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'start' });
    updateMarcasIndicadores();
  }
}

/**
 * Scroll hacia la izquierda
 */
function scrollMarcasLeft() {
  const wrapper = document.getElementById('marcasWrapper');
  if (wrapper) {
    wrapper.scrollBy({ left: -180, behavior: 'smooth' });
    setTimeout(updateMarcasIndicadores, 400);
  }
}

/**
 * Scroll hacia la derecha
 */
function scrollMarcasRight() {
  const wrapper = document.getElementById('marcasWrapper');
  if (wrapper) {
    wrapper.scrollBy({ left: 180, behavior: 'smooth' });
    setTimeout(updateMarcasIndicadores, 400);
  }
}

/**
 * Actualiza los indicadores según el scroll actual
 */
function updateMarcasIndicadores() {
  const wrapper = document.getElementById('marcasWrapper');
  const items = wrapper.querySelectorAll('.marca-item');
  const indicadores = document.querySelectorAll('.marca-indicador');
  
  if (!wrapper || items.length === 0) return;
  
  let activeIndex = 0;
  let minDistance = Infinity;
  
  items.forEach((item, index) => {
    const rect = item.getBoundingClientRect();
    const wrapperRect = wrapper.getBoundingClientRect();
    const distance = Math.abs(rect.left + rect.width / 2 - (wrapperRect.left + wrapperRect.width / 2));
    
    if (distance < minDistance) {
      minDistance = distance;
      activeIndex = index;
    }
  });
  
  // Actualizar indicadores
  indicadores.forEach((ind, index) => {
    if (index === activeIndex) {
      ind.classList.add('activo');
    } else {
      ind.classList.remove('activo');
    }
  });
  
  updateMarcasButtons();
}

/**
 * Actualiza el estado de los botones según el scroll
 */
function updateMarcasButtons() {
  const wrapper = document.getElementById('marcasWrapper');
  const btnPrev = document.querySelector('.btn-scroll-marcas-prev');
  const btnNext = document.querySelector('.btn-scroll-marcas-next');
  
  if (!wrapper || !btnPrev || !btnNext) return;
  
  const atStart = wrapper.scrollLeft <= 5;
  const atEnd = wrapper.scrollLeft >= wrapper.scrollWidth - wrapper.clientWidth - 5;
  
  btnPrev.disabled = atStart;
  btnNext.disabled = atEnd;
}

/**
 * Configura los event listeners del carrusel
 */
function setupMarcasEventListeners() {
  const wrapper = document.getElementById('marcasWrapper');
  const btnPrev = document.querySelector('.btn-scroll-marcas-prev');
  const btnNext = document.querySelector('.btn-scroll-marcas-next');
  
  if (!wrapper) return;
  
  // Escuchadores de scroll
  wrapper.addEventListener('scroll', updateMarcasIndicadores);
  wrapper.addEventListener('scroll', updateMarcasButtons);
  
  // Botones de navegación
  if (btnPrev) {
    btnPrev.addEventListener('click', scrollMarcasLeft);
  }
  if (btnNext) {
    btnNext.addEventListener('click', scrollMarcasRight);
  }
  
  // Manejar cambio de orientación en móvil
  window.addEventListener('orientationchange', () => {
    setTimeout(() => {
      wrapper.scrollLeft = 0;
      updateMarcasIndicadores();
      updateMarcasButtons();
    }, 100);
  });
  
  // Manejar resize en móvil/tablet
  let resizeTimeout;
  window.addEventListener('resize', () => {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(() => {
      if (window.innerWidth <= 1024) {
        wrapper.scrollLeft = 0;
        updateMarcasIndicadores();
        updateMarcasButtons();
      }
    }, 300);
  });
  
  // Inicializar estado
  setTimeout(() => {
    updateMarcasButtons();
    updateMarcasIndicadores();
  }, 300);
}

/**
 * Inicializa el carrusel cuando el DOM está listo
 */
document.addEventListener('DOMContentLoaded', () => {
  cargarYRenderizarMarcas();
});
