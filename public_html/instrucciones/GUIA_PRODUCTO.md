# 📦 Cómo Agregar, Cambiar y Eliminar Productos - Guía FÁCIL

¡Hola! Esta guía es para **CUALQUIERA**, aunque no sepas de programación. Es como cambiar datos en un formulario.

---

## 📁 ¿Dónde está el archivo?

Abre tu computadora y ve a esta carpeta:
```
tu_carpeta/assets/Data/productos.json
```

Haz doble clic para abrirlo en VS Code.

---

## 🏷️ ¿Qué es un producto? (Explicación simple)

Cada producto es como una **ficha de referencia** de una tienda. Por ejemplo:

```
Nombre: PEGAMENTO Z ADITIVOS GRIS INT X 25 KG
Código: 110014697
Marca: Z ADITIVOS
Categoría: PEGAMENTO
Peso: 25 kg
Foto: /ruta/imagen.jpg
¿Disponible?: SÍ
¿En oferta?: NO
```

En el archivo, esto se guarda así:

```json
{
  "sku": "110014697",
  "marca": "Z ADITIVOS",
  "categoria": "PEGAMENTO",
  "nombre": "PEGAMENTO Z ADITIVOS GRIS INT X 25 KG",
  "descripcion": "Descripción corta",
  "descripcion_larga": "Descripción larga",
  "precio": null,
  "peso2": 25,
  "imagen": "/ruta/imagen.jpg",
  "miniaturas": {
    "miniatura1": "/ruta/imagen1.jpg",
    "miniatura2": "/ruta/imagen2.jpg",
    "miniatura3": "/ruta/imagen3.jpg"
  },
  "ficha_pdf": "/ruta/ficha.pdf",
  "hoja_seguridad_pdf": null,
  "oferta_disponible": false,
  "producto_disponible": true
}
```

---

## 🎯 CADA CAMPO EXPLICADO

| Campo | ¿Qué es? | Ejemplo |
|-------|----------|---------|
| **sku** | Código del producto (como un DNI) | "110014697" |
| **marca** | ¿De qué marca es? | "Z ADITIVOS" |
| **categoria** | Tipo de producto | "PEGAMENTO" |
| **nombre** | Nombre completo | "PEGAMENTO Z ADITIVOS..." |
| **descripcion** | Descripción CORTA | "Pegante fuerte" |
| **descripcion_larga** | Descripción LARGA | "Pegante de uso profesional..." |
| **precio** | Precio | Dejar en null (no se usa) |
| **peso2** | Kilos | "25" |
| **imagen** | Foto principal | "/ruta/imagen.jpg" |
| **miniaturas** | 3 fotos más pequeñas | Rutas de las fotos |
| **ficha_pdf** | Documento técnico | "/ruta/ficha.pdf" |
| **hoja_seguridad_pdf** | Hoja de seguridad | Ruta del PDF |
| **oferta_disponible** | ¿Tiene descuento? | `true` (sí) o `false` (no) |
| **producto_disponible** | ¿Se puede comprar? | `true` (sí) o `false` (no) |

---

## ⚠️ IMPORTANTE: Espacios en las rutas → `%20`

Fíjate bien en esto: **En las rutas de las imágenes, los espacios se escriben como `%20`**

### ¿Por qué?
Los navegadores y sistemas de archivos NO entienden bien los espacios en las direcciones. Por eso usamos un código especial: `%20`

### Ejemplos reales:

**En la carpeta de tu computadora:**
```
📁 /assets/img catalogo/PEGAMENTO Z ADITIVOS/imagen.jpg
                  ↑ espacio normal
```

**En el archivo JSON:**
```json
"imagen": "/assets/img%20catalogo/PEGAMENTO%20Z%20ADITIVOS/imagen.jpg"
                     ↑                              ↑
                   %20 (espacio)                  %20 (espacio)
```

### Tabla de caracteres especiales:
| Carácter | Código |
|----------|--------|
| Espacio | `%20` |
| ñ | `%F1` |
| á | `%E1` |
| @ | `%40` |

