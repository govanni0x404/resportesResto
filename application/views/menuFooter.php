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
<!-- <script src="https://cdn.datatables.net/1.13.3/js/jquery.dataTables.js"></script> -->
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

</body>

</html>
