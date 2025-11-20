<script>
    //MENSAJES DEL PRODUCTO
    function AbrirModalMensajesDelProducto(idProducto) {
        $('#txtMsgProducto').val("");
        $('#ckVigenciaMsg').prop('checked', true);
        idProducto = $('#txtID').val();
        obtenerMensajesDelProducto(idProducto);
        $('#ModalMensajesDelProducto').modal({
            backdrop: 'static',
            keyboard: false
        });
    }

    function obtenerMensajesDelProducto(idProducto) {
        $.post(base_url + "index.php/Productos/obtenerMensajesDelProducto", {
            id_producto: idProducto
        }, function(datos) {
            var obj = $.parseJSON(datos); // lo convierto a un objeto a json
            tbl = '';
            $.each(obj, function(index, v) { // recorro el array
                tbl = tbl + "<tr>";
                tbl = tbl + "<td>" + v.id_mensaje_producto + "</td>";
                tbl = tbl + "<td>" + v.nombre_producto + "</td>";
                tbl = tbl + "<td>" + v.mensaje + "</td>";
                if (v.vigente == 't') {
                    tbl = tbl + "<td  style='width:15%'' >" + 'SI' + "</td>";
                } else {
                    tbl = tbl + "<td  style='width:15%'' >" + 'NO' + "</td>";
                }
                if (v.vigente == 't') {
                    tbl += "<td <button onclick='DeshabilitarMensajeProducto(" + v.id_mensaje_producto + ")' type='button' class='btn-block btn-danger text-center'><i class='fa fa-times-circle'></i> DESHABILITAR MENSAJE</button></td>"
                } else {
                    tbl += "<td <button onclick='HabilitarMensajeProducto(" + v.id_mensaje_producto + ")' id='BtnHabilitarMsg' type='button' class='btn-block btn-primary text-center'><i class='fa fa-check-circle'></i> HABILITAR MENSAJE</button></td>"
                }
                tbl = tbl + "</tr>";


            }); //fin each
            $("#detalleMsgDelProducto").html(tbl);

        });
    }

    function grabarMsgProductos() {
        var msg = "";
        if ($('#txtMsgProducto').val() == 0) {
            msg = "Mensaje Del Producto\n";
        }
        if (msg.length) {
            swal("Falta Ingresar Los Siguiente(s) dato(s):\n" + msg);
        } else {
            //grabar datos
            productoConMsg = $('#txtID').val();
            MsgProducto = $('#txtMsgProducto').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
            if ($('#ckVigenciaMsg').is(":checked")) {
                var vigenciaMsg = 1
            } else {
                var vigenciaMsg = 0
            }
            var miJson = {
                'id_producto': productoConMsg,
                'mensaje': MsgProducto,
                'vigente': vigenciaMsg,
            };
            $.post(base_url + "index.php/Productos/grabarMsgProductos", {
                    'prod': miJson
                }).done(function() {
                    idProducto = $('#id_producto').val();
                    AbrirModalMensajesDelProducto(idProducto)
                    swal("Listo", "Se registro Exitosamente El Nuevo Mensaje", "success");
                })
                .fail(function() {
                    swal("Se produjo un error al realizar el proceso, favor contacte a su administrador");
                })
                .always(function() {});
        }
    }

    function HabilitarMensajeProducto(id) {
        swal({
                title: "Esta seguro/a?",
                text: "De Habilitar El Mensaje Del Producto?",
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
                    $.post(base_url + "index.php/Productos/HabilitarMensajeProducto", {
                        data: id
                    }, function() {}).done(function(datos) {

                        let positionError = datos.search("ERROR:");
                        if (parseFloat(positionError) > 0) {
                            swal("Error", "Se produjo un error, favor reintente \n(" + e + ")\n" + datos.substr(positionError, 150), "error");
                        } else {
                            swal("Mensaje Habilitado!", "Se Habilito El Mensaje con éxito!", "success");
                            AbrirModalMensajesDelProducto(idProducto);
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

    function DeshabilitarMensajeProducto(id) {
        swal({
                title: "Esta seguro/a?",
                text: "De Deshabilitar El Mensaje Del Producto?",
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
                    $.post(base_url + "index.php/Productos/DeshabilitarMensajeProducto", {
                        data: id
                    }, function() {}).done(function(datos) {

                        let positionError = datos.search("ERROR:");
                        if (parseFloat(positionError) > 0) {
                            swal("Error", "Se produjo un error, favor reintente \n(" + e + ")\n" + datos.substr(positionError, 150), "error");
                        } else {
                            swal("Mensaje Deshabilitado!", "Se Deshabilito El Mensaje con éxito!", "success");
                            AbrirModalMensajesDelProducto(idProducto);
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
</script>