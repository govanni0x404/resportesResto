<script>
//AGREGADOS DEL PRODUCTO
function AbrirModalAgregadosDelProducto(idProducto) {
    ListadoDeAgregadosParaElProducto();
    $('#codigoAgregado').val("");
    $('#cantidadAgregado').val("");
    $('#valorAdicionalAgregado').val("0");
    $('#ckUsarDestinoDeImpresion').prop('checked', false);
    $('#ckVigenciaAgregado').prop('checked', true);
    idProducto = $('#txtID').val();
    ObtenerAgregadosDelProducto(idProducto);
    $('#ModalAgregadosDelProducto').modal({
        backdrop: 'static',
        keyboard: false
    });
}

function ObtenerAgregadosDelProducto(idProducto) {
    $.post(base_url + "index.php/Productos/obtenerAgregadosDelProducto", {
        id_producto_maestro: idProducto
    }, function(datos) {
        var obj = $.parseJSON(datos); // lo convierto a un objeto a json
        tbl = '';
        $.each(obj, function(index, v) { // recorro el array
            tbl = tbl + "<tr>";
            tbl = tbl + "<td>" + v.id_agregado + "</td>";
            tbl = tbl + "<td id='id_producto' >" + v.producto_maestro + "</td>";
            tbl = tbl + "<td  >" + v.nombre_producto + "</td>";
            tbl = tbl + '<td style="width:30%" class="text-right" ><input id="maxCantidadAgregado_' + v
                .id_agregado + '" onkeypress="return maxCantidadAgregado(' + v.id_agregado +
                ')" class="text-right form-control form-control-line" type="number" min="0" value="' + v
                .cantidad + '"></td>';
            tbl = tbl + '<td style="width:50%" class="text-right" ><input id="maxValorAgregado_' + v
                .id_agregado + '" onkeypress="return maxValorAgregado(' + v.id_agregado +
                ')" class="text-right form-control form-control-line" type="number" min="0" value="' + v
                .valor + '"></td>';
            if (v.vigente == 't') {
                tbl = tbl + "<td  style='width:15%'' >" + 'SI' + "</td>";
            } else {
                tbl = tbl + "<td  style='width:15%'' >" + 'NO' + "</td>";
            }
            if (v.vigente == 't') {
                tbl += "<td <button onclick='DeshabilitarAgregado(" + v.id_agregado +
                    ")' type='button' class='btn-block btn-danger text-center'><i class='fa fa-times-circle'></i> DESHABILITAR AGREGADO</button></td>"
            } else {
                tbl += "<td <button onclick='HabilitarAgregado(" + v.id_agregado +
                    ")' type='button' class='btn-block btn-primary text-center'><i class='fa fa-check-circle'></i> HABILITAR AGREGADO</button></td>"
            }
            tbl = tbl + "</tr>";


        }); //fin each
        $("#detalleAgregadosDelProducto").html(tbl);

    });
}

