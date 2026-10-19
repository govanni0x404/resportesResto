<?php
$v = $venta;
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo $h($v->nombre_tdocumento . ' ' . $v->folio); ?></title>
	<style>
		@page { size: 80mm auto; margin: 0; }
		body { margin: 0; background: #eee; font-family: 'Courier New', monospace; color: #000; }
		.ticket { width: 72mm; margin: 10px auto; padding: 4mm; background: #fff; font-size: 12px; line-height: 1.35; }
		.c { text-align: center; }
		.r { text-align: right; }
		.b { font-weight: bold; }
		.doc { border: 1px solid #000; padding: 4px; margin: 6px 0; }
		hr { border: 0; border-top: 1px dashed #000; margin: 6px 0; }
		table { width: 100%; border-collapse: collapse; }
		td { vertical-align: top; padding: 1px 0; }
		.total td { font-size: 15px; font-weight: bold; }
		.nota { font-size: 10px; margin-top: 6px; }
		.anulada { color: #c00; font-weight: bold; font-size: 14px; }
		.timbre { margin: 8px 0 2px; }
		.timbre img { display: block; width: 100%; height: auto; image-rendering: pixelated; }
		.acciones { text-align: center; margin: 10px; }
		.acciones button { font-size: 14px; padding: 6px 14px; margin: 0 4px; cursor: pointer; }
		@media print {
			body { background: #fff; }
			.ticket { margin: 0; width: 72mm; }
			.acciones { display: none; }
		}
	</style>
</head>
<body>
	<div class="acciones">
		<button onclick="window.print()">Imprimir</button>
		<button onclick="window.close()">Cerrar</button>
	</div>
	<div class="ticket">
		<?php if ($e) : ?>
			<div class="c b"><?php echo $h($e->nombre_fantasia ?: $e->razon_social); ?></div>
			<?php if ($e->nombre_fantasia && $e->razon_social) : ?><div class="c"><?php echo $h($e->razon_social); ?></div><?php endif; ?>
			<div class="c">RUT <?php echo $h(number_format((float)$e->rut_empresa, 0, ',', '.') . '-' . $e->dv_empresa); ?></div>
			<?php if ($e->giro) : ?><div class="c"><?php echo $h($e->giro); ?></div><?php endif; ?>
			<div class="c"><?php echo $h($e->direccion_empresa); ?></div>
			<?php if ($e->telefono) : ?><div class="c">Fono <?php echo $h($e->telefono); ?></div><?php endif; ?>
		<?php endif; ?>

		<div class="doc c b">
			<?php echo $h(mb_strtoupper($v->nombre_tdocumento)); ?><br>
			N° <?php echo (int)$v->folio; ?>
		</div>
		<?php if ($anulada) : ?><div class="c anulada">*** ANULADA ***</div><?php endif; ?>
		<?php if ($nc) : ?><div class="c b">Devolución de <?php echo $h($referencia); ?></div><?php endif; ?>

		<div>Fecha: <?php echo $h($v->fecha_texto); ?></div>
		<div>Cajero: <?php echo $h($v->nombre_usuario); ?></div>
		<?php if ($v->rut) : ?>
			<div>Cliente: <?php echo $h($v->nombre_cliente); ?></div>
			<?php if ($v->rut) : ?><div>RUT: <?php echo $h(number_format((float)$v->rut, 0, ',', '.') . '-' . $v->dv); ?></div><?php endif; ?>
			<?php if ($v->giro) : ?><div>Giro: <?php echo $h($v->giro); ?></div><?php endif; ?>
			<?php if ($v->direccion) : ?><div>Dir: <?php echo $h($v->direccion); ?></div><?php endif; ?>
		<?php endif; ?>
		<hr>
		<table>
			<?php foreach ($detalle as $d) : ?>
				<tr><td colspan="2"><?php echo $h($d->nombre); ?></td></tr>
				<tr>
					<td>&nbsp;&nbsp;<?php echo $cant($d->cantidad); ?> x <?php echo $m($d->bruto_un); ?></td>
					<td class="r"><?php echo $m($d->subtotal); ?></td>
				</tr>
			<?php endforeach; ?>
		</table>
		<hr>
		<table>
			<tr><td>Subtotal</td><td class="r"><?php echo $m($v->subtotal); ?></td></tr>
			<?php if ((int)$v->descuento > 0) : ?>
				<tr><td>Descuento</td><td class="r">-<?php echo $m($v->descuento); ?></td></tr>
			<?php endif; ?>
			<tr><td>Neto</td><td class="r"><?php echo $m($v->neto); ?></td></tr>
			<tr><td>IVA</td><td class="r"><?php echo $m($v->iva); ?></td></tr>
			<tr class="total"><td><?php echo $nc ? 'TOTAL DEVUELTO' : 'TOTAL'; ?></td><td class="r"><?php echo $m($v->total); ?></td></tr>
			<?php $propina = isset($v->valor_propina) ? (int)$v->valor_propina : 0; ?>
			<?php if ($propina > 0) : ?>
				<tr><td>Propina (voluntaria)</td><td class="r"><?php echo $m($propina); ?></td></tr>
				<tr class="b"><td>Total con propina</td><td class="r"><?php echo $m($v->total + $propina); ?></td></tr>
			<?php endif; ?>
		</table>
		<?php if ($pagos) : ?>
			<hr>
			<table>
				<?php foreach ($pagos as $p) : ?>
					<tr><td><?php echo $h(($nc ? 'Devuelto en ' : '') . $p->nombre_fpago); ?></td><td class="r"><?php echo $m($p->paga_con); ?></td></tr>
					<?php if ((int)$p->vuelto > 0) : ?>
						<tr><td>Vuelto</td><td class="r"><?php echo $m($p->vuelto); ?></td></tr>
					<?php endif; ?>
				<?php endforeach; ?>
			</table>
		<?php endif; ?>
		<?php if ($v->mensaje_pedido) : ?>
			<hr>
			<div class="nota"><?php echo $h($v->mensaje_pedido); ?></div>
		<?php endif; ?>
		<?php if ($electronico && !$emitido) : ?>
			<div class="nota c b">Documento pendiente de envío al SII.</div>
		<?php elseif (!empty($timbre)) : ?>
			<div class="timbre"><img src="<?php echo $timbre; ?>" alt="Timbre electrónico"></div>
			<div class="c b" style="font-size:11px">Timbre Electrónico SII</div>
			<div class="c" style="font-size:10px">Verifique documento: www.sii.cl</div>
		<?php endif; ?>
		<hr>
		<div class="c"><?php echo $nc ? 'Firma cliente: ____________________' : '¡Gracias por su compra!'; ?></div>
	</div>
	<script>
		if (/[?&]imprimir=1/.test(location.search)) {
			window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });
		}
	</script>
</body>
</html>
