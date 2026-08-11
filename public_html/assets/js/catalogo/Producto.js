// =============================
// CONFIG
// =============================
(function() {
  'use strict';

// Helpers para acceso DOM (para evitar conflicto con jQuery global $)
const $ = (sel, ctx = document) => ctx.querySelector(sel);
const $$ = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));

if (!window.WHATSAPP_NUMBER) {
  window.WHATSAPP_NUMBER = "51914776669";
}
if (!window.productos) {
  window.productos = [];
}
if (!window.carrito) {
  window.carrito = JSON.parse(localStorage.getItem("carrito") || "[]");
}
let productoActual = null;
let indiceImagen = 0;

// Helpers cortos (renombrados para evitar conflicto con jQuery)
const qs  = (sel, ctx = document) => ctx.querySelector(sel);
const qsa = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));
const q1  = (sel, ctx = document) => ctx.querySelector(sel);
const qa  = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));

/* Notificación bonita */
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

function onClick(id, fn) {
  const el = qs(id);
  if (el) el.addEventListener("click", fn);
}

function syncCarrito() {
    localStorage.setItem("carrito", JSON.stringify(window.carrito));
}

function setupProductoCartUI() {
    if (!document.getElementById('carritoModal')) {
        document.body.insertAdjacentHTML('beforeend', `
            <div class="modal fade" id="carritoModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-sm modal-dialog-scrollable modal-dialog-end">
                    <div class="modal-content rounded-4">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold">Tu carrito</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body p-3" id="carritoBody"></div>
                        <div class="modal-footer d-flex flex-column gap-2">
                            <div class="d-flex gap-2 w-100">
                                <button id="btnCancelarCompra" class="btn btn-outline-danger w-100">Cancelar compra</button>
                                <button id="btnFinalizarCompra" class="btn btn-success w-100">Finalizar compra</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `);
    }

    if (!document.getElementById('checkoutModal')) {
        document.body.insertAdjacentHTML('beforeend', `
            <div class="modal fade" id="checkoutModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content rounded-4">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold">Finalizar compra</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <form id="checkoutForm" class="row g-3" novalidate>
                                <div class="col-md-6"><label class="form-label">Nombre <span class="text-danger">*</span></label><input class="form-control" name="nombre" required></div>
                                <div class="col-md-6"><label class="form-label">Apellido <span class="text-danger">*</span></label><input class="form-control" name="apellido" required></div>
                                <div class="col-md-6">
                                  <label class="form-label">Tipo de documento <span class="text-danger">*</span></label>
                                  <div class="input-group">
                                    <select class="form-select" name="tipodoc" id="tipodoc" style="max-width:90px;flex:0 0 90px" required>
                                      <option value="">Selecciona</option>
                                      <option value="DNI">DNI (8 dígitos)</option>
                                      <option value="RUC">RUC (10 dígitos)</option>
                                    </select>
                                    <input class="form-control" name="numerodoc" id="numerodoc" type="text" inputmode="numeric" placeholder="Selecciona el tipo primero" readonly data-required="true">
                                  </div>
                                </div>
                                <div class="col-md-6"><label class="form-label">Teléfono <span class="text-danger">*</span></label><input class="form-control" name="telefono" type="text" inputmode="numeric" pattern="[0-9]{9}" maxlength="9" placeholder="Ej: 987654321" title="El teléfono debe contener exactamente 9 dígitos" required></div>
                                <div class="col-12"><label class="form-label">Dirección <span class="text-danger">*</span></label><input class="form-control" name="direccion" required></div>
                                <div class="col-12"><label class="form-label">Correo electrónico <span class="text-danger">*</span></label><input class="form-control" type="email" name="email" required></div>
                                <div class="col-md-6">
                                    <label class="form-label">Método de entrega <span class="text-danger">*</span></label>
                                    <select class="form-select" name="metodoRecojo" id="metodoRecojo" required>
                                        <option value="">-- Selecciona un método --</option>
                                        <option value="delivery">Delivery</option>
                                        <option value="recojo">Recojo en tienda</option>
                                    </select>
                                </div>
                                <div class="col-md-6" id="tiendaUbicacionWrapper">
                                    <label class="form-label">Ubicación de las tiendas <span class="text-danger">*</span></label>
                                    <select class="form-select" name="tiendaUbicacion" id="tiendaUbicacion" required>
                                        <option value="">-- Selecciona una tienda --</option>
                                        <option value="LIMA , CHORRILLOS">Lima, Chorrillos</option>
                                        <option value="LIMA , SAN BORJA">Lima, San Borja</option>
                                        <option value="CALLAO , BELLAVISTA">Callao, Bellavista</option>
                                        <option value="TRUJILLO , TRUJILLO">Trujillo</option>
                                        <option value="PIURA , SULLANA">Piura, Sullana</option>
                                        <option value="PIURA , PIURA">Piura</option>
                                        <option value="CHICLAYO , CHICLAYO">Chiclayo</option>
                                        <option value="TACNA , TACNA">Tacna</option>
                                        <option value="AREQUIPA , AREQUIPA">Arequipa</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="autorizarFactura" name="autorizarFactura" required>
                                        <label class="form-check-label" for="autorizarFactura">
                                            Autorizo el uso de mis datos para emisión de factura <span class="text-danger">*</span>
                                            <a href="#" data-bs-toggle="modal" data-bs-target="#terminosCondicionesModal" onclick="event.preventDefault()" style="color: #005587; text-decoration: underline; margin-left: 5px; font-size: 0.9em;">Ver más...</a>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Método de pago <span class="text-danger">*</span></label>
                                    <select class="form-select" name="metodoPago" required>
                                        <option value="">-- Selecciona un método --</option>
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

function renderProductoCarrito() {
    window.carrito = JSON.parse(localStorage.getItem('carrito') || '[]');

    const body = $('#carritoBody');
    const badge = $('#cartBadge');

    if (body) {
        if (window.carrito.length === 0) {
            body.innerHTML = '<p class="text-center text-secondary mb-0">Tu carrito está vacío.</p>';
        } else {
            body.innerHTML = window.carrito.map((item, index) => `
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

    if (badge) {
        const total = window.carrito.reduce((sum, item) => sum + Number(item.cantidad || 0), 0);
        badge.textContent = total;
        badge.style.display = total > 0 ? 'inline-block' : 'none';
    }
}

function cambiarCantidadProductoCarrito(index, delta) {
    if (!window.carrito[index]) return;
    window.carrito[index].cantidad += delta;

    if (window.carrito[index].cantidad <= 0) {
        window.carrito.splice(index, 1);
    }

    syncCarrito();
    renderProductoCarrito();
}

function eliminarItemProductoCarrito(index) {
    if (!window.carrito[index]) return;
    window.carrito.splice(index, 1);
    syncCarrito();
    renderProductoCarrito();
}

function finalizarCompraProducto(formElement) {
    // VALIDACIÓN: Verificar checkbox ANTES de todo
    const checkboxAutorizacion = document.getElementById('autorizarFactura');
    
    if (!checkboxAutorizacion) {
        mostrarNotificacion('❌ ERROR: Campo de autorización de factura no encontrado.', 'error');
        return false;
    }

    if (!checkboxAutorizacion.checked) {
        mostrarNotificacion('❌ DEBES MARCAR LA CASILLA DE AUTORIZACIÓN para continuar. ES OBLIGATORIO.', 'error');
        checkboxAutorizacion.focus();
        checkboxAutorizacion.classList.add('is-invalid', 'border-danger');
        checkboxAutorizacion.parentElement.parentElement.classList.add('border', 'border-danger', 'p-2', 'rounded');
        return false;
    }

    // Validar que el formulario sea válido
    if (!formElement.checkValidity()) {
        formElement.classList.add('was-validated');
        mostrarNotificacion('Por favor completa todos los campos correctamente.', 'error');
        return false;
    }

    const formData = new FormData(formElement);
    const data = Object.fromEntries(formData.entries());

    // Validar carrito no vacío
    if (!window.carrito.length) {
        mostrarNotificacion('Tu carrito está vacío. Agrega productos antes de finalizar la compra.', 'error');
        return false;
    }

    // Validar tipo y número de documento
    const tipoDoc = (data.tipodoc || '').trim();
    const numDoc = (data.numerodoc || '').trim();
    if (!tipoDoc) {
        mostrarNotificacion('Selecciona el tipo de documento (DNI o RUC).', 'error');
        return false;
    }
    if (tipoDoc === 'DNI' && !/^\d{8}$/.test(numDoc)) {
        mostrarNotificacion('El DNI debe contener exactamente 8 dígitos numéricos.', 'error');
        return false;
    }
    if (tipoDoc === 'RUC' && !/^\d{10}$/.test(numDoc)) {
        mostrarNotificacion('El RUC debe contener exactamente 10 dígitos numéricos.', 'error');
        return false;
    }

    // Validación adicional de Teléfono
    if (!data.telefono || !/^\d{9}$/.test(data.telefono)) {
        mostrarNotificacion('El teléfono debe contener exactamente 9 dígitos.', 'error');
        return false;
    }

    if (data.metodoRecojo === 'recojo' && !data.tiendaUbicacion) {
        mostrarNotificacion('Selecciona la tienda para el recojo.', 'error');
        return false;
    }

    // VERIFICACIÓN FINAL - Otra validación del checkbox antes de enviar
    if (!checkboxAutorizacion.checked) {
        mostrarNotificacion('❌ DEBES AUTORIZAR EL USO DE TUS DATOS. NO PUEDES CONTINUAR SIN ESTO.', 'error');
        return false;
    }

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

    if (!window.carrito.length) {
        mensaje += '- (carrito vacío)\n';
    } else {
        window.carrito.forEach((item, index) => {
            mensaje += `${index + 1}. ${item.nombre} (x${item.cantidad})\n`;
        });
    }

    mensaje += '----------------------\n';
    mensaje += 'Gracias por su compra';

    const url = `https://api.whatsapp.com/send?phone=${window.WHATSAPP_NUMBER}&text=${encodeURIComponent(mensaje)}`;
    const popupWindow = window.open(url, '_blank');
    if (!popupWindow) window.location.href = url;

    window.carrito = [];
    syncCarrito();
    renderProductoCarrito();

    const checkoutModal = bootstrap.Modal.getInstance($('#checkoutModal'));
    if (checkoutModal) checkoutModal.hide();

    formElement.reset();
    formElement.classList.remove('was-validated');
    mostrarNotificacion('Pedido enviado correctamente.', 'success');
    return true;
}

function setupProductoCartEvents() {
    document.addEventListener('click', (event) => {
        if (event.target.closest('#btnCarritoNav')) {
            const carritoModalElement = $('#carritoModal');
            if (!carritoModalElement) return;

            const modalInstance = bootstrap.Modal.getInstance(carritoModalElement) || new bootstrap.Modal(carritoModalElement);
            renderProductoCarrito();
            modalInstance.show();
            return;
        }

        const actionButton = event.target.closest('#carritoBody button[data-action]');
        if (actionButton) {
            const action = actionButton.dataset.action;
            const index = Number(actionButton.dataset.index);

            if (action === 'dec') cambiarCantidadProductoCarrito(index, -1);
            if (action === 'inc') cambiarCantidadProductoCarrito(index, 1);
            if (action === 'del') eliminarItemProductoCarrito(index);
            return;
        }

        if (event.target.closest('#btnCancelarCompra')) {
            window.carrito = [];
            syncCarrito();
            renderProductoCarrito();

            const carritoModal = bootstrap.Modal.getInstance($('#carritoModal'));
            if (carritoModal) carritoModal.hide();
            mostrarNotificacion('Compra cancelada. Carrito vaciado.', 'info');
            return;
        }

        if (event.target.closest('#btnFinalizarCompra')) {
            const carritoActual = JSON.parse(localStorage.getItem('carrito') || '[]');
            if (!carritoActual.length) {
                mostrarNotificacion('Tu carrito está vacío. Agrega productos antes de finalizar la compra.', 'warning');
                return;
            }
            const carritoModal = bootstrap.Modal.getInstance($('#carritoModal'));
            if (carritoModal) carritoModal.hide();

            const checkoutModalElement = $('#checkoutModal');
            if (checkoutModalElement) {
                const checkoutModal = bootstrap.Modal.getInstance(checkoutModalElement) || new bootstrap.Modal(checkoutModalElement);
                checkoutModal.show();
            }
        }
    });

    document.addEventListener('submit', (event) => {
        if (event.target.id !== 'checkoutForm') return;

        event.preventDefault();
        event.stopPropagation();

        // Validación obligatoria del checkbox de términos y condiciones
        const checkboxTerminos = document.getElementById('aceptarTerminosCondiciones');
        if (!checkboxTerminos || !checkboxTerminos.checked) {
            mostrarNotificacion('❌ DEBES ACEPTAR LOS TÉRMINOS Y CONDICIONES para continuar. (Requerido por ley)', 'error');
            checkboxTerminos.focus();
            checkboxTerminos.classList.add('is-invalid');
            return false;
        }

        checkboxTerminos.classList.remove('is-invalid');
        finalizarCompraProducto(event.target);
    });

    // Validación y formateo de campos numéricos
    document.addEventListener('change', (event) => {
        if (event.target.id !== 'tipodoc') return;
        const tipo = event.target.value;
        const numInput = document.getElementById('numerodoc');
        if (!numInput) return;
        numInput.value = '';
        if (tipo === 'DNI') {
            numInput.maxLength = 8;
            numInput.minLength = 8;
            numInput.pattern = '[0-9]{8}';
            numInput.placeholder = 'Ej: 12345678';
            numInput.title = 'El DNI debe tener exactamente 8 dígitos numéricos';
            numInput.removeAttribute('readonly');
            numInput.setAttribute('required', '');
        } else if (tipo === 'RUC') {
            numInput.maxLength = 10;
            numInput.minLength = 10;
            numInput.pattern = '[0-9]{10}';
            numInput.placeholder = 'Ej: 1234567890';
            numInput.title = 'El RUC debe tener exactamente 10 dígitos numéricos';
            numInput.removeAttribute('readonly');
            numInput.setAttribute('required', '');
        } else {
            numInput.removeAttribute('pattern');
            numInput.maxLength = 11;
            numInput.minLength = 0;
            numInput.removeAttribute('minlength');
            numInput.placeholder = 'Selecciona el tipo primero';
            numInput.setAttribute('readonly', '');
            numInput.removeAttribute('required');
        }
    });

    document.addEventListener('input', (event) => {
        if (event.target.name !== 'numerodoc') return;
        const tipoSelect = document.getElementById('tipodoc');
        const tipo = tipoSelect ? tipoSelect.value : '';
        
        // Solo permite números
        let value = event.target.value.replace(/[^\d]/g, '');
        
        // Limita según el tipo
        if (tipo === 'DNI') {
            value = value.slice(0, 8);
        } else if (tipo === 'RUC') {
            value = value.slice(0, 10);
        }
        
        event.target.value = value;
    });

    const telefonoInput = $('#checkoutForm input[name="telefono"]');
    if (telefonoInput) {
        telefonoInput.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/[^\d]/g, '').slice(0, 9);
        });
    }

    document.addEventListener('change', (event) => {
        if (event.target.id !== 'metodoRecojo') return;

        const tiendaWrapper = $('#tiendaUbicacionWrapper');
        if (tiendaWrapper) {
            tiendaWrapper.style.display = event.target.value === 'recojo' ? '' : 'none';
        }
    });
}