**Nota:** Si usas UTF-8 (recomendado), las tildes y ñ funcionen sin códigos especiales. Solo preocúpate por `%20` para espacios.

### ¿Cómo hacerlo rápido?
**Opción 1: Buscar y reemplazar**
1. Presiona **Ctrl + H**
2. Busca: ` ` (un espacio)
3. Reemplaza: `%20`
4. Presiona "Replace All"

**Opción 2: Manual**
- Lee la ruta
- Donde veas un espacio, escribe `%20`

### ✅ Checklist:
- ✅ ¿La ruta tiene espacios? → Cámbialo a `%20`
- ✅ ¿Tiene "img catalogo"? → Debe ser `img%20catalogo`
- ✅ ¿Tiene "PEGAMENTO Z"? → Debe ser `PEGAMENTO%20Z`

---

## ✅ PASO A PASO DETALLADO: AGREGAR UN NUEVO PRODUCTO

### PARTE 1: PREPARACIÓN

#### 1️⃣ Abre la carpeta en tu computadora
1. Presiona las teclas: **Windows + E** (abre Explorador)
2. Navega a: `C:\Users\Tu_Usuario\Desktop\maqueta-main\trabajo-maqueta-main\BSPeru`
3. Abre la carpeta llamada **`assets`**
4. Abre la carpeta llamada **`Data`**
5. Busca el archivo llamado **`productos.json`**

#### 2️⃣ Abre el archivo con VS Code
1. Haz clic DERECHO sobre `productos.json`
2. Elige: **"Abrir con"** → **"Visual Studio Code"**
3. Espera a que se abra (tarda 2-3 segundos)

Verás un archivo lleno de llaves y palabras. ¡No tengas miedo! Es normal.

---

### PARTE 2: IR AL FINAL DEL ARCHIVO

#### 3️⃣ Muévete al final
1. Presiona: **Ctrl + End** (va al final del archivo)
2. Verás algo así al final:
```json
    "producto_disponible": true
  }
]
```

**IMPORTANTE:** El `]` al final es MUY importante. No lo elimines.

---

### PARTE 3: COPIAR UN PRODUCTO COMO MODELO

#### 4️⃣ Busca un producto parecido para copiar
1. Presiona: **Ctrl + Home** (va al inicio del archivo)
2. Presiona: **Ctrl + F** (abre el buscador)
3. Busca un producto parecido al que quieres agregar
   - Ejemplo: Si quieres agregar un "PEGAMENTO", busca "PEGAMENTO"
4. Presiona **Enter** para encontrarlo
5. Cierra el buscador: **Escape**

#### 5️⃣ Selecciona TODO el producto
El producto se ve así (búscalo):
```json
  {
    "sku": "110014697",
    "marca": "Z ADITIVOS",
    ...
    "producto_disponible": true
  }
```

**Haz esto:**
1. Haz clic en el `{` que abre (la llave que abre)
2. Mantén presionado **Shift**
3. Haz clic en el `}` que cierra
4. Ahora está TODO seleccionado (todo se ve de otro color)

#### 6️⃣ Cópia el producto
Presiona: **Ctrl + C** (lo copia al portapapeles)

---

### PARTE 4: PEGAR EL PRODUCTO

#### 7️⃣ Ve al final del archivo
Presiona: **Ctrl + End**

#### 8️⃣ Colócate antes del `]` final
Verás esto:
```json
    "producto_disponible": true
  }
]
```

Haz clic entre el `}` y el `]`, así:
```
  }  ← Cursor aquí (entre } y ])
]
```

#### 9️⃣ Presiona ENTER para crear una línea nueva
Presiona: **Enter**

Verás:
```json
  }

]
```

#### 🔟 Pega el producto
Presiona: **Ctrl + V**

Verás algo así:
```json
  },
  {
    "sku": "110014697",
    "marca": "Z ADITIVOS",
    ...
  }
]
```

**¡Perfecto!** Ahora tienes una copia del producto.

