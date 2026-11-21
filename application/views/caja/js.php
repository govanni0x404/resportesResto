<script>
	(function () {
<?php
/* v1.27.0: el JavaScript de la Caja está dividido por tema en application/views/caja/js/ (antes eran 6.100 líneas aquí).
 * PHP junta las partes en este mismo lugar: el navegador recibe exactamente el mismo código, en el mismo orden y dentro de
 * la misma función, sin archivos extra que descargar, así que la Caja y la impresión van igual de rápidas.
 * El orden de las partes importa (cada una usa lo que definen las anteriores): no cambiarlo. La prueba
 * tests/caja_js_test.php revisa que estén todas y que juntas formen JavaScript válido. */
include __DIR__ . '/js/00_inicio.php'; ?>
<?php include __DIR__ . '/js/01_utilidades.php'; ?>
<?php include __DIR__ . '/js/02_impresion.php'; ?>
<?php include __DIR__ . '/js/03_delivery.php'; ?>
<?php include __DIR__ . '/js/04_estado_catalogo.php'; ?>
<?php include __DIR__ . '/js/05_granel_agregados_menu.php'; ?>
<?php include __DIR__ . '/js/06_promo_sushi.php'; ?>
<?php include __DIR__ . '/js/07_carro.php'; ?>
<?php include __DIR__ . '/js/08_paneles_documentos_favoritos.php'; ?>
<?php include __DIR__ . '/js/09_cliente_pago_factura.php'; ?>
<?php include __DIR__ . '/js/10_pago_electronico.php'; ?>
<?php include __DIR__ . '/js/11_mesas.php'; ?>
<?php include __DIR__ . '/js/12_dividir_cuenta.php'; ?>
<?php include __DIR__ . '/js/13_ventas_creditos_devoluciones.php'; ?>
<?php include __DIR__ . '/js/14_cierre_inventario.php'; ?>
<?php include __DIR__ . '/js/15_espera_salida.php'; ?>
<?php include __DIR__ . '/js/16_stock_bajo.php'; ?>
	})();
</script>
