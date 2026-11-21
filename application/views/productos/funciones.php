<script>
// v1.23.3: listas del modal de productos sin respuestas desordenadas. Con el servidor lejos (VPS) la lista pedida al abrir
// un producto podía llegar DESPUÉS de la del siguiente y pisarla (ej. el destino COCINA se veía como CAJA). Cada pedido
// tiene su turno: la respuesta de un turno viejo se ignora. Y si el usuario ya eligió algo antes de que llegara la lista,
// se respeta su elección.
var turnosLista = {};
function turnoLista(sel) {
    turnosLista[sel] = (turnosLista[sel] || 0) + 1;
    $(sel).data('elegidoUsuario', null);
    return turnosLista[sel];
}
function turnoVigente(sel, turno) { return turnosLista[sel] === turno; }
function respetarEleccion(sel) {
    var u = $(sel).data('elegidoUsuario');
    if (u != null && u !== '' && $(sel).find('option').filter(function () { return this.value === String(u); }).length) {
        $(sel).val(String(u)).trigger('change.select2');
    }
}
$(document).on('select2:select', '#cboFamilia, #cboSubFamilia, #cboUmedida, #cboDestinoDeImpresion, #cboBodega, #cboImpuestoIva, #cboImpuestoIla, #cboMenu, #cboReceta', function () {
    $(this).data('elegidoUsuario', $(this).val());
});
/////////////////////////////////Listado Cbos/////////////////////////////
function ListadoDeFamilias(objSelected) {
    $.post(base_url + "index.php/ListaDePrecios/ListadoDeFamilias", {
        id: ""
    }, function(datos) {
        var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
        tbl = "<option value='0' disabled " + (objSelected ? "" : "selected") + ">Seleccione La Familia</option>";
        $.each(obj, function(index, v) { // recorro el array
            if (v.nombre_familia == objSelected || v.id_familia == objSelected) {
                tbl += '<option selected value="' + v.id_familia + '">' + v.nombre_familia + '</option>';
            } else {
                tbl += '<option value="' + v.id_familia + '">' + v.nombre_familia + '</option>';
            }
        }); //fin each
        $("#cboFamilias").html(tbl);
        $("#cboFamilias").select2();
    });
}

function ListadoDeFamiliasMantenedor(objSelected, idSel) {
    var turno = turnoLista('#cboFamilia');   // v1.23.3
    // 19-11-2026 (auditoría): con idSel se elige por número (con nombres repetidos, ej. dos bodegas "AA", el nombre
    // elegía la última y el producto se cambiaba solo al grabar)
    $.post(base_url + "index.php/ListaDePrecios/ListadoDeFamilias", {
        id: ""
    }, function(datos) {
        if (!turnoVigente('#cboFamilia', turno)) return;   // v1.23.3: respuesta vieja (llegó tarde)
        var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
        tbl = '<option selected disabled value="0">Seleccione La Familia</option>';
        $.each(obj, function(index, v) { // recorro el array
            if (idSel != null && idSel !== '' ? String(v.id_familia) === String(idSel) : v.nombre_familia == objSelected) {
                tbl = tbl + '<option selected value="' + v.id_familia + '">' + escaparHtml(v.nombre_familia) +
                    '</option>';
            } else {
                tbl = tbl + '<option value="' + v.id_familia + '">' + escaparHtml(v.nombre_familia) + '</option>';
            }
        }); //fin each
        $("#cboFamilia").html(tbl);
        $("#cboFamilia").select2();
        respetarEleccion('#cboFamilia');
    });
}

function ListadoDeFamiliascboMsubFamilia(objSelected) {
    $.post(base_url + "index.php/ListaDePrecios/ListadoDeFamilias", {
        id: ""
    }, function(datos) {
        var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
        tbl = '<option selected disabled value="0">Seleccione La Familia</option>';
        $.each(obj, function(index, v) { // recorro el array
            if (v.nombre_familia == objSelected) {
                tbl = tbl + '<option selected value="' + v.id_familia + '">' + v.nombre_familia +
                    '</option>';
            } else {
                tbl = tbl + '<option value="' + v.id_familia + '">' + v.nombre_familia + '</option>';
            }
        }); //fin each
        $("#cboFamiliaMsubFamilia").html(tbl);
        $("#cboFamiliaMsubFamilia").select2();
    });
}