---

### PARTE 5: AGREGAR LA COMA (IMPORTANTE)

#### 1️⃣1️⃣ Verifica la coma anterior
Mira el producto que estaba ANTES:
```json
  {
    ...
    "producto_disponible": true
  }    ← ¿VES UNA COMA AQUÍ?
  {
    ...
  }
]
```

**Debe haber una coma (`,`) después del `}`.**

Si NO hay coma, agrégala:
```json
  {
    ...
    "producto_disponible": true
  },   ← AGREGA ESTO
  {
    ...
  }
]
```

---

### PARTE 6: CAMBIAR LOS DATOS DEL NUEVO PRODUCTO

#### 1️⃣2️⃣ Edita el CÓDIGO (sku)
Busca esta línea EN TU NUEVO PRODUCTO:
```json
"sku": "110014697"
```

Cámbialo a tu código. Ejemplo:
```json
"sku": "999999999"
```

#### 1️⃣3️⃣ Edita el NOMBRE
Busca:
```json
"nombre": "PEGAMENTO Z ADITIVOS GRIS INT X 25 KG"
```

Cámbialo a tu nombre. Ejemplo:
```json
"nombre": "MI NUEVO PRODUCTO"
```

#### 1️⃣4️⃣ Edita la DESCRIPCIÓN CORTA
Busca:
```json
"descripcion": "Descripción vieja"
```

Cámbialo a tu descripción:
```json
"descripcion": "Descripción de mi producto"
```

#### 1️⃣5️⃣ Edita la DESCRIPCIÓN LARGA
Busca:
```json
"descripcion_larga": "Descripción larga vieja"
```

Cámbialo a tu descripción:
```json
"descripcion_larga": "Descripción larga de mi producto"
```

#### 1️⃣6️⃣ Edita el PESO
Busca:
```json
"peso2": 25
```

Cámbialo a tu peso. Ejemplo (si es 50 kg):
```json
"peso2": 50
```

#### 1️⃣7️⃣ Edita las IMÁGENES (IMPORTANTE - ESPACIOS)

Este es el paso CRUCIAL. Las rutas de las imágenes tienen `%20` en lugar de espacios.

Busca:
```json
"imagen": "/assets/img%20catalogo/CARPETA_DEL_PRODUCTO/imagen.jpg"
```

**Reemplázalo CON TUS IMÁGENES:**

Si tu producto está en la carpeta: `/assets/img catalogo/MI_NUEVO_PRODUCTO/`

Entonces escribe (CON `%20` DONDE HAY ESPACIOS):
```json
"imagen": "/assets/img%20catalogo/MI_NUEVO_PRODUCTO/imagen.jpg"
```

**CHECKLIST DE ESPACIOS:**
- ❌ INCORRECTO: `/assets/img catalogo/...`
- ✅ CORRECTO: `/assets/img%20catalogo/...`
- ❌ INCORRECTO: `/MI NUEVO PRODUCTO/...`
- ✅ CORRECTO: `/MI%20NUEVO%20PRODUCTO/...`

Repite esto para todas las miniaturas:
```json
"miniaturas": {
  "miniatura1": "/assets/img%20catalogo/MI%20NUEVO%20PRODUCTO/imagen1.jpg",
  "miniatura2": "/assets/img%20catalogo/MI%20NUEVO%20PRODUCTO/imagen2.jpg",
  "miniatura3": "/assets/img%20catalogo/MI%20NUEVO%20PRODUCTO/imagen3.jpg"
}
```

Y para el PDF:
```json
"ficha_pdf": "/assets/img%20catalogo/MI%20NUEVO%20PRODUCTO/ficha.pdf"
```

#### 1️⃣8️⃣ Marca si está disponible
Busca:
```json
"producto_disponible": true
```

Deja `true` si está disponible, o cambia a `false` si NO está disponible:
```json
"producto_disponible": false
```

#### 1️⃣9️⃣ Marca si tiene oferta
Busca:
```json
"oferta_disponible": false
```