window.initProductoNavbarFeatures = function initProductoNavbarFeatures() {
    setupProductoCartUI();
    setupProductoCartEvents();
    setupNavbarSearch();
    renderProductoCarrito();

    const tiendaWrapper = $('#tiendaUbicacionWrapper');
    if (tiendaWrapper) {
        tiendaWrapper.style.display = 'none';
    }
};

function safeDecodeURIComponent(value) {
    try {
        return decodeURIComponent(value);
    } catch {
        return value;
    }
}

function normalizeImagePath(path) {
    if (!path) return "";
    const cleaned = String(path).trim().replace(/\\/g, "/").replace(/^\/+/, "");
    const segments = cleaned
        .split("/")
        .map(segment => encodeURIComponent(safeDecodeURIComponent(segment)));
    return `/${segments.join("/")}`;
}

function getImageFallback(path) {
    if (!path) return "/assets/imgWeb/no-image.png";
    const cleaned = String(path).replace(/\\/g, "/");
    const fileName = cleaned.split("/").pop();
    if (!fileName) return "/assets/imgWeb/no-image.png";
    return normalizeImagePath(`/assets/img catalogo/copia/${fileName}`);
}

function setupNavbarSearch() {
    // Configurar búsqueda del navbar (desktop)
    setupSearchInput(
        "#layoutProductSearch",
        "#layoutProductSearchForm",
        "#layoutSearchSuggestions"
    );
    
    // Configurar búsqueda del drawer (mobile)
    setupSearchInput(
        "#drawerProductSearch",
        "#drawerSearchForm",
        "#drawerSearchSuggestions"
    );
}

