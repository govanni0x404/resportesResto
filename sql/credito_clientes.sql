-- Crédito a clientes: plazo máximo y pagos de crédito desde la Caja web.
-- Se corre una vez en la base de CADA cliente (ej. laToscana):
--   psql -h localhost -U postgres -d laToscana -f sql/credito_clientes.sql
-- Se puede correr más de una vez: no duplica nada ni borra datos.

-- 1) Plazo máximo del crédito del cliente, en días (0 = sin plazo). Por defecto 30 días.
ALTER TABLE cliente ADD COLUMN IF NOT EXISTS plazo_credito integer NOT NULL DEFAULT 30;

-- 2) Cada pago de crédito que se recibe en la caja (cabecera)
CREATE TABLE IF NOT EXISTS pago_credito_cliente (
	id_pago_credito serial PRIMARY KEY,
	id_cliente integer NOT NULL,
	id_caja integer NOT NULL,
	id_usuario integer NOT NULL,
	id_fpago integer NOT NULL,
	monto integer NOT NULL,
	paga_con integer NOT NULL DEFAULT 0,
	vuelto integer NOT NULL DEFAULT 0,
	fecha timestamp NOT NULL DEFAULT now(),
	observacion varchar(200),
	id_detalle_caja integer
);
CREATE INDEX IF NOT EXISTS pago_credito_cliente_cliente ON pago_credito_cliente (id_cliente);
CREATE INDEX IF NOT EXISTS pago_credito_cliente_caja ON pago_credito_cliente (id_caja);

-- 3) Qué ventas (créditos) pagó cada pago y cuánto de cada una
CREATE TABLE IF NOT EXISTS detalle_pago_credito (
	id_detalle_pago_credito serial PRIMARY KEY,
	id_pago_credito integer NOT NULL REFERENCES pago_credito_cliente (id_pago_credito) ON DELETE CASCADE,
	id_ctacte_cliente integer NOT NULL,
	id_venta integer NOT NULL,
	monto integer NOT NULL
);
CREATE INDEX IF NOT EXISTS detalle_pago_credito_pago ON detalle_pago_credito (id_pago_credito);
CREATE INDEX IF NOT EXISTS ctacte_cliente_cliente ON ctacte_cliente (id_cliente);
