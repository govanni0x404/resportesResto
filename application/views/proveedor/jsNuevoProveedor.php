<!--  MODAL NUEVO REGISTRO  -->
<div id="ModalNuevoProveedor" class="modal fade" role="dialog" style="overflow-y: scroll;">
	<div class="modal-dialog">
		<div class="modal-content">
			<!-- TITULO  MODAL  -->
			<div id="tituloModalUsuario" class="modal-header btn-success">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title text text-center">REGISTRO DE PROVEEDORES
				</h4>
			</div>
			<!-- FIN TITULO  MODAL  -->

			<!-- CONTENIDO  MODAL  -->
			<div class="modal-body">
				<div class="h-panel">
					<ul id="myTab" class="nav nav-with title font">
						<li class="active"><a href="#tabBodegas" data-toggle="tab" id="tab1" class="text color1"></a></li>
					</ul>
					<div class="tab-pane fade active in" id="tabBodegas">
						<!-- CAMPOS DE REGISTRO  -->
						<fieldset>
							<div class="col-md-4 ">
								<label for="idProveedor">ID</label>
								<input type="number" disabled class="form-control input-sm input-sm" name="idProveedor" id="idProveedor" requerido="no" value="" />
							</div>
						</fieldset>
						<fieldset>
							<div class="col-md-8 ">
								<label for="RutProveedor">RUT PROVEEDOR *</label>
								<input min="0" type="text" class="form-control input-sm input-sm" maxlength='8' onkeypress="return soloNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="RutProveedor" id="RutProveedor" requerido="si" value="" placeholder="Rut Proveedor" />
							</div>
							<div class="col-md-4 ">
								<label for="DvRutProveedor">DV *</label>
								<input min="0" readonly type="text" class="form-control input-sm input-sm" maxlength='1' onkeypress="return sololetrasyNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="DvRutProveedor" id="DvRutProveedor" requerido="si" value="" placeholder="Dv Proveedor" />
							
							</div>
						</fieldset>
						<fieldset>
							<div class="col-md-12 ">
								<label for="nombreProveedor">NOMBRE PROVEEDOR *</label>
								<input type="text" class="form-control input-sm input-sm" maxlength='50' onkeypress="return sololetrasyNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="nombreProveedor" id="nombreProveedor" requerido="si" value="" placeholder="Nombre Proveedor" />
							</div>
						</fieldset>
						<fieldset>
							<div class="col-md-12 ">
								<label for="DireccionProveedor">DIRECCION *</label>
								<input type="text" class="form-control input-sm input-sm" maxlength='50' onkeypress="return sololetrasyNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="DireccionProveedor" id="DireccionProveedor" requerido="si" value="" placeholder="Direccion Proveedor" />
							</div>
						</fieldset>
						<fieldset>
							<div class="col-md-6 ">
								<label for="TelefonoProveedor">TELEFONO</label>
								<input min="0" type="text" class="form-control input-sm input-sm" maxlength='20' onkeypress="return soloNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="TelefonoProveedor" id="TelefonoProveedor" requerido="si" value="" placeholder="Telefono Proveedor" />
							</div>

							<div class="col-md-6 ">
								<label for="CorreoProveedor">CORREO</label>
								<input type="text" class="form-control input-sm input-sm" maxlength='20' onkeypress="return sololetrasyNumerosCorreo(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="CorreoProveedor" id="CorreoProveedor" requerido="si" value="" placeholder="Correo Proveedor" />
							</div>
						</fieldset>
						<fieldset>
							<div class="col-md-8">
								<label for="GiroProveedor">GIRO *</label>
								<input type="text" class="form-control input-sm input-sm" maxlength='50' onkeypress="return sololetrasyNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="GiroProveedor" id="GiroProveedor" requerido="si" value="" placeholder="Giro Proveedor" />
							</div>
							<div class="col-md-4">
								<label for="CreditoProveedor">CREDITO</label>
								<input min="0" type="text" class="form-control input-sm input-sm" maxlength='8' onkeypress="return soloNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="CreditoProveedor" id="CreditoProveedor" requerido="si" value="" placeholder="Credito Maximo Proveedor" />
							</div>
						</fieldset>
						<!-- FIN CAMPOS DE REGISTRO  -->
					</div>
				</div>
			</div>
			<!-- CONTENIDO  MODAL  -->

			<!-- BOTONES  -->
			<div class="modal-footer">
				<button onclick="grabarNuevoProveedor()" type="button" class="btn btn-primary"><i class=""></i>GRABAR</button>
				<button type="button" class="btn btn-default" data-dismiss="modal"><i class=""></i>SALIR</button>
			</div>
			<!-- FIN BOTONES  -->
		</div>
	</div>
