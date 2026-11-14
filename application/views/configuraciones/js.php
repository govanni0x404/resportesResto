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

    // Probar envío: manda un correo de prueba a los correos escritos (aunque no se haya grabado) con el servidor de correo
    // del sistema guardado en la base
    $(document).on('click', '#btnProbarCorreo', function () {
        var $b = $(this), correos = $.trim($('#txtCorreosInforme').val());
        if (!correos) { swal('Atención', 'Escribe al menos un correo', 'warning'); return; }
        $b.prop('disabled', true).text('Enviando…');
        $.ajax({ url: base_url + 'index.php/Configuraciones/probarCorreo', type: 'POST', dataType: 'json', data: { correos: correos } })
            .done(function (r) { swal(r.ok ? 'Correo enviado' : 'No se pudo enviar', r.detalle, r.ok ? 'success' : 'error'); })
            .fail(function (x) { swal('No se pudo enviar', (x.responseJSON && x.responseJSON.detalle) || 'Error al enviar el correo', 'error'); })
            .always(function () { $b.prop('disabled', false).text('Probar envío'); });
    });

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
                proveedor: $('input[name="proveedor_dte"]:checked').val() || '2',
                tipo_auth: $('#cboTipoAuthDte').val() || 'basic',
                usuario: $('#txtUsuarioDte').val(),
                clave: $('#txtClaveDte').val(),
                token: $('#txtTokenDte').val() || '',
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
            // La cuenta demo de Cloud Apps (66666666-6) sirve para pruebas en cualquier empresa
            var demo = normalizarRut(u) === '666666666';
            var distinto = rutEmpresaDte && u && !demo && normalizarRut(u) !== normalizarRut(rutEmpresaDte);
            $('#avisoUsuarioDte').remove();
            if (demo) {
                $('#txtUsuarioDte').after('<p class="help-block text-warning" id="avisoUsuarioDte"><i class="fa fa-flask"></i> '
                    + 'Cuenta de pruebas de Cloud Apps: los documentos salen como EMPRESA DEMO y no se envían al SII.</p>');
            } else if (distinto) {
                $('#txtUsuarioDte').after('<p class="help-block text-danger" id="avisoUsuarioDte"><i class="fa fa-exclamation-triangle"></i> '
                    + 'No coincide con el RUT de esta empresa (' + rutEmpresaDte + '). La caja no emitirá documentos con un RUT distinto.</p>');
            }
        }
        $('#txtUsuarioDte').on('input change', revisarUsuarioDte);
        revisarUsuarioDte();

        // Acceso facturación: Basic usa la contraseña; X-API-Key, Bearer y Token, la API Key
        function mostrarAccesoDte() {
            var tipo = $('#cboTipoAuthDte').val() || 'basic';
            var datamaule = $('input[name="proveedor_dte"]:checked').val() === '1';
            $('#grupoClaveDte').toggle(tipo === 'basic');
            $('#grupoTokenDte').toggle(tipo !== 'basic');
            $('#ayudaClaveDatamaule').toggle(tipo === 'basic' && datamaule);
            $('#lblProveedorDte').text(datamaule ? 'datamaule.cl' : 'Cloud Apps');
        }
        $('#cboTipoAuthDte').on('change', mostrarAccesoDte);
        $('input[name="proveedor_dte"]').on('change', mostrarAccesoDte);
        mostrarAccesoDte();

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
                // 14-11-2026: se mandan los nombres de todas las casillas del formulario. El servidor apaga solo las que
                // están aquí y llegaron sin marcar; una casilla que no vino en el envío queda como estaba.
                data: $(this).serialize() + '&_casillas=' + encodeURIComponent($(this).find('input[type="checkbox"][name]').map(function () { return this.name; }).get().join(',')),
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        alert('Configuración guardada exitosamente');
                        location.reload();
                    } else {
                        alert(response.mensaje || 'Error al guardar la configuración');
                        $submitBtn.prop('disabled', false).text('Grabar');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', xhr.responseText);
                    alert((xhr.responseJSON && xhr.responseJSON.mensaje) || ('Error en la solicitud: ' + error));
                    $submitBtn.prop('disabled', false).text('Grabar');
                }
            });
        });
    });
</script>

