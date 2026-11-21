<?php
/**
 * Ayudas compartidas por las pruebas automáticas (v1.27.0).
 *   - entrar(): entra con el login local (/login/dev, primer usuario) y deja la clave anti-CSRF lista.
 *   - pedir($url, $post): GET o POST con la misma sesión; devuelve array(código, cuerpo).
 *   - revisar($nombre, $ok, $detalle): anota una prueba; terminar() muestra el resumen y sale con 0 (todo bien) o 1.
 * La dirección se puede cambiar con el primer argumento: php tests\algo_test.php http://localhost:8082/index.php
 */
error_reporting(E_ALL);
date_default_timezone_set(getenv('RESTO_ZONA_HORARIA') ?: 'America/Santiago');   // la misma hora que la web (index.php)
if (!defined('BASE')) define('BASE', rtrim($GLOBALS['argv'][1] ?? 'http://localhost:8001/index.php', '/'));
$GLOBALS['__cookies'] = tempnam(sys_get_temp_dir(), 'prueba');
$GLOBALS['__token'] = '';
$GLOBALS['__fallas'] = array();
$GLOBALS['__total'] = 0;
register_shutdown_function(function () { @unlink($GLOBALS['__cookies']); });

function pedir($url, $post = null, $conToken = true, $cabeceras = array())
{
	$ch = curl_init($url);
	$h = array('X-Requested-With: XMLHttpRequest');
	if ($conToken && $GLOBALS['__token']) $h[] = 'X-CSRF-Token: ' . $GLOBALS['__token'];
	curl_setopt_array($ch, array(CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEJAR => $GLOBALS['__cookies'],
		CURLOPT_COOKIEFILE => $GLOBALS['__cookies'], CURLOPT_TIMEOUT => 60, CURLOPT_HTTPHEADER => array_merge($h, $cabeceras)));
	if ($post !== null) { curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post)); }
	$cuerpo = (string)curl_exec($ch);
	$codigo = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	return array($codigo, $cuerpo);
}

/** GET sin la cabecera de AJAX (como cuando se abre la página en el navegador). Devuelve array(código, cuerpo, Location). */
function abrirPagina($url)
{
	$ch = curl_init($url);
	curl_setopt_array($ch, array(CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_COOKIEJAR => $GLOBALS['__cookies'],
		CURLOPT_COOKIEFILE => $GLOBALS['__cookies'], CURLOPT_TIMEOUT => 60));
	$r = (string)curl_exec($ch);
	$codigo = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$tam = (int)curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	curl_close($ch);
	$loc = preg_match('/^Location:\s*(\S+)/mi', substr($r, 0, $tam), $m) ? $m[1] : '';
	return array($codigo, substr($r, $tam), $loc);
}

/** Entra con el login local. $idUsuario = null: el primer usuario de la lista (gerencia en la base de pruebas). */
function entrar($idUsuario = null)
{
	salir();
	list($c, $html) = pedir(BASE . '/login/dev', null, false);
	$hay = $c === 200 && preg_match('/name="token" value="([a-f0-9]+)".*?name="id_usuario" value="(\d+)"/s', $html, $m);
	// Otro usuario: la clave de la página es la misma para todos; tiene que estar en la lista
	if ($hay && $idUsuario !== null) {
		$hay = strpos($html, 'name="id_usuario" value="' . (int)$idUsuario . '"') !== false;
		$m[2] = (int)$idUsuario;
	}
	if (!$hay) {
		echo "No pude entrar por " . BASE . "/login/dev (código $c). ¿Está prendida la web y en modo local (RESTO_CONEXION=2)?\n";
		exit(1);
	}
	pedir(BASE . '/login/dev', array('token' => $m[1], 'id_usuario' => $m[2]), false);
	// La clave anti-CSRF sale de una página con menú: el Dashboard o, si el rol no lo tiene, Mi perfil (la ven todos)
	foreach (array('/Dashboard', '/PerfilDeUsuario') as $pagina) {
		list($c, $html) = pedir(BASE . $pagina, null, false);
		if (preg_match('/csrf-web" content="([a-f0-9]+)"/', $html, $t)) break;
	}
	if (empty($t)) { echo "Entré, pero no cargó ninguna página con menú (código $c)\n"; exit(1); }
	$GLOBALS['__token'] = $t[1];
	return $t[1];
}

/** Cierra la sesión de las pruebas (borra las cookies): sirve para probar qué pasa con la sesión vencida. */
function salir()
{
	file_put_contents($GLOBALS['__cookies'], '');
	$GLOBALS['__token'] = '';
}

function json($cuerpo)
{
	$j = json_decode($cuerpo, true);
	return $j === null ? array() : $j;
}

function revisar($nombre, $ok, $detalle = '')
{
	$GLOBALS['__total']++;
	echo ($ok ? '  ok   ' : '  FALLA') . " $nombre" . (!$ok && $detalle !== '' ? "\n         -> $detalle" : '') . "\n";
	if (!$ok) $GLOBALS['__fallas'][] = $nombre;
}

function terminar($titulo)
{
	$f = $GLOBALS['__fallas'];
	if ($f) { echo count($f) . ' de ' . $GLOBALS['__total'] . " pruebas fallaron en $titulo\n"; exit(1); }
	echo 'OK: ' . $GLOBALS['__total'] . " pruebas de $titulo sin fallas\n";
	exit(0);
}
