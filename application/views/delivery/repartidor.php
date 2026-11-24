<style>
.rep-wrap{max-width:760px;margin:18px auto;padding:0 12px}.rep-head{display:flex;align-items:center;justify-content:space-between;gap:8px}.rep-pedido{background:#fff;border:1px solid #e1e5e8;border-radius:12px;padding:16px;margin:12px 0;box-shadow:0 2px 8px #0000000a}.rep-num{font-size:18px;font-weight:700}.rep-estado{display:inline-block;padding:4px 9px;border-radius:20px;background:#edf4ef;color:#16783d;font-weight:600}.rep-dato{margin:7px 0}.rep-acciones{display:flex;gap:8px;margin-top:14px}.rep-acciones .btn{flex:1;white-space:normal}.rep-vacio{text-align:center;color:#69727d;padding:40px 15px;background:#fff;border-radius:12px}.rep-refresh{position:sticky;bottom:10px;width:100%;margin-top:8px}@media(max-width:480px){.rep-wrap{margin:8px auto}.rep-pedido{padding:14px}.rep-acciones{flex-direction:column}.rep-acciones .btn{width:100%}}
.rep-rendicion{background:#fff;border:1px solid #dfe4e8;border-radius:12px;padding:16px;margin:12px 0}.rep-saldo{font-size:24px;font-weight:700;color:#187a3b}.rep-rend-hist{font-size:13px;padding-left:18px}
</style>
<div class="content-header"><div class="header-icon"><i class="pe-7s-car"></i></div><div class="header-title"><h1>Mis pedidos Delivery</h1><small>Portal de repartidor</small></div></div>
<main class="rep-wrap" aria-live="polite">
  <div class="rep-head"><strong id="repActualizado">Actualizando pedidos…</strong><button id="repRecargar" class="btn btn-default"><i class="fa fa-refresh"></i> Actualizar</button></div>
  <section class="rep-rendicion"><h3>Mi rendición</h3><div>Dinero pendiente de rendir</div><div id="repSaldo" class="rep-saldo">—</div>
    <div class="rep-acciones"><input id="repMontoRendir" type="number" min="1" step="1" class="form-control" placeholder="Monto que entregas"><button id="repPresentar" class="btn btn-primary"><i class="fa fa-money"></i> Presentar rendición parcial</button></div>
    <label style="margin-top:8px;width:100%">Observación <input id="repObservacionRendir" maxlength="250" class="form-control" placeholder="Opcional"></label>
    <small class="text-muted">La rendición queda pendiente hasta que un cajero autorizado cuente el dinero. Una diferencia seguirá visible.</small><div id="repHistorial"></div>
  </section>
  <div id="repLista"></div>
  <p class="text-muted text-center">La lista se actualiza automáticamente cada 8 segundos.</p>
</main>
<script>
(function(){
  var endpoint='<?php echo base_url(); ?>index.php/Repartidor/';
  var tokenRendicion=null;
  function tokenAccion(){if(window.crypto&&crypto.randomUUID)return crypto.randomUUID();var a='';for(var i=0;i<16;i++)a+=Math.floor(Math.random()*256).toString(16).padStart(2,'0');return a;}
  function h(v){return $('<div>').text(v==null?'':String(v)).html();}
  function pintar(pedidos){
    var $l=$('#repLista').empty();
    if(!pedidos.length){$l.html('<div class="rep-vacio"><i class="fa fa-motorcycle fa-2x"></i><p>No tienes pedidos asignados.</p></div>');return;}
    $.each(pedidos,function(_,p){
      var id=Number(p.id_delivery), estado=p.estado_entrega, nombreEstado={asignado:'Nuevo · responde la asignación',aceptado:'Aceptado · falta retirar',retirado:'Retirado · en camino'}[estado]||estado;
      var acciones='';
      if(estado==='asignado') acciones='<div class="rep-acciones"><button class="btn btn-success" data-accion="aceptar" data-id="'+id+'"><i class="fa fa-check"></i> Aceptar</button><button class="btn btn-danger" data-accion="rechazar" data-id="'+id+'"><i class="fa fa-times"></i> Rechazar</button></div>';
      if(estado==='aceptado') acciones='<div class="rep-acciones"><button class="btn btn-primary" data-accion="retirar" data-id="'+id+'"><i class="fa fa-shopping-bag"></i> Confirmar retiro</button></div>';
      if(estado==='retirado') acciones=p.estado_pago==='verificado'
        ? '<div class="rep-form" data-id="'+id+'" data-pagado="1"><button class="btn btn-success btn-block" data-accion="entregar" data-id="'+id+'"><i class="fa fa-check"></i> Confirmar entrega</button><button class="btn btn-default btn-block" style="margin-top:6px" data-accion="no_entregado" data-id="'+id+'">No se pudo entregar</button></div>'
        : '<div class="rep-form" data-id="'+id+'"><label>Resultado de entrega</label><div class="rep-acciones"><select class="form-control" data-medio><option value="efectivo">Efectivo</option><option value="transferencia">Transferencia al restaurante</option><option value="tarjeta">Tarjeta del restaurante</option><option value="prepago">Prepago</option><option value="plataforma">Plataforma</option><option value="otro">Otro</option><option value="no_pagado">Sin pago recibido</option></select><input class="form-control" data-monto type="number" min="0" step="1" value="'+Number(p.total||0)+'" aria-label="Monto recibido"></div><input class="form-control" data-referencia maxlength="120" placeholder="Referencia de pago" style="margin-top:8px"><label style="margin-top:8px">Respaldo de pago (JPG, PNG o PDF; máximo 5 MB)</label><input class="form-control" data-evidencia type="file" accept="image/jpeg,image/png,application/pdf"><small class="text-muted">Obligatorio para pagos electrónicos. El efectivo queda bajo tu custodia hasta rendirlo en caja.</small><button class="btn btn-success btn-block" style="margin-top:8px" data-accion="entregar" data-id="'+id+'"><i class="fa fa-check"></i> Confirmar entrega y declarar cobro</button><button class="btn btn-default btn-block" style="margin-top:6px" data-accion="no_entregado" data-id="'+id+'">No se pudo entregar</button></div>';
      if(estado==='no_entregado') acciones='<p class="text-warning"><i class="fa fa-exclamation-triangle"></i> Entrega no completada. Contacta al encargado para resolver la incidencia.</p>';
      $l.append('<article class="rep-pedido"><div class="rep-head"><span class="rep-num">Delivery N° '+id+'</span><span class="rep-estado">'+h(nombreEstado)+'</span></div>'+
        '<div class="rep-dato"><b>Recibe:</b> '+h(p.nombre)+'</div><div class="rep-dato"><a href="tel:'+h(p.telefono)+'"><i class="fa fa-phone"></i> '+h(p.telefono)+'</a></div>'+
        '<div class="rep-dato"><b>Dirección:</b> '+h(p.direccion)+'</div>'+(p.referencia?'<div class="rep-dato"><b>Referencia:</b> '+h(p.referencia)+'</div>':'')+
        (p.observacion?'<div class="rep-dato"><b>Observación:</b> '+h(p.observacion)+'</div>':'')+
        (p.items&&p.items.length?'<div class="rep-dato"><b>Pedido:</b><ul>'+p.items.map(function(it){return '<li>'+h(it.cantidad)+' × '+h(it.nombre)+(it.agregados&&it.agregados.length?' · '+h(it.agregados.join(', ')):'')+(it.nota?' <small>('+h(it.nota)+')</small>':'')+'</li>';}).join('')+'</ul></div>':'')+
        '<div class="rep-dato"><b>Total:</b> '+h(p.total)+'</div>'+acciones+'</article>');
    });
  }
  function cargarRendicion(){ $.getJSON(endpoint+'rendicion').done(function(r){var x=r.rendicion||{};$('#repSaldo').text('$ '+Number(x.saldo||0).toLocaleString('es-CL'));var $h=$('#repHistorial').empty();if(x.rendiciones&&x.rendiciones.length){$h.append('<h4>Rendiciones recientes</h4><ul class="rep-rend-hist">'+x.rendiciones.map(function(a){return '<li>'+h(a.fecha_presentada)+' · '+h(a.estado)+' · declarado $'+Number(a.monto_declarado||0).toLocaleString('es-CL')+(a.monto_contado!==null?' · contado $'+Number(a.monto_contado||0).toLocaleString('es-CL'):'')+(Number(a.diferencia||0)?' · diferencia $'+Number(a.diferencia).toLocaleString('es-CL'):'')+'</li>';}).join('')+'</ul>');}}); }
  function cargar(){ $.getJSON(endpoint+'pedidos').done(function(r){var foco=document.activeElement;var editando=foco&&foco.closest&&foco.closest('.rep-form');if(!editando)pintar(r.pedidos||[]);$('#repActualizado').text('Actualizado '+new Date().toLocaleTimeString());}).fail(function(){ $('#repActualizado').text('No se pudo actualizar. Revisa la conexión.'); });cargarRendicion(); }
  $('#repRecargar').on('click',cargar);
  $('#repPresentar').on('click',function(){var monto=Number($('#repMontoRendir').val());if(!Number.isInteger(monto)||monto<=0){if(window.toastr)toastr.warning('Ingresa un monto entero válido');return;}if(!tokenRendicion)tokenRendicion=tokenAccion();var b=$(this).prop('disabled',true);$.ajax({url:endpoint+'presentarRendicion',method:'POST',contentType:'application/json',data:JSON.stringify({monto:monto,accion_id:tokenRendicion,observacion:$('#repObservacionRendir').val()}),dataType:'json'}).done(function(){tokenRendicion=null;$('#repMontoRendir,#repObservacionRendir').val('');if(window.toastr)toastr.success('Rendición presentada al cajero');cargarRendicion();}).fail(function(x){var e=x.responseJSON&&x.responseJSON.error||'No se pudo presentar la rendición';if(window.toastr)toastr.error(e);else alert(e);}).always(function(){b.prop('disabled',false);});});
  $('#repLista').on('click','[data-accion]',function(){
    var b=$(this), accion=b.data('accion'), datos={id_delivery:Number(b.data('id')),accion:accion};
    if(accion==='rechazar'){var motivo=window.prompt('Motivo del rechazo:');if(!motivo||!motivo.trim())return;datos.motivo=motivo.trim();}
    if(accion==='entregar'||accion==='no_entregado'){
      var $form=b.closest('.rep-form'), idempotencia=$form.data('accion-id');
      if(!idempotencia){idempotencia=tokenAccion();$form.data('accion-id',idempotencia);}
      datos.accion_id=idempotencia;datos.resultado=accion==='entregar'?'entregado':'no_entregado';
      datos.motivo=accion==='no_entregado'?(window.prompt('Motivo por el que no se pudo entregar:')||'').trim():'';
      if(accion==='no_entregado'&&!datos.motivo)return;
      datos.medio_pago=accion==='entregar'&&!$form.data('pagado')?$form.find('[data-medio]').val():'no_pagado';
      datos.monto=accion==='entregar'&&datos.medio_pago!=='no_pagado'?Number($form.find('[data-monto]').val()):0;
      datos.referencia=accion==='entregar'?$form.find('[data-referencia]').val():'';
      if(!Number.isInteger(datos.monto)||datos.monto<0){if(window.toastr)toastr.warning('Ingresa un monto entero válido');return;}
      var archivo=accion==='entregar'?$form.find('[data-evidencia]')[0].files[0]:null;
      if(datos.monto>0&&['transferencia','tarjeta','prepago','plataforma','otro'].indexOf(datos.medio_pago)>=0&&!archivo){if(window.toastr)toastr.warning('Adjunta el respaldo del pago electrónico');return;}
    }
    b.prop('disabled',true);
    var registrar=function(){return $.ajax({url:endpoint+(accion==='entregar'||accion==='no_entregado'?'entrega':'accion'),method:'POST',contentType:'application/json',data:JSON.stringify(datos),dataType:'json'});};
    var enviar=archivo? (function(){var fd=new FormData();fd.append('id_delivery',datos.id_delivery);fd.append('evidencia',archivo);return $.ajax({url:endpoint+'subirEvidencia',method:'POST',data:fd,processData:false,contentType:false,dataType:'json'}).done(function(r){datos.id_evidencia=Number(r.id_evidencia)||0;});})():$.Deferred().resolve().promise();
    enviar.done(function(){registrar().done(function(){if(window.toastr)toastr.success('Acción registrada');cargar();}).fail(function(x){var e=x.responseJSON&&x.responseJSON.error||'No se pudo registrar';if(window.toastr)toastr.error(e);else alert(e);});})
      .fail(function(x){var e=x.responseJSON&&x.responseJSON.error||'No se pudo subir el respaldo';if(window.toastr)toastr.error(e);else alert(e);})
      .always(function(){b.prop('disabled',false);});
  });
  cargar();setInterval(cargar,8000);
})();
</script>
