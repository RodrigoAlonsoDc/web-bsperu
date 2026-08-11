# 📦 Cómo Agregar, Cambiar y Eliminar Productos - Guía Fácil

¡Hola! Esta guía es para cualquiera, aunque no sepas de programación. 
Imagina que el archivo de productos es como un **catálogo de tienda**, donde cada página es un producto.

---

## 📁 ¿Dónde está el archivo?
Abre la carpeta de tu computadora y ve a:
```
tu_carpeta/assets/Data/productos.json
```

---

## 🏷️ ¿Qué tiene cada producto?

Cada producto tiene información como si fuera una **ficha de referencia**:

**Ejemplo: Producto de pegamento**

```json
{
  "sku": "110014697",
  "marca": "Z ADITIVOS",
  "categoria": "PEGAMENTO",
  "nombre": "PEGAMENTO Z ADITIVOS GRIS INT X 25 KG",
  "descripcion": "Texto corto del producto",
  "descripcion_larga": "Texto largo y detallado del producto",
  "precio": null,
  "peso2": 25,
  "imagen": "/assets/img%20catalogo/RUTA_IMAGEN/imagen.jpg",
  "miniaturas": {
    "miniatura1": "/assets/img%20catalogo/RUTA_IMAGEN/imagen1.jpg",
    "miniatura2": "/assets/img%20catalogo/RUTA_IMAGEN/imagen2.jpg",
    "miniatura3": "/assets/img%20catalogo/RUTA_IMAGEN/imagen3.jpg"
  },
  "ficha_pdf": "/assets/img%20catalogo/RUTA_IMAGEN/archivo.pdf",
  "hoja_seguridad_pdf": null,
  "oferta_disponible": false,
  "producto_disponible": true
}
```

### Explicación de campos:

| Campo | Tipo | Descripción |
|-------|------|-------------|
| **sku** | string | Código único del producto (Ej: "110014697") |
| **marca** | string | Marca del producto (Ej: "Z ADITIVOS") |
| **categoria** | string | Categoría (Ej: "PEGAMENTO", "IMPERMEABILIZANTES") |
| **nombre** | string | Nombre del producto |
| **descripcion** | string/null | Descripción corta (puede ser null) |
| **descripcion_larga** | string/null | Descripción extensa (puede ser null) |
| **precio** | number/null | Precio del producto (generalmente null) |
| **peso2** | number | Peso en kg |
| **imagen** | string | Ruta de imagen principal (con espacios codificados como %20) |
| **miniaturas** | object | 3 imágenes miniatura para galería |
| **ficha_pdf** | string/null | Ruta del PDF técnico (puede ser null) |
| **hoja_seguridad_pdf** | string/null | Ruta del PDF de seguridad (puede ser null) |
| **oferta_disponible** | boolean | ¿Tiene oferta? (true/false) |
| **producto_disponible** | boolean | ¿Está disponible? (true/false) |

---

## ✅ AGREGAR UN NUEVO PRODUCTO

### Paso 1: Abre el archivo
1. En VS Code, abre `/assets/Data/productos.json`

### Paso 2: Ubícate al final del último producto
1. Presiona `Ctrl + End` para ir al final del archivo
2. Busca el último producto antes de la llave de cierre `]`

### Paso 3: Prepara la estructura
El último producto debe verse así (sin coma después):
```json
  {
    "sku": "110014xxx",
    ...
    "producto_disponible": true
  }
]
```

### Paso 4: Agrega una coma y el nuevo producto

**Antes del último `]`, agrega:**

```json
  {
    "sku": "110014XXX",
    "marca": "Z ADITIVOS",
    "categoria": "PEGAMENTO",
    "nombre": "NOMBRE DEL NUEVO PRODUCTO",
    "descripcion": "Descripción corta",
    "descripcion_larga": "Descripción larga y detallada del producto",
    "precio": null,
    "peso2": 25,
    "imagen": "/assets/img%20catalogo/CARPETA_PRODUCTO/imagen.jpg",
    "miniaturas": {
      "miniatura1": "/assets/img%20catalogo/CARPETA_PRODUCTO/imagen1.jpg",
      "miniatura2": "/assets/img%20catalogo/CARPETA_PRODUCTO/imagen2.jpg",
      "miniatura3": "/assets/img%20catalogo/CARPETA_PRODUCTO/imagen3.jpg"
    },
    "ficha_pdf": "/assets/img%20catalogo/CARPETA_PRODUCTO/ficha.pdf",
    "hoja_seguridad_pdf": null,
    "oferta_disponible": false,
    "producto_disponible": true
  }
]
```

### Paso 5: IMPORTANTE - Agrega coma después del producto anterior

Si el producto anterior no tenía coma, **DEBES AGREGAR UNA COMA** después de su llave de cierre `}`:

