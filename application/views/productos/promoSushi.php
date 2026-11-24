<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!-- v1.25.0: Promos de sushi armables (Productos > botón "Promos Sushi"). Modelo: Mpromosushi. En la Caja: js.php abrirSushi -->
<style>
#ModalPromosSushi .modal-dialog { width: 1320px; max-width: calc(100vw - 32px); margin-top: 16px; }   /* v1.32.3: más ancha, menos larga */
#ModalPromosSushi .modal-content { border: 0; border-radius: 14px; overflow: hidden; box-shadow: 0 24px 60px rgba(17, 24, 39, .28); }
#ModalPromosSushi .modal-footer { padding: 12px 20px; background: #fff; border-top: 1px solid var(--t-borde, #E5E7EB); }
#ModalPromosSushi .modal-footer .btn { min-width: 120px; height: 40px; border-radius: 9px; font-weight: 700; }
.ps-elegir { display: flex; gap: 10px; align-items: center; }
/* v1.26.0: arriba, promos por pieza o por roll */
.ps-modo { display: inline-flex; padding: 4px; margin-bottom: 12px; border-radius: 12px; background: var(--t-fondo-alt, #F3F4F6); }
.ps-modo button { border: 0; background: transparent; padding: 8px 18px; border-radius: 9px; font-weight: 800; color: var(--t-texto-suave, #4B5563); }
.ps-modo button.on { background: #fff; color: var(--t-primario, #15803D); box-shadow: 0 1px 4px rgba(17, 24, 39, .12); }
.ps-modo button b { margin-left: 6px; padding: 0 7px; border-radius: 10px; background: rgba(0, 0, 0, .07); font-size: 12px; }
.ps-solo-rol { display: none; }
#ModalPromosSushi.modo-rol .ps-solo-rol { display: block; }
#ModalPromosSushi.modo-rol .ps-solo-pieza { display: none; }
.ps-elegir select { flex: 1; height: 42px; font-size: 15px; font-weight: 600; }
.ps-elegir .btn { height: 42px; border-radius: 9px; font-weight: 700; white-space: nowrap; }
.ps-form { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px 12px; }
.ps-form .ps-ancho { grid-column: span 2; }
.ps-form label { display: block; margin-bottom: 4px; font-size: 12px; font-weight: 700; color: var(--t-texto-suave, #4B5563); }
.ps-form label small { font-weight: 500; color: var(--t-texto-tenue, #6B7280); }
.ps-form .form-control { height: 38px; }
.ps-form .ps-check { display: flex; align-items: center; gap: 8px; margin-top: 24px; font-weight: 600; }
@media (max-width: 760px) { .ps-form { grid-template-columns: 1fr 1fr; } .ps-form .ps-ancho { grid-column: span 2; } }
.ps-pie-form { display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-top: 12px; }
.ps-pie-form small { color: var(--t-texto-tenue, #6B7280); }
.ps-pie-form .btn { height: 38px; border-radius: 9px; font-weight: 700; }
.ps-bloqueado { opacity: .5; pointer-events: none; }
.ps-tabs { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 12px; }
.ps-tab { border: 1px solid var(--t-borde, #E5E7EB); background: #fff; border-radius: 20px; padding: 7px 14px; font-weight: 700; cursor: pointer; }
.ps-tab.on { background: var(--t-primario, #15803D); border-color: var(--t-primario, #15803D); color: #fff; }
.ps-tab b { display: inline-block; min-width: 20px; margin-left: 4px; padding: 0 6px; border-radius: 10px; background: rgba(0, 0, 0, .08); font-size: 12px; }
.ps-ayuda-tipo { margin: 0 0 10px; font-size: 13px; color: var(--t-texto-suave, #4B5563); }
.ps-lista { display: flex; flex-direction: column; gap: 6px; max-height: 260px; overflow-y: auto; margin-bottom: 12px; }
.ps-op { display: flex; align-items: center; gap: 10px; padding: 7px 10px; border: 1px solid var(--t-borde, #E5E7EB); border-radius: 10px; background: #fff; }
.ps-op .nombre { flex: 1; font-weight: 700; }
.ps-op .nombre small { font-weight: 500; color: var(--t-texto-tenue, #6B7280); }
.ps-op .ps-valor { display: flex; align-items: center; gap: 4px; font-size: 12px; color: var(--t-texto-suave, #4B5563); }
.ps-op .ps-valor input { width: 90px; height: 30px; text-align: right; }
.ps-op .btn { padding: 3px 9px; }
.ps-vacio { padding: 16px; text-align: center; color: var(--t-texto-tenue, #6B7280); border: 1px dashed var(--t-borde, #E5E7EB); border-radius: 10px; }
.ps-agregar { display: grid; grid-template-columns: minmax(0, 1fr) 110px auto; gap: 8px; align-items: end; }
.ps-agregar + .ps-agregar { margin-top: 8px; }
.ps-agregar label { display: block; margin-bottom: 3px; font-size: 12px; font-weight: 700; color: var(--t-texto-suave, #4B5563); }
.ps-agregar .form-control, .ps-agregar .btn { height: 38px; }
.ps-agregar .btn { border-radius: 9px; font-weight: 700; white-space: nowrap; }
@media (max-width: 600px) { .ps-agregar { grid-template-columns: 1fr; } }
/* v1.32.0: agregar uno, varios o toda una familia */
.ps-picker { border: 1px solid var(--t-borde, #E5E7EB); border-radius: 10px; overflow: hidden; margin-bottom: 8px; }
.ps-picker-barra { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; padding: 8px; background: var(--t-fondo-alt, #F9FAFB); border-bottom: 1px solid var(--t-borde, #E5E7EB); }
.ps-picker-barra .ps-buscar { position: relative; flex: 1 1 220px; }
.ps-picker-barra .ps-buscar i { position: absolute; left: 10px; top: 11px; color: var(--t-texto-tenue, #6B7280); }
.ps-picker-barra .ps-buscar input { padding-left: 30px; height: 36px; }
.ps-picker-barra select { flex: 0 1 200px; height: 36px; }
.ps-picker-lista { max-height: 210px; overflow-y: auto; padding: 4px 0; }
.ps-picker-lista label { display: flex; align-items: center; gap: 8px; margin: 0; padding: 6px 12px; font-weight: 600; cursor: pointer; }
.ps-picker-lista label:hover { background: var(--t-primario-suave, #E8F5EC); }
.ps-picker-lista label small { font-weight: 500; color: var(--t-texto-tenue, #6B7280); }
.ps-picker-lista .ps-picker-vacio { padding: 12px; text-align: center; color: var(--t-texto-tenue, #6B7280); }
.ps-picker-pie { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; justify-content: space-between; padding: 8px; border-top: 1px solid var(--t-borde, #E5E7EB); }
.ps-picker-pie .ps-pie-valor { display: flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 700; color: var(--t-texto-suave, #4B5563); }
.ps-picker-pie .ps-pie-valor input { width: 100px; height: 34px; text-align: right; }
.ps-picker-pie .btn { height: 34px; border-radius: 9px; font-weight: 700; }
.ps-op .ps-precio-env { display: flex; align-items: center; gap: 4px; font-size: 12px; font-weight: 700; color: var(--t-primario, #15803D); }
.ps-op .ps-precio-env input { width: 100px; height: 30px; text-align: right; }
.ps-fija { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 12px; margin-top: 12px; padding: 10px 12px; border-radius: 10px; background: var(--t-fondo-alt, #F3F4F6); font-size: 13px; }
.ps-fija.on { background: var(--t-primario-suave, #E8F5EC); }
.ps-fija .btn { height: 36px; border-radius: 8px; font-weight: 700; margin-left: auto; }
.ps-fija .ps-fija-txt { flex: 1 1 320px; line-height: 1.45; }
.ps-form label a.ps-nueva-fam { font-weight: 700; margin-left: 6px; cursor: pointer; }
/* v1.32.1: dos partes (como la ficha de la Carta) y el límite de cada sección */
#ModalPromosSushi .lm-cuerpo + .ps-secciones { padding-top: 0; }
#ModalPromosSushi .ps-secciones + .lm-cuerpo { padding-top: 14px; }
.ps-seccion { display: none; }
.ps-seccion.activa { display: block; }
/* v1.32.3 (pedido del usuario): a lo ancho. Parte 1: datos a la izquierda y "promo fija" a la derecha.
   Parte 2: lo que ya tiene la promo a la izquierda y "Agregar" a la derecha. En pantallas chicas, una columna. */
@media (min-width: 992px) {
    .ps-seccion.activa { display: grid; gap: 14px; align-items: start; }
    .ps-seccion[data-ps-seccion="datos"].activa { grid-template-columns: minmax(0, 2fr) minmax(260px, 1fr); }
    .ps-seccion[data-ps-seccion="elegir"].activa { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); }
    .ps-seccion.activa > .lm-tarjeta + .lm-tarjeta { margin-top: 0; }
    .ps-seccion.activa > .lm-tarjeta { align-self: start; }
    #ModalPromosSushi .ps-arriba { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; }
    #ModalPromosSushi .ps-modo { margin-bottom: 0; flex: 0 0 auto; }
    #ModalPromosSushi .ps-elegir { flex: 1 1 auto; }
    .ps-seccion[data-ps-seccion="datos"] .ps-form { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .ps-seccion[data-ps-seccion="elegir"] .ps-lista { max-height: 430px; }
    .ps-seccion[data-ps-seccion="elegir"] .ps-picker-lista { max-height: 250px; }
    .ps-seccion[data-ps-seccion="elegir"] .ps-agregar { grid-template-columns: minmax(0, 1fr) 100px auto; }
}
.ps-fija { flex-direction: column; align-items: stretch !important; margin-top: 0 !important; }
.ps-fija .btn { margin-left: 0 !important; align-self: flex-start; }
.ps-fija .ps-fija-txt { flex: 0 0 auto !important; }
.ps-secciones .prod-sec-btn:disabled { opacity: .45; cursor: not-allowed; }
.ps-limite { display: flex; flex-wrap: wrap; align-items: center; gap: 6px 10px; margin: 0 0 12px; padding: 10px 12px; border-radius: 10px; background: var(--t-primario-suave, #E8F5EC); }
.ps-limite label { margin: 0; font-weight: 800; }
.ps-limite input { width: 80px; height: 34px; text-align: center; font-weight: 800; }
.ps-limite small { color: var(--t-texto-suave, #4B5563); }
.ps-copiar { display: flex; gap: 8px; align-items: center; margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--t-borde-suave, #EEF0F3); }
.ps-copiar select { flex: 1; height: 36px; }
.ps-copiar .btn { height: 36px; border-radius: 9px; font-weight: 700; white-space: nowrap; }
</style>

<div id="ModalPromosSushi" class="modal fade t-modal-form" role="dialog" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="lm-cab">
                <div class="lm-cab-icono"><i class="fa fa-th"></i></div>
                <div>
                    <h4>Promos de Sushi</h4>
                    <small id="psSubtitulo">Promos por piezas (20, 30, 40…): en la Caja el cliente arma sus rolls con base, rellenos y envoltorio</small>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <!-- v1.32.1 (pedido del usuario): como la ficha de la Carta, en dos partes: los datos de la promo y lo que el cliente elige -->
            <div class="lm-cuerpo">
                <div class="lm-tarjeta ps-arriba">
                    <div class="ps-modo" id="psModo">
                        <button type="button" data-modo="pieza" class="on"><i class="fa fa-th"></i> Por pieza <b>0</b></button>
                        <button type="button" data-modo="rol"><i class="fa fa-circle-o"></i> Por roll <b>0</b></button>
                    </div>
                    <div class="ps-elegir">
                        <select id="cboPromoSushi" class="form-control"></select>
                        <button type="button" class="btn btn-default" id="btnPsNueva"><i class="fa fa-plus"></i> Nueva promo</button>
                    </div>
                </div>
            </div>
            <div class="prod-secciones ps-secciones" role="tablist" aria-label="Partes de la promo">
                <button type="button" class="prod-sec-btn activo" data-ps-seccion="datos" role="tab"><i class="fa fa-tag"></i> 1. Datos de la promo</button>
                <button type="button" class="prod-sec-btn" data-ps-seccion="elegir" role="tab"><i class="fa fa-list"></i> 2. Lo que el cliente elige</button>
            </div>
            <div class="lm-cuerpo">
                <!-- 1. Datos de la promo -->
                <div class="ps-seccion activa" data-ps-seccion="datos">
                    <div class="lm-tarjeta">
                        <div class="lm-titulo"><span><i class="fa fa-tag"></i><span id="psTituloForm">Nueva promo</span></span></div>
                        <div class="ps-form">
                            <div class="ps-ancho"><label for="psNombre">Nombre</label><input type="text" class="form-control" id="psNombre" maxlength="100" placeholder="Ej: PROMO 30 PIEZAS MIXTAS"></div>
                            <div class="ps-solo-pieza"><label for="psPiezas">Piezas</label><input type="text" inputmode="numeric" class="form-control" id="psPiezas" placeholder="30"></div>
                            <div class="ps-solo-rol"><label for="psRolls">Rolls <small id="psRollsPiezas"></small></label><input type="text" inputmode="numeric" class="form-control" id="psRolls" placeholder="3"></div>
                            <div><label for="psPrecio">Precio <small>(todas las listas)</small></label><input type="text" inputmode="numeric" class="form-control" id="psPrecio" placeholder="9500"></div>
                            <div class="ps-solo-pieza"><label for="psPaso">De a cuántas piezas <small>(1 = libre)</small></label><input type="text" inputmode="numeric" class="form-control" id="psPaso" value="1"></div>
                            <div class="ps-solo-rol"><label for="psPiezasRol">Piezas por roll</label><input type="text" inputmode="numeric" class="form-control" id="psPiezasRol" value="10"></div>
                            <div><label for="psFamilia">Familia <a class="ps-nueva-fam" id="psNuevaFamilia" title="Crear una familia nueva (queda elegida)"><i class="fa fa-plus"></i> Nueva</a></label><select class="form-control" id="psFamilia"></select></div>
                            <div><label for="psDestino">Destino de impresión</label><select class="form-control" id="psDestino"></select></div>
                            <div class="ps-ancho"><label class="ps-check"><input type="checkbox" id="psVigente" checked> Vigente (se vende en la Caja)</label></div>
                            <div class="ps-ancho ps-solo-rol"><label class="ps-check" title="Cada roll va en su propia línea con el precio de su envoltorio (se pone en la parte 2, Envoltorios). Un roll con un envoltorio sin precio lleva su parte del precio de la promo."><input type="checkbox" id="psPrecioEnv"> Cada roll se cobra según su envoltorio</label></div>
                        </div>
                        <div class="ps-pie-form">
                            <small id="psAyudaForm"></small>
                            <button type="button" class="btn btn-success" id="btnPsGrabar"><i class="fa fa-save"></i> Grabar promo</button>
                        </div>
                    </div>
                    <!-- promo fija: se arma una vez en la Caja y queda así; aquí se ve y se quita -->
                    <div class="lm-tarjeta ps-fija" id="psFija" style="display:none"></div>
                </div>

                <!-- 2. Lo que el cliente elige -->
                <div class="ps-seccion" data-ps-seccion="elegir">
                    <div class="lm-tarjeta" id="psCatalogo">
                        <div class="ps-tabs" id="psTabs">
                            <span class="ps-tab on" data-tipo="relleno">Rellenos <b>0</b></span>
                            <span class="ps-tab" data-tipo="envoltorio">Envoltorios <b>0</b></span>
                            <span class="ps-tab" data-tipo="base">Bases <b>0</b></span>
                            <span class="ps-tab" data-tipo="salsa">Salsas <b>0</b></span>
                            <span class="ps-tab" data-tipo="extra">Extras <b>0</b></span>
                        </div>
                        <p class="ps-ayuda-tipo" id="psAyudaTipo"></p>
                        <!-- límite de esta sección (se guarda al cambiarlo) -->
                        <div class="ps-limite" id="psLimite">
                            <label for="psLimiteValor" id="psLimiteTexto">Máximo por roll</label>
                            <input type="text" inputmode="numeric" class="form-control" id="psLimiteValor">
                            <small id="psLimiteAyuda"></small>
                        </div>
                        <div class="ps-lista" id="psLista"></div>
                    </div>
                    <div class="lm-tarjeta">
                        <div class="lm-titulo"><span><i class="fa fa-plus-circle"></i><span id="psTituloAgregar">Agregar</span></span></div>
                        <div class="ps-picker">
                            <div class="ps-picker-barra">
                                <div class="ps-buscar"><i class="fa fa-search"></i><input type="text" class="form-control" id="psBuscar" placeholder="Buscar producto…" autocomplete="off"></div>
                                <select class="form-control" id="psFiltroFamilia"><option value="0">Todas las familias</option></select>
                            </div>
                            <div class="ps-picker-lista" id="psPickerLista"></div>
                            <div class="ps-picker-pie">
                                <span class="ps-pie-valor"><span class="ps-lbl-valor">Valor $</span> <input type="text" inputmode="numeric" class="form-control" id="psValorExistente" value="0"></span>
                                <span>
                                    <button type="button" class="btn btn-default" id="btnPsFamilia" title="Agrega todos los productos vigentes de la familia elegida arriba"><i class="fa fa-folder-open-o"></i> Agregar toda la familia</button>
                                    <button type="button" class="btn btn-success" id="btnPsAgregar" disabled><i class="fa fa-plus"></i> Agregar marcados</button>
                                </span>
                            </div>
                        </div>
                        <div class="ps-agregar">
                            <div><label for="psNuevoNombre">…o crear un producto nuevo</label><input type="text" class="form-control" id="psNuevoNombre" maxlength="100" placeholder="Ej: POLLO"></div>
                            <div><label for="psValorNuevo" class="ps-lbl-valor">Valor $</label><input type="text" inputmode="numeric" class="form-control" id="psValorNuevo" value="0"></div>
                            <button type="button" class="btn btn-success" id="btnPsCrear"><i class="fa fa-magic"></i> Crear y agregar</button>
                        </div>
                        <div class="ps-copiar">
                            <select class="form-control" id="psCopiarDe"></select>
                            <button type="button" class="btn btn-default" id="btnPsCopiar"><i class="fa fa-clone"></i> Copiar todo lo de esa promo</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Salir</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var URL_PS = base_url + 'index.php/Productos/';
    var ps = { datos: null, promo: null, opciones: null, tipo: 'relleno', modo: 'pieza', marcados: {} };   // v1.26.0: modo = pieza | rol; v1.32.0: marcados del buscador
    var cfgSushi = function () { return (ps.datos && ps.datos.config) || { por_pieza: true, bases: true, salsas: false }; };
    // v1.26.0: en una promo por roll el valor de las opciones es por roll
    var AYUDA_ROL = {
        relleno: 'Lo que va dentro del roll (pollo, kanikama, palta…). El cliente elige hasta el máximo de rellenos por roll. El valor es por roll (0 = no se cobra).',
        envoltorio: 'Con qué se envuelve (queso, panko, palta, nori…). El cliente elige hasta el máximo de envoltorios por roll. El valor es por roll (0 = no se cobra). Si en la parte 1 marcaste «Cada roll se cobra según su envoltorio», aquí pones el precio del roll con cada envoltorio.',
        base: 'Opcional. Si no agregas bases, no se pregunta (ej. siempre arroz). Si hay una sola, se elige sola. El valor es por roll.',
        extra: 'Opcional. Extras aparte de los rolls (jengibre, palillos, gyozas…). El cliente elige si quiere y cuántos, hasta el máximo de extras de la promo. El valor es por unidad (0 = no se cobra).',
        salsa: 'Opcional. Salsas aparte de los rolls (soya, teriyaki, agridulce…). El cliente elige si quiere y cuántas, hasta el máximo de salsas de la promo. El valor es por unidad (0 = no se cobra).'
    };
    var esRol = function () { return ps.modo === 'rol'; };
    var porQue = function () { return ps.tipo === 'extra' || ps.tipo === 'salsa' ? 'c/u' : (esRol() ? 'x roll' : 'x pieza'); };
    var AYUDA = {
        relleno: 'Lo que va dentro del roll (pollo, kanikama, palta…). El cliente elige hasta el máximo de rellenos por grupo. El valor es por pieza (0 = no se cobra).',
        envoltorio: 'Con qué se envuelve (queso, panko, palta, nori…). El cliente elige hasta el máximo de envoltorios por grupo. El valor es por pieza (0 = no se cobra).',
        base: 'Opcional. Si no agregas bases, no se pregunta (ej. siempre arroz). Si hay una sola, se elige sola. El valor es por pieza.',
        extra: 'Opcional. Extras aparte de las piezas (jengibre, palillos, gyozas…). El cliente elige si quiere y cuántos, hasta el máximo de extras de la promo. El valor es por unidad (0 = no se cobra).',
        salsa: 'Opcional. Salsas aparte de las piezas (soya, teriyaki, agridulce…). El cliente elige si quiere y cuántas, hasta el máximo de salsas de la promo. El valor es por unidad (0 = no se cobra).'
    };
    var esc = function (t) { return $('<div>').text(t == null ? '' : t).html(); };
    var pesos = function (n) { return '$' + Number(n || 0).toLocaleString('es-CL'); };
    var entero = function (v) { return parseInt(String(v || '').replace(/\D/g, ''), 10) || 0; };
    function error(x, def) {
        var m = (x && x.responseJSON && x.responseJSON.error) || def || 'No se pudo completar';
        showAppAlert('error', m, { duration: 5000 });
    }
    function post(accion, datos) { return $.ajax({ url: URL_PS + accion, type: 'POST', dataType: 'json', data: datos || {} }); }

    window.abrirPromosSushi = function () {
        cargarDatos(0).done(function () { $('#ModalPromosSushi').modal('show'); });
    };

    /** v1.26.0: la lista de promos del modo elegido arriba (por pieza o por roll) */
    function pintarListaPromos() {
        var d = ps.datos;
        if (!d) return;
        $('#psModo button').each(function () {
            var m = $(this).data('modo');
            $(this).toggleClass('on', m === ps.modo).find('b').text($.grep(d.promos, function (p) { return p.modo === m; }).length);
        });
        $('#psModo button[data-modo="rol"]').toggle(!!d.con_rol);
        // v1.32.1 (pedido del usuario): "Por pieza" arriba solo si está encendido en Configuraciones > Promos de sushi
        $('#psModo button[data-modo="pieza"]').toggle(!d.con_rol || piezaVisible());
        $('#psModo').toggle(!!d.con_rol && piezaVisible());
        $('#ModalPromosSushi').toggleClass('modo-rol', esRol());
        $('#psSubtitulo').text(esRol()
            ? 'Promos por roll (3 rolls = 30 piezas, 5 rolls = 50): en la Caja el cliente arma cada roll con base, rellenos y envoltorio'
            : 'Promos por piezas (20, 30, 40…): en la Caja el cliente arma sus rolls con base, rellenos y envoltorio');
        $('#psAyudaForm').html(esRol()
            ? '<i class="fa fa-info-circle"></i> Por roll: con 3 rolls de 10 piezas la promo trae 30 piezas. En la Caja se arma roll por roll.'
            : '<i class="fa fa-info-circle"></i> "De a cuántas piezas": con 10, cada grupo es un roll completo de 10 (10, 20, 30…). Con 1, cualquier cantidad.');
        $('#cboPromoSushi').html('<option value="0">— Nueva promo ' + (esRol() ? 'por roll' : 'por pieza') + ' —</option>' + $.map(d.promos, function (p) {
            if (p.modo !== ps.modo) return null;
            var cuanto = p.modo === 'rol' ? (p.piezas / p.piezas_rol) + ' rolls (' + p.piezas + ' pz)' : p.piezas + ' pz';
            return '<option value="' + p.id_producto + '">' + esc(p.nombre) + ' · ' + cuanto + ' · ' + pesos(p.precio) + (p.vigente ? '' : ' (no vigente)') + '</option>';
        }).join(''));
    }
    function rollsPiezas() {
        var r = entero($('#psRolls').val()), pr = entero($('#psPiezasRol').val()) || 10;
        $('#psRollsPiezas').text(r ? '(= ' + (r * pr) + ' piezas)' : '');
    }
    $('#psRolls, #psPiezasRol').on('input', rollsPiezas);

    function cargarDatos(elegir) {
        return post('obtenerPromosSushi').done(function (d) {
            ps.datos = d;
            // Al abrir una promo, arriba queda su modo
            var elegida = elegir ? $.grep(d.promos, function (p) { return p.id_producto === elegir; })[0] : null;
            if (elegida) ps.modo = elegida.modo;
            if (!d.con_rol) ps.modo = 'pieza';
            else if (!piezaVisible() && ps.modo === 'pieza') ps.modo = 'rol';   // v1.32.0
            pintarListaPromos();
            pintarFamiliasPicker();
            $('#psFamilia').html('<option value="0">Elige…</option>' + $.map(d.familias, function (f) { return '<option value="' + f.id_familia + '">' + esc(f.nombre_familia) + '</option>'; }).join(''));
            $('#psDestino').html('<option value="0">Según la familia</option>' + $.map(d.destinos, function (x) { return '<option value="' + x.id_dimpresion + '">' + esc(x.nombre_dimpresion) + '</option>'; }).join(''));
            pintarProductos();
            elegirPromo(elegir || 0);
        }).fail(function (x) { error(x, 'No se pudieron cargar las promos'); });
    }

    /** v1.32.0: ¿se muestran las promos por pieza? (Configuraciones, o si ya existe alguna) */
    function piezaVisible() { return !!cfgSushi().por_pieza; }
    // v1.32.1: las dos partes de la ventana (como la ficha de la Carta); la 2 se usa con la promo ya grabada
    function irSeccion(nombre) {
        if (nombre === 'elegir' && !ps.promo) nombre = 'datos';
        $('#ModalPromosSushi .ps-secciones .prod-sec-btn').each(function () { $(this).toggleClass('activo', $(this).data('ps-seccion') === nombre); });
        $('#ModalPromosSushi .ps-seccion').each(function () { $(this).toggleClass('activa', $(this).data('ps-seccion') === nombre); });
    }
    function pintarSecciones() {
        $('#ModalPromosSushi .prod-sec-btn[data-ps-seccion="elegir"]').prop('disabled', !ps.promo)
            .attr('title', ps.promo ? '' : 'Graba la promo primero');
        if (!ps.promo) irSeccion('datos');
    }
    $('#ModalPromosSushi').on('click', '.ps-secciones .prod-sec-btn', function () { irSeccion($(this).data('ps-seccion')); });
    function tipoVisible(t) {
        if (t === 'base') return !!cfgSushi().bases;
        if (t === 'salsa') return !!cfgSushi().salsas;
        return true;
    }
    function pintarFija() {
        var $f = $('#psFija');
        pintarSecciones();
        if (!ps.promo || !(ps.datos && ps.datos.con_opciones)) { $f.hide(); return; }
        $f.show().toggleClass('on', !!ps.promo.fija).html(ps.promo.fija
            ? '<div class="ps-fija-txt"><i class="fa fa-lock"></i> <b>Esta promo está FIJA.</b> En la Caja se agrega ya armada, sin preguntar.<br><small>Para cambiarla solo en una venta, toca su nombre en la Caja. Para que se vuelva a armar siempre, quítale lo fijo:</small></div>'
                + '<button type="button" class="btn btn-warning" id="btnPsQuitarFija"><i class="fa fa-unlock"></i> Quitar fija</button>'
            : '<div class="ps-fija-txt"><i class="fa fa-unlock-alt"></i> <b>Promo para armar</b> (en la Caja se elige cada vez).<br><small>Para dejarla <b>fija</b> (siempre igual): ármala una vez en la Caja y marca «Dejar esta promo fija» antes de Agregar.</small></div>');
    }

    function elegirPromo(id) {
        ps.marcados = {};   // v1.32.0
        $('#cboPromoSushi').val(String(id));
        if (!id) {
            ps.promo = null; ps.opciones = null;
            $('#psTituloForm').text('Nueva promo');
            $('#psNombre').val(''); $('#psPiezas').val(''); $('#psPrecio').val(''); $('#psPaso').val('1'); $('#psPrecioEnv').prop('checked', false);
            $('#psRolls').val(''); $('#psPiezasRol').val('10'); rollsPiezas();
            $('#psNombre').attr('placeholder', esRol() ? 'Ej: PROMO 3 ROLLS' : 'Ej: PROMO 30 PIEZAS MIXTAS');
            var sushi = $.grep(ps.datos.familias, function (f) { return /sushi/i.test(f.nombre_familia); })[0];
            $('#psFamilia').val(sushi ? String(sushi.id_familia) : '0'); $('#psDestino').val('0'); $('#psVigente').prop('checked', true);
            pintarCatalogo();
            pintarFija();
            setTimeout(function () { $('#psNombre').focus(); }, 300);
            return;
        }
        post('obtenerPromoSushi', { id_producto: id }).done(function (r) {
            ps.promo = r.promo; ps.opciones = r.opciones;
            var p = r.promo;
            $('#psTituloForm').text('Editar ' + p.nombre);
            $('#psNombre').val(p.nombre); $('#psPiezas').val(p.piezas); $('#psPrecio').val(p.precio); $('#psPaso').val(p.paso);
            $('#psRolls').val(p.modo === 'rol' ? p.piezas / p.piezas_rol : ''); $('#psPiezasRol').val(p.piezas_rol || 10); rollsPiezas();
            $('#psFamilia').val(String(p.id_familia)); $('#psDestino').val(String(p.id_dimpresion)); $('#psVigente').prop('checked', p.vigente);
            $('#psPrecioEnv').prop('checked', !!p.precio_envoltorio);
            pintarCatalogo();
            pintarFija();
        }).fail(function (x) { error(x, 'No se pudo abrir la promo'); });
    }

    function recargarPromo() { if (ps.promo) elegirPromo(ps.promo.id_producto); }

    // v1.32.0 (pedido del usuario): buscador propio (el select2 de antes no dejaba escribir dentro de la ventana) para
    // marcar uno o varios productos, o agregar toda una familia de una vez
    function pintarFamiliasPicker() {
        var $f = $('#psFiltroFamilia'), antes = $f.val() || '0';
        $f.html('<option value="0">Todas las familias</option>' + $.map(ps.datos ? ps.datos.familias : [], function (f) {
            return '<option value="' + f.id_familia + '">' + esc(f.nombre_familia) + '</option>';
        }).join('')).val(antes);
        if ($f.val() === null) $f.val('0');
    }
    function pintarProductos() {
        var usados = {}, texto = $.trim($('#psBuscar').val()).toLowerCase(), fam = Number($('#psFiltroFamilia').val()) || 0, n = 0, MAX = 200;
        $.each(ps.opciones ? ps.opciones[ps.tipo] : [], function (i, o) { usados[o.id_producto] = true; });
        var lista = $.grep(ps.datos ? ps.datos.productos : [], function (p) {
            return p.vigente && !usados[p.id_producto] && (!fam || p.id_familia === fam) && (!texto || p.nombre.toLowerCase().indexOf(texto) >= 0 || String(p.familia).toLowerCase().indexOf(texto) >= 0);
        });
        $.each(ps.marcados, function (id) { if (usados[id]) delete ps.marcados[id]; });
        $('#psPickerLista').html(lista.length ? $.map(lista.slice(0, MAX), function (p) {
            return '<label><input type="checkbox" class="ps-marca" value="' + p.id_producto + '"' + (ps.marcados[p.id_producto] ? ' checked' : '') + '> ' + esc(p.nombre)
                + (p.familia ? ' <small>· ' + esc(p.familia) + '</small>' : '') + '</label>';
        }).join('') + (lista.length > MAX ? '<div class="ps-picker-vacio">Hay ' + lista.length + ': escribe para buscar</div>' : '')
            : '<div class="ps-picker-vacio">' + (texto || fam ? 'No hay productos con ese filtro' : 'No quedan productos para agregar') + '</div>');
        $.each(ps.marcados, function () { n++; });
        $('#btnPsAgregar').prop('disabled', !n || !ps.promo).html('<i class="fa fa-plus"></i> Agregar ' + (n ? n + (n === 1 ? ' marcado' : ' marcados') : 'marcados'));
        $('#btnPsFamilia').prop('disabled', !fam || !ps.promo);
    }
    $('#psBuscar').on('input', pintarProductos);
    $('#psFiltroFamilia').on('change', pintarProductos);
    $('#psPickerLista').on('change', '.ps-marca', function () {
        if (this.checked) ps.marcados[this.value] = true; else delete ps.marcados[this.value];
        pintarProductos();
    });

    function pintarCatalogo() {
        $('#psCatalogo').toggleClass('ps-bloqueado', !ps.promo);
        if (!tipoVisible(ps.tipo)) ps.tipo = 'relleno';   // v1.32.0: bases y salsas según Configuraciones
        $('#psTabs .ps-tab').each(function () {
            var t = $(this).data('tipo');
            $(this).toggle(tipoVisible(t)).toggleClass('on', t === ps.tipo).find('b').text(ps.opciones && ps.opciones[t] ? ps.opciones[t].length : 0);
        });
        var conPrecioEnv = ps.tipo === 'envoltorio' && ps.promo && ps.promo.precio_envoltorio && esRol();   // v1.32.0
        // v1.32.1 (pedido del usuario): el límite de esta sección, aquí mismo
        var LIM = { relleno: ['max_rellenos', 'Máximo de rellenos', ' por roll', '0 = sin límite'], envoltorio: ['max_envoltorios', 'Máximo de envoltorios', ' por roll', 'de 1 a 5'],
            base: ['max_bases', 'Máximo de bases', ' por roll', 'de 1 a 5'], salsa: ['max_salsas', 'Máximo de salsas', ' en total', '0 = sin límite'],
            extra: ['max_extras', 'Máximo de extras', ' en total', '0 = sin límite'] }[ps.tipo];
        $('#psLimite').toggle(!!ps.promo);
        if (ps.promo && LIM) {
            var porQ = (ps.tipo === 'salsa' || ps.tipo === 'extra') ? LIM[2] : (esRol() ? LIM[2] : ' por grupo');
            $('#psLimiteTexto').text(LIM[1] + porQ);
            if (!$('#psLimiteValor').is(':focus')) $('#psLimiteValor').val(ps.promo[LIM[0]] != null ? ps.promo[LIM[0]] : '');
            $('#psLimiteAyuda').text(LIM[3] + ' · se guarda solo');
        }
        $('#psTituloAgregar').text('Agregar a ' + $('#psTabs .ps-tab.on').clone().children().remove().end().text().trim());
        $('#psAyudaTipo').text(ps.promo ? (esRol() ? AYUDA_ROL : AYUDA)[ps.tipo] : 'Graba la promo primero; después agrega sus rellenos, envoltorios, bases y extras.');
        $('.ps-lbl-valor').text('Valor $ ' + porQue());
        var lista = ps.opciones && ps.opciones[ps.tipo] ? ps.opciones[ps.tipo] : [];
        $('#psLista').html(lista.length ? $.map(lista, function (o) {
            return '<div class="ps-op" data-id="' + o.id_opcion + '"><span class="nombre">' + esc(o.nombre)
                + (o.vigente ? '' : ' <small class="text-danger">(producto no vigente: no se ofrece)</small>')
                + (o.con_receta ? ' <small><i class="fa fa-book"></i> con receta</small>' : '') + '</span>'
                + (conPrecioEnv ? '<span class="ps-precio-env" title="Precio de cada roll con este envoltorio (0 = el roll lleva su parte del precio de la promo)">Precio del roll $<input type="text" inputmode="numeric" class="form-control input-sm ps-precio-input" value="' + (o.precio_promo || 0) + '"></span>'
                    : '<span class="ps-valor">' + porQue() + ' $<input type="text" inputmode="numeric" class="form-control input-sm ps-valor-input" value="' + o.valor + '"></span>')
                + '<button type="button" class="btn btn-default btn-sm ps-quitar" title="Quitar de la promo"><i class="fa fa-times text-danger"></i></button></div>';
        }).join('') : '<div class="ps-vacio">' + (ps.promo ? 'Todavía no hay nada aquí' : '—') + '</div>');
        $('#psCopiarDe').html('<option value="0">Copiar las opciones de otra promo…</option>' + $.map(ps.datos ? ps.datos.promos : [], function (p) {
            // v1.26.0: solo de promos del mismo tipo (los valores son por pieza o por roll)
            return (ps.promo && p.id_producto === ps.promo.id_producto) || p.modo !== ps.modo ? null : '<option value="' + p.id_producto + '">' + esc(p.nombre) + ' (' + p.opciones + ' opciones)</option>';
        }).join(''));
        pintarProductos();
    }

    $('#cboPromoSushi').on('change', function () { elegirPromo(Number(this.value)); });
    $('#btnPsNueva').on('click', function () { elegirPromo(0); });
    // v1.26.0: cambiar arriba entre promos por pieza y por roll
    $('#psModo').on('click', 'button', function () {
        var m = $(this).data('modo');
        if (m === ps.modo) return;
        ps.modo = m;
        pintarListaPromos();
        elegirPromo(0);
    });
    $('#psTabs').on('click', '.ps-tab', function () { ps.tipo = $(this).data('tipo'); ps.marcados = {}; $('#psBuscar').val(''); pintarCatalogo(); });
    // v1.32.1: guardar el límite de la sección al cambiarlo
    $('#psLimiteValor').on('change', function () {
        if (!ps.promo) return;
        var tipo = ps.tipo, v = entero(this.value);
        post('limitePromoSushi', { id_promo: ps.promo.id_producto, tipo: tipo, valor: v }).done(function (r) {
            var campo = { relleno: 'max_rellenos', envoltorio: 'max_envoltorios', base: 'max_bases', salsa: 'max_salsas', extra: 'max_extras' }[tipo];
            ps.promo[campo] = r.valor;
            showAppAlert('success', 'Límite guardado', { duration: 2000 });
        }).fail(function (x) { error(x, 'No se pudo guardar el límite'); pintarCatalogo(); });
    }).on('keydown', function (e) { if (e.which === 13) $(this).blur(); });
    $('#ModalPromosSushi').on('input', '#psLimiteValor, #psPiezas, #psPrecio, #psPaso, #psRolls, #psPiezasRol, #psValorExistente, #psValorNuevo, .ps-valor-input, .ps-precio-input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 9);
    });

    // v1.32.1: "Quitar fija" la deja para armar otra vez (sin tocar lo demás de la promo)
    $('#ModalPromosSushi').on('click', '#btnPsQuitarFija', function () {
        if (!ps.promo) return;
        confirmarSistema('¿Quitar la promo fija?', 'En la Caja se volverá a pedir que se arme cada vez.', 'Sí, quitar', function () {
            post('quitarFijaSushi', { id_promo: ps.promo.id_producto }).done(function () {
                showAppAlert('success', 'Listo: la promo ya no es fija', { duration: 3500 });
                ps.promo.fija = false;
                pintarFija();
                cargarDatos(ps.promo.id_producto);
            }).fail(function (x) { error(x, 'No se pudo quitar'); });
        });
    });
    $('#btnPsGrabar').on('click', function () {
        var datos = {
            id_producto: ps.promo ? ps.promo.id_producto : 0, nombre: $.trim($('#psNombre').val()), piezas: entero($('#psPiezas').val()),
            precio: entero($('#psPrecio').val()), paso: entero($('#psPaso').val()) || 1, max_rellenos: ps.promo ? ps.promo.max_rellenos : 3,
            id_familia: Number($('#psFamilia').val()) || 0, id_dimpresion: Number($('#psDestino').val()) || 0, vigente: $('#psVigente').is(':checked') ? 1 : 0,
            modo: ps.modo, rolls: entero($('#psRolls').val()), piezas_rol: entero($('#psPiezasRol').val()) || 10,   // v1.26.0
            // v1.32.0
            // v1.32.1: los límites se ponen en la parte 2 (cada sección); aquí se mandan los que ya tiene
            precio_envoltorio: esRol() && $('#psPrecioEnv').is(':checked') ? 1 : 0, max_envoltorios: ps.promo ? ps.promo.max_envoltorios : 1,
            max_extras: ps.promo ? ps.promo.max_extras : 0, max_salsas: ps.promo ? ps.promo.max_salsas : 0
        };
        if (esRol()) { datos.piezas = datos.rolls * datos.piezas_rol; datos.paso = datos.piezas_rol; }
        if (!datos.nombre) { showAppAlert('warning', 'Escribe el nombre de la promo', { duration: 4000 }); return; }
        if (esRol() && !datos.rolls) { showAppAlert('warning', 'Indica cuántos rolls trae la promo', { duration: 4000 }); return; }
        if (!datos.piezas) { showAppAlert('warning', 'Indica cuántas piezas trae la promo', { duration: 4000 }); return; }
        if (!datos.precio) { showAppAlert('warning', 'Indica el precio', { duration: 4000 }); return; }
        if (!datos.id_familia) { showAppAlert('warning', 'Elige la familia', { duration: 4000 }); return; }
        var $b = $(this).prop('disabled', true);
        post('grabarPromoSushi', datos).done(function (r) {
            showAppAlert('success', datos.id_producto ? 'Promo actualizada' : 'Promo creada: ahora, en la parte 2, agrégale sus rellenos y envoltorios', { duration: 4000 });
            if (datos.precio_envoltorio) showAppAlert('info', 'Pon el precio de cada envoltorio en la parte 2 > Envoltorios', { duration: 5000 });
            var nueva = !datos.id_producto;
            cargarDatos(r.id_producto).done(function () { if (nueva) setTimeout(function () { irSeccion('elegir'); }, 400); });
            if (typeof tablaCartaProductos !== 'undefined' && tablaCartaProductos && tablaCartaProductos.ajax) tablaCartaProductos.ajax.reload(null, false);
        }).fail(function (x) { error(x, 'No se pudo grabar la promo'); }).always(function () { $b.prop('disabled', false); });
    });

    // v1.32.0: agrega los marcados (uno o varios) o toda la familia elegida en el filtro
    function agregarVarias(datos, $b) {
        $b.prop('disabled', true);
        return post('agregarVariasSushi', $.extend({ id_promo: ps.promo.id_producto, tipo: ps.tipo, valor: entero($('#psValorExistente').val()) }, datos))
            .done(function (r) {
                ps.marcados = {};
                showAppAlert('success', (r.agregados === 1 ? '1 producto agregado' : r.agregados + ' productos agregados') + (r.saltados ? ' (' + r.saltados + ' ya estaban)' : ''), { duration: 3500 });
                $('#psValorExistente').val('0');
                recargarPromo();
            })
            .fail(function (x) { error(x, 'No se pudo agregar'); }).always(function () { $b.prop('disabled', false); pintarProductos(); });
    }
    $('#btnPsAgregar').on('click', function () {
        var ids = $.map(ps.marcados, function (v, id) { return Number(id); });
        if (!ps.promo) return;
        if (!ids.length) { showAppAlert('warning', 'Marca uno o más productos', { duration: 3000 }); return; }
        agregarVarias({ ids: ids }, $(this));
    });
    $('#btnPsFamilia').on('click', function () {
        var fam = Number($('#psFiltroFamilia').val()) || 0, $b = $(this);
        if (!ps.promo || !fam) { showAppAlert('warning', 'Elige la familia en el filtro de arriba', { duration: 3000 }); return; }
        var nombre = $('#psFiltroFamilia option:selected').text(), seccion = $('#psTabs .ps-tab.on').clone().children().remove().end().text().trim();
        var cuantos = $.grep(ps.datos.productos, function (p) { return p.vigente && p.id_familia === fam; }).length;
        confirmarSistema('¿Agregar toda la familia ' + nombre + '?', 'Se agregan sus ' + cuantos + ' productos vigentes a ' + seccion + ' (los que ya estén se saltan).', 'Sí, agregar', function () {
            agregarVarias({ id_familia: fam }, $b);
        });
    });

    $('#btnPsCrear').on('click', function () {
        var nombre = $.trim($('#psNuevoNombre').val());
        if (!ps.promo) return;
        if (!nombre) { showAppAlert('warning', 'Escribe el nombre (ej. POLLO)', { duration: 3000 }); return; }
        var $b = $(this).prop('disabled', true);
        post('crearOpcionSushi', { id_promo: ps.promo.id_producto, tipo: ps.tipo, nombre: nombre, valor: entero($('#psValorNuevo').val()) })
            .done(function (r) {
                showAppAlert('success', r.creado ? 'Producto ' + nombre.toUpperCase() + ' creado y agregado. Si quieres que descuente stock, ponle su receta en la Carta.' : 'Ya existía un producto con ese nombre: se agregó ese', { duration: 5000 });
                $('#psNuevoNombre').val('').focus(); $('#psValorNuevo').val('0');
                if (r.creado) cargarDatos(ps.promo.id_producto); else recargarPromo();
            })
            .fail(function (x) { error(x, 'No se pudo crear'); }).always(function () { $b.prop('disabled', false); });
    });
    $('#psNuevoNombre').on('keydown', function (e) { if (e.which === 13) $('#btnPsCrear').click(); });

    $('#psLista').on('change', '.ps-precio-input', function () {   // v1.32.0
        var id = Number($(this).closest('.ps-op').data('id'));
        post('precioOpcionSushi', { id_opcion: id, precio: entero(this.value) })
            .done(function () { showAppAlert('success', 'Precio con este envoltorio actualizado', { duration: 2000 }); recargarPromo(); })
            .fail(function (x) { error(x, 'No se pudo cambiar el precio'); recargarPromo(); });
    }).on('change', '.ps-valor-input', function () {
        var id = Number($(this).closest('.ps-op').data('id'));
        post('valorOpcionSushi', { id_opcion: id, valor: entero(this.value) })
            .done(function () { showAppAlert('success', 'Valor actualizado', { duration: 2000 }); recargarPromo(); })
            .fail(function (x) { error(x, 'No se pudo cambiar el valor'); recargarPromo(); });
    }).on('click', '.ps-quitar', function () {
        var id = Number($(this).closest('.ps-op').data('id'));
        post('quitarOpcionSushi', { id_opcion: id }).done(recargarPromo).fail(function (x) { error(x, 'No se pudo quitar'); });
    });

    // v1.32.0 (pedido del usuario): crear una familia desde aquí con la ventana de siempre; al grabarla queda elegida
    var esperaFamilia = false;
    $('#psNuevaFamilia').on('click', function () {
        if (typeof crearNuevaFamilia !== 'function') return;
        esperaFamilia = true;
        crearNuevaFamilia();
    });
    $('#ModalNuevaFamilia').on('show.bs.modal', function () {
        if (!$('#ModalPromosSushi').hasClass('in')) return;
        // Encima de la ventana de promos (Bootstrap 3 no apila dos ventanas)
        var $m = $(this).css('z-index', 1070);
        setTimeout(function () { $('.modal-backdrop').last().css('z-index', 1065); }, 0);
    }).on('hidden.bs.modal', function () {
        $(this).css('z-index', '');
        if ($('#ModalPromosSushi').hasClass('in')) $('body').addClass('modal-open');
        setTimeout(function () { esperaFamilia = false; }, 3000);
    });
    $(document).on('familia:creada', function (e, nombre) {
        if (!esperaFamilia || !$('#ModalPromosSushi').hasClass('in')) return;
        esperaFamilia = false;
        post('obtenerPromosSushi').done(function (d) {
            ps.datos.familias = d.familias;
            var f = $.grep(d.familias, function (x) { return String(x.nombre_familia).toUpperCase() === String(nombre).toUpperCase(); })[0];
            $('#psFamilia').html('<option value="0">Elige…</option>' + $.map(d.familias, function (x) { return '<option value="' + x.id_familia + '">' + esc(x.nombre_familia) + '</option>'; }).join(''))
                .val(f ? String(f.id_familia) : '0');
            pintarFamiliasPicker();
        });
    });

    $('#btnPsCopiar').on('click', function () {
        var origen = Number($('#psCopiarDe').val()) || 0;
        if (!ps.promo || !origen) { showAppAlert('warning', 'Elige de qué promo copiar', { duration: 3000 }); return; }
        post('copiarCatalogoSushi', { id_origen: origen, id_destino: ps.promo.id_producto }).done(function (r) {
            showAppAlert('success', r.nuevas ? r.nuevas + (r.nuevas === 1 ? ' opción copiada' : ' opciones copiadas') : 'Ya tenía todas esas opciones', { duration: 4000 });
            cargarDatos(ps.promo.id_producto);
        }).fail(function (x) { error(x, 'No se pudo copiar'); });
    });
})();
</script>
