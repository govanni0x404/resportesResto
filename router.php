<?php
/**
 * 18-11-2026: router para el servidor de desarrollo de PHP. Uso:
 *
 *     php -S 0.0.0.0:8001 router.php
 *
 * `php -S` no lee los .htaccess: sin esto, en la red del local se podían descargar el historial de git (.git), los
 * respaldos, los scripts SQL y el código (application/). Aquí van las mismas reglas del .htaccess de la raíz: lo
 * prohibido responde 404 y todo lo demás sigue igual que sin router (archivos de assets/ y rutas index.php/...).
 * En Apache o Nginx (VPS) este archivo no se usa: allá están .htaccess y docker/*-seguridad.conf.
 */
if (PHP_SAPI !== 'cli-server') {
	http_response_code(404);
	exit;
}

// Sin parse_url: con "//.git/config" lo toma como nombre de servidor y la ruta queda "/config" (se colaba el .git).
// Se quita la consulta (?...), se decodifica (%2e = .) y se juntan las barras repetidas.
$ruta = isset($_SERVER['REQUEST_URI']) ? (string)$_SERVER['REQUEST_URI'] : '/';
$ruta = explode('?', $ruta, 2)[0];
$ruta = rawurldecode($ruta);
$ruta = '/' . ltrim(preg_replace('#/+#', '/', str_replace('\\', '/', $ruta)), '/');

$prohibido = array(
	// Archivos y carpetas ocultos: .git, .claude, .env, .htaccess... (menos .well-known)
	'#(^|/)\.(?!well-known(/|$))#i',
	// Respaldos del usuario
	'#(^|/)_respaldos[^/]*(/|$)#i',
	// Código, scripts de la base, instaladores (se descargan por la app) y configuración de docker
	'#^/(application|system|instaladores|sql|docker|tests)(/|$)#i',
	// Archivos del proyecto en la raíz
	'#^/(docker-compose\.ya?ml|composer\.(json|lock)|readme\.rst|license\.txt|c\.txt|router\.php)$#i',
	// Tipos que nunca son parte de la página
	'#\.(sql|bak|old|orig|swp|log|sh|bat|ps1|key|pem|env|ini|dist)$#i',
	// uploads/: solo imágenes, nunca código
	'#^/uploads/.*\.(php[0-9]?|phtml|phar|pl|py|cgi)$#i',
);
foreach ($prohibido as $patron) {
	if (preg_match($patron, $ruta)) {
		http_response_code(404);
		header('Content-Type: text/plain; charset=utf-8');
		echo 'No existe';
		return true;
	}
}

// Todo lo demás: como php -S sin router (sirve el archivo, o index.php con la ruta)
return false;
