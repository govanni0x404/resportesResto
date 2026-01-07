<script>
  var base_url = "<?php echo base_url(); ?>";
  tablaBodegas = $('#tablaBodegas').DataTable();

  $(document).ready(function () {
    ListadoDeBodegas();
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
    
    var $wrapper = $('#tablaBodegas').closest('.dataTables_wrapper');
    if ($wrapper.length === 0) {
        $wrapper = $('#tablaBodegas').parent();
    }
    
    $wrapper.css('position', 'relative');
    $wrapper.append(loaderHtml);
  }

  function hideTableLoader() {
      $('#dynamic-table-loader').fadeOut(500, function() {
          $(this).remove();
      });
  }

  function ListadoDeBodegas() {
    if ($.fn.dataTable.isDataTable('#tablaBodegas')) {
      tablaBodegas = $('#tablaBodegas').DataTable();
      tablaBodegas.destroy();
    }
    tablaBodegas = $("#tablaBodegas").DataTable({
      "ajax": {
        "url": base_url + "index.php/Bodegas/ListadoDeBodegas",
        "data": {
            },
            beforeSend: function () {
                showTableLoader("Cargando Bodegas...");
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
        mData: "id_bodega",
        className: "text-center"
      },
      {
        mData: "nombre_bodega",
        className: "text-left"
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
    tablaBodegas
      .on('select', function (e, dt, type, indexes) {
        var rowData = tablaBodegas.rows(indexes).data().toArray();

        $("#idBodega").val(rowData[0].id_bodega);
        $("#nombreBodega").val(rowData[0].nombre_bodega);
        $('#ModalNuevaBodega').modal('show');
      })
  }
</script>