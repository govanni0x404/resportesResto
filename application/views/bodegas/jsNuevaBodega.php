<style>
	/* Formularios en modales (.t-form): filas flexibles en vez de floats + <br>, etiquetas claras y móvil a una columna */
	.t-form fieldset { display: flex; flex-wrap: wrap; align-items: flex-end; margin: 0 -8px; min-width: 0; }
	.t-form fieldset > * { flex: 0 0 100%; max-width: 100%; padding-left: 8px; padding-right: 8px; }
	.t-form fieldset > [class*="col-"] { float: none; flex: 0 0 auto; margin-bottom: 12px; }
	.t-form fieldset > .table-responsive, .t-form fieldset > .nav-tabs { margin-left: 8px; margin-right: 8px; max-width: calc(100% - 16px); padding-left: 0; padding-right: 0; }
	.t-form label { font-size: 12px; text-transform: uppercase; letter-spacing: .3px; color: var(--t-texto-suave); font-weight: 700; margin-bottom: 5px; }
	.t-form .checkbox label { text-transform: none; letter-spacing: 0; font-size: 13px; color: var(--t-texto); }
	.t-form label.t-card-titulo { display: block; margin: 4px 0 10px; padding-bottom: 6px; border-bottom: 1px solid var(--t-borde-suave); }
	.t-form .form-control.input-sm { height: 36px; font-size: 14px; }
	.t-form textarea, .t-form textarea.form-control { border: 1px solid var(--t-borde, #EADFD2); border-radius: var(--t-radio-sm); padding: 8px 10px; height: auto; }
	.t-form textarea:focus { outline: none; border-color: var(--t-primario); box-shadow: 0 0 0 3px rgba(180, 67, 46, .12); }
	.t-form sup { top: -.3em; }
	.t-form sup a { cursor: pointer; font-weight: 600; text-transform: none; color: var(--t-primario); margin-left: 4px; }
	.t-form .nav-tabs { margin-bottom: 14px; }
	.t-form .tab-pane > fieldset:first-child { margin-top: 2px; }
	.modal-footer .btn i[class=""] { display: none; }
	@media (max-width: 991px) {
		.t-form fieldset > [class*="col-md-"]:not([class*="col-sm-"]):not([class*="col-xs-"]) { width: 100%; }
		.t-form fieldset > .col-md-1:empty, .t-form fieldset > .col-md-2:empty, .t-form fieldset > .col-md-5:empty, .t-form fieldset > .col-md-8:empty { display: none; }
	}
</style>
<style>
	/* La pestaña vacía solo dejaba un espacio en blanco sobre el formulario */
	#ModalNuevaBodega .nav-with { display: none; }
</style>
<!--  MODAL NUEVO REGISTRO  -->
<div id="ModalNuevaBodega" class="modal fade" role="dialog" style="overflow-y: scroll;">
	<div class="modal-dialog">
		<div class="modal-content">
			<!-- TITULO  MODAL  -->
			<div id="tituloModalUsuario" class="modal-header btn-success">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title text text-center">REGISTRO DE BODEGAS
				</h4>
			</div>
			<!-- FIN TITULO  MODAL  -->

			<!-- CONTENIDO  MODAL  -->
			<div class="modal-body t-form">
				<div class="h-panel">
					<ul id="myTab" class="nav nav-with title font">
						<li class="active"><a href="#tabBodegas" data-toggle="tab" id="tab1" class="text color1"></a></li>
					</ul>
					<div class="tab-pane fade active in" id="tabBodegas">
						<!-- CAMPOS DE REGISTRO  -->
						<fieldset>
							<div class="col-md-4 ">
								<label for="idBodega">ID</label>
								<input type="number" disabled class="form-control input-sm input-sm" name="idBodega" id="idBodega" requerido="no" value="" />
							</div>
							<div class="col-md-10 ">
								<label for="nombreBodega">NOMBRE BODEGA *</label>
								<input type="text" class="form-control input-sm input-sm" maxlength='50' onkeypress="return sololetrasyNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="nombreBodega" id="nombreBodega" requerido="si" value="" placeholder="Nombre Bodega" />
							</div>
						</fieldset>
						<!-- FIN CAMPOS DE REGISTRO  -->
					</div>
				</div>
			</div>
			<!-- CONTENIDO  MODAL  -->

			<!-- BOTONES  -->
			<div class="modal-footer">
				<button onclick="GrabarNuevaBodega()" type="button" class="btn btn-primary"><i class=""></i>GRABAR</button>
				<button type="button" class="btn btn-default" data-dismiss="modal"><i class=""></i>SALIR</button>
			</div>
			<!-- FIN BOTONES  -->
		</div>
	</div>
</div>
<!-- FIN  MODAL NUEVO REGISTRO  -->

<script>
	function AbrirModalNuevaBodega() {
		$('#idBodega').val("");
		$('#nombreBodega').val("");
		$('#ModalNuevaBodega').modal({
			backdrop: 'static',
			keyboard: false
		});
	}

	function GrabarNuevaBodega() {
		if ($('#nombreBodega').val() == 0) {
			showAppAlert('error',"No Ingreso El Nombre De La Bodega!", {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
		} else {
			idBodega = $('#idBodega').val();
			NombreBodega = $('#nombreBodega').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
			var miJson = {
				'id_bodega': idBodega,
				'nombre_bodega': NombreBodega
			};
			console.log(miJson);
			$.post(base_url + "index.php/Bodegas/grabarNuevaBodega", {
					'prod': miJson
				}).done(function() {
					showAppAlert('success', "Se registro Exitosamente La Nueva Bodega!", {
                        duration: 4000,
                        image: base_url + 'assets/img/success.gif'
                    });
					$('#ModalNuevaBodega').modal('hide');
					tablaBodegas.ajax.reload();
				})
				.fail(function() {
					showAppAlert('error',"Se produjo un error al realizar el proceso, favor contacte a su administrador.", {
                        duration: 5000,
                        image: base_url+ 'assets/img/errorGif.gif'
                    });
				})
				.always(function() {});
		}
	}

</script>
