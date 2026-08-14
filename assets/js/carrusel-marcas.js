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
  
  wrapper.innerHTML = '';
  marcasData.forEach((marca, index) => {
    const a = document.createElement('a');
    a.className = `marca-item estilo-${marca.estilo}`;
    if (marca.link && (marca.link.startsWith('/') || marca.link.startsWith('http'))) {
      a.href = marca.link;
    }
    a.target = '_blank';
    a.setAttribute('data-index', index);
    if (marca.gradient) {
      a.style.background = marca.gradient;
    }

    const divLogo = document.createElement('div');
    divLogo.className = 'marca-item-logo';
    if (marca.logo) {
      const img = document.createElement('img');
      img.src = marca.logo;
      img.alt = marca.nombre || '';
      img.addEventListener('error', function() { this.style.display = 'none'; });
      divLogo.appendChild(img);
    }

    const divText = document.createElement('div');
    divText.className = 'marca-item-text';
    const strong = document.createElement('strong');
    strong.textContent = marca.nombre || '';
    divText.appendChild(strong);

    if (marca.subtitulo) {
      const small = document.createElement('small');
      small.textContent = marca.subtitulo;
      divText.appendChild(small);
    }

    a.appendChild(divLogo);
    a.appendChild(divText);
    wrapper.appendChild(a);
  });
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
