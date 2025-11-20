<script>
    var base_url = "<?php echo base_url(); ?>";
    tableListadoDeClientes = $('#tableListadoDeClientes').DataTable();
    $(document).ready(function() {
        ListadoDeClientes();
    });

    function ListadoDeClientes() {
        let tituloExport = "Listado De Clientes";
        if ($.fn.dataTable.isDataTable('#tableListadoDeClientes')) {
            tableListadoDeClientes = $('#tableListadoDeClientes').DataTable();
            tableListadoDeClientes.destroy();
        }
        tableListadoDeClientes = $("#tableListadoDeClientes").DataTable({
            autoWidth: false,
            responsive:true,
            columnDefs: [
                {width: "2px", targets: 0},
                { width: "50px", targets: 3 },
                { width: "180px", targets: 4 },
                { width: "180px", targets: 5 },
                { width: "120px", targets: 6 },
                { width: "70px", targets: 7 },
                { width: "70px", targets: 8 },
            ],
            "ajax": {
                "url": base_url + "index.php/ListadoDeClientes/ListadoDeClientes",
                "type": "POST",
                "data": {
                   
                }
            },
            "bPaginate": true,
            "bProcessing": true,
            "pageLength": 10,
            "select": true,
            "dom": "Bfrtip",
            "ordering": true,
            "filter": true,
            "info": false,
            "footer": false,
            "responsive": {
                details: {
                    type: 'column',
                    target: -1
                }
            },
            "columns": [{
                    mData: "id_cliente",
                    className: "text-center"
                },
                {
                    mData: "rut",
                    className: "text-center",
                    visible:false
                },
                {
                    mData: "dv",
                    className: "text-center",
                     visible:false
                },
                {
                    mData: "rut_completo",
                    className: "text-center"
                },
                {
                    mData: "nombre_cliente",
                    className: "text-left"
                },
                {
                    mData: "giro",
                    className: "text-left",                
                },
                {
                    mData: "direccion",
                    className: "text-left",
                },
                 {
                    mData: "nombre_comuna",
                    className: "text-center",
                },
                 {
                    mData: "telefono",
                    className: "text-center",
                },
            ],
            'language': {
                url: "https://cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json",
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
					text: 'PDF',
					extend: 'pdf',
					exportOptions: {
						modifier: {
							selected: null
						},
						columns: [0, 3, 4, 5, 6, 7, 8]
					},
					extend: 'pdfHtml5',
					orientation: 'landscape',
					pageSize: 'LEGAL',
					title: tituloExport,
					className: "btn btn-danger"
				},
				{
					text: 'EXCEL',
					extend: 'excel',
					exportOptions: {
						modifier: {
							selected: null
						},
						columns: [0, 3, 4, 5, 6, 7, 8]
					},
					title: tituloExport,
					className: "btn btn-success"
				}
			]
        });
    }
</script>