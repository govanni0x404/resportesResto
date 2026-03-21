<script>
    var base_url = "<?php echo base_url(); ?>";
    tableListadoMenu = $('#tableListadoMenu').DataTable();
//     tableCategoriasMenuModal = $('#tableCategoriasMenuModal').DataTable();
    $(document).ready(function() {
        ListadoDeMenu(); // Esta llama a la de funciones.php para el SELECT
        ListadoDeMenuTable(); // Esta llama a la de abajo para la TABLA
        
        // Listener para el selector de productos en composición
        $('#cboSelectorProductosFinal').on('change', function() {
            let idProd = $(this).val();
            if(idProd && idProd != "0") {
                if(typeof productosCarta !== 'undefined' && productosCarta[idProd]) {
                    let info = productosCarta[idProd];
                    let costo = info.pponderado || info.precio_ucompra || 0;
                    $('#CostoCategoriaMenu').val(costo);
                    $('#AdicionalCategoriaMenu').val(0); // Reset adicional por seguridad
                    if(costo > 0) {
                        $('#labelRecetaProducto').text("CON COSTO");
                    } else {
                        $('#labelRecetaProducto').text("SIN COSTO");
                    }
                }
            } else {
                // Limpiar todo si selecciona "Seleccione el producto"
                $('#CostoCategoriaMenu').val(0);
                $('#AdicionalCategoriaMenu').val(0);
                $('#labelRecetaProducto').text("SIN RECETA");
            }
        });
    });

    function QuitarMenuDeProducto() {
        $('#cboMenu').val(0).trigger('change');
    }

    function AbrirModalListadoMenu() {
        let idMenuSeleccionado = $('#cboMenu').val();
        ListadoDeMenuModal(idMenuSeleccionado);
        $('#ModalListadoMenus').modal({
            backdrop: 'static',
            keyboard: false
        });
        
        if(idMenuSeleccionado && idMenuSeleccionado != "0") {
            setTimeout(function() {
                $('#cboMenuModal').val(idMenuSeleccionado).trigger('change');
            }, 500);
        }
    }

    $('#cboMenuModal').on('change', function() {
        let id_menu = $(this).val();
        if(id_menu && id_menu != "0") {
            ListadoCategoriaMenuModal(id_menu);
            CalcularCostosMenu(id_menu);
        } else {
            $('#bodyCategoriasMenuModal').html("");
            $('#txtCostoTotalMenu').val(0);
            $('#txtPorcentajeCostoMenu').val(0);
        }
    });

    function ListadoDeMenuModal(objSelected) {
        $.post(base_url + "index.php/Productos/ListadoDeMenu", {
            id: ""
        }, function(datos) {
            var obj = jQuery.parseJSON(datos);
            var actualModal = $('#cboMenuModal').val();
            var actualCat = $('#cboMenusModalCategorias').val();
            
            var sel = objSelected || actualModal || 0;
            
            tbl = '<option disabled value="0">Seleccione El Menu</option>';
            if (!sel || sel == "0") {
                tbl = '<option selected disabled value="0">Seleccione El Menu</option>';
            }

            $.each(obj, function(index, v) {
                if (v.id_menu == sel) {
                    tbl += '<option selected value="' + v.id_menu + '">' + v.nombre_menu + '</option>';
                } else {
                    tbl += '<option value="' + v.id_menu + '">' + v.nombre_menu + '</option>';
                }
            });
            $("#cboMenuModal").html(tbl);
            $("#cboMenuModal").select2();
            
            // Para el modal de categorías
            var selCat = objSelected || actualCat || 0;
            var tblCat = '<option disabled value="0">Seleccione El Menu</option>';
            if (!selCat || selCat == "0") {
                tblCat = '<option selected disabled value="0">Seleccione El Menu</option>';
            }
            $.each(obj, function(index, v) {
                if (v.id_menu == selCat) {
                    tblCat += '<option selected value="' + v.id_menu + '">' + v.nombre_menu + '</option>';
                } else {
                    tblCat += '<option value="' + v.id_menu + '">' + v.nombre_menu + '</option>';
                }
            });
            $("#cboMenusModalCategorias").html(tblCat);
            $("#cboMenusModalCategorias").select2();
            
            // Si hay un menú seleccionado, cargar sus categorías automáticamente
            if (selCat && selCat != "0") {
                ListadoCategoriasAbajo(selCat);
            }
        });
    }

    function CalcularCostosMenu(id_menu) {
        // Obtenemos los costos totales y precio de venta para el pie del modal
        $.post(base_url + "index.php/Productos/obtenerCategoriaMenu", {
            'id_menu': id_menu, 
        }, function(datos) {
            var obj = $.parseJSON(datos);
            var costoTotal = 0;
            $.each(obj.aaData, function(index, v) {
                costoTotal += parseFloat(v.costo_promedio_receta || 0);
            });
            $('#txtCostoTotalMenu').val(costoTotal);
            
            // Calcular % de costo
            let precioVenta = 0;
            $("#detallePrecioVenta input").each(function() {
                if($(this).val() > 0) {
                    precioVenta = $(this).val();
                    return false; // break
                }
            });
            $('#txtPrecioVentaMenu').val(precioVenta);
            
            if(precioVenta > 0) {
                let porcentaje = (costoTotal / precioVenta) * 100;
                $('#txtPorcentajeCostoMenu').val(porcentaje.toFixed(2) + "%");
            } else {
                $('#txtPorcentajeCostoMenu').val("0%");
            }
        });
    }

    function AbrirModalNuevoMenu() {
        $('#txtIdMenu').val("");
        $('#txtNombreMenu').val("");
        $('#ckVigenciaMenu').prop('checked', true);
        ListadoMenuAbajo();
        $('#ModalNuevoMenu').modal({
            backdrop: 'static',
            keyboard: false
        });
    }

    var menusCargados = {};
    function ListadoMenuAbajo() {
        $.post(base_url + "index.php/Productos/obtenerListadoMenu", {}, function(datos) {
            var obj = $.parseJSON(datos);
            var tbl = '';
            menusCargados = {};
            $.each(obj.aaData, function(index, v) {
                menusCargados[v.id_menu] = v;
                tbl += "<tr style='cursor:pointer' onclick='CargarMenuParaEditar(" + v.id_menu + ")'>";
                tbl += "<td>" + v.id_menu + "</td>";
                tbl += "<td>" + v.nombre_menu + "</td>";
                tbl += "</tr>";
            });
            $("#bodyMenuAbajo").html(tbl);
        });
    }

    function CargarMenuParaEditar(id) {
        let v = menusCargados[id];
        if(!v) return;
        $('#txtIdMenu').val(v.id_menu);
        $('#txtNombreMenu').val(v.nombre_menu);
        $('#ckVigenciaMenu').prop('checked', v.vigente == 'SI');
    }

    function ListadoDeMenuTable() {
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
            "dom": "Brtip",
            "ordering": true,
            "filter": false,
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
        if ($('#txtNombreMenu').val() === '') {
            showAppAlert('error',"- El Nombre del menu, no puede quedar en Blanco", {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
            return;
        }
        else {
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
            }).done(function(response) {
                var res = JSON.parse(response);
                var newId = res[0].id_menu;
                
                ListadoMenuAbajo();
                if (typeof tableListadoMenu !== 'undefined' && tableListadoMenu.ajax) {
                    tableListadoMenu.ajax.reload();
                }
                
                // Recargar combos y seleccionar el nuevo
                ListadoDeMenu(newId); 
                ListadoDeMenuModal(newId);
                
                showAppAlert('success', "Se registro Exitosamente El Menu!", {
                    duration: 4000,
                    image: base_url + 'assets/img/success.gif'
                });
            })
            .fail(function() {
                showAppAlert('error',"- Se produjo un error al realizar el proceso, favor contacte a su administrador", {
                    duration: 5000,
                    image: base_url+ 'assets/img/errorGif.gif'
                });
            });
    }

    //CATEGORIA MENU 

    function AbrirModalListadoCategoriasMenu(id_menu) {
        ListadoCategoriaMenuModal(id_menu);
        $('#ModalListadoCategoriasMenu').modal({
            backdrop: 'static',
            keyboard: false
        });
    }

    function AbrirModalNuevaCategoriasMenu() {
        $('#IdCategoria').val("");
        $('#txtNombreCategoria').val("");
        $('#ckVigenciaCategoria').prop('checked', true);
        
        let idMenuSeleccionado = $('#cboMenuModal').val();
        ListadoDeMenuModal(idMenuSeleccionado); // Llena cboMenusModalCategorias y mantiene selección
        
        $('#ModalNuevaCategoria').modal({
            backdrop: 'static',
            keyboard: false
        });
    }

    $('#cboMenusModalCategorias').on('change', function() {
        let id_menu = $(this).val();
        if(id_menu && id_menu != "0") {
            ListadoCategoriasAbajo(id_menu);
        } else {
            $('#bodyCategoriasAbajo').html("");
        }
    });

    var categoriasCargadas = {};
    function ListadoCategoriasAbajo(id_menu) {
        $.post(base_url + "index.php/Productos/obtenerCategoriaMenu", {
            'id_menu': id_menu
        }, function(datos) {
            var obj = $.parseJSON(datos);
            var tbl = '';
            categoriasCargadas = {};
            $.each(obj.aaData, function(index, v) {
                categoriasCargadas[v.id_categoria_menu] = v;
                tbl += "<tr style='cursor:pointer' onclick='CargarCategoriaParaEditar(" + v.id_categoria_menu + ")'>";
                tbl += "<td>" + v.id_categoria_menu + "</td>";
                tbl += "<td>" + v.nombre_categoria_menu + "</td>";
                tbl += "<td>" + v.vigente + "</td>";
                tbl += "</tr>";
            });
            $("#bodyCategoriasAbajo").html(tbl);
        });
    }

    function CargarCategoriaParaEditar(id) {
        let v = categoriasCargadas[id];
        if(!v) return;
        $('#IdCategoria').val(v.id_categoria_menu);
        $('#txtNombreCategoria').val(v.nombre_categoria_menu);
        $('#ckVigenciaCategoria').prop('checked', v.vigente == 'SI');
        $('#cboMenusModalCategorias').val(v.id_menu).trigger('change');
    }

    function ListadoCategoriaMenuModal(id_menu) {
        let tituloExport = "CATEGORIAS";
        
        // Guardar el id_menu en un input hidden o variable global
        $('#current_id_menu').val(id_menu); // Agregar un input hidden en tu modal
        
        if ($.fn.dataTable.isDataTable('#tableCategoriasMenuModal')) {
            tableCategoriasMenuModal = $('#tableCategoriasMenuModal').DataTable();
            tableCategoriasMenuModal.destroy();
        }
        tableCategoriasMenuModal = $("#tableCategoriasMenuModal").DataTable({
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
            "dom": "Brtip",
            "ordering": true,
            "filter": false,
            "info": false,
            "footer": false,
            "responsive": {
                details: {
                    type: 'column',
                    target: -1
                }
            },
            "columns": [
                {mData: "id_categoria_menu", className: "text-left"},
                {mData: "id_menu", className: "text-left", visible: false},
                {mData: "nombre_categoria_menu", className: "text-center"},
                {mData: "costo_promedio_receta", className: "text-center"},
                {mData: "btnAgregarProductosCategoria", className: "text-center"},
            ],
            'language': {
                url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json",
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
        if (!$('#cboMenusModalCategorias').val() || $('#cboMenusModalCategorias').val() === "0") {
            showAppAlert('error',"- Debe Seleccionar un Menu", {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
            return;
        }
        if ($('#txtNombreCategoria').val() === "") {
            showAppAlert('error',"- Falta Ingresar el Nombre de la Categoria", {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
            return;
        } else {
            IdCategoria = $('#IdCategoria').val();
            IdMenu = $('#cboMenusModalCategorias').val();
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
                ListadoCategoriasAbajo(IdMenu);
                
                // Recargar listado en el modal principal y asegurar que el menú siga seleccionado
                ListadoDeMenuModal(IdMenu);
                ListadoCategoriaMenuModal(IdMenu);
                
                showAppAlert('success', "Se registro Exitosamente La Categoria!", {
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



    function AbrirComposicionDesdeListado() {
        // Encontrar la fila seleccionada en la tabla tableCategoriasMenuModal
        let selectedRow = $("#tableCategoriasMenuModal tbody tr.selected");
        if (selectedRow.length == 0) {
            alert("Por favor, seleccione una categoría de la tabla primero (haga clic en una fila)");
            return;
        }
        
        // Usar DataTables API para obtener el ID real desde la data de la fila (aunque sea oculta)
        let table = $('#tableCategoriasMenuModal').DataTable();
        let data = table.row(selectedRow).data();
        let id_categoria_menu = data ? data.id_categoria_menu : null;
        
        if(!id_categoria_menu) {
            alert("No se pudo obtener el ID de la categoría seleccionada");
            return;
        }
        
        AbrirModalProductosAgregadosACategoria(id_categoria_menu);
    }

    // Listener para seleccionar filas en la tabla de categorías del listado
    $(document).on('click', '#tableCategoriasMenuModal tbody tr', function() {
        $("#tableCategoriasMenuModal tbody tr").removeClass('selected').css('background-color', '');
        $(this).addClass('selected').css('background-color', '#E8F5E9');
    });

    //PRODUCTOS AGREGADOS A LA CATEGORIA
    function AbrirModalProductosAgregadosACategoria(id_categoria_menu) {
        console.log("Abriendo modal... ID Categoria:", id_categoria_menu);
        // Encontrar datos de la categoría en el objeto de caché si existe
        let catData = categoriasCargadas[id_categoria_menu];
        let menuId = $('#cboMenuModal').val();
        let menuNombre = $("#cboMenuModal option:selected").text();
        let catNombre = catData ? catData.nombre_categoria_menu : "Categoría";
        
        // Actualizar etiqueta destacada (Número 1 en Imagen 2)
        $("#labelMenuCategoriaDestacado").text(menuNombre + " -> " + catNombre);
        $("#labelMenuCategoriaDestacado").attr('data-id-categoria', id_categoria_menu);
        
        NuevoDetalleProducto(); // Limpiar campos iniciales
        
        console.log("Cargando lista de productos para el combo...");
        ListadoDeAgregadosParaElProducto(null, "#cboSelectorProductosFinal"); // Esta función llena el combo de productos
        
        obtenerProductosQueConformanElMenu(id_categoria_menu);
        ObtenerConfiguracionesCategoriaMenu(id_categoria_menu);
        
        $('#ModalAgregarProductosAcategorias').modal({
            backdrop: 'static',
            keyboard: false
        });
    }

    function NuevoDetalleProducto() {
        $('#IdDetalleCategoria').val("");
        $('#cboSelectorProductosFinal').val(0).trigger('change');
        $('#CostoCategoriaMenu').val("0");
        $('#AdicionalCategoriaMenu').val("0");
        $('#labelRecetaProducto').text("SIN RECETA");
        $("#tableDetalleCategoria tbody tr").removeClass('selected');
    }
    function obtenerProductosQueConformanElMenu(id_categoria_menu) {
        $.post(base_url + "index.php/Productos/obtenerProductosQueConformanElMenu", {
            id_categoria_menu: id_categoria_menu
        }, function(datos) {
            var obj = $.parseJSON(datos);
            var tbl = '';
            var totalCostoReceta = 0;
            var cantItems = 0;
            
            $.each(obj, function(index, v) {
                let idDetalle = v.id_detalle_categoria_menu;
                // Guardar datos en caché para edición fácil
                detallesCargados[idDetalle] = v;
                
                tbl += "<tr onclick='CargarDetalleParaEditar(" + idDetalle + ")' style='cursor:pointer'>";
                tbl += "<td>" + idDetalle + "</td>";
                tbl += "<td>" + v.nombre_producto + "</td>";
                tbl += "<td class='text-right'>" + v.costo_receta + "</td>";
                tbl += "<td class='text-right'>" + v.costo_adicional_venta + "</td>";
                tbl += "<td class='text-center'>" + (v.permite_cambio == 't' ? 'SÍ' : 'NO') + "</td>";
                tbl += "<td class='text-center'>" + (v.imprimir == 't' ? 'SÍ' : 'NO') + "</td>";
                tbl += "</tr>";
                
                totalCostoReceta += parseFloat(v.costo_receta) || 0;
                cantItems++;
            });
            
            $("#DetalleCategoria").html(tbl);
            $("#lblCantProductosCategoria").text(cantItems);
            
            // Si hay productos, calcular promedio para mostrar (informativo)
            if(cantItems > 0) {
                let promedio = totalCostoReceta / cantItems;
                // $("#lblCostoPromedioCategoria").text(promedio.toFixed(0)); // Esto lo llena ObtenerConfiguracionesCategoriaMenu
            }
        });
    }

    var detallesCargados = {};

    function CargarDetalleParaEditar(idDetalle) {
        let v = detallesCargados[idDetalle];
        if(!v) return;
        
        // Resaltado visual
        $("#tableDetalleCategoria tbody tr").removeClass('selected');
        // Encontrar la fila que corresponde a este idDetalle
        $("#tableDetalleCategoria tbody tr").each(function() {
            if($(this).find('td:eq(0)').text() == idDetalle) {
                $(this).addClass('selected');
            }
        });

        // Asegurar que exista la opción antes de hacer set (Select2 workaround)
        if ($('#cboSelectorProductosFinal').find("option[value='" + v.id_producto + "']").length) {
            $('#cboSelectorProductosFinal').val(v.id_producto).trigger('change');
        } else {
            var newOption = new Option(v.nombre_producto, v.id_producto, true, true);
            $('#cboSelectorProductosFinal').append(newOption).trigger('change');
        }

        $('#IdDetalleCategoria').val(v.id_detalle_categoria_menu);
        $('#CostoCategoriaMenu').val(v.costo_receta);
        $('#AdicionalCategoriaMenu').val(v.costo_adicional_venta);
        $('#labelRecetaProducto').text(v.costo_receta > 0 ? "CON RECETA" : "SIN RECETA");
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
                showAppAlert('success', "Se Actualizo Exitosamente!", {
                    duration: 4000,
                    image: base_url + 'assets/img/success.gif'
                });
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
                showAppAlert('success', "Se Actualizo Exitosamente!", {
                    duration: 4000,
                    image: base_url + 'assets/img/success.gif'
                });
            })
            .fail(function() {
                alert("Se produjo un error al realizar el proceso, favor contacte a su administrador");
            })
            .always(function() {});
    }

    function GrabarDetalleCategoriaMenu() {
        let id_categoria_menu = $("#labelMenuCategoriaDestacado").attr('data-id-categoria');
        let id_producto = $('#cboSelectorProductosFinal').val();
        let costo_receta = $('#CostoCategoriaMenu').val();
        let costo_adicional_venta = $('#AdicionalCategoriaMenu').val();
        let id_detalle = $('#IdDetalleCategoria').val();
        
        if(!id_producto || id_producto == "0") {
             showAppAlert('error', "Debe seleccionar un producto", {
                duration: 4000,
                image: base_url + 'assets/img/errorGif.gif'
            });
            return;
        }

        var miJson = {
            'id_detalle_categoria_menu': id_detalle || 0,
            'id_categoria_menu': id_categoria_menu,
            'id_producto': id_producto,
            'costo_receta': costo_receta,
            'costo_adicional_venta': costo_adicional_venta,
            'permite_cambio': true,
            'imprimir': true
        };

        $.post(base_url + "index.php/Productos/grabarDetalleCategoria", {
            prod: miJson
        }, function(datos) {
            showAppAlert('success', "Asociación guardada correctamente", {
                duration: 4000,
                image: base_url + 'assets/img/success.gif'
            });
            obtenerProductosQueConformanElMenu(id_categoria_menu);
            NuevoDetalleProducto();
        });
    }

    function QuitarProductoDeCategoria() {
        let id_detalle = $('#IdDetalleCategoria').val();
        let id_categoria_menu = $("#labelMenuCategoriaDestacado").attr('data-id-categoria');
        
        console.log("Intentando quitar DetalleID:", id_detalle, "de CategoriaID:", id_categoria_menu);

        if(!id_detalle || id_detalle == "" || id_detalle == "0") {
            showAppAlert('error', "Por favor, seleccione un producto de la tabla haciendo clic sobre él.", {
                duration: 4000
            });
            return;
        }

            $.post(base_url + "index.php/Productos/DeshabilitarDetalleCategoria", {
                id: id_detalle
            }, function(datos) {
                console.log("Respuesta del servidor al quitar:", datos);
                
                // Alerta para que el usuario vea el diagnóstico sin consola
                if(typeof datos === 'string') {
                    try { datos = JSON.parse(datos); } catch(e) {}
                }
                
                if (datos.affected == 0) {
                   alert("ERROR DE ELIMINACIÓN:\nFilas afectadas: " + datos.affected + "\nSQL: " + datos.query + "\nID enviado: " + datos.id + "\n\nPor favor, envíame una foto de esta alerta.");
                } else {
                    showAppAlert('warning', "Producto quitado de la categoría", {
                        duration: 4000,
                        image: base_url + 'assets/img/success.gif'
                    });
                }
                
                if(id_categoria_menu) {
                    obtenerProductosQueConformanElMenu(id_categoria_menu);
                } else {
                    console.error("Error: No se pudo recargar la tabla porque id_categoria_menu es nulo");
                }
                NuevoDetalleProducto();
            });
        }

    function ObtenerConfiguracionesCategoriaMenu(id_categoria_menu) {
        $.post(base_url + "index.php/Productos/ObtenerConfiguracionesCategoriaMenu", {
            id_categoria_menu: id_categoria_menu
        }, function(datos) {
            var obj = JSON.parse(datos);
            if(obj && obj.length > 0) {
                $('#CantMinima').val(obj[0].cantidad_minima || 0);
                $('#CantMaxima').val(obj[0].cantidad_maxima || 5);
                $('#ckEstricto').prop('checked', obj[0].estricto == 't' || obj[0].estricto == true);
                $('#lblCostoPromedioCategoria').text(obj[0].costo_promedio_receta || 0);
            }
        });
    }

    function GrabarConfiguracionCategoriaMenu() {
        let id_categoria_menu = $("#labelMenuCategoriaDestacado").attr('data-id-categoria');
        let detalle = [{
            'id_categoria_menu': id_categoria_menu,
            'cantidad_minima': $('#CantMinima').val(),
            'cantidad_maxima': $('#CantMaxima').val(),
            'estricto': $('#ckEstricto').is(':checked'),
            'costo_promedio_receta': $('#lblCostoPromedioCategoria').text()
        }];

        $.post(base_url + "index.php/Productos/actualizarConfiguracionCategoriaMenu", {
            detalle: detalle
        }, function(datos) {
            showAppAlert('success', "Configuración de categoría guardada", {
                duration: 4000,
                image: base_url + 'assets/img/success.gif'
            });
            // Recargar el total en el modal principal si es necesario
            ListadoDeMenuModal($('#cboMenuModal').val());
        });
    }
</script>