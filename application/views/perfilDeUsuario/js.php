<script>
  var base_url = "<?php echo base_url(); ?>";


  $(document).ready(function() {
    empresa();
    cargarDatosDelUsuario();
    cargarCorreoDeAcceso();

  });

  // El correo de acceso no se cargaba nunca: el campo quedaba vacío y la validación impedía grabar
  function cargarCorreoDeAcceso() {
    $.post(base_url + "index.php/PerfilDeUsuario/obtenerCorreoAccesoWebIntranet", {}).done(function(datos) {
      try {
        var obj = (typeof datos === 'string') ? $.parseJSON(datos) : datos;
        if (obj && obj.length && obj[0].correo_acceso) {
          $('#CorreoDeAcceso').val(obj[0].correo_acceso);
        }
      } catch (e) {
        console.log(datos);
      }
    });
  }

  function empresa() {
    $.post(base_url + "index.php/Dashboard/empresa", {}, function() {}).done(function(data) {
      try {
        var obj = jQuery.parseJSON(data);
        $('#local').text(obj[0].nombre_fantasia);
        $('#lblNombreCliente').text(obj[0].razon_social);
      } catch (e) {
        console.log(data);
      }
    }).fail(function() {
      swal("Se produjo un error al realizar el proceso, favor vuelva a intentarlo");
    });
  }

  function cargarDatosDelUsuario() {
    $.post(base_url + "index.php/PerfilDeUsuario/obtenerDatosDelUsuario", {}, function(datos) {
        var obj = $.parseJSON(datos);
        $.each(obj, function(index, v) {
          $('#IdDeUsuario').val(v.id_usuario);
          $('#nombreDeUsuario').val(v.nombre_usuario);
          $('#RolUsuario').val(v.nombre_rol);
          if (v.esadmin == 't') {
            $("#ckEsAdmin").prop("checked", true);
          } else {
            $("#ckEsAdmin").prop("checked", false);
          }

          $('#RolUsuario').prop('disabled', true);
          $('#ckEsAdmin').prop('disabled', true);
        });
      }).done(function() {})
      .fail(function() {
        alert("Se produjo un error al realizar la consulta");
      })
      .always(function() {});
  }

</script>