</div>
<!-- FIN  MODAL NUEVO REGISTRO  -->

<style>
    @media (max-width: 991px) {
        #ModalNuevoProveedor fieldset > [class*="col-"] {
            margin-bottom: 12px;
        }
    }
</style>

<script>
// ============================================
// FUNCIONES AUXILIARES (DEBEN ESTAR PRIMERO)
// ============================================

function validarDV(T) {
    var M = 0, S = 1;
    for (; T; T = Math.floor(T / 10))
        S = (S + T % 10 * (9 - M++ % 6)) % 11;
    return S ? S - 1 : 'k';
}

function formatearRUT(rut, dv) {
    if (rut.length == 8) {
        return rut.slice(0, 2) + "." + rut.slice(2, 5) + "." + rut.slice(5, 8) + "-" + dv;
    }
    if (rut.length == 7) {
        return rut.slice(0, 1) + "." + rut.slice(1, 4) + "." + rut.slice(4, 7) + "-" + dv;
    }
    return rut + "-" + dv;
}

function obtenerDatosSII(rut, dv) {
    if ($("#idProveedor").val().length == 0) {
        let rutFormateado = formatearRUT(rut, dv);
        
        console.log("Consultando SII con RUT:", rutFormateado);
        
        $.getJSON('https://siichile.herokuapp.com/consulta', {
            rut: rutFormateado
        }, function(result) {
            if (result['error'] == undefined) {
                var maxLength = 50;
                
                if (result['razon_social'] && result['razon_social'].toUpperCase().replace(/['"$#%&]+/g, '') != '**') {
                    let nombreProveedor = result['razon_social'].slice(0, maxLength);
                    $('#nombreProveedor').val(nombreProveedor.toUpperCase().replace(/['"$#%&]+/g, ''));
                } else {
                    $('#nombreProveedor').val('Sin datos');
                }
                
                if (result['actividades'] && result['actividades'].length > 0) {
                    let primerGiro = result['actividades'][0].giro;
                    let giroProveedor = primerGiro.slice(0, maxLength);
                    $('#GiroProveedor').val(giroProveedor.toUpperCase().replace(/['"$#%&]+/g, ''));
                }
            } else {
                console.log("No se encontraron datos en SII");
                $('#nombreProveedor').val("");
                $('#GiroProveedor').val("");
            }
        }).fail(function() {
            console.log("Error al consultar SII");
        });
    }
}

function soloNumeros(e) {
    let key = e.keyCode || e.which;
    let teclado = String.fromCharCode(key);
    let numeros = "0123456789";
    let especiales = [8, 37, 38, 39, 46];
    
    if (especiales.indexOf(key) !== -1) {
        return true;
    }
    
    return numeros.indexOf(teclado) !== -1;
}

function sololetrasyNumeros(e) {
    let key = e.keyCode || e.which;
    let teclado = String.fromCharCode(key).toLowerCase();
    let letras = "qwertyuiopasdfghjklñzxcvbnm1234567890 ";
    let especiales = [8, 37, 38, 39, 46];
    
    if (especiales.indexOf(key) !== -1) {
        return true;
    }
    
    return letras.indexOf(teclado) !== -1;
}

function sololetrasyNumerosCorreo(e) {
    let key = e.keyCode || e.which;
    let teclado = String.fromCharCode(key).toLowerCase();
    let letras = "qwertyuiopasdfghjklzxcvbnm1234567890-._@";
    let especiales = [8, 37, 38, 39, 46];
    
    if (especiales.indexOf(key) !== -1) {
        return true;
    }
    
    return letras.indexOf(teclado) !== -1;
}

const validateEmail = (email) => {
    return email.match(
        /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
    );
};

// ============================================
// ABRIR MODAL Y CONFIGURAR EVENTOS
// ============================================

function AbrirModalNuevoProveedor() {
    // Limpiar campos
    $('#idProveedor').val("");
    $('#RutProveedor').val("");
    $('#DvRutProveedor').val("");
    $('#nombreProveedor').val("");
    $('#DireccionProveedor').val("");
    $('#TelefonoProveedor').val("");
    $('#CorreoProveedor').val("");
    $('#GiroProveedor').val("");
    $('#CreditoProveedor').val("0");
    
    // Abrir modal
    $('#ModalNuevoProveedor').modal({
        backdrop: 'static',
        keyboard: false
    });
    
    // IMPORTANTE: Configurar eventos DESPUÉS de abrir el modal
    setTimeout(function() {
        configurarEventosProveedor();
    }, 100);
}

// ============================================
// CONFIGURAR EVENTOS DEL FORMULARIO
// ============================================

function configurarEventosProveedor() {
    console.log("✓ Configurando eventos del proveedor");
    
    // Remover eventos anteriores para evitar duplicados
    $('#RutProveedor').off('input keyup blur');
    
    // EVENTO: Calcular DV automáticamente mientras escribe
    $('#RutProveedor').on('input keyup', function(e) {
        let rut = $(this).val().replace(/[^0-9]/g, '');
        
        console.log("RUT:", rut, "Longitud:", rut.length);
        
        if (rut.length >= 7) {
            let dv = validarDV(rut);
            console.log("✓ DV calculado:", dv);
            $('#DvRutProveedor').val(dv.toString().toUpperCase());
        } else {
            $('#DvRutProveedor').val('');
        }
    });
    
    // EVENTO: Validar al salir del campo
    $('#RutProveedor').on('blur', function() {
        let rut = $(this).val().replace(/[^0-9]/g, '');
        
        if (rut.length > 0 && rut.length < 7) {
            showAppAlert('error', "Ingrese un RUT válido (mínimo 7 dígitos)!", {
                duration: 5000,
                image: base_url + 'assets/img/errorGif.gif'
            });
            return;
        }
        
        if (rut.length >= 7 && $('#idProveedor').val().length == 0) {
            let dv = $('#DvRutProveedor').val();
            
            // Verificar si el RUT ya existe
            $.post(base_url + "index.php/Proveedor/busquedaProveedorPorRut", {
                rut: rut
            }, function(datos) {
                var obj = jQuery.parseJSON(datos);
                
                if (obj.length > 0) {
                    showAppAlert('error', "El RUT " + rut + "-" + obj[0].dv + " ya está registrado.", {
                        duration: 5000,
                        image: base_url + 'assets/img/errorGif.gif'
                    });
                    $("#RutProveedor").val("");
                    $("#DvRutProveedor").val("");
                } else {
                    // Consultar datos en SII
                    obtenerDatosSII(rut, dv);
                }
            });
        }
    });
}

// ============================================
// REGISTRAR EVENTOS GLOBALES (DELEGACIÓN)
// ============================================

$(document).ready(function() {
    console.log("✓ Script de proveedor cargado");
    
    // Usar delegación de eventos para modales dinámicos
    $(document).on('input keyup', '#RutProveedor', function() {
        let rut = $(this).val().replace(/[^0-9]/g, '');
        
        if (rut.length >= 7) {
            let dv = validarDV(rut);
            $('#DvRutProveedor').val(dv.toString().toUpperCase());
        } else {
            $('#DvRutProveedor').val('');
        }
    });
    
    $(document).on('blur', '#RutProveedor', function() {
        let rut = $(this).val().replace(/[^0-9]/g, '');
        
        if (rut.length > 0 && rut.length < 7) {
            showAppAlert('error', "Ingrese un RUT válido (mínimo 7 dígitos)!", {
                duration: 5000,
                image: base_url + 'assets/img/errorGif.gif'
            });
            return;
        }
        
        if (rut.length >= 7 && $('#idProveedor').val().length == 0) {
            let dv = $('#DvRutProveedor').val();
            
            $.post(base_url + "index.php/Proveedor/busquedaProveedorPorRut", {
                rut: rut
            }, function(datos) {
                var obj = jQuery.parseJSON(datos);
                
                if (obj.length > 0) {
                    showAppAlert('error', "El RUT " + rut + "-" + obj[0].dv + " ya está registrado.", {
                        duration: 5000,
                        image: base_url + 'assets/img/errorGif.gif'
                    });
                    $("#RutProveedor").val("");
                    $("#DvRutProveedor").val("");
                } else {
                    obtenerDatosSII(rut, dv);
                }
            });
        }
    });
});

// ============================================
// GRABAR PROVEEDOR
// ============================================

function grabarNuevoProveedor() {
    if (!$('#RutProveedor').val() || $('#RutProveedor').val().length < 7) {
        showAppAlert('error', "Ingrese un RUT válido del proveedor!", {
            duration: 5000,
            image: base_url + 'assets/img/errorGif.gif'
        });
        return;
    }
    
    if (!$('#DvRutProveedor').val()) {
        showAppAlert('error', "Falta el dígito verificador del RUT!", {
            duration: 5000,
            image: base_url + 'assets/img/errorGif.gif'
        });
        return;
    }
    
    if ($('#nombreProveedor').val() === '') {
        showAppAlert('error', "Ingrese el nombre del proveedor!", {
            duration: 5000,
            image: base_url + 'assets/img/errorGif.gif'
        });
        return;
    }
    
    if ($('#DireccionProveedor').val() === '') {
        showAppAlert('error', "Ingrese la dirección del proveedor!", {
            duration: 5000,
            image: base_url + 'assets/img/errorGif.gif'
        });
        return;
    }
    
    if ($('#GiroProveedor').val() === '') {
        showAppAlert('error', "Ingrese el giro del proveedor!", {
            duration: 5000,
            image: base_url + 'assets/img/errorGif.gif'
        });
        return;
    }
    
    if ($("#CorreoProveedor").val() != '' && !validateEmail($("#CorreoProveedor").val())) {
        showAppAlert('error', "El correo ingresado es inválido!", {
            duration: 5000,
            image: base_url + 'assets/img/errorGif.gif'
        });
        return;
    }

    var miJson = {
        'id_proveedor': $('#idProveedor').val(),
        'nombre_proveedor': $('#nombreProveedor').val().toUpperCase().replace(/[,.'"$#%&]+/g, ''),
        'rut': $('#RutProveedor').val(),
        'dv': $('#DvRutProveedor').val().toUpperCase(),
        'direccion': $('#DireccionProveedor').val().toUpperCase().replace(/[,.'"$#%&]+/g, ''),
        'telefono': $('#TelefonoProveedor').val(),
        'mail': $('#CorreoProveedor').val().toUpperCase().replace(/[,'"$#%&]+/g, ''),
        'giro': $('#GiroProveedor').val().toUpperCase().replace(/[,.'"$#%&]+/g, ''),
        'creditomaximo': $('#CreditoProveedor').val() === '' ? 0 : parseFloat($('#CreditoProveedor').val())
    };
    
    console.log("Datos a enviar:", miJson);
    
    $.post(base_url + "index.php/Proveedor/grabarNuevoProveedor", {
        'prod': miJson
    }).done(function(response) {
        console.log("✓ Proveedor guardado:", response);
        
        showAppAlert('success', "¡Proveedor registrado exitosamente!", {
            duration: 4000,
            image: base_url + 'assets/img/success.gif'
        });
        
        $('#ModalNuevoProveedor').modal('hide');
        
        if (typeof ListarProveedores === 'function') {
            ListarProveedores(miJson.nombre_proveedor);
        }
        
        if (typeof tablaProveedor !== 'undefined') {
            tablaProveedor.ajax.reload();
        }
    }).fail(function(xhr, status, error) {
        console.error("✗ Error al grabar:", error);
        showAppAlert('error', "Error al registrar el proveedor.", {
            duration: 5000,
            image: base_url + 'assets/img/errorGif.gif'
        });
    });
}
</script>
