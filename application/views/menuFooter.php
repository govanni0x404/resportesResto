</div> <!-- /.container -->
</div> <!-- /.content-wrapper -->
<!-- start footer -->


<footer class="main-footer">
	<div class="container">
		<!-- 18-11-2026: margen a la derecha para que el botón verde de "subir" (abajo a la derecha) no tape la versión -->
		<div class="pull-right hidden-xs" style="margin-right:70px"> <b>Version</b>
        <?php echo app_version_short(); ?><?php if (defined('RESTO_MODO_LOCAL') && RESTO_MODO_LOCAL): ?> <span class="label label-warning" title="RESTO_CONEXION=2: base de este computador, sin intranet">LOCAL</span><?php endif; ?>
  </div>
		<strong>Copyright &copy; <a href="https://datamaule.cl/">DAT@MAULE</a>.</strong> All rights reserved. <i class="fa fa-heart color-green"></i>
	</div>
</footer> <!-- /. footer -->
</div> <!-- ./wrapper -->
<!-- jQuery -->
<script src="<?php echo base_url() ?>assets/plugins/jQuery/jquery-1.12.4.min.js"></script>
<!-- jquery-ui -->
<script src="<?php echo base_url() ?>assets/plugins/jquery-ui-1.12.1/jquery-ui.min.js"></script>
<!-- Bootstrap js -->
<script src="<?php echo base_url() ?>assets/bootstrap/js/bootstrap.js"></script>
<!-- v1.24.3: ventana sobre ventana (ej. Precio de venta sobre la Carta). Bootstrap 3 no las apila: la nueva queda encima con
     su propio fondo, y lo de atrás (la otra ventana) se ve oscuro y borroso, para no perder la noción de lo que se hace -->
<style>
  .modal-backdrop.modal-apilado.in { opacity: .35; -webkit-backdrop-filter: blur(3px); backdrop-filter: blur(3px); }
</style>
<script>
  (function ($) {
    if (!$) return;
    $(document).on('show.bs.modal', '.modal', function (e) {
      if (e.target !== this) return;   // eventos de tooltips / popovers dentro de la ventana
      var $m = $(this), abiertas = $('.modal.in').not($m).filter(':visible').length;
      if (!abiertas) return;
      var z = 1050 + abiertas * 20;
      $m.css('z-index', z);
      // El fondo lo agrega Bootstrap justo después de este aviso: se toma el último
      setTimeout(function () {
        $('.modal-backdrop').not('.modal-apilado').last().css('z-index', z - 5).addClass('modal-apilado');
      }, 0);
    });
    // Al cerrar la de encima, la de abajo sigue abierta: se mantiene el scroll propio de las ventanas
    $(document).on('hidden.bs.modal', '.modal', function (e) {
      if (e.target !== this) return;
      $(this).css('z-index', '');
      if ($('.modal.in:visible').length) $('body').addClass('modal-open');
    });
  })(window.jQuery);
</script>
<!-- lobipanel js -->
<script src="<?php echo base_url() ?>assets/plugins/lobipanel/lobipanel.js"></script>
<!-- animsition js -->
<script src="<?php echo base_url() ?>assets/plugins/animsition/js/animsition.min.js"></script>
<!-- bootsnav js -->
<script src="<?php echo base_url() ?>assets/plugins/bootsnav/js/bootsnav.min.js"></script>
<!-- SlimScroll js -->
<script src="<?php echo base_url() ?>assets/plugins/slimScroll/jquery.slimscroll.min.js"></script>
<!-- FastClick js-->
<script src="<?php echo base_url() ?>assets/plugins/fastclick/fastclick.min.js"></script>
<!-- End Core Plugins
        =====================================================================-->
<!-- Start Page Lavel Plugins
        =====================================================================-->
<!-- Toastr js -->


<script src="<?php echo base_url() ?>assets/plugins/select2/select2.js"></script>
<script src="<?php echo base_url() ?>assets/plugins/toastr/toastr.min.js"></script>
<script>
  // 11-11-2026: todos los avisos (verde, rojo, naranjo y azul) traen una X para cerrarlos, en todo el sistema
  if (window.toastr) {
    toastr.options.closeButton = true;
    toastr.options.closeHtml = '<button type="button" aria-label="Cerrar aviso" title="Cerrar">&times;</button>';
  }
