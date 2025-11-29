<script>
  var base_url = "<?php echo base_url(); ?>";
  tablaComuna = $('#tablaComuna').DataTable();

  $(document).ready(function () {
    ListadoDeComunas();
  });

  function ListadoDeComunas() {
    if ($.fn.dataTable.isDataTable('#tablaComuna')) {
      tablaComuna = $('#tablaComuna').DataTable();
      tablaComuna.destroy();
    }
    tablaComuna = $("#tablaComuna").DataTable({
      "ajax": {
        "url": base_url + "index.php/Comuna/ListadoDeComunas"
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
        mData: "id_comuna",
        className: "text-center"
      },
      {
        mData: "nombre_comuna",
        className: "text-center"
      },
      {
        mData: "nombre_provincia",
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
      "buttons": [
      ]
    });
    tablaComuna
      .on('select', function (e, dt, type, indexes) {
        var rowData = tablaComuna.rows(indexes).data().toArray();

        $("#idComuna").val(rowData[0].id_comuna);
        $("#nombreComuna").val(rowData[0].nombre_comuna);
        ListadoProvincias(rowData[0].nombre_provincia);
        $('#ModalNuevaComuna').modal('show');
      })
  }
</script>