<script>
    var base_url = "<?php echo base_url(); ?>";

    function cargarCombosComuna() {
        $.ajax({
            url: base_url + "index.php/Comuna/ListarComunas",
            type: "POST",
            data: { id: "" },
            dataType: "json"
        }).done(function(obj) {
            console.log("Comunas recibidas:", obj);
            var opciones = '<option value="0">Seleccione la comuna</option>';
            if (!obj || obj.length === 0) {
                console.warn("El servidor no devolvió comunas. Verifica que la tabla 'comuna' tenga datos en esta base.");
            }
            $.each(obj, function(index, v) {
                opciones += '<option value="' + v.id_comuna + '">' + v.nombre_comuna + '</option>';
            });

            $('#cboIdComuna, #cboIdComunaSucursal, #cboIdComunaSii').each(function() {
                var $select = $(this);
                var seleccionado = $select.data('selected');
                $select.html(opciones);
                if (seleccionado) {
                    $select.val(String(seleccionado));
                }
                $select.select2();
            });
        }).fail(function(xhr, status, error) {
            console.error("Error al cargar comunas:", status, error, xhr.responseText);
        });
    }

    function toggleMostrarClaveAdmin() {
        var input = document.getElementById('txtclaveAdmin');
        var chk = document.getElementById('chkMostrarClaveAdmin');
        if (!input || !chk) return;
        input.type = chk.checked ? 'text' : 'password';
    }

    function openTab(evt, tabName) {
        var i, tabcontent, tablinks;
        tabcontent = document.getElementsByClassName("tab");
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].className = tabcontent[i].className.replace(" active", "");
        }
        tablinks = document.getElementsByClassName("tablink");
        for (i = 0; i < tablinks.length; i++) {
            tablinks[i].className = tablinks[i].className.replace(" active", "");
        }
        document.getElementById(tabName).className += " active";
        evt.currentTarget.className += " active";
    }

    // ---------- Folios electrónicos (Cloud Apps) ----------
    function miles(n) {
        return Number(n || 0).toLocaleString('es-CL');
    }

    function consultarFolios($fila) {
        var $estado = $fila.find('.f-estado');
        var $btn = $fila.find('.btn-folios').prop('disabled', true);
        $fila.find('.f-rango, .f-ultimo, .f-disp, .f-vence').text('…');
        $estado.attr('class', 'f-estado text-muted').text('Consultando…');
        return $.ajax({
            type: 'POST',
            url: base_url + 'index.php/Configuraciones/obtenerFolios',
            dataType: 'json',
            data: {
                tipo: $fila.data('tipo'),
                usuario: $('#txtUsuarioDte').val(),
                clave: $('#txtClaveDte').val(),
                terminal: $('#txtTerminalDte').val()
            }
        }).done(function (r) {
            if (!r.ok) {
                $fila.find('.f-rango, .f-ultimo, .f-disp, .f-vence').text('—');
                $estado.attr('class', 'f-estado text-danger').html('<i class="fa fa-times-circle"></i> ' + $('<span>').text(r.mensaje).html());
                return;
            }
            var rangos = $.map(r.rangos, function (x) { return miles(x.desde) + ' – ' + miles(x.hasta); });
            var ultimos = $.map(r.rangos, function (x) { return x.ultimo ? miles(x.ultimo) : '—'; });
            var vence = $.map(r.rangos, function (x) { return x.vence || '—'; });
            $fila.find('.f-rango').text(rangos.join(', '));
            $fila.find('.f-ultimo').text(ultimos.join(', '));
            $fila.find('.f-disp').html('<b>' + miles(r.disponibles) + '</b>');
            $fila.find('.f-vence').text(vence.join(', '));
            // Pocos folios: aviso para pedir más antes de quedarse sin documentos
            var pocos = r.disponibles < 50;
            $estado.attr('class', 'f-estado ' + (pocos ? 'text-warning' : 'text-success'))
                .html('<i class="fa ' + (pocos ? 'fa-exclamation-triangle' : 'fa-check-circle') + '"></i> '
                    + (r.disponibles === 0 ? 'Sin folios disponibles' : (pocos ? 'Quedan pocos folios' : 'Folios OK')));
        }).fail(function (xhr) {
            $fila.find('.f-rango, .f-ultimo, .f-disp, .f-vence').text('—');
            $estado.attr('class', 'f-estado text-danger').text(xhr.status === 401 ? 'Sesión expirada' : 'Error al consultar');
        }).always(function () {
            $btn.prop('disabled', false);
        });
    }

    $(document).ready(function() {
        cargarCombosComuna();

        // El usuario de Cloud Apps debe ser el RUT de esta empresa (cada cliente tiene el suyo)
        var rutEmpresaDte = <?php echo json_encode(!empty($empresa['rut_empresa']) ? number_format((float)$empresa['rut_empresa'], 0, '', '') . '-' . strtoupper(trim($empresa['dv_empresa'] ?? '')) : ''); ?>;
        function normalizarRut(r) { return String(r || '').replace(/[^0-9kK]/g, '').toUpperCase().replace(/^0+/, ''); }
        function revisarUsuarioDte() {
            var u = $('#txtUsuarioDte').val();
            var distinto = rutEmpresaDte && u && normalizarRut(u) !== normalizarRut(rutEmpresaDte);
            $('#avisoUsuarioDte').remove();
            if (distinto) {
                $('#txtUsuarioDte').after('<p class="help-block text-danger" id="avisoUsuarioDte"><i class="fa fa-exclamation-triangle"></i> '
                    + 'No coincide con el RUT de esta empresa (' + rutEmpresaDte + '). La caja no emitirá documentos con un RUT distinto.</p>');
            }
        }
        $('#txtUsuarioDte').on('input change', revisarUsuarioDte);
        revisarUsuarioDte();

        $('#tablaFoliosDte').on('click', '.btn-folios', function () {
            consultarFolios($(this).closest('tr'));
        });
        // De a uno, para no saturar el servicio
        $('#btnFoliosTodos').on('click', function () {
            var $b = $(this).prop('disabled', true);
            var filas = $('#tablaFoliosDte tbody tr[data-tipo]').toArray();
            (function siguiente() {
                if (!filas.length) { $b.prop('disabled', false); return; }
                consultarFolios($(filas.shift())).always(siguiente);
            })();
        });

        $('#configForm').submit(function(e) {
            e.preventDefault();
            var $submitBtn = $(this).find('button[type="submit"]');
            $submitBtn.prop('disabled', true).text('Guardando...');
            
            $.ajax({
                type: 'POST',
                url: $(this).attr('action'),
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        alert('Configuración guardada exitosamente');
                        location.reload();
                    } else {
                        alert('Error al guardar la configuración');
                        $submitBtn.prop('disabled', false).text('Grabar');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', xhr.responseText);
                    alert('Error en la solicitud: ' + error);
                    $submitBtn.prop('disabled', false).text('Grabar');
                }
            });
        });
    });
</script>