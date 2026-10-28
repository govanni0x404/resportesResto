<style>
    /* Imagen del producto en el modal de la Carta */
    .prod-imagen { display: flex; gap: 14px; align-items: center; padding: 0 15px; }
    .prod-imagen-vista { flex: 0 0 120px; width: 120px; height: 90px; border-radius: 12px; overflow: hidden; border: 1px solid var(--t-borde, #EADFD2);
        background: var(--t-fondo-alt, #FBF8F3); display: flex; align-items: center; justify-content: center; position: relative; }
    .prod-imagen-vista img { width: 100%; height: 100%; object-fit: cover; }
    .prod-imagen-vista.cargando::after { content: ""; position: absolute; inset: 0; background: rgba(255,255,255,.7) url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 50 50"><circle cx="25" cy="25" r="20" fill="none" stroke="%23B4432E" stroke-width="5" stroke-dasharray="90 60"><animateTransform attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur=".8s" repeatCount="indefinite"/></circle></svg>') center no-repeat; }
    .prod-imagen-vacia { display: flex; flex-direction: column; align-items: center; gap: 4px; font-size: 11px; color: var(--t-texto-tenue, #A89A8C); }
    .prod-imagen-vacia i { font-size: 24px; }
    .prod-imagen-acciones { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; min-width: 0; }
    .prod-imagen-ayuda { flex: 0 0 100%; color: var(--t-texto-tenue, #A89A8C); font-size: 11px; }
    .prod-imagen.deshabilitada .btn { pointer-events: none; opacity: .55; }
    /* En la columna derecha (bajo Configuración adicional) hay más espacio: vista previa más grande */
    .prod-imagen.prod-imagen-grande { padding: 0; }
    .prod-imagen.prod-imagen-grande .prod-imagen-vista { flex-basis: 200px; width: 200px; height: 150px; }
    .prod-imagen.prod-imagen-grande .prod-imagen-acciones .btn { min-width: 140px; }
    .prod-busqueda-nota { margin: 8px 0 12px; font-size: 12px; color: var(--t-texto-suave, #7A6A5E); }
    .prod-busqueda-grilla { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 10px; min-height: 120px; }
    .prod-busqueda-item { border: 1px solid var(--t-borde, #EADFD2); border-radius: 12px; overflow: hidden; background: #fff; cursor: pointer; padding: 0; text-align: left; transition: border-color .15s, box-shadow .15s; }
    .prod-busqueda-item:hover, .prod-busqueda-item:focus { border-color: var(--t-primario, #B4432E); box-shadow: 0 4px 12px rgba(58, 42, 34, .12); outline: none; }
    .prod-busqueda-item img { width: 100%; aspect-ratio: 4 / 3; object-fit: cover; display: block; background: var(--t-fondo-alt, #FBF8F3); }
    .prod-busqueda-item span { display: block; padding: 5px 8px; font-size: 10px; color: var(--t-texto-tenue, #A89A8C); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .prod-busqueda-estado { grid-column: 1 / -1; text-align: center; padding: 30px 10px; color: var(--t-texto-tenue, #A89A8C); }
    @media (max-width: 767px) { .prod-imagen { flex-direction: column; align-items: flex-start; } }
</style>
<script>
    // Imagen del producto: se sube o se elige en línea y se guarda al instante en el servidor (ImagenProducto).
    // No toca el guardado del producto (GrabarProducto): solo necesita que el producto ya tenga ID.
    (function () {
        var urlBase = base_url + 'index.php/ImagenProducto/';

        function idActual() { return Number($('#txtID').val()) || 0; }
        function mostrar(url) {
            var $img = $('#prodImagenImg');
            if (url) { $img.attr('src', url).show(); $('#prodImagenVacia').hide(); $('#btnProdImagenQuitar').show(); }
            else { $img.removeAttr('src').hide(); $('#prodImagenVacia').show(); $('#btnProdImagenQuitar').hide(); }
        }
        function cargando(si) { $('#prodImagenVista').toggleClass('cargando', !!si); }
        function error(xhr, texto) {
            var msg = (xhr && xhr.responseJSON && xhr.responseJSON.error) || texto || 'No se pudo completar la acción';
            if (window.showAppAlert) showAppAlert('error', msg, { position: 'top-right', duration: 4000 });
            else alert(msg);
        }
        function refrescar() {
            var id = idActual();
            mostrar(null);
            $('#prodImagen').toggleClass('deshabilitada', !id);
            $('#prodImagenAyuda').text(id ? 'JPG, PNG o WEBP de hasta 6 MB. Se guarda al elegirla.' : 'Guarda el producto primero para poder agregarle una imagen.');
            if (!id) return;
            $.getJSON(urlBase + 'info', { id_producto: id }).done(function (r) { if (idActual() === id) mostrar(r.url); });
        }

        // El modal de la Carta se abre después de cargar el ID (editar) o de limpiarlo (nuevo)
        $('#modalNuevoProducto').on('shown.bs.modal', refrescar);

        $('#btnProdImagenSubir').on('click', function () { if (idActual()) $('#prodImagenArchivo').val('').click(); });
        $('#prodImagenArchivo').on('change', function () {
            var archivo = this.files && this.files[0];
            if (!archivo) return;
            var fd = new FormData();
            fd.append('id_producto', idActual());
            fd.append('imagen', archivo);
            cargando(true);
            $.ajax({ url: urlBase + 'subir', type: 'POST', data: fd, processData: false, contentType: false, dataType: 'json' })
                .done(function (r) { mostrar(r.url); })
                .fail(function (x) { error(x); })
                .always(function () { cargando(false); });
        });

        $('#btnProdImagenQuitar').on('click', function () {
            if (!idActual() || !confirm('¿Quitar la imagen de este producto?')) return;
            cargando(true);
            $.post(urlBase + 'eliminar', { id_producto: idActual() }, null, 'json')
                .done(function () { mostrar(null); })
                .fail(function (x) { error(x); })
                .always(function () { cargando(false); });
        });

        // ---------- búsqueda en línea ----------
        var resultados = [];
        function buscar() {
            var q = $.trim($('#txtBuscarImagen').val());
            var $g = $('#gridBuscarImagen');
            if (q.length < 2) { $g.html('<div class="prod-busqueda-estado">Escribe el nombre del producto para buscar.</div>'); return; }
            $g.html('<div class="prod-busqueda-estado"><i class="fa fa-spinner fa-spin"></i> Buscando…</div>');
            $.getJSON(urlBase + 'buscar', { q: q }).done(function (r) {
                resultados = r.imagenes || [];
                if (!resultados.length) { $g.html('<div class="prod-busqueda-estado">No se encontraron imágenes. Prueba con otras palabras o en inglés.</div>'); return; }
                $g.html($.map(resultados, function (im, i) {
                    var pie = [im.autor, im.licencia].filter(Boolean).join(' · ');
                    return '<button type="button" class="prod-busqueda-item" data-i="' + i + '" title="' + $('<div>').text(im.titulo || '').html() + '">'
                        + '<img src="' + $('<div>').text(im.miniatura).html() + '" alt="" loading="lazy" referrerpolicy="no-referrer">'
                        + '<span>' + $('<div>').text(pie || 'Uso libre').html() + '</span></button>';
                }).join(''));
            }).fail(function (x) {
                $g.html('<div class="prod-busqueda-estado">' + $('<div>').text((x.responseJSON && x.responseJSON.error) || 'No se pudo buscar. Revisa la conexión a internet del servidor.').html() + '</div>');
            });
        }
        $('#btnProdImagenBuscar').on('click', function () {
            if (!idActual()) return;
            $('#txtBuscarImagen').val($.trim($('#txtNombre').val()).toLowerCase());
            $('#modalBuscarImagen').modal('show');
            buscar();
        });
        $('#modalBuscarImagen').on('shown.bs.modal', function () { $('#txtBuscarImagen').focus(); })
            // Modal encima de otro modal: al cerrarlo, Bootstrap 3 quita el scroll del de abajo
            .on('hidden.bs.modal', function () { if ($('#modalNuevoProducto').hasClass('in')) $('body').addClass('modal-open'); });
        $('#btnBuscarImagenIr').on('click', buscar);
        $('#txtBuscarImagen').on('keydown', function (e) { if (e.which === 13) { e.preventDefault(); buscar(); } });

        $('#gridBuscarImagen').on('click', '.prod-busqueda-item', function () {
            var im = resultados[$(this).data('i')];
            if (!im) return;
            var $b = $(this).prop('disabled', true).css('opacity', .6);
            cargando(true);
            $.post(urlBase + 'desdeUrl', { id_producto: idActual(), url: im.url }, null, 'json')
                .done(function (r) { mostrar(r.url); $('#modalBuscarImagen').modal('hide'); })
                .fail(function (x) { error(x); $b.prop('disabled', false).css('opacity', 1); })
                .always(function () { cargando(false); });
        });
    })();
</script>