// 19-11-2026 (auditoría): solo las subfamilias de la familia elegida (antes salían las de todas y se podía grabar la
// familia X con una subfamilia de la familia Y). Se guardan todas y se filtran al cambiar la familia.
var subfamiliasTodas = [];
function pintarSubFamilias(objSelected, idSel, idFamilia) {
    var fam = Number(idFamilia || $("#cboFamilia").val()) || 0;
    var tbl = '<option selected disabled value="0">Seleccione La SubFamilia</option>';
    $.each(subfamiliasTodas, function(index, v) {
        if (fam > 0 && Number(v.id_familia) !== fam) return;
        var elegida = idSel != null && idSel !== '' ? String(v.id_subfamilia) === String(idSel) : (objSelected && v.nombre_subfamilia == objSelected);
        tbl += '<option ' + (elegida ? 'selected ' : '') + 'value="' + v.id_subfamilia + '">' + escaparHtml(v.nombre_subfamilia) + '</option>';
    });
    $("#cboSubFamilia").html(tbl);
    $("#cboSubFamilia").select2();
}
function ListadoDeSubFamilias(objSelected, idSel, idFamilia) {
    var turno = turnoLista('#cboSubFamilia');   // v1.23.3
    $.post(base_url + "index.php/Productos/ListadoDeSubFamilias", {
        id: ""
    }, function(datos) {
        if (!turnoVigente('#cboSubFamilia', turno)) return;   // v1.23.3: respuesta vieja (llegó tarde)
        subfamiliasTodas = jQuery.parseJSON(datos) || [];
        pintarSubFamilias(objSelected, idSel, idFamilia);
        respetarEleccion('#cboSubFamilia');
    });
}
$(document).on('change', '#cboFamilia', function () {
    var actual = $("#cboSubFamilia").val();
    if (subfamiliasTodas.length) pintarSubFamilias('', actual, $(this).val());
});
/*
function respuestaNuevaComuna(id_comuna, id_ciudad) {
        var idCiudadModal = $("#CboCiudadComuna").val();
        llenarCboComuna(id_comuna, idCiudadModal);
        // llenarCboModificarComuna(id_comuna, idCiudadModal);
        llenarCboCiudad(id_comuna);
        llenarCboTodasLasComuna(id_comuna);
    }

    function ListadoDeSubFamilias(id_familia) {
        $.post(base_url + "index.php/SubFamilias/ListadoSubFamiliasXFamilias", {
            id_familia: id_familia
        }, function(datos) {
            var id_familia = $("#cboFamilia").val();
            var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
            tbl = "";
            if (obj.length == 0) {
                tbl = tbl + '<option selected value=0></option>';
            } else {
                $.each(obj, function(index, v) { // recorro el array
                    if (v.id_familia == id_familia) {
                        tbl = tbl + '<option selected value="' + v.id_subfamilia + '">' + v.nombre_subfamilia + '</option>';
                    } else {
                        tbl = tbl + '<option selected value="' + v.id_subfamilia + '">' + v.nombre_subfamilia + '</option>';
                    }
                }); //fin each
            }
            $("#cboSubFamilia").html(tbl);
            $("#cboSubFamilia").select2();
        });
    }

    $("#cboFamilia").on('select2:select', function(e) {
         var id = $("#cboFamilia").val();
        // var idCiudadModal = $("#CboCiudadComuna").val();
        var data = e.params.data;
        ListadoDeSubFamilias("", data.id);
    });*/

