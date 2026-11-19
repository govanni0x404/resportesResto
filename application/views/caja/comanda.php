<?php
// Comanda web: una hoja por destino de impresión (cocina, barra...) con cada producto y su mensaje.
// 19-11-2026: más datos arriba (fecha, hora, recinto, pedido, caja, quién atiende) para que se lea mejor y ocupe más
// papel; la letra quedó del tamaño de antes (más grande se veía mal).
// 16-11-2026: diseño para leer de lejos en la cocina (mesa y productos grandes) y con los avisos de anulación, cambio de
// mesa y reenvío. Se imprime desde el navegador cuando el destino usa la misma impresora de la caja (Configuraciones >
// Boletas y precuentas con el diseño de la página); con el acceso "Caja Web" sale sin el cuadro de impresión.
$h = function ($s) {
	return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
};
$cant = function ($n) {
	return rtrim(rtrim(number_format((float)$n, 3, ',', ''), '0'), ',');
};
$papel = isset($papel) ? (int)$papel : 80;
$v = isset($venta) ? $venta : null;
$hora = $v && !empty($v->fecha_texto) ? $v->fecha_texto : date('d/m/Y H:i');
$mesa = isset($mesa) ? trim((string)$mesa) : '';
$aviso = isset($aviso) && is_array($aviso) ? $aviso : null;
$tipo = $aviso ? (string)$aviso['tipo'] : '';
$info = isset($info) && is_array($info) ? $info : array();
// "MENSAJE DE LA MESA: texto" (Caja::observacionMesa): la etiqueta va chica arriba del texto
$obsTexto = isset($observacion) ? trim((string)$observacion) : '';
$obsEtiqueta = '';
if (preg_match('/^(MENSAJE DE LA MESA):\s*(.*)$/su', $obsTexto, $mm)) { $obsEtiqueta = 'Mensaje de la mesa'; $obsTexto = $mm[2]; }
$titulos = array('anulacion' => array('ANULACIÓN', 'NO PREPARAR'), 'traslado' => array('CAMBIO DE MESA', 'ENTREGAR EN LA MESA NUEVA'),
	'reenvio' => array('REENVÍO', 'YA SE HABÍA PEDIDO'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Comanda<?php echo $mesa !== '' ? ' ' . $h($mesa) : ($v ? ' ' . $h($v->folio) : ''); ?></title>
	<style>
		@page { size: 80mm auto; margin: 0; }
		body { margin: 0; background: #eee; font-family: Arial, Helvetica, sans-serif; color: #000; }
		.ticket { width: 72mm; margin: 10px auto; padding: 3mm 4mm 10mm; background: #fff; font-size: 14px; line-height: 1.25; }
		/* 19-11-2026: todas las comandas (envío a mesa, reenvío, anulación, venta directa) SIN negrita, ni en los productos
		   ni en los títulos (el usuario la encontraba gruesa y fea en la térmica): destacan solo por el tamaño y los marcos.
		   Letra del mismo tamaño de antes; la comanda es más larga por los datos de arriba (fecha, hora, recinto, pedido,
		   caja, quién atiende) y un poco más de aire entre productos. */
		.destino { font-size: 22px; font-weight: normal; text-align: center; border: 3px solid #000; padding: 4px; letter-spacing: 2px; }
		/* 18-11-2026: antes fondo negro sólido (un bloque negro en la térmica); ahora blanco con doble marco */
		.aviso { margin-top: 6px; background: #fff; color: #000; border: 4px double #000; text-align: center; font-size: 22px; font-weight: normal; padding: 4px; }
		.aviso small { display: block; font-size: 13px; letter-spacing: 1px; }
		.mesa { margin-top: 6px; text-align: center; font-size: 38px; font-weight: normal; line-height: 1.05; }
		.antes { text-align: center; font-size: 15px; font-weight: normal; }
		.doc { text-align: center; font-size: 20px; font-weight: normal; margin-top: 4px; }
		.retira { text-align: center; font-size: 20px; font-weight: normal; margin-top: 4px; }
		.entrega { font-size: 15px; font-weight: 400; margin-top: 4px; word-break: break-word; }
		.info { width: 100%; margin: 6px 0 0; border-collapse: collapse; font-size: 14px; }
		.info td { padding: 2px 0; vertical-align: top; }
		.info td.v { text-align: right; padding-left: 6px; word-break: break-word; }
		hr { border: 0; border-top: 2px dashed #000; margin: 8px 0; }
		.linea { padding: 8px 0; border-bottom: 1px dashed #000; }
		.linea:last-child { border-bottom: 0; }
		.linea .prod { font-size: 22px; font-weight: normal; line-height: 1.15; }
		.linea .cant { font-size: 24px; }
		.linea .agregado { padding-left: 7mm; font-size: 17px; font-weight: normal; margin-top: 2px; }
		.linea .mensaje { margin: 4px 0 0 4mm; padding: 2px 6px; border-left: 3px solid #000; font-size: 17px; font-weight: normal; }
		.total { font-size: 15px; font-weight: normal; margin-top: 6px; }
		.obs { border: 2px solid #000; padding: 4px 6px; margin-top: 8px; font-size: 14px; font-weight: normal; }
		.obs small { display: block; font-size: 11px; letter-spacing: 1px; text-transform: uppercase; }
		.pie { margin-top: 10px; text-align: center; font-size: 12px; }
		.vacia { width: 72mm; margin: 10px auto; padding: 4mm; background: #fff; text-align: center; font-size: 13px; }
		.acciones { text-align: center; margin: 10px; }
		.acciones button { font-size: 14px; padding: 6px 14px; margin: 0 4px; cursor: pointer; }
		@media print {
			body { background: #fff; }
			.ticket { margin: 0; page-break-after: always; break-after: page; }
			.ticket:last-of-type { page-break-after: auto; break-after: auto; }
			.acciones { display: none; }
		}
	</style>
	<?php if ($papel == 58) : ?>
	<style>
		@page { size: 58mm auto; margin: 0; }
		.ticket, .vacia { width: 50mm; padding: 2mm 3mm 8mm; }
		.destino, .aviso { font-size: 18px; }
		.mesa { font-size: 30px; }
		.info { font-size: 12px; }
		.linea .prod { font-size: 18px; }
		.linea .cant { font-size: 19px; }
		.linea .agregado { font-size: 14px; }
		.linea .mensaje { font-size: 14px; }
		.obs { font-size: 12px; }
	</style>
	<?php endif; ?>
</head>
<body>
	<div class="acciones">
		<button onclick="window.print()">Imprimir</button>
		<button onclick="window.close()">Cerrar</button>
	</div>
	<?php if (!$grupos) : ?>
		<div class="vacia">Ningún producto de este pedido tiene destino de impresión.</div>
	<?php endif; ?>
	<?php foreach ($grupos as $g) : ?>
	<?php $total = 0; foreach ($g['lineas'] as $l) $total += (float)$l['cantidad']; ?>
	<div class="ticket<?php echo $tipo ? ' t-' . $h($tipo) : ''; ?>">
		<div class="destino"><?php echo $h(mb_strtoupper($g['nombre'])); ?></div>
		<?php if (isset($titulos[$tipo])) : ?>
			<div class="aviso"><?php echo $h($titulos[$tipo][0]); ?><small><?php echo $h($titulos[$tipo][1]); ?></small></div>
		<?php endif; ?>
		<?php if ($mesa !== '') : ?>
			<div class="mesa"><?php echo $h(mb_strtoupper($mesa)); ?></div>
			<?php if ($tipo === 'traslado' && !empty($aviso['desde'])) : ?><div class="antes">Antes: <?php echo $h(mb_strtoupper($aviso['desde'])); ?></div><?php endif; ?>
		<?php elseif ($v) : ?>
			<div class="doc"><?php echo $h($v->nombre_tdocumento); ?> N° <?php echo $h($v->folio); ?></div>
		<?php endif; ?>
		<?php if ($v && !empty($v->nombre_retira)) : ?><div class="retira">RETIRA: <?php echo $h(mb_strtoupper($v->nombre_retira)); ?></div><?php endif; ?>
		<?php if (!empty($entrega)) : ?><div class="entrega"><?php foreach ($entrega as $t) : ?><div><?php echo $h($t); ?></div><?php endforeach; ?></div><?php endif; ?>
		<?php if ($info) : ?>
			<table class="info">
				<?php foreach ($info as $fila) : ?><tr><td><?php echo $h($fila[0]); ?>:</td><td class="v"><?php echo $h($fila[1]); ?></td></tr><?php endforeach; ?>
			</table>
		<?php else : ?>
			<div class="info" style="text-align:center"><?php echo $h($hora); ?><?php echo $v && !empty($v->nombre_usuario) ? ' · ' . $h($v->nombre_usuario) : ''; ?></div>
		<?php endif; ?>
		<?php if ($obsTexto !== '') : ?>
			<!-- 19-11-2026: la observación / mensaje de la mesa arriba, antes de los productos: es lo primero que se lee -->
			<div class="obs"><?php if ($obsEtiqueta !== '') : ?><small><?php echo $h($obsEtiqueta); ?></small><?php endif; ?><?php echo $h($obsTexto); ?></div>
		<?php endif; ?>
		<hr>
		<?php foreach ($g['lineas'] as $l) : ?>
			<div class="linea">
				<div class="prod"><span class="cant"><?php echo $h($cant($l['cantidad'])); ?> x</span> <?php echo $h(mb_strtoupper($l['nombre'])); ?></div>
				<?php foreach ($l['agregados'] as $a) : ?>
					<div class="agregado">+ <?php echo $h(mb_strtoupper($a)); ?></div>
				<?php endforeach; ?>
				<?php if ($l['nota'] !== '') : ?>
					<div class="mensaje">&raquo; <?php echo $h(mb_strtoupper($l['nota'])); ?></div>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
		<hr>
		<div class="total"><?php echo $tipo === 'anulacion' ? 'ANULADO: ' : 'TOTAL: '; ?><?php echo $h($cant($total)); ?> <?php echo $total == 1 ? 'producto' : 'productos'; ?></div>
		<?php if ($tipo === 'anulacion' && !empty($aviso['motivo'])) : ?><div class="obs">Motivo: <?php echo $h($aviso['motivo']); ?></div><?php endif; ?>
		<div class="pie">Impresa el <?php echo $h(date('d/m/Y H:i:s')); ?></div>
	</div>
	<?php endforeach; ?>
	<script>
		// Solo se imprime sola si hay algo que imprimir
		if (/[?&]imprimir=1/.test(location.search) && document.querySelector('.ticket')) {
			window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });
		}
	</script>
</body>
</html>