Cambia a `true` si tiene oferta, o deja `false` si NO tiene:
```json
"oferta_disponible": true
```

---

### PARTE 7: GUARDAR

#### 2️⃣0️⃣ Guarda el archivo
Presiona: **Ctrl + S**

**Si todo está correcto:** La pestaña NO tendrá un punto rojo ✅
**Si hay error:** Verás un símbolo rojo. Mira la sección "Errores" abajo.

#### 2️⃣1️⃣ Prueba en el navegador
1. Abre tu navegador (Chrome, Firefox, etc.)
2. Ve a tu sitio web
3. Busca el nuevo producto

¡Lo hiciste! 🎉

---

## ✏️ PASO A PASO DETALLADO: CAMBIAR UN PRODUCTO

### PARTE 1: ABRIR Y BUSCAR

#### 1️⃣ Abre el archivo
1. Ve a: `C:\Users\Tu_Usuario\Desktop\maqueta-main\trabajo-maqueta-main\BSPeru\assets\Data\`
2. Busca el archivo **`productos.json`**
3. Haz clic DERECHO
4. Elige: **"Abrir con"** → **"Visual Studio Code"**

#### 2️⃣ Busca el producto que quieres cambiar
1. Presiona: **Ctrl + F** (abre el buscador)
2. Escribe el nombre o código del producto
3. Presiona **Enter**
4. Se ilumina el producto (en amarillo o naranja)

Ejemplo: Si quieres cambiar "PEGAMENTO Z", busca "PEGAMENTO Z"

---

### PARTE 2: CAMBIAR DIFERENTES DATOS

#### OPCIÓN A: MARCAR COMO AGOTADO (NO disponible)

**Paso 1:** Ubica esta línea en tu producto:
```json
"producto_disponible": true
```

**Paso 2:** Cámbialo a:
```json
"producto_disponible": false
```

**Resultado:** El producto NO aparecerá en la tienda online.

---

#### OPCIÓN B: CAMBIAR LA DESCRIPCIÓN

**Paso 1:** Busca esta línea:
```json
"descripcion": "Descripción vieja que tenía"
```

**Paso 2:** Cámbiala a tu nueva descripción:
```json
"descripcion": "Nueva descripción más corta"
```

Ejemplo real:
- Viejo: `"descripcion": "Pegante fuerte"`
- Nuevo: `"descripcion": "Pegante profesional de larga duración"`

---

#### OPCIÓN C: CAMBIAR EL PESO

**Paso 1:** Busca:
```json
"peso2": 25
```

**Paso 2:** Cambia el número:
```json
"peso2": 50
```

Ejemplos:
- `"peso2": 25` (25 kilogramos)
- `"peso2": 5` (5 galones)
- `"peso2": 100` (100 unidades)

---

#### OPCIÓN D: CAMBIAR SI TIENE OFERTA

**Paso 1:** Busca:
```json
"oferta_disponible": false
```

**Paso 2:** Cambia a:
- `"oferta_disponible": true` ← Si TIENE oferta/descuento
- `"oferta_disponible": false` ← Si NO tiene oferta

---

#### OPCIÓN E: CAMBIAR EL NOMBRE

**Paso 1:** Busca:
```json
"nombre": "PEGAMENTO Z ADITIVOS GRIS INT X 25 KG"
```

**Paso 2:** Cámbialo a tu nuevo nombre:
```json
"nombre": "PEGAMENTO Z ADITIVOS GRIS INT X 50 KG"
```

---

#### OPCIÓN F: CAMBIAR LAS IMÁGENES (IMPORTANTE - ESPACIOS)

**Paso 1:** Busca:
```json
"imagen": "/assets/img%20catalogo/CARPETA_VIEJA/imagen.jpg"
```

**Paso 2:** Cámbialo a tu nueva ruta (con `%20` en lugar de espacios):
```json
"imagen": "/assets/img%20catalogo/CARPETA_NUEVA/imagen.jpg"
```

**RECUERDA:** Reemplaza los espacios con `%20`:
- ❌ INCORRECTO: `/assets/img catalogo/...`
- ✅ CORRECTO: `/assets/img%20catalogo/...`

Haz lo mismo con las miniaturas:
```json
"miniaturas": {
  "miniatura1": "/assets/img%20catalogo/CARPETA_NUEVA/imagen1.jpg",
  "miniatura2": "/assets/img%20catalogo/CARPETA_NUEVA/imagen2.jpg",
  "miniatura3": "/assets/img%20catalogo/CARPETA_NUEVA/imagen3.jpg"
}
```

---

### PARTE 3: GUARDAR

#### 3️⃣ Guarda el archivo
Presiona: **Ctrl + S**

**Verificación:**
- ✅ Si la pestaña NO tiene punto rojo = TODO CORRECTO
- ❌ Si ves un punto rojo = Hay error, deshace con **Ctrl + Z**

¡Listo! El cambio se guardó. 🎉

---

## ❌ PASO A PASO DETALLADO: ELIMINAR UN PRODUCTO

### PARTE 1: PREPARACIÓN

#### 1️⃣ Abre el archivo
1. Ve a: `C:\Users\Tu_Usuario\Desktop\maqueta-main\trabajo-maqueta-main\BSPeru\assets\Data\`
2. Busca el archivo **`productos.json`**
3. Haz clic DERECHO
4. Elige: **"Abrir con"** → **"Visual Studio Code"**

#### 2️⃣ Busca el producto que quieres eliminar
1. Presiona: **Ctrl + F** (abre el buscador)
2. Escribe el nombre o código del producto
3. Presiona **Enter**
4. Se ilumina el producto

---

### PARTE 2: SELECCIONAR EL PRODUCTO COMPLETO

#### 3️⃣ Identifica dónde empieza y dónde termina el producto

El producto tiene esta estructura:
```json
  {
    "sku": "110014697",
    "marca": "Z ADITIVOS",
    "categoria": "PEGAMENTO",
    "nombre": "PEGAMENTO Z ADITIVOS GRIS INT X 25 KG",
    "descripcion": "...",
    "descripcion_larga": "...",
    "precio": null,
    "peso2": 25,
    "imagen": "...",
    "miniaturas": { ... },
    "ficha_pdf": "...",
    "hoja_seguridad_pdf": null,
    "oferta_disponible": false,
    "producto_disponible": true
  }
