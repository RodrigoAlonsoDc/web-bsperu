-- ==========================================
-- Fase 4: Validacion de Pagos y Facturacion
-- ==========================================

CREATE TABLE IF NOT EXISTS pagos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cotizacion_id INT NOT NULL,
    metodo_pago VARCHAR(50) NOT NULL,
    numero_operacion VARCHAR(100),
    monto_pagado DECIMAL(10,2) NOT NULL,
    validador_id INT,
    fecha_pago TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cotizacion_id) REFERENCES cotizaciones(id) ON DELETE CASCADE,
    FOREIGN KEY (validador_id) REFERENCES usuarios(id) ON DELETE SET NULL
);