<?php
// Precuenta de mesa: detalle y total para que el cliente revise antes de pagar. No es un documento tributario.
$e = $empresa;
$h = function ($s) {
	return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
};
$m = function ($n) {
	return '$' . number_format((float)$n, 0, ',', '.');
};
$cant = function ($n) {
	return rtrim(rtrim(number_format((float)$n, 3, ',', ''), '0'), ',');
};
$papel = isset($papel) ? (int)$papel : 80;
$subtotal = (int)array_sum(array_column($lineas, 'bruto'));
$descuento = min($subtotal, (int)$descuento);
$total = $subtotal - $descuento;
$propina = $por_propina > 0 ? (int)round($total * $por_propina / 100) : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Precuenta <?php echo $h($nombre_mesa); ?></title>
	<style>
		@page { size: 80mm auto; margin: 0; }
		body { margin: 0; background: #eee; font-family: Arial, Helvetica, sans-serif; color: #000; }
		.ticket { width: 72mm; margin: 10px auto; padding: 4mm; background: #fff; font-size: 12px; line-height: 1.35; }
		.c { text-align: center; }
		.r { text-align: right; }
		.b { font-weight: bold; }
		.doc { border: 1px solid #000; padding: 4px; margin: 6px 0; font-size: 15px; }
		hr { border: 0; border-top: 1px dashed #000; margin: 6px 0; }
		table { width: 100%; border-collapse: collapse; }
		td { vertical-align: top; padding: 1px 0; }
		.det td { font-size: 11px; padding-left: 10px; }
		.total td { font-size: 15px; font-weight: bold; }
		.nota { font-size: 11px; margin-top: 8px; }
		.acciones { text-align: center; margin: 10px; }
		.acciones button { font-size: 14px; padding: 6px 14px; margin: 0 4px; cursor: pointer; }
		@media print {
			body { background: #fff; }
			.ticket { margin: 0; width: 72mm; font-weight: bold; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
			.acciones { display: none; }
		}
	</style>
	<?php if ($papel == 58) : ?>
	<style>
		@page { size: 58mm auto; margin: 0; }
		.ticket { width: 48mm; padding: 3mm 5mm; font-size: 11px; }
		.total td { font-size: 13px; }
	</style>
	<?php endif; ?>
</head>
<body>
	<div class="acciones">
		<button onclick="window.print()">Imprimir</button>
		<button onclick="window.close()">Cerrar</button>
	</div>
	<div class="ticket">
		<?php if ($e) : ?>
			<div class="c b"><?php echo $h($e->nombre_fantasia ?: $e->razon_social); ?></div>
			<?php if (!empty($e->direccion_empresa)) : ?><div class="c"><?php echo $h($e->direccion_empresa); ?></div><?php endif; ?>
		<?php endif; ?>
		<div class="doc c b">PRECUENTA<br><?php echo $h(mb_strtoupper($nombre_mesa)); ?></div>
		<div>Llegada: <?php echo $h($desde); ?></div>
		<div>Emitida: <?php echo date('d/m/Y H:i'); ?></div>
		<hr>
		<table>
			<?php foreach ($lineas as $l) : ?>
				<tr>
					<td><?php echo $h($cant($l['cantidad'])); ?> x <?php echo $h($l['nombre']); ?></td>
					<td class="r"><?php echo $m($l['bruto']); ?></td>
				</tr>
				<?php if ($l['agregado'] !== '') : ?><tr class="det"><td colspan="2">+ <?php echo $h($l['agregado']); ?></td></tr><?php endif; ?>
			<?php endforeach; ?>
		</table>
		<hr>
		<table>
			<?php if ($descuento) : ?>
				<tr><td>Subtotal</td><td class="r"><?php echo $m($subtotal); ?></td></tr>
				<tr><td>Descuento</td><td class="r">-<?php echo $m($descuento); ?></td></tr>
			<?php endif; ?>
			<tr class="total"><td>TOTAL</td><td class="r"><?php echo $m($total); ?></td></tr>
			<?php if ($propina) : ?>
				<tr><td>Propina sugerida (<?php echo $h(rtrim(rtrim(number_format($por_propina, 2, ',', ''), '0'), ',')); ?>%)</td><td class="r"><?php echo $m($propina); ?></td></tr>
				<tr class="total"><td>TOTAL CON PROPINA</td><td class="r"><?php echo $m($total + $propina); ?></td></tr>
			<?php endif; ?>
		</table>
		<div class="nota c">La propina es voluntaria.<br>Este documento no es válido como boleta.</div>
	</div>
	<script>
		if (/[?&]imprimir=1/.test(location.search)) {
			window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });
		}
	</script>
</body>
</html>