</script>
<style>
  /*
   * 18-11-2026: avisos (toastr) con diseño nuevo en todo el sistema (el usuario encontraba feos los de color sólido):
   * tarjeta blanca, franja e ícono del color del tipo (verde = listo, rojo = error, naranjo = cuidado, azul = info).
   */
  #toast-container > .toast {
    --tc: #2563eb; --tc-suave: #dbeafe;
    position: relative; overflow: hidden; width: 360px; max-width: calc(100vw - 24px);
    margin: 0 0 10px; padding: 14px 46px 14px 64px; border-radius: 12px; border: 1px solid #e5e7eb; border-left: 5px solid var(--tc);
    background: #fff none !important; color: #1f2937; opacity: 1;
    box-shadow: 0 12px 28px rgba(15, 23, 42, .16), 0 2px 6px rgba(15, 23, 42, .08);
    font-family: inherit;
  }
  #toast-container > .toast:hover { opacity: 1; cursor: default; box-shadow: 0 14px 32px rgba(15, 23, 42, .22), 0 3px 8px rgba(15, 23, 42, .1); }
  #toast-container > .toast::before {
    font-family: 'FontAwesome'; position: absolute; left: 16px; top: 50%; margin-top: -17px; width: 34px; height: 34px; border-radius: 50%;
    background: var(--tc-suave); color: var(--tc); font-size: 16px; line-height: 34px; text-align: center;
  }
  #toast-container > .toast-success { --tc: #16a34a; --tc-suave: #dcfce7; }
  #toast-container > .toast-success::before { content: "\f00c"; }
  #toast-container > .toast-error { --tc: #dc2626; --tc-suave: #fee2e2; }
  #toast-container > .toast-error::before { content: "\f00d"; }
  #toast-container > .toast-warning { --tc: #d97706; --tc-suave: #fef3c7; }
  #toast-container > .toast-warning::before { content: "\f12a"; }
  #toast-container > .toast-info { --tc: #2563eb; --tc-suave: #dbeafe; }
  #toast-container > .toast-info::before { content: "\f129"; }
  #toast-container .toast-title { font-size: 14px; font-weight: 700; color: #111827; line-height: 1.3; margin-bottom: 2px; }
  #toast-container .toast-message { font-size: 13px; color: #4b5563; line-height: 1.4; word-wrap: break-word; }
  #toast-container .toast-message a, #toast-container .toast-message label { color: var(--tc); }
  #toast-container .toast-progress { height: 3px; background: var(--tc); opacity: .35; }
  /* La X del aviso: grande y fácil de presionar (toastr la deja muy pequeña) */
  #toast-container .toast-close-button { position: absolute; top: 50%; right: 8px; margin-top: -15px; width: 30px; height: 30px; float: none;
    border-radius: 8px; color: #9ca3af; font-size: 22px; font-weight: 400; line-height: 28px; text-align: center; opacity: 1; text-shadow: none; }
  #toast-container .toast-close-button:hover, #toast-container .toast-close-button:focus { background: #f3f4f6; color: #111827; outline: none; }
</style>
<!-- Sparkline js -->
<script src="<?php echo base_url() ?>assets/plugins/sparkline/sparkline.min.js"></script>
<script src="<?php echo base_url() ?>assets/plugins/sparkline/sparkline-active.js"></script>
<!-- Counterup js -->
<script src="<?php echo base_url() ?>assets/plugins/counterup/jquery.counterup.min.js"></script>
<script src="<?php echo base_url() ?>assets/plugins/counterup/waypoints.js"></script>
<!-- Emojionearea -->
<script src="<?php echo base_url() ?>assets/plugins/emojionearea/emojionearea.min.js"></script>
<!-- Monthly js -->
<script src="<?php echo base_url() ?>assets/plugins/monthly/monthly.min.js"></script>
<!-- amchat js -->
<script src="<?php echo base_url() ?>assets/plugins/amcharts/amcharts.js"></script>
<script src="<?php echo base_url() ?>assets/plugins/amcharts/ammap.js"></script>
<script src="<?php echo base_url() ?>assets/plugins/amcharts/worldLow.js"></script>
<script src="<?php echo base_url() ?>assets/plugins/amcharts/serial.js"></script>
<!--<script src="<?php echo base_url() ?>assets/plugins/amcharts/export.min.js"></script>-->
<script src="<?php echo base_url() ?>assets/plugins/amcharts/export.min.js"></script>
<script src="<?php echo base_url() ?>assets/plugins/amcharts/light.js"></script>
<script src="<?php echo base_url() ?>assets/plugins/amcharts/pie.js"></script>
<!-- datatables -->
<script src="<?php echo base_url() ?>assets/plugins/amcharts/plugins/export/libs/jszip/jszip.min.js"></script>
<script src="<?php echo base_url() ?>assets/plugins/datatables/dataTables.min.js"></script>
<script src="<?php echo base_url() ?>assets/plugins/datatables/dataTables.responsive.js"></script>
<!-- 14-11-2026: las celdas de texto de todas las tablas se muestran como texto (XSS guardado) -->
<script src="<?php echo base_url() ?>assets/js/dt-escapar.js?v=20261114"></script>
<!-- v1.30.3: librerías desde el propio servidor (antes de internet: si el CDN iba lento, la página quedaba en "Cargando…") -->
<script src="<?php echo base_url() ?>assets/vendor/dataTables.select-1.3.3.min.js"></script>
<script>
  // v1.29.2: todas las tablas en español por defecto. 5 pantallas no traían su traducción y salían en inglés
  // ("Show 10 entries", "Search", "No data available in table", "Previous / Next"). La que trae la suya la sigue usando.
  if (window.jQuery && $.fn.dataTable) {
    // v1.30.1: sin decimal/thousands aquí: puestos por defecto, la columna queda como tipo "num," sin forma de ordenarse y
    // DataTables la ordenaba como texto (1, 10, 2, 3...: ID de Mesas, código de barras de la Carta)
    $.extend(true, $.fn.dataTable.defaults, { language: {
      processing: 'Procesando…', search: 'Buscar:', lengthMenu: 'Mostrar _MENU_ registros',
      info: 'Mostrando _START_ a _END_ de _TOTAL_ registros', infoEmpty: 'Sin registros', infoFiltered: '(filtrado de _MAX_ registros)',
      loadingRecords: 'Cargando…', zeroRecords: 'No se encontraron resultados', emptyTable: 'No hay datos para mostrar',
      paginate: { first: 'Primera', previous: 'Anterior', next: 'Siguiente', last: 'Última' },
      aria: { sortAscending: ': ordenar de menor a mayor', sortDescending: ': ordenar de mayor a menor' }
    } });
  }
