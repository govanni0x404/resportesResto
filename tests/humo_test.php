<?php
/**
 * Prueba de humo de la web (v1.20.0): entra con el login local y abre todas las pantallas y consultas de datos
 * buscando errores de PHP, de la base o respuestas 500. SOLO LEE: llama funciones que listan, buscan o muestran
 * (listado*, obtener*, buscar*, resumen*, total*, detalle*...), nunca las que graban, anulan o cierran.
 *
 *   1) prende la web en modo local (RESTO_CONEXION=2 en .env.php):  C:\xampp\php\php.exe -S localhost:8001 router.php
 *   2) en otra consola:                                              C:\xampp\php\php.exe tests\humo_test.php
 *      (otra dirección: C:\xampp\php\php.exe tests\humo_test.php http://localhost:8082/index.php)
 *
 * Termina con código 0 si no encontró errores y 1 si encontró alguno.
 */
error_reporting(E_ALL);
$base = rtrim($argv[1] ?? 'http://localhost:8001/index.php', '/');
$raiz = dirname(__DIR__);
$cookies = tempnam(sys_get_temp_dir(), 'humo');
$fecha = date('Y-m-d');
$fechas = array('desde' => date('Y-m-01'), 'hasta' => $fecha, 'id_caja' => 0, 'mes' => (int)date('n'), 'anio' => (int)date('Y'));

function pedir($url, $post = null, $token = '')
{
	global $cookies;
	$ch = curl_init($url);
	curl_setopt_array($ch, array(CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEJAR => $cookies, CURLOPT_COOKIEFILE => $cookies,
		CURLOPT_TIMEOUT => 60, CURLOPT_HTTPHEADER => array_filter(array($token ? 'X-CSRF-Token: ' . $token : null, 'X-Requested-With: XMLHttpRequest'))));
	if ($post !== null) { curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post)); }
	$cuerpo = (string)curl_exec($ch);
	$codigo = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	return array($codigo, $cuerpo);
}

function errores($cuerpo)
{
	preg_match_all('/(A PHP Error was encountered|A Database Error Occurred|Fatal error|An uncaught Exception|Message:\s*[^<]{0,160}|error en la base de datos[^"<]{0,160})/i', $cuerpo, $m);
	return array_unique($m[0]);
}

// Login local: el primer usuario de la lista
list($c, $html) = pedir($base . '/login/dev');
if ($c !== 200 || !preg_match('/name="token" value="([a-f0-9]+)".*?name="id_usuario" value="(\d+)"/s', $html, $m)) {
	echo "No pude entrar por $base/login/dev (código $c). ¿Está prendida la web y en modo local (RESTO_CONEXION=2)?\n";
	exit(1);
}
pedir($base . '/login/dev', array('token' => $m[1], 'id_usuario' => $m[2]));
list($c, $html) = pedir($base . '/Dashboard');
if (!preg_match('/csrf-web" content="([a-f0-9]+)"/', $html, $t)) { echo "Entré, pero el Dashboard no cargó (código $c)\n"; exit(1); }
$token = $t[1];

$saltar = array('Login', 'Welcome', 'Despliegue', 'ApiMovil', 'AgenteImpresion', 'ImagenImpresion', 'ImagenProducto');
$leer = '/^(listado|listar|obtener|cargar|buscar|ver|get|consultar|resumen|total|detalle|mostrar|traer|lista)/i';
// ean13: obtenerEAN13 avanza el correlativo de códigos de barras en la base (no es solo lectura)
$escribe = '/grabar|guardar|eliminar|borrar|anular|actualizar|crear|insert|update|delete|cerrar|abrir|cobrar|pagar|imprimir|enviar|liberar|mover|pdf|ean13/i';
$fallas = array();
$revisadas = 0;
foreach (glob($raiz . '/application/controllers/*.php') as $archivo) {
	$ctrl = basename($archivo, '.php');
	if (in_array($ctrl, $saltar, true)) continue;
	preg_match_all('/public function (\w+)\s*\(/', file_get_contents($archivo), $metodos);
	if (in_array('index', $metodos[1], true)) {
		list($c, $cuerpo) = pedir("$base/$ctrl");
		$revisadas++;
		if ($c >= 500 || errores($cuerpo)) $fallas[] = "$ctrl (pantalla, $c): " . implode(' | ', errores($cuerpo));
	}
	foreach ($metodos[1] as $met) {
		if (!preg_match($leer, $met) || preg_match($escribe, $met)) continue;
		list($c, $cuerpo) = pedir("$base/$ctrl/$met", $fechas, $token);
		$revisadas++;
		if ($c >= 500 || errores($cuerpo)) $fallas[] = "$ctrl/$met ($c): " . implode(' | ', errores($cuerpo));
	}
}
@unlink($cookies);

if ($fallas) {
	echo count($fallas) . " de $revisadas con error:\n - " . implode("\n - ", $fallas) . "\n";
	exit(1);
}
echo "OK: $revisadas pantallas y consultas sin errores en $base\n";
exit(0);