function setupSearchInput(inputSelector, formSelector, suggestionsSelector) {
    const searchInput = document.querySelector(inputSelector);
    const suggestions = document.querySelector(suggestionsSelector);
    const searchForm = document.querySelector(formSelector);

    if (!searchInput || !suggestions) return;

    const renderSuggestions = () => {
        const q = String(searchInput.value || "").trim().toLowerCase();

        if (!q) {
            suggestions.style.display = "none";
            suggestions.innerHTML = "";
            return;
        }

        const matches = window.productos
            .filter(productoEstaDisponible)
            .filter(p =>
                String(p.nombre || "").toLowerCase().includes(q) ||
                String(p.sku || "").toLowerCase().includes(q)
            );

        suggestions.innerHTML = matches.slice(0, 6).map(p => `
            <a href="/views/producto.html?sku=${encodeURIComponent(p.sku)}"
               class="list-group-item list-group-item-action d-flex align-items-center search-suggestion"
               data-sku="${p.sku}"
               style="padding: 10px 12px; border-bottom: 1px solid #eee;">
                <img src="${p.imagenes?.[0] || ''}"
                     alt="${p.nombre || 'Producto'}"
                     style="width:40px;height:40px;object-fit:contain;"
                     class="me-2">
                <div style="flex: 1;">
                    <div class="small fw-semibold">${p.nombre || "Sin nombre"}</div>
                    <div class="small text-secondary">SKU: ${p.sku || "-"}</div>
                </div>
            </a>
        `).join("");

        suggestions.style.display = matches.length ? "block" : "none";
    };

    searchInput.addEventListener("input", renderSuggestions);

    if (searchForm) {
        searchForm.addEventListener("submit", (e) => {
            e.preventDefault();
            const q = String(searchInput.value || "").trim().toLowerCase();
            if (!q) return;

            const matches = window.productos
                .filter(productoEstaDisponible)
                .filter(p =>
                    String(p.nombre || "").toLowerCase().includes(q) ||
                    String(p.sku || "").toLowerCase().includes(q)
                );

            if (matches.length === 1) {
                window.location.href = `/views/producto.html?sku=${encodeURIComponent(matches[0].sku)}`;
                return;
            }

            window.location.href = `/views/catalogo.html?q=${encodeURIComponent(q)}`;
        });
    }

    suggestions.addEventListener("click", (e) => {
        const item = e.target.closest(".search-suggestion");
        if (!item) return;
        e.preventDefault();
        const sku = item.dataset.sku;
        if (!sku) return;
        window.location.href = `/views/producto.html?sku=${encodeURIComponent(sku)}`;
    });

    document.addEventListener("click", (e) => {
        if (!e.target.closest(inputSelector) && !e.target.closest(suggestionsSelector)) {
            suggestions.style.display = "none";
        }
    });
}

