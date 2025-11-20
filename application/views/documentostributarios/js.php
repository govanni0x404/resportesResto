<script>
	var base_url = "<?php echo base_url(); ?>";
	tableDocumentosTributarios = $('#tableDocumentosTributarios').DataTable();


	$(document).ready(function() {
		// listarDocumentosTributarios();
	});

	function listarDocumentosTributarios() {
		if ($.fn.dataTable.isDataTable('#tableDocumentosTributarios')) {
			tableDocumentosTributarios = $('#tableDocumentosTributarios').DataTable();
			tableDocumentosTributarios.destroy();
		}
		// Customize this variable to hide the columns you want
		var columnas = [6, 7, 8, 9, 10, 11, 12, 13, 14, 15];
		tableDocumentosTributarios = $("#tableDocumentosTributarios").DataTable({
			"ajax": {
				"url": base_url + "index.php/DocumentosTributarios/listadoDocumentosTributarios"
			},
			"bPaginate": true,
			"bProcessing": true,
			"pageLength": 10,
			"select": true,
			"dom": "Bfrtip",
			"ordering": false,
			"select": {
				style: 'single', //permite elegir solo 1 fila
				style: 'os',
				selector: 'td:not(:last-child)' //esto permite que al presionar botones no se active el select de la tabla (detalle)
			},
			"responsive": {
				details: {
					type: 'column',
					target: -1
				}
			},
			"columns": [{
					mData: "id_tdocumento",
					className: "text-right",
					"visible": false,
					// width: "1%;",
				},
				{
					mData: "nombre_tdocumento",
					className: "text-center",
					width: "10%",
				},
				{
					mData: "cod_sii",
					className: "text-left",
					width: "40%",
				},
				{
					mData: "responsive",
					"width": "1%",
					className: 'dtr-control',
					orderable: false,
					targets: -1
				}
			],
			'language': {
				url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json",
				// traduce la linea de abajo que quedaba en ingles
				select: {
					rows: {
						_: " (Seleccionadas %d filas)",
						0: " (Presione una linea a seleccionar)",
						1: " (1 Fila seleccionada)"
					}
				}
			},
			"buttons": [{
					text: 'Nuevo',
					action: function(e, dt, node, config) {
						
					}
				}
			]
		});
		tableDocumentosTributarios
			.on('select', function(e, dt, type, indexes) {

			})
	}

</script>
