-- ==========================================
-- Esquema de Base de Datos - BS Peru ERP
-- Fase 1: Base de Datos y Clientes
-- ==========================================

CREATE TABLE IF NOT EXISTS sedes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    direccion VARCHAR(255),
    tipo ENUM('almacen', 'tienda') DEFAULT 'tienda',
    activa BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('vendedora', 'administracion', 'finanzas', 'operaciones', 'almacen') NOT NULL,
    sede_id INT,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sede_id) REFERENCES sedes(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ruc_dni VARCHAR(20) UNIQUE NOT NULL,
    razon_social VARCHAR(200) NOT NULL,
    direccion VARCHAR(255),
    categoria ENUM('aplicador', 'revendedor_minorista', 'contratista', 'constructora') NOT NULL,
    agente_retencion BOOLEAN DEFAULT FALSE,
    correo VARCHAR(100),
    contacto VARCHAR(100),
    telefono VARCHAR(20),
    registrado_por INT,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (registrado_por) REFERENCES usuarios(id) ON DELETE SET NULL
);

INSERT INTO sedes (nombre, tipo) VALUES ('Chorrillos (Principal)', 'almacen');
INSERT INTO usuarios (nombre, email, password_hash, rol, sede_id) VALUES ('Endrina', 'endrina@bsperu.pe', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'administracion', 1);
INSERT INTO usuarios (nombre, email, password_hash, rol, sede_id) VALUES ('Nayeli', 'nayeli@bsperu.pe', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'finanzas', 1);
