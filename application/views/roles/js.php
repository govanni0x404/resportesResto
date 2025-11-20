<script>
  var base_url = "<?php echo base_url(); ?>";
  tablaRoles = $('#tableRoles').DataTable();

  $(document).ready(function() {
    ListarRoles();
  });

  function ListarRoles() {
    if ($.fn.dataTable.isDataTable('#tableRoles')) {
      tablaRoles = $('#tableRoles').DataTable();
      tablaRoles.destroy();
    }
    tablaRoles = $("#tableRoles").DataTable({
      "ajax": {
        "url": base_url + "index.php/Roles/ListarRoles"
      },
      "bPaginate": true,
      "bProcessing": true,
      "pageLength": 9999999999,
            "select": true,
            "dom": "Bfrtip",
            "ordering": true,
            "filter": true,
            "info": false,
            "footer": false,
      "responsive": {
        details: {
          type: 'column',
          target: -1
        }
      },
      "columns": [{
          mData: "id_rol",
          className: "text-right",
          "visible": false
        },
        {
          mData: "nombre_rol",
          className: "text-left"
        },

      ],
      'language': {
        url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json",
        // traduce la linea de abajo que quedaba en ingles
        select: {
          rows: {
            _: " (Seleccionadas %d filas)",
            0: " (Presione una linea a seleccionar)",
            1: " (1 Fila seleccionada)"
          }
        }
      },
      "buttons": [{

      }]
    });
    tablaRoles.on('select', function(e, dt, type, indexes) {
      var rowData = tablaRoles.rows(indexes).data().toArray();
      var id_rol = rowData[0].id_rol;
      mostrarPrivilegiosRoles(id_rol);
    });
  }

  function mostrarPrivilegiosRoles(id_rol) {
    $('#ckTodos').prop('checked', false);
    $('#id_rol').val(id_rol);

    try {
      var row = tablaRoles.row({
        selected: false
      });
      var data = tablaRoles.row({
        selected: false
      }).data();
      $.post(base_url + "index.php/Roles/ListarPrivilegioDeRoles", {
          id_rol: id_rol
        }, function(datos) {
          var obj = jQuery.parseJSON(datos);

          tbl = "";
          var pago = 0;
          $.each(obj, function(index, v) {
            if (v.autoriza == 't') {
              tbl += "<tr id='" + v.id_pusuario + "' id='p_usuario' style='height: 10px; background-color:#5995f0;'>";
            } else {
              tbl += "<tr id='" + v.id_pusuario + "' id='p_usuario' style='height: 10px; background-color:#eb5757'>";
            }
            tbl += "<td class='text-center' style= 'display:none'>" + v.id_rol + "  <sup style='display: none;' id='id_rol'><button id='origen' target='_blank' class='btn btn-icon btn-default btn-xs'><i class='fa fa-info color4'></i></button></sup></td>"
            tbl += "<td class='text-center' style= 'display:none'>" + v.nombre_rol + "  <sup style='display: none;' id='origen'><button id='origen' target='_blank' class='btn btn-icon btn-default btn-xs'><i class='fa fa-info color4'></i></button></sup></td>"
            tbl += "<td class='text-center' style= 'display:none'>" + v.id_psistema + "  <sup style='display: none;' id='id_psistema'><button id='origen' target='_blank' class='btn btn-icon btn-default btn-xs'><i class='fa fa-info color4'></i></button></sup></td>"
            tbl += "<td class='text-center text-white' style= 'height:45px'>" + v.nombre_psistema + "  <sup style='display: none;' id='origen'><button id='origen' target='_blank' class='btn btn-icon btn-default btn-xs'><i class='fa fa-info color4'></i></button></sup></td>"
            tbl += "<td class='text-center text-white' style= 'height:45px'>" + v.nombre_vb + "  <sup style='display: none;' id='origen'><button id='origen' target='_blank' class='btn btn-icon btn-default btn-xs'><i class='fa fa-info color4'></i></button></sup></td>"
            if (v.autoriza == 't') {
              tbl += "<td <button onclick='quitarAccesoModulo(" + v.id_pusuario + ")' id='BtnBloquarAccesoModulo' type='button' class='btn-block btn-danger text-center'><i class='fa fa-times-circle'></i> BLOQUEAR ACCESO</button></td>"
            } else {
              tbl += "<td <button onclick='HabilitarAccesoModulo(" + v.id_pusuario + ")' type='button' class='btn-block btn-primary text-center' id='btnGuardar'><i class='fa fa-check-circle'></i> HABILITAR ACCESO</button></td>"
            }
          })
          $('#detalleRol').html(tbl);
        }).done(function() {
          $('#modalPrivilegiosRoles').modal({
            backdrop: 'static',
            keyboard: false
          });
        })
        .fail(function() {
          alert("Se produjo un error al realizar el proceso, favor contacte a su administrador")
        })
        .always(function() {})

    } catch (e) {
      console.log(e);
    } finally {}
  }

  function quitarAccesoModulo(id_pusuario) {
    swal({
        title: "Esta seguro/a?",
        text: "De Bloquear El Acceso A Este Modulo?",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#EB360F",
        confirmButtonText: "Aceptar!",
        cancelButtonText: "Cancelar!",
        closeOnConfirm: true,
        closeOnCancel: false
      },
      function(isConfirm) {
        if (isConfirm) {
          id_rol = $('#id_rol').val();
          p_usuario = $('#p_usuario').val();
          $.post(base_url + "index.php/Roles/BloquearAccesoModulos", {
            'id_pusuario': id_pusuario,
          }, function() {}).done(function(datos) {

            let positionError = datos.search("ERROR:");
            if (parseFloat(positionError) > 0) {
              swal("Error", "Se produjo un error, favor reintente \n(" + e + ")\n" + datos.substr(positionError, 150), "error");
            } else {
              swal("Modulo Bloqueado!", "Se Bloqueo El Acceso Al Modulo con éxito!", "success");
              mostrarPrivilegiosRoles(id_rol);
            }
          }).fail(function() {
            swal("Se produjo un error al realizar el bloqueo, favor vuelva a intentarlo");
          });
          swal.close();
        } else {
          swal.close();
        }
      });
  }

  function HabilitarAccesoModulo(id_pusuario) {
    swal({
        title: "Esta seguro/a?",
        text: "De Habilitar El Acceso A Este Modulo?",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#EB360F",
        confirmButtonText: "Aceptar!",
        cancelButtonText: "Cancelar!",
        closeOnConfirm: true,
        closeOnCancel: false
      },
      function(isConfirm) {
        if (isConfirm) {
          id_rol = $('#id_rol').val();
          p_usuario = $('#p_usuario').val();
          $.post(base_url + "index.php/Roles/HabilitarAccesoModulos", {
            'id_pusuario': id_pusuario,
          }, function() {}).done(function(datos) {

            let positionError = datos.search("ERROR:");
            if (parseFloat(positionError) > 0) {
              swal("Error", "Se produjo un error, favor reintente \n(" + e + ")\n" + datos.substr(positionError, 150), "error");
            } else {
              swal("Modulo Habilitado!", "Se Habilito El Acceso Al Modulo con éxito!", "success");
              mostrarPrivilegiosRoles(id_rol);
            }
          }).fail(function() {
            swal("Se produjo un error al realizar La Habilitacion, favor vuelva a intentarlo");
          });
          swal.close();
        } else {
          swal.close();
        }
      });
  }

  function HabilitarAccesoTodosLosModulo(id_rol) {
    swal({
        title: "Esta seguro/a?",
        text: "De Habilitar El Acceso A Todos Los Modulos?",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#EB360F",
        confirmButtonText: "Aceptar!",
        cancelButtonText: "Cancelar!",
        closeOnConfirm: true,
        closeOnCancel: false
      },
      function(isConfirm) {
        if (isConfirm) {
          $.post(base_url + "index.php/Roles/HabilitarAccesoTodosLosModulos", {
            'id_rol': id_rol,
          }, function() {}).done(function(datos) {

            let positionError = datos.search("ERROR:");
            if (parseFloat(positionError) > 0) {
              swal("Error", "Se produjo un error, favor reintente \n(" + e + ")\n" + datos.substr(positionError, 150), "error");
            } else {
              swal("Modulos Habilitados!", "Se Habilito El Acceso A Todos Los Modulos con éxito!", "success");
              mostrarPrivilegiosRoles(id_rol);
            }
          }).fail(function() {
            swal("Se produjo un error al realizar la habiliacion A Todos Los Modulos, favor vuelva a intentarlo");
          });
          swal.close();
        } else {
          swal.close();
        }
      });
  }

  $('#btnHabilitarAccesoAtodosLosModulos').on('click', function() {
    id_rol = $('#id_rol').val();
    HabilitarAccesoTodosLosModulo(id_rol);
  });

  function quitarAccesoTodosLosModulo(id_rol) {
    swal({
        title: "Esta seguro/a?",
        text: "De Bloquear El Acceso A Todos Los Modulos?",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#EB360F",
        confirmButtonText: "Aceptar!",
        cancelButtonText: "Cancelar!",
        closeOnConfirm: true,
        closeOnCancel: false
      },
      function(isConfirm) {
        if (isConfirm) {
          $.post(base_url + "index.php/Roles/BloquearAccesoTodosLosModulos", {
            'id_rol': id_rol,
          }, function() {}).done(function(datos) {

            let positionError = datos.search("ERROR:");
            if (parseFloat(positionError) > 0) {
              swal("Error", "Se produjo un error, favor reintente \n(" + e + ")\n" + datos.substr(positionError, 150), "error");
            } else {
              swal("Modulos Bloqueados!", "Se Bloqueo El Acceso A Todos Los Modulos con éxito!", "success");
              mostrarPrivilegiosRoles(id_rol);
            }
          }).fail(function() {
            swal("Se produjo un error al realizar el bloqueo, favor vuelva a intentarlo");
          });
          swal.close();
        } else {
          swal.close();
        }
      });
  }

  $('#btnBloquearaccesoAtodosLosModulos').on('click', function() {
    id_rol = $('#id_rol').val();
    quitarAccesoTodosLosModulo(id_rol);
  });

  function crearNuevoRol() {
    $('#idRoles').val("");
    $('#nombreRol').val("");
    $('#ModalNuevoRol').modal({
      backdrop: 'static',
      keyboard: false
    });
  }

  function GrabarRol() {
    /*
    swal({
        title: "Esta seguro/a?",
        text: "Registar Nuevo Rol?",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#EB360F",
        confirmButtonText: "Aceptar!",
        cancelButtonText: "Cancelar!",
        closeOnConfirm: true,
        closeOnCancel: false
      },
      function(isConfirm){
        if (isConfirm) {*/
    var msg = "";
    if ($('#nombreRol').val() == 0) {
      msg = "-> Nombre Rol\n";
    }
    if (msg.length) {
      swal("Falta Ingresar Los Siguiente(s) dato(s):\n" + msg);
    } else {
      //grabar datos
      IdRol = $('#idRoles').val();
      NombreRol = $('#nombreRol').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
      var miJson = {
        'id_rol': IdRol,
        'nombre_rol': NombreRol
      };
      $.post(base_url + "index.php/Roles/grabarRoles", {
          'prod': miJson
        }).done(function() {
          $('#ModalNuevoRol').modal('hide');
          tablaRoles.ajax.reload();
          swal("Listo", "Se registro Exitosamente El Nuevo Rol", "success");
        })
        .fail(function() {
          swal("Se produjo un error al realizar el proceso, favor contacte a su administrador");
        })
        .always(function() {});
    }
  }
  /*
        });
    }*/

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