// =============================
// OBTENER SKU DESDE LA URL
// =============================
function getSkuFromUrl() {
    const params = new URLSearchParams(window.location.search);
    return params.get("sku");
}

// =============================
// CARGAR PRODUCTOS Y PINTAR
// =============================
function productoEstaDisponible(producto) {
    const valor = producto?.producto_disponible ?? producto?.productos_disponibles;

    if (typeof valor === 'boolean') return valor;
    if (typeof valor === 'string') return valor.trim().toLowerCase() === 'true';
    if (typeof valor === 'number') return valor === 1;

    return true;
}

function cargarProductos() {
    return fetch("/assets/Data/productos.json", { cache: "no-cache" })
        .then(r => {
            if (!r.ok) throw new Error("No se pudo cargar productos.json");
            return r.json();
        })
        .then(data => {
            window.productos = data.filter(productoEstaDisponible).map(p => {

                // convertir miniaturas (objeto) a array — la imagen principal es para catálogo,
                // en el detalle de producto solo se muestran miniatura1, miniatura2, miniatura3
                const miniaturas = p.miniaturas
                    ? Object.values(p.miniaturas).filter(img => img !== null && img !== undefined)
                    : [];
                const imagenes = [...miniaturas]
                    .filter(img => img !== null && img !== undefined && img !== '')
                    .map(normalizeImagePath);

                return {
                    sku: String(p.sku),
                    marca: p.marca || "",
                    nombre: p.nombre,
                    categoria: p.categoria,
                    descripcion: p.descripcion || "",
                    descripcion_larga: p.descripcion_larga || "",
                    precio: p.precio !== null ? Number(p.precio) : 0,
                    precio_oferta: p.precio_oferta || null,
                    peso: p.peso || 0,
                    oferta: Boolean(p.oferta),

                    // 🔑 CLAVE
                    imagenes: imagenes,
                    miniaturas: imagenes,

                    ficha_pdf: p.ficha_pdf || "",
                    hoja_seguridad_pdf: p.hoja_seguridad_pdf || "",
                    reseñas: Array.isArray(p.reseñas) ? p.reseñas : [],
                    similares: Array.isArray(p.similares) ? p.similares : []
                };
            });
        });
}


