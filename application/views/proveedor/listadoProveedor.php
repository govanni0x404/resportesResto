<script>
    var base_url = "<?php echo base_url(); ?>";
    tablaProveedor = $('#tablaProveedor').DataTable();

    $(document).ready(function () {
        ListadoDeProveedores();
    });

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
                "url": base_url + "index.php/Proveedor/ListadoDeProveedores"
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
                mData: "id_proveedor",
                className: "text-center"
            },
            {
                mData: "nombre_proveedor",
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
                className: "text-center"
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
                    columns: [0, 1]
                },
                extend: 'pdfHtml5',
                orientation: 'landscape',
                pageSize: 'LEGAL',
                title: 'Listado De Provincias',
                className: "btn btn-danger"
            },
            {
                text: 'EXCEL',
                extend: 'excel',
                exportOptions: {
                    modifier: {
                        selected: null
                    },
                    columns: [0, 1]
                },
                title: 'Listado De Provincias',
                className: "btn btn-success"
            }
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

                $('#ModalNuevoProveedor').modal('show');
            })
    }
</script>