function GrabarNuevoAgregados() {
    var msg = "";
    var errorexiste = 0;
    if ($('#cboProductoAgregadoProductos').val() == null) {
        msg = "Producto De Agregado\n";
    } else if ($('#cantidadAgregado').val() == 0) {
        msg = "Cantidad De Agregado\n";
    }
    if (msg.length) {
        swal("Falta Ingresar Los Siguiente(s) dato(s):\n" + msg);
    } else {
        //grabar datos
        productoMaestro = $('#txtID').val();
        productoAgregado = $('#cboProductoAgregadoProductos').val();
        cantidadAgregado = $('#cantidadAgregado').val();
        valorAdicionalAgregado = $('#valorAdicionalAgregado').val();
        console.log(productoAgregado);
        $("#tableAgregados tbody tr").each(function(index) {
            $(this).children("td").each(function(index2) {
                switch (index2) {
                    case 0:
                        var idProducto = $(this).html();
                        if (idProducto == productoAgregado) {
                            errorexiste = 1;
                        }

                        console.log(idProducto);
                        break;
                }
            });
        });

        if ($('#ckUsarDestinoDeImpresion').is(":checked")) {
            var usarDestinoDeImpresion = 1
        } else {
            var usarDestinoDeImpresion = 0
        }
        if (valorAdicionalAgregado == '') {
            valorAdicionalAgregado = 0;
        }
        if ($('#ckVigenciaAgregado').is(":checked")) {
            var vigenciaAgregado = 1
        } else {
            var vigenciaAgregado = 0
        }

        if (errorexiste == 1) {
            swal("Error", "El producto ya se encuentra en la lista", "error");
        } else {
            var miJson = {
                'id_producto': productoAgregado,
                'cantidad': cantidadAgregado,
                'vigente': vigenciaAgregado,
                'id_producto_maestro': productoMaestro,
                'valor': valorAdicionalAgregado,
                'usar_destino_impresion': usarDestinoDeImpresion,
            };
            $.post(base_url + "index.php/Productos/grabarAgregados", {
                    'prod': miJson
                }).done(function() {
                    idProducto = $('#id_producto').val();
                    AbrirModalAgregadosDelProducto(idProducto);
                    swal("Listo", "Se registro Exitosamente El Nuevo Agregado", "success");
                })
                .fail(function() {
                    swal("Se produjo un error al realizar el proceso, favor contacte a su administrador");
                })
                .always(function() {});
        }
    }
}

function actualizarCantidadProductoAgregado() {
    var idProducto = $("#txtID").val();
    var detalleAgregados = [];
    $("#tableAgregados tbody tr").each(function(index) {
        $(this).children("td").each(function(index2) {
            CantidadAgregado = 0;
            switch (index2) {
                case 0: //id producto
                    idAgregado = $(this).html();
                    break;
                case 3: // 
                    var row = $(this);
                    if (row.find('input[type="number"]').val()) {
                        CantidadAgregado = row.find('input[type="number"]').val().replace(/[+-]+/g, '');
                    }

                    detalleAgregados.push({
                        'id_producto_maestro': idProducto,
                        'id_agregado': idAgregado,
                        'cantidad': CantidadAgregado,
                    });
                    break;
            }
        });
    }); //fin recorrer tabla

    console.log(detalleAgregados);
    $.post(base_url + "index.php/Productos/actualizarCantidadProductoAgregado", {
            detalle: detalleAgregados
        }, function() {}).done(function(datos) {
            console.log(datos);
            swal("Listo", "Se Actualizo Exitosamente El Agregado", "success");
            //$('#modalCambioPrecio').modal('hide');
            //tableResumenListaPrecios.ajax.reload();
        })
        .fail(function() {
            alert("Se produjo un error al realizar el proceso, favor contacte a su administrador");
        })
        .always(function() {});


} //

function actualizarValorAdicionalProductoAgregado() {
    var idProducto = $("#txtID").val();
    var detalleAgregados = [];
    $("#tableAgregados tbody tr").each(function(index) {
        $(this).children("td").each(function(index2) {
            ValorAdicionalAgregado = 0;
            switch (index2) {
                case 0: //id producto
                    idAgregado = $(this).html();
                    break;
                case 4: // 
                    var row = $(this);
                    if (row.find('input[type="number"]').val()) {
                        ValorAdicionalAgregado = row.find('input[type="number"]').val().replace(
                            /[+-]+/g, '');
                    }

                    detalleAgregados.push({
                        'id_producto_maestro': idProducto,
                        'id_agregado': idAgregado,
                        'valor': ValorAdicionalAgregado,
                    });
                    break;
            }
        });
    }); //fin recorrer tabla

    console.log(detalleAgregados);
    $.post(base_url + "index.php/Productos/actualizarValorAdicionalProductoAgregado", {
            detalle: detalleAgregados
        }, function() {}).done(function(datos) {
            console.log(datos);
            swal("Listo", "Se Actualizo Exitosamente El Agregado", "success");
        })
        .fail(function() {
            alert("Se produjo un error al realizar el proceso, favor contacte a su administrador");
        })
        .always(function() {});


} //

$('#btnActualizarAgregado').on('click', function() {
    actualizarCantidadProductoAgregado();
    actualizarValorAdicionalProductoAgregado();
});