function encontrarProducto(sku) {
  const cleanSku = String(sku).trim();
  return window.productos.find(p => String(p.sku).trim() === cleanSku);
}


// =============================
// RENDER PRINCIPAL (CORREGIDO)
// =============================
function renderProducto() {
    if (!productoActual) {
        $(".producto-wrapper").innerHTML =
            `<div class="text-center py-5 text-muted">
                No se encontró el producto solicitado.
             </div>`;
        return;
    }

    // Título
    $("#tituloProducto").textContent = productoActual.nombre || "Producto sin nombre";

    // Descripción corta
    $("#descripcionCorta").textContent =
        productoActual.descripcion ||
        productoActual.descripcion_larga ||
        "Solución de alta calidad para tus proyectos de construcción.";

    const tagOferta = $("#tagOferta");
    if (tagOferta) {
        tagOferta.style.display = "none";
    }

    // Código + categorías
    const codigo = productoActual.sku || productoActual.codigo || "";
    const cat = productoActual.categoria || "";
    $("#codigoYCategorias").innerHTML =
        `<strong>CÓDIGO DEL PRODUCTO:</strong> ${codigo}<br>
         <strong>CATEGORÍAS:</strong> ${cat}`;

    // Rating
    const rating = Number(productoActual.rating || 5);
    const reviews = Number(productoActual.reseñas?.length || 0);

    let estrellas = "";
    for (let i = 1; i <= 5; i++) {
        estrellas += `<i class="bi ${i <= rating ? "bi-star-fill text-warning" : "bi-star text-warning"}"></i>`;
    }
    $("#ratingStars").innerHTML = estrellas;

    $("#ratingLabel").textContent = `(${reviews} valoración${reviews === 1 ? "" : "es"} de clientes)`;

    // Descripción siempre visible
    $("#paneDesc").innerHTML =
        productoActual.descripcion_larga ||
        `<ul>
            <li>Alta elasticidad y flexibilidad.</li>
            <li>Resistente a la humedad y a los cambios de temperatura.</li>
            <li>Ideal para obras nuevas y mantenimiento.</li>
         </ul>`;

    // Segunda pestaña dinámica: Hoja de seguridad o Ficha técnica
    const fichaPdf = String(productoActual.ficha_pdf || "").trim();
    const hojaPdf = String(productoActual.hoja_seguridad_pdf || "").trim();
    const hasHoja = Boolean(hojaPdf);
    const hasFicha = Boolean(fichaPdf);
    const hasSegundaPestana = hasHoja || hasFicha;
    const usarHoja = hasHoja;

    const fichaSection = $("#tabFicha")?.closest(".mt-4");
    const tabFichaBtn = $("#tabDesc");
    const tabHojaBtn = $("#tabInfo");
    const tabHojaItem = tabHojaBtn?.closest(".nav-item");
    const paneFicha = $("#paneDesc");
    const paneHoja = $("#paneInfo");

    if (fichaSection) {
        fichaSection.style.display = "";
    }

    if (tabFichaBtn) tabFichaBtn.textContent = "Descripción";

    if (paneHoja) {
        paneHoja.style.display = hasSegundaPestana ? "" : "none";
        if (hasSegundaPestana) {
            if (usarHoja) {
                paneHoja.innerHTML = `<p><strong>Hoja de seguridad:</strong> <a href="${hojaPdf}" target="_blank" rel="noopener">Descargar</a></p>`;
            } else {
                paneHoja.innerHTML = `<p><strong>Ficha PDF:</strong> <a href="${fichaPdf}" target="_blank" rel="noopener">Descargar</a></p>`;
            }
        } else {
            paneHoja.innerHTML = "";
        }
    }

    if (tabHojaItem) tabHojaItem.style.display = hasSegundaPestana ? "" : "none";
    if (tabHojaBtn) tabHojaBtn.textContent = usarHoja ? "Hoja de Seguridad" : "Ficha técnica";

    // Activar pestaña de descripción por defecto
    if (tabFichaBtn && tabHojaBtn && paneFicha && paneHoja) {
        const activarFicha = true;

        tabFichaBtn.classList.toggle("active", activarFicha);
        tabHojaBtn.classList.toggle("active", false);

        paneFicha.classList.toggle("show", activarFicha);
        paneFicha.classList.toggle("active", activarFicha);

        const activarHoja = false;
        paneHoja.classList.toggle("show", activarHoja);
        paneHoja.classList.toggle("active", activarHoja);
    }

    // Reseñas
    if (Array.isArray(productoActual.reseñas) && productoActual.reseñas.length > 0) {
        $("#contenedorResenas").innerHTML = productoActual.reseñas.map(r => `
            <div class="border-bottom pb-2 mb-2">
                <div class="fw-semibold">${r.autor || "Cliente"}</div>
                <div class="small text-muted">${r.fecha || ""}</div>
                <div>${"★".repeat(r.estrellas || 5)}</div>
                <p class="mb-0">${r.texto || ""}</p>
            </div>
        `).join("");
    }

    renderGaleria();
    renderSimilares();
}