```json
  {
    "sku": "110014xxx",
    ...
    "producto_disponible": true
  },   ← ⚠️ AGREGA ESTA COMA
  {
    "sku": "110014YYY",
    ...
    "producto_disponible": true
  }
]
```

### Paso 6: Guarda el archivo
- Presiona `Ctrl + S`
- Verifica que no haya errores de sintaxis JSON

### Ejemplo completo de adición:

```json
  {
    "sku": "110014500",
    "marca": "Z ADITIVOS",
    "categoria": "IMPERMEABILIZANTES",
    "nombre": "PRODUCTO NUEVO 2024",
    "descripcion": "Producto revolucionario",
    "descripcion_larga": "Este es un producto nuevo con características mejoradas para impermeabilización de techos y superficies.",
    "precio": null,
    "peso2": 30,
    "imagen": "/assets/img%20catalogo/PRODUCTO%20NUEVO%202024/producto1.jpg",
    "miniaturas": {
      "miniatura1": "/assets/img%20catalogo/PRODUCTO%20NUEVO%202024/producto1.jpg",
      "miniatura2": "/assets/img%20catalogo/PRODUCTO%20NUEVO%202024/producto2.jpg",
      "miniatura3": "/assets/img%20catalogo/PRODUCTO%20NUEVO%202024/producto3.jpg"
    },
    "ficha_pdf": "/assets/img%20catalogo/PRODUCTO%20NUEVO%202024/ficha.pdf",
    "hoja_seguridad_pdf": "/assets/img%20catalogo/PRODUCTO%20NUEVO%202024/seguridad.pdf",
    "oferta_disponible": true,
    "producto_disponible": true
  }
```

---

## ✏️ MODIFICAR UN PRODUCTO

### Paso 1: Abre el archivo
1. En VS Code, abre `/assets/Data/productos.json`

### Paso 2: Busca el producto
1. Presiona `Ctrl + F` para abrir la búsqueda
2. Busca por **SKU** o **nombre** del producto

Ejemplo: Busca `"sku": "110014697"` o `"nombre": "PEGAMENTO Z ADITIVOS"`

### Paso 3: Localiza el campo a modificar

Una vez hayas encontrado el producto, busca el campo que deseas modificar:

```json
{
  "sku": "110014697",           ← Generalmente NO modificar
  "marca": "Z ADITIVOS",         ← Puedes cambiar
  "categoria": "PEGAMENTO",      ← Puedes cambiar
  "nombre": "PEGAMENTO Z...",    ← Puedes cambiar
  "descripcion": "Aditivo...",   ← Puedes cambiar
  "descripcion_larga": "....",   ← Puedes cambiar
  "precio": null,                ← Puedes cambiar
  "peso2": 25,                   ← Puedes cambiar
  "imagen": "...",               ← Puedes cambiar
  "miniaturas": {...},           ← Puedes cambiar
  "ficha_pdf": "...",            ← Puedes cambiar
  "hoja_seguridad_pdf": null,    ← Puedes cambiar
  "oferta_disponible": false,    ← Puedes cambiar
  "producto_disponible": true    ← Puedes cambiar
}
```

### Paso 4: Edita el valor

**Ejemplo 1:** Cambiar descripción
```json
// ANTES:
"descripcion": "Aditivo antiguo",

// DESPUÉS:
"descripcion": "Aditivo mejorado 2024",
```

**Ejemplo 2:** Cambiar disponibilidad
```json
// ANTES:
"producto_disponible": true,

// DESPUÉS:
"producto_disponible": false,
```

**Ejemplo 3:** Cambiar categoría
```json
// ANTES:
"categoria": "PEGAMENTO",

// DESPUÉS:
"categoria": "ADITIVOS ESPECIALES",
```

**Ejemplo 4:** Cambiar peso
```json
// ANTES:
"peso2": 25,

// DESPUÉS:
"peso2": 50,
```

### Paso 5: Guarda el archivo
- Presiona `Ctrl + S`
- Verifica que no haya errores

---

## ❌ QUITAR UN PRODUCTO

### Paso 1: Abre el archivo
1. En VS Code, abre `/assets/Data/productos.json`

### Paso 2: Busca el producto a eliminar
1. Presiona `Ctrl + F`
2. Busca por SKU o nombre

### Paso 3: Selecciona TODO el producto

Debes seleccionar el objeto completo, desde la llave de apertura `{` hasta la llave de cierre `}`:

```json
  {
    "sku": "110014697",
    "marca": "Z ADITIVOS",
    "categoria": "PEGAMENTO",
    "nombre": "PEGAMENTO Z ADITIVOS GRIS INT X 25 KG",
    "descripcion": null,
    "descripcion_larga": null,
    "precio": null,
    "peso2": 25,
    "imagen": "/assets/img%20catalogo/...",
    "miniaturas": {
      "miniatura1": "...",
      "miniatura2": "...",
      "miniatura3": "..."
    },
    "ficha_pdf": "...",
    "hoja_seguridad_pdf": null,
    "oferta_disponible": false,
    "producto_disponible": true
  }  ← ⚠️ Selecciona TODO desde { hasta }
```

