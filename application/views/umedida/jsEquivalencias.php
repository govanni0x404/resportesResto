<script>
/**
 * v1.34.0 (pedido del usuario: "si dice 1 kg, se sabe que son 1000 gr"): equivalencias de las unidades de medida.
 * Lo usan Insumos (la medida de receta se calcula sola), Recetas y la receta de la Carta (escribir en kilos o gramos)
 * y Producción (lo que se gasta se convierte a la unidad de inventario). Servidor: Umedida::equivalencias.
 *
 * Unidades.factor(desde, hacia): cantidad en "hacia" = cantidad en "desde" × factor (null si no se pueden convertir).
 * Unidades.aReceta(insumo, cantidad, idUnidad): la cantidad en la unidad de receta del insumo, usando la equivalencia de
 * las unidades y, si no alcanza, la del propio insumo (MEDIDA INVENTARIO / MEDIDA RECETA). null si no se puede.
 *   insumo = {id_umedida_receta, id_umedida_inventario, umedida_receta, umedida_inventario}
 */
window.Unidades = window.Unidades || (function () {
	var lista = [], porId = {}, pedido = null, cargado = false;
	function cargar(forzar) {
		if (pedido && !forzar) return pedido;
		pedido = $.ajax({ url: base_url + 'index.php/Umedida/equivalencias', dataType: 'json', cache: false }).done(function (r) {
			lista = $.isArray(r) ? r : [];
			porId = {};
			$.each(lista, function (i, u) { porId[Number(u.id_umedida)] = u; });
			cargado = true;
		});
		return pedido;
	}
	function de(id) { return porId[Number(id)] || null; }
	function nombre(id) { var u = de(id); return u ? u.nombre_umedida : ''; }
	function factor(desde, hacia) {
		desde = Number(desde); hacia = Number(hacia);
		if (desde && desde === hacia) return 1;
		var a = de(desde), b = de(hacia);
		if (!a || !b || !a.tipo || a.tipo !== b.tipo || !Number(a.factor) || !Number(b.factor)) return null;
		return Number(a.factor) / Number(b.factor);
	}
	/** Unidades de receta en las que se puede escribir la cantidad de este insumo (la suya primero). */
	function delInsumo(ins) {
		var res = [], vistos = {}, ids = [Number(ins.id_umedida_receta)];
		$.each(lista, function (i, u) { ids.push(Number(u.id_umedida)); });
		$.each(ids, function (i, id) {
			if (!id || vistos[id]) return;
			if (aReceta(ins, 1, id) === null) return;
			vistos[id] = true;
			res.push({ id: id, nombre: nombre(id) || (id === Number(ins.id_umedida_receta) ? (ins.nombre_umedida_receta || '') : '') });
		});
		return res;
	}
	function aReceta(ins, cantidad, idUnidad) {
		var rec = Number(ins.id_umedida_receta), inv = Number(ins.id_umedida_inventario), id = Number(idUnidad);
		var mInv = Number(ins.umedida_inventario) || 0, mRec = Number(ins.umedida_receta) || 0;
		cantidad = Number(cantidad) || 0;
		if (!id || id === rec) return cantidad;
		var f = factor(id, rec);
		if (f !== null) return cantidad * f;
		if (inv && mInv > 0 && mRec > 0) {
			var g = id === inv ? 1 : factor(id, inv);
			if (g !== null) return cantidad * g * mRec / mInv;
		}
		return null;
	}
	/** Cantidad en la unidad de inventario a partir de la unidad de receta del insumo. */
	function recetaAInventario(ins, cantidadReceta) {
		var mInv = Number(ins.umedida_inventario) || 1, mRec = Number(ins.umedida_receta) || 1;
		return (Number(cantidadReceta) || 0) * mInv / mRec;
	}
	/** "1.000" / "0,5" / "12,345": hasta 3 decimales, sin ceros de más. */
	function fmt(n) {
		n = Math.round((Number(n) || 0) * 1000) / 1000;
		var p = String(Math.abs(n)).split('.');
		return (n < 0 ? '-' : '') + p[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.') + (p[1] ? ',' + p[1] : '');
	}
	/** Ejecuta fn cuando ya están las equivalencias (al tiro si ya estaban). */
	function listo(fn) { if (cargado) fn(); else cargar().always(function () { cargado = true; fn(); }); }
	return { cargar: cargar, listo: listo, de: de, nombre: nombre, factor: factor, delInsumo: delInsumo, aReceta: aReceta,
		recetaAInventario: recetaAInventario, fmt: fmt, lista: function () { return lista; } };
})();
$(function () { Unidades.cargar(); });
</script>
