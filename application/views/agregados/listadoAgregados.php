<script>
  var base_url = "<?php echo base_url(); ?>";
  tablaAgregados = $('#tablaAgregados').DataTable();

  $(document).ready(function () {
    ListadoDeAgregados();
  });

  function ListadoDeAgregados() {
    if ($.fn.dataTable.isDataTable('#tablaAgregados')) {
      tablaAgregados = $('#tablaAgregados').DataTable();
      tablaAgregados.destroy();
    }
    tablaAgregados = $("#tablaAgregados").DataTable({
      "ajax": {
        "url": base_url + "index.php/Agregados/ListadoDeAgregados"
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
        mData: "id_agregado",
        className: "text-center"
      },
      {
        mData: "producto_maestro",
        className: "text-center"
      },
      {
        mData: "nombre_producto",
        className: "text-center"
      },
      {
        mData: "cantidad",
        className: "text-center"
      },
      {
        mData: "valor",
        className: "text-center",
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