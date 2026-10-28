<script>
    var base_url = "<?php echo base_url(); ?>";
    tablaProveedor = $('#tablaProveedor').DataTable();

    $(document).ready(function () {
        ListadoDeProveedores();
    });

    function showTableLoader(message) {
    if ($('#dynamic-table-loader').length) {
        $('#dynamic-table-loader').show();
        return;
    }
    
    var loaderHtml = 
        '<div id="dynamic-table-loader" style="' +
        'position: absolute; top: 0; left: 0; right: 0; bottom: 0; ' +
        'background: rgba(246, 241, 232, 0.95); ' +
        'display: flex; flex-direction: column; align-items: center; justify-content: center; ' +
        'z-index: 100; gap: 12px;">' +
        '<div style="width: 40px; height: 40px; border: 4px solid rgba(180, 67, 46,0.18); ' +
        'border-top-color: #B4432E; border-radius: 50%; animation: spin 0.8s linear infinite;"></div>' +
        '<div style="color: #3A2A22; font-size: 14px;">' + (message || 'Cargando...') + '</div>' +
        '</div>';
    
    var $wrapper = $('#tablaProveedor').closest('.dataTables_wrapper');
    if ($wrapper.length === 0) {
        $wrapper = $('#tablaProveedor').parent();
    }
    
    $wrapper.css('position', 'relative');
    $wrapper.append(loaderHtml);
    }   

    function hideTableLoader() {
        $('#dynamic-table-loader').fadeOut(500, function() {
            $(this).remove();
        });
    }

    function ListadoDeProveedores() {
        if ($.fn.dataTable.isDataTable('#tablaProveedor')) {
            tablaProveedor = $('#tablaProveedor').DataTable();
            tablaProveedor.destroy();
        }
        tablaProveedor = $("#tablaProveedor").DataTable({
            columnDefs: [
                { width: "20px", targets: 0 },   // ID
                { width: "200px", targets: 1 },  // Nombre
                { width: "50px", targets: 4 },  // RUT
                { width: "200px", targets: 8 },  // Giro
            ],
            "ajax": {
                "url": base_url + "index.php/Proveedor/ListadoDeProveedores",
                "data": {
            },
            beforeSend: function () {
                showTableLoader("Cargando Proveedores...");
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
                mData: "id_proveedor",
                className: "text-center"
            },
            {
                mData: "rut",
                className: "text-left",
                visible: false
            },
            {
                mData: "dv",
                className: "text-center",
                visible: false
            },
            {
                mData: "rut_completo",
                className: "text-left"
            },
            {
                mData: "nombre_proveedor",
                className: "text-left"
            },
            {
                mData: "direccion",
                className: "text-center",
                visible: false
            },
            {
                mData: "telefono",
                className: "text-center",
                visible: false
            },
            {
                mData: "mail",
                className: "text-center",
                visible: false
            },
            {
                mData: "giro",
                className: "text-left"
            },
            {
                mData: "creditomaximo",
                className: "text-left",
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
        tablaProveedor
            .on('select', function (e, dt, type, indexes) {
                var rowData = tablaProveedor.rows(indexes).data().toArray();

                $("#idProveedor").val(rowData[0].id_proveedor);
                $("#RutProveedor").val(rowData[0].rut);
                $("#DvRutProveedor").val(rowData[0].dv);
                $("#nombreProveedor").val(rowData[0].nombre_proveedor);
                $("#DireccionProveedor").val(rowData[0].direccion);
                $("#TelefonoProveedor").val(rowData[0].telefono);
                $("#CorreoProveedor").val(rowData[0].mail);
                $("#GiroProveedor").val(rowData[0].giro);
                $("#CreditoProveedor").val(rowData[0].creditomaximo);

                $('#ModalNuevoProveedor').modal('show');
            })
    }
</script>