-- Solución: Configurar precio del Plan Pro
-- Ejecuta esto en tu base de datos MySQL

-- Ver el plan actual
SELECT id, name, code, prices, price_cents FROM plans WHERE id = 1;

-- Opción 1: Actualizar usando el campo prices (JSON)
UPDATE plans 
SET prices = '{"ARS": 1500000}' 
WHERE id = 1;
-- Esto establece $15,000 ARS (en centavos: 1,500,000)

-- Opción 2: Si el plan usa price_cents
UPDATE plans 
SET price_cents = 1500000 
WHERE id = 1;
-- $15,000 ARS

-- Verificar el cambio
SELECT id, name, code, prices, price_cents FROM plans WHERE id = 1;