```

**Recuerda:**
- La llave que ABRE: `{` (aquí empieza)
- La llave que CIERRA: `}` (aquí termina)

#### 4️⃣ Haz clic en la llave que ABRE `{`

Posiciona tu cursor aquí:
```json
  {  ← Haz clic aquí
    "sku": "110014697",
```

#### 5️⃣ Selecciona TODO el producto

**Opción 1 (MÁS FÁCIL):**
1. Haz clic en el número de línea del `{` (a la izquierda)
2. Mantén presionado **Shift**
3. Haz clic en el número de línea del `}`
4. Listo, está todo seleccionado

**Opción 2 (Manual):**
1. Haz clic en el `{`
2. Mantén presionado **Shift + Fin** para ir al final de cada línea
3. Repite hasta llegar al `}`

**Lo que verás cuando esté seleccionado:**
```json
  {  ← TODO ESTO SE VE CON COLOR
    "sku": "110014697",
    ...
    "producto_disponible": true
  }  ← Hasta aquí
```

---

### PARTE 3: ELIMINAR EL PRODUCTO

#### 6️⃣ Borra el producto

Una vez que TODO está seleccionado, presiona: **Delete**

El producto desaparece. Verás que ahora hay un espacio vacío donde estaba.

**O ALTERNATIVELY:**
- Con el cursor en la línea del `{`, presiona **Ctrl + Shift + K** varias veces hasta borrar todo

---

### PARTE 4: VERIFICAR Y ARREGLAR LAS COMAS (IMPORTANTE)

#### 7️⃣ Verifica que las comas estén correctas

Las comas son SUPER importantes. Tienes que revisar:

**Regla de ORO:**
- ✅ Todos los productos MENOS EL ÚLTIMO tienen coma después de `}`
- ✅ El producto ÚLTIMO NO tiene coma

**Ejemplo CORRECTO:**
```json
[
  {
    "nombre": "Producto 1",
    ...
    "producto_disponible": true
  },   ← Coma aquí (hay más productos)
  {
    "nombre": "Producto 2",
    ...
    "producto_disponible": true
  }    ← SIN coma (es el último)
]
```

#### 8️⃣ Mira lo que quedó después de tu eliminación

Si eliminaste un producto del MEDIO, verás:
```json
  {
    "nombre": "Producto 1",
    ...
  },   ← ¿Tiene coma? (DEBE tener)
  {
    "nombre": "Producto 3",  ← Producto 2 se eliminó
    ...
  }    ← ¿Tiene coma? (Si es el último, NO debe tener)
]
```

#### 9️⃣ Arregla si falta o sobra coma

**Caso 1: Eliminaste del medio**
```json
  {
    ...
    "producto_disponible": true
  }    ← Falta coma aquí
  {
```

Agrega la coma:
```json
  {
    ...
    "producto_disponible": true
  },   ← ¡Agrega esto!
  {
```

**Caso 2: Eliminaste el último producto**
```json
  {
    ...
    "producto_disponible": true
  },   ← Sobra coma aquí
]
```

Quita la coma:
```json
  {
    ...
    "producto_disponible": true
  }    ← Quita la coma
]
```

---

### PARTE 5: GUARDAR

#### 1️⃣0️⃣ Guarda el archivo
Presiona: **Ctrl + S**

**Verificación final:**
- ✅ Si la pestaña NO tiene punto rojo = TODO CORRECTO ✅
- ❌ Si ves un símbolo rojo = Hay error, deshace con **Ctrl + Z**

#### 1️⃣1️⃣ Verifica en la tienda online
1. Abre tu navegador
2. Ve a tu sitio web
3. Busca el producto
4. No debe estar allí = ¡Eliminado! ✅

¡Listo! El producto fue eliminado exitosamente. 🎉

---

## 🚨 ERRORES Y SOLUCIONES

### ❌ Ves un punto rojo o símbolo de error
**Problema:** Hay un error en el archivo

**Solución:**
- Presiona **Ctrl + Z** para deshacer
- Intenta de nuevo, lentamente

### ❌ Dice "Unexpected token"
**Problema:** Falta una coma o la sintaxis está mal

**Busca esto:**
```json
  {
    "nombre": "Algo"
  }    ← Falta coma aquí
  {
    "nombre": "Otro"
  }
```

**Debería ser:**
```json
  {
    "nombre": "Algo"
  },   ← Agregar coma
  {
    "nombre": "Otro"
  }
```

### ❌ Ves caracteres raros (ñ, á, ?)
**Problema:** El archivo tiene problemas de idioma/codificación

**Solución:**
1. Mira la esquina **ABAJO A LA DERECHA** de VS Code
2. Verás `UTF-8`
3. Haz clic
4. Elige **"Reopen with Encoding"**
5. Prueba **"Latin-1"** o **"Windows-1252"**

Si se ve bien, vuelve a hacer esto y elige **"Save with Encoding"** con **UTF-8**.

### ❌ El archivo no se abre
**Solución:**
1. Abre el Explorador de Carpetas
2. Ve a: `tu_carpeta/assets/Data/`
3. Busca el archivo `productos.json`
4. Haz doble clic

---

## ⚡ ATAJOS ÚTILES

| Lo que quieres hacer | Presiona |
|----------------------|----------|
| Guardar | Ctrl + S |
| Buscar | Ctrl + F |
| Buscar y Reemplazar | Ctrl + H |
| Deshacer | Ctrl + Z |
| Ir al final | Ctrl + End |
| Ir al inicio | Ctrl + Home |
| Eliminar una línea | Ctrl + Shift + K |

---

## 📝 PLANTILLA LISTA PARA COPIAR

Si prefieres, copia esto, cambia los datos y pega en el archivo:

```json
  {
    "sku": "999999999",
    "marca": "Z ADITIVOS",
    "categoria": "PEGAMENTO",
    "nombre": "NOMBRE DE TU PRODUCTO",
    "descripcion": "Descripción corta del producto",
    "descripcion_larga": "Descripción larga y detallada del producto",
    "precio": null,
    "peso2": 25,
    "imagen": "/assets/img%20catalogo/CARPETA_DEL_PRODUCTO/imagen.jpg",
    "miniaturas": {
      "miniatura1": "/assets/img%20catalogo/CARPETA_DEL_PRODUCTO/imagen1.jpg",
      "miniatura2": "/assets/img%20catalogo/CARPETA_DEL_PRODUCTO/imagen2.jpg",
      "miniatura3": "/assets/img%20catalogo/CARPETA_DEL_PRODUCTO/imagen3.jpg"
    },
    "ficha_pdf": "/assets/img%20catalogo/CARPETA_DEL_PRODUCTO/ficha.pdf",
    "hoja_seguridad_pdf": null,
    "oferta_disponible": false,
    "producto_disponible": true
  }
```

Cambia:
- `999999999` → Tu código
- `NOMBRE DE TU PRODUCTO` → Nombre real
- Las rutas de imágenes
- El peso (25)

---

## 🎯 RESUMEN EN 3 FRASES

- **AGREGAR:** Copia un producto al final, cámbialo, agrega coma, guarda
- **CAMBIAR:** Busca el producto, edita el campo, guarda
- **ELIMINAR:** Busca el producto, elimínalo, verifica comas, guarda

---

## ❓ PREGUNTAS FRECUENTES

**P: ¿Qué pasa si cometo un error?**
R: Presiona **Ctrl + Z** para deshacer.

**P: ¿Necesito guardar todo?**
R: Sí, presiona **Ctrl + S** después de cada cambio importante.

**P: ¿Qué es "null"?**
R: Es como decir "sin valor" o "vacío". Es correcto dejarlo así.

**P: ¿Qué significa "true" y "false"?**
R: `true` = Sí, `false` = No. Por ejemplo:
- `"producto_disponible": true` = El producto está disponible
- `"oferta_disponible": false` = El producto NO tiene oferta

**P: ¿Puedo cambiar el sku de un producto?**
R: Mejor no. Es como cambiar el DNI. Es mejor dejar el sku igual.

**P: ¿De dónde saco las rutas de las imágenes?**
R: Ve a la carpeta `/assets/img catalogo/` y copia la ruta. Usa `%20` en lugar de espacios.

**P: ¿Por qué aparece `%20` en lugar de espacios en las rutas?**
R: ¡Excelente pregunta! `%20` es la forma especial de escribir un espacio en las rutas de internet y archivos JSON. 

**¿Por qué?** En internet, los espacios causan problemas. Por eso se usan códigos especiales:
- El espacio normal = `%20`
- La `ñ` = `%F1` (pero mejor usa UTF-8)

**Ejemplo:**
- ❌ Incorrecto: `/assets/img catalogo/PRODUCTO/imagen.jpg` (tiene espacios)
- ✅ Correcto: `/assets/img%20catalogo/PRODUCTO/imagen.jpg` (`%20` en lugar de espacios)

**¿Cómo lo hago?**
1. Escribe la ruta normalmente
2. Antes de guardar, reemplaza todos los espacios con `%20`
3. **O usa buscar y reemplazar:** Presiona **Ctrl + H**
   - Busca: `img catalogo` (con espacio)
   - Reemplaza: `img%20catalogo`

**Recordatorio:** En la carpeta real, el nombre tiene espacios. Pero EN EL ARCHIVO JSON, escribe con `%20`.

---

**¡Eso es todo! Si tienes dudas, pregunta. 😊**

Última actualización: 25 de mayo de 2026