// =============================
// GALERÍA
// =============================
function obtenerImagenesProducto() {
    if (Array.isArray(productoActual.imagenes) && productoActual.imagenes.length) {
        return productoActual.imagenes;
    }
    return ["/assets/imgWeb/no-image.png"];
}



function renderGaleria() {
    const imagenes = obtenerImagenesProducto();
    if (!imagenes.length) return;

    indiceImagen = 0;
    $("#imgPrincipal").src = imagenes[indiceImagen];
    $("#imgPrincipal").onerror = () => {
        const fallback = getImageFallback(imagenes[indiceImagen]);
        if ($("#imgPrincipal").src !== fallback) {
            $("#imgPrincipal").src = fallback;
        }
    };
    const thumbsRow = $("#thumbsRow");
    thumbsRow.innerHTML = imagenes.map((src, i) =>
        `<img src="${src}" data-fallback="${getImageFallback(src)}" data-index="${i}" class="${i === 0 ? "active" : ""}" alt="thumb">`
    ).join("");

    $$("#thumbsRow img").forEach(img => {
        img.addEventListener("error", () => {
            const fallback = img.getAttribute("data-fallback") || "/assets/imgWeb/no-image.png";
            if (img.src !== fallback) {
                img.src = fallback;
            }
        }, { once: true });
    });

    thumbsRow.addEventListener("click", (e) => {
        const img = e.target.closest("img[data-index]");
        if (!img) return;
        indiceImagen = Number(img.dataset.index);
        actualizarImagenPrincipal();
    });

    $("#btnPrevImg").addEventListener("click", () => {
        indiceImagen = (indiceImagen - 1 + imagenes.length) % imagenes.length;
        actualizarImagenPrincipal();
    });
    $("#btnNextImg").addEventListener("click", () => {
        indiceImagen = (indiceImagen + 1) % imagenes.length;
        actualizarImagenPrincipal();
    });
}

function actualizarImagenPrincipal() {
    const imagenes = obtenerImagenesProducto();
    if (!imagenes.length) return;
    $("#imgPrincipal").src = imagenes[indiceImagen];
    $("#imgPrincipal").onerror = () => {
        const fallback = getImageFallback(imagenes[indiceImagen]);
        if ($("#imgPrincipal").src !== fallback) {
            $("#imgPrincipal").src = fallback;
        }
    };
    $$("#thumbsRow img").forEach((t, i) => {
        t.classList.toggle("active", i === indiceImagen);
    });
}

