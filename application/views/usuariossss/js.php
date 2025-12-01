<script>
    var base_url = "<?php echo base_url(); ?>";
    tablaUsuarios = $('#tableUsuarios').DataTable();

    $(document).ready(function() {
        ListarControlDeUsuarios();
    });

    function ListarControlDeUsuarios() {
        if ($.fn.dataTable.isDataTable('#tableUsuarios')) {
            tablaUsuarios = $('#tableUsuarios').DataTable();
            tablaUsuarios.destroy();
        }
        tablaUsuarios = $("#tableUsuarios").DataTable({
            "ajax": {
                "url": base_url + "index.php/Usuarios/ListarUsuariosDelSistema"
            },
            "bPaginate": true,
            "bProcessing": true,
            "pageLength": 10,
            "select": true,
            "dom": "Bfrtip",
            "ordering": false,
            "responsive": {
                details: {
                    type: 'column',
                    target: -1
                }
            },
            "columns": [{
                    mData: "id_usuario",
                    className: "text-right",
                    "visible": false
                },
                {
                    mData: "nombre_usuario",
                    className: "text-left"
                },
                {
                    mData: "clave",
                    className: "text-right",
                    "visible": false
                },
                {
                    mData: "esadmin",
                    className: "text-left"
                },
                {
                    mData: "esgarzon",
                    className: "text-left"
                },
                {
                    mData: "nombre_rol",
                    className: "text-left"
                },
                {
                    mData: "vigente",
                    className: "text-left"
                },
            ],
            'language': {
                url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json",
                // traduce la linea de abajo que quedaba en ingles
                select: {
                    rows: {
                        _: " (Seleccionadas %d filas)",
                        0: " (Presione una linea a seleccionar)",
                        1: " (1 Fila seleccionada)"
                    }
                }
            },
            "buttons": [{}]
        });
        tablaUsuarios
            .on('select', function(e, dt, type, indexes) {
                var rowData = tablaUsuarios.rows(indexes).data().toArray();

                $('#idUsuario').val(rowData[0].id_usuario);
                $('#nombreUsuario').val(rowData[0].nombre_usuario);
                $('#claveUsuario').val(rowData[0].clave);
                llenarRoles(rowData[0].nombre_rol);
                if (rowData[0].esadmin == 'SI') {
                    $('#ckEsAdmin').prop('checked', true);
                } else {
                    $('#ckEsAdmin').prop('checked', false);
                }
                if (rowData[0].esgarzon == 'SI') {
                    $('#ckEsGarzon').prop('checked', true);
                } else {
                    $('#ckEsGarzon').prop('checked', false);
                }
                if (rowData[0].vigente == 'SI') {
                    $('#ckVigente').prop('checked', true);
                } else {
                    $('#ckVigente').prop('checked', false);
                }
                $('#ModalUsuario').modal({
                    backdrop: 'static',
                    keyboard: false
                });

            })
    } //fin obtenerDatos


    function crearNuevoUsuario() {
        $('#id_usuario').val("");
        $('#nombre_usuario').val("");
        $('#clave').val("");
        $('#ckEsAdmin').prop('checked', false);
        $('#ckEsGarzon').prop('checked', false);
        $('#ckVigente').prop('checked', true);
        $('#ckAutorizaCambioConexion').prop('checked', true);
        llenarRoles();
        $('#ModalUsuario').modal({
            backdrop: 'static',
            keyboard: false
        });
    }

    function grabarUsuariosLocal() {
        var msg = "";
        if ($('#nombreUsuario').val() == 0) {
            msg = "-> Nombre De Usuario\n";
        }
        if ($('#claveUsuario').val() == 0) {
            msg = "-> Clave De Usuario\n";
        }
        if ($('#CboRol').val() == 0) {
            msg = "-> Rol De Usuario\n";
        }
        if ($('#correoAccesoWeb').val() == 0) {
            msg = "-> Correo De Acceso Web Para El Portal\n";
        }
        if (msg.length) {
            swal("Falta Ingresar Los Siguiente(s) dato(s):\n" + msg);
        } else {
            //grabar datos
            IdUsuario = $('#idUsuario').val();
            NombreUsuario = $('#nombreUsuario').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
            RolUsuario = $('#CboRol').val();
            ClaveUsaurio = $('#claveUsuario').val();

            if ($('#ckEsAdmin').is(":checked")) {
                var EsAdmin = 1
            } else {
                var EsAdmin = 0
            }
            if ($('#ckEsGarzon').is(":checked")) {
                var EsGarzon = 1
            } else {
                var EsGarzon = 0
            }
            if ($('#ckVigente').is(":checked")) {
                var EsVigente = 1
            } else {
                var EsVigente = 0
            }

            var miJsonLocal = {
                'id_usuario': IdUsuario,
                'nombre_usuario': NombreUsuario,
                'clave': ClaveUsaurio,
                'esadmin': EsAdmin,
                'esgarzon': EsGarzon,
                'id_rol': RolUsuario,
                'vigente': EsVigente
            };

            CorreoAcceso = $('#correoAccesoWeb').val();
            if ($('#ckAutorizaCambioConexion').is(":checked")) {
                var autorizaCambioConexion = 1
            } else {
                var autorizaCambioConexion = 0
            }

            var miJsonIntarnet = {
                'correo_acceso': CorreoAcceso,
                'clave_acceso': ClaveUsaurio,
                'nombre_usuario': NombreUsuario,
                'id_usuario_cliente': IdUsuario,
                'autoriza_cambio_conexion': autorizaCambioConexion

            };
            $.post(base_url + "index.php/Usuarios/grabarUsuariosLocal", {
                'prodLocal': miJsonLocal
            })
            $.post(base_url + "index.php/Usuarios/grabarUsuariosIntranet", {
                    'prodIntranet': miJsonIntarnet
                }).done(function() {
                    $('#ModalUsuario').modal('hide');
                    tablaUsuarios.ajax.reload();
                    swal("Listo", "Se registro Exitosamente", "success");
                })
                .fail(function() {
                    alert("Se produjo un error al realizar el proceso, favor contacte a su administrador");
                })
                .always(function() {});
        }
    }

    $('#btnGrabarUsuario').click(function() {
        grabarUsuariosLocal();
    })

    function ValidacionOcultarMostarClaveUsuario() {
        if ($('#ckMostrarClaveUsuario').prop('checked') == true) {
            $('#claveUsuario').prop('type', 'text');
        } else {

            $('#claveUsuario').prop('type', 'password');
        }
    }
    $('#ckMostrarClaveUsuario').click(function() {
        ValidacionOcultarMostarClaveUsuario();
    })

    function llenarRoles(objSelected, id_rol) {
        $.post(base_url + "index.php/Usuarios/ListarRolesUsuarios", {
            id_rol: id_rol
        }, function(datos) {

            var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json

            tbl = "";
            $.each(obj, function(index, v) { // recorro el array
                if (v.nombre_rol == objSelected) {
                    tbl = tbl + '<option selected value="' + v.id_rol + '">' + v.nombre_rol + '</option>';
                } else {
                    tbl = tbl + '<option value="' + v.id_rol + '">' + v.nombre_rol + '</option>';
                }
            }); //fin each
            $("#CboRol").html(tbl);
            $("#CboRol").select2();

        });
    }

    function sololetrasyNumeros(e, espace) {
        key = e.keyCode || e.which;
        teclado = String.fromCharCode(key).toLowerCase();
        letras = "qwertyuiopasdfghjklñzxcvbnm12345678 ";
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
        aNumerico = "0123456789.";
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

        if (aNumerico.indexOf(teclado) == -1 && !teclado_especial) {
            return false;
        }
    }
    /*
        const validateEmail = () => {
            return email.match(
                /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
            );
        };*/
</script>