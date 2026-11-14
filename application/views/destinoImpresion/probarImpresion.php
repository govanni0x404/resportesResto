<?php
/*
 * Probar impresión directa en este equipo (12-11-2026), como mipdv_web: el navegador le manda el trabajo al agente de
 * impresión local (http://localhost:5050) con PrinterJob (assets/js/impresion-local). Por ahora solo en Destino de
 * impresión: imprime una hoja de prueba con los datos del formulario, aunque todavía no se graben.
 *
 * Impresora: la de Windows de "Ruta para Windows" (//equipo/POS80 => POS80) si este equipo la tiene; si no, la IP y el
 * puerto (impresora de red). Es el mismo orden que usa el agente de la cola (assets/agente-impresion/agente.ps1).
 */
$__flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
?>
<script>
	window.LOCAL_PRINT_CONFIG = Object.assign({}, window.LOCAL_PRINT_CONFIG || {},
		<?php echo json_encode(array('baseUrl' => $impresion_local['url'], 'apiKey' => $impresion_local['clave']), $__flags); ?>);
</script>
<script src="<?php echo base_url() ?>assets/js/impresion-local/LocalPrintClient.js?v=1"></script>
<script src="<?php echo base_url() ?>assets/js/impresion-local/printerJob.js?v=1"></script>
<script src="<?php echo base_url() ?>assets/js/impresion-local/impresionLocal.js?v=1"></script>
<script>
	(function () {
		var USUARIO = <?php echo json_encode($usuario, $__flags); ?>, EMPRESA = <?php echo json_encode($empresa, $__flags); ?>;
		var probando = false;

		function esc(t) {
			return String(t == null ? '' : t).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; });
		}
		function aviso(tipo, html, titulo) {
			showAppAlert(tipo, html, { title: titulo, duration: tipo === 'success' ? 4000 : 9000, image: base_url + 'assets/img/' + (tipo === 'success' ? 'success.gif' : 'errorGif.gif') });
		}
		function miles(n, dec) {
			var p = Number(n).toFixed(dec || 0).split('.');
			return p[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',') + (p[1] ? '.' + p[1] : '');
		}
		function fila(izq, der, cols) {
			izq = String(izq); der = String(der);
			var libre = cols - izq.length - der.length;
			return libre >= 1 ? izq + new Array(libre + 1).join(' ') + der : izq.slice(0, Math.max(0, cols - der.length - 1)) + ' ' + der;
		}
		function ahora() {
			var d = new Date(), z = function (n) { return (n < 10 ? '0' : '') + n; };
			return z(d.getDate()) + '/' + z(d.getMonth() + 1) + '/' + d.getFullYear() + ' ' + z(d.getHours()) + ':' + z(d.getMinutes()) + ':' + z(d.getSeconds());
		}
		/** Hoja de prueba (operaciones de PrinterJob) */
		function hojaPrueba(destino, impresora, mm) {
			var cols = mm === 58 ? 28 : 41, job = new PrinterJob({});
			job.fontsize(1).textaling('center').fontbold(true).texto('PRUEBA DE IMPRESION').fontbold(false);
			if (EMPRESA) job.texto(EMPRESA);
			job.texto('dat@Resto').linea({ caracter: '-', ancho: cols, ancho_maximo: cols })
				.textaling('left')
				.texto(fila('Destino:', destino, cols))
				.texto('Impresora: ' + impresora)
				.texto(fila('Papel:', mm + ' mm (' + cols + ' columnas)', cols))
				.texto(fila('Fecha:', ahora(), cols))
				.texto(fila('Usuario:', USUARIO, cols))
				.linea({ caracter: '-', ancho: cols, ancho_maximo: cols })
				.texto('Acentos: áéíóú ÁÉÍÓÚ ñ Ñ ü')
				.texto(fila('Subtotal', '$' + miles(1234567.5, 2), cols))
				.fontbold(true).texto(fila('TOTAL', '$' + miles(1469135), cols)).fontbold(false)
				.linea({ caracter: '=', ancho: cols, ancho_maximo: cols })
				.textaling('center').texto('Si lees esto, la impresora funciona')
				.feed(4).cut();
			return job.operaciones;
		}

		window.probarImpresionLocal = async function () {
			if (probando) return;
			if (!window.ImpresionLocal || !window.PrinterJob) { aviso('error', 'No se cargó el cliente de impresión local. Recarga la página.'); return; }
			var destino = {
				nombre: $.trim($('#nombreDestinoDeImpresion').val()) || 'SIN NOMBRE',
				ruta_windows: $.trim($('#RutaWindowsImpresora').val()), ip: $.trim($('#rutaImpresora').val()), puerto: parseInt($('#PuertoImpresora').val(), 10) || 0
			};
			if (!destino.ruta_windows && !(ImpresionLocal.esIp(destino.ip) && destino.puerto > 0)) { aviso('error', 'Indica la ruta para Windows o la IP y el puerto de la impresora.'); return; }
			var $b = $('#btnProbarImpresion'), html = $b.html();
			probando = true;
			$b.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Imprimiendo…');
			try {
				// La conexión se averigua primero para poner en la hoja la impresora y el ancho del papel
				var con = await ImpresionLocal.conexion(destino);
				var mm = ImpresionLocal.anchoMm(con.nombre);
				var r = await ImpresionLocal.imprimir(destino, hojaPrueba(destino.nombre, con.impresora, mm));
				aviso('success', 'Se envió la hoja de prueba a <b>' + esc(r.impresora) + '</b>.', 'Impresión enviada');
			} catch (e) {
				aviso('error', ImpresionLocal.mensaje(e), 'No se pudo imprimir');
			} finally {
				probando = false;
				$b.prop('disabled', false).html(html);
			}
		};
	})();
</script>
