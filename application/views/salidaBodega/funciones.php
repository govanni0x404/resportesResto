<script>
function ListadoInsumos(objSelected) {
    $.post(base_url + "index.php/Insumos/ListarInsumos", {
        id: ""
    }, function(datos) {
        var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
        tbl = '<option selected value="0">Seleccione El Insumo</option>';
        $.each(obj, function(index, v) { // recorro el array
            if (v.nombre_insumo == objSelected) {
                tbl = tbl + '<option selected value="' + v.id_insumo + '">' + v.nombre_insumo +
                    '</option>';
            } else {
                tbl = tbl + '<option value="' + v.id_insumo + '">' + v.nombre_insumo + '</option>';
            }
        }); //fin each
        $("#cboInsumo").html(tbl);
        $("#cboInsumo").select2();
    });
}

function ListadoBodegas(objSelected) {
    $.post(base_url + "index.php/Productos/ListadoDeBodega", {
        id: ""
    }, function(datos) {
        var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
        tbl = '<option selected disabled value="0">Seleccione La Bodega</option>';
        $.each(obj, function(index, v) { // recorro el array
            if (v.nombre_bodega == objSelected) {
                tbl = tbl + '<option selected value="' + v.id_bodega + '">' + v.nombre_bodega +
                    '</option>';
            } else {
                tbl = tbl + '<option value="' + v.id_bodega + '">' + v.nombre_bodega + '</option>';
            }
        }); //fin each
        $("#cboBodega").html(tbl);
        $("#cboBodega").select2();
    });
}

function commaSeparateNumber(val) {
    while (/(\d+)(\d{3})/.test(val.toString())) {
        val = val.toString().replace(/(\d+)(\d{3})/, '$1' + ',' + '$2');
    }

    return val;
}

function sololetrasyNumeros(e, espace) {
    key = e.keyCode || e.which;
    teclado = String.fromCharCode(key).toLowerCase();
    letras = "qwertyuiopasdfghjklñzxcvbnm1234567890 ";
    especiales = "8-37-38-46-164";
    teclado_especial = false;

    for (var i in especiales) {
        if (key == especiales[i]) {
            teclado_especial = true;
            break;
        }
    }

    if (espace == true) {
        if (e.keyCode == 32) {
            return false;
        }
    }

    if (letras.indexOf(teclado) == -1 && !teclado_especial) {
        return false;
    }
}
</script>