function ListadoDeUmedida(objSelected, idSel) {
    var turno = turnoLista('#cboUmedida');   // v1.23.3
    // 19-11-2026 (auditoría): con idSel se elige por número (con nombres repetidos, ej. dos bodegas "AA", el nombre
    // elegía la última y el producto se cambiaba solo al grabar)
    $.post(base_url + "index.php/Productos/ListadoDeUmedida", {
        id: ""
    }, function(datos) {
        if (!turnoVigente('#cboUmedida', turno)) return;   // v1.23.3: respuesta vieja (llegó tarde)
        var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
        tbl = '<option selected disabled value="0">Seleccione La Unidad De Medida</option>';
        $.each(obj, function(index, v) { // recorro el array
            if (idSel != null && idSel !== '' ? String(v.id_umedida) === String(idSel) : v.nombre_umedida == objSelected) {
                tbl = tbl + '<option selected value="' + v.id_umedida + '">' + escaparHtml(v.nombre_umedida) +
                    '</option>';
            } else {
                tbl = tbl + '<option value="' + v.id_umedida + '">' + escaparHtml(v.nombre_umedida) + '</option>';
            }
        }); //fin each
        $("#cboUmedida").html(tbl);
        $("#cboUmedida").select2();
        respetarEleccion('#cboUmedida');
    });
}

function ListadoDestinoDeImpresion(objSelected, idSel) {
    var turno = turnoLista('#cboDestinoDeImpresion');   // v1.23.3
    // 19-11-2026 (auditoría): con idSel se elige por número (con nombres repetidos, ej. dos bodegas "AA", el nombre
    // elegía la última y el producto se cambiaba solo al grabar)
    $.post(base_url + "index.php/Productos/ListadoDestinoDeImpresion", {
        id: ""
    }, function(datos) {
        if (!turnoVigente('#cboDestinoDeImpresion', turno)) return;   // v1.23.3: respuesta vieja (llegó tarde)
        var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
        var encontrado = false;
        tbl = '<option disabled value="0">Seleccione El Destino De Impresion</option>';
        $.each(obj, function(index, v) { // recorro el array
            if (idSel != null && idSel !== '' ? String(v.id_dimpresion) === String(idSel) : v.nombre_dimpresion == objSelected) {
                encontrado = true;
                tbl = tbl + '<option selected value="' + v.id_dimpresion + '">' + escaparHtml(v.nombre_dimpresion) +
                    '</option>';
            } else {
                tbl = tbl + '<option value="' + v.id_dimpresion + '">' + escaparHtml(v.nombre_dimpresion) +
                    '</option>';
            }
        }); //fin each
        $("#cboDestinoDeImpresion").html(tbl);
        // Si no hubo coincidencia con objSelected, dejar seleccionado el primer destino real de la lista
        if (!encontrado && obj.length > 0) {
            $("#cboDestinoDeImpresion").val(obj[0].id_dimpresion);
        }
        $("#cboDestinoDeImpresion").select2();
        respetarEleccion('#cboDestinoDeImpresion');
        // v1.30.0: segundo destino (mismas impresoras). window.destino2Producto = el guardado del producto (0 = ninguno)
        var d2 = Number(window.destino2Producto || 0), tbl2 = '';
        $.each(obj, function (i, v) {
            tbl2 += '<option value="' + v.id_dimpresion + '"' + (String(v.id_dimpresion) === String(d2) ? ' selected' : '') + '>' + escaparHtml(v.nombre_dimpresion) + '</option>';
        });
        $('#cboDestino2').html(tbl2).select2();   // mismo estilo que las demás listas de la ficha
        $('#ckDestino2').prop('checked', d2 > 0);
        $('#cajaDestino2').toggle(d2 > 0);
    });
}
// v1.30.0: la casilla muestra u oculta la lista del segundo destino
$(document).on('change', '#ckDestino2', function () { $('#cajaDestino2').toggle(this.checked); });

