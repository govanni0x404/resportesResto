<script>
    var base_url = "<?php echo base_url(); ?>";
    tablaClientes = $('#tablaClientes').DataTable();
    $(document).ready(function () {
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
    
    var $wrapper = $('#tablaClientes').closest('.dataTables_wrapper');
    if ($wrapper.length === 0) {
        $wrapper = $('#tablaClientes').parent();
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
        if ($.fn.dataTable.isDataTable('#tablaClientes')) {
            tablaClientes = $('#tablaClientes').DataTable();
            tablaClientes.destroy();
        }
        tablaClientes = $("#tablaClientes").DataTable({
            autoWidth: false,
            responsive:true,
            columnDefs: [
                {width: "2px", targets: 0},
                { width: "50px", targets: 3 },
                { width: "250px", targets: 4 },
                { width: "220px", targets: 5 },
                { width: "120px", targets: 6 },
                { width: "70px", targets: 7 },
                { width: "70px", targets: 8 },
            ],
            "ajax": {
                "url": base_url + "index.php/Cliente/ListadoDeClientes",
                "type": "POST",
                "data": {
            },
            beforeSend: function () {
                showTableLoader("Cargando Clientes...");
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
                visible: false
            },
            {
                mData: "dv",
                className: "text-center",
                visible: false
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
            {
                mData: "nombre_lprecio",
                className: "text-center",
                visible: false
            },
            {
                mData: "mail",
                className: "text-center",
                visible: false
            },
            {
                mData: "creditomaximo",
                className: "text-center",
                visible: false
            },
            {
                mData: "referencia_direccion",
                className: "text-center",
                visible: false
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
            "buttons": [
            ]
        });
        tablaClientes
            .on('select', function (e, dt, type, indexes) {
                var rowData = tablaClientes.rows(indexes).data().toArray();

                $("#idCliente").val(rowData[0].id_cliente);
                $("#RutCliente").val(rowData[0].rut);
                $("#DvRutCliente").val(rowData[0].dv);
                $("#nombreCliente").val(rowData[0].nombre_cliente);
                $("#DireccionCliente").val(rowData[0].direccion);
                $("#ReferenciaDireccionCliente").val(rowData[0].referencia_direccion);
                $("#TelefonoCliente").val(rowData[0].telefono);
                $("#CorreoCliente").val(rowData[0].mail);
                $("#GiroCliente").val(rowData[0].giro);
                $("#CreditoMaximoCliente").val(rowData[0].creditomaximo);
                ListarComunas(rowData[0].nombre_comuna);
                ListarListasDePrecios(rowData[0].nombre_lprecio);
                $('#ModalNuevoCliente').modal('show');
            })
    }
</script>