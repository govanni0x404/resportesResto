<script>
	/* Consultas > Anulación de ventas (10-11-2026). Datos: AnulacionVentas/listado (models/Mauditoriacaja.php). */
	(function () {
		var url = "<?php echo base_url(); ?>index.php/AnulacionVentas/listado";
		var datos = null;

		function fmt(n) {
			n = Math.round(Number(n) || 0);
			return (n < 0 ? '-$' : '$') + Math.abs(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
		}
		function esc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }
		function vacio(t) { return '<div class="aud-vacio"><i class="fa fa-check-circle-o"></i>' + esc(t) + '</div>'; }

		$('#date-range-picker').daterangepicker({
			ranges: dateRange,
			startDate: moment().subtract(6, 'days'),
			endDate: moment(),
			locale: {
				format: 'DD-MM-YYYY', separator: ' - ', applyLabel: 'Aplicar', cancelLabel: 'Cancelar', fromLabel: 'Desde', toLabel: 'Hasta',
				customRangeLabel: 'Rango personalizado', weekLabel: 'S', daysOfWeek: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sá'],
				monthNames: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
				firstDay: 1
			}
		});

		function consultar() {
			var p = $('#date-range-picker').data('daterangepicker');
			$('#anTabla tbody').html('<tr><td colspan="5" class="aud-vacio">Cargando…</td></tr>');
			$.ajax({ url: url, type: 'POST', dataType: 'json',
				data: { desde: p.startDate.format('YYYY-MM-DD'), hasta: p.endDate.format('YYYY-MM-DD'), tipo: $('#cboTipo').val() } })
				.done(function (r) {
					datos = r;
					pintar();
					$('#btnImprimir, #btnCsv').prop('disabled', !r.filas.length);
				}).fail(function (x) {
					$('#anTabla tbody').html('<tr><td colspan="5" class="aud-vacio">No se pudieron cargar las anulaciones</td></tr>');
					var m = (x.responseJSON && x.responseJSON.error) || 'No se pudieron cargar las anulaciones';
					if (window.toastr) toastr.error(m);
				});
		}

		function pintar() {
			var t = datos.totales;
			$('#anKpis').html(
				'<div class="aud-kpi"><small>Anulaciones</small><b>' + t.cantidad + '</b><span>en el período</span></div>'
				+ '<div class="aud-kpi"><small>Monto anulado</small><b>' + fmt(t.monto) + '</b><span>suma de lo anulado</span></div>'
				+ '<div class="aud-kpi"><small>Ventas en espera anuladas</small><b>' + t.espera + '</b><span>' + fmt(t.monto_espera) + (datos.bitacora ? '' : ' · la base aún no tiene ventas en espera') + '</span></div>'
				+ '<div class="aud-kpi"><small>Documentos anulados</small><b>' + t.documento + '</b><span>' + fmt(t.monto_documento) + '</span></div>');
			var max = 0;
			$.each(t.usuarios, function (i, u) { max = Math.max(max, u.monto); });
			$('#anUsuarios').html(t.usuarios.length ? $.map(t.usuarios, function (u) {
				return '<div class="fila"><span class="nombre">' + esc(u.usuario) + '</span><span class="valor">' + fmt(u.monto) + '</span>'
					+ '<div class="barra"><i style="width:' + (max > 0 ? Math.max(2, Math.round(u.monto / max * 100)) : 0) + '%"></i></div>'
					+ '<span class="sub">' + u.cantidad + (u.cantidad === 1 ? ' anulación' : ' anulaciones') + '</span></div>';
			}).join('') : vacio('Sin anulaciones'));
			filtrar();
		}

		function filtrar() {
			var q = $.trim($('#anBuscar').val()).toLowerCase();
			var filas = $.grep(datos.filas, function (f) {
				return !q || (f.referencia + ' ' + f.detalle + ' ' + f.motivo + ' ' + f.usuario + ' ' + f.origen).toLowerCase().indexOf(q) >= 0;
			});
			var $t = $('#anTabla tbody').empty();
			if (!filas.length) { $t.html('<tr><td colspan="5">' + vacio(q ? 'Nada coincide con la búsqueda' : 'Sin anulaciones en el período') + '</td></tr>'); return; }
			$.each(filas, function (i, f) {
				var chip = f.tipo === 'espera' ? '<span class="aud-chip alerta">Venta en espera</span>' : '<span class="aud-chip mal">Documento</span>';
				$t.append('<tr><td>' + esc(f.hora) + '<span class="sub">Caja N° ' + f.id_caja + '</span></td>'
					+ '<td>' + chip + ' <b>' + esc(f.referencia) + '</b>' + (f.detalle ? '<span class="sub">' + esc(f.detalle) + '</span>' : '')
					+ (f.origen ? '<span class="sub">' + esc(f.origen) + '</span>' : '') + '</td>'
					+ '<td>' + esc(f.motivo || '—') + '</td><td>' + esc(f.usuario || '—') + '</td><td class="num">' + fmt(f.monto) + '</td></tr>');
			});
		}
		$('#anBuscar').on('input', function () { if (datos) filtrar(); });
		$('#btnConsultar').on('click', consultar);
		$('#cboTipo').on('change', consultar);
		$('#btnImprimir').on('click', function () { window.print(); });
		$('#btnCsv').on('click', function () {
			if (!datos) return;
			var c = function (v) { v = v == null ? '' : String(v); return /[";\n]/.test(v) ? '"' + v.replace(/"/g, '""') + '"' : v; };
			var filas = [['Fecha', 'Tipo', 'Qué se anuló', 'Detalle', 'Justificación', 'Usuario', 'Caja', 'Monto']];
			$.each(datos.filas, function (i, f) {
				filas.push([f.fecha, f.tipo === 'espera' ? 'Venta en espera' : 'Documento', f.referencia, f.detalle + (f.origen ? ' · ' + f.origen : ''), f.motivo, f.usuario, f.id_caja, f.monto]);
			});
			var csv = '﻿' + $.map(filas, function (f) { return $.map(f, c).join(';'); }).join('\r\n');
			var a = document.createElement('a');
			a.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }));
			a.download = 'anulacion_ventas_' + datos.desde + '_' + datos.hasta + '.csv';
			document.body.appendChild(a);
			a.click();
			setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 500);
		});
		$(function () { consultar(); });
	})();
</script>