function ListadoDeBodega(objSelected, idSel) {
    var turno = turnoLista('#cboBodega');   // v1.23.3
    // 19-11-2026 (auditoría): con idSel se elige por número (con nombres repetidos, ej. dos bodegas "AA", el nombre
    // elegía la última y el producto se cambiaba solo al grabar)
    $.post(base_url + "index.php/Productos/ListadoDeBodega", {
        id: ""
    }, function(datos) {
        if (!turnoVigente('#cboBodega', turno)) return;   // v1.23.3: respuesta vieja (llegó tarde)
        var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
        tbl = '<option selected disabled value="0">Seleccione La Bodega</option>';
        $.each(obj, function(index, v) { // recorro el array
            if (idSel != null && idSel !== '' ? String(v.id_bodega) === String(idSel) : v.nombre_bodega == objSelected) {
                tbl = tbl + '<option selected value="' + v.id_bodega + '">' + escaparHtml(v.nombre_bodega) +
                    '</option>';
            } else {
                tbl = tbl + '<option value="' + v.id_bodega + '">' + escaparHtml(v.nombre_bodega) + '</option>';
            }
        }); //fin each
        $("#cboBodega").html(tbl);
        $("#cboBodega").select2();
        respetarEleccion('#cboBodega');
    });
}

function ListadoDeImpuestosIva(objSelected, idSel) {
    var turno = turnoLista('#cboImpuestoIva');   // v1.23.3
    // 19-11-2026 (auditoría): con idSel se elige por número (con nombres repetidos, ej. dos bodegas "AA", el nombre
    // elegía la última y el producto se cambiaba solo al grabar)
    $.post(base_url + "index.php/Productos/ListadoDeImpuestos", {
        id: ""
    }, function(datos) {
        if (!turnoVigente('#cboImpuestoIva', turno)) return;   // v1.23.3: respuesta vieja (llegó tarde)
        var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
        tbl = '<option selected disabled value="0">Seleccione El Impuesto Iva</option>';
        $.each(obj, function(index, v) { // recorro el array
            if (idSel != null && idSel !== '' ? String(v.id_impuesto) === String(idSel) : v.nombre_impuesto == objSelected) {
                tbl = tbl + '<option selected value="' + v.id_impuesto + '">' + escaparHtml(v.nombre_impuesto) +
                    '</option>';
            } else {
                tbl = tbl + '<option value="' + v.id_impuesto + '">' + escaparHtml(v.nombre_impuesto) + '</option>';
            }
        }); //fin each
        $("#cboImpuestoIva").html(tbl);
        $("#cboImpuestoIva").select2();
        respetarEleccion('#cboImpuestoIva');
    });
}

// v1.20.0: ILA del producto (bebidas, cervezas, vinos, licores). idSel = impto_ila del producto; sin él, "sin ILA".
function ListadoDeImpuestosAdicional(idSel) {
    var turno = turnoLista('#cboImpuestoIla');   // v1.23.3
    $.post(base_url + "index.php/Productos/ListadoDeImpuestosAdicionales", {
        id: ""
    }, function(datos) {
        if (!turnoVigente('#cboImpuestoIla', turno)) return;   // v1.23.3: respuesta vieja (llegó tarde)
        var obj = jQuery.parseJSON(datos);
        var tbl = '', hayElegido = false;
        $.each(obj, function(index, v) {
            var sinIla = Number(v.valor) === 0;
            var texto = sinIla ? 'Sin impuesto adicional' : v.nombre_impuesto + (v.observacion ? ' (' + v.observacion + ')' : '');
            var elegido = idSel != null && idSel !== '' ? String(v.id_impuesto) === String(idSel) : (sinIla && !hayElegido);
            if (elegido) hayElegido = true;
            tbl += '<option value="' + v.id_impuesto + '"' + (elegido ? ' selected' : '') + '>' + escaparHtml(texto) + '</option>';
        });
        $("#cboImpuestoIla").html(tbl);
        $("#cboImpuestoIla").select2();
        respetarEleccion('#cboImpuestoIla');
    });
}