// =============================
// SIMILARES (con random shuffle)
// =============================
function renderSimilares() {
    const cont = $("#similaresRow");
    if (!cont) return;

    const categoria = (productoActual.categoria || "").toLowerCase();

    // Filtrar similares
    let similares = window.productos.filter(p =>
        p.sku !== productoActual.sku &&
        (p.categoria || "").toLowerCase() === categoria
    );

    // 🔥 MEZCLAR ALEATORIAMENTE (shuffle)
    similares = similares
        .map(p => ({ p, sort: Math.random() })) // asigna número random
        .sort((a, b) => a.sort - b.sort)        // ordena por ese random
        .map(obj => obj.p);                     // limpia los objetos

    // tomar solo 3
    similares = similares.slice(0, 3);

    if (!similares.length) {
        cont.innerHTML = `<p class="text-muted small mb-0">No hay productos similares para mostrar.</p>`;
        return;
    }

    cont.innerHTML = similares.map(p => `
        <div class="col-12 col-md-4">
            <div class="similares-card h-100 p-2 d-flex flex-column">
                <div class="text-center mb-2">
                    <img src="${p.imagenes?.[0] || '/assets/imgWeb/no-image.png'}" alt="${p.nombre}" class="similares-img w-100">
                </div>
                <div class="px-1 flex-grow-1">
                    <div class="small text-uppercase text-secondary">${p.categoria || ""}</div>
                    <div class="fw-semibold small mb-1">${p.nombre}</div>
                </div>
                <div class="mt-2 d-flex gap-1 px-1 pb-1">
                    <button class="btn btn-sm btn-outline-primary w-50 btn-ver-similar" data-sku="${p.sku}">
                        Ver
                    </button>
                    <button class="btn btn-sm btn-primary w-50 btn-agregar-similar" data-sku="${p.sku}">
                        Añadir
                    </button>
                </div>
            </div>
        </div>
    `).join("");

    // eventos
    $$(".btn-ver-similar", cont).forEach(btn => {
        btn.addEventListener("click", () => {
            const sku = btn.dataset.sku;
            window.location.href = `/views/producto.html?sku=${encodeURIComponent(sku)}`;
        });
    });

    $$(".btn-agregar-similar", cont).forEach(btn => {
        btn.addEventListener("click", () => {
            const sku = btn.dataset.sku;
            const prod = window.productos.find(p => String(p.sku) === String(sku));
            if (prod) {
                let imgUrl = "";
                if (prod.imagenes && prod.imagenes.length > 0) {
                    imgUrl = prod.imagenes[0];
                } else if (prod.miniaturas && typeof prod.miniaturas === 'object') {
                    const miniaturas = Object.values(prod.miniaturas).filter(m => m && m.trim());
                    if (miniaturas.length > 0) {
                        imgUrl = miniaturas[0];
                    }
                }
                agregarAlCarrito({
                    ...prod,
                    img: imgUrl
                }, 1, true);
            }
        });
    });
}


// =============================
// CARRITO + BOTONES
// =============================
function agregarAlCarrito(prod, cantidad, mostrarAlerta) {
    const qty = Number(cantidad || 1);
    const idx = window.carrito.findIndex(i => String(i.sku) === String(prod.sku));
    
    // Obtener imagen correctamente
    let imgUrl = prod.img || "";
    if (!imgUrl && prod.imagenes && prod.imagenes.length > 0) {
        imgUrl = prod.imagenes[0];
    }
    if (!imgUrl && prod.miniaturas && typeof prod.miniaturas === 'object') {
        const miniaturas = Object.values(prod.miniaturas).filter(m => m && m.trim());
        if (miniaturas.length > 0) {
            imgUrl = miniaturas[0];
        }
    }
    
    if (idx >= 0) window.carrito[idx].cantidad += qty;
    else window.carrito.push({
        sku: prod.sku,
        nombre: prod.nombre,
        precio: Number(prod.precio || 0),
        img: imgUrl,
        cantidad: qty
    });
    syncCarrito();
    renderProductoCarrito();
    if (mostrarAlerta) {
        mostrarNotificacion(`${prod.nombre} agregado al carrito`, 'success');
    }
}
function setupBotonesAccion() {
  const inputCantidad = $("#inputCantidad");

  // Si por alguna razón el HTML no tiene el input, no revienta
  if (!inputCantidad) return;

  onClick("#btnMenosQty", () => {
    let v = Number(inputCantidad.value || 1);
    v = Math.max(1, v - 1);
    inputCantidad.value = v;
  });

  onClick("#btnMasQty", () => {
    let v = Number(inputCantidad.value || 1);
    inputCantidad.value = v + 1;
  });

  const btnAgregarCarrito = $("#btnAgregarCarrito");
  if (btnAgregarCarrito) {
    btnAgregarCarrito.addEventListener("click", () => {
      if (!productoActual) return;
      const qty = Number(inputCantidad.value || 1);
      agregarAlCarrito(productoActual, qty, true);
    });
  }

  const btnComprarAhora = $("#btnComprarAhora");
  if (btnComprarAhora) {
    btnComprarAhora.addEventListener("click", () => {
      // Solo redirige al catálogo, no agrega al carrito
      window.location.href = "/views/catalogo.html";
    });
  }

  // OJO: en tu HTML NO existe un botón con id="btnWhatsAppProducto"
  // Tú tienes un <a class="whatsapp-btn"> fijo, así que esto lo dejamos OPTIONAL:
  onClick("#btnWhatsAppProducto", () => {
    if (!productoActual) return;
    const qty = Number(inputCantidad.value || 1);
    enviarWhatsAppProducto(qty);
  });
}


