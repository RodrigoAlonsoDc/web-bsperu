let sucursales = [];
let searchSucursal = null;
let map = null;
let puntosContainer = null;
let puntosUbicacion = {};
const sucursalesContainer = document.getElementById("sucursalesContainer");

// Mapeo de ciudades a departamentos
const CIUDAD_DEPARTAMENTO = {
    'CALLAO': 'Callao',
    'CHORRILLOS': 'Lima',
    'LIMA': 'Lima',
    'SULLANA': 'Piura',
    'PIURA': 'Piura',
    'TRUJILLO': 'La Libertad',
    'CHICLAYO': 'Lambayeque',
    'TACNA': 'Tacna',
    'AREQUIPA': 'Arequipa'
};

// Posiciones DIRECTAS en el mapa (% de izquierda y arriba)
// Posiciones finales exactas proporcionadas por usuario
const POSICIONES_MAPA = {
    'fali': { x: 55, y: 64 },        // CALLAO
    'chorrillos': { x: 57, y: 66 },  // CHORRILLOS
    'lima': { x: 55, y: 58 },        // LIMA
    'sullana': { x: 44, y: 26 },     // SULLANA
    'piuraee': { x: 45, y: 32 },     // PIURA
    'trujillo': { x: 50, y: 46 },    // TRUJILLO
    'chiclayo': { x: 47, y: 37 },    // CHICLAYO
    'tacna': { x: 75, y: 93 },       // TACNA
    'arequipa': { x: 69, y: 83 }     // AREQUIPA
};

// Cargar sucursales desde JSON
async function cargarSucursales() {
    try {
        const response = await fetch("/assets/Data/sucursales.json");
        const data = await response.json();
        
        // Generar HTML dinámicamente para lista
        data.forEach(sucursal => {
            const div = document.createElement("div");
            div.className = "sucursal";
            div.setAttribute("data-key", sucursal.key);
            div.setAttribute("data-map", sucursal.urlMap);
            
            div.innerHTML = `
                <b>${sucursal.nombre}</b><br>
                ${sucursal.direccion}<br>
                ${sucursal.referencia ? `<small style="font-style: italic; color: #666;">${sucursal.referencia}</small><br>` : ''}
                <small>Teléfono: ${sucursal.telefono || 'N/A'}</small>
            `;
            
            sucursalesContainer.appendChild(div);
        });
        
        // Obtener referencias a todos los elementos generados
        sucursales = document.querySelectorAll(".sucursal");
        searchSucursal = document.getElementById("searchSucursal");
        map = document.getElementById("map");
        puntosContainer = document.getElementById("puntosUbicacion");
        
        // Agregar event listeners a cada sucursal
        sucursales.forEach(item => {
            item.addEventListener("click", function () {
                activateSucursal(this);
            });
        });
        
        // Filtrado
        if (searchSucursal) {
            searchSucursal.addEventListener("input", filtrarSucursales);
        }
        
        // Crear puntos interactivos en el mapa
        crearPuntosEnMapa(data);
        
        // Generar lista de departamentos
        generarListaDepartamentos(data);
        
        // Check URL parameter
        const params = new URLSearchParams(window.location.search);
        const selected = params.get('sucursal');
        const selectedKey = (selected || '').toLowerCase().replace(/[^a-z0-9]/g, '');
        
        if (selected) {
            const target = document.querySelector(`.sucursal[data-key="${selectedKey}"]`);
            if (target) {
                activateSucursal(target);
            }
        }
        
        filtrarSucursales();
        
    } catch (error) {
        console.error("Error al cargar sucursales:", error);
    }
}

// Crear puntos interactivos en el mapa
function crearPuntosEnMapa(data) {
    if (!puntosContainer) return;
    
    data.forEach(sucursal => {
        // Obtener posición directa del mapa
        const pos = POSICIONES_MAPA[sucursal.key];
        
        if (pos) {
            const punto = document.createElement("div");
            punto.className = "punto";
            punto.setAttribute("data-key", sucursal.key);
            punto.style.left = pos.x + "%";
            punto.style.top = pos.y + "%";
            
            punto.innerHTML = `<div class="tooltip-ubicacion">${sucursal.nombre}</div>`;
            
            // Click en el punto
            punto.addEventListener("click", function(e) {
                e.stopPropagation();
                const element = document.querySelector(`.sucursal[data-key="${sucursal.key}"]`);
                if (element) {
                    activateSucursal(element);
                    element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });
            
            puntosContainer.appendChild(punto);
            puntosUbicacion[sucursal.key] = punto;
        } else {
            console.warn(`Posición no definida para: ${sucursal.key}`);
        }
    });
}

// Generar lista de departamentos en el mapa
function generarListaDepartamentos(data) {
    const departamentosList = document.getElementById("departamentosList");
    if (!departamentosList) return;
    
    // Obtener departamentos únicos
    const departamentosUnicos = new Set();
    data.forEach(sucursal => {
        const depto = CIUDAD_DEPARTAMENTO[sucursal.nombre];
        if (depto) {
            departamentosUnicos.add(depto);
        }
    });
    
    // Convertir a array y ordenar
    const departamentosArray = Array.from(departamentosUnicos).sort();
    
    // Generar HTML
    departamentosArray.forEach(depto => {
        const li = document.createElement("li");
        li.textContent = depto;
        departamentosList.appendChild(li);
    });
}

function activateSucursal(element) {
    const key = element.getAttribute("data-key");
    
    map.src = element.getAttribute("data-map");
    sucursales.forEach(s => s.classList.remove("active-sucursal"));
    element.classList.add("active-sucursal");
    
    // Actualizar punto activo en mapa
    Object.keys(puntosUbicacion).forEach(k => {
        puntosUbicacion[k].classList.remove("active");
    });
    if (puntosUbicacion[key]) {
        puntosUbicacion[key].classList.add("active");
    }
    
    // En móvil, ocultar la lista después de seleccionar
    if (window.innerWidth <= 576) {
        searchSucursal.value = "";
        sucursales.forEach(s => s.classList.remove("mostrar-en-busqueda"));
    }
}

function normalizaTexto(value) {
    return (value || "")
        .toString()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .toLowerCase();
}

function filtrarSucursales() {
    const query = normalizaTexto(searchSucursal?.value || "").trim();
    let primeraVisible = null;

    sucursales.forEach((item) => {
        const texto = normalizaTexto(item.textContent);
        const visible = !query || texto.includes(query);
        item.style.display = visible ? "block" : "none";
        
        // Agregar clase para móvil
        if (visible && query) {
            item.classList.add("mostrar-en-busqueda");
        } else {
            item.classList.remove("mostrar-en-busqueda");
        }
        
        if (visible && !primeraVisible) primeraVisible = item;
    });
}

// Cargar sucursales cuando el DOM esté listo
document.addEventListener("DOMContentLoaded", cargarSucursales);