</script>
<!-- <script src="<?php echo base_url() ?>assets/plugins/datatables/buttons.bootstrap.js"></script> -->
<script src="<?php echo base_url() ?>assets/vendor/chart-3.9.1.min.js"></script>
<!-- <script type="text/javascript" src="https://cdn.datatables.net/responsive/2.2.7/js/dataTables.responsive.min.js"></script> -->

<!-- <script src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script> -->
<script src="<?php echo base_url() ?>assets/vendor/moment-2.30.1.min.js"></script>
<script src="<?php echo base_url() ?>assets/plugins/date-range-picker/daterangepicker.js"></script>
<!-- Start Theme label Script
        =====================================================================-->
<!-- Dashboard js -->
<script src="<?php echo base_url() ?>assets/dist/js/dashboard.js"></script>
<!-- Redirect js -->
<script src="<?php echo base_url() ?>/assets/redirect/jquery.redirect.js"></script>
<!-- Sweet Alert -->
<script src="<?php echo base_url() ?>assets/plugins/sweetalert/sweetalert.min.js"></script>
<script>
  // Confirmar usa el color del tema (SweetAlert traía celeste); las acciones destructivas pasan su propio confirmButtonColor
  (function () {
    var color = getComputedStyle(document.documentElement).getPropertyValue('--t-primario').trim();
    if (window.swal && swal.setDefaults && /^#[0-9a-f]{6}$/i.test(color)) swal.setDefaults({ confirmButtonColor: color });
  })();
</script>

<!-- End Theme label Script
        =====================================================================-->
<script src="<?php echo base_url() ?>assets/vendor/dataTables.sum-1.10.20.js"></script>
<script>
  // Texto seguro para meter en HTML: lo que escriben los usuarios (nombres, observaciones, motivos) no se ejecuta
  window.escHtml = function (s) {
    return s === null || s === undefined ? '' : String(s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  };
  // DataTables muestra cada celda como HTML: las columnas de datos sin render propio se muestran como texto.
  // Quedan como HTML las columnas "btn..." (botones que arma el servidor), las que tienen render propio
  // y las marcadas con html: true.
  (function ($) {
    if (!$ || !$.fn || !$.fn.dataTable) return;
    var original = $.fn.dataTable;
    var comoTexto = function (d, type) { return type === 'display' && typeof d === 'string' ? escHtml(d) : d; };
    var proteger = function (cols) {
      $.each(cols || [], function (i, c) {
        if (!c || typeof c !== 'object' || c.render || c.mRender || c.fnRender || c.html) return;
        var campo = c.data !== undefined ? c.data : c.mData;
        if (typeof campo === 'string' && !/^btn/i.test(campo)) c.render = comoTexto;
      });
    };
    var envoltura = function (opciones) {
      if (opciones && typeof opciones === 'object') { proteger(opciones.columns); proteger(opciones.aoColumns); }
      return original.apply(this, arguments);
    };
    $.extend(envoltura, original);
    envoltura.prototype = original.prototype;
    $.fn.dataTable = envoltura;
  })(window.jQuery);
</script>


  <script type="text/javascript">


      var currentYear = moment().year(); // This Year
      var currentYearStart = moment({
         years: currentYear,
         months: '0',
         date: '1'
      }); // 1st Jan this year
      var currentYearEnd = moment({
         years: currentYear,
         months: '11',
         date: '31'
      }); // 31st Dec this yea

      var dateRange = {};
      dateRange["Hoy"] = [moment(), moment()];
      //dateRange["Semana Actual"] = [moment().startOf('week'), moment().endOf('week')];
      //dateRange["Semana Anterior"] = [moment().subtract(1, 'week').startOf('week'), moment().subtract(1, 'week').endOf('week')];
      dateRange["Mes Actual"] = [moment().startOf('month'), moment().endOf('month')];
      dateRange["Mes Anterior"] = [moment().subtract(1, 'months').startOf('month'),moment().subtract(1, 'months').endOf('month')];
      dateRange["Últimos 3 meses"] = [moment().subtract(2, 'months').startOf('month'), moment().endOf('month')];
      dateRange["Últimos 6 meses"] = [moment().subtract(6, 'months').startOf('month'), moment().endOf('month')];
      dateRange["Este Año " + (currentYear )] = [moment(currentYearStart), moment(currentYearEnd)]; // Year 2017
      dateRange["Año Pasado " + (currentYear - 1)] = [moment(currentYearStart.subtract(1, "year")), moment(currentYearEnd.subtract(1, "year"))]; // Year 2017
      //dateRange["Año " + (currentYear - 2)] = [moment(currentYearStart.subtract(1, "year")), moment(currentYearEnd.subtract(1, "year"))]; // Year 2016
      //dateRange["Año " + (currentYear - 3)] = [moment(currentYearStart.subtract(1, "year")), moment(currentYearEnd.subtract(1, "year"))]; // Year 2015
      //dateRange["Año " + (currentYear - 4)] = [moment(currentYearStart.subtract(1, "year")), moment(currentYearEnd.subtract(1, "year"))]; // Year 2014

  </script>

  <script>
          /**
           * Muestra una alerta reutilizando #appAlert
           * @param {"success"|"error"} type
           * @param {string} body  Texto del mensaje (permite HTML simple)
           * @param {Object} [opts]
           * @param {string} [opts.title]     Título (por defecto según type)
           * @param {number} [opts.duration]  ms antes de ocultar (0 = no autocerrar). Default: 4000
           * @param {string} [opts.image]     URL imagen (opcional)
           * @param {"top-right"|"top-left"|"bottom-right"|"bottom-left"|"top"|"bottom"} [opts.position] Posición (default "top-right")
           * @param {string} [opts.paletteClass] Clase de color alternativa (ej: "alert7") para usar tu paleta
           */
          function showAppAlert(type, body, opts){
            // v1.24.5: todos los avisos del sistema con el mismo diseño de la Caja (toastr: tarjeta blanca, franja e ícono
            // del color del tipo). Antes era un cuadro verde o rojo con un GIF (#appAlert). Se mantiene la misma firma:
            // type success / error (también warning e info), body con HTML simple o saltos de línea, opts.title y
            // opts.duration (ms; 0 = queda hasta cerrarlo). opts.image y opts.position ya no se usan.
            opts = opts || {};
            var tipo = { success: 'success', error: 'error', warning: 'warning', info: 'info' }[type] || (type === 'ok' ? 'success' : 'error');
            var titulos = { success: 'Operación exitosa', error: 'Ocurrió un error', warning: 'Atención', info: 'Información' };
            var texto = String(body == null ? '' : body).replace(/^\s+|\s+$/g, '').replace(/\r?\n/g, '<br>');
            var duracion = (typeof opts.duration === 'number') ? opts.duration : (tipo === 'error' ? 7000 : 4000);
            if (!window.toastr) { if (texto) window.console && console.log(tipo, texto); return; }
            toastr[tipo](texto, opts.title || titulos[tipo], {
              escapeHtml: false,   // los mensajes de las pantallas traen HTML simple (<b>, <br>)
              timeOut: duracion, extendedTimeOut: duracion > 0 ? 2000 : 0,
              closeButton: true, progressBar: duracion > 0, newestOnTop: true,
              preventDuplicates: false
            });
          }
          window.showAppAlert = showAppAlert;

          // v1.24.11: aviso entre pestañas. Cuando una pantalla graba algo que usa la Caja (productos, precios, agregados,
          // menús, familias, mensajes, formas de pago, configuraciones, mesas...), las otras pestañas abiertas del mismo
          // navegador se enteran y la Caja se actualiza sola, sin recargar la página.
          (function () {
            var CLAVE = 'dataresto.cambioDatos';
            var canal = null;
            try { canal = window.BroadcastChannel ? new BroadcastChannel('dataresto-web') : null; } catch (e) { canal = null; }
            var oyentes = [];
            var avisar = function (m) { $.each(oyentes, function (i, fn) { try { fn(m); } catch (e) {} }); };
            if (canal) canal.onmessage = function (e) { if (e && e.data) avisar(e.data); };
            else window.addEventListener('storage', function (e) {
              if (e.key !== CLAVE || !e.newValue) return;
              try { avisar(JSON.parse(e.newValue)); } catch (x) {}
            });
            /** Avisa a las otras pestañas. tipo: 'catalogo' o 'mesas' */
            window.avisarCambioDatos = function (tipo) {
              var m = { tipo: tipo || 'catalogo', t: Date.now() };
              if (canal) { try { canal.postMessage(m); } catch (e) {} }
              else { try { localStorage.setItem(CLAVE, JSON.stringify(m)); } catch (e) {} }
            };
            /** La pantalla que quiera enterarse (la Caja) se suscribe aquí */
            window.alCambiarDatos = function (fn) { if (typeof fn === 'function') oyentes.push(fn); };

            // Se detecta solo: toda grabación (POST) que salió bien en esas pantallas avisa. Las consultas no.
            var CATALOGO = /index\.php\/(Productos|Agregados|ListaDePrecios|Familias|SubFamilias|MensajesPorProductos|FormasDePago|Impuestos|ImagenProducto|Umedida|Configuraciones)\//i;
            var MESAS = /index\.php\/(Mesas|MesasPorRecinto|Recintos|TipoMesa)\//i;
            var CONSULTA = /\/(listado|listar|obtener|buscar|busqueda|verificar|llenar|get|datos|tiposMesa|probar|destinoGaveta|empresas)/i;
            $(document).ajaxSuccess(function (ev, xhr, opts) {
              if (String(opts.type || opts.method || '').toUpperCase() !== 'POST') return;
              var url = String(opts.url || '');
              if (CONSULTA.test(url.replace(/^.*index\.php\/[^\/]+/, ''))) return;
              if (CATALOGO.test(url)) window.avisarCambioDatos('catalogo');
              else if (MESAS.test(url)) window.avisarCambioDatos('mesas');
            });
          })();

          // v1.24.5: los alert() del navegador (ventana gris que bloquea la pantalla) salen como aviso del sistema.
          // v1.29.2: con el color que corresponde: error (rojo), salió bien (verde) o falta algo (amarillo). Antes un
          // "Configuración guardada exitosamente" salía amarillo como "Atención".
          (function () {
            var nativo = window.alert;
            window.alert = function (msg) {
              if (!window.toastr) return nativo.apply(window, arguments);
              var t = String(msg == null ? '' : msg);
              var tipo = /error|no se pudo|fall|inv[aá]lid/i.test(t) ? 'error'
                : (/exitos|correctamente|guardad|grabad|cread|eliminad|actualizad|listo/i.test(t) ? 'success' : 'warning');
              showAppAlert(tipo, $('<div>').text(t).html(), { duration: tipo === 'success' ? 4000 : 6000 });
            };
          })();

          /**
           * v1.29.2: pregunta "¿Seguro?" con la ventana del sistema (SweetAlert con el diseño de la Caja) en vez del confirm()
           * gris del navegador. si() se llama solo si se acepta. peligro = true pinta el botón en rojo.
           */
          window.confirmarSistema = function (titulo, texto, botonSi, si, peligro) {
            if (!window.swal) { if (window.confirm(titulo + (texto ? '\n' + texto : ''))) si(); return; }
            swal({ title: titulo, text: texto || '', type: 'warning', showCancelButton: true, confirmButtonText: botonSi || 'Sí',
              cancelButtonText: 'Cancelar', confirmButtonColor: peligro ? '#b91c1c' : undefined, closeOnConfirm: true },
              function (ok) { if (ok) si(); });
          };

          // v1.29.2: un aviso que se está mostrando cuando la página se recarga (ej. "Guardado" y al tiro location.reload())
          // ya no se pierde: se vuelve a mostrar en la página nueva. Vale para todos los avisos (toastr) del sistema.
          (function () {
            if (!window.toastr || !window.sessionStorage) return;
            var CLAVE = 'restoWeb.avisosPendientes', VIDA = 8000;
            var leer = function () { try { return JSON.parse(sessionStorage.getItem(CLAVE) || '[]') || []; } catch (e) { return []; } };
            var escribir = function (l) { try { sessionStorage.setItem(CLAVE, JSON.stringify(l)); } catch (e) { /* sin almacenamiento */ } };
            var originales = {}, seq = 0;
            $.each(['success', 'error', 'warning', 'info'], function (i, tipo) {
              originales[tipo] = toastr[tipo];
              toastr[tipo] = function (mensaje, titulo, opciones) {
                var id = Date.now() + '-' + (++seq);
                var l = leer().filter(function (a) { return Date.now() - a.t < VIDA; });
                l.push({ id: id, tipo: tipo, m: mensaje == null ? '' : String(mensaje), ti: titulo == null ? '' : String(titulo),
                  o: opciones && typeof opciones === 'object' ? { escapeHtml: opciones.escapeHtml, timeOut: opciones.timeOut, closeButton: opciones.closeButton } : null, t: Date.now() });
                escribir(l.slice(-5));
                var o = $.extend({}, opciones || {});
                var antes = o.onHidden;
                o.onHidden = function () {   // se vio entero (o lo cerraron): ya no hay que repetirlo
                  escribir(leer().filter(function (a) { return a.id !== id; }));
                  if (typeof antes === 'function') antes.apply(this, arguments);
                };
                return originales[tipo].call(toastr, mensaje, titulo, o);
              };
            });
            // Al cargar: los que la recarga anterior cortó
            var pendientes = leer().filter(function (a) { return Date.now() - a.t < VIDA; });
            escribir([]);
            $.each(pendientes, function (i, a) { toastr[a.tipo](a.m, a.ti || undefined, a.o || undefined); });
          })();

          // Opcional: cerrar al presionar ESC
          $(document).on('keyup', function(e){
            if (e.key === 'Escape') $('#appAlert').fadeOut(150);
          });
</script>
 <script>
  (function(){
    let pending = 0;
    const el = () => document.getElementById('app-loader');
    const txt = () => el().querySelector('[data-loader-text]');

    window.showLoader = function(message){
      pending++;
      if (message) txt().textContent = message;
      el().classList.remove('hidden');
    };
    window.hideLoader = function(){
      pending = Math.max(0, pending - 1);
      if (pending === 0) el().classList.add('hidden');
    };
  })();
  </script>
 <script>
  // Barra lateral: marca la sección actual (todas las opciones traen class="active", por eso se usa una clase propia).
  // Si la página está como opción directa de la barra (p. ej. "Carta") solo se marca esa; si no, se marca la ruta de submenús.
  (function(){
    var actual = (location.pathname.split('/index.php/')[1] || '').split('/')[0].toLowerCase();
    if (!actual) return;
    function destino(a){ return ((a.getAttribute('href') || '').split('/index.php/')[1] || '').split('/')[0].toLowerCase(); }
    var $nav = $('nav.bootsnav ul.nav.navbar-nav').first();
    var $directo = $nav.children('li').children('a[href*="/index.php/"]').filter(function(){ return destino(this) === actual; }).first();
    if ($directo.length) { $directo.parent().addClass('t-actual'); return; }
    var $sub = $nav.find('.dropdown-menu a[href*="/index.php/"]').filter(function(){ return destino(this) === actual; }).first();
    $sub.parents('li').addClass('t-actual');
  })();

  // menuTop abre un div más de los que cierra este archivo, por lo que el pie quedaba dentro de .content-wrapper
  // (con su margen lateral duplicado). Se deja al final de .wrapper, donde theme.css lo pega al borde inferior.
  $(function(){ $('.main-footer').appendTo('.wrapper.animsition'); });

  // Algunas secciones (p. ej. Compras) cargan su vista después de menuFooter: su contenido quedaba fuera de
  // .content-wrapper, arriba del pie y debajo de la barra lateral. Se mueve al área de contenido antes de que
  // corra el JS de la vista (mover nodos conserva ids y eventos).
  $(function(){
    var $destino = $('.content-wrapper > .container > .content').first();
    if (!$destino.length) return;
    // Bloques de contenido que quedaron fuera de .content-wrapper (como hijos directos de body o de un .wrapper;
    // bootsnav puede envolver la página en otro .wrapper, así que no basta con mirar los hermanos del pie)
    $('div.content-header, section, div.container, div.container-padding, div.content, div.row, div.panel').filter(function(){
      var $p = $(this).parent();
      if (!($p.is('body') || $p.is('.wrapper'))) return false;
      return !$(this).is('.modal, .content-wrapper, .main-header') && !$(this).closest('.content-wrapper, .modal, .main-header, .main-footer, nav').length;
    }).appendTo($destino);
  });

  // Barra lateral (escritorio): los submenús se despliegan hacia abajo dentro de la misma barra, con clic.
  // Se quitan los eventos hover de bootsnav (que los abrían hacia el lado) y se vuelven a quitar tras cada resize.
  $(function(){
    var $nav = $('nav.bootsnav');
    function esEscritorio(){ return window.innerWidth >= 993; }
    function quitarHover(){
      if (!esEscritorio()) return;
      $nav.find('ul.nav').off('mouseleave').find('li.dropdown').off('mouseenter mouseleave');
    }
    quitarHover();
    $(window).on('resize', function(){ setTimeout(quitarHover, 50); });

    // Primer nivel (Procesos, Mantención, Consultas, Herramientas)
    $nav.find('ul.nav.navbar-nav > li.dropdown > a.dropdown-toggle').on('click.tLateral', function(e){
      if (!esEscritorio()) return;
      e.preventDefault();
      var $li = $(this).parent(), $menu = $li.children('.dropdown-menu');
      // Barra contraída: se despliega flotando sobre el contenido con este grupo abierto
      var desplegada = !!(window.tLateral && window.tLateral.desplegar());
      var abrir = desplegada || !$li.hasClass('t-abierto');
      $li.siblings('.t-abierto').removeClass('t-abierto').children('.dropdown-menu').stop(true, true).slideUp(160);
      $li.toggleClass('t-abierto', abrir);
      $menu.stop(true, true)[abrir ? 'slideDown' : 'slideUp'](160);
    });

    // Al entrar, deja abierto el grupo de la sección actual
    if (esEscritorio()) {
      $nav.find('ul.nav.navbar-nav > li.dropdown.t-actual').addClass('t-abierto').children('.dropdown-menu').show();
      $nav.find('.dropdown-menu li.dropdown.t-actual').addClass('open');
    }
  });

  // Barra lateral contraída (solo íconos), escritorio. El botón de arriba la oculta o la muestra y queda recordado en
  // este navegador (menuTop pone la clase t-lateral-mini en <html> antes de pintar). Contraída, al abrir un grupo o el
  // menú de configuración se despliega flotando sobre el contenido (sin moverlo) y se vuelve a contraer al salir de
  // ella con el mouse, al hacer clic fuera o con Esc.
  $(function(){
    var CLAVE = 'restoWeb.lateralMini';
    var $html = $('html'), $btn = $('#btnLateral');
    var $zona = $('nav.bootsnav').add('.main-header .top-nav');
    var $enlaces = $('nav.bootsnav ul.nav.navbar-nav > li > a');
    var timer = null;
    function esEscritorio(){ return window.innerWidth >= 993; }
    function mini(){ return $html.hasClass('t-lateral-mini'); }
    function flotando(){ return $html.hasClass('t-lateral-flotante'); }
    function pintar(){
      var m = mini(), texto = m ? 'Mostrar menú' : 'Ocultar menú';
      $btn.attr({ 'aria-expanded': m ? 'false' : 'true', title: texto });
      $btn.find('i').attr('class', m ? 'fa fa-angle-double-right' : 'fa fa-angle-double-left');
      $btn.find('span').text(texto);
      // Contraída solo se ven los íconos: el nombre de cada opción queda como ayuda al pasar el mouse
      $enlaces.each(function(){
        if (m) $(this).attr('title', $.trim($(this).text())); else $(this).removeAttr('title');
      });
    }
    function plegar(){
      clearTimeout(timer);
      if (!flotando()) return;
      $html.removeClass('t-lateral-flotante');
      $('.main-header .top-nav .navbar-top-links > li.open').removeClass('open').children('.dropdown-toggle').attr('aria-expanded', 'false');
    }
    window.tLateral = {
      // Al abrir un grupo: si la barra está contraída la despliega flotando y devuelve true
      desplegar: function(){
        clearTimeout(timer);
        if (!esEscritorio() || !mini() || flotando()) return false;
        $html.addClass('t-lateral-flotante');
        return true;
      }
    };
    $btn.on('click', function(){
      var m = !mini();
      plegar();
      $html.toggleClass('t-lateral-mini', m);
      try { localStorage.setItem(CLAVE, m ? '1' : '0'); } catch (e) {}
      pintar();
      // Cambió el ancho del contenido: tablas y gráficos se reacomodan
      setTimeout(function(){ try { window.dispatchEvent(new Event('resize')); } catch (e) { $(window).trigger('resize'); } }, 0);
    });
    // Engranaje de configuración (pie de la barra) con la barra contraída: también la despliega
    $('.main-header .top-nav .navbar-top-links > li.dropdown').on('show.bs.dropdown', function(){ window.tLateral.desplegar(); });
    $zona.on('mouseenter', function(){ clearTimeout(timer); })
      .on('mouseleave', function(){
        if (!flotando()) return;
        clearTimeout(timer);
        timer = setTimeout(plegar, 350);
      });
    $(document).on('click', function(e){
      if (flotando() && !$(e.target).closest($zona).length) plegar();
    }).on('keydown', function(e){
      if ((e.key === 'Escape' || e.keyCode === 27) && flotando()) plegar();
    });
    $(window).on('resize', function(){ if (!esEscritorio()) plegar(); });
    pintar();
  });
  </script>
 <script>
  // Aviso de carga: al abrir una página, al salir hacia otra sección y mientras se esperan datos del servidor (AJAX)
  (function(){
    var loader = document.getElementById('page-loader');
    if (!loader) return;
    var texto = loader.querySelector('.page-loader-text');
    var navegando = true, cargandoDatos = false;
    var timerNav = null, timerDatos = null, timerSeguridad = null;

    function pintar(){
      loader.classList.toggle('visible', navegando || cargandoDatos);
      // Mientras se esperan datos también se bloquea la pantalla (velo suave), para no presionar botones a medio cargar
      loader.classList.toggle('solo-datos', !navegando && cargandoDatos);
      texto.textContent = navegando ? 'Cargando…' : 'Cargando datos…';
    }
    function finNavegacion(){
      clearTimeout(timerNav); clearTimeout(timerSeguridad);
      navegando = false; pintar();
    }
    function inicioNavegacion(){
      clearTimeout(timerNav);
      // Pequeño retraso para no parpadear cuando la página responde al instante
      timerNav = setTimeout(function(){ navegando = true; pintar(); }, 150);
      // Por si la navegación no ocurre (descargas, enlaces cancelados), no dejarlo pegado
      clearTimeout(timerSeguridad);
      timerSeguridad = setTimeout(finNavegacion, 15000);
    }

    pintar();
    // v1.30.3: el aviso se quita cuando la página ya está armada (DOMContentLoaded, después de los $(function) de la página),
    // sin esperar imágenes, fotos de productos ni letras: con internet lento eso dejaba "Cargando…" muchos segundos
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function(){ setTimeout(finNavegacion, 50); });
    else setTimeout(finNavegacion, 50);
    window.addEventListener('load', finNavegacion);
    timerSeguridad = setTimeout(finNavegacion, 15000);
    // Al volver con el botón atrás el navegador puede restaurar la página con el aviso visible
    window.addEventListener('pageshow', function(e){ if (e.persisted) finNavegacion(); });

    document.addEventListener('click', function(e){
      if (e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;
      var a = e.target.closest ? e.target.closest('a[href]') : null;
      if (!a) return;
      // Se evalúa después de los manejadores de la página, que pueden cancelar el clic
      setTimeout(function(){
        if (e.defaultPrevented) return;
        var href = a.getAttribute('href') || '';
        if (!href || href.charAt(0) === '#' || /^(javascript|mailto|tel):/i.test(href)) return;
        if (a.target && a.target !== '_self') return;
        if (a.hasAttribute('download') || a.getAttribute('data-toggle')) return;
        if (a.host !== window.location.host) return;
        if (a.pathname === window.location.pathname && a.search === window.location.search && a.hash) return;
        inicioNavegacion();
      }, 0);
    });

    document.addEventListener('submit', function(e){
      var f = e.target;
      setTimeout(function(){
        if (e.defaultPrevented) return;
        if (f.target && f.target !== '_self') return;
        // Formularios que descargan un archivo: la página no cambia, no se muestra el aviso de carga
        if (f.hasAttribute && f.hasAttribute('data-sin-carga')) return;
        inicioNavegacion();
      }, 0);
    });

    // Peticiones AJAX (listados, pestañas de la caja, reportes): solo se avisa si tardan más de 200 ms
    if (window.jQuery) {
      // Tope de seguridad: si un callback de la página lanza un error, jQuery 1.12 no dispara ajaxStop y el
      // aviso (que ahora bloquea la pantalla) quedaría pegado. Se libera tras 12 s sin nuevas peticiones.
      var timerTope = null;
      function liberarDatos(){ clearTimeout(timerDatos); clearTimeout(timerTope); cargandoDatos = false; pintar(); }
      jQuery(document).ajaxStart(function(){
        clearTimeout(timerDatos);
        timerDatos = setTimeout(function(){ cargandoDatos = true; pintar(); }, 200);
      }).ajaxSend(function(){
        clearTimeout(timerTope);
        timerTope = setTimeout(liberarDatos, 12000);
      }).ajaxStop(liberarDatos);
    }
  })();
 </script>

 <script>
  $(function() {
    function ajustarTablasResponsivas() {
      if (!window.jQuery || !$.fn || !$.fn.dataTable) {
        return;
      }

      var tableNodes = $.fn.dataTable.tables({ visible: true, api: false });
      if (!tableNodes || !tableNodes.length) {
        return;
      }

      $(tableNodes).each(function() {
        if ($.fn.dataTable.isDataTable(this)) {
          $(this).DataTable().columns.adjust();
        }
      });
    }

    $(window).on('resize orientationchange', ajustarTablasResponsivas);
    $(document).on('shown.bs.modal', '.modal', ajustarTablasResponsivas);

    setTimeout(ajustarTablasResponsivas, 250);
  });
 </script>


 <script>
  // Alinea las columnas de todas las tablas según su contenido: cifras a la derecha, texto a la izquierda,
  // columnas de botones/estados al centro. Reemplaza el "text-center" que traían las columnas.
  (function () {
    var reNum = /^[-+]?\s*\$?\s*[-+]?[\d.,]+\s*(%|\$|[a-zA-Z]{0,3})?$/;
    function tipo(celdas) {
      var n = 0, num = 0, ctl = 0;
      celdas.each(function () {
        var $c = $(this);
        if ($c.find('.btn, button, input, select, .label, .badge, .fa, .glyphicon').length && $.trim($c.clone().find('i,.fa,.glyphicon').remove().end().text()).length <= 12) { ctl++; n++; return; }
        var t = $.trim($c.text());
        if (!t) return;
        n++;
        if (reNum.test(t) && /\d/.test(t) && t.length < 20) num++;
      });
      if (!n) return 't';
      if (ctl / n >= .6) return 'c';
      return num / n >= .7 ? 'n' : 't';
    }
    function marca($th, $celdas, clase) {
      var todos = $th.add($celdas);
      todos.removeClass('col-t col-n col-c text-center text-right text-left').addClass(clase);
    }
    function alinear(tabla) {
      var $t = $(tabla);
      if ($t.is('.no-alinear, .inv-cierre-tabla') || $t.closest('.no-alinear').length) return;
      if ($.fn.dataTable && $.fn.dataTable.isDataTable(tabla)) {
        var api = $t.DataTable();
        api.columns().every(function () {
          var $celdas = $(this.nodes());
          var $h = $(this.header());
          var $hClon = $t.closest('.dataTables_scroll').find('.dataTables_scrollHead th').eq(this.index());
          marca($h.add($hClon), $celdas, tipo($celdas));
        });
        return;
      }
      var $filas = $t.children('tbody').children('tr');
      var cols = $t.children('thead').find('tr').last().children('th,td').length;
      for (var i = 0; i < cols; i++) {
        var $celdas = $filas.children(':nth-child(' + (i + 1) + ')').filter(function () { return !this.colSpan || this.colSpan === 1; });
        marca($t.children('thead').find('tr').last().children().eq(i), $celdas, tipo($celdas));
      }
    }
    var timer;
    function todas() {
      clearTimeout(timer);
      timer = setTimeout(function () { $('table.table, table.dataTable').each(function () { try { alinear(this); } catch (e) {} }); }, 60);
    }
    $(document).on('draw.dt init.dt', todas).on('shown.bs.modal', todas).ajaxStop(todas);
    $(todas);
  })();
 </script>

<?php
// Migraciones de la base de la empresa: una vez por sesión y empresa, en segundo plano (Login::postLoginWarmup).
$__ciMig =& get_instance();
$__ciMig->load->library('MigracionesResto');
if ($__ciMig->migracionesresto->revisarEnSesion()) : ?>
 <script>
  // global: false para que no muestre el aviso de carga ni bloquee la pantalla mientras corre
  $(function () {
    $.ajax({ url: '<?php echo base_url() ?>index.php/Login/postLoginWarmup', type: 'POST', dataType: 'json', global: false })
      .done(function (r) {
        if (!r || !window.toastr) return;
        if (r.aviso) toastr.warning(r.aviso);
        else if (r.aplicadas > 0 && <?php echo ((int)$this->session->userdata('id_rol') === 1) ? 'true' : 'false' ?>) {
          toastr.success('Se actualizó la base de la empresa (' + r.aplicadas + (r.aplicadas === 1 ? ' migración' : ' migraciones') + '). Recarga la página para ver los cambios.');
        }
      });
  });
 </script>
<?php endif; ?>
<?php
// v1.27.0: respaldo automático del día (Login::respaldoDiario), una vez por día y empresa en cada sesión, en segundo plano.
// Espera 20 segundos después de cargar la página para no competir con lo que el usuario está abriendo.
// Nunca desde la Caja: en un equipo local con Windows, php -S atiende de a una petición y la impresión esperaría.
$__resp = date('Y-m-d') . '|' . $__ciMig->session->userdata('db_server') . '|' . $__ciMig->session->userdata('bd_name');
if (strtolower($__ciMig->router->fetch_class()) !== 'caja' && strlen((string)$__ciMig->session->userdata('nombre_usuario')) > 0
    && $__ciMig->session->userdata('respaldo_revisado') !== $__resp) : ?>
 <script>
  setTimeout(function () {
    if (window.jQuery) $.ajax({ url: '<?php echo base_url() ?>index.php/Login/respaldoDiario', type: 'POST', dataType: 'json', global: false });
  }, 20000);
 </script>
<?php endif; ?>
</body>

</html>
