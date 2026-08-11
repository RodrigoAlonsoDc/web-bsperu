# 🛡️ SOLUCIONES DE SEGURIDAD XSS - BS PERÚ

**Propósito:** Código seguro para reemplazar las vulnerabilidades XSS encontradas.

---

## 📋 Índice

1. [Función `sanitizeHTML()`](#función-sanitizehtml)
2. [Solución para terminos-condiciones.js](#solución-para-terminos-condicionesjs)
3. [Solución para carrusel-marcas.js](#solución-para-carrusel-marcasjs)
4. [Solución para ubicacion.js](#solución-para-ubicacionjs)
5. [Solución para contacto.js](#solución-para-contactojs)

---

## Función `sanitizeHTML()`

Esta función **SEGURA** HTML antes de insertarlo en el DOM. La puedes agregar a un archivo nuevo o a un archivo existente.

### Opción A: Crear archivo nuevo `sanitize.js`

**Crear:** `assets/js/utils/sanitize.js`

```javascript
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
```

### Opción B: Agregar a un archivo existente

Si prefieres agregar la función a un archivo ya existente (ej: `assets/js/index/index.js`), copia la función `sanitizeHTML()` al principio del archivo.

---

### Uso en tu código

```javascript
// ✅ SEGURO
const html = '<h1>Hola</h1><script>alert("XSS")</script>';
const sanitized = sanitizeHTML(html);
console.log(sanitized); // Solo muestra '<h1>Hola</h1>'

// ✅ SEGURO - Insertar en DOM
insertSafeHTML(document.getElementById('contenedor'), sanitized);

// ✅ SEGURO - Crear elementos
const elemento = createSafeElement('div', 'Mi texto seguro', {
  class: 'alert',
  id: 'mi-alerta'
});
document.body.appendChild(elemento);
```

---

## Solución para `terminos-condiciones.js`

**Archivo actual:** `assets/js/custom/terminos-condiciones.js`

### Antes (Vulnerable):
```javascript
function generarHTMLTerminos(datos) {
  // ...
  html += `<h6>${seccion.titulo}</h6>`; // ❌ XSS RISK
  // ...
}

async function crearModalTerminos() {
  // ...
  document.body.insertAdjacentHTML('beforeend', modalHTML); // ❌ INSEGURO
}
```

### Después (Seguro):
```javascript
function generarHTMLTerminos(datos) {
  if (!datos || !datos.secciones) {
    return '<p class="alert alert-danger">Error al cargar los términos y condiciones.</p>';
  }

  // ✅ Usar elementos seguros
  const contenedor = document.createElement('div');
  contenedor.className = 'terms-content';
  
  // Encabezado
  const encabezado = createSafeElement('h6', `DOCUMENTOS LEGALES - ${datos.empresa.nombre}`, {
    class: 'fw-bold text-primary'
  });
  encabezado.style.fontSize = '1.2rem';
  encabezado.style.marginBottom = '1rem';
  contenedor.appendChild(encabezado);

  // Iterar sobre cada sección
  datos.secciones.forEach(seccion => {
    const titulo = createSafeElement('h6', seccion.titulo, {
      class: 'fw-bold mt-4 mb-3'
    });
    titulo.style.color = '#005587';
    titulo.style.fontSize = '1.1rem';
    contenedor.appendChild(titulo);

    // Contenido
    if (seccion.contenido) {
      if (typeof seccion.contenido === 'string') {
        const parrafo = createSafeElement('p', seccion.contenido);
        contenedor.appendChild(parrafo);
      } else if (Array.isArray(seccion.contenido)) {
        seccion.contenido.forEach(item => {
          const parrafo = createSafeElement('p', item);
          contenedor.appendChild(parrafo);
        });
      }
    }
  });

  return contenedor.innerHTML;
}

async function crearModalTerminos() {
  // ... código existente ...
  
  // ✅ SEGURO - Usar insertSafeHTML en lugar de insertAdjacentHTML
  const modalHTML = `...tu modal HTML...`;
  const tempDiv = document.createElement('div');
  insertSafeHTML(tempDiv, modalHTML);
  document.body.appendChild(tempDiv);
}
```

---

## Solución para `carrusel-marcas.js`

**Archivo actual:** `assets/js/carrusel-marcas.js`

### Antes (Vulnerable):
```javascript
// ❌ INSEGURO - innerHTML con datos de JSON
wrapper.innerHTML = marcasData.map((marca, index) => {
  return `<img src="${marca.logo}" alt="${marca.nombre}" ...>`;
}).join('');
```

### Después (Seguro):
```javascript
// ✅ SEGURO - Crear elementos dinámicamente
wrapper.innerHTML = ''; // Limpiar

marcasData.forEach((marca, index) => {
  const img = createSafeElement('img', '', {
    src: marca.logo,
    alt: marca.nombre,
    class: 'marca-circulo-img'
  });
  
  // Validar URL de imagen
  if (isValidImageUrl(marca.logo)) {
    wrapper.appendChild(img);
  }
});

/**
 * Validar que la URL de imagen es segura
 */
function isValidImageUrl(url) {
  if (!url || typeof url !== 'string') return false;
  
  try {
    const urlObj = new URL(url, window.location.origin);
    // Permitir imágenes locales (/assets/...) y HTTPS externas
    return urlObj.origin === window.location.origin || 
           urlObj.protocol === 'https:';
  } catch (e) {
    return false;
  }
}
```

---

## Solución para `ubicacion.js`

**Archivo actual:** `assets/js/ubicacion/ubicacion.js`

### Antes (Vulnerable):
```javascript
// ❌ INSEGURO
div.innerHTML = `
  <div class="sucursal-card" ...>
    <h5>${sucursal.nombre}</h5>
    <p>${sucursal.direccion}</p>
    ...
  </div>
`;
```

### Después (Seguro):
```javascript
// ✅ SEGURO - Crear elementos
const div = document.createElement('div');
div.className = 'swiper-slide';

const card = createSafeElement('div', '', { class: 'sucursal-card' });

const nombre = createSafeElement('h5', sucursal.nombre);
const direccion = createSafeElement('p', sucursal.direccion);
const telefono = createSafeElement('p', sucursal.telefono);
const horario = createSafeElement('p', sucursal.horario);

card.appendChild(nombre);
card.appendChild(direccion);
card.appendChild(telefono);
card.appendChild(horario);

div.appendChild(card);

// Para tooltips:
const punto = document.createElement('div');
const tooltip = createSafeElement('div', sucursal.nombre, {
  class: 'tooltip-ubicacion'
});
punto.appendChild(tooltip);
```

---

## Solución para `contacto.js`

**Archivo actual:** `assets/js/contacto/contacto.js`

### Antes (Vulnerable):
```javascript
// ⚠️ Bajo riesgo pero mala práctica
formStatus.innerHTML = '<div class="alert alert-success mt-2">¡Se abrió WhatsApp!</div>';
```

### Después (Seguro):
```javascript
// ✅ SEGURO
const successDiv = createSafeElement('div', '¡Se abrió WhatsApp!', {
  class: 'alert alert-success mt-2'
});
formStatus.innerHTML = '';
formStatus.appendChild(successDiv);

// O simplemente:
formStatus.textContent = '¡Se abrió WhatsApp!';
formStatus.className = 'alert alert-success mt-2';
```

---

## 📋 Paso a paso para implementar

### Paso 1: Crear archivo de utilidades
1. Crear carpeta: `assets/js/utils/`
2. Crear archivo: `assets/js/utils/sanitize.js`
3. Copiar la función `sanitizeHTML()` en ese archivo

### Paso 2: Incluir en todas las páginas
En **ANTES del cierre de `</body>`**, agregar:

```html
<!-- Utilities -->
<script src="/assets/js/utils/sanitize.js"></script>

<!-- Otros scripts -->
<script src="/assets/js/index/index.js"></script>
```

### Paso 3: Actualizar archivos vulnerables
1. Copiar las soluciones de arriba
2. Reemplazar en cada archivo JS
3. Probar en DevTools (F12 → Consola) que no hay errores

### Paso 4: Validar
- ✅ No debe haber errores en consola (rojo)
- ✅ Los sitios web deben seguir funcionando igual
- ✅ Los formularios deben funcionar
- ✅ Los carruseles deben funcionar

---

## 🔍 Testing

Después de implementar, prueba con estos payloads en DevTools:

```javascript
// En la consola:
console.log(sanitizeHTML('<img src=x onerror="alert(1)">'));
// Debe mostrar: '<img src="x">' (sin onerror)

console.log(sanitizeHTML('<script>alert("XSS")</script><h1>Hola</h1>'));
// Debe mostrar: '<h1>Hola</h1>' (sin script)
```

---

## ⚠️ Notas importantes

1. **`textContent` es siempre seguro** - Úsalo cuando solo necesites texto
2. **`innerHTML` con datos no confiables = XSS** - Evítalo siempre
3. **Los `data-*` attributes son seguros** - Los puedes usar con confianza
4. **Event listeners con `addEventListener()` son seguros** - Mejor que `onclick="..."`

---

## 📚 Recursos

- https://owasp.org/www-community/attacks/xss/
- https://developer.mozilla.org/es/docs/Glossary/Cross-site_scripting
- https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html

---

**Generado:** 12 de mayo de 2026  
**Versión:** 1.0  
**Compatibilidad:** Todos los navegadores modernos