// =============================
// WHATSAPP PARA ESTE PRODUCTO
// =============================
function enviarWhatsAppProducto(cantidad) {
    let msg = `*Pedido de producto*\n\n`;
    msg += `Producto: ${productoActual.nombre}\n`;
    msg += `SKU / Código: ${productoActual.sku || productoActual.codigo || "-"}\n`;
    msg += `Cantidad: ${cantidad}\n`;
    msg += `\n`;
    msg += `Envío este mensaje para coordinar mi compra.`;

    const url = `https://api.whatsapp.com/send?phone=${window.WHATSAPP_NUMBER}&text=${encodeURIComponent(msg)}`;
    const w = window.open(url, "_blank");
    if (!w) window.location.href = url;
}

// =============================
// INIT
// =============================
document.addEventListener("DOMContentLoaded", () => {
    const sku = getSkuFromUrl();
    if (!sku) {
        const wrapper = $(".producto-wrapper");
        if (wrapper) {
            wrapper.innerHTML =
                `<div class="text-center py-5 text-muted">
                    No se indicó un producto (sku) en la URL.
                 </div>`;
        }
        return;
    }

cargarProductos()
  .then(() => {
    productoActual = encontrarProducto(sku);
    renderProducto();
    setupBotonesAccion();
  })
  .catch(err => {
    console.error(err);
    $(".producto-wrapper").innerHTML =
      `<div class="text-center py-5 text-danger">
          Ocurrió un error al cargar el producto.
       </div>`;
  });

});

// ===============================
// INICIALIZACIÓN DE PÁGINA - INICIO
// ===============================
document.addEventListener('DOMContentLoaded', function() {

    // -------------------------------------------------------
    // DRAWER MENÚ - COMENTADO
    // Esta lógica es manejada automáticamente por loyout.js
    // mediante la función inyectarDrawer() para páginas que
    // tengan <div class="barrera-menu"></div>.
    // No se necesita aquí para evitar doble registro de eventos.
    // -------------------------------------------------------
    /*
    const drawer = document.getElementById('navbarDrawer');
    const overlay = document.getElementById('drawerOverlay');
    const drawerCloseBtn = document.getElementById('drawerCloseBtn');
    const mobileToggleBtn = document.querySelector('.navbar-bs__toggle--mobile');
    const desktopToggleBtn = document.querySelector('.navbar-bs__toggle--desktop');
    const drawerProductsToggle = document.getElementById('drawerProductosToggle');
    const drawerProductsPanel = document.getElementById('drawerProductosPanel');

    function openDrawer() {
        drawer.classList.add('active');
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeDrawer() {
        drawer.classList.remove('active');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
        drawerProductsPanel.style.display = 'none';
    }

    if (mobileToggleBtn) mobileToggleBtn.addEventListener('click', openDrawer);
    if (desktopToggleBtn) desktopToggleBtn.addEventListener('click', openDrawer);
    if (drawerCloseBtn) drawerCloseBtn.addEventListener('click', closeDrawer);
    if (overlay) overlay.addEventListener('click', closeDrawer);

    if (drawerProductsToggle) {
        drawerProductsToggle.addEventListener('click', function(e) {
            e.preventDefault();
            if (drawerProductsPanel.style.display === 'none' || !drawerProductsPanel.style.display) {
                drawerProductsPanel.style.display = 'block';
            } else {
                drawerProductsPanel.style.display = 'none';
            }
        });
    }

    document.querySelectorAll('.navbar-bs__drawer-link').forEach(link => {
        link.addEventListener('click', closeDrawer);
    });
    */

    // -------------------------------------------------------
    // ACTUALIZAR TÍTULO DE LA PÁGINA
    // Observa cuando el nombre del producto es cargado
    // por JS y actualiza el <title> del documento
    // -------------------------------------------------------
    const tituloProducto = document.getElementById('tituloProducto');
    if (tituloProducto) {
        const observer = new MutationObserver(function() {
            const nuevoTitulo = tituloProducto.textContent;
            if (nuevoTitulo && nuevoTitulo !== 'Nombre producto') {
                document.title = nuevoTitulo;
            }
        });
        observer.observe(tituloProducto, { childList: true, subtree: true, characterData: true });
    }
});
// ===============================
// INICIALIZACIÓN DE PÁGINA - FIN
// ===============================
})();
