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
// Configuraciones > Imprimir cierre de caja: qué secciones salen en el ticket
$ver = function ($k) use ($config) { return !isset($config[$k]) || $config[$k]; };
$correo = !empty($correo);
$papel = isset($papel) ? (int)$papel : 80;
?>
<!DOCTYPE html>
<html lang="es-CL" translate="no">
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
		.firmas { display: flex; gap: 10px; margin-top: 40px; }
		.firma { flex: 1; border-top: 1px solid #000; padding-top: 2px; text-align: center; font-size: 11px; }
		/* v1.39.4: diseño como el impreso térmico */
		.emp { font-size: 16px; font-weight: bold; }
		.titulo { border-top: 2px solid #000; border-bottom: 2px solid #000; padding: 5px 0; margin: 6px 0; }
		.titulo .t1 { font-size: 19px; font-weight: bold; letter-spacing: .5px; }
		.sec { background: #000; color: #fff; text-align: center; font-weight: bold; padding: 2px 0; margin: 10px 0 3px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
		.sum td { border-top: 1px dashed #000; padding-top: 3px; }
		.caja-dif { border-top: 2px solid #000; border-bottom: 2px solid #000; padding: 5px 0; margin: 10px 0 4px; }
		.caja-dif .monto { font-size: 22px; font-weight: bold; }
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
	<?php if (!$correo) : ?>
	<div class="acciones">
		<button onclick="window.print()">Imprimir</button>
		<button onclick="window.close()">Cerrar</button>
	</div>
	<?php endif; ?>
	<div class="ticket">
		<?php /* v1.39.4 (pedido del usuario): mismo diseño que el impreso térmico (Ticket_impresion::cierre) */ ?>
		<?php if ($e) : $nom = trim((string)($e->nombre_fantasia ?: $e->razon_social)); $dir = trim((string)(!empty($e->direccion_sucursal) ? $e->direccion_sucursal : $e->direccion_empresa)); ?>
			<div class="c emp"><?php echo $h($nom); ?></div>
			<?php if ($e->nombre_fantasia && $e->razon_social && strcasecmp(trim($e->razon_social), $nom) !== 0) : ?><div class="c"><?php echo $h($e->razon_social); ?></div><?php endif; ?>
			<?php if (!empty($e->rut_empresa)) : ?><div class="c">RUT <?php echo number_format((float)$e->rut_empresa, 0, ',', '.') . '-' . $h($e->dv_empresa); ?></div><?php endif; ?>
			<?php if ($dir !== '') : ?><div class="c"><?php echo $h($dir); ?></div><?php endif; ?>
		<?php endif; ?>
		<div class="titulo c"><div class="t1"><?php echo $titulo; ?></div><div class="b">Caja N° <?php echo (int)$c->id_caja; ?></div>
			<?php if (!$cerrada) : ?><div>Caja abierta · resumen parcial</div><?php endif; ?></div>
		<table>
			<tr><td>Cajero</td><td class="r"><?php echo $h($c->nombre_usuario); ?></td></tr>
			<tr><td>Apertura</td><td class="r"><?php echo $h($c->apertura); ?></td></tr>
			<tr><td><?php echo $cerrada ? 'Cierre' : 'Emitido'; ?></td><td class="r"><?php echo $cerrada ? $h($c->cierre_texto) : date('d/m/Y H:i'); ?></td></tr>
		</table>

		<div class="sec">RESUMEN DE VENTAS</div>
		<table>
			<tr><td>Ventas (<?php echo (int)$r['cantidad_ventas']; ?>)</td><td class="r"><?php echo $m($r['ventas'] + $r['devoluciones']); ?></td></tr>
			<?php if ($r['cantidad_devoluciones']) : ?>
				<tr><td>Devoluciones (<?php echo (int)$r['cantidad_devoluciones']; ?>)</td><td class="r"><?php echo $m(-$r['devoluciones']); ?></td></tr>
			<?php endif; ?>
			<tr class="total"><td>VENTA NETA</td><td class="r"><?php echo $m($r['ventas']); ?></td></tr>
		</table>
		<?php
		$extras = array();
		if ($r['descuentos']) $extras[] = array('Descuentos otorgados', $m($r['descuentos']));
		if ($ver('cierre_creditos') && !empty($r['creditos'])) $extras[] = array('Créditos a clientes', $m($r['creditos']));
		if ($ver('cierre_cortesias') && $r['cortesias']) $extras[] = array('Cortesías', $m($r['cortesias']));
		if ($ver('cierre_anulaciones') && $r['anuladas']) $extras[] = array('Ventas anuladas', (int)$r['anuladas']);
		if ($r['propinas']) $extras[] = array('Propinas (a garzones)', $m($r['propinas']));
		if (!empty($r['pagos_credito'])) $extras[] = array('Pagos de créditos recibidos', $m($r['pagos_credito']));
		if ($ver('cierre_ingresos') && $r['ingresos']) $extras[] = array('Ingresos de caja', $m($r['ingresos']));
		if ($ver('cierre_ingresos') && $r['egresos']) $extras[] = array('Retiros de caja', $m(-$r['egresos']));
		?>
		<?php if ($extras) : ?>
			<hr>
			<table><?php foreach ($extras as $x) : ?><tr><td><?php echo $h($x[0]); ?></td><td class="r"><?php echo $h($x[1]); ?></td></tr><?php endforeach; ?></table>
		<?php endif; ?>

		<div class="sec">FORMAS DE PAGO</div>
		<table>
			<?php if ($cerrada) : $tS = 0; $tR = 0; ?>
				<tr><th>Forma</th><th>Sistema</th><th>Rendido</th><th>Dif.</th></tr>
				<?php foreach ($formas as $f) : $dif = (int)$f['rendido'] - (int)$f['esperado']; $tS += (int)$f['esperado']; $tR += (int)$f['rendido']; ?>
					<tr><td class="b"><?php echo $h($f['nombre']); ?><?php if ($f['efectivo']) : ?><br><small>(con fondo)</small><?php endif; ?></td>
						<td class="r"><?php echo $m($f['esperado']); ?></td><td class="r"><?php echo $m($f['rendido']); ?></td>
						<td class="r"><?php echo $dif ? ($dif > 0 ? '+' : '') . $m($dif) : '0'; ?></td></tr>
				<?php endforeach; $tD = $tR - $tS; ?>
				<tr class="b sum"><td>TOTAL</td><td class="r"><?php echo $m($tS); ?></td><td class="r"><?php echo $m($tR); ?></td><td class="r"><?php echo $tD ? ($tD > 0 ? '+' : '') . $m($tD) : '0'; ?></td></tr>
			<?php else : ?>
				<?php foreach ($formas as $f) : ?>
					<tr><td><?php echo $h($f['nombre']); ?><?php if ($f['efectivo']) : ?> <small>(con fondo)</small><?php endif; ?></td><td class="r"><?php echo $m($f['esperado']); ?></td></tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</table>

		<div class="sec">EFECTIVO</div>
		<table>
			<tr><td>Fondo inicial</td><td class="r"><?php echo $m($r['fondo']); ?></td></tr>
			<?php if ($cerrada) : ?>
				<tr><td>Efectivo esperado</td><td class="r"><?php echo $m($r['efectivo_esperado']); ?></td></tr>
				<tr><td>Efectivo entregado</td><td class="r"><?php echo $m($c->efectivo_entregado); ?></td></tr>
				<tr><td>Fondo próximo turno</td><td class="r"><?php echo $m($c->fondo_fijo); ?></td></tr>
			<?php endif; ?>
		</table>
		<?php if ($cerrada) : $dif = (int)$c->diferencia; ?>
			<div class="caja-dif c"><div>DIFERENCIA</div><div class="monto"><?php echo $m($dif); ?></div>
				<div class="b"><?php echo $dif > 0 ? 'SOBRA ' . $m($dif) : ($dif < 0 ? 'FALTA ' . $m(-$dif) : 'CAJA CUADRADA'); ?></div></div>
		<?php else : ?>
			<div class="caja-dif c"><div>EFECTIVO EN CAJA</div><div class="monto"><?php echo $m($r['efectivo_esperado']); ?></div></div>
		<?php endif; ?>

		<?php if ($cerrada && !empty($inventario)) : ?>
			<?php
			$cn = function ($n) { return rtrim(rtrim(number_format((float)$n, 3, ',', '.'), '0'), ','); };
			$bodegaActual = null;
			$conDif = 0;
			foreach ($inventario as $iv) { if (abs($iv['diferencia']) > 0.0005) $conDif++; }
			?>
			<div class="sec">INVENTARIO DE CIERRE</div>
			<div class="nota c">Lo contado reemplaza al stock del sistema.</div>
			<table>
				<tr><th>Insumo</th><th>Sistema</th><th>Contado</th><th>Dif.</th></tr>
				<?php foreach ($inventario as $iv) : ?>
					<?php if ($iv['bodega'] !== $bodegaActual) : $bodegaActual = $iv['bodega']; ?>
						<tr><td colspan="4" class="b">&rsaquo; <?php echo $h($bodegaActual); ?></td></tr>
					<?php endif; ?>
					<tr>
						<td><?php echo $h($iv['nombre']); ?><?php echo $iv['umedida'] ? ' <small>(' . $h($iv['umedida']) . ')</small>' : ''; ?></td>
						<td class="r"><?php echo $cn($iv['stock']); ?></td>
						<td class="r b"><?php echo $cn($iv['contado']); ?></td>
						<td class="r"><?php echo (abs($iv['diferencia']) <= 0.0005 ? '0' : ($iv['diferencia'] > 0 ? '+' : '') . $cn($iv['diferencia'])); ?></td>
					</tr>
				<?php endforeach; ?>
			</table>
			<div class="nota c"><?php echo count($inventario); ?> insumo(s) · <?php echo $conDif ? $conDif . ' con diferencia' : 'sin diferencias'; ?></div>
		<?php endif; ?>
		<?php if ($cerrada) : ?>
			<div class="firmas"><div class="firma">Cajero</div><div class="firma">Supervisor</div></div>
		<?php else : ?>
			<div class="nota c b">Informe parcial: no cierra la caja.</div>
		<?php endif; ?>
		<div class="nota c">Impreso el <?php echo date('d/m/Y H:i'); ?></div>
	</div>
	<?php if (!$correo) : ?>
	<script>
		if (/[?&]imprimir=1/.test(location.search)) {
			window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });
		}
	</script>
	<?php endif; ?>
</body>
</html>