function HabilitarAgregado(id) {
    swal({
            title: "Esta seguro/a?",
            text: "De Habilitar El Agregado?",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#EB360F",
            confirmButtonText: "Aceptar!",
            cancelButtonText: "Cancelar!",
            closeOnConfirm: true,
            closeOnCancel: false
        },
        function(isConfirm) {
            if (isConfirm) {
                idProducto = $('#id_producto').val();
                $.post(base_url + "index.php/Productos/HabilitarAgregado", {
                    data: id
                }, function() {}).done(function(datos) {

                    let positionError = datos.search("ERROR:");
                    if (parseFloat(positionError) > 0) {
                        swal("Error", "Se produjo un error, favor reintente \n(" + e + ")\n" + datos.substr(
                            positionError, 150), "error");
                    } else {
                        swal("Agregado Habilitado!", "Se Habilito El Agregado con éxito!", "success");
                        AbrirModalAgregadosDelProducto(idProducto);
                    }
                }).fail(function() {
                    swal("Se produjo un error al realizar La Habilitacion, favor vuelva a intentarlo");
                });
                swal.close();
            } else {
                swal.close();
            }
        });
}

function DeshabilitarAgregado(id) {
    swal({
            title: "Esta seguro/a?",
            text: "De Deshabilitar El Agregado?",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#EB360F",
            confirmButtonText: "Aceptar!",
            cancelButtonText: "Cancelar!",
            closeOnConfirm: true,
            closeOnCancel: false
        },
        function(isConfirm) {
            if (isConfirm) {
                idProducto = $('#id_producto').val();
                $.post(base_url + "index.php/Productos/DeshabilitarAgregado", {
                    data: id
                }, function() {}).done(function(datos) {

                    let positionError = datos.search("ERROR:");
                    if (parseFloat(positionError) > 0) {
                        swal("Error", "Se produjo un error, favor reintente \n(" + e + ")\n" + datos.substr(
                            positionError, 150), "error");
                    } else {
                        swal("Agregado Deshabilitado!", "Se Deshabilito El Agregado con éxito!", "success");
                        AbrirModalAgregadosDelProducto(idProducto);
                    }
                }).fail(function() {
                    swal("Se produjo un error al realizar La Habilitacion, favor vuelva a intentarlo");
                });
                swal.close();
            } else {
                swal.close();
            }
        });
}

function ListadoDeAgregadosParaElProducto(objSelected) {
    $.post(base_url + "index.php/Productos/ListadoDeProductos", {
        id: ""
    }, function(datos) {
        var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
        tbl = '<option selected disabled value="0">SELECCIONE EL AGREGADO</option>';
        $.each(obj, function(index, v) { // recorro el array
            if (v.nombre_producto == objSelected) {
                tbl = tbl + '<option selected value="' + v.id_producto + '">' + v.nombre_producto +
                    '</option>';
            } else {
                tbl = tbl + '<option value="' + v.id_producto + '">' + v.nombre_producto + '</option>';
            }
        }); //fin each
        $("#cboProductoAgregadoProductos").html(tbl);
        $("#cboProductoAgregadoProductos").select2();
    });
}

function maxCantidadAgregado(id_agregado) {
    var numberInput = document.getElementById('maxCantidadAgregado_' + id_agregado);
    numberInput.addEventListener('input', function() {
        var value = parseInt(numberInput.value);
        if (numberInput.value.length > 4) {
            numberInput.value = numberInput.value.slice(0, 4);
        }
        if (value > 1000) {
            numberInput.value = 1000;
        } else if (value < 0) {
            numberInput.value = 0;
        }
    });
}

function maxValorAgregado(id_agregado) {
    var numberInput = document.getElementById('maxValorAgregado_' + id_agregado);
    numberInput.addEventListener('input', function() {
        var value = parseInt(numberInput.value);
        if (numberInput.value.length > 5) {
            numberInput.value = numberInput.value.slice(0, 5);
        }
        if (value > 50000) {
            numberInput.value = 50000;
        } else if (value < 0) {
            numberInput.value = 0;
        }
    });
}
</script>