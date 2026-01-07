<!-- MODAL CANTIDAD A PRODUCIR -->
<div id="ModalCantidadProduccion" class="modal fade" role="dialog">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #5cb85c; color: white;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white;">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title text-center">Cargar receta de producción</h4>
            </div>
            
            <div class="modal-body">
                <div class="form-group">
                    <label for="recetaSeleccionada">Receta</label>
                    <select id="recetaSeleccionada" class="form-control input-sm" style="width:100%">
                        <option value="0">-- Seleccione --</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="cantidadAProducir">Cantidad a producir</label>
                    <input type="number" 
                           class="form-control input-sm" 
                           id="cantidadAProducir" 
                           value="1" 
                           min="1"
                           step="0.01"
                           onkeypress="return soloNumerosDecimales(event)" />
                </div>
                
                <div class="form-group">
                    <button type="button" class="btn btn-info btn-sm btn-block" id="btnVerStockDisponible">
                        <i class="fa fa-cubes"></i> Ver Stock Disponible
                    </button>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="btnAceptarCantidad">
                    <i class="fa fa-check"></i> ACEPTAR
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <i class="fa fa-times"></i> Salir
                </button>
            </div>
        </div>
    </div>
</div>