function ListadoDeMenu(objSelected) {
    var turno = turnoLista('#cboMenu');   // v1.23.3
    $.post(base_url + "index.php/Productos/ListadoDeMenu", {
        id: ""
    }, function(datos) {
        if (!turnoVigente('#cboMenu', turno)) return;   // v1.23.3: respuesta vieja (llegó tarde)
        var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
        tbl = '<option selected disabled value="0">Seleccione El Menu</option>';
        $.each(obj, function(index, v) { // recorro el array
            if (v.nombre_menu == objSelected || v.id_menu == objSelected) {
                tbl = tbl + '<option selected value="' + v.id_menu + '">' + v.nombre_menu + '</option>';
            } else {
                tbl = tbl + '<option value="' + v.id_menu + '">' + v.nombre_menu + '</option>';
            }
        }); //fin each
        $("#cboMenu").html(tbl);
        $("#cboMenu").select2();
        respetarEleccion('#cboMenu');
    });
}

function ListadoDeReceta(objSelected, idSel) {
    var turno = turnoLista('#cboReceta');   // v1.23.3
    // 19-11-2026 (auditoría): con idSel se elige por número (con nombres repetidos, ej. dos bodegas "AA", el nombre
    // elegía la última y el producto se cambiaba solo al grabar)
    $.post(base_url + "index.php/Productos/ListadoDeReceta", {
        id: ""
    }, function(datos) {
        if (!turnoVigente('#cboReceta', turno)) return;   // v1.23.3: respuesta vieja (llegó tarde)
        var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
        tbl = '<option selected disabled value="0">Seleccione La Receta</option>';
        $.each(obj, function(index, v) { // recorro el array
            if (idSel != null && idSel !== '' ? String(v.id_receta) === String(idSel) : v.nombre_receta == objSelected) {
                tbl = tbl + '<option selected value="' + v.id_receta + '">' + escaparHtml(v.nombre_receta) +
                    '</option>';
            } else {
                tbl = tbl + '<option value="' + v.id_receta + '">' + escaparHtml(v.nombre_receta) + '</option>';
            }
        }); //fin each
        $("#cboReceta").html(tbl);
        $("#cboReceta").select2();
        respetarEleccion('#cboReceta');
    });
}

function ListadoDestinoDeImpresionFamilia(objSelected) {
    $.post(base_url + "index.php/Productos/ListadoDestinoDeImpresion", {
        id: ""
    }, function(datos) {
        var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
        tbl = '<option selected disabled value="0">Seleccione El Destino De Impresion</option>';
        $.each(obj, function(index, v) { // recorro el array
            if (v.nombre_dimpresion == objSelected) {
                tbl = tbl + '<option selected value="' + v.id_dimpresion + '">' + v.nombre_dimpresion +
                    '</option>';
            } else {
                tbl = tbl + '<option value="' + v.id_dimpresion + '">' + v.nombre_dimpresion +
                    '</option>';
            }
        }); //fin each
        $("#cboDestinoDeImpresionFamilia").html(tbl);
        $("#cboDestinoDeImpresionFamilia").select2();
    });
}

function ObtenerPreciosDeVenta(idProducto) {
    $.post(base_url + "index.php/Productos/obtenerPreciosDeVenta", {
        id_producto: idProducto
    }, function(datos) {
        var obj = $.parseJSON(datos); // lo convierto a un objeto a json
        tbl = '';
        $.each(obj, function(index, v) { // recorro el array
            tbl = tbl + "<tr id='" + v.id_detalle_lprecio + "'>";
            tbl = tbl + "<td  style= 'display:none' >" + v.id_detalle_lprecio + "</td>";
            tbl = tbl + "<td  style='width:30%'' >" + v.nombre_lprecio + "</td>";
            tbl = tbl +
                '<td style="width:30%" class="text-right" ><input id="montoPrecioVenta" onkeypress="return maxPrecioVenta()" class="text-right form-control form-control-line" type="number" min="0" max="1000000" value="' +
                v.total + '"></td>';
            tbl = tbl + "</tr>";


        }); //fin each
        $("#detallePrecioVenta").html(tbl);

    });
}



