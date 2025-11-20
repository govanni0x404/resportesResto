<script>
    //NEW FAMILIA//////////
    function crearNuevaFamilia() {
        $('#idFamilia').val("");
        $('#nombreFamilia').val("");
        $('#AliasFamilia').val("");
        $('#cboDestinoDeImpresionFamilia').val("");
        $('#ckVentaDirectaFamilia').prop('checked', true);
        $('#ordenFamilia').val("");
        $('#ckImprimirEnDetallePedido').prop('checked', false);
        ListadoDestinoDeImpresionFamilia();
        $('#ModalNuevaFamilia').modal({
            backdrop: 'static',
            keyboard: false
        });
    }

    function GrabarNuevaFamilia() {
        var msg = "";
        if ($('#nombreFamilia').val() == 0) {
            msg = "Nombre Familia\n";
        }
        if ($('#AliasFamilia').val().trim() === '') {
            $('#AliasFamilia').val($('#nombreFamilia').val().trim());
        }
        var destinoImpresion = $('#cboDestinoDeImpresionFamilia').val();
        if (!destinoImpresion || destinoImpresion == '' || destinoImpresion == '0' || destinoImpresion == 0) {
            msg += "Destino De Impresion De Familia\n";
        }
        if (msg.length) {
            showAppAlert('error',"- Falta Ingresar Los Siguiente(s) dato(s):\n" + msg, {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
        } else {
            //grabar datos
            IdFamilia = $('#idFamilia').val();
            NombreFamilia = $('#nombreFamilia').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
            AliasFamilia = $('#AliasFamilia').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
            IdDimpresion = $('#cboDestinoDeImpresionFamilia').val();
            if ($('#ckVentaDirectaFamilia').is(":checked")) {
                var VentaDirectaFamilia = 1
            } else {
                var VentaDirectaFamilia = 0
            }

            var OrdenFamiliaRaw = $('#ordenFamilia').val();
            var OrdenFamilia = (OrdenFamiliaRaw === '' || OrdenFamiliaRaw === null) ? 0: parseInt(OrdenFamiliaRaw);

            if ($('#ckImprimirEnDetallePedido').is(":checked")) {
                var ImprimirEnDetallePedido = 1
            } else {
                var ImprimirEnDetallePedido = 0
            }


            var miJson = {
                'id_familia': IdFamilia,
                'nombre_familia': NombreFamilia,
                'venta_directa': VentaDirectaFamilia,
                'id_dimpresion': IdDimpresion,
                'orden_familia': OrdenFamilia,
                'imprimir_en_detalle_pedido': ImprimirEnDetallePedido,
                'alias': AliasFamilia,
            };
            $.post(base_url + "index.php/Productos/grabarNuevaFamilia", {
                    'prod': miJson
                }).done(function() {
                    $('#ModalNuevaFamilia').modal('hide');
                    //tableResumenListaPrecios.ajax.reload();
                    ListadoDeFamiliasMantenedor("GENERAL");
                    showAppAlert('success', "Se registro Exitosamente La Nueva Familia", {
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

    //NE SUBFAMILIA////////////////////
    function crearNuevaSubFamilia() {
        $('#idSubFamilia').val("");
        $('#nombreSubFamilia').val("");
        ListadoDeFamiliascboMsubFamilia();
        $('#ModalNuevaSubFamilia').modal({
            backdrop: 'static',
            keyboard: false
        });
    }

    function GrabarNuevaSubFamilia() {
        var msg = "";
        if ($('#nombreSubFamilia').val() == 0) {
            msg = "Nombre Sub Familia\n";
        }
        var nombreFamiliaa = $('#cboFamiliaMsubFamilia').val();
        if (!nombreFamiliaa || nombreFamiliaa == '' || nombreFamiliaa == '0' || nombreFamiliaa == 0) {
            msg = "Familia\n";
        }
        if (msg.length) {
            showAppAlert('error',"- Falta Ingresar Los Siguiente(s) dato(s):\n" + msg, {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
            
        } else {
            //grabar datos
            IdSubFamilia = $('#idSubFamilia').val();
            NombreSubFamilia = $('#nombreSubFamilia').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
            Familia = $('#cboFamiliaMsubFamilia').val();

            var miJson = {
                'id_subfamilia': IdSubFamilia,
                'nombre_subfamilia': NombreSubFamilia,
                'id_familia': Familia
            };
            $.post(base_url + "index.php/Productos/grabarNuevaSubFamilia", {
                    'prod': miJson
                }).done(function() {
                    $('#ModalNuevaSubFamilia').modal('hide');
                    //tableResumenListaPrecios.ajax.reload();
                    ListadoDeSubFamilias("");
                    showAppAlert('success', "Se registro Exitosamente La Nueva Sub Familia", {
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

    //NEW BODEGA///////////////////////
    function crearNuevaBodega() {
        $('#idBodega').val("");
        $('#nombreBodega').val("");
        $('#ModalNuevaBodega').modal({
            backdrop: 'static',
            keyboard: false
        });
    }

    function GrabarNuevaBodega() {
        var msg = "";
        if ($('#nombreBodega').val() == 0) {
            msg = "Nombre Bodega\n";
        }
        if (msg.length) {
            showAppAlert('error',"- Falta Ingresar Los Siguiente(s) dato(s):\n" + msg, {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
        } else {
            //grabar datos
            IdBodega = $('#idBodega').val();
            NombreBodega = $('#nombreBodega').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
            var miJson = {
                'id_bodega': IdBodega,
                'nombre_bodega': NombreBodega
            };
            $.post(base_url + "index.php/Productos/grabarNuevaBodega", {
                    'prod': miJson
                }).done(function() {
                    $('#ModalNuevaBodega').modal('hide');
                    //tableResumenListaPrecios.ajax.reload();
                    ListadoDeBodega("BODEGA PRINCIPAL");
                    showAppAlert('success', "Se registro Exitosamente La Nueva Bodega", {
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
</script>