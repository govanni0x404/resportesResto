-- Intranet mínima para iniciar sesión sin la base remota (ccerda_intranet2).
-- Solo tiene lo que usa Mlogin: software, cliente, sucursal y usuario_appweb.
-- Crear y cargar:
--   createdb -h localhost -U postgres intranet_local
--   psql -h localhost -U postgres -d intranet_local -f sql/intranet_local.sql
-- y en application/config/database.php apuntar $db['default'] a localhost / intranet_local.

CREATE TABLE IF NOT EXISTS software (
	id_software integer PRIMARY KEY,
	nombre_software varchar(100),
	abreviatura varchar(20)
);

CREATE TABLE IF NOT EXISTS cliente (
	id_cliente integer PRIMARY KEY,
	nombre_cliente varchar(150),
	nombre_fantasia varchar(150),
	rut integer,
	dv varchar(1)
);

CREATE TABLE IF NOT EXISTS sucursal (
	id_sucursal integer PRIMARY KEY,
	id_cliente integer REFERENCES cliente (id_cliente),
	nombre_sucursal varchar(100)
);

-- nombre_conexion = grupo de database.php que se usa después del login
-- id_usuario_cliente = usuario.id_usuario en la base del cliente (define el rol)
CREATE TABLE IF NOT EXISTS usuario_appweb (
	id_usuario_appweb serial PRIMARY KEY,
	id_cliente integer REFERENCES cliente (id_cliente),
	id_software integer REFERENCES software (id_software),
	correo_acceso varchar(100),
	clave_acceso varchar(100),
	nombre_usuario varchar(100),
	nombre_conexion varchar(50),
	id_usuario_cliente integer,
	autoriza_cambio_conexion boolean DEFAULT false
);

INSERT INTO software VALUES (5, 'RESTO WEB', 'RESTO') ON CONFLICT DO NOTHING;
INSERT INTO cliente VALUES (1, 'PANADERIA Y PASTELERIA GUSTAVO SANCHEZ EIRL', 'LA TOSCANA', 76193938, '6') ON CONFLICT DO NOTHING;
INSERT INTO sucursal VALUES (1, 1, 'CASA MATRIZ') ON CONFLICT DO NOTHING;

-- Usuarios de prueba: DATAMAULE (gerencia, usuario 1) y CAJA (cajero, usuario 2) de laToscana
INSERT INTO usuario_appweb (id_cliente, id_software, correo_acceso, clave_acceso, nombre_usuario, nombre_conexion, id_usuario_cliente)
SELECT 1, 5, 'admin@local', 'admin', 'DATAMAULE', '76193938-6', 1
WHERE NOT EXISTS (SELECT 1 FROM usuario_appweb WHERE correo_acceso = 'admin@local');
INSERT INTO usuario_appweb (id_cliente, id_software, correo_acceso, clave_acceso, nombre_usuario, nombre_conexion, id_usuario_cliente)
SELECT 1, 5, 'caja@local', 'caja', 'CAJA', '76193938-6', 2
WHERE NOT EXISTS (SELECT 1 FROM usuario_appweb WHERE correo_acceso = 'caja@local');
