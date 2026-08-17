-- ==========================================
-- Fase 2: Gestión de Inventario y Lotes
-- ==========================================

CREATE TABLE IF NOT EXISTS productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT,
    categoria VARCHAR(100),
    unidad_medida VARCHAR(20) DEFAULT 'unidad',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS inventario_lotes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    sede_id INT DEFAULT 1,
    codigo_barras VARCHAR(50) UNIQUE NOT NULL,
    cantidad INT NOT NULL,
    estante VARCHAR(50),
    fecha_vencimiento DATE,
    fecha_ingreso TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    registrado_por INT,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    FOREIGN KEY (sede_id) REFERENCES sedes(id) ON DELETE SET NULL,
    FOREIGN KEY (registrado_por) REFERENCES usuarios(id) ON DELETE SET NULL
);

INSERT INTO productos (nombre, descripcion, categoria) VALUES ('Pintura Esmalte Sintético Blanco 1 Galón', 'Esmalte brillante', 'Pinturas');
INSERT INTO productos (nombre, descripcion, categoria) VALUES ('Thinner Acrílico 1 Galón', 'Disolvente', 'Químicos');