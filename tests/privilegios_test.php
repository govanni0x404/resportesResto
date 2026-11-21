<?php
/**
 * Prueba de privilegios de lo nuevo (v1.29.2): Cocina, Reservas, Respaldos, aviso de stock y bloqueo por reserva.
 *
 * Usa un rol que NO es gerencia. En una COPIA de la base, crea un rol nuevo y su usuario con solo el módulo Cocina
 * (ojo: en la tabla rol la llave es el nombre; usa un id_rol que no exista, si no hereda los permisos de ese rol):
 *   INSERT INTO rol (id_rol, nombre_rol) VALUES (9, 'COCINERO');
 *   INSERT INTO usuario (id_usuario, nombre_usuario, clave, esadmin, esgarzon, id_rol, vigente) VALUES (2, 'COCINERO', '1234', false, false, 9, true);
 *   INSERT INTO permiso_web (id_rol, modulo, autoriza) VALUES (9, 'Cocina', true);
 * y corre:
 *   php tests/privilegios_test.php http://localhost:8002/index.php COPIA
 *
 * Revisa que el cocinero vea y use solo lo suyo, que lo demás le responda "sin permiso" (403) aunque escriba la
 * dirección a mano, y que al darle Reservas en Privilegios le aparezca.
 */
require __DIR__ . '/lib_pruebas.php';
if (($argv[2] ?? '') !== 'COPIA') {
	echo "Esta prueba cambia permisos y configuraciones. Córrela solo contra una COPIA de la base:\n"
		. "  php tests/privilegios_test.php http://localhost:8002/index.php COPIA\n";
	exit(1);
}
const COCINERO = 2;       // id_usuario
const ROL_COCINERO = 9;   // id_rol

echo "Gerencia:\n";
entrar();
list($c, ) = pedir(BASE . '/Configuraciones/guardar', array('usar_cocina_web' => '1', 'usar_reservas_web' => '1'));
revisar('enciende Cocina y Reservas en Configuraciones', $c === 200, "código $c");
// Punto de partida (la prueba se puede repetir): el cocinero sin Reservas, con Cocina
pedir(BASE . '/PrivilegiosUsuario/guardar', array('id_rol' => ROL_COCINERO, 'modulo' => 'Reservas', 'autoriza' => '0'));
pedir(BASE . '/PrivilegiosUsuario/guardar', array('id_rol' => ROL_COCINERO, 'modulo' => 'Cocina', 'autoriza' => '1'));
// La pantalla pide la lista de módulos del rol aparte (ListarPrivilegiosPorRol)
list($c, $b) = pedir(BASE . '/PrivilegiosUsuario/ListarPrivilegiosPorRol', array('id_rol' => ROL_COCINERO));
$nombres = array();
foreach ((json($b)['grupos'] ?? array()) as $g) foreach ($g['modulos'] as $m) $nombres[$m['modulo']] = $m;
revisar('Privilegios de Usuario ofrece "Pantalla de cocina" y "Reservas de mesas"', $c === 200
	&& ($nombres['Cocina']['nombre'] ?? '') === 'Pantalla de cocina' && ($nombres['Reservas']['nombre'] ?? '') === 'Reservas de mesas', "código $c");
revisar('y muestra lo que tiene el rol: Cocina sí, Reservas no', !empty($nombres['Cocina']['autoriza']) && empty($nombres['Reservas']['autoriza']));
revisar('sin la marca "(prueba)" de antes', stripos($b, 'prueba') === false);
list($c, ) = pedir(BASE . '/Configuraciones/respaldos');
revisar('gerencia ve los respaldos', $c === 200, "código $c");

echo "Cocinero (solo el módulo Cocina):\n";
entrar(COCINERO);
list($c, $html) = pedir(BASE . '/Cocina', null, false);
revisar('abre la pantalla de cocina', $c === 200 && strpos($html, 'id="kGrilla"') !== false, "código $c");
list($c, ) = pedir(BASE . '/Cocina/pendientes');
revisar('ve las comandas por preparar', $c === 200, "código $c");
revisar('su menú muestra Cocina', strpos($html, 'index.php/Cocina"') !== false);
revisar('su menú NO muestra Reservas, Caja ni Configuraciones', strpos($html, 'index.php/Reservas"') === false
	&& strpos($html, 'index.php/Caja"') === false && strpos($html, 'index.php/Configuraciones"') === false);

$prohibidas = array(
	array('GET', '/Reservas', 'la pantalla de Reservas'),
	array('GET', '/Reservas/listado', 'la lista de reservas (nombres y teléfonos)'),
	array('POST', '/Reservas/guardar', 'crear una reserva'),
	array('POST', '/Reservas/estado', 'cambiar una reserva'),
	array('GET', '/Caja/stockBajo', 'el aviso de stock de la Caja'),
	array('POST', '/Caja/reservaLlego', 'desbloquear una mesa reservada'),
	array('POST', '/Caja/enviarMesa', 'enviar pedidos a una mesa'),
	array('GET', '/Configuraciones/respaldos', 'la lista de respaldos'),
	array('POST', '/Configuraciones/respaldarAhora', 'hacer un respaldo'),
	array('GET', '/Configuraciones/descargarRespaldo?archivo=x.dump', 'descargar un respaldo'),
	array('POST', '/Configuraciones/guardar', 'cambiar las configuraciones'),
	array('GET', '/Insumos/ListadoInsumos', 'la lista de insumos (costos)'),
	array('POST', '/Insumos/grabarInsumo', 'grabar un insumo (stock mínimo)'),
	array('GET', '/PrivilegiosUsuario', 'Privilegios de Usuario'),
);
foreach ($prohibidas as $p) {
	list($c, $b) = pedir(BASE . $p[1], $p[0] === 'POST' ? array('id' => 1) : null);
	$aviso = strpos($b, 'No tienes permiso') !== false || strpos($b, 'Sin permiso') !== false || strpos($b, 'Solo un administrador') !== false
		|| strpos($b, 'no tiene acceso') !== false;
	revisar("NO puede: {$p[2]}", $c === 403 || ($c === 200 && $aviso), "código $c: " . substr(strip_tags($b), 0, 120));
}

echo "Gerencia le da Reservas:\n";
entrar();
list($c, $b) = pedir(BASE . '/PrivilegiosUsuario/guardar', array('id_rol' => ROL_COCINERO, 'modulo' => 'Reservas', 'autoriza' => '1'));
revisar('gerencia le da "Reservas de mesas" en Privilegios', $c === 200 && !empty(json($b)['ok']), "código $c: $b");
$guardoPorPantalla = true;
entrar(COCINERO);
list($c, $html) = pedir(BASE . '/Reservas', null, false);
list($c2, ) = pedir(BASE . '/Reservas/listado');
revisar('con el módulo Reservas, el cocinero ya entra a Reservas', ($c === 200 && $c2 === 200) || !$guardoPorPantalla, "códigos $c / $c2");

echo "Apagada en Configuraciones:\n";
entrar();
pedir(BASE . '/Configuraciones/guardar', array('_casillas' => 'usar_cocina_web'));
entrar(COCINERO);
list($c, $html) = pedir(BASE . '/Cocina/pendientes');
revisar('con la cocina apagada, el cocinero no recibe comandas aunque tenga el permiso', $c === 409, "código $c");
list($c, $html) = pedir(BASE . '/PerfilDeUsuario', null, false);
revisar('y Cocina ya no sale en su menú', strpos($html, 'index.php/Cocina"') === false);
entrar();
pedir(BASE . '/Configuraciones/guardar', array('usar_cocina_web' => '1'));

terminar('privilegios');
