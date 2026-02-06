<script>
    var base_url = "<?php echo base_url(); ?>";
    $(document).ready(function() {
        cargarConfiguracionesRestoWeb();
        ValidacionCheckedTodosLosMontos();
        ValidacionCheckedMontoTotal();
    });

    function GrabarConfiguraciones() {
        swal({
                title: "Confirmacion!",
                text: "Esta segur@ De Guardar Las Configuraciones",
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
                    if ($('#ckDesglozarTodosLosMontosEnReportes').is(":checked")) {
                        var desglozarTodosLosMontosEnReportes = 1
                    } else {
                        var desglozarTodosLosMontosEnReportes = 0
                    }

                    if ($('#ckDesglozarSoloMontoTotalEnReportes').is(":checked")) {
                        var desglozarSoloMontoTotalEnReportes = 1
                    } else {
                        var desglozarSoloMontoTotalEnReportes = 0
                    }

                    var miJson = {
                        'desglozar_monto_neto_iva_total_en_reportes': desglozarTodosLosMontosEnReportes,
                        'desglozar_solo_monto_total_en_reportes': desglozarSoloMontoTotalEnReportes,
                    };
                    $.post(base_url + "index.php/Configuraciones/grabarConfiguraciones", {
                        'prod': miJson
                    }, function() {}).done(function(datos) {
                        let positionError = datos.search("ERROR:");
                        if (parseFloat(positionError) > 0) {
                            swal("Error", "Se produjo un error, favor reintente \n(" + e + ")\n" + datos.substr(positionError, 150), "error");
                        } else {
                            swal("Configuraciones Actualizadas!", "Se Actualizaron Las Configuraciones!", "success");

                        }
                    }).fail(function() {
                        swal("Se produjo un error al realizar La Actualizacion, favor vuelva a intentarlo");
                    });
                    swal.close();
                } else {
                    swal.close();
                }
            });
    }

    $('#btnGrabarConfiguraciones').click(function() {
        GrabarConfiguraciones();
    })

    function ValidacionCheckedTodosLosMontos() {
        if ($('#ckDesglozarTodosLosMontosEnReportes').prop('checked') == true) {
            $('#ckDesglozarSoloMontoTotalEnReportes').prop('disabled', true);
            $('#ckDesglozarSoloMontoTotalEnReportes').prop('checked', false);
        } else {
            $('#ckDesglozarSoloMontoTotalEnReportes').prop('disabled', false);
            $('#ckDesglozarSoloMontoTotalEnReportes').prop('checked', true);
        }
    }
    $('#ckDesglozarTodosLosMontosEnReportes').click(function() {
        ValidacionCheckedTodosLosMontos();
    })

    function ValidacionCheckedMontoTotal() {
        if ($('#ckDesglozarSoloMontoTotalEnReportes').prop('checked') == true) {
            $('#ckDesglozarTodosLosMontosEnReportes').prop('disabled', true);
            $('#ckDesglozarTodosLosMontosEnReportes').prop('checked', false);

        } else {
            $('#ckDesglozarTodosLosMontosEnReportes').prop('disabled', false);
            $('#ckDesglozarTodosLosMontosEnReportes').prop('checked', true);

        }
    }
    $('#ckDesglozarSoloMontoTotalEnReportes').click(function() {
        ValidacionCheckedMontoTotal();
    })

    function cargarConfiguracionesRestoWeb() {
        $.post(base_url + "index.php/Configuraciones/obtenerConfiguracionesRestoWeb", {}, function(datos) {
                var obj = $.parseJSON(datos);
                $.each(obj, function(index, v) {
                    if (v.desglozar_monto_neto_iva_total_en_reportes == 't') {
                        $("#ckDesglozarTodosLosMontosEnReportes").prop("checked", true);
                    } else {
                        $("#ckDesglozarTodosLosMontosEnReportes").prop("checked", false);
                    }

                    if (v.desglozar_solo_monto_total_en_reportes == 't') {
                        $("#ckDesglozarSoloMontoTotalEnReportes").prop("checked", true);
                    } else {
                        $("#ckDesglozarSoloMontoTotalEnReportes").prop("checked", false);
                    }
                });
            }).done(function() {})
            .fail(function() {
                alert("Se produjo un error al realizar la consulta");
            })
            .always(function() {});
    }
</script>