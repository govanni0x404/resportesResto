<script>
  var base_url = "<?php echo base_url(); ?>";
  tablaCanalVenta = $('#tablaCanalVenta').DataTable();

  $(document).ready(function () {
    ListadoDeCanalDeVenta();
  });

  function ListadoDeCanalDeVenta() {
    if ($.fn.dataTable.isDataTable('#tablaCanalVenta')) {
      tablaCanalVenta = $('#tablaCanalVenta').DataTable();
      tablaCanalVenta.destroy();
    }
    tablaCanalVenta = $("#tablaCanalVenta").DataTable({
      "ajax": {
        "url": base_url + "index.php/CanalDeVenta/ListadoCanalDeVenta"
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
        mData: "id_canal_venta",
        className: "text-center"
      },
      {
        mData: "nombre_canal_venta",
        className: "text-center"
      },
      {
        mData: "vigente",
        className: "text-center",
        visible: false
      },
      {
        mData: "cortesia",
        className: "text-center",
        visible: false
      },

      {
        mData: "nombre_lprecio",
        className: "text-center",
        visible: false
      },


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
        text: 'PDF',
        extend: 'pdf',
        exportOptions: {
          modifier: {
            selected: null
          },
          columns: [0, 1, 2]
        },
        extend: 'pdfHtml5',
        orientation: 'landscape',
        pageSize: 'LEGAL',
        title: 'Listado De Canal De Venta',
        className: "btn btn-danger"
      },
      {
        text: 'EXCEL',
        extend: 'excel',
        exportOptions: {
          modifier: {
            selected: null
          },
          columns: [0, 1, 2]
        },
        title: 'Listado De Canal De Venta',
        className: "btn btn-success"
      }
      ]
    });
    tablaCanalVenta
      .on('select', function (e, dt, type, indexes) {
        var rowData = tablaCanalVenta.rows(indexes).data().toArray();

        $("#idCanalDeVenta").val(rowData[0].id_canal_venta);
        $("#nombreCanalDeVenta").val(rowData[0].nombre_canal_venta);
        ListarListasDePrecios(rowData[0].nombre_lprecio);
        if (rowData[0].vigente == 'SI') {
          $('#ckVigente').prop('checked', true);
        } else {
          $('#ckVigente').prop('checked', false);
        }
        if (rowData[0].cortesia == 'SI') {
          $('#ckEsCortesia').prop('checked', true);
        } else {
          $('#ckEsCortesia').prop('checked', false);
        }
        $('#ModalNuevoCanalDeVenta').modal('show');
      })
  }
</script>