<script>
	(function () {
		var base = "<?php echo base_url(); ?>index.php/DocumentosTributarios/";
		var esAdmin = <?php echo $esAdmin ? 'true' : 'false' ?>;
		var hayFolioAuto = <?php echo in_array('folio_automatico', $columnas, true) ? 'true' : 'false' ?>;
		var hayFormato = <?php echo in_array('formato_dte', $columnas, true) ? 'true' : 'false' ?>;
		var ELECTRONICOS = [33, 34, 39, 41, 46, 52, 56, 61];
		var guardando = false;

		var siNo = function (b) { return b ? 'SÍ' : 'NO'; };
		var esElectronico = function (cod) { return ELECTRONICOS.indexOf(Number(cod)) >= 0; };

		var tabla = $('#tablaDocumentos').DataTable({
			data: [],
			paging: false,
			info: false,
			order: [[1, 'asc']],
			columns: [
				{ data: 'id_tdocumento', className: 'text-center', width: '60px' },
				{ data: 'nombre_tdocumento' },
				{ data: 'cod_sii', className: 'text-center', width: '90px' },
				{ data: null, className: 'text-center', width: '80px', render: function (r) { return r.electronico ? (hayFormato && r.formato_dte ? escHtml(r.formato_dte) : 'SÍ') : 'NO'; } },
				{ data: null, className: 'text-center', width: '80px', render: function (r) { return siNo(r.para_venta); } },
				{ data: null, className: 'text-center', width: '80px', render: function (r) { return siNo(r.para_compra); } }
			],
			language: { emptyTable: 'No hay documentos', search: 'Buscar:', zeroRecords: 'No se encontraron documentos' }
		});

		function cargar() {
			$.ajax({ url: base + 'listado', type: 'POST', dataType: 'json' }).done(function (r) {
				tabla.clear().rows.add(r.filas || []).draw();
			}).fail(function (x) { error(x, 'No se pudo cargar el listado'); });
		}

		// f = documento a editar; sin f se abre vacío para crear uno nuevo
		function abrir(f) {
			$('#tdTitulo').text(f ? (esAdmin ? 'EDITAR DOCUMENTO TRIBUTARIO' : 'DOCUMENTO TRIBUTARIO') : 'NUEVO DOCUMENTO TRIBUTARIO');
			$('#tdId').val(f ? f.id_tdocumento : '');
			$('#tdNombre').val(f ? f.nombre_tdocumento : '');
			$('#tdCodSii').val(f ? f.cod_sii : '');
			$('#tdElectronico').prop('checked', !!(f && f.electronico));
			$('#tdRequiereCliente').prop('checked', !!(f && f.requiere_cliente));
			$('#tdCatBoleta').prop('checked', !!(f && f.categoria_boleta));
			$('#tdCatFactura').prop('checked', !!(f && f.categoria_factura));
			$('#tdVenta').prop('checked', !!(f && f.para_venta));
			$('#tdCompra').prop('checked', !!(f && f.para_compra));
			if (hayFolioAuto) $('#tdFolioAuto').prop('checked', f ? !!f.folio_automatico : true);
			if (hayFormato) $('#tdFormato').val((f && f.formato_dte) || '80');
			$('#modalDocumento .modal-body').find('input, select, button').not('#tdId, #tdElectronico, #tdAyudaSii').prop('disabled', !esAdmin);
			$('#modalDocumento').modal('show');
		}
		$('#modalDocumento').on('shown.bs.modal', function () { if (esAdmin) $('#tdNombre').focus(); });

		function error(x, defecto) {
			var m = defecto;
			try { m = (x.responseJSON && x.responseJSON.error) || JSON.parse(x.responseText).error || defecto; } catch (e) {}
			swal('Atención', m, 'warning');
		}

		$('#tablaDocumentos tbody').on('click', 'tr', function () {
			var d = tabla.row(this).data();
			if (d) abrir(d);
		});
		$('#tdNuevo').on('click', function () { abrir(null); });
		$('#tdCodSii').on('input', function () { $('#tdElectronico').prop('checked', esElectronico($(this).val())); });
		$('#tdNombre').on('blur', function () { $(this).val($.trim($(this).val()).toUpperCase()); });

		$('#tdAyudaSii').on('click', function () {
			swal({
				title: 'Códigos SII',
				html: true,
				text: '<div style="text-align:left;font-size:14px;line-height:1.8">'
					+ '<b>33</b> Factura electrónica<br><b>34</b> Factura exenta electrónica<br><b>39</b> Boleta electrónica<br>'
					+ '<b>41</b> Boleta exenta electrónica<br><b>46</b> Factura de compra electrónica<br><b>52</b> Guía de despacho electrónica<br>'
					+ '<b>56</b> Nota de débito electrónica<br><b>61</b> Nota de crédito electrónica<br><b>0</b> Documento interno (comprobante, NC interna)</div>'
			});
		});

		$('#tdGrabar').on('click', function () {
			if (!esAdmin || guardando) return;
			var nombre = $.trim($('#tdNombre').val()).toUpperCase(), cod = $.trim($('#tdCodSii').val());
			var faltan = [];
			if (!nombre) faltan.push('Nombre del documento');
			if (cod === '' || !/^\d+$/.test(cod)) faltan.push('Código SII (0 si es interno)');
			if (faltan.length) { swal('Faltan datos', faltan.join('\n'), 'warning'); return; }
			var datos = {
				id: $('#tdId').val() || 0,
				nombre: nombre,
				cod_sii: cod,
				requiere_cliente: $('#tdRequiereCliente').is(':checked') ? 1 : 0,
				categoria_boleta: $('#tdCatBoleta').is(':checked') ? 1 : 0,
				categoria_factura: $('#tdCatFactura').is(':checked') ? 1 : 0,
				para_venta: $('#tdVenta').is(':checked') ? 1 : 0,
				para_compra: $('#tdCompra').is(':checked') ? 1 : 0
			};
			if (hayFolioAuto) datos.folio_automatico = $('#tdFolioAuto').is(':checked') ? 1 : 0;
			if (hayFormato) datos.formato_dte = $('#tdFormato').val();
			guardando = true;
			$('#tdGrabar').prop('disabled', true);
			$.ajax({ url: base + 'grabar', type: 'POST', data: datos, dataType: 'json' }).done(function (r) {
				$('#modalDocumento').modal('hide');
				toastr.success(r.mensaje || 'Documento grabado');
				cargar();
			}).fail(function (x) { error(x, 'No se pudo grabar el documento'); })
				.always(function () { guardando = false; $('#tdGrabar').prop('disabled', false); });
		});

		cargar();
	})();
</script>
