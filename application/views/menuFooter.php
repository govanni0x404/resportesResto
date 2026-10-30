</div> <!-- /.container -->
</div> <!-- /.content-wrapper -->
<!-- start footer -->


<footer class="main-footer">
	<div class="container">
		<div class="pull-right hidden-xs"> <b>Version</b>
        <?php echo app_version_short(); ?>
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
<script src="https://cdn.datatables.net/select/1.3.3/js/dataTables.select.min.js"></script>
<!-- <script src="<?php echo base_url() ?>assets/plugins/datatables/buttons.bootstrap.js"></script> -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.js"></script>
<!-- <script type="text/javascript" src="https://cdn.datatables.net/responsive/2.2.7/js/dataTables.responsive.min.js"></script> -->

<!-- <script src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script> -->
<script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script src="<?php echo base_url() ?>assets/plugins/date-range-picker/daterangepicker.js"></script>
<!-- Start Theme label Script
        =====================================================================-->
<!-- Dashboard js -->
<script src="<?php echo base_url() ?>assets/dist/js/dashboard.js"></script>
<!-- Redirect js -->
<script src="<?php echo base_url() ?>/assets/redirect/jquery.redirect.js"></script>
<!-- Sweet Alert -->
<script src="<?php echo base_url() ?>assets/plugins/sweetalert/sweetalert.min.js"></script>

<!-- End Theme label Script
        =====================================================================-->
<script src="https://cdn.datatables.net/plug-ins/1.10.20/api/sum().js"></script>


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
            opts = opts || {};
            var $box   = $('#appAlert');
            var $title = $('#appAlertTitle');
            var $body  = $('#appAlertBody');
            var $img   = $('#appAlertImg');

            // limpiar clases de estado y de posición
            var states = 'alert--success alert--error alert1 alert2 alert3 alert4 alert5 alert6 alert7 alert8 alert9';
            var positions = 'kode-alert-top-right kode-alert-top-left kode-alert-bottom-right kode-alert-bottom-left kode-alert-top kode-alert-bottom';

            $box.removeClass(states + ' ' + positions);

            // posición
            var posMap = {
              'top-right':'kode-alert-top-right',
              'top-left':'kode-alert-top-left',
              'bottom-right':'kode-alert-bottom-right',
              'bottom-left':'kode-alert-bottom-left',
              'top':'kode-alert-top',
              'bottom':'kode-alert-bottom'
            };
            var posClass = posMap[opts.position || 'top-right'] || 'kode-alert-top-right';
            $box.addClass(posClass);

            // color / estado
            var stateClass = opts.paletteClass ? opts.paletteClass :
                             (type === 'success' ? 'alert--success' : 'alert--error');
            $box.addClass(stateClass);

            // contenido
            $title.text(opts.title || (type === 'success' ? 'Operación exitosa' : 'Ocurrió un error'));
            $body.html(body || '');

            // imagen opcional
            if (opts.image){ $img.attr('src', opts.image).show(); } else { $img.hide(); }

            // mostrar
            $box.stop(true,true).fadeIn(200);

            // autocerrar
            var duration = (typeof opts.duration === 'number') ? opts.duration : 4000;
            if (duration > 0){
              clearTimeout($box.data('hideTimer'));
              var t = setTimeout(function(){ $box.fadeOut(200); }, duration);
              $box.data('hideTimer', t);
            }
          }

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
      var abrir = !$li.hasClass('t-abierto');
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

</body>

</html>
