<script>
    var base_url = "<?php echo base_url(); ?>";
    tableListadoMenu = $('#tableListadoMenu').DataTable();
    tableCategoriasMenu = $('#tableCategoriasMenu').DataTable();
    $(document).ready(function() {

    });

    function AbrirModalListadoMenu() {
        ListadoMenu();
        $('#ModalListadoMenus').modal({
            backdrop: 'static',
            keyboard: false
        });
    }

    function AbrirModalNuevoMenu() {
        $('#txtIdMenu').val("");
        $('#txtNombreMenu').val("");
        $('#ckVigenciaMenu').prop('checked', true);
        $('#ModalNuevoMenu').modal({
            backdrop: 'static',
            keyboard: false
        });
    }

    function ListadoMenu() {
        let tituloExport = "CARTA DE PRODUCTOS";
        if ($.fn.dataTable.isDataTable('#tableListadoMenu')) {
            tableListadoMenu = $('#tableListadoMenu').DataTable();
            tableListadoMenu.destroy();
        }
        tableListadoMenu = $("#tableListadoMenu").DataTable({
            "ajax": {
                "url": base_url + "index.php/Productos/obtenerListadoMenu",
                "type": "POST",
                "data": {

                }
            },
            "bPaginate": false,
            "bProcessing": false,
            "pageLength": 10,
            "select": false,
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
                    mData: "id_menu",
                    className: "text-left",
                    visible: false
                },
                {
                    mData: "nombre_menu",
                    className: "text-left"
                },
                {
                    mData: "vigente",
                    className: "text-center"
                },
                /*
                                {
                                    mData: "btn_accion",
                                    className: "text-center"
                                },*/
                {
                    mData: "btnCategoriasMenu",
                    className: "text-center"
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
            'buttons': []
        });
        /*tableListadoMenu.on('select', function(e, dt, type, indexes) {
                var rowData = tableListadoMenu.rows(indexes).data().toArray();
             AbrirModalListadoCategoriasMenu();
                
            });*/
    }

    function GrabarNuevoMenu() {
        var msg = "";
        if ($('#txtNombreMenu').val() == 0) {
            msg = "Nombre Menu\n";
        }
        if (msg.length) {
            swal("Falta Ingresar Los Siguiente(s) dato(s):\n" + msg);
        } else {
            //grabar datos
            IdMenu = $('#txtIdMenu').val();
            NombreMenu = $('#txtNombreMenu').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
            if ($('#ckVigenciaMenu').is(":checked")) {
                var vigenciaMenu = 1
            } else {
                var vigenciaMenu = 0
            }
            var miJson = {
                'id_menu': IdMenu,
                'nombre_menu': NombreMenu,
                'vigente': vigenciaMenu
            };
        };
        $.post(base_url + "index.php/Productos/grabarNuevoMenu", {
                'prod': miJson
            }).done(function() {
                $('#ModalNuevoMenu').modal('hide');
                tableListadoMenu.ajax.reload();
                swal("Listo", "Se registro Exitosamente El Nuevo Menu", "success");
            })
            .fail(function() {
                swal("Se produjo un error al realizar el proceso, favor contacte a su administrador");
            })
            .always(function() {});
    }


    /*
            function habilitarVigenciaMenu(id_menu) {
            swal({
                    title: "Esta seguro/a?",
                    text: "De Habilitar El Menu?",
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
                         id_menu = $('v.id_menu').val();
                        $.post(base_url + "index.php/Productos/habilitarVigenciaMenu", {
                            id_menu: id_menu
                        }, function() {}).done(function(datos) {

                            let positionError = datos.search("ERROR:");
                            if (parseFloat(positionError) > 0) {
                                swal("Error", "Se produjo un error, favor reintente \n(" + e + ")\n" + datos.substr(positionError, 150), "error");
                            } else {
                                swal("Menu Habilitado!", "Se Habilito El Menu con éxito!", "success");
                                ListadoMenu(id_menu);
                            }
                        }).fail(function() {
                            swal("Se produjo un error al realizar La Habilitacion, favor vuelva a intentarlo");
                        });
                        swal.close();
                    } else {
                        swal.close();
                    }
                });
        }*/


    //CATEGORIA MENU 

    function AbrirModalListadoCategoriasMenu(id_menu) {
        ListadoCategoriaMenu(id_menu);
        $('#ModalListadoCategoriasMenu').modal({
            backdrop: 'static',
            keyboard: false
        });
    }

    function AbrirModalNuevaCategoriasMenu() {
        $('#IdCategoria').val("");
        $('#txtNombreCategoria').val("");
        $('#ckVigenciaCategoria').prop('checked', true);
        ListadoMenuModalCategorias();
        $('#ModalNuevaCategoria').modal({
            backdrop: 'static',
            keyboard: false
        });
    }

    function ListadoCategoriaMenu(id_menu) {
        let tituloExport = "CATEGORIAS";
        if ($.fn.dataTable.isDataTable('#tableCategoriasMenu')) {
            tableCategoriasMenu = $('#tableCategoriasMenu').DataTable();
            tableCategoriasMenu.destroy();
        }
        tableCategoriasMenu = $("#tableCategoriasMenu").DataTable({
            "ajax": {
                "url": base_url + "index.php/Productos/obtenerCategoriaMenu",
                "type": "POST",
                "data": {
                    'id_menu': id_menu,
                }
            },
            "bPaginate": false,
            "bProcessing": false,
            "pageLength": 10,
            "select": false,
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
                    mData: "id_categoria_menu",
                    className: "text-left",
                    visible: false
                },
                {
                    mData: "id_menu",
                    className: "text-left",
                    visible: false
                },
                {
                    mData: "nombre_categoria_menu",
                    className: "text-center"
                },
                {
                    mData: "vigente",
                    className: "text-center"
                },
                {
                    mData: "btnAgregarProductosCategoria",
                    className: "text-center"
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
            'buttons': []
        });
    }

    function GrabarCategoriaMenu() {
        var msg = "";
        if ($('#cboMenus').val() == 0) {
            msg = "Menu Al Cual Asignara La Categoria\n";
        }
        if ($('#txtNombreCategoria').val() == 0) {
            msg = "Nombre Categoria\n";
        }
        if (msg.length) {
            swal("Debe Ingresar Los Siguiente(s) dato(s):\n" + msg);
        } else {
            //grabar datos
            IdCategoria = $('#IdCategoria').val();
            IdMenu = $('#cboMenus').val();
            NombreCategoria = $('#txtNombreCategoria').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
            if ($('#ckVigenciaCategoria').is(":checked")) {
                var vigenciaCategoria = 1
            } else {
                var vigenciaCategoria = 0
            }
            var miJson = {
                'id_categoria_menu': IdCategoria,
                'id_menu': IdMenu,
                'nombre_categoria_menu': NombreCategoria,
                'vigente': vigenciaCategoria
            };
        };
        $.post(base_url + "index.php/Productos/grabarNuevaCategoria", {
                'prod': miJson
            }).done(function() {
                $('#ModalNuevaCategoria').modal('hide');
                tableCategoriasMenu.ajax.reload();
                swal("Listo", "Se registro Exitosamente La Nueva Categoria", "success");
            })
            .fail(function() {
                swal("Se produjo un error al realizar el proceso, favor contacte a su administrador");
            })
            .always(function() {});
    }



    //PRODUCTOS AGREGADOS A LA CATEGORIA
    function AbrirModalProductosAgregadosACategoria(id_categoria_menu) {
        $('#CostoCategoriaMenu').val("0");
        $('#AdicionalCategoriaMenu').val("0");
        $('#ckPermitirCambio').prop('checked', false);
        $('#ckImprimirProductoCategoria').prop('checked', false);
        $('#CantMinima').val("");
        $('#CantMaxima').val("");
        $('#ckEstricto').prop('checked', false);
        $('#CostoPromedio').val("");
        listadoDeCategorias();
        ListadoDeAgregadosParaElProducto();
        obtenerProductosQueConformanElMenu(id_categoria_menu);
        ObtenerConfiguracionesCategoriaMenu(id_categoria_menu);
        $('#ModalAgregarProductosAcategorias').modal({
            backdrop: 'static',
            keyboard: false
        });
    }

    function obtenerProductosQueConformanElMenu(id_categoria_menu) {
        $.post(base_url + "index.php/Productos/obtenerProductosQueConformanElMenu", {
            id_categoria_menu: id_categoria_menu
        }, function(datos) {
            var obj = $.parseJSON(datos); // lo convierto a un objeto a json
            tbl = '';
            $.each(obj, function(index, v) { // recorro el array
                tbl = tbl + "<tr>";
                tbl = tbl + "<td style= 'display:none'>" + v.id_detalle_categoria_menu + "</td>";
                tbl = tbl + "<td id='id_categoria_menu' style= 'display:none'>" + v.id_categoria_menu + "</td>";
                tbl = tbl + "<td style= 'display:none'>" + v.nombre_categoria_menu + "</td>";
                tbl = tbl + "<td>" + v.nombre_producto + "</td>";
                tbl = tbl + '<td style="width:35%" class="text-right" ><input class="text-right form-control form-control-line" type="number" min="0" name="" value="' + v.costo_receta + '"></td>';
                tbl = tbl + '<td style="width:35%" class="text-right" ><input class="text-right form-control form-control-line" type="number" min="0" name="" value="' + v.costo_adicional_venta + '"></td>';
                if (v.permite_cambio == 't') {
                    tbl = tbl + "<td  style='width:15%'' >" + 'SI' + "</td>";
                } else {
                    tbl = tbl + "<td  style='width:15%'' >" + 'NO' + "</td>";
                }
                if (v.imprimir == 't') {
                    tbl = tbl + "<td  style='width:15%'' >" + 'SI' + "</td>";
                } else {
                    tbl = tbl + "<td  style='width:15%'' >" + 'NO' + "</td>";
                }
                if (v.vigente == 't') {
                    tbl = tbl + "<td  style='width:15%'' >" + 'SI' + "</td>";
                } else {
                    tbl = tbl + "<td  style='width:15%'' >" + 'NO' + "</td>";
                }
                /*if (v.permite_cambio == 't') {
                    tbl += "<td <button type='button' class='btn-block btn-danger text-center'><i class='fa fa-times-circle'></i> Deshabilitar Permitir Cambio</button></td>"
                } else {
                    tbl += "<td <button type='button' class='btn-block btn-primary text-center'><i class='fa fa-check-circle'></i> habilitar Permitir Cambio</button></td>"
                }
                if (v.imprimir == 't') {
                    tbl += "<td <button type='button' class='btn-block btn-danger text-center'><i class='fa fa-times-circle'></i> Deshabilitar Impresion</button></td>"
                } else {
                    tbl += "<td <button type='button' class='btn-block btn-primary text-center'><i class='fa fa-check-circle'></i> habilitar Impresion</button></td>"
                }
                if (v.vigente == 't') {
                    tbl += "<td <button type='button' class='btn-block btn-danger text-center'><i class='fa fa-times-circle'></i> Deshabilitar Producto</button></td>"
                } else {
                    tbl += "<td <button type='button' class='btn-block btn-primary text-center'><i class='fa fa-check-circle'></i> Habilitar Producto</button></td>"
                }*/
                tbl = tbl + "</tr>";


            }); //fin each
            $("#DetalleCategoria").html(tbl);

        });
    }

    function actualizarCostoRecetaCategoriaMenu() {
        var detalleComposicionMenu = [];
        $("#tableDetalleCategoria tbody tr").each(function(index) {
            $(this).children("td").each(function(index2) {
                CostoReceta = 0;
                switch (index2) {
                    case 0:
                        idDetalleCategoriaMenu = $(this).html();
                        break;
                    case 3:
                        var row = $(this);
                        if (row.find('input[type="number"]').val()) {
                            CostoReceta = row.find('input[type="number"]').val();
                        }

                        detalleComposicionMenu.push({
                            'id_detalle_categoria_menu': idDetalleCategoriaMenu,
                            'costo_receta': CostoReceta,
                        });
                        break;
                }
            });
        });

        console.log(detalleComposicionMenu);
        $.post(base_url + "index.php/Productos/actualizarCostoRecetaCategoriaMenu", {
                detalle: detalleComposicionMenu
            }, function() {}).done(function(datos) {
                console.log(datos);
                swal("Listo", "Se Actualizo Exitosamente", "success");
            })
            .fail(function() {
                alert("Se produjo un error al realizar el proceso, favor contacte a su administrador");
            })
            .always(function() {});
    }

    function actualizarCostoAdicionalVentaCategoriaMenu() {
        var detalleComposicionMenu = [];
        $("#tableDetalleCategoria tbody tr").each(function(index) {
            $(this).children("td").each(function(index2) {
                CostoAdicionalVenta = 0;
                switch (index2) {
                    case 0:
                        idDetalleCategoriaMenu = $(this).html();
                        break;
                    case 4: // 
                        var row = $(this);
                        if (row.find('input[type="number"]').val()) {
                            CostoAdicionalVenta = row.find('input[type="number"]').val();
                        }

                        detalleComposicionMenu.push({
                            'id_detalle_categoria_menu': idDetalleCategoriaMenu,
                            'costo_adicional_venta': CostoAdicionalVenta,
                        });
                        break;
                }
            });
        });

        console.log(detalleComposicionMenu);
        $.post(base_url + "index.php/Productos/actualizarCostoAdicionalVentaCategoriaMenu", {
                detalle: detalleComposicionMenu
            }, function() {}).done(function(datos) {
                console.log(datos);
                swal("Listo", "Se Actualizo Exitosamente", "success");
            })
            .fail(function() {
                alert("Se produjo un error al realizar el proceso, favor contacte a su administrador");
            })
            .always(function() {});
    }

    $('#BtnActualizarDetalleMenu').on('click', function() {
        actualizarCostoRecetaCategoriaMenu();
        actualizarCostoAdicionalVentaCategoriaMenu();
        actualizarConfiguracionCategoriaMenu();
    });

    function GrabarDetalleCategoriaMenu() {
        var msg = "";
        if ($('#cboMenuCategoria').val() == 0) {
            msg = "Categoria A La Cual Aignar Productos\n";
        }
        if ($('#cboListadoProductosParaCategoria').val() == 0) {
            msg = "Productos A Agregar\n";
        }
        if (msg.length) {
            swal("Debe Ingresar Los Siguiente(s) dato(s):\n" + msg);
        } else {
            //grabar datos
            IdDetalleCategoriaMenu = $('#IdDetalleCategoria').val();
            IdCategoriaMenu = $('#cboMenuCategoria').val();
            IdProducto = $('#cboListadoProductosParaCategoria').val();
            CostoCategoria = $('#CostoCategoriaMenu').val();
            CostoAdicional = $('#AdicionalCategoriaMenu').val();
            if ($('#ckPermitirCambio').is(":checked")) {
                var permiteCambio = 1
            } else {
                var permiteCambio = 0
            }

            if ($('#ckImprimirProductoCategoria').is(":checked")) {
                var imprimirProductosCategoria = 1
            } else {
                var imprimirProductosCategoria = 0
            }


            var miJson = {
                'id_detalle_categoria_menu': IdDetalleCategoriaMenu,
                'id_categoria_menu': IdCategoriaMenu,
                'id_producto': IdProducto,
                'costo_receta': CostoCategoria,
                'costo_adicional_venta': CostoAdicional,
                'permite_cambio': permiteCambio,
                'imprimir': imprimirProductosCategoria,
            };
        };
        $.post(base_url + "index.php/Productos/grabarDetalleCategoria", {
                'prod': miJson
            }).done(function() {
                id_categoria_menu = $('#id_categoria_menu').val();
                obtenerProductosQueConformanElMenu(id_categoria_menu);
                swal("Listo", "Se registro Exitosamente", "success");
            })
            .fail(function() {
                swal("Se produjo un error al realizar el proceso, favor contacte a su administrador");
            })
            .always(function() {});
    }

    function ObtenerConfiguracionesCategoriaMenu(id_categoria_menu) {
        $.post(base_url + "index.php/Productos/ObtenerConfiguracionesCategoriaMenu", {
                id_categoria_menu: id_categoria_menu
            }, function(datos) {
                var obj = $.parseJSON(datos);
                $.each(obj, function(index, v) {
                    $('#CantMinima').val(v.cantidad_minima);
                    $('#CantMaxima').val(v.cantidad_maxima);
                    if (v.estricto == 't') {
                        $("#ckEstricto").prop("checked", true);
                    } else {
                        $("#ckEstricto").prop("checked", false);
                    }
                    $('#CostoPromedio').val(v.costo_promedio_receta);


                });
            }).done(function() {})
            .fail(function() {
                alert("Se produjo un error al realizar la consulta");
            })
            .always(function() {});
    }

    function actualizarConfiguracionCategoriaMenu() {
        var detalleConfiguracionMenu = [];
        $("#tableDetalleCategoria tbody tr").each(function(index) {
            $(this).children("td").each(function(index2) {
                CantidadMinima = $('#CantMinima').val();
                CantidadMaxima = $('#CantMaxima').val();
                CostoPromedio = $('#CostoPromedio').val();
                if ($('#ckEstricto').is(":checked")) {
                    var esEstricto = 1
                } else {
                    var esEstricto = 0
                }
                switch (index2) {
                    case 1:
                        idCategoriaMenu = $(this).html();


                        detalleConfiguracionMenu.push({
                            'id_categoria_menu': idCategoriaMenu,
                            'cantidad_minima': CantidadMinima,
                            'cantidad_maxima': CantidadMaxima,
                            'costo_promedio_receta': CostoPromedio,
                            'estricto': esEstricto,
                        });
                        break;
                }
            });
        });

        console.log(detalleConfiguracionMenu);
        $.post(base_url + "index.php/Productos/actualizarConfiguracionCategoriaMenu", {
                detalle: detalleConfiguracionMenu
            }, function() {}).done(function(datos) {
                console.log(datos);
                swal("Listo", "Se Actualizo Exitosamente", "success");
            })
            .fail(function() {
                alert("Se produjo un error al realizar el proceso, favor contacte a su administrador");
            })
            .always(function() {});
    }
</script>