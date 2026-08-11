// ===== PÁGINA DE TÉRMINOS Y CONDICIONES - CARGA DINÁMICA =====

/**
 * Inicializar la página de términos y condiciones
 */
document.addEventListener('DOMContentLoaded', async () => {
    await cargarYRenderizarTerminos();
    inicializarNavegacion();
    marcarEnlaceActivoPorURL(); // Detectar página actual y marcar como activo
    hacerScrollAlHash(); // Hacer scroll automático si hay un hash
    
    // Detectar cambios en el hash para actualizar el activo en tiempo real
    window.addEventListener('hashchange', () => {
        marcarEnlaceActivoPorURL();
        hacerScrollAlHash();
    });
});

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
 * Cargar y renderizar el contenido
 */
async function cargarYRenderizarTerminos() {
    const datos = await cargarTerminosCondiciones();
    
    if (!datos) {
        document.getElementById('terminosContenido').innerHTML = 
            '<div class="alert alert-danger">Error al cargar los términos y condiciones.</div>';
        return;
    }

    const contenidoHTML = generarHTMLTerminosCompleto(datos);
    document.getElementById('terminosContenido').innerHTML = contenidoHTML;
    
    console.log('Términos y condiciones cargados correctamente');
}

/**
 * Generar HTML completo de términos y condiciones
 */
function generarHTMLTerminosCompleto(datos) {
    if (!datos || !datos.secciones) {
        return '<p class="alert alert-danger">Error al cargar los términos y condiciones.</p>';
    }

    let html = '';

    // Iterar sobre cada sección principal
    datos.secciones.forEach((seccion, index) => {
        html += `<div id="${seccion.id}" class="terminos-seccion">`;
        html += `<h2 class="terminos-seccion-titulo">${seccion.titulo}</h2>`;

        // Si tiene contenido directo (como Aviso Legal) - puede ser string o array
        if (seccion.contenido) {
            if (typeof seccion.contenido === 'string') {
                // Si es string, mostrar como un párrafo
                html += `<p>${seccion.contenido}</p>`;
            } else if (Array.isArray(seccion.contenido) && seccion.contenido.length > 0) {
                // Si es array, iterar sobre cada párrafo
                seccion.contenido.forEach(parrafo => {
                    html += `<p>${parrafo}</p>`;
                });
            }
        }

        // Si tiene items con miniTitulo (nueva estructura)
        if (seccion.items && seccion.items.length > 0) {
            seccion.items.forEach(item => {
                html += `<div class="terminos-subseccion">`;
                html += `<h3 class="terminos-subseccion-titulo">${item.miniTitulo}</h3>`;
                html += `<p>${item.contenido}</p>`;
                html += `</div>`;
            });
        }

        // Si tiene subsecciones (estructura antigua)
        if (seccion.subsecciones && seccion.subsecciones.length > 0) {
            seccion.subsecciones.forEach(subseccion => {
                html += `<div class="terminos-subseccion">`;
                html += `<h3 class="terminos-subseccion-titulo">${subseccion.subtitulo}</h3>`;
                
                if (subseccion.contenido && subseccion.contenido.length > 0) {
                    subseccion.contenido.forEach(parrafo => {
                        html += `<p>${parrafo}</p>`;
                    });
                }
                
                html += `</div>`;
            });
        }

        html += `</div>`;
    });

    return html;
}

/**
 * Inicializar navegación y scroll spy
 */
function inicializarNavegacion() {
    const links = document.querySelectorAll('.terminos-nav-link');
    let isUserClicking = false;
    
    links.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            isUserClicking = true;
            
            // Remover clase active de todos los links
            links.forEach(l => l.classList.remove('active'));
            
            // Añadir clase active al link clickeado
            link.classList.add('active');
            
            // Scroll suave al elemento
            const target = document.querySelector(link.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            
            // Permitir scroll spy nuevamente después del scroll
            setTimeout(() => {
                isUserClicking = false;
            }, 1000);
        });
    });

    // Implementar scroll spy automático (solo si no está en modo click)
    window.addEventListener('scroll', () => {
        if (isUserClicking) return; // No hacer nada si el usuario acaba de hacer click
        
        let currentSection = '';
        
        const secciones = document.querySelectorAll('.terminos-seccion');
        secciones.forEach(seccion => {
            const sectionTop = seccion.offsetTop;
            
            if (window.pageYOffset >= sectionTop - 200) {
                currentSection = seccion.getAttribute('id');
            }
        });

        // Actualizar el nav link activo solo si hay una sección actual
        if (currentSection) {
            links.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === `#${currentSection}`) {
                    link.classList.add('active');
                }
            });
        }
    });

    // Marcar el primer elemento como activo
    if (links.length > 0) {
        links[0].classList.add('active');
    }
}

/**
 * Marcar el enlace activo basándose en la URL actual y el anchor/hash
 * Funciona tanto con páginas separadas como con anchors
 */
function marcarEnlaceActivoPorURL() {
    const links = document.querySelectorAll('.terminos-nav-link');
    
    // Obtener el hash actual de la URL (ej: #aviso-legal)
    const currentHash = window.location.hash.toLowerCase();
    
    let foundMatch = false;
    
    links.forEach(link => {
        const href = link.getAttribute('href');
        const anchorId = href.replace('#', '').toLowerCase();
        
        // Remover clase active
        link.classList.remove('active');
        
        // Si hay un hash en la URL, usarlo para determinar el activo
        if (currentHash) {
            // Comparar el hash actual con el ID del anchor
            const hashWithoutSymbol = currentHash.replace('#', '');
            if (hashWithoutSymbol === anchorId) {
                link.classList.add('active');
                foundMatch = true;
            }
        }
    });
    
    // Si no hay hash en la URL, marcar el primer elemento como activo
    if (!foundMatch && links.length > 0) {
        links[0].classList.add('active');
    }
}

/**
 * Hacer scroll automático a la sección si hay un hash en la URL
 */
function hacerScrollAlHash() {
    const currentHash = window.location.hash;
    
    if (currentHash) {
        const targetElement = document.querySelector(currentHash);
        
        if (targetElement) {
            // Pequeño delay para asegurar que el DOM esté completamente renderizado
            setTimeout(() => {
                targetElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 100);
        }
    }
}
