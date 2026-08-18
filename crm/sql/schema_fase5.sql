-- ==========================================
-- Fase 5: Operaciones y Despacho
-- ==========================================

CREATE TABLE IF NOT EXISTS despachos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cotizacion_id INT NOT NULL,
    operario_id INT,
    modalidad ENUM('Recojo en Local', 'Envio a Provincia') NOT NULL,
    agencia_transporte VARCHAR(100),
    guia_remision VARCHAR(50),
    fecha_despacho TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cotizacion_id) REFERENCES cotizaciones(id) ON DELETE CASCADE,
    FOREIGN KEY (operario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);