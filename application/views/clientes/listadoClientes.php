<script>
    var base_url = "<?php echo base_url(); ?>";
    tablaClientes = $('#tablaClientes').DataTable();
    $(document).ready(function () {
        ListadoDeClientes();
    });

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
                className: "text-center",
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