<script>
    // ---------- Datos de la Empresa (11-11-2026): empresas de esta base y sus sucursales, con datos de la intranet ----------
    (function () {
        var url = base_url + 'index.php/Configuraciones/';
        var datos = { empresas: [], comunas: [] };
        var cargada = false, sincronizando = false;
        var empresaEditando = null, empresaSucursales = null;
        var etiquetas = { rut_empresa: 'RUT', dv_empresa: 'DV', razon_social: 'Razón social', nombre_fantasia: 'Nombre fantasía',
            codigo_actividad_economica: 'Código act. económica', giro: 'Giro', direccion_empresa: 'Dirección', id_comuna: 'Comuna',
            mail: 'Correo', telefono: 'Teléfono' };

        function esc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }
        function aviso(xhr, porDefecto) {
            var msg = (xhr && xhr.responseJSON && xhr.responseJSON.error) || porDefecto || 'No se pudo completar la operación';
            if (window.swal) swal('Error', msg, 'error'); else alert(msg);
        }
        function vacio(html) { $('#cuerpoEmpresas').html('<tr><td colspan="7" class="emp-vacio">' + html + '</td></tr>'); }
        function empresa(id) { return $.grep(datos.empresas, function (e) { return e.id === id; })[0]; }

        function pintar() {
            if (!datos.empresas.length) { vacio('<i class="fa fa-building-o"></i> No hay empresas registradas en esta base.'); return; }
            var h = '';
            $.each(datos.empresas, function (i, e) {
                var d = e.datos || {};
                var vigentes = $.grep(e.sucursales, function (s) { return s.vigente; }).length;
                var estado = e.id_cliente_intranet && e.estado === 'Datos de la intranet'
                    ? '<span class="emp-chip ok">Sincronizada</span>'
                    : (e.estado ? '<span class="emp-chip aviso">' + esc(e.estado) + '</span>' : '<span class="emp-chip">Sin consultar</span>');
                h += '<tr data-id="' + e.id + '">' +
                    '<td class="emp-rut">' + esc(e.rut || '—') + '</td>' +
                    '<td><span class="emp-nombre">' + esc(d.razon_social || '—') + '</span>' +
                        (d.nombre_fantasia ? '<span class="emp-sub">' + esc(d.nombre_fantasia) + '</span>' : '') +
                        (e.principal ? '<span class="emp-sub"><i class="fa fa-star"></i> Emite los documentos</span>' : '') + '</td>' +
                    '<td>' + esc(d.giro || '') + '</td>' +
                    '<td>' + esc(d.direccion_empresa || '') + (e.comuna ? '<span class="emp-sub">' + esc(e.comuna) + '</span>' : '') + '</td>' +
                    '<td class="text-center">' + vigentes + '</td>' +
                    '<td>' + estado + (e.sincronizada ? '<span class="emp-sub">' + esc(e.sincronizada) + '</span>' : '') + '</td>' +
                    '<td class="emp-acciones">' +
                        '<button type="button" class="btn btn-default btn-sm emp-btn-datos"><i class="fa fa-pencil"></i> Datos</button>' +
                        '<button type="button" class="btn btn-primary btn-sm emp-btn-sucursales"><i class="fa fa-map-marker"></i> Sucursales (' + vigentes + ')</button>' +
                    '</td></tr>';
            });
            $('#cuerpoEmpresas').html(h);
            if (empresaSucursales) pintarSucursales(empresa(empresaSucursales));
        }

        function cargar() {
            return $.ajax({ url: url + 'empresas', dataType: 'json', cache: false }).done(function (r) {
                datos = r; cargada = true; pintar();
                if (r.sincronizar) sincronizar(true);
            }).fail(function (xhr) {
                vacio('<i class="fa fa-exclamation-triangle"></i> ' + esc((xhr.responseJSON && xhr.responseJSON.error) || 'No se pudieron cargar las empresas.'));
            });
        }

        function sincronizar(automatico) {
            if (sincronizando) return;
            sincronizando = true;
            var $b = $('#btnSincronizarEmpresas').prop('disabled', true);
            $b.find('i').addClass('fa-spin');
            $.ajax({ url: url + 'sincronizarEmpresas', type: 'POST', dataType: 'json' }).done(function (r) {
                datos = r; pintar();
                var ok = $.grep(r.resultado || [], function (x) { return x.ok; }).length;
                var malas = $.grep(r.resultado || [], function (x) { return !x.ok; });
                if (ok) toastr.success('Empresas actualizadas desde la intranet: ' + ok);
                if (malas.length && !automatico) toastr.warning($.map(malas, function (x) { return (x.rut || 'Empresa') + ': ' + x.mensaje; }).join(' · '));
            }).fail(function (xhr) {
                if (!automatico) aviso(xhr, 'No se pudo actualizar desde la intranet');
            }).always(function () {
                sincronizando = false;
                $b.prop('disabled', false).find('i').removeClass('fa-spin');
            });
        }

        // ---- Datos de una empresa ----
        function opcionesComuna(sel) {
            var h = '<option value="0">Seleccione la comuna</option>';
            $.each(datos.comunas || [], function (i, c) {
                h += '<option value="' + c.id_comuna + '"' + (+c.id_comuna === +sel ? ' selected' : '') + '>' + esc(c.nombre_comuna) + '</option>';
            });
            return h;
        }
        function abrirEmpresa(e) {
            empresaEditando = e.id;
            var d = e.datos || {};
            $('#tituloModalEmpresa').html('<i class="fa fa-building-o"></i> ' + esc(d.razon_social || 'Datos de la empresa'));
            $('#modalEmpresa .emp-de-intranet').remove();
            $('#modalEmpresa [data-campo]').each(function () {
                var $c = $(this), campo = $c.data('campo');
                var existe = $.inArray(campo, e.columnas) >= 0;
                var bloqueado = $.inArray(campo, e.bloqueados) >= 0;
                if ($c.is('select')) $c.html(opcionesComuna(d[campo] || 0));
                else $c.val(d[campo] == null ? '' : d[campo]);
                $c.prop('disabled', !existe || bloqueado).closest('.form-group').toggle(existe);
                if (bloqueado) $c.siblings('label').append('<span class="emp-de-intranet"><i class="fa fa-lock"></i> intranet</span>');
            });
            $('#ayudaModalEmpresa').html(e.id_cliente_intranet
                ? '<i class="fa fa-info-circle"></i> Los datos con <i class="fa fa-lock"></i> vienen de la intranet y se cambian allá. Aquí se completa lo que la intranet no trae.'
                    + (e.comuna_intranet && $.inArray('id_comuna', e.bloqueados) < 0 ? ' La comuna de la intranet (' + esc(e.comuna_intranet) + ') no calza con las comunas del sistema: elígela aquí.' : '')
                : '<i class="fa fa-info-circle"></i> Esta empresa aún no tiene datos de la intranet' + (e.estado ? ' (' + esc(e.estado) + ')' : '') + ': se editan aquí.');
            $('#btnGuardarEmpresa').prop('disabled', false);
            $('#modalEmpresa').modal('show');
        }
        $('#btnGuardarEmpresa').on('click', function () {
            var e = empresa(empresaEditando);
            if (!e) return;
            var cambios = {};
            $('#modalEmpresa [data-campo]').each(function () {
                var $c = $(this);
                if (!$c.prop('disabled')) cambios[$c.data('campo')] = $c.val();
            });
            var $b = $(this).prop('disabled', true);
            $.ajax({ url: url + 'guardarEmpresa', type: 'POST', dataType: 'json', data: { id: e.id, datos: cambios } }).done(function () {
                toastr.success('Datos de la empresa guardados');
                $('#modalEmpresa').modal('hide');
                cargar();
            }).fail(function (xhr) { aviso(xhr, 'No se pudieron guardar los datos'); }).always(function () { $b.prop('disabled', false); });
        });

        // ---- Sucursales de una empresa ----
        function pintarSucursales(e) {
            if (!e) return;
            $('#tituloModalSucursales').html('<i class="fa fa-map-marker"></i> Sucursales de ' + esc((e.datos && e.datos.razon_social) || e.rut));
            if (!e.sucursales.length) {
                $('#cuerpoSucursales').html('<tr><td colspan="7" class="emp-vacio">' + (e.id_cliente_intranet
                    ? 'La intranet no informa sucursales de esta empresa para esta base.'
                    : 'Sin datos de la intranet' + (e.estado ? ': ' + esc(e.estado) : '') + '. Pulsa «Actualizar desde la intranet».') + '</td></tr>');
                return;
            }
            var h = '';
            $.each(e.sucursales, function (i, s) {
                var comuna = s.comuna_calza
                    ? esc(s.comuna_local || s.comuna)
                    : '<select class="form-control emp-comuna">' + opcionesComuna(s.id_comuna) + '</select>'
                        + (s.comuna ? '<span class="emp-sub">Intranet: ' + esc(s.comuna) + '</span>' : '');
                var codigo = s.codigo_sii > 0
                    ? esc(s.codigo_sii) + '<span class="emp-sub">intranet</span>'
                    : '<input type="text" inputmode="numeric" maxlength="9" class="form-control emp-codigo" value="' + (s.codigo_sii_manual || '') + '" placeholder="Sin código">';
                h += '<tr data-id="' + s.id + '" class="' + (s.vigente ? '' : 'emp-no-vigente') + '">' +
                    '<td><span class="emp-nombre">' + esc(s.nombre) + '</span>' +
                        (s.nombre_fantasia && s.nombre_fantasia !== s.nombre ? '<span class="emp-sub">' + esc(s.nombre_fantasia) + '</span>' : '') +
                        (s.casa_matriz ? '<span class="emp-chip ok">Casa matriz</span>' : '') + '</td>' +
                    '<td>' + esc(s.direccion || '—') + '</td>' +
                    '<td>' + comuna + '</td>' +
                    '<td>' + esc(s.telefono || '') + '</td>' +
                    '<td>' + codigo + '</td>' +
                    '<td class="text-center">' + (s.cajas || 0) + '</td>' +
                    '<td class="text-center"><span class="emp-chip ' + (s.vigente ? 'ok">Sí' : '">No') + '</span></td>' +
                    '</tr>';
            });
            $('#cuerpoSucursales').html(h);
        }
        function guardarSucursal($tr) {
            var id = +$tr.data('id');
            var $codigo = $tr.find('.emp-codigo'), $comuna = $tr.find('.emp-comuna');
            var codigo = $.trim($codigo.val() || '');
            if (codigo !== '' && !/^\d{1,9}$/.test(codigo)) { aviso(null, 'El código SII de la sucursal es un número de hasta 9 dígitos'); return; }
            $tr.find('input, select').prop('disabled', true);
            $.ajax({ url: url + 'guardarSucursal', type: 'POST', dataType: 'json',
                data: { id: id, codigo_sii: codigo, id_comuna: $comuna.length ? $comuna.val() : 0 } }).done(function () {
                toastr.success('Sucursal guardada');
                var e = empresa(empresaSucursales);
                $.each(e ? e.sucursales : [], function (i, s) {
                    if (s.id !== id) return;
                    if ($codigo.length) s.codigo_sii_manual = codigo === '' ? 0 : +codigo;
                    if ($comuna.length) s.id_comuna = +$comuna.val();
                });
            }).fail(function (xhr) { aviso(xhr, 'No se pudo guardar la sucursal'); pintarSucursales(empresa(empresaSucursales)); })
              .always(function () { $tr.find('input, select').prop('disabled', false); });
        }
        $('#cuerpoSucursales').on('change', '.emp-codigo, .emp-comuna', function () { guardarSucursal($(this).closest('tr')); })
            .on('keydown', '.emp-codigo', function (ev) { if (ev.which === 13) { ev.preventDefault(); $(this).blur(); } });
        $('#modalSucursales').on('hidden.bs.modal', function () { empresaSucursales = null; });

        $('#cuerpoEmpresas').on('click', '.emp-btn-datos', function () {
            var e = empresa(+$(this).closest('tr').data('id'));
            if (e) abrirEmpresa(e);
        }).on('click', '.emp-btn-sucursales', function () {
            var e = empresa(+$(this).closest('tr').data('id'));
            if (!e) return;
            empresaSucursales = e.id;
            pintarSucursales(e);
            $('#modalSucursales').modal('show');
        });
        $('#btnSincronizarEmpresas').on('click', function () { sincronizar(false); });

        // Se carga al abrir la pestaña (y la intranet se consulta sola una vez por sesión)
        $(document).on('click', '.tablink', function () {
            if (!cargada && /'empresa'/.test($(this).attr('onclick') || '')) cargar();
        });
        $(function () { if ($('#empresa').hasClass('active')) cargar(); });
    })();
</script>
