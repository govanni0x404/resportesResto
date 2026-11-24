<script>
// v1.24.12: secciones de la ficha del producto (Datos / Parámetros / Imagen). Siempre abre en Datos.
function mostrarSeccionProducto(nombre) {
    $('#modalNuevoProducto .prod-sec-btn').each(function () {   // v1.32.1: solo la ficha (Promos Sushi usa el mismo estilo)
        var on = $(this).data('seccion') === nombre;
        $(this).toggleClass('activo', on).attr('aria-selected', on ? 'true' : 'false');
    });
    $('#modalNuevoProducto .prod-seccion').each(function () { $(this).toggleClass('activa', $(this).data('seccion') === nombre); });
    $('#modalNuevoProducto .modal-body').scrollTop(0);
}
$(document).on('click', '#modalNuevoProducto .prod-sec-btn', function () { mostrarSeccionProducto($(this).data('seccion')); });
$(document).on('show.bs.modal', '#modalNuevoProducto', function (e) { if (e.target === this) mostrarSeccionProducto('datos'); });

var base_url = "<?php echo base_url(); ?>";
tablaCartaProductos = $('#tableCartaProductos').DataTable();
var datosProductoPendiente = null; 
var preciosExistentes = []; 
var debeAbrirAgregados = false;

$(document).ready(function() {
    ListadoCartaDeProductos();
});

// Favoritos de la Caja: la estrella del listado marca o desmarca el producto sin abrir su ficha
// (la celda de la estrella no selecciona la fila, ver "select" en ListadoCartaDeProductos)
$('#tableCartaProductos').on('click.favorito', '.btn-favorito', function() {
    var $b = $(this);
    var fila = tablaCartaProductos.row($b.closest('tr'));
    var datos = fila.data();
    if (!datos) return;
    var nuevo = datos.favorito !== 'SI';
    $b.prop('disabled', true);
    $.post(base_url + "index.php/Productos/marcarFavorito", {
        id_producto: datos.id_producto,
        favorito: nuevo ? 1 : 0
    }, null, 'json').done(function(r) {
        if (!r || !r.ok) {
            swal('No se pudo guardar', (r && r.error) || 'Intenta nuevamente', 'error');
            return;
        }
        datos.favorito = nuevo ? 'SI' : 'NO';
        fila.data(datos).draw(false);
    }).fail(function() {
        swal('No se pudo guardar', 'Revisa la conexión e intenta nuevamente', 'error');
    }).always(function() {
        $b.prop('disabled', false);
    });
});

$('#btnFiltrar').on('click', function() {
    var id_familia = $("#cboFamilias").val();
    console.log(id_familia);
    if ($('#ckTodasLasFamilias').prop('checked') == false) {
        if (id_familia == null || id_familia == 0) {
            showAppAlert('warning', "Debe seleccionar la familia");
            return;
        }
    } else {
        id_familia = 0;
    }
    ListadoCartaDeProductos(id_familia);
});

$('#ckTodasLasFamilias').click(function() {
    if ($('#ckTodasLasFamilias').prop('checked') == false) {
        $("#cboFamilias").attr("disabled", false);
    } else {
        $("#cboFamilias").attr("disabled", true);
    }
});

function ObtenerPreciosDeVenta(idProducto) {
    // v1.23.3: los precios de un producto abierto antes podían llegar tarde y quedar como los de este; al grabar se
    // guardaban en este producto. Ahora solo se usan si son del producto que está abierto y del último pedido.
    var turno = turnoLista('precios');
    preciosExistentes = [];
    $.post(base_url + "index.php/Productos/obtenerPreciosYComisiones", {
        id_producto: idProducto
    }, function(datos) {
        if (!turnoVigente('precios', turno) || String($('#txtID').val()) !== String(idProducto)) return;
        var obj = $.parseJSON(datos);
        preciosExistentes = obj; // Guardar precios existentes
        console.log("Precios cargados:", preciosExistentes);
    });
}

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
    
    var $wrapper = $('#tableCartaProductos').closest('.dataTables_wrapper');
    if ($wrapper.length === 0) {
        $wrapper = $('#tableCartaProductos').parent();
    }
    
    $wrapper.css('position', 'relative');
    $wrapper.append(loaderHtml);
}

