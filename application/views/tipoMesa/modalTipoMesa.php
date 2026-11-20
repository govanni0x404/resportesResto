<?php
/*
 * v1.21.2: modal de crear / editar tipo de mesa, el MISMO en Mantención > Recintos > Tipos de mesa y en Mesas
 * (Editar mesa y Cambiar varias mesas a otro tipo). Guarda con TipoMesa/guardar (sus permisos y validaciones).
 *
 *   TipoMesaModal.abrir(tipo, alGuardar)
 *     tipo      = null para uno nuevo, o {id, nombre, prefijo, espacio, vigente} para editar
 *     alGuardar = function (r) con la respuesta del servidor: r.id (el tipo guardado) y r.tipos (todos)
 * Se puede abrir encima de otro modal (queda arriba, con su propio fondo).
 */
?>
<style>
    #modalTipoMesa .modal-dialog { max-width: 560px; width: auto; }
    #modalTipoMesa fieldset { margin: 0 -8px; }
    #modalTipoMesa fieldset::after { content: ""; display: table; clear: both; }
    #modalTipoMesa fieldset > [class*="col-"] { padding: 0 8px; margin-bottom: 14px; }
    #modalTipoMesa label.tm-etq { font-size: 12px; color: var(--t-texto-suave); text-transform: uppercase; letter-spacing: .3px; margin-bottom: 5px; }
    #modalTipoMesa .help-block { font-size: 12px; margin: 4px 0 0; }
    .tm-opciones { display: flex; flex-wrap: wrap; gap: 8px; }
    .tm-opciones label { display: inline-flex; align-items: center; gap: 6px; margin: 0; padding: 7px 12px; border: 1px solid var(--t-borde); border-radius: var(--t-radio-sm);
        background: var(--t-fondo-alt); font-size: 13px; font-weight: 600; color: var(--t-texto); cursor: pointer; }
    .tm-opciones input { margin: 0; }
    .tm-vista { display: flex; align-items: center; gap: 10px; padding: 12px 14px; border: 1px dashed var(--t-borde); border-radius: var(--t-radio-sm); background: var(--t-fondo-alt); }
    .tm-vista span { font-size: 12px; color: var(--t-texto-suave); text-transform: uppercase; letter-spacing: .3px; }
    .tm-vista b { font-size: 18px; color: var(--t-texto-fuerte); }
    #modalTipoMesa .modal-footer .btn i { margin-right: 4px; }
    #modalTipoMesa .modal-header { border-top: 3px solid var(--t-primario); }
</style>
<div class="modal fade" id="modalTipoMesa" tabindex="-1" role="dialog" aria-labelledby="tituloTipoMesa">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="frmTipoMesa" autocomplete="off">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="tituloTipoMesa">Nuevo tipo de mesa</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="tmId" value="0">
                    <fieldset>
                        <div class="col-sm-6">
                            <label class="tm-etq" for="tmNombre">Nombre del tipo</label>
                            <input type="text" class="form-control" name="nombre" id="tmNombre" maxlength="40" placeholder="Ej: Delivery" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="tm-etq" for="tmPrefijo">Texto antes del número</label>
                            <input type="text" class="form-control" name="prefijo" id="tmPrefijo" maxlength="20" placeholder="Ej: D (vacío = Mesa 1)">
                        </div>
                        <div class="col-sm-12">
                            <div class="tm-opciones">
                                <label><input type="checkbox" id="tmEspacio"> Con espacio antes del número</label>
                                <label><input type="checkbox" id="tmVigente" checked> Vigente</label>
                            </div>
                            <p class="help-block" id="tmAvisoDefecto" style="display:none"><i class="fa fa-star"></i> Es el tipo <b>por defecto</b>: las mesas nuevas y las que no tienen otro tipo son de él. Puedes cambiarle el nombre o el texto, pero no desactivarlo.</p>
                            <p class="help-block">Sin espacio: <b>Mesa D1</b>. Con espacio: <b>Mesa EVENT 1</b>. Un tipo no vigente no se puede elegir para mesas nuevas.</p>
                        </div>
                        <div class="col-sm-12">
                            <div class="tm-vista"><span>Así sale la mesa 1</span><b id="tmVista">Mesa 1</b></div>
                        </div>
                    </fieldset>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success" id="btnGuardarTipo"><i class="fa fa-save"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
	(function () {
		var url = "<?php echo base_url(); ?>index.php/TipoMesa/guardar";
		var alGuardar = null;

		// Igual que Mtipomesa::nombre
		function nombreMesa(n, prefijo, espacio) {
			prefijo = $.trim(String(prefijo || '').replace(/\s+/g, ' '));
			return 'Mesa ' + (prefijo ? prefijo + (espacio ? ' ' : '') : '') + n;
		}
		function vista() {
			$('#tmVista').text(nombreMesa(1, $('#tmPrefijo').val(), $('#tmEspacio').is(':checked')));
		}
		function error(xhr, porDefecto) {
			var msg = (xhr && xhr.responseJSON && xhr.responseJSON.error)
				|| (xhr && xhr.status === 403 ? 'No tienes permiso para crear o editar tipos de mesa' : porDefecto);
			if (xhr && xhr.status === 401) {
				swal({ title: 'Sesión expirada', text: msg, type: 'warning' }, function () { location.href = "<?php echo base_url(); ?>"; });
				return;
			}
			swal('Error', msg, 'error');
		}
		$('#tmPrefijo').on('input', vista);
		$('#tmEspacio').on('change', vista);

		var $m = $('#modalTipoMesa');
		$m.on('shown.bs.modal', function () { $('#tmNombre').focus(); });
		// v1.24.3: encima de otra ventana lo resuelve menuFooter (ventana sobre ventana, fondo borroso)

		window.TipoMesaModal = {
			abrir: function (t, fn) {
				alGuardar = fn || null;
				$('#tituloTipoMesa').text(t ? 'Editar tipo de mesa' : 'Nuevo tipo de mesa');
				$('#tmId').val(t ? t.id : 0);
				$('#tmNombre').val(t ? t.nombre : '');
				$('#tmPrefijo').val(t ? t.prefijo : '');
				$('#tmEspacio').prop('checked', t ? !!t.espacio : false);
				// v1.22.0: el tipo por defecto no se desactiva
				$('#tmVigente').prop('checked', t ? !!t.vigente : true).prop('disabled', !!(t && t.defecto))
					.closest('label').attr('title', t && t.defecto ? 'El tipo por defecto siempre está vigente' : '');
				$('#tmAvisoDefecto').toggle(!!(t && t.defecto));
				vista();
				$m.modal('show');
			}
		};

		$('#frmTipoMesa').on('submit', function (e) {
			e.preventDefault();
			var d = {
				id: $('#tmId').val(), nombre: $.trim($('#tmNombre').val()), prefijo: $.trim($('#tmPrefijo').val()),
				espacio: $('#tmEspacio').is(':checked') ? 1 : 0, vigente: $('#tmVigente').is(':checked') ? 1 : 0
			};
			if (!d.nombre) { toastr.warning('Escribe el nombre del tipo de mesa'); $('#tmNombre').focus(); return; }
			var $b = $('#btnGuardarTipo').prop('disabled', true);
			$.ajax({ url: url, type: 'POST', dataType: 'json', data: d }).done(function (r) {
				$m.modal('hide');
				toastr.success('Tipo de mesa guardado');
				if (alGuardar) alGuardar(r);
			}).fail(function (xhr) { error(xhr, 'No se pudo guardar el tipo de mesa'); })
				.always(function () { $b.prop('disabled', false); });
		});
	})();
</script>
