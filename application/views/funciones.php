<script>
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

	function rutas(e, espace) {
		key = e.keyCode || e.which;
		teclado = String.fromCharCode(key).toLowerCase();
		letras = "qwertyuiopasdfghjklñzxcvbnm1234567890/\\.";
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

	function soloNumeros(e, espace) {
		key = e.keyCode || e.which;
		teclado = String.fromCharCode(key).toLowerCase();
		letras = "1234567890";
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
	/*
	  function ListadoDestinoDeImpresion(objSelected, nombreObjeto){
	      $.post(base_url+"index.php/Productos/ListadoDestinoDeImpresion",{
	        'id':""
	      },function(){
	      }).done(function(datos) {
	          try {
	            var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
	              tbl = '<option selected disabled value="0">Seleccione El Destino De Impresion</option>';
	            $.each(obj,function(index,v) { // recorro el array
	              if (v.nombre_dimpresion==objSelected) {
	                tbl=tbl+'<option selected value="'+v.id_dimpresion+'">'+v.nombre_dimpresion+'</option>';
	              }else{
	                tbl=tbl+'<option value="'+v.id_dimpresion+'">'+v.nombre_dimpresion+'</option>';
	              }
	            });	//fin each
	            $("#"+nombreObjeto).html(tbl);
	          //
	          } catch (e) {
	            let positionError = datos.search("ERROR:");
	            swal("Error", "Se produjo un error, favor reintente \n("+e+")\n"+datos.substr(positionError,150), "error");
	            return;
	          }
	      }).fail(function() {
	        alert( "Se produjo un error al realizar el proceso, favor contacte a su administrador" );
	      });
	      $("#"+nombreObjeto).select2();
	  }*/
</script>