function hideTableLoader() {
    $('#dynamic-table-loader').fadeOut(500, function() {
        $(this).remove();
    });
}

function ListadoCartaDeProductos() {
    let tituloExport = "CARTA DE PRODUCTOS";
    if ($.fn.dataTable.isDataTable('#tableCartaProductos')) {
        tablaCartaProductos = $('#tableCartaProductos').DataTable();
        tablaCartaProductos.destroy();
    }
    tablaCartaProductos = $("#tableCartaProductos").DataTable({
        "ajax": {
            "url": base_url + "index.php/Productos/ListadoCartaDeProductos",
            "type": "POST",
            "data": {
            },
            beforeSend: function () {
                showTableLoader("Cargando productos…");
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
        "pageLength": 15,
        // La celda de la estrella (favorito de la Caja) no abre la ficha del producto
        "select": { style: 'os', selector: 'td:not(.col-favorito)' },
        //"dom": "Bfrtip",
        "ordering": true,
        "filter": true,
        "info": false,
        "footer": false,
        "lengthChange": false,
        "responsive": {
            details: {
                type: 'column',
                target: -1
            }
        },
        "columns": [
            {mData: "id_producto", className: "text-center", visible: false},
            {mData: "favorito", className: "text-center col-favorito", responsivePriority: 1, render: function(d, type) {
                if (type !== 'display') return d === 'SI' ? 1 : 0;
                var on = d === 'SI';
                return '<button type="button" class="btn-favorito' + (on ? ' activo' : '') + '" title="' +
                    (on ? 'Quitar de favoritos de la Caja' : 'Marcar como favorito de la Caja') + '" aria-pressed="' + on + '">' +
                    '<i class="fa ' + (on ? 'fa-star' : 'fa-star-o') + '"></i></button>';
            }},
            {mData: "cbarra", className: "text-center"},
            {mData: "nombre_producto", className: "text-left"},
            {mData: "nombre_familia", className: "text-left"},
            {mData: "nombre_umedida", className: "text-left"},
            {mData: "iva", className: "text-center", render: $.fn.dataTable.render.number(',', '.', 0, '$'), visible: false},
            {mData: "impto_adicional", className: "text-center", visible: false},
            {mData: "nombre_dimpresion", className: "text-center", visible: false},
            {mData: "nombre_bodega", className: "text-left"},
            {mData: "pponderado", className: "text-center", visible: false, render: $.fn.dataTable.render.number(',', '.', 0, '$')},
            {mData: "menu", className: "text-center", visible: false},
            {mData: "subfamilia", className: "text-center", visible: false},
            {mData: "receta", className: "text-center", visible: false},
            {mData: "vigente", className: "text-center", visible: false},
            {mData: "con_agregado", className: "text-center", visible: false},
            {mData: "venta_publico", className: "text-center", visible: false},
            {mData: "permite_porcion", className: "text-center", visible: false},
            {mData: "venta_segun_stock", className: "text-center", visible: false},
            {mData: "elaboracion_propia", className: "text-center", visible: false},
            {mData: "permitir_venta_sin_precio", className: "text-center", visible: false},
            {mData: "venta_uno_a_uno", className: "text-center", visible: false},
            {mData: "pedir_cantidad_en_venta", className: "text-center", visible: false},
            {mData: "ver_en_dispositivos_moviles", className: "text-center", visible: false},
            {mData: "permite_cortesia", className: "text-center", visible: false}
        ],
        // Sin la columna producto.favorito en la base (migración pendiente) no se muestran las estrellas
        "initComplete": function(settings, json) {
            var hayFavoritos = !!(json && json.favoritos);
            this.api().column(1).visible(hayFavoritos);
            $('#grupoFavorito').toggle(hayFavoritos);
        },
        'language': {
            select: {
                rows: {
                    _: " (Seleccionadas %d filas)",
                    0: " (Presione una linea a seleccionar)",
                    1: " (1 Fila seleccionada)"
                }
            }
        },
        'buttons': []
    });
    
    tablaCartaProductos.on('select', function(e, dt, type, indexes) {
        var rowData = tablaCartaProductos.rows(indexes).data().toArray();

        $("#txtID").val(rowData[0].id_producto);
        $("#txtCbarra").val(rowData[0].cbarra);
        $("#txtNombre").val(rowData[0].nombre_producto);
        
        // 19-11-2026 (auditoría): cada lista se elige por su número (con nombres repetidos se elegía otro)
        ListadoDeFamiliasMantenedor(rowData[0].nombre_familia, rowData[0].id_familia);
        ListadoDeSubFamilias(rowData[0].subfamilia, rowData[0].id_subfamilia, rowData[0].id_familia);
        ListadoDeUmedida(rowData[0].nombre_umedida, rowData[0].id_umedida);
        window.destino2Producto = rowData[0].id_dimpresion2_web || 0;   // v1.30.0
        ListadoDestinoDeImpresion(rowData[0].nombre_dimpresion, rowData[0].id_dimpresion);
        ListadoDeBodega(rowData[0].nombre_bodega, rowData[0].id_bodega);
        ListadoDeImpuestosIva(rowData[0].iva, rowData[0].impto_iva);
        ListadoDeMenu(rowData[0].id_menu || rowData[0].menu);
        ListadoDeReceta(rowData[0].receta, rowData[0].id_receta);
        
        $("#extras").removeClass('hidden');

        $('#ckVigente').prop('checked', rowData[0].vigente == 'SI');
        $('#ckConAgregado').prop('checked', rowData[0].con_agregado == 'SI');
        conAgregadoEnBase = rowData[0].con_agregado == 'SI';   // v1.34.1
        $('#ckVentaPublico').prop('checked', rowData[0].venta_publico == 'SI');
        $('#ckPermitirFraccion').prop('checked', rowData[0].permite_porcion == 'SI');
        $('#ckVentaPorStock').prop('checked', rowData[0].venta_segun_stock == 'SI');
        $('#ckElaboracionLocal').prop('checked', rowData[0].elaboracion_propia == 'SI');
        $('#ckPermitirVentaSiempre').prop('checked', rowData[0].permitir_venta_sin_precio == 'SI');
        $('#ckVentaDirecta').prop('checked', rowData[0].venta_uno_a_uno == 'SI');
        $('#ckPedirCantidadEnVenta').prop('checked', rowData[0].pedir_cantidad_en_venta == 'SI');
        $('#ckVerDispositivomovil').prop('checked', rowData[0].ver_en_dispositivos_moviles == 'SI');
        $('#ckPermiteCortesia').prop('checked', rowData[0].permite_cortesia == 'SI');
        $('#ckFavorito').prop('checked', rowData[0].favorito == 'SI');
        
        if (rowData[0].con_agregado == 'SI') {
            $("#btnAgregados").attr("disabled", false);
        } else {
            $("#btnAgregados").attr("disabled", true);
        }
        
        ObtenerPreciosDeVenta(rowData[0].id_producto);
        
        $('#modalNuevoProducto').modal('show');
    });
}

function crearNuevoProducto() {
    $('#txtID').val("");
    $('#txtCbarra').val("");
    $('#txtNombre').val("");

    cargarSiguienteCodigoBarra();
    
    // Limpiar precios existentes
    preciosExistentes = [];
    
    //ObtenerUltimoIdProducto("");
    ListadoDeFamiliasMantenedor("GENERAL");
    ListadoDeSubFamilias("");
    ListadoDeUmedida("UNIDAD");
    window.destino2Producto = 0;   // v1.30.0: producto nuevo, sin segundo destino
    ListadoDestinoDeImpresion("CAJA");
    ListadoDeBodega("BODEGA PRINCIPAL");
    ListadoDeImpuestosIva("IVA 19%");
    ListadoDeMenu("");
    ListadoDeReceta("");
    
    $('#ckVigente').prop('checked', true);
    $('#ckVentaPublico').prop('checked', true);
    $('#ckPermitirVentaSiempre').prop('checked', false);
    $('#ckVentaDirecta').prop('checked', false);
    $('#ckConAgregado').prop('checked', false);
    conAgregadoEnBase = false;   // v1.34.1
    $('#ckPermitirFraccion').prop('checked', false);
    $('#ckVentaPorStock').prop('checked', false);
    $('#ckElaboracionLocal').prop('checked', false);
    $('#ckPedirCantidadEnVenta').prop('checked', false);
    $('#ckVerDispositivomovil').prop('checked', true);
    $('#ckPermiteCortesia').prop('checked', false);
    $('#ckFavorito').prop('checked', false);
    
    $("#extras").addClass('hidden');
    $("#btnAgregados").attr("disabled", true);
    
    // Limpiar tabla de precios
    $("#detallePrecioVenta").html('');
    
    $('#modalNuevoProducto').modal({
        backdrop: 'static',
        keyboard: false
    });
}

// v1.34.1: delegado. Este archivo se carga antes que la ventana (productos/modals.php), así que el enlace directo no
// encontraba la casilla y marcar "Con Agregado" nunca habilitaba el botón Agregados del producto.
$(document).on('change', '#ckConAgregado', function() {
    var idProducto = $('#txtID').val();
    
    if ($('#ckConAgregado').prop('checked') && idProducto && idProducto != "") {
        $("#btnAgregados").attr("disabled", false);
    } else {
        $("#btnAgregados").attr("disabled", true);
    }
});

// 18-11-2026: el código automático sugerido; si se guarda sin cambiarlo, el servidor puede darle el siguiente libre
var cbarraSugerido = null;
function cargarSiguienteCodigoBarra() {
    cbarraSugerido = null;
    $.ajax({
        url: base_url + "index.php/Productos/obtenerSiguienteCodigoBarra",
        type: "POST",
        dataType: "json",
        success: function(resp) {
            console.log("Respuesta siguiente código:", resp);
            if (resp && resp.codigo) {
                cbarraSugerido = String(resp.codigo);
                $('#txtCbarra').val(resp.codigo);
            } else {
                // Fallback manual si el controlador falla
                console.warn("Respuesta inválida del servidor para código de barra");
            }
        },
        error: function(xhr, status, error) {
            console.error("Error al obtener código de barra:", xhr.responseText);
            // Intentar usar la función alternativa si la principal falla
            $.post(base_url + "index.php/Productos/ObtenerUltimoIdProducto", {}, function(datos) {
                try {
                    var obj = JSON.parse(datos);
                    if (obj && obj[0] && obj[0].new_id) {
                        cbarraSugerido = String(obj[0].new_id);
                        $('#txtCbarra').val(obj[0].new_id);
                    }
                } catch(e) {
                    showAppAlert('error', "No se pudo obtener el código de barras automático", {
                        duration: 5000,
                        image: base_url + 'assets/img/errorGif.gif'
                    });
                }
            });
        }
    });
}


function ObtenerUltimoIdProducto(objSelected) {
    $.post(base_url + "index.php/Productos/ObtenerUltimoIdProducto", {
        id: ""
    }, function(datos) {
        var obj = jQuery.parseJSON(datos);
        tbl = "";
        $.each(obj, function(index, v) {
            tbl = v.new_id;
        });
        $("#txtCbarra").val(tbl);
    });
}

    // v1.24.1: Configuraciones > "Comisión en el precio de los productos". Apagada: sin recuadro de comisión (se graba 0)
    var comisionHabilitada = true;
    function pintarColumnaComision() {
        $('#tablePrecios thead th:last').toggle(comisionHabilitada);
        $('#modalPrecioVentaProducto .modal-title').text(comisionHabilitada ? 'PRECIO DE VENTA Y COMISIÓN' : 'PRECIO DE VENTA');
        $('#btnGrabarPrecios').html('<i class="fa fa-check"></i> ' + (comisionHabilitada ? 'Grabar Precios y Comisiones' : 'Grabar Precios'));
    }
    $(function () {
        $.post(base_url + "index.php/Productos/verificarColumnaComision", {}, function (datos) {
            try { var r = typeof datos === 'string' ? JSON.parse(datos) : datos; comisionHabilitada = !!(r.existe && r.habilitada !== false); } catch (e) {}
            pintarColumnaComision();
        });
    });

    function CargarListasDePreciosVacias() {
        $.post(base_url + "index.php/Productos/obtenerListasDePrecios", {
            id: ""
        }, function(datos) {
            var obj = $.parseJSON(datos);
            var tbl = '';
            
            // v1.24.1: según Configuraciones (antes siempre se mostraba)
            var tieneComision = comisionHabilitada;
            pintarColumnaComision();
            
            // Verificar si tenemos precios existentes cargados
            var hayPreciosExistentes = preciosExistentes && preciosExistentes.length > 0;
            
            console.log("Cargando listas de precios. Precios existentes:", preciosExistentes);
            
            $.each(obj, function(index, v) {
                var precioExistente = null;
                if (hayPreciosExistentes) {
                    precioExistente = preciosExistentes.find(function(p) {
                        return p.id_lprecio == v.id_detalle_lprecio;
                    });
                }
                
                var precio = precioExistente ? precioExistente.total : 0;
                var comision = (precioExistente) ? 
                            (precioExistente.monto_comision || 0) : 0;
                
                tbl += "<tr>";
                tbl += "<td style='display:none'>" + v.id_detalle_lprecio + "</td>";
                tbl += "<td style='width:40%'>" + escaparHtml(v.nombre_lprecio) + "</td>";
                
                // Input de precio
                tbl += '<td style="width:30%" class="text-right">';
                tbl += '<input class="text-right form-control precio-input" type="number" min="0" max="1000000" value="' + precio + '">';
                tbl += '</td>';
                
                // Input de comisión (solo con la opción encendida; sin él se graba 0)
                if (tieneComision) {
                    tbl += '<td style="width:30%" class="text-right">';
                    tbl += '<input class="text-right form-control comision-input" type="number" min="0" max="100" step="0.01" value="' + comision + '" placeholder="0.00">';
                    tbl += '</td>';
                }
                
                tbl += "</tr>";
            });
            
            $("#detallePrecioVenta").html(tbl);
            maxPrecioVenta();
            maxComision();
        });
    }

function GrabarProducto() {
    var msg = "";
    
    if ($('#txtNombre').val() == "" || $('#txtNombre').val() == 0) {
        msg += "- Nombre Del Producto\n";
    }

    if ($('#txtCbarra').val().trim() === "") {
        msg += "- Código de barras no puede estar en blanco\n";
    }

    if ($('#cboFamilia').val() == null || $('#cboFamilia').val() == 0) {
        msg += "- Familia Del Producto\n";
    }
    if ($('#cboDestinoDeImpresion').val() == 0 || $('#cboDestinoDeImpresion').val() == null) {
        msg += "- Destino De Impresion Del Producto\n";
    }
    if ($("#cboBodega").val() == 0 || $("#cboBodega").val() == null) {
        msg += "- Bodega\n";
    }
    
    if (msg.length) {
        mostrarSeccionProducto('datos');   // v1.24.12: lo que falta está en Datos del producto
        showAppAlert('error',"- Falta Ingresar Los Siguiente(s) dato(s):\n" + msg, {
            duration: 5000,
            image: base_url+ 'assets/img/errorGif.gif'
        });
        return;
    }

    if ($('#txtCbarra').val().trim() === "" || $('#txtCbarra').val() == "0") {
        mostrarSeccionProducto('datos');
        showAppAlert('error', "Debe ingresar o generar un código de barras válido", {
            duration: 5000,
            image: base_url + 'assets/img/errorGif.gif'
        });
        return;
    }

    debeAbrirAgregados = $('#ckConAgregado').is(":checked");
    console.log("¿Debe abrir agregados?:", debeAbrirAgregados);

    // Preparar datos del producto
    var datosProducto = {
        'id_producto': $('#txtID').val() || 0, // Asegurar que sea 0 si está vacío
        'nombre_producto': $('#txtNombre').val().toUpperCase().replace(/[,.'"$#%&]+/g, ''),
        'cbarra': $('#txtCbarra').val(),
        'cbarra_auto': cbarraSugerido !== null && $.trim($('#txtCbarra').val()) === cbarraSugerido ? 1 : 0,
        'impto_iva': $('#cboImpuestoIva').val(),
        // v1.34.1 (pedido del usuario): el impuesto adicional (ILA) ya no va en la Carta. Al editar se conserva el que tenga
        // el producto; uno nuevo queda "sin ILA" (Productos::grabarNuevoProducto)
        'impto_ila': 0,
        'id_familia': $('#cboFamilia').val(),
        'id_umedida': $('#cboUmedida').val(),
        'precio_bruto': 0,
        'espack': false,
        'vigente': $('#ckVigente').is(":checked") ? 1 : 0,
        'inventariable': false,
        'valor_ila': 0,
        'precio_ucompra': 0,
        'pponderado': 1,
        'stock_minimo': 0,
        'cbarra_balanza': 0,
        'con_agregado': $('#ckConAgregado').is(":checked") ? 1 : 0,
        'venta_publico': $('#ckVentaPublico').is(":checked") ? 1 : 0,
        'descripcion_corta': "",
        'permite_porcion': $('#ckPermitirFraccion').is(":checked") ? 1 : 0,
        'id_subfamilia': $('#cboSubFamilia').val() || 0,
        'id_dimpresion': $('#cboDestinoDeImpresion').val(),
        'id_dimpresion2_web': $('#ckDestino2').is(':checked') ? ($('#cboDestino2').val() || 0) : 0,   // v1.30.0
        'id_receta': $('#cboReceta').val() || 0,
        'orden': 0,
        'permite_cambio': false,
        'valor1': 0,
        'valor2': 0,
        'id_bodega': $('#cboBodega').val(),
        'es_menu': (Number($('#cboMenu').val()) || 0) > 0,   // v1.24.0: con un menú elegido, el producto es un menú
        'id_menu': $('#cboMenu').val() || 0,
        'venta_segun_stock': $('#ckVentaPorStock').is(":checked") ? 1 : 0,
        'elaboracion_propia': $('#ckElaboracionLocal').is(":checked") ? 1 : 0,
        'permitir_venta_sin_precio': $('#ckPermitirVentaSiempre').is(":checked") ? 1 : 0,
        'venta_uno_a_uno': $('#ckVentaDirecta').is(":checked") ? 1 : 0,
        'pedir_cantidad_en_venta': $('#ckPedirCantidadEnVenta').is(":checked") ? 1 : 0,
        'detalle_preparacion': "",
        'tiempo_preparacion': 0,
        'mostrar_detalle_insumos_receta': false,
        'ver_en_dispositivos_moviles': $('#ckVerDispositivomovil').is(":checked") ? 1 : 0,
        'permite_cortesia': $('#ckPermiteCortesia').is(":checked") ? 1 : 0,
        'favorito': $('#ckFavorito').is(":checked") ? 1 : 0,
    };
    
    var idProducto = $('#txtID').val();
    
    if (idProducto && idProducto != "" && idProducto != "0") {
        // ES EDICIÓN - Grabar directamente SIN abrir modal
        grabarProductoExistente(datosProducto);
    } else {
        // ES CREACIÓN - Abrir modal de precios
        datosProductoPendiente = datosProducto;
        preciosExistentes = []; // Limpiar precios existentes
        CargarListasDePreciosVacias();
        $('#modalPrecioVentaProducto').modal({
            backdrop: 'static',
            keyboard: false
        });
    }
}

function grabarProductoExistente(datosProducto) {
    // Recolectar precios actuales (si existen en el array preciosExistentes)
    var precios = [];
    
    if (preciosExistentes && preciosExistentes.length > 0) {
        preciosExistentes.forEach(function(precio) {
            precios.push({
                id_lprecio: precio.id_lprecio,
                total: precio.total,
                monto_comision: precio.monto_comision
            });
        });
    }
    
    $.ajax({
        url: base_url + "index.php/Productos/grabarProductoConPrecios",
        type: "POST",
        data: { 
            producto: datosProducto,
            precios: precios // Enviar precios existentes para mantenerlos
        },
        dataType: "json",
        success: function(response) {
            console.log("✓ Producto actualizado:", response);
            
            if (response.error) {
                showAppAlert('error', response.error, {
                    duration: 5000,
                    image: base_url+ 'assets/img/errorGif.gif'
                });
                return;
            }
            
            // Si es venta directa, crear/actualizar insumo.
            // 19-11-2026 (auditoría): con los datos de ESTE producto (antes leía datosProductoPendiente, que al editar es
            // null: error de JavaScript y el modal no se cerraba aunque el producto sí se grababa)
            if (datosProducto.venta_uno_a_uno == 1) {
                try { GrabarNuevoInsumo(datosProducto); } catch (e) { console.warn('Insumo del producto:', e); }
            }
            
            // Cerrar modal
            $('#modalNuevoProducto').modal('hide');
            
            // Limpiar
            preciosExistentes = [];
            
            // Mostrar éxito
            showAppAlert('success', "Producto actualizado correctamente!", {
                duration: 4000,
                image: base_url + 'assets/img/success.gif'
            });
            
            // Recargar tabla
            if (typeof tablaCartaProductos !== 'undefined') {
                tablaCartaProductos.ajax.reload();
            }
        },
        error: function(xhr, status, error) {
            console.error("✗ Error:", xhr.responseText);
            showAppAlert('error', "No se pudo actualizar: " + error, {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
        }
    });
}

function grabarPreciosYProducto() {
    console.log("=== INICIANDO GRABACIÓN ===");
    
    if (!datosProductoPendiente) {
        showAppAlert('error', "No hay datos del producto", {
            duration: 5000,
            image: base_url+ 'assets/img/errorGif.gif'
        });
        return;
    }
    
    var precios = [];
    var tienePrecio = false;
    
    // Recolectar precios (se permiten todos los valores, incluyendo 0)
    $("#detallePrecioVenta tr").each(function() {
        var idLista = $(this).find("td").eq(0).text().trim();
        var precio = parseInt($(this).find(".precio-input").val()) || 0;
        
        var $comisionInput = $(this).find(".comision-input");
        var comision = 0;
        
        if ($comisionInput.length > 0) {
            comision = parseFloat($comisionInput.val()) || 0;
        }
        
        // Incluir todos los precios definidos por el usuario
        var precioData = {
            id_lprecio: idLista,
            total: precio
        };
        
        if ($comisionInput.length > 0) {
            precioData.monto_comision = comision;
        }
        
        precios.push(precioData);
        // 19-11-2026 (auditoría): la lista pública en 0 deja el producto fuera de la Caja (no se vende sin precio)
        if (/P[UÚ]BLIC/i.test($(this).find("td").eq(1).text()) && precio <= 0) tienePrecio = 'publica0';
    });
    if (tienePrecio === 'publica0' && datosProductoPendiente.permitir_venta_sin_precio != 1
        && !window.confirm('El precio de la LISTA PÚBLICA quedó en $0: el producto NO aparecerá en la Caja hasta que tenga precio '
            + '(o hasta marcar "Permitir venta sin precio").\n\n¿Grabar igual?')) {
        return;
    }
    
    $.ajax({
        url: base_url + "index.php/Productos/grabarProductoConPrecios",
        type: "POST",
        data: { 
            producto: datosProductoPendiente,
            precios: precios
        },
        dataType: "json",
        success: function(response) {
            console.log("✓ Respuesta:", response);
            
            if (response.error) {
                showAppAlert('error', response.error, {
                    duration: 5000,
                    image: base_url+ 'assets/img/errorGif.gif'
                }); 
                return;
            }
            
            // 18-11-2026: el código con que quedó (si el sugerido se ocupó, el servidor usó el siguiente libre)
            if (response.cbarra) datosProductoPendiente.cbarra = response.cbarra;
            // Crear insumo si es venta directa
            if (datosProductoPendiente.venta_uno_a_uno == 1) {
                GrabarNuevoInsumo();
            }
            
            $('#modalPrecioVentaProducto').modal('hide');
            
            if (debeAbrirAgregados && response.id_producto) {
                console.log("✓ Abriendo modal de agregados para producto:", response.id_producto);
                
                // Establecer el ID del producto recién creado
                $('#txtID').val(response.id_producto);
                
                // Pequeño delay para que se cierre bien el modal anterior
                setTimeout(function() {
                    AbrirModalAgregadosDelProducto();
                }, 500);
                
            } else {
                // Si NO tiene agregados, cerrar todo y limpiar
                $('#modalNuevoProducto').modal('hide');
                datosProductoPendiente = null;
                preciosExistentes = [];
                debeAbrirAgregados = false;
                
                showAppAlert('success', "Producto creado correctamente!", {
                    duration: 4000,
                    image: base_url + 'assets/img/success.gif'
                });
                
                if (typeof tablaCartaProductos !== 'undefined') {
                    tablaCartaProductos.ajax.reload();
                }
            }
        },
        error: function(xhr, status, error) {
            console.error("✗ Error:", xhr.responseText);
            showAppAlert('error', "No se pudo guardar: " + error, {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            }); 
        }
    });
}

function maxComision() {
    // Usar delegación de eventos en el contenedor padre que siempre existe
    $(document).off('input', '.comision-input').on('input', '.comision-input', function() {
        var value = $(this).val();
        var n = parseFloat(value) || 0;
        
        // Limitar a 2 decimales
        n = Math.round(n * 100) / 100;
        
        if (n > 100) n = 100;
        if (n < 0) n = 0;
        
        $(this).val(n);
    });
}

$(document).ready(function() {
    maxPrecioVenta();
    maxComision();
    console.log("✓ Sistema de precios inicializado");
});

function GrabarNuevoInsumo(datos) {
    datos = datos || datosProductoPendiente;
    if (!datos) return;
    var miJson = {
        'id_insumo': "",
        'cbarra': datos.cbarra,
        'nombre_insumo': datos.nombre_producto,
        'id_grupo_insumo': 1,
        'id_impto_iva': datos.impto_iva,
        'id_impto_adicional': 1,
        'umedida_inventario': 1,
        'id_umedida_inventario': 1,
        'umedida_receta': 1,
        'id_umedida_receta': 1,
        'costo_neto_ppp': 0,
        'costo_bruto_ppp': 0,
        'precio_ucompra': 0,
        'observaciones': "",
        'inv_cierre': false
    };
    
    $.post(base_url + "index.php/Insumos/grabarInsumo", {
        'prod': miJson
    }).done(function() {
        console.log("Insumo creado correctamente");
    }).fail(function() {
        console.log("Error al crear el insumo");
    });
}

function sololetrasyNumeros(e, espace) {
    key = e.keyCode || e.which;
    teclado = String.fromCharCode(key).toLowerCase();
    letras = "qwertyuiopasdfghjklñzxcvbnm1234567890 ";
    especiales = "8-37-38-46-164";
    teclado_especial = false;

    for (var i in especiales) {
        if (key == especiales[i]) {
            teclado_especial = true;
            break;
        }
    }

    if (espace == true) {
        if (e.keyCode == 32) {
            return false;
        }
    }

    if (letras.indexOf(teclado) == -1 && !teclado_especial) {
        return false;
    }
}

function maxPrecioVenta() {
    // Usar delegación de eventos en el contenedor padre que siempre existe
    $(document).off('input', '.precio-input').on('input', '.precio-input', function() {
        var value = $(this).val();
        if (value.length > 7) {
            value = value.slice(0, 7);
        }
        var n = parseInt(value) || 0;
        if (n > 1000000) n = 1000000;
        if (n < 0) n = 0;
        $(this).val(n);
    });
}

function cancelarCreacionProducto() {
    swal({
        title: "¿Está seguro?",
        text: "Si cancela, perderá toda la información ingresada del producto",
        type: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, cancelar",
        cancelButtonText: "No, continuar editando",
        closeOnConfirm: true,
        closeOnCancel: true
    }, function(isConfirm) {

        if (isConfirm) {
            datosProductoPendiente = null;
            $('#modalPrecioVentaProducto').modal('hide');
        }
    });
}

function finalizarCreacionProductoConAgregados() {
    console.log("✓ Finalizando creación de producto con agregados");
    
    // Cerrar modal de agregados
    $('#ModalAgregadosDelProducto').modal('hide');
    
    // Cerrar modal principal de producto
    $('#modalNuevoProducto').modal('hide');
    
    // Limpiar variables
    datosProductoPendiente = null;
    preciosExistentes = [];
    debeAbrirAgregados = false;
    
    // Mostrar mensaje de éxito
    showAppAlert('success', "¡Producto creado completamente con agregados!", {
        duration: 4000,
        image: base_url + 'assets/img/success.gif'
    });
    
    // Recargar tabla
    if (typeof tablaCartaProductos !== 'undefined') {
        tablaCartaProductos.ajax.reload();
    }
}
</script>