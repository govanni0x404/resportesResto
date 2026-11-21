<?php /* v1.28.0 (PRUEBA): reservas de mesas. Controlador Reservas, modelo Mreservas. */ ?>
<style>
	.r-barra { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; margin: 0 0 14px; }
	.r-barra h1 { margin: 0 auto 0 0; font-size: 22px; font-weight: 800; color: #2A1D17; display: flex; align-items: center; gap: 10px; }
	.r-barra h1 .r-prueba { font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 6px; background: #fef3c7; color: #a16207; }
	.r-barra .btn, .r-barra input { height: 40px; }
	.r-dia { display: flex; align-items: center; gap: 6px; }
	.r-dia input { width: 160px; }
	.r-resumen { color: #7A6A5E; margin: 0 0 10px; }
	.r-lista { display: flex; flex-direction: column; gap: 10px; }
	.r-res { display: flex; flex-wrap: wrap; align-items: center; gap: 10px 16px; padding: 12px 14px; border-radius: 12px; background: #fff;
		border: 1px solid #e5ddd3; border-left: 6px solid #1f5fa8; box-shadow: 0 3px 10px rgba(42, 29, 23, .05); }
	.r-res.llego { border-left-color: #15803d; }
	.r-res.cancelada, .r-res.no_vino { border-left-color: #9a8b80; opacity: .65; }
	.r-hora { font-size: 22px; font-weight: 800; min-width: 64px; font-variant-numeric: tabular-nums; }
	.r-info { flex: 1 1 220px; min-width: 0; }
	.r-info b { font-size: 16px; }
	.r-info small { display: block; color: #7A6A5E; }
	.r-info .r-nota { color: #b91c1c; font-weight: 600; }
	.r-estado { font-size: 12px; font-weight: 700; padding: 3px 10px; border-radius: 99px; background: #e8f0fb; color: #1f5fa8; white-space: nowrap; }
	.r-res.llego .r-estado { background: #e7f6ec; color: #15803d; }
	.r-res.cancelada .r-estado, .r-res.no_vino .r-estado { background: #eee; color: #555; }
	.r-acciones { display: flex; flex-wrap: wrap; gap: 6px; }
	.r-vacio { text-align: center; padding: 50px 16px; color: #7A6A5E; font-size: 16px; }
	.r-barra .btn, .r-acciones .btn { margin: 0; }
	@media (max-width: 600px) { .r-barra h1 { width: 100%; } .r-dia { flex: 1 1 100%; } .r-dia input { width: auto; flex: 1 1 auto; min-width: 0; }
		.r-dia .btn { width: auto; } #rNueva { flex: 1 1 100%; }
		.r-acciones { width: 100%; } .r-acciones .btn { flex: 1 1 auto; width: auto; } }
</style>

<div class="r-barra">
	<h1><i class="fa fa-calendar-check-o" aria-hidden="true"></i> Reservas <span class="r-prueba">PRUEBA</span></h1>
	<div class="r-dia">
		<button type="button" class="btn btn-default" id="rAntes" title="Día anterior" aria-label="Día anterior"><i class="fa fa-chevron-left"></i></button>
		<label class="sr-only" for="rFecha">Día</label>
		<input type="date" class="form-control" id="rFecha">
		<button type="button" class="btn btn-default" id="rDespues" title="Día siguiente" aria-label="Día siguiente"><i class="fa fa-chevron-right"></i></button>
		<button type="button" class="btn btn-default" id="rHoy">Hoy</button>
	</div>
	<button type="button" class="btn btn-success" id="rNueva"<?php echo $disponible ? '' : ' disabled'; ?>><i class="fa fa-plus"></i> Nueva reserva</button>
</div>
<?php if (!$disponible) : ?>
<div class="alert alert-warning">Falta actualizar la base de esta empresa (migración de reservas). Recarga la página en un momento.</div>
<?php endif; ?>
<p class="r-resumen" id="rResumen"></p>
<div class="r-lista" id="rLista"><div class="r-vacio"><i class="fa fa-spinner fa-spin"></i> Cargando…</div></div>

<div class="modal fade" id="modalReserva" tabindex="-1" role="dialog" aria-labelledby="rTitulo">
	<div class="modal-dialog" role="document">
		<form class="modal-content" id="rForm" novalidate>
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title" id="rTitulo"><i class="fa fa-calendar-plus-o"></i> Nueva reserva</h4>
			</div>
			<div class="modal-body">
				<input type="hidden" id="rId" value="0">
				<div class="row">
					<div class="col-sm-6 form-group"><label for="rNombre">Nombre</label><input type="text" class="form-control" id="rNombre" maxlength="80" required></div>
					<div class="col-sm-6 form-group"><label for="rTelefono">Teléfono</label><input type="tel" class="form-control" id="rTelefono" maxlength="30" placeholder="+56 9 1234 5678"></div>
				</div>
				<div class="row">
					<div class="col-xs-6 col-sm-4 form-group"><label for="rDia">Día</label><input type="date" class="form-control" id="rDia" required></div>
					<div class="col-xs-6 col-sm-4 form-group"><label for="rHora">Hora</label><input type="time" class="form-control" id="rHora" required></div>
					<div class="col-xs-12 col-sm-4 form-group"><label for="rPersonas">Personas</label><input type="number" class="form-control" id="rPersonas" min="1" max="200" value="2" required></div>
				</div>
				<div class="form-group">
					<label for="rMesa">Mesa (opcional)</label>
					<select class="form-control" id="rMesa">
						<option value="0">Sin mesa asignada</option>
						<?php foreach ($mesas as $m) : ?>
						<option value="<?php echo (int)$m['id']; ?>"><?php echo html_escape($m['nombre']); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="form-group"><label for="rNota">Nota (alergias, cumpleaños, silla de niño…)</label><input type="text" class="form-control" id="rNota" maxlength="250"></div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
				<button type="submit" class="btn btn-success" id="rGrabar"><i class="fa fa-check"></i> Grabar</button>
			</div>
		</form>
	</div>
</div>

<script>
	// jQuery se carga al final de la página (menuFooter): se espera a que termine de cargar
	document.addEventListener('DOMContentLoaded', function () {
		var URL = '<?php echo base_url() ?>index.php/Reservas/';
		var ESTADOS = { pendiente: 'Por llegar', llego: 'Llegó', cancelada: 'Cancelada', no_vino: 'No vino' };
		var reservas = [];
		function hoy() { var d = new Date(); d.setMinutes(d.getMinutes() - d.getTimezoneOffset()); return d.toISOString().slice(0, 10); }
		function mover(dias) {
			var d = new Date($('#rFecha').val() + 'T12:00:00');
			d.setDate(d.getDate() + dias);
			$('#rFecha').val(d.toISOString().slice(0, 10));
			cargar();
		}
		function esc(t) { return $('<span>').text(t == null ? '' : String(t)).html(); }
		function aviso(x, def) { toastr.error((x && x.responseJSON && x.responseJSON.error) || def); }

		function pintar() {
			var $l = $('#rLista').empty(), activas = 0, personas = 0;
			$.each(reservas, function (i, r) {
				if (r.estado !== 'cancelada' && r.estado !== 'no_vino') { activas++; personas += r.personas; }
				var acciones = '';
				if (r.estado === 'pendiente') acciones += '<button type="button" class="btn btn-success btn-sm r-est" data-e="llego"><i class="fa fa-check"></i> Llegó</button>'
					+ '<button type="button" class="btn btn-default btn-sm r-est" data-e="no_vino">No vino</button>'
					+ '<button type="button" class="btn btn-default btn-sm r-est" data-e="cancelada">Cancelar</button>';
				else acciones += '<button type="button" class="btn btn-default btn-sm r-est" data-e="pendiente"><i class="fa fa-undo"></i> Volver a por llegar</button>';
				acciones += '<button type="button" class="btn btn-default btn-sm r-editar"><i class="fa fa-pencil"></i> Editar</button>';
				$l.append('<div class="r-res ' + esc(r.estado) + '" data-id="' + r.id + '">'
					+ '<div class="r-hora">' + esc(r.hora) + '</div>'
					+ '<div class="r-info"><b>' + esc(r.nombre) + '</b> · ' + r.personas + (r.personas === 1 ? ' persona' : ' personas')
					+ '<small>' + (r.id_mesa ? 'Mesa ' + r.id_mesa : 'Sin mesa') + (r.telefono ? ' · <a href="tel:' + esc(r.telefono.replace(/\s/g, '')) + '">' + esc(r.telefono) + '</a>' : '') + '</small>'
					+ (r.nota ? '<small class="r-nota"><i class="fa fa-exclamation-circle"></i> ' + esc(r.nota) + '</small>' : '') + '</div>'
					+ '<span class="r-estado">' + esc(ESTADOS[r.estado] || r.estado) + '</span>'
					+ '<div class="r-acciones">' + acciones + '</div></div>');
			});
			if (!reservas.length) $l.append('<div class="r-vacio"><i class="fa fa-calendar-o"></i> No hay reservas para este día.</div>');
			$('#rResumen').text(activas ? activas + (activas === 1 ? ' reserva' : ' reservas') + ' · ' + personas + ' personas' : '');
		}
		function cargar() {
			$.ajax({ url: URL + 'listado', data: { fecha: $('#rFecha').val() }, dataType: 'json' })
				.done(function (r) { reservas = r.reservas || []; pintar(); })
				.fail(function (x) { $('#rLista').html('<div class="r-vacio">' + esc((x.responseJSON && x.responseJSON.error) || 'No se pudieron cargar las reservas') + '</div>'); });
		}
		function abrir(r) {
			r = r || { id: 0, nombre: '', telefono: '', fecha: $('#rFecha').val(), hora: '21:00', personas: 2, id_mesa: 0, nota: '' };
			$('#rTitulo').html(r.id ? '<i class="fa fa-pencil"></i> Editar reserva' : '<i class="fa fa-calendar-plus-o"></i> Nueva reserva');
			$('#rId').val(r.id); $('#rNombre').val(r.nombre); $('#rTelefono').val(r.telefono); $('#rDia').val(r.fecha);
			$('#rHora').val(r.hora); $('#rPersonas').val(r.personas); $('#rMesa').val(String(r.id_mesa)); $('#rNota').val(r.nota);
			$('#modalReserva').modal('show');
		}
		$('#modalReserva').on('shown.bs.modal', function () { $('#rNombre').trigger('focus'); });
		$('#rForm').on('submit', function (e) {
			e.preventDefault();
			var $b = $('#rGrabar').prop('disabled', true);
			$.ajax({ url: URL + 'guardar', type: 'POST', dataType: 'json', data: { id: $('#rId').val(), reserva: {
				nombre: $('#rNombre').val(), telefono: $('#rTelefono').val(), fecha: $('#rDia').val(), hora: $('#rHora').val(),
				personas: $('#rPersonas').val(), id_mesa: $('#rMesa').val(), nota: $('#rNota').val() } } })
				.done(function (r) {
					$('#modalReserva').modal('hide');
					toastr.success('Reserva grabada');
					$('#rFecha').val(r.reserva.fecha);
					cargar();
				})
				.fail(function (x) { aviso(x, 'No se pudo grabar la reserva'); })
				.always(function () { $b.prop('disabled', false); });
		});
		$('#rLista').on('click', '.r-est', function () {
			var id = $(this).closest('.r-res').data('id');
			$.ajax({ url: URL + 'estado', type: 'POST', dataType: 'json', data: { id: id, estado: $(this).data('e') } })
				.done(cargar).fail(function (x) { aviso(x, 'No se pudo cambiar la reserva'); });
		}).on('click', '.r-editar', function () {
			var id = $(this).closest('.r-res').data('id');
			abrir($.grep(reservas, function (r) { return r.id === id; })[0]);
		});
		$('#rNueva').on('click', function () { abrir(null); });
		$('#rAntes').on('click', function () { mover(-1); });
		$('#rDespues').on('click', function () { mover(1); });
		$('#rHoy').on('click', function () { $('#rFecha').val(hoy()); cargar(); });
		$('#rFecha').on('change', cargar).val(hoy());
		<?php if ($disponible) : ?>cargar();<?php else : ?>$('#rLista').empty();<?php endif; ?>
	});
</script>
