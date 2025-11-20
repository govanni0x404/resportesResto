<script>
  var base_url = "<?php echo base_url(); ?>";
  tablaBodegas = $('#tablaBodegas').DataTable();

  $(document).ready(function () {
    ListadoDeBodegas();
  });

  function ListadoDeBodegas() {
    if ($.fn.dataTable.isDataTable('#tablaBodegas')) {
      tablaBodegas = $('#tablaBodegas').DataTable();
      tablaBodegas.destroy();
    }
    tablaBodegas = $("#tablaBodegas").DataTable({
      "ajax": {
        "url": base_url + "index.php/Bodegas/ListadoDeBodegas"
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
        mData: "id_bodega",
        className: "text-center"
      },
      {
        mData: "nombre_bodega",
        className: "text-center"
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
          columns: [0, 1]
        },
        extend: 'pdfHtml5',
        orientation: 'landscape',
        pageSize: 'LEGAL',
        title: 'Listado De Bodegas',
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
        title: 'Listado De Bodegas',
        className: "btn btn-success"
      }
      ]
    });
    tablaBodegas
      .on('select', function (e, dt, type, indexes) {
        var rowData = tablaBodegas.rows(indexes).data().toArray();

        $("#idBodega").val(rowData[0].id_bodega);
        $("#nombreBodega").val(rowData[0].nombre_bodega);
        $('#ModalNuevaBodega').modal('show');
      })
  }
</script>