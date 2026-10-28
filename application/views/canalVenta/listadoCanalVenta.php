<script>
  var base_url = "<?php echo base_url(); ?>";
  tablaCanalVenta = $('#tablaCanalVenta').DataTable();

  $(document).ready(function () {
    ListadoDeCanalDeVenta();
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
    
    var $wrapper = $('#tablaCanalVenta').closest('.dataTables_wrapper');
    if ($wrapper.length === 0) {
        $wrapper = $('#tablaCanalVenta').parent();
    }
    
    $wrapper.css('position', 'relative');
    $wrapper.append(loaderHtml);
  }

  function hideTableLoader() {
      $('#dynamic-table-loader').fadeOut(500, function() {
          $(this).remove();
      });
  }

  function ListadoDeCanalDeVenta() {
    if ($.fn.dataTable.isDataTable('#tablaCanalVenta')) {
      tablaCanalVenta = $('#tablaCanalVenta').DataTable();
      tablaCanalVenta.destroy();
    }
    tablaCanalVenta = $("#tablaCanalVenta").DataTable({
      "ajax": {
        "url": base_url + "index.php/CanalDeVenta/ListadoCanalDeVenta",
        "data": {
            },
            beforeSend: function () {
                showTableLoader("Cargando Canales de Venta...");
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
        mData: "id_canal_venta",
        className: "text-center"
      },
      {
        mData: "nombre_canal_venta",
        className: "text-left"
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