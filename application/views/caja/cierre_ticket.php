<?php
$c = $caja;
$r = $resumen;
$e = $empresa;
$h = function ($s) {
	return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
};
$m = function ($n) {
	return ($n < 0 ? '-' : '') . '$' . number_format(abs((float)$n), 0, ',', '.');
};
$titulo = $cerrada ? 'CIERRE DE CAJA' : 'INFORME X';
$papel = isset($papel) ? (int)$papel : 80;
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo $h($titulo . ' ' . $c->id_caja); ?></title>
	<style>
		@page { size: 80mm auto; margin: 0; }
		body { margin: 0; background: #eee; font-family: Arial, Helvetica, sans-serif; color: #000; }
		.ticket { width: 72mm; margin: 10px auto; padding: 4mm; background: #fff; font-size: 12px; line-height: 1.35; }
		.c { text-align: center; }
		.r { text-align: right; }
		.b { font-weight: bold; }
		.doc { border: 1px solid #000; padding: 4px; margin: 6px 0; }
		hr { border: 0; border-top: 1px dashed #000; margin: 6px 0; }
		table { width: 100%; border-collapse: collapse; }
		td, th { vertical-align: top; padding: 1px 0; }
		th { text-align: right; font-weight: bold; }
		th:first-child { text-align: left; }
		.total td { font-size: 14px; font-weight: bold; }
		.nota { font-size: 11px; margin-top: 6px; }
		.firma { margin-top: 28px; border-top: 1px solid #000; padding-top: 2px; text-align: center; font-size: 11px; }
		.acciones { text-align: center; margin: 10px; }
		.acciones button { font-size: 14px; padding: 6px 14px; margin: 0 4px; cursor: pointer; }
		@media print {
			body { background: #fff; }
			/* Térmica (203 dpi): trazos gruesos y negro puro; los finos se pierden al rasterizar */
			.ticket { margin: 0; width: 72mm; font-weight: bold; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
			.acciones { display: none; }
		}
	</style>
	<?php if ($papel == 58) : ?>
	<style>
		/* POS58: área imprimible de ~48mm */
		@page { size: 58mm auto; margin: 0; }
		.ticket { width: 48mm; padding: 3mm 5mm; font-size: 11px; }
		.total td { font-size: 12px; }
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
		<?php endif; ?>
		<div class="doc c b"><?php echo $titulo; ?><br>Caja N° <?php echo (int)$c->id_caja; ?></div>
		<?php if (!$cerrada) : ?><div class="c">Caja abierta · resumen parcial</div><?php endif; ?>

		<div>Cajero: <?php echo $h($c->nombre_usuario); ?></div>
		<div>Apertura: <?php echo $h($c->apertura); ?></div>
		<div><?php echo $cerrada ? 'Cierre: ' . $h($c->cierre_texto) : 'Emitido: ' . date('d/m/Y H:i'); ?></div>
		<hr>
		<table>
			<tr><td>Ventas (<?php echo (int)$r['cantidad_ventas']; ?>)</td><td class="r"><?php echo $m($r['ventas'] + $r['devoluciones']); ?></td></tr>
			<?php if ($r['cantidad_devoluciones']) : ?>
				<tr><td>Devoluciones (<?php echo (int)$r['cantidad_devoluciones']; ?>)</td><td class="r"><?php echo $m(-$r['devoluciones']); ?></td></tr>
			<?php endif; ?>
			<tr class="b"><td>Venta neta</td><td class="r"><?php echo $m($r['ventas']); ?></td></tr>
			<?php if ($r['descuentos']) : ?><tr><td>Descuentos otorgados</td><td class="r"><?php echo $m($r['descuentos']); ?></td></tr><?php endif; ?>
			<?php if ($r['cortesias']) : ?><tr><td>Cortesías</td><td class="r"><?php echo $m($r['cortesias']); ?></td></tr><?php endif; ?>
			<?php if ($r['anuladas']) : ?><tr><td>Ventas anuladas</td><td class="r"><?php echo (int)$r['anuladas']; ?></td></tr><?php endif; ?>
			<?php if ($r['propinas']) : ?><tr><td>Propinas (a garzones)</td><td class="r"><?php echo $m($r['propinas']); ?></td></tr><?php endif; ?>
			<?php if ($r['ingresos']) : ?><tr><td>Ingresos de caja</td><td class="r"><?php echo $m($r['ingresos']); ?></td></tr><?php endif; ?>
			<?php if ($r['egresos']) : ?><tr><td>Retiros de caja</td><td class="r"><?php echo $m(-$r['egresos']); ?></td></tr><?php endif; ?>
		</table>
		<hr>
		<table>
			<tr><th>Forma de pago</th><th>Sistema</th><?php if ($cerrada) : ?><th>Rendido</th><?php endif; ?></tr>
			<?php foreach ($formas as $f) : ?>
				<tr>
					<td><?php echo $h($f['nombre']); ?><?php if ($f['efectivo']) : ?><br><small>(en caja, con fondo)</small><?php endif; ?></td>
					<td class="r"><?php echo $m($f['esperado']); ?></td>
					<?php if ($cerrada) : ?><td class="r"><?php echo $m($f['rendido']); ?></td><?php endif; ?>
				</tr>
			<?php endforeach; ?>
		</table>
		<hr>
		<table>
			<tr><td>Fondo inicial</td><td class="r"><?php echo $m($r['fondo']); ?></td></tr>
			<?php if ($cerrada) : ?>
				<tr><td>Fondo próximo turno</td><td class="r"><?php echo $m($c->fondo_fijo); ?></td></tr>
				<tr><td>Efectivo entregado</td><td class="r"><?php echo $m($c->efectivo_entregado); ?></td></tr>
				<tr class="total"><td>DIFERENCIA</td><td class="r"><?php echo $m($c->diferencia); ?></td></tr>
			<?php else : ?>
				<tr class="total"><td>EFECTIVO EN CAJA</td><td class="r"><?php echo $m($r['efectivo_esperado']); ?></td></tr>
			<?php endif; ?>
		</table>
		<?php if ($cerrada) : ?>
			<div class="firma">Firma cajero</div>
			<div class="firma">Firma supervisor</div>
		<?php else : ?>
			<div class="nota c">Informe parcial: no cierra la caja.</div>
		<?php endif; ?>
	</div>
	<script>
		if (/[?&]imprimir=1/.test(location.search)) {
			window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });
		}
	</script>
</body>
</html>