function ListadoMenuModalCategorias(objSelected) {
    $.post(base_url + "index.php/Productos/ListadoDeMenu", {
        id: ""
    }, function(datos) {
        var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
        tbl = '<option selected disabled value="0">Seleccione El Menu</option>';
        $.each(obj, function(index, v) { // recorro el array
            if (v.nombre_menu == objSelected || v.id_menu == objSelected) {
                tbl = tbl + '<option selected value="' + v.id_menu + '">' + v.nombre_menu + '</option>';
            } else {
                tbl = tbl + '<option value="' + v.id_menu + '">' + v.nombre_menu + '</option>';
            }
        }); //fin each
        $("#cboMenus").html(tbl);
        $("#cboMenus").select2();
    });
}

function listadoDeCategorias(objSelected) {
    $.post(base_url + "index.php/Productos/listadoDeCategorias", {
        id: ""
    }, function(datos) {
        var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
        tbl = '<option selected disabled value="0">Seleccione La Categoria</option>';
        $.each(obj, function(index, v) { // recorro el array
            if (v.nombre_categoria_menu == objSelected) {
                tbl = tbl + '<option selected value="' + v.id_categoria_menu + '">' + v
                    .nombre_categoria_menu + '</option>';
            } else {
                tbl = tbl + '<option value="' + v.id_categoria_menu + '">' + v.nombre_categoria_menu +
                    '</option>';
            }
        }); //fin each
        $("#cboMenuCategoria").html(tbl);
        $("#cboMenuCategoria").select2();
    });
}

var productosCarta = {};
function ListadoDeAgregadosParaElProducto(objSelected, selectorId) {
    if (!selectorId) selectorId = "#cboSelectorProductosFinal";
    
    console.log("Iniciando carga de productos desde (FUNC):", base_url + "index.php/Productos/ListadoDeProductos", "Para selector:", selectorId);
    
    $.ajax({
        url: base_url + "index.php/Productos/ListadoDeProductos",
        type: "POST",
        dataType: "json",
        data: { id: "" },
        success: function(obj) {
            console.log("Objetos recibidos (FUNC):", obj);
            let tbl = '<option value="0">SELECCIONE EL PRODUCTO</option>';
            productosCarta = {};
            
            let items = obj.aaData || obj; 
            if (Array.isArray(items)) {
                $.each(items, function(index, v) {
                    productosCarta[v.id_producto] = v;
                    if (v.nombre_producto == objSelected) {
                        tbl += '<option selected value="' + v.id_producto + '">' + v.nombre_producto + '</option>';
                    } else {
                        tbl += '<option value="' + v.id_producto + '">' + v.nombre_producto + '</option>';
                    }
                });
            } else {
                console.warn("La respuesta no contiene una lista de ítems válida:", items);
            }
            
            $(selectorId).html(tbl);
            if ($.fn.select2) {
                $(selectorId).select2();
            }
            $(selectorId).trigger('opciones-cargadas');   // v1.24.5: la lista de "varios agregados" se arma con estas opciones
        },
        error: function(xhr, status, error) {
            console.error("Error detallado en la petición AJAX (FUNC):", {xhr: xhr, status: status, error: error});
        }
    });
}

function llenarCboUnMedidaModalReceta(objSelected) {
    $.post(base_url + "index.php/Productos/ListadoDeUmedida", {
        id: ""
    }, function(datos) {
        var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
        tbl = '<option selected value="0">Seleccione La Unidad De Medida</option>';
        $.each(obj, function(index, v) { // recorro el array
            if (v.nombre_umedida == objSelected) {
                tbl = tbl + '<option selected value="' + v.id_umedida + '">' + v.nombre_umedida +
                    '</option>';
            } else {
                tbl = tbl + '<option value="' + v.id_umedida + '">' + v.nombre_umedida + '</option>';
            }
        }); //fin each
        $("#cboUnMedida").html(tbl);
        $("#cboUnMedida").select2();
    });
}

