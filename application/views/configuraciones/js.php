<script>
    var base_url = "<?php echo base_url(); ?>";

    function cargarCombosComuna() {
        $.ajax({
            url: base_url + "index.php/Comuna/ListarComunas",
            type: "POST",
            data: { id: "" },
            dataType: "json"
        }).done(function(obj) {
            console.log("Comunas recibidas:", obj);
            var opciones = '<option value="0">Seleccione la comuna</option>';
            if (!obj || obj.length === 0) {
                console.warn("El servidor no devolvió comunas. Verifica que la tabla 'comuna' tenga datos en esta base.");
            }
            $.each(obj, function(index, v) {
                opciones += '<option value="' + v.id_comuna + '">' + v.nombre_comuna + '</option>';
            });

            $('#cboIdComuna, #cboIdComunaSucursal, #cboIdComunaSii').each(function() {
                var $select = $(this);
                var seleccionado = $select.data('selected');
                $select.html(opciones);
                if (seleccionado) {
                    $select.val(String(seleccionado));
                }
                $select.select2();
            });
        }).fail(function(xhr, status, error) {
            console.error("Error al cargar comunas:", status, error, xhr.responseText);
        });
    }

    function toggleMostrarClaveAdmin() {
        var input = document.getElementById('txtclaveAdmin');
        var chk = document.getElementById('chkMostrarClaveAdmin');
        if (!input || !chk) return;
        input.type = chk.checked ? 'text' : 'password';
    }

    function openTab(evt, tabName) {
        var i, tabcontent, tablinks;
        tabcontent = document.getElementsByClassName("tab");
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].className = tabcontent[i].className.replace(" active", "");
        }
        tablinks = document.getElementsByClassName("tablink");
        for (i = 0; i < tablinks.length; i++) {
            tablinks[i].className = tablinks[i].className.replace(" active", "");
        }
        document.getElementById(tabName).className += " active";
        evt.currentTarget.className += " active";
    }

    $(document).ready(function() {
        cargarCombosComuna();

        $('#configForm').submit(function(e) {
            e.preventDefault();
            var $submitBtn = $(this).find('button[type="submit"]');
            $submitBtn.prop('disabled', true).text('Guardando...');
            
            $.ajax({
                type: 'POST',
                url: $(this).attr('action'),
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        alert('Configuración guardada exitosamente');
                        location.reload();
                    } else {
                        alert('Error al guardar la configuración');
                        $submitBtn.prop('disabled', false).text('Grabar');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', xhr.responseText);
                    alert('Error en la solicitud: ' + error);
                    $submitBtn.prop('disabled', false).text('Grabar');
                }
            });
        });
    });
</script>