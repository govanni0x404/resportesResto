<script>
    var base_url = "<?php echo base_url(); ?>";
    tableListadoDeClientes = $('#tableListadoDeClientes').DataTable();
    $(document).ready(function() {
        ListadoDeClientes();
    });

    function showTableLoader(message) {
    if ($('#dynamic-table-loader').length) {
        $('#dynamic-table-loader').show();
        return;
    }
    
    var loaderHtml = 
        '<div id="dynamic-table-loader" style="' +
        'position: absolute; top: 0; left: 0; right: 0; bottom: 0; ' +
        'background: rgba(255, 255, 255, 0.95); ' +
        'display: flex; flex-direction: column; align-items: center; justify-content: center; ' +
        'z-index: 100; gap: 12px;">' +
        '<div style="width: 40px; height: 40px; border: 4px solid rgba(0,123,255,0.2); ' +
        'border-top-color: #007bff; border-radius: 50%; animation: spin 0.8s linear infinite;"></div>' +
        '<div style="color: #333; font-size: 14px;">' + (message || 'Cargando...') + '</div>' +
        '</div>';
    
    var $wrapper = $('#tableListadoDeClientes').closest('.dataTables_wrapper');
    if ($wrapper.length === 0) {
        $wrapper = $('#tableListadoDeClientes').parent();
    }
    
    $wrapper.css('position', 'relative');
    $wrapper.append(loaderHtml);
    }

    function hideTableLoader() {
        $('#dynamic-table-loader').fadeOut(500, function() {
            $(this).remove();
        });
    }

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
                },
                beforeSend: function () {
                    showTableLoader("Cargando Listado de Clientes...");
                },
                complete: function () {
                    hideTableLoader();
                },
                error: function () {
                    hideTableLoader();
                }
            },
            "bPaginate": true,
            "bProcessing": false,
            "pageLength": 15,
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
                    className: "text-left",
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
                text: "PDF",
                extend: 'pdfHtml5',
                orientation: 'landscape',
                pageSize: 'LEGAL',
                title: tituloExport,
                className: "btn btn-danger",
                exportOptions: {
                    modifier: {
                        selected: null
                    },
                    columns: [0, 3, 4, 5, 6, 7, 8]
                }
            }, {
                text: "Excel",
                extend: 'excelHtml5',
                title: tituloExport,
                className: "btn btn-success",
                exportOptions: {
                    modifier: {
                        selected: null
                    },
                    columns: [0, 3, 4, 5, 6, 7, 8]
                }
            }]
        });
    }
</script>