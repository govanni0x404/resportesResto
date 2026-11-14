<?php
/*
 * Impresión local de la Caja (12-11-2026): cliente del agente de impresión del equipo (como mipdv_web). La dirección y
 * la clave del agente solo se entregan con Configuraciones > Imprimir documentos en el equipo de la caja marcado
 * (Caja::index); sin eso la Caja imprime como antes. Uso: views/caja/js.php (imprimirTicket).
 */
?>
<?php if (!empty($impresion_local)) : ?>
<script>
	window.LOCAL_PRINT_CONFIG = Object.assign({}, window.LOCAL_PRINT_CONFIG || {},
		<?php echo json_encode(array('baseUrl' => $impresion_local['url'], 'apiKey' => $impresion_local['clave']), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>);
</script>
<script src="<?php echo base_url() ?>assets/js/impresion-local/LocalPrintClient.js?v=1"></script>
<script src="<?php echo base_url() ?>assets/js/impresion-local/printerJob.js?v=1"></script>
<script src="<?php echo base_url() ?>assets/js/impresion-local/impresionLocal.js?v=1"></script>
<?php endif; ?>