### Paso 4: ⚠️ IMPORTANTE - Maneja las comas correctamente

**Caso 1: Si el producto tiene un producto ANTES Y DESPUÉS**
```json
  {
    "sku": "anterior",
    ...
  },      ← Coma del producto anterior (MANTENER)
  {
    "sku": "110014697",     ← ELIMINAR ESTE OBJETO COMPLETO
    ...
  },      ← Coma de este producto (ELIMINAR también)
  {
    "sku": "siguiente",
    ...
  }
```

**Qué quedará después:**
```json
  {
    "sku": "anterior",
    ...
  },      ← Se mantiene
  {
    "sku": "siguiente",
    ...
  }
```

**Caso 2: Si es el ÚLTIMO producto**
```json
  {
    "sku": "penultimo",
    ...
  },      ← Coma MANTENER
  {
    "sku": "110014697",     ← ELIMINAR ESTE OBJETO COMPLETO
    ...
  }       ← NO hay coma (es el último)
]
```

**Qué quedará después:**
```json
  {
    "sku": "penultimo",
    ...
  }       ← NO hay coma (ahora es el último)
]
```

### Paso 5: Elimina el producto

1. **Opción A - Manual:**
   - Selecciona el objeto completo
   - Presiona `Delete` o `Backspace`
   - Si el producto tiene una coma después, **elimínala también** (excepto si es el último)

2. **Opción B - Con VS Code:**
   - Haz clic al inicio de la línea del producto
   - Presiona `Ctrl + Shift + K` (elimina la línea)
   - Repite hasta eliminar todo el objeto

### Paso 6: Verifica las comas

Después de eliminar, verifica que:
- ✅ Cada producto (excepto el último) termine con `,`
- ✅ El último producto NO tenga coma
- ✅ El archivo termine con `]`

**Estructura correcta:**
```json
[
  {
    ...
  },     ← Coma
  {
    ...
  },     ← Coma
  {
    ...
  }      ← Sin coma (es el último)
]
```

### Paso 7: Guarda el archivo
- Presiona `Ctrl + S`
- Verifica que no haya errores JSON

---

## 🔍 VALIDAR LA SINTAXIS JSON

Para asegurar que el archivo está bien formado:

### Opción 1: Ver errores en VS Code
1. Si hay un error de sintaxis, aparecerá una **X roja** en la pestaña del archivo
2. Los errores aparecen en la sección **"Problemas"** (Ctrl + Shift + M)

### Opción 2: Usar un validador online
1. Copia todo el contenido del archivo
2. Pega en: https://jsonlint.com/
3. Presiona "Validate JSON"

---

## ⚡ CONSEJOS RÁPIDOS

| Acción | Atajo |
|--------|-------|
| Guardar | `Ctrl + S` |
| Buscar | `Ctrl + F` |
| Reemplazar | `Ctrl + H` |
| Deshacer | `Ctrl + Z` |
| Ir al final | `Ctrl + End` |
| Ir al inicio | `Ctrl + Home` |
| Seleccionar línea | `Ctrl + L` |
| Eliminar línea | `Ctrl + Shift + K` |

---

## ⚠️ ERRORES COMUNES

### Error: "Unexpected token" o "Expected comma"
**Causa:** Falta una coma entre productos
```json
  {
    ...
  }    ← ❌ Falta coma aquí
  {
    ...
  }
```
**Solución:** Agrega una coma: `},`

---

### Error: "Trailing comma"
**Causa:** El último producto tiene coma
```json
  {
    ...
  },   ← ❌ El último NO debe tener coma
]
```
**Solución:** Elimina la coma antes del `]`

---

### Error: Caracteres extraños (?, ñ, á, etc.)
**Causa:** Problema de codificación del archivo
**Solución:**
1. Haz clic en **UTF-8** (esquina inferior derecha)
2. Selecciona **"Reopen with Encoding"**
3. Prueba con **"Latin-1"** o **"Windows-1252"**
4. Si se ve bien, vuelve a seleccionar **UTF-8** y elige **"Save with Encoding"**

---

## 📞 ¿Necesitas ayuda?

Si algo no funciona:
1. Verifica que estés en el archivo correcto: `/assets/Data/productos.json`
2. Busca errores en la sección "Problemas" (`Ctrl + Shift + M`)
3. Usa un validador JSON online para confirmar la sintaxis
4. Haz un backup antes de hacer cambios grandes

---

**Última actualización:** 25 de mayo de 2026
