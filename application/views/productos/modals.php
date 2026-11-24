<style>
/* v1.24.5: agregar agregados de a uno o varios a la vez */
.ag-modo { display: inline-flex; gap: 4px; padding: 4px; margin-bottom: 12px; border-radius: 10px; background: var(--t-fondo-alt, #F3F4F6); border: 1px solid var(--t-borde, #E5E7EB); }
.ag-modo-btn { border: 0; background: transparent; padding: 7px 14px; border-radius: 7px; font-size: 13px; font-weight: 600; color: var(--t-texto-suave, #4B5563); }
.ag-modo-btn i { margin-right: 5px; }
.ag-modo-btn:hover { color: var(--t-texto-fuerte, #111827); }
.ag-modo-btn.activo { background: #fff; color: var(--t-primario, #15803D); box-shadow: 0 1px 3px rgba(17, 24, 39, .12); }
.ag-varios { border: 1px solid var(--t-borde, #E5E7EB); border-radius: 10px; overflow: hidden; background: #fff; }
.ag-varios-barra { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; padding: 10px; background: var(--t-tinte, #F7FBF8); border-bottom: 1px solid var(--t-borde, #E5E7EB); }
.ag-buscar { position: relative; flex: 1 1 200px; }
.ag-buscar i { position: absolute; left: 10px; top: 50%; margin-top: -7px; color: var(--t-texto-tenue, #6B7280); }
.ag-buscar input { padding-left: 30px; }
.ag-varios-barra .btn i { margin-right: 4px; }
.ag-varios-lista { display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 6px; max-height: 260px; overflow-y: auto; padding: 10px; }
.ag-op { display: flex; align-items: center; gap: 8px; margin: 0; padding: 8px 10px; border: 1px solid var(--t-borde, #E5E7EB); border-radius: 8px;
    cursor: pointer; font-size: 13px; font-weight: 600; color: var(--t-texto, #1F2937); text-transform: none !important; letter-spacing: 0 !important; transition: border-color .12s, background .12s; }
.ag-op:hover { border-color: var(--t-primario-borde, #BBE3C9); }
.ag-op input { margin: 0; accent-color: var(--t-primario, #15803D); }
.ag-op span { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.ag-op.elegido { border-color: var(--t-primario, #15803D); background: var(--t-primario-suave, #E8F5EC); }
.ag-op.ya-esta { opacity: .55; cursor: default; background: var(--t-fondo-alt, #F3F4F6); }
.ag-op.ya-esta small { font-weight: 600; color: var(--t-texto-tenue, #6B7280); }
.ag-varios-vacio { grid-column: 1 / -1; padding: 18px; text-align: center; color: var(--t-texto-tenue, #6B7280); }
/* v1.30.3: máximo de agregados por producto */
.ag-maximo { display: flex; flex-wrap: wrap; align-items: center; gap: 12px 16px; margin-top: 14px; padding: 14px 16px; border: 1px solid var(--t-primario-borde, #BBE3C8); border-radius: 12px; background: var(--t-primario-suave, #E8F5EC); }
.ag-maximo-icono { flex: 0 0 40px; width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; background: #fff; color: var(--t-primario, #15803D); font-size: 18px; box-shadow: 0 1px 2px rgba(17, 24, 39, .08); }
.ag-maximo-texto { flex: 1 1 220px; min-width: 0; line-height: 1.35; }
.ag-maximo-texto > b { display: block; font-size: 14px; color: var(--t-texto-fuerte, #111827); }
.ag-maximo-texto small { display: block; margin-top: 2px; font-size: 12.5px; color: var(--t-texto-suave, #4B5563); }
.ag-maximo-control { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 10px; }
.ag-contador { display: inline-flex; align-items: center; height: 40px; border: 1px solid var(--t-borde, #D1D5DB); border-radius: 10px; background: #fff; overflow: hidden; }
.ag-cont-btn { width: 38px; height: 100%; border: 0; background: transparent; color: var(--t-texto-suave, #4B5563); font-size: 12px; }
.ag-cont-btn:hover:not(:disabled) { background: var(--t-primario-suave, #E8F5EC); color: var(--t-primario, #15803D); }
.ag-cont-btn:disabled { opacity: .35; cursor: not-allowed; }
.ag-contador input { width: 52px; height: 100%; border: 0; border-left: 1px solid var(--t-borde-suave, #EEF0F3); border-right: 1px solid var(--t-borde-suave, #EEF0F3); text-align: center; font-size: 17px; font-weight: 800; color: var(--t-texto-fuerte, #111827); outline: none; }
.ag-contador input::placeholder { font-size: 20px; color: var(--t-primario, #15803D); }
.ag-sin-limite { height: 40px; padding: 0 12px; border: 1px solid var(--t-borde, #D1D5DB); border-radius: 10px; background: #fff; font-size: 13px; font-weight: 700; color: var(--t-texto-suave, #4B5563); }
.ag-sin-limite .ag-inf { font-size: 17px; line-height: 0; vertical-align: -1px; margin-right: 2px; }
.ag-sin-limite.activo { border-color: var(--t-primario, #15803D); background: var(--t-primario, #15803D); color: #fff; }
.ag-maximo-estado { min-width: 74px; font-size: 12px; font-weight: 700; color: var(--t-primario, #15803D); }
.ag-maximo-estado.error { color: #B91C1C; }
/* v1.35.4: ventana de agregados más simple */
.agx-grid { display: grid; grid-template-columns: minmax(380px, 5fr) 7fr; gap: 18px; align-items: start; }
.agx-izq > :last-child { margin-bottom: 0; }
.agx-der { min-width: 0; }
.agx-der .agx-tabla { max-height: calc(100vh - 230px); overflow-y: auto; }
.agx-der .agx-tabla thead th { position: sticky; top: 0; z-index: 2; }
.agx-izq .agx-producto { flex-basis: 100%; }
.agx-izq .agx-num { flex: 1 1 90px; }
.agx-izq .agx-grabar { flex: 1 1 120px; }
#ModalAgregadosDelProducto .agx-izq .ag-varios-lista { max-height: calc(100vh - 610px); min-height: 150px; }
@media (max-width: 991px) { .agx-grid { grid-template-columns: 1fr; } .agx-der .agx-tabla { max-height: none; } }
.agx-reglas { display: flex; flex-wrap: wrap; align-items: center; gap: 10px 22px; padding: 10px 14px; margin-bottom: 14px; border-radius: 10px;
    background: var(--t-primario-suave, #E8F5EC); border: 1px solid var(--t-primario-borde, #BBE3C8); }
.agx-regla { display: inline-flex; align-items: center; gap: 8px; margin: 0; font-weight: 400; }
.agx-etq { font-size: 13px; font-weight: 700; color: var(--t-texto-fuerte, #111827); white-space: nowrap; }
.agx-etq i { color: var(--t-texto-tenue, #9CA3AF); font-size: 12px; margin-left: 2px; cursor: help; }
.ag-contador-sm { height: 32px; }
.ag-contador-sm .ag-cont-btn { width: 30px; }
.ag-contador-sm input { width: 40px; font-size: 15px; }
.ag-sin-limite.agx-sm { height: 32px; padding: 0 10px; }
.agx-switch { cursor: pointer; position: relative; }
.agx-switch input:checked + .op-switch-pista { background: var(--t-primario, #15803D); }
.agx-switch input:checked + .op-switch-pista::after { transform: translateX(18px); }
.agx-switch input { position: absolute; opacity: 0; width: 0; height: 0; }
.agx-nota { margin-top: -8px !important; color: var(--t-texto-tenue, #6B7280) !important; }
.agx-nota i { margin-right: 3px; }
.agx-resumen { flex: 1 1 100%; margin-top: -4px; font-size: 12px; color: var(--t-texto-suave, #4B5563); }
.agx-caja { padding: 12px 14px; margin-bottom: 14px; border: 1px solid var(--t-borde, #E5E7EB); border-radius: 10px; }
.agx-caja-cab { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 10px; }
.agx-caja-cab .ag-modo { margin: 0; }
.agx-caja-cab { flex-wrap: wrap; }
.agx-caja-cab .ag-modo { display: inline-flex; flex-wrap: nowrap; }
.agx-caja-cab .ag-modo-btn { white-space: nowrap; display: inline-flex; flex-direction: row; align-items: center; gap: 6px; }
.agx-copiar { color: var(--t-texto-suave, #4B5563) !important; font-weight: 600; }
.agx-fila { display: flex; align-items: flex-end; gap: 10px; flex-wrap: wrap; }
.agx-campo label { display: block; margin-bottom: 4px; font-size: 11.5px; text-transform: uppercase; letter-spacing: .3px; color: var(--t-texto-suave, #6B7280); }
.agx-producto { flex: 1 1 260px; min-width: 0; }
.agx-num { flex: 0 0 100px; }
.agx-grabar { height: 32px; padding: 0 16px; }
#bloqueVariosAgregados + .agx-fila { margin-top: 10px; }
.agx-opciones { display: flex; flex-wrap: wrap; align-items: center; gap: 6px 18px; margin-top: 10px; font-size: 13px; }
.agx-opciones label { margin: 0; font-weight: 400; cursor: pointer; color: var(--t-texto, #374151); }
.agx-opciones input { margin: 0 3px 0 0; vertical-align: -2px; accent-color: var(--t-primario, #15803D); }
.agx-destino { font-size: 12px; color: var(--t-texto-suave, #4B5563); }
.agx-lista-cab { display: flex; align-items: baseline; gap: 8px; margin: 2px 0 6px; font-size: 14px; }
.agx-lista-cab span { font-size: 12px; color: var(--t-texto-suave, #6B7280); }
.agx-tabla { border: 1px solid var(--t-borde, #E5E7EB); border-radius: 10px; }
.agx-tabla table { margin: 0; }
.agx-tabla th { font-size: 11.5px; text-transform: uppercase; letter-spacing: .3px; color: var(--t-texto-suave, #6B7280); background: var(--t-fondo-alt, #F9FAFB); border-bottom: 1px solid var(--t-borde, #E5E7EB) !important; }
.agx-tabla td { vertical-align: middle !important; padding: 5px 8px !important; }
.agx-tabla .ag-nombre { font-weight: 600; }
.agx-tabla tr.ag-inactivo td { color: var(--t-texto-tenue, #9CA3AF); }
.agx-tabla tr.ag-inactivo .ag-nombre { text-decoration: line-through; }
.agx-tabla input.campo-agregado { height: 30px; padding: 2px 6px; text-align: center; }
.agx-mini-switch { position: relative; display: inline-block; width: 36px; height: 20px; margin: 0; cursor: pointer; vertical-align: middle; }
.agx-mini-switch input { opacity: 0; width: 0; height: 0; }
.agx-mini-switch span { position: absolute; inset: 0; border-radius: 20px; background: #D1D5DB; transition: background .15s; }
.agx-mini-switch span::before { content: ""; position: absolute; left: 2px; top: 2px; width: 16px; height: 16px; border-radius: 50%; background: #fff; transition: transform .15s; box-shadow: 0 1px 2px rgba(0,0,0,.2); }
.agx-mini-switch input:checked + span { background: var(--t-primario, #15803D); }
.agx-mini-switch input:checked + span::before { transform: translateX(16px); }
.agx-dest { display: block; font-size: 11px; color: var(--t-texto-suave, #6B7280); white-space: nowrap; }
.t-modal-form .agx-opciones label, .t-modal-form label.agx-switch, .t-modal-form .agx-switch .agx-etq { text-transform: none; letter-spacing: 0; font-size: 13px; }
.t-modal-form label.agx-switch .agx-etq { font-weight: 700; color: var(--t-texto-fuerte, #111827); }
.agx-fila .form-control, .agx-fila .select2-container .select2-selection--single { height: 36px !important; }
.agx-fila .select2-container .select2-selection--single .select2-selection__rendered { line-height: 34px !important; }
.agx-grabar { height: 36px; }
@media (max-width: 600px) { .agx-num { flex: 1 1 80px; } .agx-grabar { flex: 1 1 100%; } }
/* v1.35.1: casilla "Extras con cobro" en el recuadro del máximo y categorías en cuadros de color */
.ag-extras-producto { display: flex; align-items: center; gap: 12px; flex: 1 1 100%; margin-top: 4px; padding-top: 12px; border-top: 1px dashed var(--t-primario-borde, #BBE3C8); }
.ag-extras-producto .op-switch { flex: 1 1 auto; margin: 0; background: #fff; }
.ag-color-0 { --ag-c: #15803D; --ag-f: #F0FDF4; }
.ag-color-1 { --ag-c: #C2410C; --ag-f: #FFF7ED; }
.ag-color-2 { --ag-c: #1D4ED8; --ag-f: #EFF6FF; }
.ag-color-3 { --ag-c: #7E22CE; --ag-f: #FAF5FF; }
.ag-color-4 { --ag-c: #BE123C; --ag-f: #FFF1F2; }
.ag-color-5 { --ag-c: #0E7490; --ag-f: #ECFEFF; }
.ag-color-otros { --ag-c: #6B7280; --ag-f: #F9FAFB; }
.ag-varios-lista.con-categorias { display: flex; flex-direction: column; gap: 10px; max-height: 340px; }
.ag-varios-cat { position: relative; border: 2px solid var(--ag-c); border-radius: 10px; background: var(--ag-f); padding: 16px 8px 8px; margin-top: 8px; }
.ag-varios-cat-titulo { position: absolute; top: -11px; left: 10px; padding: 1px 10px; border-radius: 8px; background: var(--ag-c); color: #fff; font-size: 11.5px; font-weight: 800; text-transform: uppercase; letter-spacing: .4px; }
.ag-varios-cat-lista { display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 6px; }
.ag-varios-cat .ag-op { background: #fff; }
#tableAgregados tr.ag-grupo td { background: var(--ag-f, #E8F5EC); color: var(--ag-c, #15803D); font-weight: 800; text-transform: uppercase; letter-spacing: .3px; font-size: 12.5px; padding: 6px 10px; border-top: 2px solid var(--ag-c, #BBE3C8); }
@media (max-width: 520px) { .ag-maximo-control { width: 100%; } }
.ag-varios-pie { display: flex; flex-wrap: wrap; gap: 4px 12px; align-items: baseline; justify-content: space-between; padding: 8px 12px; border-top: 1px solid var(--t-borde, #E5E7EB); background: var(--t-fondo-alt, #F3F4F6); }
.ag-varios-pie span { font-weight: 700; color: var(--t-primario-osc, #116632); }
.ag-varios-pie small { color: var(--t-texto-tenue, #6B7280); }

/* Modales de productos (solo estilos) */
.t-modal-form .modal-header { border-top: 3px solid var(--t-primario, #B4432E); }
.t-modal-form .modal-title { text-align: left; }
.t-modal-form .h-panel > .nav { display: none; }
.t-modal-form .tab-pane > fieldset { margin: 0 -8px; }
.t-modal-form fieldset::after { content: ""; display: table; clear: both; }
.t-modal-form fieldset fieldset { margin: 0; }
.t-modal-form fieldset > [class*="col-"] { padding: 0 8px; margin-bottom: 14px; }
.t-modal-form fieldset > .t-card-titulo { padding: 0 8px; }
.t-modal-form label { font-size: 12px; text-transform: uppercase; letter-spacing: .3px; color: var(--t-texto-suave, #7A6A5E); margin-bottom: 5px; }
.t-modal-form .checkbox label { font-size: 13px; text-transform: none; letter-spacing: 0; color: var(--t-texto, #3A2A22); }
.t-modal-form .checkbox { margin: 0; }
.t-modal-form label sup a { cursor: pointer; text-transform: none; font-weight: 600; margin-left: 4px; }
/* v1.24.14: "Nuevo" / "Quitar" junto al título de cada lista: botones tipo pastilla, más grandes y fáciles de presionar */
.t-modal-form label { display: flex; align-items: center; flex-wrap: wrap; gap: 4px; min-height: 24px; }   /* mismo alto con o sin botón: campos alineados */
.t-modal-form label sup { top: 0; line-height: 1; }
.t-modal-form label sup a { display: inline-flex; align-items: center; gap: 4px; height: 24px; padding: 0 10px; margin-left: 6px; border-radius: 12px;
    border: 1px solid var(--t-primario-borde, #BBE3C9); background: var(--t-primario-suave, #E8F5EC); color: var(--t-primario-osc, #116632) !important;
    font-size: 12px; font-weight: 700; letter-spacing: 0; text-decoration: none !important; transition: background .12s, color .12s, border-color .12s; }
.t-modal-form label sup a::before { content: "\f067"; font-family: 'FontAwesome'; font-size: 10px; }
.t-modal-form label sup a:hover { background: var(--t-primario, #15803D); border-color: var(--t-primario, #15803D); color: #fff !important; }
.t-modal-form label sup a[onclick^="Quitar"] { border-color: #F5C2C0; background: #FDECEC; color: #B42318 !important; }
.t-modal-form label sup a[onclick^="Quitar"]::before { content: "\f00d"; }
.t-modal-form label sup a[onclick^="Quitar"]:hover { background: #D92D20; border-color: #D92D20; color: #fff !important; }
.t-modal-form .checkbox label { display: inline-block; }
.t-modal-form .t-card-titulo { margin-top: 2px; }
.t-modal-form .t-card-titulo i { color: var(--t-primario, #B4432E); margin-right: 4px; }
.t-modal-form .modal-body .panel { box-shadow: none; }
.t-modal-form .modal-footer .btn { min-width: 110px; }

/* Modal principal: Carta de productos */
.t-prod-modal .modal-body { background: var(--t-fondo, #F6F1E8); }
.t-prod-modal .h-panel > .row > [class*="col-md-"] > .nav,
.t-prod-modal .h-panel > .row > div:not([class]) { display: none; }
.t-prod-modal .h-panel > .row > [class*="col-md-"] > br,
.t-prod-modal .h-panel > .row > [class*="col-md-"] > .tab-pane > br { display: none; }
.t-prod-modal .h-panel > .row > [class*="col-md-"] > .tab-pane {
    background: #fff; border: 1px solid var(--t-borde, #EADFD2); border-radius: var(--t-radio, 10px);
    box-shadow: var(--t-sombra); padding: 16px 18px 4px; margin-bottom: 14px;
}
.t-prod-modal .tab-pane > .t-card-titulo:not(:first-child) { margin-top: 6px; padding-top: 12px; border-top: 1px solid var(--t-borde-suave, #F0E8DD); }
.t-prod-modal #tabUnidadMedidaInsumo .btn-group {
    display: block; background: var(--t-fondo-alt, #FBF8F3); border: 1px solid var(--t-borde-suave, #F0E8DD);
    border-radius: var(--t-radio-sm, 6px); padding: 9px 12px; transition: border-color .15s, background-color .15s;
}
.t-prod-modal #tabUnidadMedidaInsumo .btn-group:hover { border-color: var(--t-primario-borde, #EFD2C6); background: var(--t-primario-suave, #FBEEE8); }
.t-prod-modal #extras { margin-top: 6px; padding-top: 12px; border-top: 1px solid var(--t-borde-suave, #F0E8DD); }
.t-prod-modal #extras .btn-group, .t-prod-modal #extras .btn { display: block; width: 100%; }
.t-prod-modal #extras .btn { white-space: normal; background: #fff; color: var(--t-primario, #B4432E); border: 1px solid var(--t-primario-borde, #EFD2C6); }
.t-prod-modal #extras .btn:hover, .t-prod-modal #extras .btn:focus { background: var(--t-primario-suave, #FBEEE8); border-color: var(--t-primario, #B4432E); }
.t-prod-modal #extras .btn[disabled] { opacity: .55; }
@media (min-width: 992px) {
    .t-prod-modal .h-panel > .row { display: flex; flex-wrap: wrap; }
    .t-prod-modal .h-panel > .row > [class*="col-md-"] { display: flex; flex-direction: column; }
    .t-prod-modal .h-panel > .row > [class*="col-md-"] > .tab-pane { flex: 1; }
}
/* v1.24.12: ficha del producto en secciones (Datos / Parámetros / Imagen), una a la vez y a todo el ancho, con letras y
   campos más grandes (antes dos columnas y todo muy chico) */
.prod-secciones { flex: 0 0 auto; position: relative; z-index: 2; display: flex; gap: 6px; padding: 10px 18px 0; background: #fff; border-bottom: 1px solid var(--t-borde, #E5E7EB); overflow-x: auto; }
.prod-sec-btn { flex: 0 0 auto; border: 0; border-bottom: 3px solid transparent; background: transparent; padding: 10px 16px 11px; font-size: 15px; font-weight: 700; color: var(--t-texto-suave, #4B5563); white-space: nowrap; }
.prod-sec-btn i { margin-right: 6px; }
.prod-sec-btn:hover { color: var(--t-texto-fuerte, #111827); }
.prod-sec-btn.activo { color: var(--t-primario, #15803D); border-bottom-color: var(--t-primario, #15803D); }
.t-prod-modal .prod-seccion { display: none !important; }
.t-prod-modal .prod-seccion.activa { display: flex !important; flex-direction: column; }
.t-prod-modal .modal-dialog { max-width: 1180px; }
.t-prod-modal .modal-title { font-size: 19px; }
.t-prod-modal .modal-body { padding: 18px 22px; }
@media (min-width: 768px) { .t-prod-modal .modal-body { min-height: min(600px, calc(100vh - 230px)); } }   /* no salta de alto al cambiar de sección */
.t-prod-modal .h-panel > .row > [class*="col-md-"] > .tab-pane { padding: 20px 24px 8px; }
.t-prod-modal .t-card-titulo { font-size: 14px; margin-bottom: 12px; }
.t-prod-modal .t-card-titulo small { text-transform: none; font-weight: 400; color: var(--t-texto-tenue, #6B7280); }
.t-prod-modal label { font-size: 13px; }
.t-prod-modal .form-control, .t-prod-modal .form-control.input-sm { height: 44px; font-size: 15px; border-radius: 8px; }
.t-prod-modal .select2-container .select2-selection--single { height: 44px; border-radius: 8px; }
.t-prod-modal .select2-container .select2-selection--single .select2-selection__rendered { line-height: 42px; font-size: 15px; padding-left: 12px; }
.t-prod-modal .select2-container .select2-selection--single .select2-selection__arrow { height: 42px; }
.t-prod-modal fieldset > [class*="col-"] { margin-bottom: 16px; }
/* v1.34.1 (pedido del usuario): Datos del producto en una grilla de 6 columnas: 3 campos por fila (o 2 anchos), sin huecos */
.prod-grid { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 0 18px; margin-bottom: 6px; }
.prod-grid > div { grid-column: span 6; min-width: 0; margin-bottom: 16px; }
.prod-grid > .pg-2 { grid-column: span 2; }
.prod-grid > .pg-3 { grid-column: span 3; }
.prod-grid > .pg-4 { grid-column: span 4; }
.prod-grid > [data-id-auto] { display: none; }
.t-prod-modal .prod-grid .destino2-check { display: flex; align-items: center; gap: 6px; margin: 8px 0 0; font-size: 12.5px; font-weight: 600;
    text-transform: none; letter-spacing: 0; color: var(--t-texto-suave, #4B5563); cursor: pointer; min-height: 0; }
.t-prod-modal .prod-grid .destino2-check input { margin: 0; accent-color: var(--t-primario, #15803D); }
.t-prod-modal .prod-grid #cajaDestino2 { margin-top: 6px; }
@media (max-width: 991px) { .prod-grid > .pg-2, .prod-grid > .pg-3 { grid-column: span 3; } .prod-grid > .pg-4 { grid-column: span 6; } }
@media (max-width: 600px) { .prod-grid > .pg-2, .prod-grid > .pg-3, .prod-grid > .pg-4 { grid-column: span 6; } }
.t-prod-modal [data-seccion="datos"] .t-card-titulo { clear: both; }
/* Parámetros: casillas grandes */
.t-prod-modal #tabUnidadMedidaInsumo .btn-group { padding: 14px 16px; }
.t-prod-modal #tabUnidadMedidaInsumo .checkbox label { font-size: 15px; font-weight: 600; }
.t-prod-modal #extras .btn { height: 52px; font-size: 15px; font-weight: 700; border-radius: 10px; }
/* Imagen: grande, en su tarjeta blanca como las otras secciones */
.t-prod-modal #tabImagenProducto { background: #fff; border: 1px solid var(--t-borde, #E5E7EB); border-radius: var(--t-radio, 10px); box-shadow: var(--t-sombra); padding: 20px 24px 8px; }
.t-prod-modal [data-seccion="imagen"] .prod-imagen { padding: 6px 0 14px; gap: 22px; }
.t-prod-modal [data-seccion="imagen"] .prod-imagen-vista { flex-basis: 280px; width: 280px; height: 210px; }
.t-prod-modal [data-seccion="imagen"] .prod-imagen-vacia { font-size: 14px; }
.t-prod-modal [data-seccion="imagen"] .prod-imagen-vacia i { font-size: 44px; }
.t-prod-modal [data-seccion="imagen"] .prod-imagen-acciones .btn { height: 42px; padding: 0 16px; font-size: 14px; }
.t-prod-modal [data-seccion="imagen"] .prod-imagen-ayuda { font-size: 13px; }
.t-prod-modal .modal-footer .btn { height: 44px; font-size: 15px; }
@media (max-width: 600px) { .t-prod-modal [data-seccion="imagen"] .prod-imagen { flex-direction: column; align-items: flex-start; } }

/* v1.20.1: modales de productos centrados en la pantalla. La regla general (theme.css) los deja a 90 px de arriba y el de
   la Carta, más alto que la ventana, quedaba muy abajo y cortado. Con margin:auto dentro de un flex se centran si caben y,
   si no, parten arriba sin cortarse. El de la Carta además nunca pasa del alto de la ventana: título y botones Grabar /
   Salir siempre a la vista y el formulario se desliza por dentro. */
@media (min-width: 768px) {
    .t-modal-form.in { display: flex !important; }
    .t-modal-form .modal-dialog { margin: auto !important; padding: 16px 0; }
    .t-prod-modal .modal-dialog { width: calc(100% - 48px); }
    .t-prod-modal .modal-content { max-height: calc(100vh - 32px); display: flex; flex-direction: column; }
    .t-prod-modal .modal-header, .t-prod-modal .modal-footer { flex: 0 0 auto; }
    .t-prod-modal .modal-body { flex: 1 1 auto; min-height: 0; overflow-y: auto; }
}
@media (max-width: 480px) {
    .t-modal-form .modal-footer { display: flex; flex-direction: column-reverse; gap: 8px; }
    .t-modal-form .modal-footer .btn { width: 100%; margin: 0 !important; }
}
</style>
<div id="modalNuevoProducto" class="modal fade t-modal-form t-prod-modal" role="dialog" style="overflow-y: scroll;">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <!-- TITULO  MODAL  -->
            <div id="tituloModalInsumo" class="modal-header btn-success">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title text text-center">CARTA DE PRODUCTOS
                </h4>
            </div>
            <!-- FIN TITULO  MODAL  -->

            <!-- CONTENIDO  MODAL  -->
                <!-- v1.24.12: secciones de la ficha del producto (antes todo en dos columnas, muy chico) -->
                <div class="prod-secciones" role="tablist" aria-label="Secciones del producto">
                    <button type="button" class="prod-sec-btn activo" data-seccion="datos" role="tab" aria-selected="true"><i class="fa fa-tag"></i> Datos del producto</button>
                    <button type="button" class="prod-sec-btn" data-seccion="parametros" role="tab" aria-selected="false"><i class="fa fa-sliders"></i> Parámetros de venta</button>
                    <button type="button" class="prod-sec-btn" data-seccion="imagen" role="tab" aria-selected="false"><i class="fa fa-picture-o"></i> Imagen</button>
                </div>
                <div class="modal-body">
                    <div class="h-panel">
                        <div class="row">
                         <div class="col-md-12 prod-seccion activa" data-seccion="datos">
                            <ul id="myTab" class="nav nav-tabs title font">
                                <li class="active">
                                    <a href="#tabDatosInsumo" data-toggle="tab" id="tab1" class="text color1"></a>
                                </li>
                            </ul>
                            <div class="tab-pane fade active in" id="tabDatosInsumo">
                                <br>
                                <!-- CAMPOS DE REGISTRO  -->
                                <!-- v1.34.1 (pedido del usuario): datos en una grilla ordenada (antes los selects ocupaban media pantalla
                                     cada uno y quedaban huecos). Sin el impuesto adicional (ILA): ya no va en la Carta. -->
                                <div class="t-card-titulo"><i class="fa fa-tag"></i> Datos del producto</div>
                                <div class="prod-grid">
                                    <div style="display:none" data-id-auto="1">
                                        <label for="txtID">ID</label>
                                        <input type="text" disabled class="form-control input-sm input-sm" name="txtID"
                                            id="txtID" requerido="no" value="">
                                    </div>
                                    <div class="pg-2">
                                        <label for="txtCbarra">Cód. Barra</label>
                                        <input type="text" autocomplete="off" class="form-control input-sm input-sm"
                                            name="txtCbarra" id="txtCbarra" requerido="no" value="">
                                    </div>
                                    <div class="pg-4">
                                        <label for="txtNombre">Nombre Producto</label>
                                        <input type="text" autocomplete="off" maxlength="100"
                                            class="form-control input-sm input-sm" name="txtNombre" id="txtNombre"
                                            requerido="si" value="">
                                    </div>
                                </div>
                                <div class="t-card-titulo"><i class="fa fa-sitemap"></i> Clasificación</div>
                                <div class="prod-grid">
                                    <div class="pg-2">
                                        <label for="cboFamilia">Familia
                                            <sup><a onclick="crearNuevaFamilia()">Nuevo</a></sup>
                                        </label>
                                        <select id="cboFamilia" nombre="cboFamilia" class="form-control input-sm"
                                            style="width:100%" requerido="si"></select>
                                    </div>
                                    <div class="pg-2">
                                        <label for="cboSubFamilia">Sub Familia
                                            <sup><a onclick="crearNuevaSubFamilia()">Nuevo</a></sup>
                                        </label>
                                        <select id="cboSubFamilia" nombre="cboSubFamilia" class="form-control input-sm"
                                            style="width:100%" requerido="si"></select>
                                    </div>
                                    <div class="pg-2">
                                        <label for="cboUmedida">U/Medida</label>
                                        <select id="cboUmedida" nombre="cboUmedida" class="form-control input-sm"
                                            style="width:100%" requerido="si"></select>
                                    </div>
                                </div>
                                <div class="t-card-titulo"><i class="fa fa-cubes"></i> Venta e impresión</div>
                                <div class="prod-grid">
                                    <div class="pg-2">
                                        <label for="cboBodega">Bodega
                                            <sup><a onclick="crearNuevaBodega()">Nuevo</a></sup>
                                        </label>
                                        <select id="cboBodega" nombre="cboBodega" class="form-control input-sm"
                                            style="width:100%" requerido="si"></select>
                                    </div>
                                    <div class="pg-2">
                                        <label for="cboImpuestoIva">Impuesto Iva</label>
                                        <select id="cboImpuestoIva" class="form-control input-sm" style="width:100%"
                                            requerido="si"></select>
                                    </div>
                                    <div class="pg-2">
                                        <label for="cboDestinoDeImpresion">Destino De Impresion </label>
                                        <select id="cboDestinoDeImpresion" nombre="cboDestinoDeImpresion"
                                            class="form-control input-sm" style="width:100%" requerido="si"></select>
                                        <!-- v1.30.0 (pedido del usuario): el mismo producto en dos comandas (ej. arroz en la cocina y carne en la parrilla) -->
                                        <label class="destino2-check" title="El producto sale completo en su destino de impresión y también en este (ej. arroz con carne: el arroz en la cocina y la carne en la parrilla). Cada comanda dice dónde más salió.">
                                            <input type="checkbox" id="ckDestino2"> Imprimir también en otro destino
                                        </label>
                                        <div id="cajaDestino2" style="display:none"><select id="cboDestino2" class="form-control input-sm" style="width:100%" aria-label="Segundo destino de impresión"></select></div>
                                    </div>
                                </div>
                                <div class="t-card-titulo"><i class="fa fa-book"></i> Receta y menú</div>
                                <div class="prod-grid">
                                    <div class="pg-3">
                                        <label for="cboReceta">Receta
                                            <sup><a onclick="AbrirModalNuevoReceta()">Nuevo</a></sup>
                                        </label>
                                        <select id="cboReceta" class="form-control input-sm" style="width:100%"
                                            requerido="si"></select>
                                    </div>
                                    <div class="pg-3">
                                        <label for="cboMenu">Menu
                                            <sup><a onclick="AbrirModalListadoMenu()" style="color: var(--t-primario, #B4432E); cursor: pointer;">Nuevo</a></sup>
                                            <sup><a onclick="QuitarMenuDeProducto()" style="color: var(--t-peligro, #C9372C); cursor: pointer;">Quitar</a></sup>
                                        </label>
                                        <select id="cboMenu" class="form-control input-sm" style="width:100%"
                                            requerido="no"></select>
                                    </div>
                                </div>

                                <!-- FIN CAMPOS DE REGISTRO  -->
                            </div>
                         </div>
                         <div class="col-md-12 prod-seccion" data-seccion="parametros">
                            <ul id="myTab2" class="nav nav-tabs title font">
                                <li id="tabUmedida" class="active">
                                    <a href="#tabUnidadMedidaInsumo" data-toggle="tab" id="tab2"
                                        class="text color1">Parametros</a>
                                </li>

                            </ul>
                            <div class="tab-pane fade active in" id="tabUnidadMedidaInsumo">
                                <div class="t-card-titulo"><i class="fa fa-sliders"></i> Parámetros de venta</div>
                                <fieldset>
                                    <div class="col-md-4">
                                        <div class="btn-group" role="group" aria-label="...">
                                            <div class="checkbox checkbox-success checkbox-circle">
                                                <input id="ckVigente" type="checkbox">
                                                <label for="ckVigente">
                                                    Vigente
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="btn-group" role="group" aria-label="...">
                                            <div class="checkbox checkbox-success checkbox-circle">
                                                <input id="ckVentaPublico" type="checkbox">
                                                <label for="ckVentaPublico">
                                                    Venta A Publico
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="btn-group" role="group" aria-label="...">
                                            <div class="checkbox checkbox-success checkbox-circle">
                                                <input id="ckPermitirVentaSiempre" type="checkbox">
                                                <label for="ckPermitirVentaSiempre">
                                                    Permitir Venta Siempre
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </fieldset>
                                <br>
                                <fieldset>
                                    <div class="col-md-4">
                                        <div class="btn-group" role="group" aria-label="...">
                                            <div class="checkbox checkbox-success checkbox-circle">
                                                <input id="ckVentaDirecta" type="checkbox">
                                                <label for="ckVentaDirecta">
                                                    Venta Directa (1 a 1)
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="btn-group" role="group" aria-label="...">
                                            <div class="checkbox checkbox-success checkbox-circle">
                                                <input id="ckConAgregado" type="checkbox">
                                                <label for="ckConAgregado">
                                                    Con Agregado
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="btn-group" role="group" aria-label="...">
                                            <div class="checkbox checkbox-success checkbox-circle">
                                                <input id="ckPermitirFraccion" type="checkbox">
                                                <label for="ckPermitirFraccion">
                                                    Permitir Fraccion
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </fieldset>
                                <br>
                                <fieldset>
                                    <div class="col-md-4">
                                        <div class="btn-group" role="group" aria-label="...">
                                            <div class="checkbox checkbox-success checkbox-circle">
                                                <input id="ckVentaPorStock" type="checkbox">
                                                <label for="ckVentaPorStock">
                                                    Venta Por Stock
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="btn-group" role="group" aria-label="...">
                                            <div class="checkbox checkbox-success checkbox-circle">
                                                <input id="ckElaboracionLocal" type="checkbox">
                                                <label for="ckElaboracionLocal">
                                                    Elaboracion Local
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="btn-group" role="group" aria-label="...">
                                            <div class="checkbox checkbox-success checkbox-circle">
                                                <input id="ckPedirCantidadEnVenta" type="checkbox">
                                                <label for="ckPedirCantidadEnVenta">
                                                    Pedir Cantidad En venta
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </fieldset>
                                <br>
                                <fieldset>
                                    <div class="col-md-4">
                                        <div class="btn-group" role="group" aria-label="...">
                                            <div class="checkbox checkbox-success checkbox-circle">
                                                <input id="ckVerDispositivomovil" type="checkbox">
                                                <label for="ckVerDispositivomovil">
                                                    Ver Dispositivo Movil
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="btn-group" role="group" aria-label="...">
                                            <div class="checkbox checkbox-success checkbox-circle">
                                                <input id="ckPermiteCortesia" type="checkbox">
                                                <label for="ckPermiteCortesia">
                                                    Permite Cortesia
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Favorito: aparece en el botón ★ de la Caja. Se oculta si la base aún no tiene producto.favorito (migración 20261110190000) -->
                                    <div class="col-md-4" id="grupoFavorito">
                                        <div class="btn-group" role="group" aria-label="...">
                                            <div class="checkbox checkbox-success checkbox-circle">
                                                <input id="ckFavorito" type="checkbox">
                                                <label for="ckFavorito" title="Aparece en el botón Favoritos (★) de la Caja">
                                                    Favorito En Caja
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </fieldset>
                                <br>
                                <fieldset id="extras">
                                    <div class="t-card-titulo"><i class="fa fa-puzzle-piece"></i> Configuración adicional</div>
                                    <div class="col-md-4">
                                        <div class="btn-group" role="group" aria-label="...">
                                            <button id="btnAgregados" onclick="AbrirModalAgregadosDelProducto()"
                                                type="button" class="btn btn-success"><i class="fa fa-plus-square"></i> Agregados Del
                                                Producto</button>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="btn-group" role="group" aria-label="...">
                                            <button id="BtnMensajesXProducto" onclick="AbrirModalMensajesDelProducto()"
                                                type="button" class="btn btn-success"><i class="fa fa-commenting-o"></i> Mensaje Predefinidos</button>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="btn-group" role="group" aria-label="...">
                                            <button id="BtnPrecioVenta" onclick="AbrirModalPrecioVenta()" type="button"
                                                class="btn btn-success"><i class="fa fa-usd"></i> Precio Venta</button>
                                        </div>
                                    </div>

                                </fieldset>
                                <br>

                                </div>





                            </div>

                         </div>
                         <div class="col-md-12 prod-seccion" data-seccion="imagen">
                            <div class="tab-pane fade active in" id="tabImagenProducto">
                                <!-- Imagen del producto (se ve en la Caja). La maneja productos/jsImagen.php -->
                                <div class="t-card-titulo"><i class="fa fa-picture-o"></i> Imagen del producto <small>(se ve en la Caja)</small></div>
                                <div class="prod-imagen prod-imagen-grande" id="prodImagen">
                                    <div class="prod-imagen-vista" id="prodImagenVista">
                                        <img id="prodImagenImg" alt="" style="display:none">
                                        <span class="prod-imagen-vacia" id="prodImagenVacia"><i class="fa fa-picture-o"></i>Sin imagen</span>
                                    </div>
                                    <div class="prod-imagen-acciones">
                                        <input type="file" id="prodImagenArchivo" accept="image/jpeg,image/png,image/webp,image/gif" style="display:none">
                                        <button type="button" class="btn btn-default btn-sm" id="btnProdImagenSubir"><i class="fa fa-upload"></i> Subir imagen</button>
                                        <button type="button" class="btn btn-default btn-sm" id="btnProdImagenBuscar"><i class="fa fa-search"></i> Buscar en línea</button>
                                        <button type="button" class="btn btn-default btn-sm" id="btnProdImagenQuitar" style="display:none"><i class="fa fa-trash"></i> Quitar</button>
                                        <small class="prod-imagen-ayuda" id="prodImagenAyuda">JPG, PNG o WEBP de hasta 6 MB. Se guarda al elegirla.</small>
                                    </div>
                            </div>
                         </div>
                        </div>
                    </div>
                </div>
            <!-- CONTENIDO  MODAL  -->

            <!-- BOTONES  -->
            <div class="modal-footer">
                <button onclick="GrabarProducto()" type="button" class="btn btn-primary"><i class="fa fa-check"></i> Grabar Producto</button>
                <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> SALIR</button>
            </div>
            <!-- FIN BOTONES  -->
        </div>
    </div>
</div>

<div id="modalPrecioVentaProducto" class="modal fade t-modal-form" role="dialog" style="overflow-y: scroll;">
    <div class="modal-dialog modal-lg"> <!-- Cambiado a modal-lg para más espacio -->
        <div class="modal-content">
            <div class="modal-header btn-success">
                <button type="button" class="close" onclick="cancelarEdicionPrecios()" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title color4 f-bold">
                    PRECIO DE VENTA Y COMISIÓN
                </h4>
            </div>
            <div class="modal-body">
                <input hidden type="text" name="idProducto" id="idProducto" requerido="no" value="">
                <fieldset>
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-body table-responsive">
                                <table id="tablePrecios" class="table table-hover display" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th style='display:none'>ID</th>
                                            <th style="width:30%">LISTA DE PRECIOS</th>
                                            <th class="text-right" style="width:30%">PRECIO BRUTO</th>
                                            <th class="text-right" style="width:30%">COMISIÓN</th>
                                        </tr>
                                    </thead>
                                    <tbody id="detallePrecioVenta">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </fieldset>
            </div>
            <div class="modal-footer">
                <!-- onclick="grabarPreciosYProducto()" -->
                <button  id="btnGrabarPrecios" type="button" class="btn btn-primary">
                    <i class="fa fa-check"></i> Grabar Precios y Comisiones
                </button>
                <button onclick="cancelarEdicionPrecios()" type="button" class="btn btn-default">
                    <i class="fa fa-times"></i> Cancelar
                </button>
            </div>
        </div>
    </div>
</div>
<div id="ModalNuevaFamilia" class="modal fade t-modal-form" role="dialog" style="overflow-y: scroll;">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- TITULO  MODAL  -->
            <div id="tituloModal" class="modal-header btn-success">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title text text-center">NUEVA FAMILIA
                </h4>
            </div>
            <!-- FIN TITULO  MODAL  -->

            <!-- CONTENIDO  MODAL  -->
            <div class="modal-body">
                <div class="h-panel">
                    <ul id="myTab" class="nav nav-with title font">
                        <li class="active"><a href="#tabDatosUsuarios" data-toggle="tab" id="tab1"
                                class="text color1"></a></li>
                    </ul>
                    <div class="tab-pane fade active in" id="tabDatosUsuarios">
                        <!-- CAMPOS DE REGISTRO  -->
                        <fieldset>
                            <div class="col-sm-4" style="display:none" data-id-auto="1">
                                <label for="idFamilia">ID</label>
                                <input type="number" disabled class="form-control input-sm input-sm" name="idFamilia"
                                    id="idFamilia" requerido="no" value="">
                            </div>
                        </fieldset>
                        <fieldset>
                            <div class="col-sm-6">
                                <label for="nombreFamilia">FAMILIA *</label>
                                <input type="text" class="form-control input-sm input-sm" maxlength='28'
                                    onkeypress="return sololetrasyNumeros(event)" onpaste="return false;"
                                    ondrop="return false;" autocomplete="off" name="nombreFamilia" id="nombreFamilia"
                                    requerido="si" value="" placeholder="Nombre Familia" />
                            </div>

                            <div class="col-sm-6">
                                <label for="AliasFamilia">ALIAS FAMILIA</label>
                                <input type="text" class="form-control input-sm input-sm" maxlength='28'
                                    onkeypress="return sololetrasyNumeros(event)" onpaste="return false;"
                                    ondrop="return false;" autocomplete="off" name="AliasFamilia" id="AliasFamilia"
                                    requerido="si" value="" placeholder="Alias Familia" />
                            </div>
                        </fieldset>
                        <fieldset>
                            <div class="col-md-7">
                                <label for="cboDestinoDeImpresionFamilia">DESTINO DE IMPRESION</label>
                                <select id="cboDestinoDeImpresionFamilia" nombre="cboDestinoDeImpresionFamilia"
                                    class="form-control input-sm" style="width:100%" requerido="si"></select>
                            </div>
                            <br>
                            <div class="col-md-5">
                                <div class="btn-group" role="group" aria-label="...">
                                    <div class="checkbox checkbox-success checkbox-circle">
                                        <input id="ckVentaDirectaFamilia" type="checkbox">
                                        <label for="ckVentaDirectaFamilia">
                                            VENTA DIRECTA
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                        <fieldset>
                            <div class="col-md-5 ">
                                <label for="ordenFamilia">ORDEN FAMILIA</label>
                                <input type="number" class="form-control input-sm input-sm" maxlength='28'
                                    onkeypress="return sololetrasyNumeros(event)" onpaste="return false;"
                                    ondrop="return false;" autocomplete="off" name="ordenFamilia" id="ordenFamilia"
                                    requerido="si" value="" placeholder="Orden Familia" />
                            </div>
                            <br>
                            <div class="col-md-7">
                                <div class=" btn-group" role="group" aria-label="...">
                                    <div class="checkbox checkbox-success checkbox-circle">
                                        <input id="ckImprimirEnDetallePedido" type="checkbox">
                                        <label for="ckImprimirEnDetallePedido">
                                            IMPRIMIR EN DETALLE PEDIDO
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </fieldset>

                        <!-- FIN CAMPOS DE REGISTRO  -->
                    </div>
                </div>
            </div>
            <!-- CONTENIDO  MODAL  -->

            <!-- BOTONES  -->
            <div class="modal-footer">
                <button onclick="GrabarNuevaFamilia()" type="button" class="btn btn-primary"><i class="fa fa-check"></i> GRABAR</button>
                <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> SALIR</button>
            </div>
            <!-- FIN BOTONES  -->
        </div>
    </div>
</div>
<!-- FIN  MODAL NUEVO REGISTRO  -->
<div id="ModalNuevaBodega" class="modal fade t-modal-form" role="dialog" style="overflow-y: scroll;">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- TITULO  MODAL  -->
            <div id="tituloModal" class="modal-header btn-success">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title text text-center">NUEVA BODEGA
                </h4>
            </div>
            <!-- FIN TITULO  MODAL  -->

            <!-- CONTENIDO  MODAL  -->
            <div class="modal-body">
                <div class="h-panel">
                    <ul id="myTab" class="nav nav-with title font">
                        <li class="active"><a href="#tabDatosUsuarios" data-toggle="tab" id="tab1"
                                class="text color1"></a></li>
                    </ul>
                    <div class="tab-pane fade active in" id="tabDatosUsuarios">
                        <!-- CAMPOS DE REGISTRO  -->
                        <fieldset>
                            <div class="col-sm-3" style="display:none" data-id-auto="1">
                                <label for="idBodega">ID</label>
                                <input type="number" disabled class="form-control input-sm input-sm" name="idBodega"
                                    id="idBodega" requerido="no" value="">
                            </div>
                            <div class="col-sm-12">
                                <label for="nombreBodega">BODEGA *</label>
                                <input type="text" class="form-control input-sm input-sm" maxlength='28'
                                    onkeypress="return sololetrasyNumeros(event)" onpaste="return false;"
                                    ondrop="return false;" autocomplete="off" name="nombreBodega" id="nombreBodega"
                                    requerido="si" value="" placeholder="Nombre Bodega" />
                            </div>
                        </fieldset>
                        <!-- FIN CAMPOS DE REGISTRO  -->
                    </div>
                </div>
            </div>
            <!-- CONTENIDO  MODAL  -->

            <!-- BOTONES  -->
            <div class="modal-footer">
                <button onclick="GrabarNuevaBodega()" type="button" class="btn btn-primary"><i class="fa fa-check"></i> GRABAR</button>
                <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> SALIR</button>
            </div>
            <!-- FIN BOTONES  -->
        </div>
    </div>
</div>
<!-- FIN  MODAL NUEVO REGISTRO  -->

<div id="ModalNuevaSubFamilia" class="modal fade t-modal-form" role="dialog" style="overflow-y: scroll;">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- TITULO  MODAL  -->
            <div id="tituloModal" class="modal-header btn-success">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title text text-center">NUEVA SUB FAMILIA
                </h4>
            </div>
            <!-- FIN TITULO  MODAL  -->

            <!-- CONTENIDO  MODAL  -->
            <div class="modal-body">
                <div class="h-panel">
                    <ul id="myTab" class="nav nav-with title font">
                        <li class="active"><a href="#tabDatosUsuarios" data-toggle="tab" id="tab1"
                                class="text color1"></a></li>
                    </ul>
                    <div class="tab-pane fade active in" id="tabDatosUsuarios">
                        <!-- CAMPOS DE REGISTRO  -->
                        <fieldset>
                            <div class="col-md-4 " style="display:none" data-id-auto="1">
                                <label for="idSubFamilia">ID</label>
                                <input type="number" disabled class="form-control input-sm input-sm" name="idSubFamilia"
                                    id="idSubFamilia" requerido="no" value="">
                            </div>
                        </fieldset>
                        <fieldset>
                            <div class="col-md-12">
                                <label for="cboFamiliaMsubFamilia">FAMILIA</label>
                                <select id="cboFamiliaMsubFamilia" nombre="cboFamiliaMsubFamilia"
                                    class="form-control input-sm" style="width:100%" requerido="si"></select>
                            </div>
                            <div class="col-md-12">
                                <label for="nombreSubFamilia">SUB FAMILIA *</label>
                                <input type="text" class="form-control input-sm input-sm" maxlength='28'
                                    onkeypress="return sololetrasyNumeros(event)" onpaste="return false;"
                                    ondrop="return false;" autocomplete="off" name="nombreSubFamilia"
                                    id="nombreSubFamilia" requerido="si" value="" placeholder="Nombre SubFamilia" />
                            </div>
                        </fieldset>
                        <!-- FIN CAMPOS DE REGISTRO  -->
                    </div>
                </div>
            </div>
            <!-- CONTENIDO  MODAL  -->

            <!-- BOTONES  -->
            <div class="modal-footer">
                <button onclick="GrabarNuevaSubFamilia()" type="button" class="btn btn-primary"><i class="fa fa-check"></i> GRABAR</button>
                <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> SALIR</button>
            </div>
            <!-- FIN BOTONES  -->
        </div>
    </div>
</div>
<!-- FIN  MODAL NUEVO REGISTRO  -->

<!-- v1.24.17: copiar los agregados de un producto a otro o varios (la maneja productos/jsAgregados.php) -->
<style>
#ModalCopiarAgregados .modal-dialog { width: 760px; max-width: calc(100vw - 24px); }
/* v1.33.1 (pedido del usuario): la ventana Agregados del producto más ancha (la tabla de abajo se veía apretada) */
#ModalAgregadosDelProducto .modal-dialog { width: 1360px; max-width: calc(100vw - 24px); margin-top: 16px; }
#ModalAgregadosDelProducto #tableAgregados th { white-space: nowrap; vertical-align: bottom; }
#ModalAgregadosDelProducto #tableAgregados td { vertical-align: middle; }
#ModalAgregadosDelProducto #tableAgregados .cantidad-agregado-input, #ModalAgregadosDelProducto #tableAgregados .valor-agregado-input { min-width: 80px; }
#ModalAgregadosDelProducto .ag-destino-propio { min-width: 150px; }
#ModalAgregadosDelProducto .ag-destino-propio small { white-space: nowrap; }
#ModalCopiarAgregados .modal-content { border: 0; border-radius: 14px; overflow: hidden; }
.ca-cab { display: flex; align-items: center; gap: 12px; padding: 14px 18px; border-bottom: 1px solid var(--t-borde, #E5E7EB); background: #fff; }
.ca-cab-icono { width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; background: var(--t-primario-suave, #E8F5EC); color: var(--t-primario, #15803D); font-size: 18px; }
.ca-cab h4 { margin: 0; font-size: 18px; font-weight: 800; text-align: left; }
.ca-cab small { display: block; font-size: 12px; color: var(--t-texto-tenue, #6B7280); }
.ca-cab .close { margin-left: auto; font-size: 26px; }
#ModalCopiarAgregados .modal-body { padding: 16px 18px; background: var(--t-fondo, #F5F6F8); }
.ca-origen { background: #fff; border: 1px solid var(--t-borde, #E5E7EB); border-radius: 12px; padding: 12px 14px; margin-bottom: 12px; }
.ca-origen-tit { font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: .3px; color: var(--t-texto-suave, #4B5563); margin-bottom: 8px; }
.ca-chips { display: flex; flex-wrap: wrap; gap: 6px; max-height: 88px; overflow-y: auto; }
.ca-chip { padding: 4px 10px; border-radius: 14px; background: var(--t-primario-suave, #E8F5EC); color: var(--t-primario-osc, #116632); font-size: 13px; font-weight: 600; }
.ca-chip small { opacity: .75; font-weight: 500; }
#ModalCopiarAgregados .ag-varios-lista { max-height: 300px; }
#ModalCopiarAgregados .ag-op { font-size: 14px; padding: 9px 10px; }
#ModalCopiarAgregados .ag-buscar input { height: 40px; font-size: 15px; }
.ca-modo { background: #fff; border: 1px solid var(--t-borde, #E5E7EB); border-radius: 12px; padding: 10px 14px; margin-top: 12px; }
.ca-modo-tit { font-size: 13px; font-weight: 700; margin-bottom: 4px; }
.ca-modo label { display: flex; gap: 8px; align-items: flex-start; font-weight: 500; font-size: 14px; margin: 6px 0 0; cursor: pointer; }
.ca-modo label input { margin-top: 3px; accent-color: var(--t-primario, #15803D); }
.ca-modo small { display: block; color: var(--t-texto-tenue, #6B7280); font-size: 12px; }
#ModalCopiarAgregados .modal-footer { background: #fff; }
#ModalCopiarAgregados .modal-footer .btn { height: 42px; min-width: 120px; font-weight: 700; border-radius: 10px; }
#btnCopiarAgregados { float: right; height: 40px; padding: 0 14px; border-radius: 10px; font-weight: 700; }
#btnCopiarAgregados:hover { background: var(--t-primario, #15803D); border-color: var(--t-primario, #15803D); color: #fff; }
@media (max-width: 600px) { #btnCopiarAgregados { float: none; display: block; width: 100%; margin-bottom: 10px; } }
#ModalCopiarAgregados .ca-modo label, #ModalCopiarAgregados .ca-modo small { text-transform: none; letter-spacing: 0; }
#ModalCopiarAgregados .ca-modo label { display: flex; }
#btnCopiarAgregados i { margin-right: 5px; }
</style>
<div id="ModalCopiarAgregados" class="modal fade t-modal-form" role="dialog" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="ca-cab">
                <div class="ca-cab-icono"><i class="fa fa-clone"></i></div>
                <div><h4>Copiar agregados</h4><small id="caSubtitulo"></small></div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">&times;</button>
            </div>
            <div class="modal-body">
                <div class="ca-origen">
                    <div class="ca-origen-tit"><i class="fa fa-plus-square"></i> Agregados que se copian</div>
                    <div class="ca-chips" id="caChips"></div>
                </div>
                <div class="ag-varios">
                    <div class="ag-varios-barra">
                        <div class="ag-buscar"><i class="fa fa-search"></i><input type="text" id="txtBuscarCopiarAg" class="form-control" placeholder="Buscar producto donde copiarlos…" autocomplete="off"></div>
                        <button type="button" class="btn btn-default btn-sm" id="btnCaMarcar"><i class="fa fa-check-square-o"></i> Marcar los que se ven</button>
                        <button type="button" class="btn btn-default btn-sm" id="btnCaLimpiar"><i class="fa fa-eraser"></i> Limpiar</button>
                    </div>
                    <div class="ag-varios-lista" id="listaCopiarAg"></div>
                    <div class="ag-varios-pie"><span id="lblCaElegidos">Ningún producto elegido</span><small>Ningún agregado se borra: solo se suman.</small></div>
                </div>
                <div class="ca-modo">
                    <div class="ca-modo-tit">Si el producto ya tiene alguno de estos agregados:</div>
                    <label><input type="radio" name="caModo" value="0" checked><span>Dejarlo como está<small>Solo se suman los agregados que le faltan.</small></span></label>
                    <label><input type="radio" name="caModo" value="1"><span>Ponerle la cantidad y el valor de este producto<small>Así todos quedan iguales.</small></span></label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btnCaCopiar" disabled><i class="fa fa-clone"></i> Copiar</button>
            </div>
        </div>
    </div>
</div>

<div id="ModalAgregadosDelProducto" class="modal fade t-modal-form" role="dialog" style="overflow-y: scroll;">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- TITULO  MODAL  -->
            <div id="tituloModal" class="modal-header btn-success">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title text text-center">AGREGADOS
                </h4>
            </div>
            <!-- FIN TITULO  MODAL  -->

            <!-- CONTENIDO  MODAL  -->
            <div class="modal-body">
                <div class="h-panel">
                    <ul id="myTab" class="nav nav-with title font">
                        <li class="active"><a href="#tabDatosUsuarios" data-toggle="tab" id="tab1"
                                class="text color1"></a></li>
                    </ul>
                    <div class="tab-pane fade active in" id="tabDatosUsuarios">
                        <!-- CAMPOS DE REGISTRO  -->
                        <!-- v1.35.4 (pedido del usuario): ventana más simple, en 3 partes: reglas en la Caja, agregar y la lista.
                             Las explicaciones largas quedan en el (i) / al pasar el mouse. Mismos ids que antes. -->
                        <input id="ckVariosAgregados" type="checkbox" hidden>

                        <!-- v1.35.4: a lo ancho: a la izquierda reglas y agregar, a la derecha la lista -->
                        <div class="agx-grid">
                        <div class="agx-izq">
                        <!-- 1. Reglas en la Caja: máximo y extras con cobro (se guardan solos) -->
                        <div class="agx-reglas" id="bloqueMaxAgregados">
                            <div class="agx-regla">
                                <span class="agx-etq">Máximo a elegir <i class="fa fa-info-circle" title="Cuántos agregados puede elegir el cliente de este producto en la Caja. ∞ = todos."></i></span>
                                <div class="ag-contador ag-contador-sm" role="group" aria-label="Máximo de agregados">
                                    <button type="button" class="ag-cont-btn" data-paso="-1" title="Uno menos" aria-label="Uno menos"><i class="fa fa-minus"></i></button>
                                    <input type="text" id="txtMaxAgregados" inputmode="numeric" maxlength="3" aria-label="Máximo de agregados" autocomplete="off">
                                    <button type="button" class="ag-cont-btn" data-paso="1" title="Uno más" aria-label="Uno más"><i class="fa fa-plus"></i></button>
                                </div>
                                <button type="button" class="ag-sin-limite agx-sm" id="btnSinLimiteAg" title="Que se puedan elegir todos"><span class="ag-inf">∞</span></button>
                                <span class="ag-maximo-estado" id="estadoMaxAgregados" aria-live="polite"></span>
                            </div>
                            <!-- v1.35.1: extras con cobro (solo si está activado en Configuraciones) -->
                            <label class="agx-regla agx-switch" id="bloqueExtrasProducto" style="display:none">
                                <input id="ckExtrasProducto" type="checkbox">
                                <span class="op-switch-pista" aria-hidden="true"></span>
                                <span class="agx-etq">Extras con cobro <i class="fa fa-info-circle" id="lblExtrasProducto" title=""></i></span>
                            </label>
                            <small class="agx-resumen" id="lblMaxAgregados"></small>
                            <small class="agx-resumen agx-nota" id="lblNotaExtras" style="display:none"><i class="fa fa-info-circle"></i>
                                Cuántos extras se pueden sumar se define en <b>Configuraciones &rsaquo; Agregados en la Caja</b>. Cada extra cobra el <b>Valor $</b> de su agregado (columna de la lista).</small>
                        </div>

                        <!-- 2. Agregar -->
                        <div class="agx-caja">
                            <div class="agx-caja-cab">
                                <div class="ag-modo" role="tablist" aria-label="Cómo agregar">
                                    <button type="button" class="ag-modo-btn activo" data-modo="uno" role="tab" aria-selected="true"><i class="fa fa-plus"></i> De a uno</button>
                                    <button type="button" class="ag-modo-btn" data-modo="varios" role="tab" aria-selected="false"><i class="fa fa-th-list"></i> Varios a la vez</button>
                                </div>
                                <button type="button" class="btn btn-link btn-sm agx-copiar" id="btnCopiarAgregados" onclick="abrirCopiarAgregados()" title="Copiar los agregados de este producto a otro o varios"><i class="fa fa-clone"></i> Copiar a otros productos</button>
                            </div>
                            <div id="bloqueVariosAgregados" style="display:none">
                                <div class="ag-varios">
                                    <div class="ag-varios-barra">
                                        <div class="ag-buscar"><i class="fa fa-search"></i><input type="text" id="txtBuscarVariosAgregados" class="form-control input-sm" placeholder="Buscar producto…" autocomplete="off"></div>
                                        <button type="button" class="btn btn-default btn-sm" id="btnMarcarVisiblesAg" title="Marcar los que se ven"><i class="fa fa-check-square-o"></i> Marcar todos</button>
                                        <button type="button" class="btn btn-default btn-sm" id="btnLimpiarAg" title="Desmarcar"><i class="fa fa-eraser"></i></button>
                                    </div>
                                    <div class="ag-varios-lista" id="listaVariosAgregados"></div>
                                    <div class="ag-varios-pie"><span id="lblElegidosAg">Ningún producto elegido</span></div>
                                </div>
                            </div>
                            <div class="agx-fila">
                                <div class="agx-campo agx-producto" id="bloqueUnAgregado">
                                    <label for="cboProductoAgregadoProductos" id="lblProductoAgregado">Producto</label>
                                    <select id="cboProductoAgregadoProductos" nombre="cboProductoAgregadoProductos" class="form-control input-sm" style="width:100%" requerido="si"></select>
                                </div>
                                <div class="agx-campo agx-num">
                                    <label for="cantidadAgregado">Cantidad</label>
                                    <input type="number" min="0" max="50000" class="form-control input-sm" onkeypress="return soloNumeros(event)" onpaste="return false;"
                                        ondrop="return false;" autocomplete="off" name="cantidadAgregado" id="cantidadAgregado" requerido="si" value="" />
                                </div>
                                <div class="agx-campo agx-num">
                                    <label for="valorAdicionalAgregado">Valor $</label>
                                    <input type="number" min="0" max="50000" class="form-control input-sm" onkeypress="return soloNumeros(event)" onpaste="return false;"
                                        ondrop="return false;" autocomplete="off" name="valorAdicionalAgregado" id="valorAdicionalAgregado" requerido="si" value="" />
                                </div>
                                <button onclick="GrabarNuevoAgregados()" type="button" class="btn btn-success agx-grabar" id="btnGrabarAgregado"><i class="fa fa-plus"></i> Agregar</button>
                            </div>
                            <div class="agx-opciones">
                                <label title="El agregado sale en la impresora que tiene en la Carta (ej. la salsa en la barra). Apagado: sale junto con el producto.">
                                    <input id="ckUsarDestinoDeImpresion" type="checkbox"> <i class="fa fa-print"></i> Imprimir en su propio destino</label>
                                <label title="Se ofrece en la Caja. Apagado: queda guardado, pero no se ofrece.">
                                    <input id="ckVigenciaAgregado" type="checkbox"> Activo</label>
                                <!-- v1.30.1: a qué impresora sale -->
                                <span id="infoDestinoAgregado" class="agx-destino" style="display:none"></span>
                            </div>
                        </div>

                        </div>
                        <div class="agx-der">
                        <!-- 3. Lista de agregados del producto (cantidad y valor se guardan al cambiarlos) -->
                        <div class="agx-lista-cab"><b>Agregados de este producto</b> <span id="lblCuantosAg"></span></div>
                        <div class="table-responsive agx-tabla">
                            <table id="tableAgregados" class="table table-hover" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Agregado</th>
                                        <th class="text-center" style="width:90px">Cantidad</th>
                                        <th class="text-center" style="width:110px">Valor $</th>
                                        <th class="text-center" style="width:120px" title="Imprimir en su propio destino">Su impresora</th>
                                        <th class="text-center" style="width:80px">Activo</th>
                                    </tr>
                                </thead>
                                <tbody id="detalleAgregadosDelProducto">
                                </tbody>
                            </table>
                        </div>
                        </div>
                        </div>

                        <!-- FIN CAMPOS DE REGISTRO  -->
                    </div>
                </div>
            </div>
            <!-- CONTENIDO  MODAL  -->

            <!-- BOTONES  -->
            <div class="modal-footer">
                <button id="btnFinalizarCreacion" onclick="finalizarYCerrarAgregados()" 
                        type="button" class="btn btn-success" style="display:none;">
                    <i class="fa fa-check"></i> Finalizar y Guardar
                </button>   
                <!-- <button id="btnActualizarAgregado" type="button" class="btn btn-primary"><i
                        class=""></i>ACTUALIZAR AGREGADO</button> -->
                <button type="button" class="btn btn-default" onclick="cancelarModalAgregados()" data-dismiss="modal"><i class="fa fa-times"></i> SALIR</button>
            </div>
            <!-- FIN BOTONES  -->
        </div>
    </div>
</div>

<div id="ModalMensajesDelProducto" class="modal fade t-modal-form" role="dialog" style="overflow-y: scroll;">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- TITULO  MODAL  -->
            <div id="tituloModal" class="modal-header btn-success">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title text text-center">MENSAJES AL PRODUCTO
                </h4>
            </div>
            <!-- FIN TITULO  MODAL  -->

            <!-- CONTENIDO  MODAL  -->
            <div class="modal-body">
                <div class="h-panel">
                    <ul id="myTab" class="nav nav-with title font">
                        <li class="active"><a href="#tabDatosUsuarios" data-toggle="tab" id="tab1"
                                class="text color1"></a></li>
                    </ul>
                    <div class="tab-pane fade active in" id="tabDatosUsuarios">
                        <fieldset>
                            <div class="col-md-12">
                                <label for="txtMsgProducto">MENSAJE DEL PRODUCTO</label>
                                <textarea onpaste="return false;" ondrop="return false;" autocomplete="off"
                                    class="form-control" rows="3" id="txtMsgProducto"
                                    placeholder="Mensaje adicional producto"></textarea>
                            </div>
                        </fieldset>
                        <fieldset>
                            <div class="col-md-6">
                                <div class="btn-group" role="group" aria-label="...">
                                    <div class="checkbox checkbox-success checkbox-circle">
                                        <input id="ckVigenciaMsg" type="checkbox">
                                        <label for="ckVigenciaMsg">
                                            ACTIVO/VIGENTE
                                        </label>
                                    </div>
                                </div>
                            </div>

                        </fieldset>
                        <fieldset>
                            <div class="col-md-4">
                                <button onclick="grabarMsgProductos()" type="button" class="btn btn-primary"><i class="fa fa-check"></i> GRABAR MENSAJE</button>
                            </div>
                            <!--<div class="col-md-4">
                                <button type="button" class="btn btn-warning"><i class="fa fa-plus"></i>NUEVO AGREGADO</button>
                            </div>-->
                        </fieldset>
                        <br>
                        <fieldset>
                            <div class="col-md-12">
                                <div class="panel panel-default">
                                    <div class="panel-body table-responsive">
                                        <table id="tableMsgAgregados" class="table table-hover display"
                                            style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>CODIGO</th>
                                                    <th>PRODUCTO</th>
                                                    <th>MENSAJE</th>
                                                    <th>ACTIVO</th>
                                                    <th style="width:10%">ACCION</th>
                                                </tr>
                                            </thead>
                                            <tbody id="detalleMsgDelProducto">
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </fieldset>

                        <!-- FIN CAMPOS DE REGISTRO  -->
                    </div>
                </div>
            </div>
            <!-- CONTENIDO  MODAL  -->

            <!-- BOTONES  -->
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> SALIR</button>
            </div>
            <!-- FIN BOTONES  -->
        </div>
    </div>
</div>


<!-- v1.24.12: menús y sus categorías, rediseñado (mismos id: los usa jsMenu.php) -->
<style>
#ModalListadoMenus .modal-dialog { width: 820px; max-width: calc(100vw - 32px); }
#ModalListadoMenus .modal-content { border: 0; border-radius: 14px; overflow: hidden; box-shadow: 0 24px 60px rgba(17, 24, 39, .28); }
.lm-cab { display: flex; align-items: center; gap: 14px; padding: 16px 20px; border-bottom: 1px solid var(--t-borde, #E5E7EB); background: #fff; }
.lm-cab-icono { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; background: var(--t-primario-suave, #E8F5EC); color: var(--t-primario, #15803D); font-size: 18px; }
.lm-cab h4 { margin: 0; font-size: 18px; font-weight: 800; color: var(--t-texto-fuerte, #111827); }
.lm-cab small { display: block; margin-top: 2px; font-size: 12px; color: var(--t-texto-tenue, #6B7280); }
.lm-cab .close { margin-left: auto; font-size: 26px; }
.lm-cuerpo { padding: 18px 20px; background: var(--t-fondo, #F5F6F8); }
.lm-tarjeta { background: #fff; border: 1px solid var(--t-borde, #E5E7EB); border-radius: 12px; padding: 16px; }
.lm-tarjeta + .lm-tarjeta { margin-top: 14px; }
.lm-titulo { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 12px; }
.lm-titulo span { font-size: 13px; font-weight: 800; text-transform: uppercase; letter-spacing: .4px; color: var(--t-texto-suave, #4B5563); }
.lm-titulo span i { margin-right: 6px; color: var(--t-primario, #15803D); }
.lm-titulo .btn { height: 34px; border-radius: 8px; font-weight: 700; }
.lm-titulo .btn i { margin-right: 5px; }
#ModalListadoMenus .select2-container .select2-selection--single { height: 44px; border-radius: 8px; }
#ModalListadoMenus .select2-container .select2-selection--single .select2-selection__rendered { line-height: 42px; font-size: 15px; font-weight: 600; padding-left: 12px; }
#ModalListadoMenus .select2-container .select2-selection--single .select2-selection__arrow { height: 42px; }
#tableCategoriasMenuModal { margin: 0 !important; border: 0; }
#tableCategoriasMenuModal thead th { background: var(--t-fondo-alt, #F3F4F6); border: 0 !important; border-bottom: 1px solid var(--t-borde, #E5E7EB) !important;
    font-size: 11px; text-transform: uppercase; letter-spacing: .3px; color: var(--t-texto-suave, #4B5563); padding: 9px 12px; }
#tableCategoriasMenuModal tbody td { border: 0 !important; border-bottom: 1px solid var(--t-borde-suave, #EEF0F3) !important; padding: 10px 12px; vertical-align: middle; font-size: 14px; }
#tableCategoriasMenuModal tbody td:nth-child(2) { font-weight: 700; color: var(--t-texto-fuerte, #111827); }
#tableCategoriasMenuModal tbody tr:hover td { background: var(--t-tinte, #F7FBF8); }
#tableCategoriasMenuModal_wrapper .dataTables_empty { padding: 30px 10px; color: var(--t-texto-tenue, #6B7280); }
.lm-btn-productos { display: inline-flex; align-items: center; gap: 6px; height: 34px; padding: 0 14px; border-radius: 8px; border: 1px solid var(--t-primario-borde, #BBE3C9);
    background: var(--t-primario-suave, #E8F5EC); color: var(--t-primario-osc, #116632); font-weight: 700; font-size: 13px; white-space: nowrap; }
.lm-btn-productos:hover { background: var(--t-primario, #15803D); border-color: var(--t-primario, #15803D); color: #fff; }
.lm-ayuda { margin: 10px 0 0; font-size: 12px; color: var(--t-texto-tenue, #6B7280); }
#ModalListadoMenus .modal-footer { padding: 12px 20px; background: #fff; border-top: 1px solid var(--t-borde, #E5E7EB); }
#ModalListadoMenus .modal-footer .btn { min-width: 120px; height: 40px; border-radius: 9px; font-weight: 700; }
</style>
<div id="ModalListadoMenus" class="modal fade t-modal-form" role="dialog" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="lm-cab">
                <div class="lm-cab-icono"><i class="fa fa-book"></i></div>
                <div>
                    <h4>Menús</h4>
                    <small>Elige un menú y arma sus categorías (Entrada, Fondo, Postre…)</small>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="lm-cuerpo">
                <div class="lm-tarjeta">
                    <div class="lm-titulo">
                        <span><i class="fa fa-book"></i>Menú</span>
                        <button type="button" class="btn btn-default btn-sm" onclick="AbrirModalNuevoMenu()"><i class="fa fa-plus"></i> Nuevo menú</button>
                    </div>
                    <select id="cboMenuModal" class="form-control" style="width: 100%;"></select>
                </div>
                <div class="lm-tarjeta">
                    <div class="lm-titulo">
                        <span><i class="fa fa-th-list"></i>Categorías del menú</span>
                        <button type="button" class="btn btn-success btn-sm" onclick="AbrirModalNuevaCategoriasMenu()"><i class="fa fa-plus"></i> Nueva categoría</button>
                    </div>
                    <div class="table-responsive">
                        <table id="tableCategoriasMenuModal" class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th style="display:none">MenuID</th>
                                    <th>Categoría</th>
                                    <th class="text-center">Costo prom.</th>
                                    <th class="text-center">Productos</th>
                                </tr>
                            </thead>
                            <tbody id="bodyCategoriasMenuModal">
                                <!-- Se llena via JS -->
                            </tbody>
                        </table>
                    </div>
                    <p class="lm-ayuda"><i class="fa fa-info-circle"></i> En cada categoría, <b>Productos</b> abre lo que el cliente puede elegir y cuántos.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Salir</button>
            </div>
        </div>
    </div>
</div>

<div id="ModalNuevoMenu" class="modal fade t-modal-form" role="dialog" style="overflow-y: scroll;">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- TITULO MODAL -->
            <div class="modal-header btn-success">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title text-center">Menú</h4>
            </div>
            <!-- CONTENIDO MODAL -->
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-3" style="display:none" data-id-auto="1">
                        <label>ID</label>
                        <input type="text" disabled class="form-control input-sm" id="txtIdMenu" value="">
                    </div>
                    <div class="col-md-12">
                        <label>Nombre del Menú</label>
                        <input type="text" class="form-control input-sm" id="txtNombreMenu" placeholder="Nombre Menu">
                    </div>
                </div>
                <br>
                <br>
                <div class="row" style="display:none;">
                    <div class="col-md-4">
                        <button onclick="GrabarNuevoMenu()" type="button" class="btn btn-primary btn-block">Grabar Datos</button>
                    </div>
                    <div class="col-md-4">
                        <!-- <button onclick="AbrirModalNuevoMenu()" type="button" class="btn btn-default btn-block">Nuevo</button> -->
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-default btn-block" data-dismiss="modal">Salir (Esc)</button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="table-responsive">
                                <table id="tableMenuAbajo" class="table table-bordered table-condensed">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre</th>
                                        </tr>
                                    </thead>
                                    <tbody id="bodyMenuAbajo">
                                        <!-- Se llena via JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button onclick="GrabarNuevoMenu()" type="button" class="btn btn-primary">Grabar Datos</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Salir</button>
            </div>
        </div>
    </div>
</div>

<div id="ModalListadoCategoriasMenu" class="modal fade t-modal-form" role="dialog" style="overflow-y: scroll;">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- TITULO  MODAL  -->
            <div id="tituloModal" class="modal-header btn-success">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title text text-center">LISTADO DE CATEGORIAS
                </h4>
            </div>
            <!-- FIN TITULO  MODAL  -->

            <!-- CONTENIDO  MODAL  -->
            <div class="modal-body">
                <div class="h-panel">
                    <ul id="myTab" class="nav nav-with title font">
                        <li class="active"><a href="#tabDatosUsuarios" data-toggle="tab" id="tab1"
                                class="text color1"></a></li>
                    </ul>
                    <div class="tab-pane fade active in" id="tabDatosUsuarios">
                        <fieldset>
                            <div class="col-md-4">
                                <button onclick="AbrirModalNuevaCategoriasMenu()" type="button"
                                    class="btn btn-primary"><i class="fa fa-plus"></i> NUEVA CATEGORIA</button>
                            </div>
                        </fieldset>
                        <br>
                        <fieldset>
                            <div class="col-md-12">
                                <div class="panel panel-default">
                                    <div class="table-responsive" style="margin-top:10px; padding:5px;">
                                        <table id="tableCategoriasMenu" class="display responsive" style="width: 100%">
                                            <thead>
                                                <tr>
                                                    <th class="text text-right" style='height:10px'>N°</th>
                                                    <th class="text text-center">MENU</th>
                                                    <th class="text text-center">CATEGORIA</th>
                                                    <th class="text text-center">VIGENCIA</th>
                                                    <th class="text text-center"></th>
                                                </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </fieldset>

                        <!-- FIN CAMPOS DE REGISTRO  -->
                    </div>
                </div>
            </div>
            <!-- CONTENIDO  MODAL  -->

            <!-- BOTONES  -->
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> SALIR</button>
            </div>
            <!-- FIN BOTONES  -->
        </div>
    </div>
</div>

<div id="ModalNuevaCategoria" class="modal fade t-modal-form" role="dialog" style="overflow-y: scroll;">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- TITULO MODAL -->
            <div class="modal-header btn-success">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title text-center">Categorias del menú</h4>
            </div>
            <!-- CONTENIDO MODAL -->
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <label>Nombre del Menú</label>
                        <select id="cboMenusModalCategorias" class="form-control input" style="background-color: #FFF4DF; color: var(--t-texto, #3A2A22); width: 100%;"></select>
                    </div>
                </div>
                <br>
                <div class="row">
                    <div class="col-md-3" style="display:none" data-id-auto="1">
                        <label>ID</label>
                        <input type="text" disabled class="form-control input-sm" id="IdCategoria" value="">
                    </div>
                    <div class="col-md-9">
                        <label>Nombre de la Categoria</label>
                        <input type="text" class="form-control input-sm" id="txtNombreCategoria" placeholder="Nombre Categoria">
                    </div>
                    <div class="col-md-3 text-right">
                        <br>
                        <div class="checkbox checkbox-success checkbox-circle" style="margin-top: 5px;">
                            <input id="ckVigenciaCategoria" type="checkbox" checked>
                            <label for="ckVigenciaCategoria"> Vigente </label>
                        </div>
                    </div>
                </div>
                <br>
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="table-responsive">
                                <table id="tableCategoriasAbajo" class="table table-bordered table-condensed">
                                    <thead>
                                        <tr style="background-color: #fff;">
                                            <th>Código</th>
                                            <th>Nombre de la categoría</th>
                                            <th>Vigente</th>
                                        </tr>
                                    </thead>
                                    <tbody id="bodyCategoriasAbajo">
                                        <!-- Se llena via JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button onclick="GrabarCategoriaMenu()" type="button" class="btn btn-primary">Grabar</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Salir</button>
            </div>
        </div>
    </div>
</div>

<!-- v1.24.10: productos de una categoría del menú, rediseñado y centrado (mismos id: los usa jsMenu.php) -->
<style>
#ModalAgregarProductosAcategorias.in { display: flex !important; align-items: center; justify-content: center; }
#ModalAgregarProductosAcategorias .modal-dialog { width: 1100px; max-width: calc(100vw - 32px); margin: 16px auto; }
#ModalAgregarProductosAcategorias .modal-content { border: 0; border-radius: 14px; overflow: hidden; box-shadow: 0 24px 60px rgba(17, 24, 39, .28); }
.pc-cab { display: flex; align-items: center; gap: 14px; padding: 16px 20px; border-bottom: 1px solid var(--t-borde, #E5E7EB); background: #fff; }
.pc-cab-icono { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; background: var(--t-primario-suave, #E8F5EC); color: var(--t-primario, #15803D); font-size: 18px; }
.pc-cab h4 { margin: 0; font-size: 18px; font-weight: 800; color: var(--t-texto-fuerte, #111827); }
.pc-ruta { display: inline-block; margin-top: 3px; padding: 2px 10px; border-radius: 20px; background: var(--t-fondo-alt, #F3F4F6); color: var(--t-texto-suave, #4B5563); font-size: 12px; font-weight: 700; letter-spacing: .3px; }
.pc-cab .close { margin-left: auto; font-size: 26px; }
.pc-cuerpo { padding: 18px 20px; background: var(--t-fondo, #F5F6F8); }
.pc-cols { display: grid; grid-template-columns: 360px minmax(0, 1fr); gap: 16px; }
.pc-tarjeta { background: #fff; border: 1px solid var(--t-borde, #E5E7EB); border-radius: 12px; padding: 16px; }
.pc-tarjeta-titulo { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin: 0 0 12px; font-size: 13px; font-weight: 800; text-transform: uppercase; letter-spacing: .4px; color: var(--t-texto-suave, #4B5563); }
.pc-tarjeta-titulo i { margin-right: 6px; color: var(--t-primario, #15803D); }
.pc-campo { margin-bottom: 12px; }
.pc-campo label { display: flex; align-items: center; gap: 8px; margin-bottom: 5px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .3px; color: var(--t-texto-suave, #4B5563); }
.pc-campo .form-control { height: 38px; border-radius: 8px; }
.pc-par { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.pc-receta { padding: 1px 8px; border-radius: 10px; font-size: 10px; letter-spacing: .3px; background: var(--t-primario-suave, #E8F5EC); color: var(--t-primario-osc, #116632); border: 1px solid var(--t-primario-borde, #BBE3C9); }
.pc-opciones { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 14px; }
.pc-opcion { display: flex; align-items: center; gap: 8px; margin: 0; padding: 9px 10px; border: 1px solid var(--t-borde, #E5E7EB); border-radius: 9px; cursor: pointer; font-weight: 600; color: var(--t-texto, #1F2937); }
.pc-opcion input { margin: 0; accent-color: var(--t-primario, #15803D); }
.pc-opcion:has(input:checked) { border-color: var(--t-primario, #15803D); background: var(--t-primario-suave, #E8F5EC); }
.pc-botones .btn { height: 42px; border-radius: 9px; font-weight: 700; }
.pc-botones .btn i { margin-right: 5px; }
.pc-botones-fila { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 8px; }
.pc-lista { height: 320px; overflow-y: auto; border: 1px solid var(--t-borde-suave, #EEF0F3); border-radius: 10px; }
#tableDetalleCategoria { margin: 0; }
#tableDetalleCategoria thead th { position: sticky; top: 0; z-index: 1; background: var(--t-fondo-alt, #F3F4F6); font-size: 11px; text-transform: uppercase; letter-spacing: .3px; color: var(--t-texto-suave, #4B5563); border-bottom: 1px solid var(--t-borde, #E5E7EB); white-space: nowrap; }
#tableDetalleCategoria td { vertical-align: middle; border-color: var(--t-borde-suave, #EEF0F3); }
#tableDetalleCategoria tr:not(.pc-sin) > td:first-child, #tableDetalleCategoria th:first-child { display: none; }
#tableDetalleCategoria tr.pc-sin, #tableDetalleCategoria tr.pc-sin td { cursor: default; background: #fff !important; }
#tableDetalleCategoria tbody tr { cursor: pointer; }
#tableDetalleCategoria tbody tr:hover td { background: var(--t-tinte, #F7FBF8); }
#tableDetalleCategoria tbody tr.selected td { background: var(--t-primario-suave, #E8F5EC) !important; color: var(--t-primario-osc, #116632); font-weight: 700; }
.pc-vacio { padding: 40px 10px; text-align: center; color: var(--t-texto-tenue, #6B7280); }
.pc-vacio i { display: block; margin-bottom: 8px; font-size: 28px; }
.pc-contador { min-width: 26px; padding: 2px 9px; border-radius: 12px; background: var(--t-primario, #15803D); color: #fff; font-size: 12px; text-align: center; letter-spacing: 0; }
.pc-pie { display: grid; grid-template-columns: minmax(0, 1fr) 220px; gap: 16px; margin-top: 16px; }
.pc-reglas { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 14px; }
.pc-reglas .pc-campo { margin: 0; }
.pc-reglas input[type=number] { width: 90px; height: 38px; border-radius: 8px; text-align: center; font-weight: 700; }
.pc-reglas .pc-opcion { height: 38px; }
.pc-ayuda { flex: 1 1 260px; margin: 0; font-size: 12px; color: var(--t-texto-tenue, #6B7280); }
.pc-promedio { display: flex; flex-direction: column; justify-content: center; text-align: center; }
.pc-promedio small { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .4px; color: var(--t-texto-suave, #4B5563); }
.pc-promedio b { font-size: 28px; color: var(--t-primario-osc, #116632); }
#ModalAgregarProductosAcategorias .modal-footer { padding: 12px 20px; background: #fff; border-top: 1px solid var(--t-borde, #E5E7EB); }
#ModalAgregarProductosAcategorias .modal-footer .btn { min-width: 120px; height: 40px; border-radius: 9px; font-weight: 700; }
@media (max-width: 900px) { .pc-cols, .pc-pie { grid-template-columns: 1fr; } }
</style>
<div id="ModalAgregarProductosAcategorias" class="modal fade" role="dialog" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="pc-cab">
                <div class="pc-cab-icono"><i class="fa fa-cutlery"></i></div>
                <div>
                    <h4>Productos de la categoría</h4>
                    <!-- Se llena vía JS: MENU -> CATEGORIA (y data-id-categoria) -->
                    <span class="pc-ruta" id="labelMenuCategoriaDestacado"></span>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>

            <div class="pc-cuerpo">
                <div class="pc-cols">
                    <!-- Agregar o editar un producto -->
                    <div class="pc-tarjeta">
                        <div class="pc-tarjeta-titulo"><span><i class="fa fa-plus-circle"></i>Agregar producto</span></div>
                        <input type="hidden" id="IdDetalleCategoria">
                        <input type="hidden" id="IdDetalleCategoriaReal" value="">
                        <div class="pc-campo">
                            <label for="cboSelectorProductosFinal">Producto <span class="pc-receta" id="labelRecetaProducto">SIN RECETA</span></label>
                            <select id="cboSelectorProductosFinal" class="form-control input-sm" style="width:100%"></select>
                        </div>
                        <div class="pc-par">
                            <div class="pc-campo">
                                <label for="CostoCategoriaMenu">Costo receta</label>
                                <input type="number" min="0" class="form-control text-right" id="CostoCategoriaMenu" value="0">
                            </div>
                            <div class="pc-campo">
                                <label for="AdicionalCategoriaMenu">$ Adicional venta</label>
                                <input type="number" min="0" class="form-control text-right" id="AdicionalCategoriaMenu" value="0" title="Lo que se suma al precio del menú si el cliente elige este producto">
                            </div>
                        </div>
                        <div class="pc-opciones">
                            <label class="pc-opcion"><input type="checkbox" id="ckPermiteCambio"> Permitir cambio</label>
                            <label class="pc-opcion" title="Sale en la comanda de cocina / barra"><input type="checkbox" id="ckImprimir"> Imprimir</label>
                        </div>
                        <div class="pc-botones">
                            <button onclick="GrabarDetalleCategoriaMenu()" type="button" class="btn btn-success btn-block"><i class="fa fa-plus-circle"></i> Añadir / actualizar</button>
                            <div class="pc-botones-fila">
                                <button onclick="NuevoDetalleProducto()" type="button" class="btn btn-default"><i class="fa fa-eraser"></i> Nuevo</button>
                                <button onclick="QuitarProductoDeCategoria()" type="button" class="btn btn-danger"><i class="fa fa-trash"></i> Quitar</button>
                            </div>
                        </div>
                    </div>

                    <!-- Lo que ya tiene la categoría -->
                    <div class="pc-tarjeta">
                        <div class="pc-tarjeta-titulo"><span><i class="fa fa-list"></i>En esta categoría</span><span class="pc-contador" id="lblCantProductosCategoria">0</span></div>
                        <div class="pc-lista">
                            <table id="tableDetalleCategoria" class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Producto</th>
                                        <th class="text-right">Costo receta</th>
                                        <th class="text-right">Adicional</th>
                                        <th class="text-center">Cambio</th>
                                        <th class="text-center">Imprime</th>
                                    </tr>
                                </thead>
                                <tbody id="DetalleCategoria"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="pc-pie">
                    <div class="pc-tarjeta">
                        <div class="pc-tarjeta-titulo"><span><i class="fa fa-sliders"></i>Reglas de selección</span></div>
                        <div class="pc-reglas">
                            <div class="pc-campo"><label for="CantMinima">Mínima</label><input type="number" min="0" max="50" class="form-control" id="CantMinima" value="0"></div>
                            <div class="pc-campo"><label for="CantMaxima">Máxima</label><input type="number" min="0" max="50" class="form-control" id="CantMaxima" value="5"></div>
                            <label class="pc-opcion"><input type="checkbox" id="ckEstricto"> Estricto (obligatorio)</label>
                            <p class="pc-ayuda">Cuántos productos de esta categoría elige el cliente en la Caja. Máxima 0 = sin límite. Con "Estricto" no se puede agregar el menú sin elegir la mínima.</p>
                        </div>
                    </div>
                    <div class="pc-tarjeta pc-promedio">
                        <small>Costo promedio</small>
                        <b id="lblCostoPromedioCategoria">0</b>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Salir</button>
                <button onclick="GrabarConfiguracionCategoriaMenu()" type="button" class="btn btn-success"><i class="fa fa-check"></i> Grabar</button>
            </div>
        </div>
    </div>
</div>

<div id="modalReceta" class="modal fade t-modal-form" role="dialog" style="overflow-y: scroll;">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <!-- TITULO  MODAL  -->
            <div id="tituloModalReceta" class="modal-header btn-success">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title text text-center">REGISTRO RECETAS
                </h4>
            </div>
            <!-- FIN TITULO  MODAL  -->

            <!-- CONTENIDO  MODAL  -->
            <div class="right">
                <input type="number" class="form-control" style="display: none;" name="cant_lineas" id="cant_lineas"
                    value="0" min="0" oninput="validity.valid||(value='');">
            </div>
            <div class="col-md-12">
                <div class="modal-body">
                    <div class="h-panel">
                        <div class="panel panel-default">
                            <div class="panel-body">
                                <div>
                                    <!-- CAMPOS DE REGISTRO  -->
                                    <fieldset>
                                        <div class="col-md-1" style="display:none" data-id-auto="1">
                                            <input disabled class="form-control input-sm input-sm"
                                                style="display: none;" name="contador" id="contador" requerido="no"
                                                value="0">
                                            <label for="idReceta">ID</label>
                                            <input type="number" disabled class="form-control input-sm input-sm"
                                                name="idReceta" id="idReceta" requerido="no" value="">
                                        </div>
                                        <div class="col-md-7">
                                            <label for="nombreReceta">NOMBRE RECETA</label>
                                            <input type="text" class="form-control input-sm input-sm"
                                                name="nombreReceta" id="nombreReceta" maxlength='50'
                                                onkeypress="return sololetrasyNumeros(event)" onpaste="return false;"
                                                ondrop="return false;" autocomplete="off" requerido="no" value="">
                                        </div>
                                        <div class="col-md-2">
                                            <label for="rendimientoReceta">RENDIMIENTO</label>
                                            <input type="text" class="form-control input-sm input-sm" maxlength='50'
                                                onkeypress="return soloNumeros(event)" onpaste="return false;"
                                                ondrop="return false;" autocomplete="off" name="rendimientoReceta"
                                                id="rendimientoReceta" requerido="si" value="" />
                                        </div>
                                        <div class="col-md-3">
                                            <label for="brutoSugerido">PRECIO BRUTO SUG/VEN</label>
                                            <input type="text" class="form-control input-sm input-sm" maxlength='50'
                                                onkeypress="return soloNumeros(event)" onpaste="return false;"
                                                ondrop="return false;" autocomplete="off" name="brutoSugerido"
                                                id="brutoSugerido" requerido="si" value="" />
                                        </div>
                                    </fieldset>
                                    <br>
                                    <fieldset>
                                        <div class="col-md-3">
                                            <label for="cboUnMedida">UNIDAD MEDIDA</label>
                                            <select style="width:100%;" id="cboUnMedida" nombre="cboUnMedida"
                                                class="form-control input-sm" requerido="si">
                                                <option value="">--Seleccione--</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="cboGrupoReceta">GRUPO/FAMILIA
                                                <sup><a onclick="crearNuevaFamiliaReceta()">Nuevo</a></sup>
                                            </label>
                                            <select style="width:100%;" id="cboGrupoReceta" nombre="cboGrupoReceta"
                                                class="form-control input-sm" requerido="si">
                                                <option value="">--Seleccione--</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="cboSeccionReceta">SECCION
                                                <sup><a onclick="crearNuevaSeccion()">Nuevo</a></sup>
                                            </label>
                                            <select style="width:100%;" id="cboSeccionReceta" nombre="cboSeccionReceta"
                                                class="form-control input-sm" requerido="si">
                                                <option value="">--Seleccione--</option>
                                            </select>
                                        </div>
                                    </fieldset>
                                </div>
                            </div>
                        </div>
                        <div class="panel panel-default">
                            <div class="panel-body">
                                <div class="col-md-12">
                                    <br>
                                    <ul id="myTab" class="nav nav-tabs title font">
                                        <li class="active" id="tabDetallesReceta">
                                            <a href="#tabDetalleInsumosReceta" data-toggle="tab" id="tab1"
                                                class="text color1">DETALLE DE INSUMOS DE LA RECETA</a>
                                        </li>
                                        <li class="" id="tabPreparacionRecetas">
                                            <a href="#tabPreparacionReceta" data-toggle="tab" id="tab2"
                                                class="text color1">PREPARACION DE LA RECETA</a>
                                        </li>
                                    </ul>
                                    <div class="tab-pane fade active in" id="tabDetalleInsumosReceta">
                                        <br>
                                        <!-- CAMPOS DE REGISTRO  -->
                                        <fieldset>
                                            <div class="col-md-3">
                                                <label for="cboCodBarraInsumoReceta">COD BARRA/COD INTERNO</label>
                                                <select style="width:100%;" id="cboCodBarraInsumoReceta"
                                                    nombre="cboCodBarraInsumoReceta" class="form-control input-sm"
                                                    requerido="si">
                                                    <option value="0">--Seleccione--</option>
                                                </select>
                                                <input type="hidden" disabled class="form-control input-sm input-sm"
                                                    name="cBarraI" id="cBarraI" requerido="si" value="">

                                            </div>
                                            <div class="col-md-7">
                                                <label for="nombreInsumoReceta">NOMBRE INSUMO</label>
                                                <input type="text" disabled class="form-control input-sm input-sm"
                                                    name="nombreInsumoReceta" id="nombreInsumoReceta" requerido="si"
                                                    value="">
                                            </div>
                                            <div class="col-md-2">
                                                <label for="esSubreceta">SUBRECETA</label>
                                                <input type="text" disabled class="form-control input-sm input-sm"
                                                    name="esSubreceta" id="esSubreceta" requerido="si" value="">
                                            </div>
                                        </fieldset>
                                        <br>
                                        <fieldset>
                                            <div class="col-md-2">
                                                <label for="idInsumoReceta">ID INSUMO</label>
                                                <input type="number" disabled class="form-control input-sm input-sm"
                                                    name="idInsumoReceta" id="idInsumoReceta" requerido="no" value="">
                                            </div>

                                            <div class="col-md-2">
                                                <label for="cantidadInsumoReceta">CANTIDAD</label>
                                                <input type="number" class="form-control input-sm input-sm"
                                                    onkeypress="return soloNumerosDecimales(event)"
                                                    onpaste="return false;" ondrop="return false;" autocomplete="off"
                                                    name="cantidadInsumoReceta" id="cantidadInsumoReceta" requerido="si"
                                                    value="" />
                                                <div id="equivLineaReceta" class="rec-equiv" hidden></div>
                                            </div>
                                            <div class="col-md-2">
                                                <!-- v1.34.0: se puede escribir en kilos o gramos (se guarda en la unidad de receta del insumo) -->
                                                <label for="unidadLineaReceta">UN MEDIDA</label>
                                                <select id="unidadLineaReceta" class="form-control input-sm" disabled></select>
                                                <input type="hidden" id="unMedidaInsumoReceta" value="" />
                                            </div>
                                            <div class="col-md-2">
                                                <label for="costoInsumoReceta" title="Costo neto de 1 unidad de receta (ej. 1 gramo)">COSTO X UN. RECETA</label>
                                                <input type="number" disabled class="form-control input-sm input-sm"
                                                    onkeypress="return soloNumeros(event)" onpaste="return false;"
                                                    ondrop="return false;" autocomplete="off" name="costoInsumoReceta"
                                                    id="costoInsumoReceta" requerido="si" value="" />
                                            </div>
                                            <div class="col-md-2">
                                                <label for="costoTotalInsumoReceta">COSTO TOTAL</label>
                                                <input type="number" disabled class="form-control input-sm input-sm"
                                                    onkeypress="return soloNumeros(event)" onpaste="return false;"
                                                    ondrop="return false;" autocomplete="off"
                                                    name="costoTotalInsumoReceta" id="costoTotalInsumoReceta"
                                                    requerido="si" value="" />
                                            </div>
                                            <div class="col-md-2">
                                                <br>
                                                <button type="button" class="btn btn-rounded btn-danger"
                                                    id="btnAgregarInsumoTabla"><i class="fa fa-check-circle"></i>
                                                    AGREGAR INSUMO</button>
                                            </div>
                                        </fieldset>
                                        <br>
                                        <fieldset>
                                            <div class="table-responsive" style="margin-top:10px; padding:5px;">
                                                <table id="tableInsumoReceta" class="display responsive"
                                                    style="width: 100%;">
                                                    <thead>
                                                        <tr>
                                                            <th style='display:none;' hidden scope="col">ID DETALLE</th>
                                                            <th style='display:none;' hidden scope="col">ID INSUMO</th>
                                                            <th scope="col">COD/BARRA</th>
                                                            <th scope="col">NOMBRE DEL INSUMO</th>
                                                            <th scope="col">SUBRECETA</th>
                                                            <th scope="col">CANTIDAD</th>
                                                            <th scope="col">UN. MEDIDA</th>
                                                            <th scope="col">COSTO PP</th>
                                                            <th scope="col">COSTO TOTAL</th>
                                                            <th scope="col"></th>
                                                        </tr>
                                                    </thead>
                                                </table>
                                            </div>
                                        </fieldset>
                                        <!-- FIN CAMPOS DE REGISTRO  -->
                                    </div>
                                    <div class="tab-pane fade" id="tabPreparacionReceta">
                                        <br>
                                        <textarea
                                            style="resize:none;-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box; width:100%; height:500px;"
                                            type='text' cols="200" maxlength='10000' name="preparacionReceta"
                                            onkeypress="return sololetrasyNumeros(event)" id="preparacionReceta"
                                            rows="20"></textarea>
                                    </div>
                                </div>
                                <!-- FIN CAMPOS DE REGISTRO  -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- CONTENIDO  MODAL  -->

            <!-- BOTONES  -->
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="btnGrabarInsumo"><i class="fa fa-check"></i> GRABAR</button>
                <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> SALIR</button>
            </div>
            <!-- FIN BOTONES  -->
        </div>
    </div>
</div>
<script>
// Manejar el clic del botón según el contexto
$(document).ready(function() {
    $('#btnGrabarPrecios').off('click').on('click', function() {
        var idProducto = $("#txtID").val();
        
        if (idProducto && idProducto !== "0" && idProducto !== "") {
            // Es edición de producto existente
            grabarSoloPreciosDeProducto();
        } else {
            // Es creación de nuevo producto
            grabarPreciosYProducto();
        }
    });
});
</script>

<!-- Buscar imagen en línea (Openverse: imágenes de uso libre) -->
<div class="modal fade" id="modalBuscarImagen" tabindex="-1" role="dialog" style="z-index: 1060;">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-search"></i> Buscar imagen en línea</h4>
            </div>
            <div class="modal-body">
                <div class="input-group">
                    <input type="text" class="form-control" id="txtBuscarImagen" placeholder="Nombre del producto (ej: pan amasado, croissant, café latte)">
                    <span class="input-group-btn"><button class="btn btn-primary" type="button" id="btnBuscarImagenIr"><i class="fa fa-search"></i> Buscar</button></span>
                </div>
                <p class="prod-busqueda-nota">Imágenes de uso libre (Creative Commons) de Flickr, Wikimedia y otros. Toca una para usarla. Si no aparece nada, prueba con el nombre en inglés.</p>
                <div class="prod-busqueda-grilla" id="gridBuscarImagen"></div>
            </div>
        </div>
    </div>
</div>

