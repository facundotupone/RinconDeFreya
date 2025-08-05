-- Script para agregar el campo price_sale a la tabla products
-- Ejecutar este script en la base de datos para agregar la funcionalidad de liquidación

ALTER TABLE products ADD COLUMN price_sale DECIMAL(10,2) NULL AFTER price;

-- Opcional: Agregar comentario para documentar el campo
ALTER TABLE products MODIFY COLUMN price_sale DECIMAL(10,2) NULL 
COMMENT 'Precio promocional/liquidación (NULL si no está en liquidación)';
