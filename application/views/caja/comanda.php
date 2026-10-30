<?php
// Comanda web: una hoja por destino de impresión Cocina con cada producto y su mensaje.
$h = function ($s) {
	return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
};
$cant = function ($n) {
	return rtrim(rtrim(number_format((float)$n, 3, ',', ''), '0'), ',');
};
$papel = isset($papel) ? (int)$papel : 80;
$v = isset($venta) ? $venta : null;
$hora = $v && !empty($v->fecha_texto) ? $v->fecha_texto : date('d/m/Y H:i');
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Comanda<?php echo $v ? ' ' . $h($v->folio) : ''; ?></title>
	<style>
		@page { size: 80mm auto; margin: 0; }
		body { margin: 0; background: #eee; font-family: Arial, Helvetica, sans-serif; color: #000; }
		.ticket { width: 72mm; margin: 10px auto; padding: 4mm; background: #fff; font-size: 13px; line-height: 1.35; }
		.ticket + .ticket { break-before: page; page-break-before: always; }
		.c { text-align: center; }
		.b { font-weight: bold; }
		.destino { font-size: 20px; font-weight: bold; text-align: center; border: 2px solid #000; padding: 4px; margin-bottom: 6px; letter-spacing: 1px; }
		.meta { font-size: 12px; text-align: center; }
		hr { border: 0; border-top: 1px dashed #000; margin: 6px 0; }
		.linea { margin: 6px 0; }
		.linea .prod { font-size: 15px; font-weight: bold; }
		.linea .cant { display: inline-block; min-width: 9mm; }
		.linea .agregado { padding-left: 9mm; font-size: 13px; }
		.linea .mensaje { margin: 3px 0 0 9mm; padding: 2px 4px; border-left: 3px solid #000; font-size: 14px; font-weight: bold; }
		.obs { border: 1px solid #000; padding: 4px; margin-top: 6px; font-size: 13px; }
		.vacia { width: 72mm; margin: 10px auto; padding: 4mm; background: #fff; text-align: center; font-size: 13px; }
		.acciones { text-align: center; margin: 10px; }
		.acciones button { font-size: 14px; padding: 6px 14px; margin: 0 4px; cursor: pointer; }
		@media print {
			body { background: #fff; }
			.ticket { margin: 0; width: 72mm; font-weight: bold; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
			.acciones, .vacia { display: none; }
		}
	</style>
	<?php if ($papel == 58) : ?>
	<style>
		@page { size: 58mm auto; margin: 0; }
		.ticket, .vacia { width: 48mm; padding: 3mm 5mm; font-size: 12px; }
		.destino { font-size: 17px; }
		.linea .prod { font-size: 13px; }
		.linea .mensaje { font-size: 12px; }
	</style>
	<?php endif; ?>
</head>
<body>
	<div class="acciones">
		<button onclick="window.print()">Imprimir</button>
		<button onclick="window.close()">Cerrar</button>
	</div>
	<?php if (!$grupos) : ?>
		<div class="vacia">Ningún producto de esta venta tiene destino de impresión Cocina.</div>
	<?php endif; ?>
	<?php foreach ($grupos as $g) : ?>
	<div class="ticket">
		<div class="destino"><?php echo $h($g['nombre']); ?></div>
		<div class="meta">COMANDA<?php echo $v ? ' · ' . $h($v->nombre_tdocumento) . ' N° ' . $h($v->folio) : ''; ?></div>
		<div class="meta"><?php echo $h($hora); ?><?php echo $v && !empty($v->nombre_usuario) ? ' · ' . $h($v->nombre_usuario) : ''; ?></div>
		<hr>
		<?php foreach ($g['lineas'] as $l) : ?>
			<div class="linea">
				<div class="prod"><span class="cant"><?php echo $h($cant($l['cantidad'])); ?> x</span> <?php echo $h($l['nombre']); ?></div>
				<?php foreach ($l['agregados'] as $a) : ?>
					<div class="agregado">+ <?php echo $h($a); ?></div>
				<?php endforeach; ?>
				<?php if ($l['nota'] !== '') : ?>
					<div class="mensaje">&raquo; <?php echo $h($l['nota']); ?></div>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
		<?php if (!empty($observacion)) : ?>
			<div class="obs"><span class="b">Obs:</span> <?php echo $h($observacion); ?></div>
		<?php endif; ?>
		<hr>
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
