<script>
function AbrirModalPrecioVenta(idProducto) {
    // 1. Si no llega parámetro, usamos el input correcto
    if (!idProducto || idProducto === "0") {
        idProducto = $("#txtID").val();
    }

    // 2. Validación
    if (!idProducto || idProducto === "0" || idProducto === "") {
        console.error("ID vacío → no se puede abrir modal de precios");
        return;
    }

    console.log("Abriendo modal de precios para producto:", idProducto);

    // 3. PRIMERO cargar los precios existentes del producto
    $.post(base_url + "index.php/Productos/obtenerPreciosYComisiones", {
        id_producto: idProducto
    }, function(datos) {
        var obj = $.parseJSON(datos);
        preciosExistentes = obj; // Guardar precios existentes
        console.log("Precios existentes cargados:", preciosExistentes);
        
        // 4. LUEGO cargar la tabla con esos precios
        CargarListasDePreciosVacias();
        
        // 5. Finalmente abrir el modal
        $('#modalPrecioVentaProducto').modal({
            backdrop: 'static',
            keyboard: false
        });
    }).fail(function(xhr, status, error) {
        console.error("Error al cargar precios:", error);
        showAppAlert('error', "No se pudieron cargar los precios del producto", {
            duration: 5000,
            image: base_url+ 'assets/img/errorGif.gif'
        });
    });
}

function cargarPreciosYComisionesParaEdicion(idProducto) {
    if (!idProducto || idProducto === "0") {
        console.error("ID vacío → evitando llamada");
        return;
    }

    $.post(base_url + "index.php/Productos/obtenerPreciosYComisiones", {
        id_producto: idProducto
    }, function(datos) {
        var obj = $.parseJSON(datos);
        preciosExistentes = obj;
        console.log("Precios y comisiones cargados:", preciosExistentes);
    });
}

function cancelarEdicionPrecios() {
    $('#modalPrecioVentaProducto').modal('hide');
}

// NUEVA FUNCIÓN: Grabar solo precios (cuando editas desde el modal)
function grabarSoloPreciosDeProducto() {
    var idProducto = $("#txtID").val();
    
    if (!idProducto || idProducto === "0" || idProducto === "") {
        showAppAlert('error', "No hay producto seleccionado", {
            duration: 5000,
            image: base_url+ 'assets/img/errorGif.gif'
        });
        return;
    }
    
    var precios = [];
    var tienePrecio = false;
    
    $("#detallePrecioVenta tr").each(function() {
        var idLista = $(this).find("td").eq(0).text().trim();
        var precio = parseInt($(this).find(".precio-input").val()) || 0;
        
        // Verificar si existe el input de comisión antes de leerlo
        var $comisionInput = $(this).find(".comision-input");
        var comision = 0;
        
        if ($comisionInput.length > 0) {
            comision = parseFloat($comisionInput.val()) || 0;
        }
        
        // Incluir todos los precios definidos por el usuario, incluso si son 0
        var precioData = {
            id_lprecio: idLista,
            total: precio
        };
        
        // Solo incluir comisión si el input existe
        if ($comisionInput.length > 0) {
            precioData.monto_comision = comision;
        }
        
        precios.push(precioData);
    });
    
    console.log("Precios a grabar:", precios);
    
    // Grabar solo los precios
    $.ajax({
        url: base_url + "index.php/Productos/actualizarPreciosYComisiones",
        type: "POST",
        data: { 
            id_producto: idProducto,
            precios: precios
        },
        dataType: "json",
        success: function(response) {
            console.log("✓ Precios actualizados:", response);
            
            if (response.error) {
                showAppAlert('error', response.error, {
                    duration: 5000,
                    image: base_url+ 'assets/img/errorGif.gif'
                });
                return;
            }
            
            // Cerrar modal
            $('#modalPrecioVentaProducto').modal('hide');
            
            // Actualizar precios existentes
            preciosExistentes = precios;
            
            showAppAlert('success', "Precios actualizados correctamente!", {
                duration: 4000,
                image: base_url + 'assets/img/success.gif'
            });
        },
        error: function(xhr, status, error) {
            console.error("✗ Error:", xhr.responseText);
            showAppAlert('error', "No se pudieron actualizar los precios: " + error, {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
        }
    });
}
</script>