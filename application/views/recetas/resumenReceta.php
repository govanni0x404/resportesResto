<?php
/*
 * v1.37.14 (pedido del usuario: "no veo los valores de neto, costo por porción, etc."): resumen bajo la tabla de insumos de la
 * receta, como el sistema de escritorio: Cant., Costo total $, C. Neto Porción, % Costo Porción, Ganancia Neta Porción y
 * % Ganancia Porción. Se recalcula solo al cambiar la tabla, el rendimiento o el precio. Mismas fórmulas que
 * Mrecetas::recalcularCostosReceta (lo que se graba): neto = precio bruto / (1 + IVA%), costo porción = costo / rendimiento.
 * Lo usan Recetas y la receta de la Carta (mismos ids: #tableInsumoReceta, #rendimientoReceta, #brutoSugerido).
 */
$__iva = 19;
try {
    $__ci =& get_instance();
    $__ci->load->helper('tenant');
    $__r = get_tenant_db($__ci)->query('SELECT COALESCE(por_iva, 19) AS iva FROM configuracion LIMIT 1');
    if ($__r && ($__f = $__r->row())) $__iva = (float)$__f->iva;
} catch (Throwable $e) { /* sin configuración: 19% */ }
?>
<style>
.rr-resumen { margin-top: 12px; }
.rr-fila { display: flex; flex-wrap: wrap; align-items: center; gap: 10px 22px; padding: 10px 14px; border-radius: 10px;
    background: var(--t-fondo-alt, #F9FAFB); border: 1px solid var(--t-borde, #E5E7EB); font-size: 13px; }
.rr-fila b { font-size: 16px; color: var(--t-texto-fuerte, #111827); }
.rr-fila .rr-total { margin-left: auto; }
.rr-fila .rr-total b { font-size: 19px; }
.rr-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; margin-top: 10px; }
.rr-kpi { padding: 10px 12px; border: 1px solid var(--t-borde, #E5E7EB); border-radius: 10px; background: #fff; }
.rr-kpi small { display: block; font-size: 11px; text-transform: uppercase; letter-spacing: .3px; color: var(--t-texto-suave, #6B7280); }
.rr-kpi b { font-size: 18px; color: var(--t-texto-fuerte, #111827); }
.rr-kpi.rr-costo { background: #FFF7ED; border-color: #FED7AA; }
.rr-kpi.rr-costo b { color: #9A3412; }
.rr-kpi.rr-ganancia { background: var(--t-primario-suave, #E8F5EC); border-color: var(--t-primario-borde, #BBE3C8); }
.rr-kpi.rr-ganancia b { color: var(--t-primario, #15803D); }
.rr-kpi.rr-perdida { background: #FEF2F2; border-color: #FECACA; }
.rr-kpi.rr-perdida b { color: #B91C1C; }
.rr-nota { margin-top: 6px; font-size: 11.5px; color: var(--t-texto-tenue, #9CA3AF); }
@media (max-width: 768px) { .rr-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
</style>
<script>
(function () {
    var IVA = <?php echo json_encode($__iva); ?>;
    var n2 = function (n) { return (Number(n) || 0).toLocaleString('es-CL', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };
    var n0 = function (n) { return Math.round(Number(n) || 0).toLocaleString('es-CL'); };
    var num = function (v) { return Number(String(v == null ? '' : v).replace(/\s/g, '').replace(',', '.')) || 0; };
    function calcular() {
        var $t = $('#tableInsumoReceta');
        if (!$t.length || !$('#rrResumen').length) return;
        var cant = 0, total = 0;
        $t.find('tbody tr').each(function () {
            var $m = $(this).children('td.montoTotal');
            if (!$m.length) return;
            cant++;
            total += num($m.text());
        });
        var rend = num($('#rendimientoReceta').val()) || 1;
        var porcion = total / rend;
        var bruto = num($('#brutoSugerido').val());
        var neto = bruto / (1 + IVA / 100);
        var pCosto = neto > 0 ? porcion / neto * 100 : 0;
        var ganancia = neto - porcion;
        $('#rrCant').text(cant);
        $('#rrTotal').text('$' + n0(total));
        $('#rrPorcion').text(n2(porcion));
        $('#rrPCosto').text(neto > 0 ? n2(pCosto) + '%' : '—');
        $('#rrGanancia').text(neto > 0 ? '$' + n0(ganancia) : '—');
        $('#rrPGanancia').text(neto > 0 ? n2(100 - pCosto) + '%' : '—');
        $('#rrKpiGanancia, #rrKpiPGanancia').toggleClass('rr-ganancia', !(neto > 0) || ganancia >= 0).toggleClass('rr-perdida', neto > 0 && ganancia < 0);
        $('#rrNota').text(neto > 0 ? 'Neto de venta: $' + n0(neto) + ' (precio bruto $' + n0(bruto) + ' sin IVA ' + IVA + '%)'
            + (rend !== 1 ? ' · el costo por porción es el costo total / rendimiento (' + rend + ')' : '')
            : 'Escribe el precio bruto sugerido para ver el % de costo y la ganancia.');
    }
    window.recCalcularResumen = calcular;
    /**
     * v1.37.16 (pedido del usuario): al grabar la receta, una ventana al medio que confirma que se grabó, con su resumen.
     * Se toman los números antes de cerrar la ventana de la receta.
     */
    window.recMensajeGrabada = function (nombre) {
        calcular();
        var t = (nombre ? nombre + '\n' : '') + 'Insumos: ' + $('#rrCant').text() + ' · Costo total: ' + $('#rrTotal').text()
            + '\nCosto por porción: $' + $('#rrPorcion').text()
            + ($('#rrPCosto').text() !== '—' ? '\n% costo: ' + $('#rrPCosto').text() + ' · Ganancia neta: ' + $('#rrGanancia').text() + ' (' + $('#rrPGanancia').text() + ')' : '');
        setTimeout(function () {
            if (window.swal) swal({ title: '¡Receta grabada!', text: t, type: 'success', confirmButtonText: 'Aceptar' });
            else showAppAlert('success', 'Receta grabada', { duration: 5000 });
        }, 350);
    };
    $(function () {
        var $t = $('#tableInsumoReceta');
        if (!$t.length) return;
        var $tras = $t.closest('.table-responsive');
        if (!$tras.length) $tras = $t.closest('.dataTables_wrapper');
        if (!$tras.length) $tras = $t;
        $tras.after('<div class="rr-resumen" id="rrResumen">'
            + '<div class="rr-fila"><span>Insumos: <b id="rrCant">0</b></span><span class="rr-total">Costo total: <b id="rrTotal">$0</b></span></div>'
            + '<div class="rr-kpis">'
            + '<div class="rr-kpi"><small>C. neto porción</small><b id="rrPorcion">0,00</b></div>'
            + '<div class="rr-kpi rr-costo"><small>% costo porción</small><b id="rrPCosto">—</b></div>'
            + '<div class="rr-kpi rr-ganancia" id="rrKpiGanancia"><small>Ganancia neta porción</small><b id="rrGanancia">—</b></div>'
            + '<div class="rr-kpi rr-ganancia" id="rrKpiPGanancia"><small>% ganancia porción</small><b id="rrPGanancia">—</b></div>'
            + '</div><div class="rr-nota" id="rrNota"></div></div>');
        // Se recalcula solo: filas agregadas o quitadas, cantidades cambiadas, rendimiento o precio
        if (window.MutationObserver) new MutationObserver(function () { clearTimeout(calcular._t); calcular._t = setTimeout(calcular, 30); })
            .observe($t[0], { childList: true, subtree: true, characterData: true });
        $(document).on('input change', '#rendimientoReceta, #brutoSugerido', calcular);
        calcular();
    });
})();
</script>
