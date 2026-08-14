/**
 * Sanitiza HTML para prevenir inyecciones XSS
 * Elimina scripts y eventos inline peligrosos
 * 
 * @param {string} html - HTML a sanitizar
 * @returns {string} HTML sanitizado
 */
function sanitizeHTML(html) {
  if (!html || typeof html !== 'string') {
    return '';
  }

  // Crear un contenedor temporal
  const temp = document.createElement('div');
  temp.textContent = html;
  
  // Si es HTML puro, usar DOMParser
  try {
    const parser = new DOMParser();
    const doc = parser.parseFromString(html, 'text/html');
    
    // Eliminar todos los scripts
    const scripts = doc.querySelectorAll('script');
    scripts.forEach(script => script.remove());
    
    // Eliminar atributos 'on*' (onclick, onload, etc)
    const allElements = doc.querySelectorAll('*');
    allElements.forEach(el => {
      // Obtener todos los atributos
      const attrs = Array.from(el.attributes || []);
      attrs.forEach(attr => {
        // Eliminar atributos de evento
        if (attr.name.toLowerCase().startsWith('on')) {
          el.removeAttribute(attr.name);
        }
      });
    });
    
    return doc.body.innerHTML;
  } catch (e) {
    console.warn('Error al sanitizar HTML:', e);
    return temp.textContent;
  }
}

/**
 * Inserta HTML sanitizado en un elemento
 * 
 * @param {HTMLElement} element - Elemento donde insertar
 * @param {string} html - HTML a insertar
 */
function insertSafeHTML(element, html) {
  if (!element) return;
  const sanitized = sanitizeHTML(html);
  element.innerHTML = sanitized;
}

/**
 * Crea un elemento HTML de forma segura
 * 
 * @param {string} tag - Etiqueta HTML (ej: 'div', 'span')
 * @param {string} text - Texto a insertar (será escapado)
 * @param {Object} attributes - Atributos (clase, id, etc)
 * @returns {HTMLElement} Elemento creado
 */
function createSafeElement(tag, text = '', attributes = {}) {
  const element = document.createElement(tag);
  
  if (text) {
    element.textContent = text; // textContent es seguro contra XSS
  }
  
  // Agregar atributos seguros
  Object.entries(attributes).forEach(([key, value]) => {
    if (key.toLowerCase().startsWith('on')) {
      console.warn(`Atributo "${key}" rechazado por seguridad`);
      return;
    }
    element.setAttribute(key, value);
  });
  
  return element;
}
