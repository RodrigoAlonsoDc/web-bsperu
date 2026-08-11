// ===== TÉRMINOS Y CONDICIONES - CARGA DINÁMICA DESDE JSON =====

/**
 * Cargar términos y condiciones desde JSON
 */
async function cargarTerminosCondiciones() {
  try {
    const response = await fetch('/assets/Data/terminos-condiciones.json');
    if (!response.ok) {
      throw new Error(`Error al cargar: ${response.status}`);
    }
    const datos = await response.json();
    return datos;
  } catch (error) {
    console.error('❌ Error cargando términos y condiciones:', error);
    return null;
  }
}

/**
 * Generar HTML del contenido de términos y condiciones
 */
function generarHTMLTerminos(datos) {
  if (!datos || !datos.secciones) {
    return '<p class="alert alert-danger">Error al cargar los términos y condiciones.</p>';
  }

  let html = '<div class="terms-content">';
  
  // Encabezado
  html += `<h6 class="fw-bold text-primary" style="font-size: 1.2rem; margin-bottom: 1rem;">
    DOCUMENTOS LEGALES - ${datos.empresa.nombre}
  </h6>`;

  // Iterar sobre cada sección
  datos.secciones.forEach(seccion => {
    html += `<h6 class="fw-bold mt-4 mb-3" style="color: #005587; font-size: 1.1rem;">${seccion.titulo}</h6>`;

    // Si tiene contenido directo (puede ser string o array)
    if (seccion.contenido) {
      if (typeof seccion.contenido === 'string') {
        // Si es un string, renderizarlo directamente como párrafos
        html += `<p>${seccion.contenido}</p>`;
      } else if (Array.isArray(seccion.contenido)) {
        // Si es un array, iterar sobre cada párrafo
        seccion.contenido.forEach(parrafo => {
          html += `<p>${parrafo}</p>`;
        });
      }
    }

    // Si tiene subsecciones
    if (seccion.subsecciones && seccion.subsecciones.length > 0) {
      seccion.subsecciones.forEach(sub => {
        html += `<h6 style="font-size: 1rem; color: #003d5c; margin-top: 1.2rem; margin-bottom: 0.8rem; font-weight: 600;">
          <strong>${sub.subtitulo}</strong>
        </h6>`;
        
        if (sub.contenido && sub.contenido.length > 0) {
          sub.contenido.forEach(parrafo => {
            html += `<p>${parrafo}</p>`;
          });
        }
      });
    }
  });

  // Pie de página con contacto
  html += `<div class="alert alert-info mt-4">
    <strong>Contacto para dudas sobre privacidad:</strong><br>
    Email: <a href="mailto:${datos.empresa.email}">${datos.empresa.email}</a><br>
    Teléfono: ${datos.empresa.telefono}
  </div>`;

  html += '</div>';
  return html;
}

/**
 * Crear el modal de términos y condiciones dinámicamente
 */
async function crearModalTerminos() {
  // Verificar si ya existe
  if (document.getElementById('terminosCondicionesModal')) {
    return;
  }

  // Cargar datos
  const datos = await cargarTerminosCondiciones();
  const contenidoHTML = generarHTMLTerminos(datos);

  // Crear modal compatible con el sistema personalizado de loyout.js
  const modalHTML = `
    <div class="layout-cart-modal" id="terminosCondicionesModal" aria-hidden="true">
      <div class="layout-cart-backdrop" data-close="terminosCondicionesModal"></div>
      <div class="layout-checkout-panel" role="dialog" aria-modal="true" aria-labelledby="terminosCondicionesTitle">
        <div class="layout-cart-header">
          <h5 class="fw-bold mb-0" id="terminosCondicionesTitle">Términos y Condiciones</h5>
          <button type="button" class="btn-close" data-close="terminosCondicionesModal" aria-label="Cerrar"></button>
        </div>
        <div class="layout-cart-body" style="max-height: 70vh; overflow-y: auto; padding: 20px;">
          ${contenidoHTML}
        </div>
        <div class="layout-cart-footer d-flex justify-content-end">
          <button type="button" class="btn btn-primary" data-close="terminosCondicionesModal">Entendido</button>
        </div>
      </div>
    </div>
  `;

  // Insertar en el DOM
  document.body.insertAdjacentHTML('beforeend', modalHTML);
  
  // Configurar eventos para cerrar el modal
  setTimeout(() => {
    const modal = document.getElementById('terminosCondicionesModal');
    if (!modal) return;

    // Cerrar con botones data-close
    modal.querySelectorAll('[data-close="terminosCondicionesModal"]').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        closeLayoutModal('terminosCondicionesModal');
      });
    });
    
    // Cerrar con backdrop (click fuera del modal)
    const backdrop = modal.querySelector('.layout-cart-backdrop');
    if (backdrop) {
      backdrop.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        closeLayoutModal('terminosCondicionesModal');
      });
    }
  }, 100);
   
}

// Ejecutar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
  crearModalTerminos();
});
