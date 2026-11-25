<?php $this->load->view('umedida/jsEquivalencias.php'); ?>
<?php $this->load->view('recetas/buscadorInsumos.php'); /* v1.37.11: buscar insumos como en la Caja */ ?>
<script>
/**
 * v1.34.0 (pedido del usuario: "que en las recetas tome los valores correspondientes, gramos, kilogramos"): la cantidad de
 * cada insumo se puede escribir en cualquier unidad que se pueda convertir (KILOGRAMO o GRAMO, LITRO o MILILITRO) y se
 * guarda en la unidad de receta del insumo. El costo usa el costo de 1 unidad de receta (costo_receta del servidor:
 * costo del kilo / 1000 para un insumo en gramos). Lo usan Recetas (recetas/modalRecetas.php) y la receta de la Carta
 * (productos/jsReceta.php); los dos formularios tienen los mismos campos.
 */
function recInsumoElegido() {
	var o = $('#cboCodBarraInsumoReceta').find('option:selected');
	return { id_umedida_receta: o.attr('idUmRec'), id_umedida_inventario: o.attr('idUmInv'), umedida_receta: o.attr('mRec'),
		umedida_inventario: o.attr('mInv'), nombre_umedida_receta: o.attr('umedida') || '' };
}
/** Opción del insumo para el combo, con lo que hace falta para convertir. */
function recOpcionInsumo(v, elegido) {
	return '<option ' + (elegido ? 'selected ' : '') + 'value="' + v.id_insumo + '" cbarra="' + v.cbarra + '" nombreInsumo="' + v.nombre_insumo
		+ '" esReceta="' + v.esreceta + '" umedida="' + v.nombre_umedida + '" costoPP="' + recRedondear(v.costo_receta, 4)
		+ '" idUmRec="' + v.id_umedida_receta + '" idUmInv="' + v.id_umedida_inventario + '" mRec="' + v.umedida_receta
		+ '" mInv="' + v.umedida_inventario + '" grupo="' + $('<div>').text(v.nombre_grupo_insumo || '').html().replace(/"/g, '&quot;') + '">' + v.cbarra + '/' + v.nombre_insumo + '</option>';
}
function recRedondear(n, dec) { var p = Math.pow(10, dec || 0); return Math.round((Number(n) || 0) * p) / p; }
/** Llena el combo de unidades con las que sirven para el insumo elegido (su unidad de receta primero). */
function recPintarUnidades() {
	var $u = $('#unidadLineaReceta').empty(), ins = recInsumoElegido();
	if (!Number($('#cboCodBarraInsumoReceta').val())) { $u.prop('disabled', true); recCalcularCostoLinea(); return; }
	Unidades.listo(function () {
		$u.empty();
		$.each(Unidades.delInsumo(ins), function (i, u) {
			$u.append($('<option>').val(u.id).text(u.nombre || ins.nombre_umedida_receta));
		});
		if (!$u.children().length) $u.append($('<option>').val(ins.id_umedida_receta).text(ins.nombre_umedida_receta));
		$u.val(String(ins.id_umedida_receta)).prop('disabled', $u.children().length < 2);
		recCalcularCostoLinea();
	});
}
/** La cantidad escrita, pasada a la unidad de receta del insumo (null si no se puede convertir). */
function recCantidadEnReceta() {
	var cant = Number(String($('#cantidadInsumoReceta').val()).replace(',', '.')) || 0;
	var r = Unidades.aReceta(recInsumoElegido(), cant, $('#unidadLineaReceta').val());
	return r === null ? null : recRedondear(r, 3);
}
function recCalcularCostoLinea() {
	var ins = recInsumoElegido(), costo = Number($('#costoInsumoReceta').val()) || 0, enRec = recCantidadEnReceta();
	var idU = Number($('#unidadLineaReceta').val()), cant = Number(String($('#cantidadInsumoReceta').val()).replace(',', '.')) || 0;
	$('#costoTotalInsumoReceta').val(enRec === null ? '' : Math.round(enRec * costo));
	var $h = $('#equivLineaReceta');
	if (cant > 0 && enRec !== null && idU && idU !== Number(ins.id_umedida_receta)) {
		$h.html('<i class="fa fa-exchange"></i> ' + Unidades.fmt(cant) + ' ' + $('#unidadLineaReceta option:selected').text()
			+ ' = <b>' + Unidades.fmt(enRec) + ' ' + ins.nombre_umedida_receta + '</b>').prop('hidden', false);
	} else $h.prop('hidden', true);
}
$(document).on('change', '#unidadLineaReceta', recCalcularCostoLinea)
	.on('input keyup', '#cantidadInsumoReceta', recCalcularCostoLinea);
</script>
<style>
	.rec-equiv { margin-top: 4px; font-size: 12px; color: var(--t-primario, #15803D); }
	.rec-equiv i { margin-right: 3px; }
</style>
