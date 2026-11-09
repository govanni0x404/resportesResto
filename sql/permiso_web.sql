-- Permisos de la web por rol (pantalla Herramientas > Privilegios, solo gerencia).
-- Se corre una vez en la base de CADA cliente (ej. laToscana):
--   psql -h localhost -U postgres -d laToscana -f sql/permiso_web.sql
--
-- Deja a los roles 2, 3, 4... con los mismos accesos que tienen hoy (todo, menos Usuarios y Configuraciones).
-- Después gerencia los ajusta desde la web. Se puede correr más de una vez: no duplica ni pisa lo ya configurado.

CREATE TABLE IF NOT EXISTS permiso_web (
	id_rol integer NOT NULL,   -- id de la tabla rol (sin llave foránea: en rol la llave es nombre_rol, no id_rol)
	modulo varchar(60) NOT NULL,
	autoriza boolean NOT NULL DEFAULT false,
	PRIMARY KEY (id_rol, modulo)
);

INSERT INTO permiso_web (id_rol, modulo, autoriza)
SELECT r.id_rol, m.modulo, m.modulo NOT IN ('Usuario', 'Configuraciones')
FROM (SELECT DISTINCT id_rol FROM rol WHERE id_rol IS NOT NULL) r
CROSS JOIN (VALUES ('Dashboard'), ('Caja'), ('Mesas'), ('EntradaBodega'), ('SalidaBodega'), ('Compras'), ('Inventario'),
	('Productos'), ('Insumos'), ('Recetas'), ('ListaDePrecios'), ('Cliente'), ('Proveedor'), ('Recintos'), ('MesasPorRecinto'),
	('Agregados'), ('Bodegas'), ('CanalDeVenta'), ('CentroCosto'), ('Comuna'), ('Provincias'), ('Delivery'), ('DestinoDeImpresion'),
	('GastoEventos'), ('IngresoEventos'), ('TipoEvento'), ('Familias'), ('FamiliasInsumos'), ('FamiliasReceta'), ('SubFamilias'),
	('FormasDePago'), ('DocumentosTributarios'), ('Impuestos'), ('MensajesPorProductos'), ('Moneda'), ('Turnos'), ('Umedida'),
	('ItemGastosMensuales'), ('InsumosPorBodega'), ('DetalleOperaciones'), ('CierresDeCaja'), ('ResumenDeCompras'),
	('ConsolidadoVentas'), ('VentasPorProducto'), ('PropinasPorGarzon'), ('ResumenAnulaciones'), ('ListadoDeClientes'),
	('VentasMensualesTotalizadas'), ('ResumenVentasProductosMes'), ('ResumenEntradaBodega'), ('ResumenSalidaBodega'),
	('Usuario'), ('Configuraciones')) AS m (modulo)
WHERE r.id_rol <> 1
ON CONFLICT (id_rol, modulo) DO NOTHING;
