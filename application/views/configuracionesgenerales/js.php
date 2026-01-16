<script>
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