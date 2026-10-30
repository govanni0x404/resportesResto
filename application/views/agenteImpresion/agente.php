<?php $h = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }; ?>
<div class="content-header">
  <div class="header-icon"><i class="pe-7s-print"></i></div>
  <div class="header-title">
    <h1>Agente De Impresion</h1>
    <small>Conecta el sistema con las impresoras de Windows de cada equipo</small>
    <ol class="breadcrumb">
      <li><a href="<?php echo base_url() ?>index.php/DestinoDeImpresion"><i class="pe-7s-home"></i>Destino De Impresion</a></li>
      <li class="active">Agente De Impresion</li>
    </ol>
  </div>
</div>
<div class="container-padding">
  <div class="row"><div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading"><h3 class="panel-title"><i class="fa fa-info-circle"></i> Cómo funciona</h3></div>
      <div class="panel-body">
        <ol>
          <li>Agrega aquí cada equipo Windows que tenga impresoras (caja, cocina, barra) y descarga su instalador.</li>
          <li>Ejecuta el instalador <b>en ese equipo</b> (pide permisos de administrador): deja el agente funcionando y se inicia solo con Windows.</li>
          <li>En <b>Destino de impresión</b>, en <b>Ruta Windows</b> escribe el nombre de la impresora tal como aparece en el Panel de control (ej: <code>COCINA</code>). También sirve <code>//equipo/COCINA</code>.</li>
          <li>Con al menos un equipo conectado, las comandas van al agente que tiene esa impresora. Sin agentes conectados se intenta el envío directo desde el servidor.</li>
        </ol>
      </div>
    </div>

    <div class="panel panel-default">
      <div class="panel-heading"><h3 class="panel-title"><i class="fa fa-desktop"></i> Equipos</h3></div>
      <div class="panel-body">
        <table class="table table-bordered no-alinear" id="tablaAgentes">
          <thead><tr><th>Equipo</th><th>Estado</th><th>Último contacto</th><th>Impresoras que ve</th><th style="width:90px"></th></tr></thead>
          <tbody>
          <?php if (!$agentes) : ?><tr><td colspan="5" class="text-center">Aún no hay equipos.</td></tr><?php endif; ?>
          <?php foreach ($agentes as $a) : $imp = json_decode($a->impresoras, true); ?>
            <tr>
              <td><b><?php echo $h($a->nombre); ?></b></td>
              <td><?php echo $a->conectado === 't' || $a->conectado === true || $a->conectado === '1' ? '<span class="label label-success">Conectado</span>' : '<span class="label label-default">Sin conexión</span>'; ?></td>
              <td><?php echo $h($a->ultimo ?: 'nunca'); ?></td>
              <td><?php echo $h(is_array($imp) ? implode(' · ', $imp) : ''); ?></td>
              <td><button type="button" class="btn btn-danger btn-sm btn-revocar" data-id="<?php echo (int)$a->id_agente; ?>"><i class="fa fa-trash"></i> Quitar</button></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <form id="formAgente" class="form-inline" onsubmit="return false">
          <input type="text" id="nombreAgente" class="form-control" maxlength="60" placeholder="Nombre del equipo (ej: Caja)">
          <button type="button" id="btnAgregarAgente" class="btn btn-success"><i class="fa fa-plus"></i> Agregar equipo</button>
        </form>
        <div id="resultadoAgente" style="display:none; margin-top:14px" class="alert alert-info">
          <p><b>Equipo creado.</b> Descarga el instalador y ejecútalo en ese equipo. La clave solo se muestra ahora; si la pierdes, quita el equipo y crea otro.</p>
          <form method="post" action="<?php echo base_url() ?>index.php/AgenteImpresion/instalador">
            <input type="hidden" name="token" id="tokenAgente">
            <button type="submit" class="btn btn-primary"><i class="fa fa-download"></i> Descargar instalador (.bat)</button>
          </form>
        </div>
      </div>
    </div>

    <div class="panel panel-default">
      <div class="panel-heading"><h3 class="panel-title"><i class="fa fa-list"></i> Últimos trabajos</h3></div>
      <div class="panel-body">
        <table class="table table-bordered no-alinear">
          <thead><tr><th>Fecha</th><th>Destino</th><th>Equipo</th><th>Estado</th><th>Detalle</th></tr></thead>
          <tbody>
          <?php if (!$ultimos) : ?><tr><td colspan="5" class="text-center">Sin trabajos todavía.</td></tr><?php endif; ?>
          <?php foreach ($ultimos as $u) : ?>
            <tr>
              <td><?php echo $h($u->creado); ?></td><td><?php echo $h($u->destino); ?></td><td><?php echo $h($u->agente); ?></td>
              <td><?php echo $u->estado === 'impreso' ? '<span class="label label-success">Impreso</span>' : ($u->estado === 'error' ? '<span class="label label-danger">Error</span>' : '<span class="label label-warning">' . $h($u->estado) . '</span>'); ?></td>
              <td><?php echo $h($u->error); ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div></div>
</div>
<script>
window.addEventListener('load', function () {
  var base = '<?php echo base_url() ?>index.php/AgenteImpresion/';
  $('#btnAgregarAgente').on('click', function () {
    var nombre = $.trim($('#nombreAgente').val());
    if (!nombre) { $('#nombreAgente').focus(); return; }
    $.post(base + 'crear', { nombre: nombre }, null, 'json').done(function (r) {
      $('#tokenAgente').val(r.token);
      $('#resultadoAgente').show();
    }).fail(function (x) { alert((x.responseJSON && x.responseJSON.error) || 'No se pudo crear el equipo'); });
  });
  $('#tablaAgentes').on('click', '.btn-revocar', function () {
    if (!confirm('¿Quitar este equipo? Su agente dejará de imprimir.')) return;
    $.post(base + 'revocar', { id_agente: $(this).data('id') }, null, 'json').always(function () { location.reload(); });
  });
});
</script>
