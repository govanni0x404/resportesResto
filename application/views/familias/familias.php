<style>
/* Mantenedores: listado + modal de registro (solo estilos) */
.t-mant > .row > [class*="col-"] > .panel-body { padding: 0; }
.t-mant .panel > .t-acciones { padding: 14px 18px 0; margin-bottom: 0; }
.t-mant .panel > .t-tabla { padding: 6px 18px 18px; margin: 0; border: 0; }
.t-mant .panel-heading .panel-title { display: flex; align-items: center; }
@media (max-width: 480px) {
    .t-mant .panel > .t-acciones, .t-mant .panel > .t-tabla { padding-left: 12px; padding-right: 12px; }
    .t-mant .t-acciones .btn { width: 100%; }
}
.t-modal-form .modal-header { border-top: 3px solid var(--t-primario, #B4432E); }
.t-modal-form .modal-title { text-align: left; }
.t-modal-form .h-panel > .nav { display: none; }
.t-modal-form .tab-pane > fieldset { margin: 0 -8px; }
.t-modal-form fieldset::after { content: ""; display: table; clear: both; }
.t-modal-form fieldset fieldset { margin: 0; }
.t-modal-form fieldset > [class*="col-"] { padding: 0 8px; margin-bottom: 14px; }
.t-modal-form label { font-size: 12px; text-transform: uppercase; letter-spacing: .3px; color: var(--t-texto-suave, #7A6A5E); margin-bottom: 5px; }
.t-modal-form .checkbox label { font-size: 13px; letter-spacing: 0; color: var(--t-texto, #3A2A22); }
.t-modal-form .checkbox { margin: 0; }
.t-modal-form .t-card-titulo { margin-top: 2px; }
.t-modal-form .t-opciones { background: var(--t-fondo-alt, #FBF8F3); border: 1px solid var(--t-borde-suave, #F0E8DD); border-radius: var(--t-radio-sm, 6px); padding: 10px 12px; height: 100%; }
.t-modal-form .t-opciones .btn-group { display: block; }
.t-modal-form .modal-footer .btn { min-width: 110px; }
@media (max-width: 480px) {
    .t-modal-form .modal-footer { display: flex; flex-direction: column-reverse; gap: 8px; }
    .t-modal-form .modal-footer .btn { width: 100%; margin: 0 !important; }
}
</style>
<div class="content-header">
  <div class="header-icon">
    <i class="pe-7s-folder"></i>
  </div>
  <div class="header-title">
    <h1>Familias</h1>
    <small id="caja"></small>
    <ol class="breadcrumb">
      <li><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="pe-7s-home"></i>Mantencion</a></li>
      <li class="active">Familias</li>
    </ol>
  </div>
</div>


<div class="container-padding t-mant">
  <input type="number" class="form-control" style="display: none;" name="id_rol" id="id_rol" value="0" min="0" oninput="validity.valid||(value='');">

  <div class="row">
    <div class="col-xs-12 col-sm-12 col-md-12 m-b-20">
      <div class="panel-body">

        <div class="panel panel-success lobidisable">
          <div class="panel-heading">
            <div class="panel-title">
              <i class="pe-7s-menu"></i>
              <h4>Listado De Familias</h4>
            </div>
          </div>
          <div class="t-acciones">
                        <button onclick="AbrirModalNuevaFamilia()" type="button" class="btn btn-primary"><i class="fa fa-plus"></i> Crear Nueva Familia</button>
                    </div>
          <div class="table-responsive t-tabla">
            <table id="tablaFamilias" class="display responsive" style="width: 100%">
              <thead>
                <tr>
                  <th class="text text-center" style='height:10px'>ID</th>
                  <th class="text text-left" style='height:45px'>FAMILIA</th>
                  <th class="text text-left">ALIAS</th>
                  <th class="text text-center">VENTA DIRECTA</th>
                  <th class="text text-center">ORDEN</th>
                  <th class="text text-center">IMPRIMIR DETALLE</th>
                </tr>
              </thead>
            </table>
          </div>
        </div>
      </div>
