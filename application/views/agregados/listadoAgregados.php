<script>
  var base_url = "<?php echo base_url(); ?>";
  tablaAgregados = $('#tablaAgregados').DataTable();

  $(document).ready(function () {
    ListadoDeAgregados();
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
    
    var $wrapper = $('#tablaAgregados').closest('.dataTables_wrapper');
    if ($wrapper.length === 0) {
        $wrapper = $('#tablaAgregados').parent();
    }
    
    $wrapper.css('position', 'relative');
    $wrapper.append(loaderHtml);
  }

  function hideTableLoader() {
      $('#dynamic-table-loader').fadeOut(500, function() {
          $(this).remove();
      });
  }

  function ListadoDeAgregados() {
    if ($.fn.dataTable.isDataTable('#tablaAgregados')) {
      tablaAgregados = $('#tablaAgregados').DataTable();
      tablaAgregados.destroy();
    }
    tablaAgregados = $("#tablaAgregados").DataTable({
      "ajax": {
        "url": base_url + "index.php/Agregados/ListadoDeAgregados",
        "data": {
            },
            beforeSend: function () {
                showTableLoader("Cargando Agregados...");
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
        mData: "id_agregado",
        className: "text-center"
      },
      {
        mData: "producto_maestro",
        className: "text-left"
      },
      {
        mData: "nombre_producto",
        className: "text-left"
      },
      {
        mData: "cantidad",
        className: "text-right"
      },
      {
        mData: "valor",
        className: "text-right",
        render: $.fn.dataTable.render.number(',', '.', 0, '$')
      },
      {
        mData: "vigente",
        className: "text-center"
      },
      {
        mData: "usar_destino_impresion",
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
    tablaAgregados
    .on('select', function (e, dt, type, indexes) {
        var rowData = tablaAgregados.rows(indexes).data().toArray();
        var data = rowData[0]; // Datos de la fila seleccionada
        
        $("#idAgregado").val(data.id_agregado);

        // --- SOLUCIÓN: Usar IDs en lugar de NOMBRES ---
        // Carga el producto maestro usando su ID
        ListadoProductoDeVentaConAgregado(data.id_producto_maestro); 
        // Carga el producto agregado usando su ID
        ListadoProductosCboAgregados(data.id_producto); 
        // --- FIN SOLUCIÓN ---

        $("#cantidadAgregado").val(data.cantidad);
        $("#valorAdicionalAgregado").val(data.valor);
        
        // ... Código para checkboxes (vigencia/destino impresión) que ya tenías
        if (data.usar_destino_impresion == 'SI') {
          $('#ckUsarDestinoDeImpresion').prop('checked', true);
        } else {
          $('#ckUsarDestinoDeImpresion').prop('checked', false);
        }
        if (data.vigente == 'SI') {
          $('#ckVigenciaAgregado').prop('checked', true);
        } else {
          $('#ckVigenciaAgregado').prop('checked', false);
        }
        
        $('#ModalNuevoAgregado').modal('show');
    })
  }
</script>