function llenarFamiliaReceta(id_familia_receta) {
    $.post(base_url + "index.php/Recetas/llenarFamiliaReceta", {
        'id_familia_receta': id_familia_receta,
    }, function(datos) {
        var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
        tbl = "";
        tbl = "<option selected value = 0>--SELECCIONE--</option>";
        $.each(obj, function(index, v) { // recorro el array
            if (v.id_familia_receta == id_familia_receta) {
                tbl = tbl + '<option selected value="' + v.id_familia_receta + '">' + v
                    .nombre_familia_receta + '</option>';
            } else {
                tbl = tbl + '<option value="' + v.id_familia_receta + '">' + v.nombre_familia_receta +
                    '</option>';
            }
        }); //fin each

        $("#cboGrupoReceta").html(tbl);
        $("#cboGrupoReceta").select2();
    });
}

function llenarSeccionReceta(id_seccion) {
    $.post(base_url + "index.php/Recetas/llenarSeccionReceta", {
        'id_seccion': id_seccion,
    }, function(datos) {
        var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
        tbl = "";
        tbl = "<option selected value = 0>--SELECCIONE--</option>";
        $.each(obj, function(index, v) { // recorro el array
            if (v.id_seccion == id_seccion) {
                tbl = tbl + '<option selected value="' + v.id_seccion + '">' + v.nombre_seccion +
                    '</option>';
            } else {
                tbl = tbl + '<option value="' + v.id_seccion + '">' + v.nombre_seccion + '</option>';
            }
        }); //fin each

        $("#cboSeccionReceta").html(tbl);
        $("#cboSeccionReceta").select2();
    });
}

function llenarCboInsumos(id_insumo) {
    $.post(base_url + "index.php/Recetas/llenarCboInsumos", {
        'id_insumo': id_insumo,
    }, function(datos) {
        var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
        tbl = "";
        tbl = "<option selected value = 0>Seleccione El Insumo</option>";
        $.each(obj, function(index, v) { // recorro el array
            if (v.id_insumo == id_insumo) {
                tbl = tbl + '<option selected value="' + v.id_insumo + '" cbarra="' + v.cbarra +
                    '" nombreInsumo="' + v.nombre_insumo + '" esReceta="' + v.esreceta + '" umedida="' +
                    v.nombre_umedida + '" costoPP="' + v.costo_neto_ppp + '">' + v.cbarra + '/' + v
                    .nombre_insumo + '</option>';
            } else {
                tbl = tbl + '<option value="' + v.id_insumo + '" cbarra="' + v.cbarra +
                    '"  nombreInsumo="' + v.nombre_insumo + '" esReceta="' + v.esreceta +
                    '" umedida="' + v.nombre_umedida + '" costoPP="' + v.costo_neto_ppp + '">' + v
                    .cbarra + '/' + v.nombre_insumo + '</option>';
            }
        }); //fin each

        $("#cboCodBarraInsumoReceta").html(tbl);
        $("#cboCodBarraInsumoReceta").select2();
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

function soloNumeros(e, espace) {
    key = e.keyCode || e.which;
    teclado = String.fromCharCode(key).toLowerCase();
    letras = "1234567890";
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

function soloNumerosDecimales(e, espace) {
    key = e.keyCode || e.which;
    teclado = String.fromCharCode(key).toLowerCase();
    letras = "1234567890.";
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

function validate(evt) {
    var theEvent = evt || window.event;

    // Handle paste
    if (theEvent.type === 'paste') {
        key = event.clipboardData.getData('text/plain');
    } else {
        // Handle key press
        var key = theEvent.keyCode || theEvent.which;
        key = String.fromCharCode(key);
    }
}
</script>