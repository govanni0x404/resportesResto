<script>
  var base_url = "<?php echo base_url(); ?>";


  $(document).ready(function() {
    empresa();
    cargarDatosDelUsuario();

  });

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
          $('#IdDeUsuario').hide();
          $('#nombreDeUsuario').val(v.nombre_usuario);
          $('#ClaveDeUsuario').val(v.clave);
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

  function ValidacionOcultarMostarClaveUsuario() {
    if ($('#ckMostrarClaveUsuario').prop('checked') == true) {
      $('#ClaveDeUsuario').prop('type', 'text');
    } else {

      $('#ClaveDeUsuario').prop('type', 'password');
    }
  }
  $('#ckMostrarClaveUsuario').click(function() {
    ValidacionOcultarMostarClaveUsuario();
  })

  function GrabarActualizacionNombreYcontraseña() {
    var msg = "";
    if ($('#nombreDeUsuario').val() == 0) {
      msg = "-> Nombre De Usuario\n";
    }
    if ($('#ClaveDeUsuario').val() == 0) {
      msg = "-> Clave De Acceso\n";
    }
    if ($('#CorreoDeAcceso').val() == 0) {
      msg = "-> Correo De Acceso\n";
    }
    if (msg.length) {
      swal("Falta Ingresar Los Siguiente(s) dato(s):\n" + msg);
    } else {
      //grabar datos
      NombreUsuario = $('#nombreDeUsuario').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
      ClaveUsuario = $('#ClaveDeUsuario').val();
      CorreoAcceso = $('#CorreoDeAcceso').val();
      var miJsonLocal = {
        'nombre_usuario': NombreUsuario,
        'clave': ClaveUsuario
      };

      var miJsonIntranet = {
        'correo_acceso': CorreoAcceso,
        'clave_acceso': ClaveUsuario
      };
      $.post(base_url + "index.php/PerfilDeUsuario/ActualizarNombreYPasswordDeUsuarioIntranet", {
        'prodIntranet': miJsonIntranet
      })
      $.post(base_url + "index.php/PerfilDeUsuario/ActualizarNombreYPasswordDeUsuarioLocal", {
          'prod': miJsonLocal
        }).done(function() {
          swal("Datos De Acceso Actualizados!", "Se Actualizo El Perfil De Acceso!", "success");
        })
        .fail(function() {
          swal("Se produjo un error al realizar el proceso, favor contacte a su administrador");
        })
        .always(function() {});
    }
  }
</script>