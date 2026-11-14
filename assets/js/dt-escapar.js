/*
 * 14-11-2026 (QA): XSS guardado en los listados.
 * Las tablas DataTables (1.10) escriben cada celda como HTML. Si alguien guardaba "<script>" o "<img onerror>" en un
 * nombre (familia, producto, cliente...), se ejecutaba al abrir el listado en la sesión de quien lo mirara.
 *
 * Regla para TODAS las tablas: en las columnas con nombre de campo (mData/data: "nombre_familia"), lo que llega como
 * texto se muestra como texto. No se escapan:
 *  - las columnas cuyo campo empieza con "btn" (botones que arma el servidor: btnVerDetalle, btn_ver_detalle...),
 *  - las columnas que ya tienen su propio render (esas deciden ellas),
 *  - las tablas que leen el HTML de la página (columna con índice numérico).
 * Cargar después de dataTables.min.js (menuFooter.php).
 */
(function ($) {
	if (!$ || !$.fn || !$.fn.dataTable || !$.fn.dataTable.defaults) return;

	var mapa = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
	function escapar(s) {
		return String(s).replace(/[&<>"']/g, function (c) { return mapa[c]; });
	}
	window.escaparHtml = window.escaparHtml || escapar;

	$.fn.dataTable.defaults.column.mRender = function (data, type, row, meta) {
		if (type !== 'display' || typeof data !== 'string') return data;
		var col = meta && meta.settings && meta.settings.aoColumns ? meta.settings.aoColumns[meta.col] : null;
		var campo = col ? col.mData : null;
		if (typeof campo !== 'string') return data;      // tabla armada con el HTML de la página
		if (/^btn/i.test(campo)) return data;             // botones del servidor
		return escapar(data);
	};
})(window.jQuery);
