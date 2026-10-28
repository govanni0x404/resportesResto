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
            showAppAlert('error',"- Falta Ingresar Los Siguiente(s) dato(s):\n" + msg, {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
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
                    showAppAlert('success', "Se registro Exitosamente El Nuevo Mensaje!", {
                        duration: 4000,
                        image: base_url + 'assets/img/success.gif'
                    });
                })
                .fail(function() {
                    showAppAlert('error',"- Se produjo un error al realizar el proceso, favor contacte a su administrador", {
                        duration: 5000,
                        image: base_url+ 'assets/img/errorGif.gif'
                    });
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
                confirmButtonColor: "#C9372C",
                confirmButtonText: "Aceptar!",
                cancelButtonText: "Cancelar!",
                closeOnConfirm: true,
                closeOnCancel: true
            },
            function(isConfirm) {
                if (isConfirm) {
                    idProducto = $('#id_producto').val();
                    $.post(base_url + "index.php/Productos/HabilitarMensajeProducto", {
                        data: id
                    }, function() {}).done(function(datos) {

                        let positionError = datos.search("ERROR:");
                        if (parseFloat(positionError) > 0) {
                            showAppAlert('error',"- Se produjo un error, favor reintente \n(" + e + ")\n" + datos.substr(positionError, 150), {
                                duration: 5000,
                                image: base_url+ 'assets/img/errorGif.gif'
                            });
                        } else {
                            showAppAlert('Mensaje Habilitado', "Se Habilito El Mensaje con éxito!", {
                                duration: 4000,
                                image: base_url + 'assets/img/success.gif'
                            });
                            AbrirModalMensajesDelProducto(idProducto);
                        }
                    }).fail(function() {
                        showAppAlert('error',"- Se produjo un error al realizar La Habilitacion, favor vuelva a intentarlo", {
                            duration: 5000,
                            image: base_url+ 'assets/img/errorGif.gif'
                        });
                    });
                }
            });
    }

    function DeshabilitarMensajeProducto(id) {
        swal({
                title: "Esta seguro/a?",
                text: "De Deshabilitar El Mensaje Del Producto?",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#C9372C",
                confirmButtonText: "Aceptar!",
                cancelButtonText: "Cancelar!",
                closeOnConfirm: true,
                closeOnCancel: true
            },
            function(isConfirm) {
                if (isConfirm) {
                    idProducto = $('#id_producto').val();
                    $.post(base_url + "index.php/Productos/DeshabilitarMensajeProducto", {
                        data: id
                    }, function() {}).done(function(datos) {

                        let positionError = datos.search("ERROR:");
                        if (parseFloat(positionError) > 0) {
                            showAppAlert('error',"- Se produjo un error, favor reintente \n(" + e + ")\n" + datos.substr(positionError, 150), {
                                duration: 5000,
                                image: base_url+ 'assets/img/errorGif.gif'
                            });
                        } else {
                            showAppAlert('Mensaje Deshabilitado', "Se Deshabilito El Mensaje con éxito!", {
                                duration: 4000,
                                image: base_url + 'assets/img/success.gif'
                            });
                            AbrirModalMensajesDelProducto(idProducto);
                        }
                    }).fail(function() {
                        showAppAlert('error',"- Se produjo un error al realizar La Habilitacion, favor vuelva a intentarlo", {
                            duration: 5000,
                            image: base_url+ 'assets/img/errorGif.gif'
                        });
                    });
                }else{

                }
            });
    }
</script>