<style>
	/* Paleta "Perla y azul" (variables de theme.css): acciones var(--t-primario, #B4432E), bordes #EADFD2, texto #3A2A22 */
	/* Paneles, cabeceras, tablas y modales base vienen de assets/dist/css/theme.css; aquí solo lo propio de la caja */
	.caja .panel { margin-bottom: 20px; overflow: hidden; }
	.caja .panel-heading .panel-title { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
	.caja .panel-heading .badge { background: var(--t-primario, #B4432E); color: #fff; font-size: 12px; }

	/* Pestañas y resumen de la caja en una sola franja */
	.caja-barra { display: flex; align-items: center; flex-wrap: wrap; gap: 8px 20px; background: #fff; border: 1px solid var(--t-borde, #EADFD2); border-radius: 10px;
		padding: 0 16px; margin-bottom: 16px; box-shadow: 0 1px 3px rgba(58, 42, 34,.06); }
	.caja-tabs { border: 0; margin: 0; display: flex; flex-wrap: wrap; }
	.caja-tabs > li { float: none; margin-bottom: 0; }
	.caja-tabs > li > a { color: var(--t-texto-suave, #7A6A5E); padding: 15px 14px 13px; margin: 0; border: 0 !important; border-bottom: 2px solid transparent !important; border-radius: 0; background: none !important; }
	.caja-tabs > li > a:hover { color: #3A2A22; }
	.caja-tabs > li.active > a, .caja-tabs > li.active > a:hover, .caja-tabs > li.active > a:focus { color: var(--t-primario, #B4432E); font-weight: 600; border-bottom-color: var(--t-primario, #B4432E) !important; }
	.caja-estado { display: flex; flex-wrap: wrap; gap: 6px 22px; margin-left: auto; padding: 8px 0; }
	.caja-estado .caja-btn-cierre { align-self: center; border-radius: 8px; font-weight: 600; }
	.caja-estado > div { white-space: nowrap; }
	.caja-estado small { display: block; color: var(--t-texto-tenue, #A89A8C); text-transform: uppercase; font-size: 10px; letter-spacing: .4px; line-height: 1.2; }
	.caja-estado b { font-size: 14px; color: #3A2A22; }
	.caja-estado .ok b { color: var(--t-primario, #B4432E); }
	.caja-contenido { border: 0; }
	/* El tema le pone borde propio a .tab-content .panel-body: dentro de un panel queda doble */
	.caja .tab-content .panel-body { border: 0; border-radius: 0; }

	/* Venta: la venta en tabla al centro y el panel de cliente / cobro a la derecha. En escritorio ocupa el alto
	   visible (ajustarAltoVenta en js.php) y solo las líneas hacen scroll. */
	.caja-venta { display: flex; gap: 16px; }
	.caja-venta-principal { flex: 1 1 auto; min-width: 0; display: flex; flex-direction: column; gap: 12px; }
	/* Panel lateral proporcional a la pantalla: ~30% del ancho, entre 360 y 560 px */
	.caja-venta-lateral { flex: 0 0 30%; min-width: 360px; max-width: 560px; display: flex; flex-direction: column; gap: 12px; min-height: 0; overflow-y: auto; }
	.caja-venta .panel { margin: 0; }
	.caja-tarjeta { background: #fff; border: 1px solid var(--t-borde, #EADFD2); border-radius: 10px; box-shadow: 0 1px 3px rgba(58, 42, 34,.06); }
	.caja-tarjeta-titulo { font-size: 12px; color: var(--t-texto-suave, #7A6A5E); text-transform: uppercase; letter-spacing: .4px; font-weight: 700; margin-bottom: 8px; }

	/* Buscador con resultados desplegables */
	.caja-busqueda { display: flex; gap: 10px; align-items: center; padding: 12px; }
	.caja-buscar { position: relative; flex: 1; min-width: 0; }
	.caja-buscar > i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--t-texto-tenue, #A89A8C); font-size: 15px; z-index: 3; }
	.caja-buscar input { font-size: 15px; height: 44px; padding-left: 40px; border-radius: 8px; border-color: var(--t-borde, #EADFD2); box-shadow: none; }
	.caja-buscar input:focus { border-color: var(--t-primario, #B4432E); box-shadow: 0 0 0 3px rgba(180, 67, 46,.12); }
	.caja-resultados { display: none; position: absolute; left: 0; right: 0; top: 48px; z-index: 1000; background: #fff; border: 1px solid var(--t-borde, #EADFD2); border-radius: 8px;
		box-shadow: 0 8px 24px rgba(58, 42, 34,.15); max-height: 360px; overflow-y: auto; }
	.caja-resultados.visible { display: block; }
	.caja-resultado { display: flex; align-items: center; gap: 10px; padding: 9px 14px; cursor: pointer; border-bottom: 1px solid var(--t-borde-suave, #F0E8DD); }
	.caja-resultado:last-child { border-bottom: 0; }
	.caja-resultado.activo, .caja-resultado:hover { background: var(--t-primario-suave, #FBEEE8); }
	.caja-resultado .nombre { flex: 1; min-width: 0; font-weight: 600; color: #3A2A22; font-size: 13px; }
	.caja-resultado .familia { font-size: 11px; color: var(--t-texto-tenue, #A89A8C); font-weight: 400; display: block; }
	/* Código de barras del producto: en el buscador (junto a la familia) y sobre el nombre en el carro */
	.caja-resultado .familia .cbarra { margin-right: 8px; padding: 1px 7px; border-radius: 6px; background: var(--t-fondo-alt, #FBF8F3); border: 1px solid var(--t-borde, #EADFD2); color: var(--t-texto-suave, #7A6A5E); font-weight: 600; letter-spacing: .3px; }
	/* Inventario de insumos al cierre */
	#modalInventarioCierre .inv-cierre-nota { margin: 0 0 12px; color: var(--t-texto-suave, #7A6A5E); font-size: 13px; }
	.inv-cierre-tabla { margin: 0; }
	.inv-cierre-tabla > thead > tr > th { font-size: 11px; text-transform: uppercase; letter-spacing: .4px; }
	.inv-cierre-tabla > tbody > tr > td { vertical-align: middle; padding: 6px 8px; }
	.inv-cierre-tabla tr.inv-bodega td { background: var(--t-fondo-alt, #FBF8F3); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; color: var(--t-texto-suave, #7A6A5E); }
	.inv-cierre-tabla .inv-nombre { font-weight: 600; color: var(--t-texto-fuerte, #2A1D17); }
	.inv-cierre-tabla .inv-unidad { margin-left: 4px; font-size: 11px; font-weight: 600; color: var(--t-texto-tenue, #A89A8C); }
	.inv-cierre-tabla .inv-stock { text-align: right; white-space: nowrap; color: var(--t-texto-suave, #7A6A5E); }
	.inv-cierre-tabla .inv-contado { width: 130px; margin-left: auto; text-align: right; font-weight: 700; height: 36px; }
	.inv-cierre-tabla .inv-contado.falta { border-color: #C9372C; box-shadow: 0 0 0 3px rgba(201, 55, 44, .15); }
	.inv-cierre-tabla .inv-dif { text-align: right; white-space: nowrap; font-weight: 700; color: var(--t-texto-tenue, #A89A8C); }
	.inv-cierre-tabla .inv-dif.cero { color: #5E7F3A; }
	.inv-cierre-tabla .inv-dif.menos { color: #C9372C; }
	.inv-cierre-tabla .inv-dif.mas { color: #A66C25; }
	#modalInventarioCierre .modal-body { max-height: 60vh; overflow-y: auto; }
	.inv-cierre-resumen { float: left; margin-top: 8px; font-size: 13px; font-weight: 600; color: var(--t-texto-suave, #7A6A5E); }
	.inv-cierre-resumen.listo { color: #5E7F3A; }
	/* Pesables: unidad (kg) junto a la cantidad y campo algo más ancho para los decimales */
	.caja-linea .cantidad .caja-umedida { padding: 0 6px; font-size: 11px; font-weight: 700; background: var(--t-fondo-alt, #FBF8F3); color: var(--t-texto-suave, #7A6A5E); }
	.caja-linea .cantidad .cant.fraccion { min-width: 64px; }
	/* Resumen: total después del descuento, antes del desglose neto / IVA */
	#filaConDescuento { font-weight: 700; color: var(--t-texto-fuerte, #2A1D17); border-bottom: 1px dashed var(--t-borde, #EADFD2); padding-bottom: 6px; margin-bottom: 6px; }
	.caja-linea .cbarra { font-size: 11px; font-weight: 600; letter-spacing: .3px; color: var(--t-texto-tenue, #A89A8C); line-height: 1.2; margin-bottom: 2px; }
	.caja-resultado .cbarra i, .caja-linea .cbarra i { margin-right: 3px; }
	.caja-resultado .precio { font-weight: 700; color: var(--t-primario, #B4432E); white-space: nowrap; }
	.caja-resultados .sin-resultados { padding: 14px; color: var(--t-texto-tenue, #A89A8C); text-align: center; }
	.caja-busqueda .btn-catalogo { height: 44px; border-radius: 8px; font-weight: 600; padding: 0 16px; white-space: nowrap; }
	.caja-busqueda select { height: 44px; border-radius: 8px; width: 190px; flex: 0 0 190px; }

	/* Líneas de la venta */
	.caja-carro { flex: 1; min-height: 0; display: flex; flex-direction: column; overflow: hidden; }
	/* Mesas */
	.caja-mesas { padding: 16px 18px; }
	.mesas-leyenda { display: flex; flex-wrap: wrap; gap: 14px; align-items: center; margin-bottom: 14px; font-size: 13px; }
	.mesas-leyenda i { display: inline-block; width: 12px; height: 12px; border-radius: 3px; margin-right: 4px; vertical-align: -1px; }
	.mesas-leyenda i.libre { background: #E4EFE3; border: 1px solid #5E8C5A; }
	.mesas-leyenda i.ocupada { background: var(--t-primario, #B4432E); }
	.mesas-leyenda i.bloqueada { background: #D9D2C9; }
	.mesas-barra { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 12px; }
	.mesas-sectores { display: flex; flex-wrap: wrap; gap: 6px; }
	.mesas-sectores button { padding: 8px 16px; border: 1px solid var(--t-borde, #EADFD2); border-radius: 20px; background: #fff; color: var(--t-texto, #3A2A22); font-weight: 600; font-size: 14px; }
	.mesas-sectores button.activo { background: var(--t-primario, #B4432E); border-color: var(--t-primario, #B4432E); color: #fff; }
	.mesas-sectores button:disabled { opacity: .55; }
	.mesas-sectores .cuenta { margin-left: 6px; padding: 0 7px; border-radius: 10px; background: rgba(58, 42, 34, .12); font-size: 12px; }
	.mesas-sectores button.activo .cuenta { background: rgba(255, 255, 255, .25); }
	.mesas-herramientas { display: flex; gap: 6px; }
	/* Fondo: hexágonos tenues en verde, distinto de los puntos del modo Organizar */
	.mesas-plano { position: relative; min-height: 340px; overflow: auto; border: 1px solid var(--t-borde, #EADFD2); border-radius: 12px;
		background-color: #F4F6EF;
		background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='56' height='98' viewBox='0 0 56 98' fill='none' stroke='%232E5A3C' stroke-opacity='.10'><path d='M28 0L56 16V49L28 65L0 49V16ZM28 65V98M0 16V0M56 16V0'/></svg>"); }
	.mesas-plano.editando { border: 2px dashed var(--t-primario, #B4432E); background: var(--t-fondo-alt, #FBF8F3) radial-gradient(rgba(58, 42, 34, .2) 1px, transparent 1px) 0 0 / 20px 20px; }
	.mesas-plano.editando .mesa-card { cursor: move; touch-action: none; user-select: none; }
	.mesas-plano .mesa-card.arrastrando { z-index: 5; opacity: .9; box-shadow: 0 10px 22px rgba(58, 42, 34, .3); }
	.mesa-card { position: absolute; width: 130px; height: 112px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 1px; padding: 8px; border-radius: 16px; border: 1px solid #5E8C5A; background: #E4EFE3; color: #2F4F2C; cursor: pointer; box-shadow: 0 2px 6px rgba(58, 42, 34, .12); }
	.mesa-card:hover, .mesa-card:focus { box-shadow: 0 6px 14px rgba(58, 42, 34, .2); outline: none; }
	.mesa-card .num { font-size: 20px; font-weight: 800; line-height: 1.1; }
	.mesa-card .estado, .mesa-card .atiende { font-size: 12px; }
	.mesa-card .atiende { opacity: .85; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
	.mesa-card .total { font-size: 15px; font-weight: 700; }
	.mesa-card .tiempo { font-size: 12px; font-variant-numeric: tabular-nums; }
	.mesa-card.ocupada { background: var(--t-primario, #B4432E); border-color: var(--t-primario, #B4432E); color: #fff; }
	/* Ocupadas según el tiempo: ámbar desde 1 hora, rojo desde 2 */
	.mesa-card.ocupada.demora { background: #C98A12; border-color: #A87210; }
	.mesa-card.ocupada.critica { background: #B83A2A; border-color: #96291C; }
	.mesas-leyenda i.demora { background: #C98A12; }
	.mesas-leyenda i.critica { background: #B83A2A; }
	.caja-banner-mesa .btn-group .dropdown-menu > li > a { padding: 8px 16px; }
	.caja-banner-mesa .dropdown-menu .text-danger { color: #C9372C !important; }
	.caja-cobro-parcial { padding: 4px 10px; border-radius: 14px; background: rgba(255, 255, 255, .2); font-size: 13px; font-weight: 600; }
	.caja-cobro-parcial a { color: #fff; text-decoration: underline; margin-left: 6px; }
	.caja-linea.enviada.fuera-cobro { opacity: .4; }
	#modalMesaProductos .mp-linea { display: flex; align-items: center; gap: 12px; padding: 10px 4px; border-bottom: 1px solid var(--t-borde-suave, #F0E8DD); }
	#modalMesaProductos .mp-linea .mp-nombre { flex: 1; min-width: 0; font-weight: 600; }
	#modalMesaProductos .mp-linea .mp-nombre small { display: block; font-weight: 400; color: var(--t-texto-tenue, #A89A8C); }
	#modalMesaProductos .mp-linea .mp-de { width: 54px; font-size: 13px; color: var(--t-texto-suave, #7A6A5E); }
	#modalMesaProductos .mp-linea .input-group { width: 130px; flex: 0 0 130px; }
	#modalMesaProductos .mp-linea input { text-align: center; font-weight: 700; }
	#modalMesaProductos .mp-linea .mp-monto { width: 90px; text-align: right; font-weight: 700; font-variant-numeric: tabular-nums; }
	#modalMesaProductos .mp-resumen { display: flex; justify-content: space-between; padding: 12px 4px 0; font-size: 16px; font-weight: 700; }
	#modalMesaProductos .mp-campo { margin-top: 14px; }
	/* Pago: propina legible y pie ordenado */
	#modalPago .modal-title small { margin-left: 6px; font-size: 13px; font-weight: 400; color: var(--t-texto-suave, #7A6A5E); }
	.propina-pregunta { padding: 24px 22px 20px; text-align: center; }
	.propina-pregunta h4 { margin: 0 0 6px; font-size: 21px; font-weight: 700; }
	.propina-pregunta label { display: block; margin: 16px 0 6px; font-size: 13px; color: var(--t-texto-suave, #7A6A5E); }
	.propina-pregunta input { text-align: right; font-size: 22px; font-weight: 700; }
	.propina-pregunta .btn-block { margin-top: 12px; height: 48px; font-weight: 600; }
	.propina-pregunta #btnPropinaSi { margin-top: 18px; }
	#modalPago .pago-propina { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
	#modalPago .pago-propina .checkbox { margin: 0 !important; }
	html body #modalPago .pago-propina input[type=number] { width: 170px; height: 42px; font-size: 20px; font-weight: 700; text-align: right; padding: 4px 12px; }
	#modalPago .modal-footer { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
	#modalPago .modal-footer::before, #modalPago .modal-footer::after { display: none; }
	#modalPago .modal-footer .caja-chk-imprimir { float: none; display: flex; align-items: center; margin: 0 !important; min-height: 32px; }
	#modalPago .modal-footer .caja-chk-imprimir label { margin: 0; }
	#modalPago .modal-footer .caja-chk-comanda { margin-left: 8px !important; }
	#modalPago .modal-footer .btn-default { margin-left: auto; }
	.mesa-card.bloqueada { background: #EDE8E1; border-color: #D9D2C9; color: #7A6A5E; }
	.mesas-vacio { padding: 70px 20px; text-align: center; color: var(--t-texto-suave, #7A6A5E); }
	html body #mesasOcupadas { background: var(--t-primario, #B4432E) !important; border-color: var(--t-primario, #B4432E) !important; color: #fff !important; }
	.mesa-card.actual { box-shadow: 0 0 0 3px var(--t-acento, #D9A441); }
	.caja-banner-mesa { display: flex; align-items: center; gap: 14px; padding: 10px 18px; background: var(--t-primario, #B4432E); color: #fff; }
	.caja-banner-mesa .nombre { font-size: 17px; font-weight: 700; }
	.caja-banner-mesa .tiempo { font-variant-numeric: tabular-nums; margin-right: auto; }
	.caja-linea.enviada { background: var(--t-fondo-alt, #FBF8F3); }
	.caja-linea.enviada .nombre .fa { font-size: 12px; margin-right: 4px; color: var(--t-texto-tenue, #A89A8C); }
	.caja-linea.enviada .cantidad { font-size: 17px; font-weight: 700; color: var(--t-texto-fuerte, #2A1D17); }
	.caja-linea.enviada .hora { font-size: 12px; color: var(--t-texto-tenue, #A89A8C); }
	.caja-linea.enviada .reenviar-linea { margin-left: 8px; font-size: 12px; color: var(--t-texto-suave, #7A6A5E); text-decoration: underline; }
	.mesa-card .num { max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
	.mesa-card .num.largo { font-size: 16px; }
	.caja-linea-cab { display: flex; align-items: center; gap: 10px; padding: 10px 18px; background: var(--t-fondo-alt, #FBF8F3); border-bottom: 1px solid var(--t-borde-suave, #F0E8DD);
		font-size: 13px; font-weight: 700; color: var(--t-texto-suave, #7A6A5E); text-transform: uppercase; letter-spacing: .3px; }
	.caja-lineas { flex: 1; min-height: 160px; max-height: 45vh; overflow-y: auto; }
	.caja-linea { display: flex; align-items: center; gap: 10px; padding: 12px 18px; border-bottom: 1px solid var(--t-borde-suave, #F0E8DD); }
	.caja-linea .info, .caja-linea-cab .info { flex: 1; min-width: 0; }
	.caja-linea-cab .info { white-space: nowrap; }
	.caja-linea-cab .badge { background-color: var(--t-primario, #B4432E) !important; color: #fff !important; font-size: 12px; margin-left: 4px; vertical-align: 1px; }
	.caja-linea .nombre { font-weight: 700; color: var(--t-texto-fuerte, #2A1D17); font-size: 16px; line-height: 1.3; }
	.caja-linea a.editar { color: #3A2A22; border-bottom: 1px dashed var(--t-texto-tenue, #A89A8C); text-decoration: none; }
	.caja-linea .detalle-linea { font-size: 13px; color: var(--t-texto-suave, #7A6A5E); }
	.caja-linea .unitario { font-size: 12px; color: var(--t-texto-tenue, #A89A8C); display: none; }
	.caja-linea .precio, .caja-linea-cab .precio { width: 110px; text-align: right; color: var(--t-texto-suave, #7A6A5E); white-space: nowrap; }
	.caja-linea .precio { font-size: 16px; font-weight: 600; }
	.caja-linea .subtotal, .caja-linea-cab .subtotal { width: 110px; text-align: right; white-space: nowrap; }
	.caja-linea .subtotal { font-weight: 700; color: var(--t-texto-fuerte, #2A1D17); font-size: 17px; }
	.caja-linea .cantidad, .caja-linea-cab .cantidad { width: 132px; flex: 0 0 132px; text-align: center; }
	.caja-linea .cantidad .form-control, .caja-linea .cantidad .btn { height: 36px; }
	.caja-linea .cantidad .btn { font-size: 13px; }
	.caja-linea .cantidad .btn { border-color: var(--t-borde, #EADFD2); color: var(--t-texto-suave, #7A6A5E); }
	.caja-linea .cantidad input { text-align: center; padding: 4px; font-size: 17px; font-weight: 700; color: var(--t-texto-fuerte, #2A1D17); border-color: var(--t-borde, #EADFD2); -moz-appearance: textfield; }
	.caja-linea .cantidad input::-webkit-inner-spin-button, .caja-linea .cantidad input::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
	.caja-linea .quitar, .caja-linea-cab .quitar { width: 28px; flex: 0 0 28px; text-align: center; }
	.caja-linea .quitar { color: #B4C0CF; background: none; border: 0; padding: 4px; font-size: 16px; }
	.caja-linea .quitar:hover { color: var(--t-peligro, #C9372C); }
	.caja-carro-vacio { text-align: center; color: var(--t-texto-tenue, #A89A8C); padding: 60px 10px; }
	.caja-carro-vacio i { display: block; font-size: 44px; margin-bottom: 8px; color: #C9D3E0; }
	.caja-carro-pie { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 18px; border-top: 1px solid var(--t-borde-suave, #F0E8DD); background: var(--t-fondo-alt, #FBF8F3); }
	.caja-carro-pie select { width: 260px; max-width: 60%; border-radius: 6px; }
	.caja-carro-pie .total { font-size: 17px; font-weight: 600; color: var(--t-texto-suave, #7A6A5E); white-space: nowrap; }
	.caja-carro-pie .total b { color: var(--t-primario, #B4432E); font-size: 32px; font-weight: 800; margin-left: 6px; vertical-align: middle; }
	.caja-carro-pie select { height: 42px; font-size: 15px; font-weight: 600; }

	/* Panel lateral */
	.caja-venta-lateral .caja-tarjeta { padding: 16px 18px; }
	.caja-venta-lateral .form-control, .caja-venta-lateral .input-group-btn .btn { height: 44px; font-size: 16px; font-weight: 600; color: var(--t-texto-fuerte, #2A1D17); }
	.caja-venta-lateral .form-control::placeholder { font-weight: 400; color: var(--t-texto-tenue, #A89A8C); }
	.caja-venta-lateral .caja-cliente .btn { height: 44px; flex-basis: 44px; font-size: 15px; }
	/* El resumen ocupa el resto del alto y deja Cobrar al fondo, alineado con el total de la venta */
	.caja-venta-lateral .caja-resumen { flex: 1; display: flex; flex-direction: column; }
	.caja-resumen .caja-acciones { margin-top: auto; padding-top: 14px; }
	.caja-venta-lateral .form-group { margin-bottom: 10px; }
	.caja-venta-lateral .form-group:last-child { margin-bottom: 0; }
	.caja-venta-lateral label { font-size: 12px; color: var(--t-texto-suave, #7A6A5E); text-transform: uppercase; letter-spacing: .3px; margin-bottom: 5px; font-weight: 700; }
	.caja-venta-lateral .form-control { border-radius: 6px; }
	.caja-cliente { display: flex; gap: 6px; }
	.caja-cliente input { flex: 1; min-width: 0; background: #fff !important; cursor: pointer; }
	.caja-cliente .btn { flex: 0 0 38px; border-radius: 6px; }
	.caja-resumen .fila { display: flex; justify-content: space-between; color: var(--t-texto, #3A2A22); font-size: 17px; line-height: 2; }
	.caja-resumen .fila span:last-child { font-weight: 700; color: var(--t-texto-fuerte, #2A1D17); }
	.caja-resumen .fila.descuento, .caja-resumen .fila.descuento span:last-child { color: var(--t-peligro, #C9372C); }
	.caja-resumen .fila.total { border-top: 1px solid var(--t-borde-suave, #F0E8DD); margin-top: 6px; padding-top: 6px; color: #3A2A22; font-size: 16px; font-weight: 700; }
	.caja-acciones { display: flex; gap: 10px; }
	.caja-acciones #btnLimpiar { flex: 0 0 54px; border-radius: 8px; color: var(--t-texto-tenue, #A89A8C); }
	.caja-acciones #btnLimpiar { height: 56px; }
	.caja-acciones #btnEnviarMesa { flex: 1; font-size: 18px; font-weight: 600; border-radius: 8px; height: 56px; }
	.caja-acciones #btnCobrar { flex: 1; font-size: 20px; font-weight: 600; padding: 13px; border-radius: 8px; height: 56px; }
	.caja-acciones #btnPrecuenta { flex: 0 0 54px; height: 56px; border-radius: 8px; font-size: 18px; }
	/* Mesa sin pedido: solo se arma el pedido. Sin panel lateral (cobro, opciones) ni documento; "Enviar a mesa" va junto al total */
	#btnEnviarMesaBarra { display: none; align-items: center; gap: 8px; height: 50px; margin-left: auto; padding: 0 26px; border-radius: 10px; font-size: 17px; font-weight: 600; }
	.caja-venta.mesa-sin-pedido .caja-venta-lateral, .caja-venta.mesa-sin-pedido #cboDocumento { display: none !important; }
	.caja-venta.mesa-sin-pedido #btnEnviarMesaBarra { display: inline-flex; }
	/* Mesa sin pedido: la ★ va en la barra de búsqueda y abre (o cierra) el panel lateral solo con Favoritos; Pagar y Opciones no aplican */
	/* .btn.btn-fav-mesa: le gana al color del tema para .btn-default (la ★ va amarilla, igual que en el selector) */
	.caja-busqueda .btn.btn-fav-mesa { display: none; flex: 0 0 44px; width: 44px; height: 44px; padding: 0; align-items: center; justify-content: center;
		border: 1px solid var(--t-borde, #EADFD2); border-radius: 8px; background: #fff; color: #E3A008; font-size: 18px; box-shadow: none; }
	.caja-busqueda .btn.btn-fav-mesa:hover, .caja-busqueda .btn.btn-fav-mesa:focus { background: #FFF6E3; color: #C98A00; }
	.caja-busqueda .btn.btn-fav-mesa.is-activo, .caja-busqueda .btn.btn-fav-mesa.is-activo:hover, .caja-busqueda .btn.btn-fav-mesa.is-activo:focus {
		background: var(--t-primario, #B4432E); border-color: var(--t-primario, #B4432E); color: #fff; box-shadow: 0 4px 12px rgba(58, 42, 34, .16); }
	.caja-venta.mesa-sin-pedido .caja-busqueda .btn.btn-fav-mesa { display: inline-flex; }
	/* El tema pinta gris los íconos de .btn-default con !important (html body .btn-default i): la ★ sigue el color del botón */
	html body .caja-busqueda .btn.btn-fav-mesa i { color: inherit !important; }
	.caja-venta.mesa-sin-pedido.mesa-fav-abierto .caja-venta-lateral { display: flex !important; }
	.caja-venta.mesa-sin-pedido .caja-panel-selector { display: none; }
	/* Configuraciones > Modo de la caja web = Venta directa: las mesas con pedido de antes solo se cobran o anulan (sin productos nuevos ni cambio de mesa) */
	.caja-venta.mesa-modo-directa .caja-busqueda, .caja-venta.mesa-modo-directa #btnMoverMesa, .caja-venta.mesa-modo-directa #btnFavoritos { display: none !important; }
	.caja-mesas.solo-pendientes .leyenda-libre, .caja-mesas.solo-pendientes #btnOrganizarMesas { display: none !important; }
	.caja-mesas.solo-pendientes #mesasAyuda { color: var(--t-texto, #3A2A22); font-weight: 600; }
	.caja-num { text-align: right; white-space: nowrap; }

	/* Modal de productos por familia */
	.caja-familias { display: flex; flex-wrap: wrap; gap: 8px; padding: 12px 0; }
	.caja-familias .btn { white-space: nowrap; border-radius: 18px; font-size: 12px; padding: 6px 14px; }
	.caja-familias .btn-default { color: #3A2A22; border-color: var(--t-borde, #EADFD2); background: var(--t-fondo-alt, #FBF8F3); }
	.caja-familias .btn-default:hover { background: var(--t-primario-suave, var(--t-primario-suave, #FBEEE8)); }
	.caja-productos { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); grid-auto-rows: min-content; gap: 12px;
		overflow-y: auto; padding: 2px 4px 4px 2px; max-height: 58vh; }
	.caja-producto { border: 1px solid var(--t-borde, #EADFD2); border-radius: 10px; background: #fff; cursor: pointer; min-height: 92px; padding: 12px 14px; position: relative;
		display: flex; flex-direction: column; justify-content: space-between; text-align: left; transition: border-color .15s, box-shadow .15s, transform .1s; }
	.caja-producto:hover, .caja-producto:focus { border-color: var(--t-primario, #B4432E); box-shadow: 0 4px 12px rgba(58, 42, 34,.10); outline: none; }
	.caja-producto:active { transform: scale(.97); }
	.caja-producto.agregado { border-color: var(--t-primario, #B4432E); background: var(--t-primario-suave, #FBEEE8); }
	.caja-producto .nombre { font-size: 12px; font-weight: 600; line-height: 1.35; color: #3A2A22; word-break: break-word; text-transform: uppercase; }
	.caja-producto .opciones { font-size: 11px; color: var(--t-texto-tenue, #A89A8C); margin-top: 3px; }
	.caja-producto .precio { font-weight: 700; font-size: 15px; color: var(--t-primario, #B4432E); margin-top: 8px; }
	/* Tarjetas con imagen del producto (o inicial cuando no tiene) */
	.caja-producto.con-foto { padding: 0 0 10px; overflow: hidden; display: flex; flex-direction: column; text-align: left; }
	.caja-producto.con-foto .foto { display: block; width: 100%; aspect-ratio: 4 / 3; background: var(--t-fondo-alt, #FBF8F3); border-bottom: 1px solid var(--t-borde, #EADFD2); margin-bottom: 8px; overflow: hidden; }
	.caja-producto.con-foto .foto img { width: 100%; height: 100%; object-fit: cover; display: block; }
	.caja-producto.con-foto .foto.sin-foto { display: flex; align-items: center; justify-content: center; font-size: 30px; font-weight: 700; color: var(--t-primario-borde, #EFD2C6); background: linear-gradient(135deg, #FBF7F1 0%, #F3ECE1 100%); }
	.caja-producto.con-foto .nombre, .caja-producto.con-foto .opciones, .caja-producto.con-foto .precio { padding: 0 12px; }
	.caja-producto.con-foto .precio { margin-top: auto; padding-top: 6px; }
	.caja-producto.con-foto .en-venta { z-index: 2; box-shadow: 0 2px 6px rgba(0,0,0,.2); }
	.caja-producto .en-venta { position: absolute; top: 6px; right: 6px; background: var(--t-primario, #B4432E); color: #fff; border-radius: 10px; font-size: 11px; font-weight: 700; padding: 1px 7px; }
	.caja-vacio { color: var(--t-texto-tenue, #A89A8C); padding: 40px 10px; text-align: center; grid-column: 1 / -1; }
	.caja-vacio i { display: block; font-size: 36px; margin-bottom: 6px; }
	#modalProductos .modal-dialog { width: 92%; max-width: 1100px; }
	#modalProductos .modal-body { padding-top: 10px; }
	.modal-productos-pie { display: flex; align-items: center; justify-content: space-between; text-align: left; }
	/* El clearfix de Bootstrap (::before/::after) cuenta como elemento flex y descuadra el pie */
	.modal-productos-pie::before, .modal-productos-pie::after { display: none; }

	/* Celular: barra fija para ir a la venta cuando el carro queda abajo de los productos */
	.caja-barra-movil { display: none; }
	@media (max-width: 991px) {
		.caja-barra-movil.visible { display: flex; position: fixed; left: 0; right: 0; bottom: 0; z-index: 1030; align-items: center; justify-content: space-between;
			background: var(--t-primario, #B4432E); color: #fff; padding: 12px 16px; font-size: 16px; font-weight: 600; box-shadow: 0 -2px 8px rgba(0,0,0,.15); cursor: pointer; }
		.caja.con-barra { padding-bottom: 60px; }
	}

	/* Tablas y paneles de las otras pestañas */
	.caja .table-bordered, .caja .table-bordered > thead > tr > th, .caja .table-bordered > tbody > tr > td { border-left: 0; border-right: 0; border-color: var(--t-borde-suave, #F0E8DD); }
	#tablaVentas tr.anulada td { color: #B4C0CF; text-decoration: line-through; }
	#tablaVentas tr.anulada td.acciones { text-decoration: none; }
	#tablaVentas tr.nc td.caja-num { color: var(--t-peligro, #C9372C); }
	/* Cabecera con botón a la derecha: en flex para que en celular el botón baje en vez de tapar el título */
	#tabVentas .panel-heading, #tabCierre .panel-heading { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px 12px; }
	#tabVentas .panel-heading::before, #tabVentas .panel-heading::after,
	#tabCierre .panel-heading::before, #tabCierre .panel-heading::after { display: none; }
	.caja-btn-titulo { flex: 0 0 auto; border-radius: 6px; }
	#tabVentas .panel-heading .panel-title, #tabCierre .panel-heading .panel-title { flex: 1 1 220px; min-width: 0; }
	.dev-info { background: var(--t-fondo-alt, #FBF8F3); border: 1px solid var(--t-borde, #EADFD2); border-radius: 8px; padding: 10px 12px; margin-bottom: 12px; color: #3A2A22; }
	.dev-info b { font-size: 15px; }
	#devLineas td { vertical-align: middle; }
	#devLineas input { text-align: center; }
	#devLineas .dev-col-cant { width: 130px; }
	@media (max-width: 767px) { #devLineas .dev-col-cant { width: 80px; } }
	#devLineas tr.agotada td { color: #B4C0CF; }
	.caja-chk-imprimir { float: left; margin: 8px 0 0; }
	.caja-chk-imprimir .cbo-papel { margin-left: 8px; height: 24px; padding: 0 4px; }
	.caja-chk-comanda { margin-left: 16px !important; }
	/* Botón de mensaje para la comanda en cada línea del carro */
	.caja-linea .caja-btn-mensaje { display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; margin-left: 6px; border-radius: 8px;
		color: var(--t-texto-tenue, #A89A8C); border: 1px dashed var(--t-borde, #EADFD2); font-size: 13px; vertical-align: middle; text-decoration: none; }
	.caja-linea .caja-btn-mensaje:hover { color: var(--t-primario, #B4432E); border-color: var(--t-primario-borde, #EFD2C6); background: var(--t-primario-suave, #FBEEE8); }
	.caja-linea .caja-btn-mensaje.con-mensaje { color: var(--t-primario, #B4432E); border-style: solid; border-color: var(--t-primario-borde, #EFD2C6); background: var(--t-primario-suave, #FBEEE8); }
	.caja-frame-impresion { position: absolute; left: -10000px; top: 0; width: 400px; height: 600px; border: 0; }
	.caja-apertura { max-width: 460px; margin: 30px auto; }
	/* Sin caja abierta: de fondo la misma foto del login, oscurecida para que se lea la tarjeta (11-11-2026) */
	body.caja-sin-abrir .content-wrapper {
		background: linear-gradient(rgba(20, 14, 10, .45), rgba(20, 14, 10, .45)),
			url("<?php echo base_url() ?>assets/dist/img/imgfondoo.jpg") center / cover no-repeat fixed;
	}
	body.caja-sin-abrir .caja-apertura { margin-top: 8vh; border: 0; box-shadow: 0 20px 60px rgba(0, 0, 0, .35); }
	.caja-apertura .icono { width: 88px; height: 88px; line-height: 88px; margin: 6px auto 14px; border-radius: 50%; background: var(--t-primario-suave, #FBEEE8);
		font-size: 48px; color: var(--t-primario, #B4432E); text-align: center; display: block; }
	.caja-apertura p.text-muted { font-size: 14px; line-height: 1.6; margin-bottom: 18px; }
	.caja-apertura label, .caja-cierre-form label, .caja-mov-form label { font-size: 12px; color: var(--t-texto-suave, #7A6A5E); text-transform: uppercase; letter-spacing: .3px; font-weight: 700; margin-bottom: 5px; }
	.caja-apertura .input-group-addon, .caja-cierre-form .input-group-addon { font-weight: 700; }
	#fondoInicial, #efectivoContado { font-size: 22px; font-weight: 800; color: var(--t-texto-fuerte, #2A1D17); text-align: right; }
	#btnAbrirCaja, #btnCerrarCaja { border-radius: 8px; font-weight: 700; padding: 12px; }
	.caja-control-info { font-size: 13px; margin: 6px 0 0; }
	.caja-control-info i { margin-right: 4px; }

	/* Pestaña Ventas: estado vacío, acciones y montos legibles */
	.caja-tab-ayuda { display: block; font-size: 12px; font-weight: 400; color: var(--t-texto-tenue, #A89A8C); margin-top: 2px; text-transform: none; letter-spacing: 0; }
	.caja .panel-body.table-responsive { padding: 0; margin: 0; border: 0; }
	.caja .panel-body.table-responsive > .table { margin: 0; }
	.caja .panel-body.table-responsive > .table > thead > tr > th:first-child,
	.caja .panel-body.table-responsive > .table > tbody > tr > td:first-child { padding-left: 18px; }
	.caja .panel-body.table-responsive > .table > thead > tr > th:last-child,
	.caja .panel-body.table-responsive > .table > tbody > tr > td:last-child { padding-right: 18px; }
	.caja .panel-body.table-responsive > .table > thead > tr > th { font-size: 12px; color: var(--t-texto-suave, #7A6A5E); text-transform: uppercase; letter-spacing: .3px; padding-top: 10px; padding-bottom: 10px; }
	.caja .panel-body.table-responsive > .table > tbody > tr > td { padding-top: 10px; padding-bottom: 10px; }
	#tablaVentas td.caja-num, #tablaMovimientos td.caja-num { font-weight: 700; color: var(--t-texto-fuerte, #2A1D17); font-size: 14px; }
	#tablaVentas td.acciones { text-align: center; }
	#tablaVentas td.acciones .btn-xs { border-radius: 5px; padding: 3px 8px; margin: 1px 0; }
	#tablaVentas td .label { font-size: 11px; }
	/* La fila "sin datos" que arma js.php (td con colspan) se ve como estado vacío */
	#tablaVentas td[colspan], #tablaMovimientos td[colspan] { padding: 44px 10px !important; color: var(--t-texto-tenue, #A89A8C); font-size: 14px; background: #fff; }
	#tablaVentas td[colspan]::before, #tablaMovimientos td[colspan]::before { font-family: FontAwesome; display: block; font-size: 38px; color: #C9D3E0; margin-bottom: 8px; }
	#tablaVentas td[colspan]::before { content: "\f15c"; }
	#tablaMovimientos td[colspan]::before { content: "\f0d6"; }
	#tablaVentas tbody tr:hover td[colspan], #tablaMovimientos tbody tr:hover td[colspan] { background: #fff; }

	/* Pestaña Créditos */
	.cred-clientes { margin-top: 10px; max-height: 520px; overflow-y: auto; }
	.cred-clientes .list-group-item { cursor: pointer; }
	.cred-clientes .list-group-item.active, .cred-clientes .list-group-item.active:hover { background: var(--t-primario-suave, #F3E7E3); color: inherit; border-color: #ddd; }
	.cred-clientes .cred-monto { float: right; font-weight: 700; }
	.cred-clientes .cred-vencido { color: #C9372C; font-size: 11px; }
	.cred-resumen { display: flex; gap: 18px; flex-wrap: wrap; margin-bottom: 10px; }
	.cred-resumen div { background: #F7F4F1; border-radius: 6px; padding: 6px 12px; }
	.cred-resumen small { display: block; color: #8A7B6E; }
	.cred-tabla td, .cred-tabla th { vertical-align: middle !important; }
	.cred-tabla tr.cred-vence td { background: #FDF0EE; }
	.cred-tabla .cred-lineas td { background: #FAFAFA; font-size: 12px; padding: 4px 8px 4px 40px !important; }
	.cred-tabla .cred-ver { cursor: pointer; color: var(--t-primario, #B4432E); }
	.cred-pago { border-top: 1px solid #eee; margin-top: 10px; padding-top: 12px; }
	.cred-pago .cred-total { font-size: 22px; font-weight: 700; }

	/* Pestaña Ingresos / Retiros */
	.caja-tipo-mov { display: flex; gap: 6px; background: var(--t-tinte-2, #F5EFE6); border-radius: 8px; padding: 4px; width: 100%; }
	.caja-tipo-mov label { flex: 1; text-align: center; margin: 0; border: 0 !important; border-radius: 6px !important; background: transparent; color: var(--t-texto-suave, #7A6A5E);
		padding: 9px 6px; text-transform: none; letter-spacing: 0; font-size: 14px; box-shadow: none !important; }
	.caja-tipo-mov label.active { background: #fff; color: var(--t-texto-fuerte, #2A1D17); box-shadow: 0 1px 3px rgba(58, 42, 34,.15) !important; }
	.caja-tipo-mov label.active .fa-arrow-down { color: var(--t-primario, #B4432E); }
	.caja-tipo-mov label.active .fa-arrow-up { color: var(--t-peligro, #C9372C); }
	#montoMov { font-size: 18px; font-weight: 700; text-align: right; }
	#btnMovimiento { border-radius: 8px; padding: 10px; font-weight: 700; }
	#tablaMovimientos tbody td.caja-num:nth-child(3) { color: var(--t-primario, #B4432E); }
	#tablaMovimientos tbody td.caja-num:nth-child(4) { color: var(--t-peligro, #C9372C); }

	/* Pestaña Cierre */
	.cierre-tabla { margin: 0; }
	.cierre-tabla td { padding: 9px 4px !important; color: var(--t-texto, #3A2A22); border-top-color: var(--t-borde-suave, #F0E8DD) !important; }
	.cierre-tabla td:last-child { text-align: right; white-space: nowrap; font-weight: 700; color: var(--t-texto-fuerte, #2A1D17); }
	.cierre-tabla tr:first-child td { border-top: 0; }
	.cierre-tabla th[colspan] { padding: 16px 4px 6px !important; font-size: 12px; color: var(--t-texto-suave, #7A6A5E); text-transform: uppercase; letter-spacing: .3px; font-weight: 700;
		background: none !important; border-top: 0 !important; border-bottom: 1px solid var(--t-borde-suave, #F0E8DD) !important; }
	.cierre-tabla tr.info td { background: var(--t-primario-suave, #FBEEE8) !important; border-top: 1px solid var(--t-primario-borde, #EFD2C6) !important; color: #3A2A22; font-size: 15px; }
	.cierre-tabla tr.info td:last-child b { color: var(--t-primario, #B4432E); font-size: 18px; }
	.cierre-tabla tr.info td:first-child { border-radius: 8px 0 0 8px; padding-left: 12px !important; }
	.cierre-tabla tr.info td:last-child { border-radius: 0 8px 8px 0; padding-right: 12px !important; }
	.caja-cierre-form #lblEntregar { font-size: 20px; color: var(--t-texto-fuerte, #2A1D17); }
	.caja-cierre-form .form-control-static { padding-top: 4px; }
	.caja-cierre-form #declaradoOtros { border-top: 1px dashed var(--t-borde, #EADFD2); padding-top: 12px; margin-bottom: 4px; }
	.caja-cierre-form #declaradoOtros:empty { display: none; }
	.caja-cierre-form .declarado { text-align: right; font-weight: 700; }
	.cierre-kpis { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin: 14px 0 16px; }
	.cierre-kpis > div { background: var(--t-fondo-alt, #FBF8F3); border: 1px solid var(--t-borde, #EADFD2); border-radius: 10px; padding: 10px 12px; min-width: 0; }
	.cierre-kpis small { display: block; font-size: 11px; font-weight: 700; color: var(--t-texto-suave, #7A6A5E); text-transform: uppercase; letter-spacing: .3px; }
	.cierre-kpis b, .cierre-kpis span#lblDiferencia { display: block; font-size: 20px; font-weight: 800; color: var(--t-texto-fuerte, #2A1D17); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
	.cierre-kpis .cierre-diferencia { background: #fff; border-width: 2px; }
	.cierre-kpis.cierre-ciego { grid-template-columns: 1fr; }
	.cierre-ayuda { font-size: 12px; color: var(--t-texto-tenue, #A89A8C); text-align: center; margin: 8px 0 0; }

	/* Totales con filas (devolución) */
	.caja-totales { background: var(--t-fondo-alt, #FBF8F3); border: 1px solid var(--t-borde, #EADFD2); border-radius: 10px; padding: 8px 14px; margin-top: 10px; }
	.caja-totales .fila { display: flex; justify-content: space-between; align-items: center; line-height: 1.9; color: var(--t-texto, #3A2A22); }
	.caja-totales .fila b { color: var(--t-texto-fuerte, #2A1D17); }
	.caja-totales .fila.total { font-size: 16px; font-weight: 700; color: #3A2A22; }
	.caja-totales .fila.total b { font-size: 22px; color: var(--t-peligro, #C9372C); }

	/* Modales */
	/* Cobro: montos arriba, formas de pago abajo a la izquierda y el monto a cargar a la derecha */
	#modalPago .modal-dialog { width: 94%; max-width: 860px; }
	#modalPago .modal-body { padding: 18px 20px; }
	.pago-montos { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 14px; }
	.pago-montos > div { background: var(--t-fondo-alt, #FBF8F3); border: 1px solid var(--t-borde, #EADFD2); border-radius: 10px; padding: 10px 14px; }
	.pago-montos small { display: block; font-size: 12px; font-weight: 700; color: var(--t-texto-suave, #7A6A5E); text-transform: uppercase; letter-spacing: .3px; }
	.pago-montos b { font-size: 26px; font-weight: 800; color: var(--t-texto-fuerte, #2A1D17); white-space: nowrap; }
	.pago-montos .total { background: var(--t-primario-suave, #FBEEE8); border-color: var(--t-primario-borde, #EFD2C6); }
	.pago-montos .total b { color: var(--t-primario, #B4432E); }
	.pago-montos .falta b { color: var(--t-peligro, #C9372C); }
	.pago-montos .falta.cubierto b { color: var(--t-texto-tenue, #A89A8C); }
	.pago-montos .vuelto b { color: var(--t-ok, #5E7F3A); }
	.pago-propina { background: var(--t-fondo-alt, #FBF8F3); border: 1px solid var(--t-borde, #EADFD2); border-radius: 10px; padding: 8px 12px; margin-bottom: 12px; }
	.pago-propina input[type=number] { width: 120px; display: inline-block; text-align: right; }
	.pago-agregados { border: 1px dashed var(--t-primario-borde, var(--t-primario-borde, #EFD2C6)); border-radius: 10px; padding: 6px 12px; margin-bottom: 16px; min-height: 52px; }
	.pago-agregados .vacio { color: var(--t-texto-tenue, #A89A8C); text-align: center; padding: 10px 0; }
	.pago-fila { display: flex; gap: 8px; align-items: center; padding: 6px 0; border-bottom: 1px solid var(--t-borde-suave, #F0E8DD); }
	.pago-fila:last-child { border-bottom: 0; }
	.pago-fila .nombre { flex: 1; font-size: 16px; font-weight: 700; color: var(--t-texto-fuerte, #2A1D17); }
	.pago-fila input { width: 150px; text-align: right; font-size: 18px; font-weight: 700; height: 40px; }
	.pago-cuerpo { display: flex; gap: 18px; }
	.pago-formas { flex: 0 0 38%; display: flex; flex-direction: column; gap: 8px; }
	.pago-formas .btn { text-align: left; font-size: 15px; font-weight: 700; padding: 11px 14px; border-radius: 8px; background: #fff; color: #3A2A22; border: 1px solid var(--t-borde, #EADFD2); white-space: normal; }
	.pago-formas .btn:hover { border-color: var(--t-primario-borde, #EFD2C6); }
	.pago-formas .btn.activa { background: var(--t-primario, #B4432E); border-color: var(--t-primario, #B4432E); color: #fff; }
	.pago-formas .btn.cortesia { border-style: dashed; }
	.pago-formas .btn.cortesia.activa { background: var(--t-aviso-fondo, #FFF4DF); border-color: var(--t-aviso-borde, #F3DDB4); color: var(--t-aviso, #A66C25); }
	.pago-formas .btn .fa { width: 22px; }
	.pago-monto { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 10px; }
	.pago-monto label { font-size: 12px; font-weight: 700; color: var(--t-texto-suave, #7A6A5E); text-transform: uppercase; letter-spacing: .3px; margin: 0; }
	.pago-monto .input-group-addon { font-size: 20px; font-weight: 700; }
	#montoPago { height: 56px; font-size: 28px; font-weight: 800; text-align: right; color: var(--t-texto-fuerte, #2A1D17); }
	.pago-billetes { display: flex; flex-wrap: wrap; gap: 6px; }
	.pago-billetes .btn { flex: 1 1 70px; font-weight: 700; }
	#btnAgregarPago { font-size: 16px; font-weight: 700; padding: 10px; border-radius: 8px; }
	.btn-pago-exacto { font-size: 20px; font-weight: 800; padding: 14px; border-radius: 8px; margin-top: auto; }
	/* Venta directa: nombre de quien retira (opcional, 12-11-2026) */
	.pago-retira label small { font-weight: 400; text-transform: none; letter-spacing: 0; color: var(--t-texto-tenue, #A89A8C); }
	.pago-retira .form-control { height: 42px; font-size: 17px; font-weight: 700; }
	#modalPago .modal-footer .btn { border-radius: 8px; }
	@media (max-width: 767px) {
		.pago-montos { grid-template-columns: repeat(2, 1fr); }
		.pago-cuerpo { flex-direction: column; }
		.pago-montos b { font-size: 20px; }
	}
	.opcion-lista .checkbox { margin: 6px 0; }
	.opcion-lista .checkbox label { font-size: 14px; }
	.opcion-lista .valor { color: var(--t-primario, #B4432E); font-weight: bold; }
	#modalAutorizacion { z-index: 1060; }

	/* Venta registrada */
	#modalVentaOk .modal-dialog { width: 92%; max-width: 440px; margin-top: 12vh; }
	.venta-ok { border: 0; border-radius: 14px; padding: 30px 28px 20px; text-align: center; box-shadow: 0 20px 50px rgba(0,0,0,.25); }
	.venta-ok-icono { width: 76px; height: 76px; margin: 0 auto 14px; border-radius: 50%; background: var(--t-primario-suave, #FBEEE8); color: var(--t-primario, #B4432E); font-size: 38px; line-height: 76px; }
	.venta-ok h3 { margin: 0 0 6px; font-size: 26px; font-weight: 800; color: var(--t-texto-fuerte, #2A1D17); }
	.venta-ok-doc { font-size: 16px; font-weight: 600; color: var(--t-texto-suave, #7A6A5E); margin-bottom: 16px; }
	.venta-ok-vuelto { background: var(--t-primario-suave, #FBEEE8); border: 1px solid var(--t-primario-borde, #EFD2C6); border-radius: 10px; padding: 12px; margin-bottom: 16px; font-size: 26px; font-weight: 800; color: var(--t-ok, #5E7F3A); }
	.venta-ok-vuelto:empty { display: none; }
	.venta-ok-acciones { display: flex; gap: 10px; margin-bottom: 10px; }
	.venta-ok-acciones .btn { flex: 1; padding: 10px; font-weight: 700; border-radius: 8px; border-color: var(--t-borde, #EADFD2); color: #3A2A22; }
	.venta-ok-acciones .btn:hover { border-color: var(--t-primario, #B4432E); color: var(--t-primario, #B4432E); background: #fff; }
	.venta-ok-acciones .fa { margin-right: 4px; }
	#btnNuevaVenta { font-size: 18px; font-weight: 700; padding: 13px; border-radius: 8px; }
	#btnNuevaVenta:focus { outline: none; box-shadow: 0 0 0 4px rgba(180, 67, 46,.3); }
	.venta-ok-ayuda { font-size: 12px; color: var(--t-texto-tenue, #A89A8C); margin-top: 8px; }
	.modal-header.btn-success .close { color: var(--t-texto-tenue, #A89A8C); opacity: 1; text-shadow: none; }

	@media (max-width: 991px) {
		.caja-venta { flex-direction: column; }
		.caja-venta-lateral { flex-basis: auto; min-width: 0; max-width: none; overflow: visible; }
		.caja-busqueda { flex-wrap: wrap; }
		.caja-busqueda .caja-buscar { flex-basis: 100%; }
		.caja-busqueda select { flex: 1; width: auto; }
		.caja-productos { max-height: 55vh; }
		.caja-estado { margin-left: 0; width: 100%; border-top: 1px solid var(--t-borde-suave, #F0E8DD); }
		/* Movimientos y Cierre apilados: separar las columnas */
		#tabMovimientos .col-md-4 .panel, #tabCierre .col-md-6 .panel { margin-bottom: 16px; }
	}
	@media (max-width: 767px) {
		.cierre-kpis { grid-template-columns: 1fr 1fr; }
		.cierre-kpis .cierre-diferencia { grid-column: 1 / -1; }
		.caja-tabs { flex-wrap: nowrap; overflow-x: auto; -webkit-overflow-scrolling: touch; width: 100%; }
		.caja-tabs > li > a { white-space: nowrap; }
	}
	@media (min-width: 992px) {
		/* El alto lo fija ajustarAltoVenta() en js.php */
		.caja-venta.alto-fijo .caja-lineas { max-height: none; }
	}
	@media (max-width: 480px) {
		.caja-tabs > li > a { padding: 10px 8px; font-size: 12px; }
		.caja-productos { grid-template-columns: repeat(2, 1fr); }
		.caja-linea .subtotal { width: 64px; }
		.caja-linea-cab .precio, .caja-linea .precio { display: none; }
		.caja-linea .unitario { display: block; }
		.caja-linea .cantidad, .caja-linea-cab .cantidad { width: 96px; flex-basis: 96px; }
		.caja-carro-pie { flex-wrap: wrap; }
		.caja-carro-pie select { max-width: none; width: 100%; }
	}

	/* ===== Capa final "Perla y azul" (estilo mockup): tarjetas blancas radio 14px, borde #EADFD2 sobre fondo perla ===== */
	.caja-barra, .caja-tarjeta, .caja .panel { background: #fff; border: 1px solid var(--t-borde, #EADFD2); border-radius: 14px; box-shadow: var(--t-sombra, 0 1px 2px rgba(58, 42, 34,.05)); }
	.caja-tabs > li > a { color: var(--t-texto-suave, #7A6A5E); }
	.caja-tabs > li > a:hover { color: var(--t-texto, #3A2A22); }
	.caja-tabs > li > a i { color: var(--t-texto-tenue, #A89A8C); }
	.caja-tabs > li.active > a i { color: var(--t-primario, #B4432E); }
	.caja-estado small, .caja-tarjeta-titulo, .caja-venta-lateral label { color: var(--t-texto-suave, #7A6A5E); }
	.caja-estado b { color: var(--t-texto, #3A2A22); }
	.caja-estado .ok b { color: var(--t-primario, #B4432E); }
	/* Buscador: tarjeta blanca, input sobre fondo alterno */
	.caja-busqueda { background: #fff; border-radius: 14px; }
	.caja-buscar input { background: var(--t-fondo-alt, #FBF8F3); border-color: var(--t-borde, #EADFD2); color: var(--t-texto, #3A2A22); }
	.caja-buscar input:focus { background: #fff; }
	.caja-busqueda select, .caja-carro-pie select { border-color: var(--t-borde, #EADFD2); color: var(--t-texto, #3A2A22); }
	.caja-resultados { border-color: var(--t-borde, #EADFD2); border-radius: 12px; box-shadow: var(--t-sombra-alta, 0 12px 32px rgba(58, 42, 34,.14)); }
	/* Cabecera del carro: blanca, mayúsculas pequeñas en gris */
	.caja-linea-cab { background: #fff; border-bottom: 1px solid var(--t-borde, #EADFD2); font-size: 11px; color: var(--t-texto-suave, #7A6A5E); letter-spacing: .5px; }
	.caja-linea:hover { background: var(--t-fondo-alt, #FBF8F3); }
	.caja-linea .nombre, .caja-linea .subtotal { color: var(--t-marino, #3A2A22); }
	.caja-carro-pie { background: var(--t-fondo-alt, #FBF8F3); border-top-color: var(--t-borde, #EADFD2); }
	/* Total destacado: fondo azul suave y cifra azul */
	.caja-carro-pie .total { background: var(--t-primario-suave, #FBEEE8); border: 1px solid var(--t-primario-borde, #EFD2C6); border-radius: 12px; padding: 4px 14px; color: var(--t-texto-suave, #7A6A5E); }
	.caja-carro-pie .total b { color: var(--t-primario, #B4432E); }
	.caja-venta-lateral .caja-resumen { background: #fff; }
	.caja-resumen .fila { color: var(--t-texto-suave, #7A6A5E); }
	.caja-resumen .fila span:last-child { color: var(--t-marino, #3A2A22); }
	.caja-resumen .fila.descuento, .caja-resumen .fila.descuento span:last-child { color: var(--t-peligro, #C9372C); }
	.caja-venta-lateral [data-tipo-desc].active { background: var(--t-primario-suave, #FBEEE8); border-color: var(--t-primario-borde, #EFD2C6); color: var(--t-primario, #B4432E); box-shadow: none; }
	/* Modal de productos */
	.caja-familias .btn-default { background: #fff; border-color: var(--t-borde, #EADFD2); color: var(--t-texto, #3A2A22); }
	.caja-familias .btn-default:hover { background: var(--t-primario-suave, #FBEEE8); border-color: var(--t-primario-borde, #EFD2C6); }
	.caja-productos { background: var(--t-fondo, #F6F1E8); border-radius: 14px; padding: 10px; }
	.caja-producto { border-color: var(--t-borde, #EADFD2); border-radius: 12px; }
	.caja-producto:hover, .caja-producto:focus, .caja-producto.agregado { border-color: var(--t-primario-borde, #EFD2C6); }
	.caja-producto.agregado { background: var(--t-primario-suave, #FBEEE8); }
	.caja-producto .nombre { color: var(--t-marino, #3A2A22); }
	/* Cuadros de cifras: neutros en fondo alterno; los destacados en azul suave */
	.cierre-kpis > div, .caja-totales, .pago-montos > div, .pago-propina, .dev-info { background: var(--t-fondo-alt, #FBF8F3); border-color: var(--t-borde, #EADFD2); border-radius: 12px; }
	.pago-montos .total { background: var(--t-primario-suave, #FBEEE8); border-color: var(--t-primario-borde, #EFD2C6); }
	.cierre-kpis .cierre-diferencia { background: #fff; border-color: var(--t-primario-borde, #EFD2C6); }
	.pago-formas .btn { background: #fff; border-color: var(--t-borde, #EADFD2); color: var(--t-texto, #3A2A22); border-radius: 10px; }
	.pago-formas .btn:hover { background: var(--t-fondo-alt, #FBF8F3); border-color: var(--t-primario-borde, #EFD2C6); }
	.pago-formas .btn.activa, .pago-formas .btn.activa:hover { background: var(--t-primario, #B4432E); border-color: var(--t-primario, #B4432E); color: #fff; }
	.pago-formas .btn.cortesia.activa, .pago-formas .btn.cortesia.activa:hover { background: var(--t-aviso-fondo, #FFF4DF); border-color: var(--t-aviso-borde, #F3DDB4); color: var(--t-aviso, #A66C25); }
	.pago-billetes .btn { background: #fff; border-color: var(--t-borde, #EADFD2); color: var(--t-texto, #3A2A22); }
	.pago-billetes .btn:hover { background: var(--t-primario-suave, #FBEEE8); border-color: var(--t-primario-borde, #EFD2C6); color: var(--t-primario, #B4432E); }
	.venta-ok-vuelto { color: var(--t-primario, #B4432E); }
	.venta-ok h3 { color: var(--t-marino, #3A2A22); }
	/* Tablas de las pestañas: cabecera blanca en mayúsculas pequeñas gris */
	.caja .panel-body.table-responsive > .table > thead > tr > th, #devLineas > thead > tr > th { background: #fff !important; color: var(--t-texto-suave, #7A6A5E) !important; font-size: 11px; font-weight: 700;
		text-transform: uppercase; letter-spacing: .5px; border-bottom: 1px solid var(--t-borde, #EADFD2) !important; }
	.caja .panel-heading h4, .caja .panel-heading .panel-title { color: var(--t-marino, #3A2A22); }
	.caja-tab-ayuda { color: var(--t-texto-tenue, #A89A8C); }
	.cierre-tabla td { color: var(--t-texto-suave, #7A6A5E); }
	.cierre-tabla td:last-child { color: var(--t-marino, #3A2A22); }
	.cierre-tabla tr.info td { background: var(--t-primario-suave, #FBEEE8) !important; color: var(--t-marino, #3A2A22); }

	/* Ancho reducido por la barra lateral (contenido ≈ ventana - 232 - 56 px): columnas más compactas */
	@media (min-width: 992px) and (max-width: 1439px) {
		.caja-venta-lateral { flex-basis: 30%; min-width: 300px; }
		.caja-venta-lateral .caja-tarjeta { padding: 14px 16px; }
		.caja-busqueda select { width: 150px; flex: 0 0 150px; }
		.caja-linea, .caja-linea-cab { padding-left: 14px; padding-right: 14px; gap: 8px; }
		.caja-linea .precio, .caja-linea-cab .precio { width: 88px; }
		.caja-linea .subtotal, .caja-linea-cab .subtotal { width: 96px; }
		.caja-linea .cantidad, .caja-linea-cab .cantidad { width: 116px; flex-basis: 116px; }
		.caja-linea .nombre { font-size: 15px; }
		.caja-carro-pie select { width: 220px; }
		.caja-carro-pie .total b { font-size: 26px; }
		.caja-acciones #btnCobrar { font-size: 18px; }
		.caja-estado { gap: 6px 16px; }
		.pago-montos b { font-size: 22px; }
		.cierre-kpis b, .cierre-kpis span#lblDiferencia { font-size: 18px; }
	}
	/* 992-1199 px: el contenido queda en ~700-900 px; se oculta la columna Precio (el unitario va bajo el nombre) */
	@media (min-width: 992px) and (max-width: 1199px) {
		.caja-venta-lateral { min-width: 280px; }
		.caja-busqueda { flex-wrap: wrap; }
		.caja-busqueda .caja-buscar { flex-basis: 100%; }
		.caja-busqueda select { flex: 1; width: auto; }
		.caja-linea-cab .precio, .caja-linea .precio { display: none; }
		.caja-linea .unitario { display: block; }
		.caja-carro-pie select { width: auto; flex: 1 1 140px; min-width: 0; }
		.cierre-kpis { grid-template-columns: 1fr 1fr; }
		.cierre-kpis .cierre-diferencia { grid-column: 1 / -1; }
	}

	/* Datos de la factura (al cobrar con factura) */
	#modalFactura .fac-cliente { display: flex; flex-wrap: wrap; align-items: baseline; gap: 4px 10px; margin-bottom: 14px; padding: 10px 12px;
		border-radius: 10px; background: var(--t-fondo-alt, #FBF8F3); font-size: 14px; }
	#modalFactura .fac-cliente b { color: var(--t-texto-fuerte, #2A1D17); }
	#modalFactura .fac-solicita { margin-top: 4px; padding: 10px 12px 0; border: 1px solid var(--t-borde, #EADFD2); border-radius: 10px; }
	#modalFactura .fac-solicita legend { width: auto; margin: 0 0 4px; padding: 0 6px; border: 0; font-size: 13px; font-weight: 700; color: var(--t-texto, #3A2A22); }
	#modalFactura label { font-size: 12px; text-transform: uppercase; letter-spacing: .3px; color: var(--t-texto-suave, #7A6A5E); }
	#modalFactura .form-group.has-error .form-control { border-color: #C9372C; }
	/* Listas del cliente (12-11-2026): se elige de la lista; "Otro…" muestra los campos para escribirlo */
	#modalFactura [hidden] { display: none !important; }
	#modalFactura .fac-otro { margin-top: 6px; }
	#modalFactura .fac-guardar { margin: 4px 0 0; }
	#modalFactura .fac-guardar label { text-transform: none; letter-spacing: 0; font-size: 13px; color: var(--t-texto, #3A2A22); }

	/* Modal de productos: interruptor tipo iOS "Cerrar al seleccionar" junto a Listo */
	.modal-productos-pie { flex-wrap: wrap; gap: 10px; }
	.modal-productos-acciones { display: flex; align-items: center; gap: 16px; margin-left: auto; }
	.caja-switch { position: relative; display: inline-flex; align-items: center; gap: 9px; margin: 0; font-weight: 600; font-size: 13px;
		color: var(--t-texto-suave, #7A6A5E); cursor: pointer; -webkit-user-select: none; user-select: none; }
	.caja-switch input { position: absolute; opacity: 0; width: 1px; height: 1px; margin: 0; pointer-events: none; }
	.caja-switch-pista { position: relative; flex: 0 0 46px; width: 46px; height: 28px; border-radius: 14px; background: #D8D2CA; transition: background .2s; }
	.caja-switch-pista::after { content: ""; position: absolute; top: 2px; left: 2px; width: 24px; height: 24px; border-radius: 50%; background: #fff;
		box-shadow: 0 2px 4px rgba(0, 0, 0, .25); transition: transform .2s; }
	.caja-switch input:checked + .caja-switch-pista { background: var(--t-primario, #15803D); }
	.caja-switch input:checked + .caja-switch-pista::after { transform: translateX(18px); }
	.caja-switch input:focus-visible + .caja-switch-pista { box-shadow: 0 0 0 3px var(--t-primario-borde, #BBE3C9); }
	.caja-switch input:checked ~ .caja-switch-texto { color: var(--t-texto, #3A2A22); }

	/* Botón solo ícono junto al buscador: Productos (F2). Favoritos (★) va en el selector del panel lateral */
	.caja-busqueda .btn-icono { flex: 0 0 44px; width: 44px; padding: 0; font-size: 18px; }
	.caja-familias .caja-familia-fav i { color: #E3A008; margin-right: 2px; }
	.caja-familias .caja-familia-fav.btn-success i { color: #fff; }

	/* Ingresos / Retiros, Créditos y Cierre se abren desde el panel Opciones: arriba solo se ven mientras están abiertas.
	   En modo "solo mesas" sin mesa elegida no se ve la venta (ni su panel), y ahí quedan visibles arriba (js.php). */
	.caja-tabs > li.caja-tab-opcion:not(.active) { display: none; }
	.caja-tabs.mostrar-opciones > li.caja-tab-opcion { display: block; }

	/* Paneles laterales de la venta: selector Pagar / Opciones; se ve un panel a la vez y entra deslizándose */
	.caja-venta-lateral { overflow-x: hidden; }
	.caja-panel-selector { flex: 0 0 auto; display: flex; gap: 4px; padding: 4px; background: #fff; border: 1px solid var(--t-borde, #EADFD2);
		border-radius: 14px; box-shadow: var(--t-sombra, 0 1px 2px rgba(58, 42, 34, .05)); }
	.caja-panel-selector button { flex: 1 1 0; display: flex; align-items: center; justify-content: center; gap: 8px; height: 44px; padding: 0 10px;
		border: 0; border-radius: 10px; background: transparent; color: var(--t-texto-suave, #7A6A5E); font-size: 15px; font-weight: 600;
		cursor: pointer; transition: background .15s, color .15s; }
	.caja-panel-selector button:hover { background: var(--t-fondo-alt, #FBF8F3); color: var(--t-texto, #3A2A22); }
	.caja-panel-selector button.active { background: var(--t-primario, #B4432E); color: #fff; box-shadow: 0 4px 12px rgba(58, 42, 34, .16); }
	.caja-panel-selector button:focus { outline: none; }
	.caja-panel-selector button:focus-visible { outline: 2px solid var(--t-primario-borde, #EFD2C6); outline-offset: 2px; }
	.caja-panel { display: none; flex: 1 0 auto; flex-direction: column; gap: 12px; }
	.caja-panel.activo { display: flex; -webkit-animation: cajaPanelEntra .22s ease-out both; animation: cajaPanelEntra .22s ease-out both; }
	.caja-panel.activo.desde-izq { -webkit-animation-name: cajaPanelEntraIzq; animation-name: cajaPanelEntraIzq; }
	@keyframes cajaPanelEntra { from { opacity: 0; transform: translateX(28px); } to { opacity: 1; transform: none; } }
	@keyframes cajaPanelEntraIzq { from { opacity: 0; transform: translateX(-28px); } to { opacity: 1; transform: none; } }
	@media (prefers-reduced-motion: reduce) { .caja-panel.activo { -webkit-animation: none; animation: none; } }

	/* Panel Opciones: accesos grandes, de a dos por fila */
	.caja-opciones { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
	.caja-opcion { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 3px; min-height: 124px; padding: 14px 10px;
		border: 1px solid var(--t-borde, #EADFD2); border-radius: 14px; background: #fff; color: var(--t-texto, #3A2A22); text-align: center;
		cursor: pointer; transition: border-color .15s, background .15s, transform .15s; }
	.caja-opcion:hover { background: var(--t-fondo-alt, #FBF8F3); border-color: var(--t-primario-borde, #EFD2C6); transform: translateY(-1px); }
	.caja-opcion:focus { outline: none; }
	.caja-opcion:focus-visible { outline: 2px solid var(--t-primario-borde, #EFD2C6); outline-offset: 2px; }
	.caja-opcion i { display: inline-block; width: 50px; height: 50px; line-height: 50px; margin-bottom: 6px; border-radius: 50%; font-size: 22px; text-align: center;
		background: var(--t-primario-suave, #FBEEE8); color: var(--t-primario, #B4432E); }
	.caja-opcion[data-abrir="ingreso"] i { background: #E9F1DF; color: #46672A; }
	.caja-opcion[data-abrir="retiro"] i { background: #FBE9E6; color: #A63A2B; }
	.caja-opcion[data-abrir="creditos"] i { background: #FFF4DF; color: #9A6B12; font-size: 26px; }
	.caja-opcion[data-abrir="cierre"] i { background: #F3EEE7; color: #5A4436; font-size: 26px; }
	.caja-opcion b { font-size: 15px; font-weight: 700; color: var(--t-texto-fuerte, #2A1D17); }
	.caja-opcion small { font-size: 12px; line-height: 1.3; color: var(--t-texto-suave, #7A6A5E); }

	/* ===== Panel Favoritos (botón ★ del selector): sale desde la derecha, como Pagar y Opciones ===== */
	/* ★ del selector: solo ícono, a la izquierda de Pagar; marcado igual que Pagar y Opciones cuando el panel está abierto */
	.caja-panel-selector .sel-fav { flex: 0 0 52px; padding: 0; color: #E3A008; font-size: 18px; }
	.caja-panel-selector .sel-fav:hover { background: #FFF6E3; color: #C98A00; }
	.caja-panel-selector .sel-fav.is-activo { background: var(--t-primario, #B4432E); color: #fff; box-shadow: 0 4px 12px rgba(58, 42, 34, .16); }
	.caja-favoritos { display: flex; flex-direction: column; flex: 1 0 auto; }
	/* Escritorio (alto fijo): la cabecera queda quieta y solo la lista hace scroll */
	.caja-venta.alto-fijo #panelFavoritos.activo, .caja-venta.alto-fijo .caja-favoritos { flex: 1 1 0; min-height: 0; }
	.caja-venta.alto-fijo .fav-lista { flex: 1 1 0; min-height: 0; overflow-y: auto; margin-right: -8px; padding-right: 8px; }
	.fav-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; margin-bottom: 12px; }
	.fav-head-text { min-width: 0; }
	.fav-title { margin: 0; display: flex; align-items: center; gap: 8px; font-size: 18px; font-weight: 700; color: var(--t-texto-fuerte, #2A1D17); }
	.fav-title .fa-star { color: #F5B301; }
	.fav-resumen { margin-top: 3px; font-size: 12px; color: var(--t-texto-suave, #7A6A5E); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
	.fav-head-acciones { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
	/* El tema pone los label del panel lateral en mayúsculas pequeñas: el switch Fijar va normal */
	.caja-venta-lateral .fav-fijar { margin: 0; text-transform: none; letter-spacing: 0; }
	.fav-fijar .caja-switch-texto { font-size: 13px; font-weight: 700; color: var(--t-texto, #3A2A22); }
	.fav-recargar { flex-shrink: 0; width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid var(--t-borde, #EADFD2);
		border-radius: 8px; background: #fff; color: var(--t-texto-suave, #7A6A5E); cursor: pointer; }
	.fav-recargar:hover { background: var(--t-fondo-alt, #FBF8F3); color: var(--t-texto, #3A2A22); }
	.fav-recargar:focus { outline: none; }
	.fav-recargar:focus-visible { outline: 2px solid #F5B301; outline-offset: 2px; }
	.fav-recargar.is-cargando .fa-refresh { -webkit-animation: favGirar .8s linear infinite; animation: favGirar .8s linear infinite; }
	@keyframes favGirar { to { transform: rotate(360deg); } }
	.fav-toolbar { display: flex; align-items: center; gap: 8px; margin-bottom: 12px; }
	.fav-toolbar[hidden] { display: none; }
	.caja-venta-lateral .fav-familia { flex: 1; min-width: 0; height: 38px; padding: 0 10px; font-size: 13px; font-weight: 500; cursor: pointer; }
	.fav-vista { display: inline-flex; flex-shrink: 0; border: 1px solid var(--t-borde, #EADFD2); border-radius: 8px; background: #fff; overflow: hidden; }
	.fav-vista-btn { width: 38px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border: 0; background: transparent;
		color: var(--t-texto-suave, #7A6A5E); font-size: 14px; cursor: pointer; }
	.fav-vista-btn + .fav-vista-btn { border-left: 1px solid var(--t-borde, #EADFD2); }
	.fav-vista-btn:hover { background: var(--t-fondo-alt, #FBF8F3); color: var(--t-texto, #3A2A22); }
	.fav-vista-btn.is-activo { background: #FFF4CC; color: #B7791F; }
	.fav-vista-btn:focus { outline: none; }
	.fav-vista-btn:focus-visible { outline: 2px solid #F5B301; outline-offset: -2px; }
	.fav-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(118px, 1fr)); gap: 10px; }
	.fav-item { position: relative; display: flex; flex-direction: column; align-items: stretch; gap: 6px; min-width: 0; padding: 8px;
		border: 1px solid var(--t-borde, #EADFD2); border-radius: 10px; background: #fff; color: inherit; font: inherit; text-align: left; cursor: pointer;
		transition: border-color .15s ease, box-shadow .15s ease, transform .08s ease; }
	.fav-item:hover { border-color: #F5B301; box-shadow: 0 4px 12px rgba(58, 42, 34, .10); }
	.fav-item:active { transform: scale(.98); }
	.fav-item:focus { outline: none; }
	.fav-item:focus-visible { outline: 2px solid #F5B301; outline-offset: 2px; }
	.fav-item.en-venta { border-color: var(--t-primario-borde, #EFD2C6); background: var(--t-primario-suave, #FBEEE8); }
	.fav-cant { position: absolute; top: 6px; right: 6px; z-index: 1; padding: 1px 7px; border-radius: 10px; background: var(--t-primario, #B4432E); color: #fff; font-size: 11px; font-weight: 700; }
	.fav-img { position: relative; display: flex; align-items: center; justify-content: center; height: 74px; border-radius: 8px; background: var(--t-fondo-alt, #FBF8F3); overflow: hidden; }
	.fav-img img { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; }
	.fav-img-ph { font-size: 26px; font-weight: 700; color: #C9BBAA; text-transform: uppercase; }
	.fav-nombre { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; min-height: 2.5em; font-size: 12.5px; font-weight: 600;
		line-height: 1.25; color: var(--t-texto-fuerte, #2A1D17); text-transform: uppercase; word-break: break-word; }
	.fav-pie { display: flex; flex-direction: column; align-items: flex-start; gap: 2px; margin-top: auto; }
	.fav-precio { font-size: 15px; font-weight: 800; color: var(--t-primario, #B4432E); white-space: nowrap; }
	.fav-opciones { font-size: 11px; color: var(--t-texto-tenue, #A89A8C); white-space: nowrap; }
	.fav-ok { position: absolute; top: 0; right: 0; bottom: 0; left: 0; display: flex; align-items: center; justify-content: center; border-radius: 10px;
		background: rgba(22, 163, 74, .14); color: #16A34A; font-size: 30px; opacity: 0; pointer-events: none; }
	.fav-item.is-agregado { border-color: #16A34A; }
	.fav-item.is-agregado .fav-ok { -webkit-animation: favAgregado .65s ease; animation: favAgregado .65s ease; }
	@keyframes favAgregado { 0% { opacity: 0; transform: scale(.92); } 25% { opacity: 1; transform: none; } 100% { opacity: 0; } }
	/* Vista lista */
	.fav-grid.fav-grid--lista { display: flex; flex-direction: column; gap: 6px; }
	.fav-grid--lista .fav-item { flex-direction: row; align-items: center; gap: 10px; padding: 6px 10px 6px 6px; }
	.fav-grid--lista .fav-img { flex-shrink: 0; width: 40px; height: 40px; border-radius: 6px; }
	.fav-grid--lista .fav-img-ph { font-size: 16px; }
	.fav-grid--lista .fav-nombre { flex: 1; min-width: 0; min-height: 0; }
	.fav-grid--lista .fav-cant { position: static; order: 2; flex-shrink: 0; }
	.fav-grid--lista .fav-pie { order: 3; flex-shrink: 0; margin-top: 0; align-items: flex-end; }
	.fav-grid--lista .fav-ok { font-size: 22px; }
	/* Estados: sin favoritos, sin catálogo, familia vacía */
	.fav-estado { padding: 36px 16px; text-align: center; color: var(--t-texto-suave, #7A6A5E); }
	.fav-grid .fav-estado { grid-column: 1 / -1; }
	.fav-estado-icono { display: block; margin-bottom: 10px; font-size: 34px; color: #F5B301; }
	.fav-estado-titulo { font-size: 15px; font-weight: 700; color: var(--t-texto, #3A2A22); }
	.fav-estado-texto { margin-top: 6px; font-size: 13px; line-height: 1.4; }
	@media (max-width: 991px) { .fav-lista { max-height: 70vh; overflow-y: auto; } }
	@media (prefers-reduced-motion: reduce) {
		.fav-item { transition: none; }
		.fav-item.is-agregado .fav-ok, .fav-recargar.is-cargando .fa-refresh { -webkit-animation: none; animation: none; }
	}

	/* ===== Mesas: las que tienen pedido se destacan en azul; las libres quedan neutras ===== */
	.mesa-card { background: #fff; border-color: #CFC6B8; color: var(--t-texto, #3A2A22); }
	.mesa-card.libre:hover { border-color: #8FA88C; }
	.mesas-leyenda i.libre { background: #fff; border: 1px solid #CFC6B8; }
	.mesa-card.ocupada, .mesa-card.con-pedido { background: #2563EB; border-color: #1D4ED8; color: #fff; }
	.mesas-leyenda i.ocupada { background: #2563EB; }
	/* demora (ámbar) y crítica (rojo) siguen ganando: tienen más clases */
	.mesa-card.ocupada.demora, .mesa-card.con-pedido.demora { background: #C98A12; border-color: #A87210; }
	.mesa-card.ocupada.critica, .mesa-card.con-pedido.critica { background: #B83A2A; border-color: #96291C; }
	.mesa-card.bloqueada { background: #EDE8E1; border-color: #D9D2C9; color: #7A6A5E; }

	/* ===== Drawer de mesa ===== */
	.mesa-drawer-fondo { position: fixed; top: 0; right: 0; bottom: 0; left: 0; z-index: 1036; background: rgba(42, 29, 23, .38);
		opacity: 0; visibility: hidden; transition: opacity .2s ease, visibility 0s linear .2s; }
	.mesa-drawer-fondo.abierto { opacity: 1; visibility: visible; transition: opacity .2s ease, visibility 0s; }
	.mesa-drawer { position: fixed; top: 0; right: 0; bottom: 0; z-index: 1037; display: flex; flex-direction: column;
		width: 1180px; max-width: calc(100vw - var(--t-sidebar, 220px) - 40px); background: var(--t-fondo, #F3ECE1);
		box-shadow: -14px 0 40px rgba(42, 29, 23, .28); transform: translateX(105%); visibility: hidden;
		transition: transform .25s ease, width .2s ease, visibility 0s linear .25s; }
	.mesa-drawer.abierto { transform: none; visibility: visible; transition: transform .25s ease, width .2s ease, visibility 0s; }
	/* Mesa sin pedido: sin panel lateral, la mitad de ancho. El borde izquierdo se arrastra para ajustarlo (se recuerda en este
	   computador, por separado con y sin pedido; doble clic vuelve al ancho de siempre). Ver js.php (ancho del drawer de mesa) */
	.mesa-drawer.sin-pedido { width: 590px; }
	.mesa-drawer.redimensionando { transition: none; }
	.mesa-drawer.redimensionando, .mesa-drawer.redimensionando * { cursor: col-resize !important; -webkit-user-select: none; user-select: none; }
	.mesa-drawer-tirador { position: absolute; top: 0; bottom: 0; left: -6px; z-index: 2; width: 12px; cursor: col-resize; touch-action: none; }
	.mesa-drawer-tirador::after { content: ''; position: absolute; top: 50%; left: 4px; width: 4px; height: 56px; margin-top: -28px; border-radius: 2px;
		background: rgba(42, 29, 23, .28); opacity: 0; transition: opacity .15s; }
	.mesa-drawer:hover .mesa-drawer-tirador::after, .mesa-drawer.redimensionando .mesa-drawer-tirador::after, .mesa-drawer-tirador:focus-visible::after { opacity: 1; }
	.mesa-drawer-tirador:focus { outline: none; }
	.mesa-drawer-cab { flex: 0 0 auto; display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 18px;
		background: #fff; border-bottom: 1px solid var(--t-borde, #EADFD2); }
	.mesa-drawer-titulo { min-width: 0; }
	.mesa-drawer-titulo h3 { margin: 0; font-size: 20px; font-weight: 800; color: var(--t-texto-fuerte, #2A1D17); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
	.mesa-drawer-titulo h3 i { color: #2563EB; margin-right: 4px; }
	.mesa-drawer-titulo small { display: block; margin-top: 2px; font-size: 12px; color: var(--t-texto-suave, #7A6A5E); }
	.mesa-drawer-cerrar { flex: 0 0 auto; width: 40px; height: 40px; border: 1px solid var(--t-borde, #EADFD2); border-radius: 10px; background: #fff;
		color: var(--t-texto-suave, #7A6A5E); font-size: 26px; line-height: 1; cursor: pointer; }
	.mesa-drawer-cerrar:hover { background: var(--t-fondo-alt, #FBF8F3); color: var(--t-texto, #3A2A22); }
	.mesa-drawer-cerrar:focus { outline: none; }
	.mesa-drawer-cerrar:focus-visible { outline: 2px solid #2563EB; outline-offset: 2px; }
	.mesa-drawer-cuerpo { flex: 1 1 auto; min-height: 0; padding: 14px 16px; overflow: hidden; }
	.mesa-drawer-cuerpo > .caja-venta { height: 100%; }
	/* En el drawer: el nombre ya va en la cabecera, la X reemplaza "Volver a mesas" y el selector Pagar / Opciones no aplica */
	.mesa-drawer .caja-banner-mesa .nombre, .mesa-drawer #btnSalirMesa { display: none !important; }
	/* Panel de la mesa: ★ / Pagar / Opciones; en Opciones solo las de la mesa (las de la caja quedan en la venta directa) */
	#opcionesMesa, .mesa-drawer #panelOpciones > .caja-tarjeta:not(#opcionesMesa), .mesa-drawer #esperasBadgeSel { display: none !important; }
	.mesa-drawer #opcionesMesa { display: block !important; }
	.caja-opcion.opcion-mesa:disabled { opacity: .45; cursor: not-allowed; transform: none; background: #fff; }
	.caja-opcion[data-mesa="mover"] i { background: #E8EEFB; color: #2F5BB7; }
	.caja-opcion[data-mesa="dividir"] i { background: #F3EEE7; color: #5A4436; }
	.caja-opcion[data-mesa="garzon"] i { background: #E9F1DF; color: #46672A; }
	.caja-opcion[data-mesa="reenviar"] i { background: #FFF4DF; color: #9A6B12; }
	.caja-opcion[data-mesa="precuenta"] i { background: #EEF2F6; color: #3A4A5C; }
	.caja-opcion[data-mesa="anular"] i { background: #FBE9E6; color: #A63A2B; }
	.caja-opcion[data-mesa="anular"] b { color: #A63A2B; }
	.opciones-mesa-ayuda { margin: 10px 0 0; font-size: 12px; color: var(--t-texto-suave, #7A6A5E); }
	.garzon-actual { margin-bottom: 12px; color: var(--t-texto-suave, #7A6A5E); }

	/* ===== Opciones de la mesa > Dividir cuenta: por comensal (arrastrar), partes iguales o libre (10-11-2026) ===== */
	#modalDividir .modal-dialog { width: 94%; max-width: 1060px; }
	#modalDividir [hidden] { display: none !important; }
	.dc-modos { display: flex; gap: 4px; margin-bottom: 12px; padding: 4px; border: 1px solid var(--t-borde, #EADFD2); border-radius: 12px; background: var(--t-fondo-alt, #FBF8F3); }
	.dc-modos button { flex: 1 1 0; display: flex; align-items: center; justify-content: center; gap: 8px; height: 42px; border: 0; border-radius: 9px;
		background: transparent; color: var(--t-texto-suave, #7A6A5E); font-size: 14px; font-weight: 600; }
	.dc-modos button:hover { color: var(--t-texto, #3A2A22); }
	.dc-modos button.activo { background: #fff; color: var(--t-texto-fuerte, #2A1D17); box-shadow: 0 1px 4px rgba(58, 42, 34, .14); }
	.dc-resumen { margin-bottom: 14px; font-size: 13px; color: var(--t-texto-suave, #7A6A5E); }
	.dc-resumen b { color: var(--t-texto-fuerte, #2A1D17); }
	.dc-vacio { padding: 30px 12px; border: 1px dashed var(--t-borde, #EADFD2); border-radius: 12px; color: var(--t-texto-suave, #7A6A5E); text-align: center; }
	.dc-vacio > i { display: block; margin-bottom: 8px; font-size: 30px; color: var(--t-texto-tenue, #A89A8C); }
	.dc-vacio .btn { margin-top: 14px; }
	.dc-ayuda { margin: 0 0 12px; font-size: 13px; color: var(--t-texto-suave, #7A6A5E); }
	.dc-cols { display: grid; grid-template-columns: 330px minmax(0, 1fr); gap: 16px; align-items: start; }
	.dc-sin { max-height: 60vh; padding: 10px; overflow-y: auto; border: 2px dashed var(--t-borde, #EADFD2); border-radius: 12px; background: var(--t-fondo-alt, #FBF8F3); }
	.dc-titulo-col { margin: 2px 4px 10px; font-size: 12px; font-weight: 700; letter-spacing: .4px; text-transform: uppercase; color: var(--t-texto-suave, #7A6A5E); }
	.dc-prod { position: relative; margin-bottom: 8px; padding: 10px 12px 10px 28px; border: 1px solid var(--t-borde, #EADFD2); border-radius: 10px; background: #fff; cursor: grab; }
	.dc-prod:active, .dc-item:active { cursor: grabbing; }
	.dc-prod .dc-asa { position: absolute; top: 13px; left: 12px; color: var(--t-texto-tenue, #A89A8C); }
	.dc-prod b { display: block; font-size: 13px; color: var(--t-texto-fuerte, #2A1D17); }
	.dc-prod small { display: block; font-size: 12px; color: var(--t-texto-suave, #7A6A5E); }
	.dc-chips { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 8px; }
	.dc-chip { padding: 2px 9px; border: 1px solid var(--t-borde, #EADFD2); border-radius: 12px; background: #fff; color: var(--t-texto, #3A2A22); font-size: 11px; font-weight: 600; }
	.dc-chip:hover { border-color: var(--t-primario, #15803D); color: var(--t-primario, #15803D); }
	.dc-chip.dividir { color: #2F5BB7; }
	.dc-repartir { margin-top: 8px; padding: 8px 10px; border-radius: 8px; background: #F3F7FE; font-size: 12px; }
	.dc-repartir label { display: inline-flex; align-items: center; gap: 4px; margin: 0 10px 6px 0; font-weight: 500; }
	.dc-sin-listo { padding: 16px; color: #276227; text-align: center; }
	.dc-arrastrando { opacity: .45; }
	.dc-zona.sobre { outline: 2px dashed var(--t-primario, #15803D); outline-offset: 2px; background: var(--t-primario-suave, #f1f7ec); }
	.dc-nuevo { display: flex; gap: 8px; margin-bottom: 12px; }
	.dc-nuevo input { flex: 1; height: 40px; }
	.dc-com-lista { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 12px; max-height: 56vh; padding: 3px; overflow-y: auto; }
	.dc-com { display: flex; flex-direction: column; min-height: 160px; padding: 12px; border: 1px solid var(--t-borde, #EADFD2); border-radius: 12px; background: #fff; }
	.dc-com-cab { display: flex; align-items: center; gap: 6px; margin-bottom: 8px; }
	.dc-com-nombre { flex: 1; min-width: 0; height: 30px; padding: 2px 6px; border: 1px solid transparent; border-radius: 6px; background: transparent;
		font-size: 15px; font-weight: 700; color: var(--t-texto-fuerte, #2A1D17); }
	.dc-com-nombre:hover, .dc-com-nombre:focus { border-color: var(--t-borde, #EADFD2); background: #fff; outline: none; }
	.dc-com-quitar { border: 0; background: none; color: var(--t-texto-tenue, #A89A8C); }
	.dc-com-quitar:hover { color: #A63A2B; }
	.dc-com-items { flex: 1; min-height: 44px; }
	.dc-com-vacio { padding: 14px 8px; border: 1px dashed var(--t-borde, #EADFD2); border-radius: 8px; color: var(--t-texto-tenue, #A89A8C); font-size: 12px; text-align: center; }
	.dc-item { display: flex; align-items: center; gap: 6px; padding: 5px 0; border-bottom: 1px solid var(--t-borde-suave, #F0E8DD); font-size: 13px; cursor: grab; }
	.dc-item > span:first-child { flex: 1; min-width: 0; }
	.dc-item .monto { font-weight: 600; white-space: nowrap; }
	.dc-item button { padding: 0 2px; border: 0; background: none; color: var(--t-texto-tenue, #A89A8C); font-size: 16px; line-height: 1; }
	.dc-com-pie { display: flex; align-items: center; gap: 6px; margin-top: 10px; }
	.dc-com-pie .total { margin-right: auto; font-size: 18px; font-weight: 800; color: var(--t-texto-fuerte, #2A1D17); }
	.dc-com.pagado { min-height: 0; border-color: #CFE6CF; background: #F4FAF4; }
	.dc-com.pagado .dc-com-items { min-height: 0; color: #276227; font-size: 13px; }
	.dc-iguales-n { display: flex; align-items: center; gap: 8px; margin-bottom: 14px; }
	.dc-iguales-n label { margin: 0 8px 0 0; }
	.dc-iguales-n input { width: 74px; height: 42px; font-size: 18px; font-weight: 700; text-align: center; }
	.dc-iguales-n .btn { width: 42px; height: 42px; }
	.dc-partes { width: 100%; border: 1px solid var(--t-borde, #EADFD2); border-radius: 12px; border-collapse: separate; border-spacing: 0; overflow: hidden; }
	.dc-partes th { padding: 9px 14px; background: var(--t-fondo-alt, #FBF8F3); color: var(--t-texto-suave, #7A6A5E); font-size: 12px; font-weight: 600; }
	.dc-partes td { padding: 10px 14px; border-top: 1px solid var(--t-borde-suave, #F0E8DD); vertical-align: middle; }
	.dc-partes .num { text-align: right; white-space: nowrap; }
	.dc-partes .acc { width: 1%; text-align: right; white-space: nowrap; }
	.dc-partes tr.pagada td { color: #276227; background: #F4FAF4; }
	.dc-partes tfoot td { font-weight: 700; font-size: 15px; }
	.dc-nota { margin: 10px 0 0; font-size: 12px; color: var(--t-texto-suave, #7A6A5E); }
	.dc-cobro-cab { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; margin-bottom: 10px; }
	.dc-cobro-cab b { font-size: 18px; color: var(--t-texto-fuerte, #2A1D17); }
	.dc-cobro-cab span { font-size: 24px; font-weight: 800; color: var(--t-primario, #15803D); }
	.dc-cobro-detalle { max-height: 26vh; margin-bottom: 14px; padding: 8px 12px; overflow-y: auto; border-radius: 10px; background: var(--t-fondo-alt, #FBF8F3); font-size: 13px; }
	.dc-cobro-detalle div { display: flex; justify-content: space-between; gap: 10px; padding: 3px 0; }
	.dc-cliente { display: flex; align-items: center; gap: 8px; }
	.dc-cliente .form-control { flex: 1; }
	.dc-factura { margin-top: 4px; padding: 10px 12px; border: 1px solid #F2D49B; border-radius: 10px; background: #FFF7E6; }
	.dc-acciones { display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px; }
	@media (max-width: 767px) { .dc-cols { grid-template-columns: 1fr; } .dc-modos button { font-size: 12px; } }
	.mesa-drawer .caja-banner-mesa { background: #2563EB; }
	body.mesa-drawer-abierto { overflow: hidden; }
	body.mesa-drawer-abierto #barraMovil { display: none !important; }
	@media (max-width: 991px) {
		.mesa-drawer, .mesa-drawer.sin-pedido { width: 100%; max-width: 100%; }
		.mesa-drawer-tirador { display: none; }
		.mesa-drawer-cuerpo { overflow-y: auto; -webkit-overflow-scrolling: touch; }
		.mesa-drawer-cuerpo > .caja-venta { height: auto; }
	}
	@media (prefers-reduced-motion: reduce) { .mesa-drawer, .mesa-drawer-fondo { transition: none; } }

	/* ===== Opciones > Documentos emitidos: lista a la izquierda, vista del documento a la derecha ===== */
	#modalDocumentos .modal-dialog { width: 94%; max-width: 1180px; }
	.doc-filtros { display: flex; flex-wrap: wrap; align-items: center; gap: 10px 14px; margin-bottom: 12px; }
	.doc-filtros .doc-fecha { display: flex; align-items: center; gap: 8px; margin: 0; font-weight: 600; color: var(--t-texto-suave, #7A6A5E); }
	.doc-filtros .doc-fecha input { width: 170px; height: 40px; }
	.doc-filtros .doc-buscar { flex: 1 1 260px; }
	.doc-filtros .doc-buscar input { height: 40px; }
	.doc-cuerpo { display: flex; gap: 14px; height: 64vh; min-height: 360px; }
	.doc-lista { flex: 1 1 auto; min-width: 0; overflow: auto; border: 1px solid var(--t-borde, #EADFD2); border-radius: 12px; }
	.doc-lista .table { margin: 0; }
	.doc-lista thead th { position: sticky; top: 0; z-index: 1; }
	#tablaDocumentos tbody tr { cursor: pointer; }
	#tablaDocumentos tbody tr.activo > td { background: var(--t-primario-suave, #FBEEE8) !important; }
	#tablaDocumentos tbody tr.anulada > td { color: var(--t-texto-tenue, #A89A8C); }
	.doc-vista { flex: 0 0 360px; display: flex; flex-direction: column; gap: 10px; min-height: 0; }
	.doc-vista-vacia { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; border: 1px dashed var(--t-borde, #EADFD2);
		border-radius: 12px; color: var(--t-texto-tenue, #A89A8C); text-align: center; padding: 20px; }
	.doc-vista-vacia i { font-size: 34px; }
	#docFrame { flex: 1; min-height: 0; width: 100%; border: 1px solid var(--t-borde, #EADFD2); border-radius: 12px; background: #fff; }
	.doc-vista-acciones { display: flex; gap: 8px; }
	.doc-vista-acciones .btn { flex: 1; }
	@media (max-width: 991px) {
		.doc-cuerpo { flex-direction: column; height: auto; }
		.doc-lista { max-height: 45vh; }
		.doc-vista { flex-basis: auto; }
		#docFrame { height: 60vh; flex: none; }
	}

	/* ===== Ventas en espera (Opciones) y aviso al salir de la Caja con una venta en curso (10-11-2026) ===== */
	.caja-opcion { position: relative; }
	.caja-opcion[data-abrir="espera"] i { background: #E8EEFB; color: #2F5BB7; }
	.caja-opcion[data-abrir="esperas"] i { background: #FFF4DF; color: #9A6B12; }
	.caja-opcion-badge { position: absolute; top: 10px; right: 10px; min-width: 24px; height: 24px; padding: 0 7px; border-radius: 12px;
		background: var(--t-primario, #B4432E); color: #fff; font-size: 12px; font-weight: 700; line-height: 24px; }
	.caja-panel-selector .caja-espera-badge { display: inline-block; min-width: 18px; height: 18px; margin-left: 4px; padding: 0 5px; border-radius: 9px;
		background: #F5B301; color: #3A2A22; font-size: 11px; font-weight: 700; line-height: 18px; vertical-align: 1px; }
	.caja-panel-selector .caja-espera-badge[hidden], .caja-opcion-badge[hidden], #opcionesEspera[hidden], #esperasVistaLista[hidden], #esperasVistaAnular[hidden],
	#esperasAviso[hidden], #salidaDirecta[hidden], #salidaMesa[hidden], #salidaNombreGrupo[hidden] { display: none !important; }
	#modalEsperas .modal-dialog { width: 94%; max-width: 860px; }
	.esp-aviso { display: flex; align-items: center; gap: 8px; margin-bottom: 12px; padding: 10px 12px; border-radius: 10px; background: #FFF7E6;
		border: 1px solid #F2D49B; color: #7A5410; font-size: 13px; }
	.esp-lista { display: flex; flex-direction: column; gap: 10px; max-height: 62vh; overflow-y: auto; }
	.esp-item { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 4px 16px; padding: 14px 16px; border: 1px solid var(--t-borde, #EADFD2);
		border-radius: 12px; background: #fff; }
	.esp-cabeza { display: flex; flex-wrap: wrap; align-items: center; gap: 6px 10px; min-width: 0; }
	.esp-nombre { font-size: 16px; font-weight: 700; color: var(--t-texto-fuerte, #2A1D17); overflow-wrap: anywhere; }
	.esp-origen { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600; background: #F3EEE7; color: #5A4436; }
	.esp-origen.menu { background: #E8EEFB; color: #2F5BB7; }
	.esp-origen.recarga { background: #FFF4DF; color: #8A5D0B; }
	.esp-total { font-size: 18px; font-weight: 700; color: var(--t-texto-fuerte, #2A1D17); text-align: right; white-space: nowrap; }
	.esp-productos { grid-column: 1; font-size: 13px; color: var(--t-texto, #3A2A22); overflow-wrap: anywhere; }
	.esp-meta { grid-column: 1; font-size: 12px; color: var(--t-texto-suave, #7A6A5E); }
	.esp-acciones { grid-column: 2; grid-row: 2 / span 2; display: flex; align-items: flex-end; justify-content: flex-end; gap: 8px; }
	.esp-vacio { padding: 36px 12px; text-align: center; color: var(--t-texto-tenue, #A89A8C); }
	.esp-vacio i { display: block; margin-bottom: 8px; font-size: 34px; }
	.esp-anular-detalle { margin-bottom: 12px; padding: 12px 14px; border-radius: 10px; background: var(--t-fondo-alt, #FBF8F3); }
	.mesa-drawer #opcionesEspera { display: none !important; }   /* en una mesa no hay venta en espera */
	.salida-bloque { display: flex; gap: 12px; margin-bottom: 12px; padding: 12px 14px; border: 1px solid; border-radius: 12px; }
	.salida-bloque > i { flex: 0 0 auto; margin-top: 2px; font-size: 22px; }
	.salida-bloque.directa { background: #F3F7FE; border-color: #C9D8F5; }
	.salida-bloque.directa > i { color: #2F5BB7; }
	.salida-bloque.mesa { background: #FFF7E6; border-color: #F2D49B; }
	.salida-bloque.mesa > i { color: #9A6B12; }
	.salida-bloque p { margin: 0 0 6px; }
	.salida-bloque .form-group { margin: 8px 0 0; }
	@media (max-width: 767px) {
		.esp-item { grid-template-columns: 1fr; }
		.esp-total { text-align: left; }
		.esp-acciones { grid-column: 1; grid-row: auto; justify-content: flex-start; }
	}
	/* Producto a granel (fraccionable + "Pedir cantidad en venta"), 12-11-2026: ventana Por valor | Por peso */
	.pes-modo { display: flex; gap: 0; margin-bottom: 16px; border: 1px solid var(--t-borde, #e6eaf0); border-radius: 10px; overflow: hidden; }
	.pes-modo button { flex: 1; border: 0; background: #fff; padding: 12px 10px; font-size: 16px; font-weight: 600; color: var(--t-texto-suave, #6b7a90); }
	.pes-modo button + button { border-left: 1px solid var(--t-borde, #e6eaf0); }
	.pes-modo button.activo { background: var(--t-primario, #558B2F); color: #fff; }
	.pes-modo button i { margin-right: 6px; }
	.pes-precio { text-align: center; color: var(--t-texto-suave, #6b7a90); margin-bottom: 10px; font-size: 15px; }
	.pes-precio b { color: var(--t-texto-fuerte, #2b3a55); }
	.pes-entrada { position: relative; }
	.pes-entrada label { display: block; font-size: 12px; text-transform: uppercase; letter-spacing: .3px; color: var(--t-texto-suave, #6b7a90); margin-bottom: 6px; }
	.pes-entrada .pes-input { width: 100%; height: 76px; font-size: 40px; font-weight: 700; text-align: right; padding: 6px 70px 6px 54px;
		border: 2px solid var(--t-borde, #e6eaf0); border-radius: 12px; color: var(--t-texto-fuerte, #2b3a55); background: #fff; }
	.pes-entrada .pes-input:focus { outline: 0; border-color: var(--t-primario, #558B2F); box-shadow: 0 0 0 3px rgba(85, 139, 47, .15); }
	.pes-entrada.pes-error .pes-input { border-color: #c9302c; }
	.pes-entrada.sin-unidad .pes-input { padding-right: 20px; }
	.pes-entrada .pes-pre, .pes-entrada .pes-suf { position: absolute; bottom: 0; height: 76px; line-height: 76px; font-size: 24px; font-weight: 600; color: var(--t-texto-tenue, #8a94a6); }
	.pes-entrada .pes-pre { left: 20px; }
	.pes-entrada .pes-suf { right: 18px; }
	.pes-msg { min-height: 20px; margin: 6px 2px 0; font-size: 13px; color: #c9302c; }
	.pes-resultado { margin-top: 12px; padding: 14px 16px; border-radius: 12px; background: var(--t-fondo-alt, #f7f9fa); display: flex; justify-content: space-between; align-items: baseline; gap: 10px; flex-wrap: wrap; }
	.pes-resultado span { font-size: 14px; color: var(--t-texto-suave, #6b7a90); }
	.pes-resultado b { font-size: 30px; color: var(--t-texto-fuerte, #2b3a55); }
	.pes-nota { margin: 8px 2px 0; font-size: 12px; color: var(--t-texto-tenue, #8a94a6); min-height: 16px; }
	#btnConfirmarPesable { min-width: 140px; font-weight: 700; }
</style>

<div class="row caja">
	<div class="col-xs-12">

		<!-- Sin caja abierta -->
		<div id="panelApertura" class="panel panel-success lobidisable caja-apertura" style="display:none;">
			<div class="panel-heading">
				<div class="panel-title"><h4><i class="pe-7s-unlock"></i>Abrir caja</h4></div>
			</div>
			<div class="panel-body">
				<i class="pe-7s-calculator icono"></i>
				<p class="text-center text-muted" id="textoApertura">No tienes una caja abierta.<br>Ingresa el fondo inicial (sencillo) para comenzar a vender.</p>
				<!-- Herramientas > Control de cajas: la caja define la lista de precios y la bodega del stock -->
				<!-- 12-11-2026: primero la empresa y la sucursal; después las cajas de esa sucursal que no están en uso -->
				<div class="form-group" id="grupoEmpresaApertura" style="display:none">
					<label for="cboEmpresaApertura">Empresa</label>
					<select class="form-control" id="cboEmpresaApertura"></select>
				</div>
				<div class="form-group" id="grupoSucursalApertura" style="display:none">
					<label for="cboSucursalApertura">Sucursal</label>
					<select class="form-control" id="cboSucursalApertura"></select>
				</div>
				<div class="form-group" id="grupoControlCaja" style="display:none">
					<label for="cboControlCaja">Caja</label>
					<select class="form-control input-lg" id="cboControlCaja"></select>
					<p class="help-block caja-control-info" id="infoControlCaja"></p>
				</div>
				<div class="form-group">
					<label for="fondoInicial">Fondo inicial</label>
					<div class="input-group">
						<span class="input-group-addon">$</span>
						<input type="number" min="0" step="1" class="form-control input-lg" id="fondoInicial" value="0">
					</div>
					<p class="help-block" id="ayudaFondo" style="display:none">Sencillo que quedó en el último cierre.</p>
				</div>
				<button id="btnAbrirCaja" class="btn btn-success btn-lg btn-block"><i class="fa fa-unlock"></i> Abrir caja</button>
			</div>
		</div>

		<!-- Caja abierta -->
		<div id="panelCaja" style="display:none;">
			<div class="caja-barra-movil" id="barraMovil">
				<span><i class="pe-7s-cart"></i> Ver venta (<span id="barraItems">0</span>)</span>
				<span id="barraTotal">$0</span>
			</div>
			<div class="caja-barra">
				<ul class="nav nav-tabs caja-tabs">
					<li class="active"><a href="#tabVenta" data-toggle="tab" id="lnkVenta"><i class="pe-7s-cart"></i> Venta directa</a></li>
					<li id="navMesas" style="display:none"><a href="#tabMesas" data-toggle="tab" id="lnkMesas"><i class="fa fa-th"></i> <span id="lnkMesasTexto">Mesas y recintos</span> <span class="badge" id="mesasOcupadas" style="display:none">0</span></a></li>
					<li class="caja-tab-opcion"><a href="#tabVentas" data-toggle="tab" id="lnkVentas"><i class="pe-7s-note2"></i> Ventas</a></li>
					<li class="caja-tab-opcion"><a href="#tabMovimientos" data-toggle="tab" id="lnkMovimientos"><i class="pe-7s-cash"></i> Ingresos / Retiros</a></li>
					<li class="caja-tab-opcion"><a href="#tabCreditos" data-toggle="tab" id="lnkCreditos"><i class="pe-7s-credit"></i> Créditos</a></li>
					<li class="caja-tab-opcion"><a href="#tabCierre" data-toggle="tab" id="lnkCierre"><i class="pe-7s-lock"></i> Cierre</a></li>
				</ul>
				<div class="caja-estado">
					<button type="button" class="btn btn-danger btn-sm caja-btn-cierre" id="btnIrCierre" title="Contar el dinero y cerrar la caja"><i class="fa fa-lock"></i> Cerrar caja</button>
					<div><small>Caja</small><b id="estCaja">-</b></div>
					<div><small>Apertura</small><b id="estApertura">-</b></div>
				</div>
			</div>

			<div class="tab-content caja-contenido">
				<!-- ===================== VENTA ===================== -->
				<div class="tab-pane fade active in" id="tabVenta">
					<div class="caja-venta">
						<div class="caja-venta-principal">
							<div class="caja-tarjeta caja-busqueda">
								<div class="caja-buscar">
									<i class="fa fa-search"></i>
									<input type="text" id="buscarProducto" class="form-control" placeholder="Buscar por nombre o código · cantidad*código para varios (ej: 3*5)" autocomplete="off">
									<div class="caja-resultados" id="resultados"></div>
								</div>
								<!-- Solo con una mesa sin pedido (sin panel lateral): abre y cierra el panel Favoritos -->
								<button type="button" class="btn btn-default btn-fav-mesa" id="btnFavMesa" title="Favoritos" aria-label="Favoritos" aria-pressed="false" aria-controls="panelFavoritos"><i class="fa fa-star"></i></button>
								<button type="button" class="btn btn-success btn-catalogo btn-icono" id="btnCatalogo" title="Productos por familia (F2)" aria-label="Productos"><i class="fa fa-th-large"></i></button>
								<select id="cboLista" class="form-control" title="Lista de precios"></select>
							</div>

							<div class="caja-tarjeta caja-carro">
								<div class="caja-banner-mesa" id="bannerMesa" style="display:none">
									<span class="nombre"><i class="fa fa-th"></i> <b id="bannerMesaNum"></b></span>
									<span class="tiempo"><i class="fa fa-clock-o"></i> <span id="bannerMesaTiempo">Sin enviar</span></span>
									<span class="caja-cobro-parcial" id="avisoCobroParcial" style="display:none"><i class="fa fa-scissors"></i> <span id="avisoCobroParcialTexto">Cobrando una parte</span> <a href="#" id="btnCancelarParcial">Cancelar</a></span>
									<!-- Precuenta (abajo, en Pagar) y las opciones de la mesa (panel Opciones) ya no van en esta franja -->
									<button type="button" class="btn btn-default btn-sm" id="btnSalirMesa"><i class="fa fa-arrow-left"></i> Volver a mesas</button>
								</div>
								<div class="caja-linea-cab">
									<span class="info">Producto <span class="badge" id="cantItems">0</span></span>
									<span class="precio">Precio</span>
									<span class="cantidad">Cantidad</span>
									<span class="subtotal">Total</span>
									<span class="quitar"></span>
								</div>
								<div class="caja-lineas" id="lineas"></div>
								<div class="caja-carro-pie">
									<select id="cboDocumento" class="form-control" title="Documento"></select>
									<!-- Mesa sin pedido: no hay panel lateral; el envío va aquí, junto al total -->
									<button type="button" class="btn btn-primary btn-lg" id="btnEnviarMesaBarra" disabled><i class="fa fa-paper-plane"></i> Enviar a mesa</button>
									<div class="total">Total <b id="lblTotal">$0</b></div>
								</div>
							</div>
						</div>

						<div class="caja-venta-lateral">
							<!-- Paneles laterales: los botones muestran uno y ocultan los otros (★ Favoritos / Pagar / Opciones), ver js.php -->
							<div class="caja-panel-selector" role="tablist" aria-label="Panel lateral de la venta">
								<button type="button" class="sel-fav" id="btnFavoritos" title="Favoritos" aria-label="Favoritos" aria-pressed="false" aria-controls="panelFavoritos"><i class="fa fa-star"></i></button>
								<button type="button" class="active" data-panel="pago" role="tab" aria-selected="true" aria-controls="panelPago"><i class="fa fa-money"></i> Pagar</button>
								<button type="button" data-panel="opciones" role="tab" aria-selected="false" aria-controls="panelOpciones"><i class="fa fa-sliders"></i> Opciones<span class="caja-espera-badge" id="esperasBadgeSel" title="Ventas en espera" hidden></span></button>
							</div>

							<!-- Panel Pagar: el cobro de siempre (cliente, descuento, observación, totales y Cobrar) -->
							<div class="caja-panel activo" id="panelPago" data-panel="pago" role="tabpanel">
							<div class="caja-tarjeta">
								<div class="caja-tarjeta-titulo">Cliente</div>
								<div class="caja-cliente">
									<input type="text" id="txtCliente" class="form-control" readonly placeholder="Público general">
									<button class="btn btn-default" id="btnBuscarCliente" title="Buscar o crear cliente"><i class="fa fa-search"></i></button>
									<button class="btn btn-default" id="btnQuitarCliente" title="Quitar cliente"><i class="fa fa-times"></i></button>
								</div>
							</div>

							<div class="caja-tarjeta">
								<div class="form-group">
									<label for="txtDescuento">Descuento</label>
									<div class="input-group">
										<input type="number" min="0" step="1" id="txtDescuento" class="form-control" value="0">
										<span class="input-group-btn">
											<button class="btn btn-default active" data-tipo-desc="monto" type="button">$</button>
											<button class="btn btn-default" data-tipo-desc="porc" type="button">%</button>
										</span>
									</div>
								</div>
								<div class="form-group">
									<label for="txtObservacion">Observación</label>
									<input type="text" id="txtObservacion" maxlength="150" class="form-control" placeholder="Opcional">
								</div>
							</div>

							<div class="caja-tarjeta caja-resumen">
								<div class="fila"><span>Subtotal</span><span id="lblSubtotal">$0</span></div>
								<div class="fila descuento" id="filaDescuento" style="display:none"><span>Descuento</span><span id="lblDescuento">$0</span></div>
								<div class="fila con-descuento" id="filaConDescuento" style="display:none"><span>Total con descuento</span><span id="lblConDescuento">$0</span></div>
								<div class="fila"><span>Neto</span><span id="lblNeto">$0</span></div>
								<div class="fila"><span>IVA (19%)</span><span id="lblIva">$0</span></div>
								<div class="fila" id="filaExento" style="display:none"><span>Exento</span><span id="lblExento">$0</span></div>
								<div class="caja-acciones">
									<button class="btn btn-default btn-lg" id="btnLimpiar" title="Vaciar venta"><i class="fa fa-trash"></i></button>
									<button class="btn btn-default btn-lg" id="btnPrecuenta" style="display:none" title="Precuenta: imprime el detalle para que el cliente lo revise antes de pagar" aria-label="Precuenta"><i class="fa fa-print"></i></button>
									<button class="btn btn-primary btn-lg" id="btnEnviarMesa" style="display:none" disabled><i class="fa fa-paper-plane"></i> Enviar a mesa</button>
									<button class="btn btn-success btn-lg" id="btnCobrar" disabled><i class="fa fa-money"></i> Cobrar <span id="lblCobrar"></span></button>
								</div>
							</div>
							</div>

							<!-- Panel Opciones: lo que antes eran pestañas arriba (Ingresos / Retiros, Créditos y Cierre) -->
							<div class="caja-panel" id="panelOpciones" data-panel="opciones" role="tabpanel">
								<!-- Opciones de la mesa (solo en el panel de la mesa): lo que antes era el menú "Opciones" de la franja azul -->
								<div class="caja-tarjeta" id="opcionesMesa">
									<div class="caja-tarjeta-titulo">Mesa</div>
									<div class="caja-opciones">
										<button type="button" class="caja-opcion opcion-mesa" id="btnMoverMesa" data-mesa="mover"><i class="fa fa-exchange"></i><b>Cambiar mesa</b><small>Pasar todo o parte a otra mesa</small></button>
										<button type="button" class="caja-opcion opcion-mesa" id="btnCobrarParte" data-mesa="dividir"><i class="fa fa-scissors"></i><b>Dividir cuenta</b><small>Cobrar solo una parte</small></button>
										<button type="button" class="caja-opcion opcion-mesa" id="btnCambiarGarzon" data-mesa="garzon"><i class="fa fa-user"></i><b>Cambiar garzón</b><small id="opcionGarzonActual">Quién atiende la mesa</small></button>
										<button type="button" class="caja-opcion opcion-mesa" id="btnReenviarTodo" data-mesa="reenviar"><i class="fa fa-repeat"></i><b>Reenviar a cocina</b><small>Volver a imprimir la comanda</small></button>
										<button type="button" class="caja-opcion opcion-mesa" id="btnPrecuentaOpcion" data-mesa="precuenta"><i class="fa fa-print"></i><b>Precuenta</b><small>Detalle para el cliente</small></button>
										<button type="button" class="caja-opcion opcion-mesa" id="btnAnularPedido" data-mesa="anular"><i class="fa fa-ban"></i><b>Anular pedido</b><small>Anula todo y libera la mesa</small></button>
									</div>
									<p class="opciones-mesa-ayuda" id="opcionesMesaAyuda">Envía el pedido a la mesa para usar estas opciones.</p>
								</div>
								<!-- Ventas en espera (10-11-2026): solo venta directa; lógica en js.php (ventas en espera) -->
								<div class="caja-tarjeta" id="opcionesEspera" hidden>
									<div class="caja-tarjeta-titulo">Venta</div>
									<div class="caja-opciones">
										<button type="button" class="caja-opcion" data-abrir="espera"><i class="fa fa-pause"></i><b>Poner en espera</b><small>Guarda la venta con un nombre</small></button>
										<button type="button" class="caja-opcion" data-abrir="esperas"><i class="fa fa-clock-o"></i><b>Ventas en espera</b><small>Cargar o anular</small><span class="caja-opcion-badge" id="esperasBadge" hidden></span></button>
									</div>
								</div>
								<div class="caja-tarjeta">
									<div class="caja-tarjeta-titulo">Caja</div>
									<div class="caja-opciones">
										<button type="button" class="caja-opcion" data-abrir="ingreso"><i class="fa fa-arrow-down"></i><b>Ingreso</b><small>Dinero que entra a la caja</small></button>
										<button type="button" class="caja-opcion" data-abrir="retiro"><i class="fa fa-arrow-up"></i><b>Retiro</b><small>Retiros y gastos</small></button>
										<button type="button" class="caja-opcion" data-abrir="creditos"><i class="pe-7s-credit"></i><b>Créditos</b><small>Ventas a crédito y abonos</small></button>
										<button type="button" class="caja-opcion" data-abrir="cierre"><i class="pe-7s-lock"></i><b>Cierre</b><small>Contar y cerrar la caja</small></button>
										<button type="button" class="caja-opcion" data-abrir="documentos"><i class="fa fa-file-text-o"></i><b>Documentos</b><small>Ver y reimprimir boletas y facturas</small></button>
										<button type="button" class="caja-opcion" data-abrir="ventas"><i class="pe-7s-note2"></i><b>Ventas</b><small>Anular, devolver o reenviar al SII</small></button>
									</div>
								</div>
							</div>

							<!-- Panel Favoritos: se abre con el botón ★ del selector (a la izquierda de Pagar) y sale desde la derecha.
							     Los productos marcados «Favorito En Caja» en la Carta, con el precio de la lista elegida. Un clic agrega
							     igual que el buscador; sin «Fijar» vuelve a Pagar. Lógica en js.php (panel Favoritos). -->
							<div class="caja-panel" id="panelFavoritos" data-panel="favoritos" role="region" aria-labelledby="favTitulo">
								<div class="caja-tarjeta caja-favoritos">
									<div class="fav-head">
										<div class="fav-head-text">
											<h4 id="favTitulo" class="fav-title"><i class="fa fa-star" aria-hidden="true"></i> Favoritos</h4>
											<div id="favResumen" class="fav-resumen" aria-live="polite"></div>
										</div>
										<div class="fav-head-acciones">
											<label class="caja-switch fav-fijar" title="Al agregar un producto se queda en Favoritos en vez de volver a Pagar">
												<span class="caja-switch-texto">Fijar</span>
												<input type="checkbox" id="favFijar" role="switch">
												<span class="caja-switch-pista" aria-hidden="true"></span>
											</label>
											<button type="button" id="btnFavRecargar" class="fav-recargar" title="Actualizar favoritos y precios" aria-label="Actualizar favoritos"><i class="fa fa-refresh" aria-hidden="true"></i></button>
										</div>
									</div>
									<div id="favToolbar" class="fav-toolbar" hidden>
										<select id="favFamilia" class="form-control fav-familia" aria-label="Filtrar favoritos por familia"><option value="0">Todas las familias</option></select>
										<div class="fav-vista" role="group" aria-label="Ver favoritos como">
											<button type="button" class="fav-vista-btn" data-fav-vista="iconos" title="Ver como íconos"><i class="fa fa-th-large" aria-hidden="true"></i></button>
											<button type="button" class="fav-vista-btn" data-fav-vista="lista" title="Ver como lista"><i class="fa fa-list" aria-hidden="true"></i></button>
										</div>
									</div>
									<div id="favLista" class="fav-lista"></div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- ===================== MESAS ===================== -->
				<div class="tab-pane fade" id="tabMesas">
					<div class="caja-tarjeta caja-mesas">
						<div class="mesas-barra">
							<div class="mesas-sectores" id="mesasSectores"></div>
							<div class="mesas-herramientas">
								<!-- 14-11-2026: recargar al tiro (pedidos que llegan de la app de garzones u otra caja) -->
								<button type="button" class="btn btn-default btn-sm" id="btnActualizarMesas" title="Volver a cargar las mesas"><i class="fa fa-refresh"></i> Actualizar</button>
								<button type="button" class="btn btn-default btn-sm" id="btnOrganizarMesas"><i class="fa fa-arrows"></i> Organizar mesas</button>
								<button type="button" class="btn btn-default btn-sm" id="btnCancelarPlano" style="display:none">Cancelar</button>
								<button type="button" class="btn btn-success btn-sm" id="btnGuardarPlano" style="display:none"><i class="fa fa-check"></i> Guardar plano</button>
							</div>
						</div>
						<div class="mesas-leyenda">
							<span class="leyenda-libre"><i class="libre"></i> Libre</span>
							<span><i class="ocupada"></i> Con pedido</span>
							<span><i class="demora"></i> Más de 1 hora</span>
							<span><i class="critica"></i> Más de 2 horas</span>
							<span><i class="bloqueada"></i> Bloqueada</span>
							<span class="text-muted" id="mesasAyuda">Toca una mesa libre para tomar el pedido, o una con pedido para agregar productos, pedir la precuenta o cobrar.</span>
						</div>
						<div id="mesasGrilla"></div>
					</div>
				</div>

				<div class="tab-pane fade" id="tabVentas">
					<div class="panel panel-success lobidisable">
						<div class="panel-heading">
							<div class="panel-title"><h4><i class="pe-7s-note2"></i>Ventas de la caja<small class="caja-tab-ayuda">Reimprime, reenvía al SII, devuelve o anula las ventas de este turno</small></h4></div>
							<button class="btn btn-default btn-sm caja-btn-titulo" id="btnDevolucionBuscar" title="Devolver productos de una venta de cualquier caja">
								<i class="fa fa-undo"></i> Devolución
							</button>
						</div>
						<div class="panel-body table-responsive">
							<table class="table table-bordered table-hover" id="tablaVentas">
								<thead>
									<tr><th>Hora</th><th>Documento</th><th>Folio</th><th>Cliente</th><th>Pago</th><th class="caja-num">Total</th><th class="text-center">Acciones</th></tr>
								</thead>
								<tbody></tbody>
							</table>
						</div>
					</div>
				</div>

				<!-- ===================== CRÉDITOS (pago de créditos de clientes) ===================== -->
				<div class="tab-pane fade" id="tabCreditos">
					<div class="row">
						<div class="col-md-4">
							<div class="panel panel-success lobidisable">
								<div class="panel-heading">
									<div class="panel-title"><h4><i class="pe-7s-users"></i>Clientes con deuda<small class="caja-tab-ayuda">Elige el cliente que viene a pagar</small></h4></div>
								</div>
								<div class="panel-body">
									<input type="text" class="form-control" id="credBuscar" placeholder="Buscar cliente por nombre o RUT" autocomplete="off">
									<div class="list-group cred-clientes" id="credClientes"></div>
								</div>
							</div>
						</div>
						<div class="col-md-8">
							<div class="panel panel-success lobidisable">
								<div class="panel-heading">
									<div class="panel-title"><h4><i class="pe-7s-wallet"></i><span id="credTitulo">Ventas a crédito</span><small class="caja-tab-ayuda" id="credSubtitulo">Elige un cliente de la lista</small></h4></div>
								</div>
								<div class="panel-body" id="credDetalle">
									<p class="text-muted text-center" style="margin:30px 0">Elige un cliente para ver sus ventas a crédito pendientes.</p>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- ===================== MOVIMIENTOS ===================== -->
				<div class="tab-pane fade" id="tabMovimientos">
					<div class="row">
						<div class="col-md-4">
							<div class="panel panel-success lobidisable">
								<div class="panel-heading">
									<div class="panel-title"><h4><i class="pe-7s-plus"></i>Nuevo movimiento<small class="caja-tab-ayuda">Dinero que entra o sale de la caja sin ser venta</small></h4></div>
									<button type="button" class="btn btn-default btn-sm caja-btn-titulo" id="btnAbrirGaveta" title="Abre el cajón de dinero conectado a la impresora de la caja">
										<i class="fa fa-inbox"></i> Abrir gaveta
									</button>
								</div>
								<div class="panel-body caja-mov-form">
									<div class="form-group">
										<div class="btn-group caja-tipo-mov" data-toggle="buttons">
											<label class="btn btn-default active"><input type="radio" name="tipoMov" value="ingreso" checked> <i class="fa fa-arrow-down"></i> Ingreso</label>
											<label class="btn btn-default"><input type="radio" name="tipoMov" value="egreso"> <i class="fa fa-arrow-up"></i> Retiro / Gasto</label>
										</div>
									</div>
									<div class="form-group">
										<label for="montoMov">Monto</label>
										<div class="input-group">
											<span class="input-group-addon">$</span>
											<input type="number" min="1" step="1" class="form-control" id="montoMov">
										</div>
										<small class="text-muted" id="movDisponible"></small>
									</div>
									<div class="form-group">
										<label for="obsMov">Motivo</label>
										<input type="text" maxlength="100" class="form-control" id="obsMov" placeholder="Ej: pago proveedor de pan">
									</div>
									<button class="btn btn-success btn-block" id="btnMovimiento"><i class="fa fa-check"></i> Registrar</button>
								</div>
							</div>
						</div>
						<div class="col-md-8">
							<div class="panel panel-success lobidisable">
								<div class="panel-heading">
									<div class="panel-title"><h4><i class="pe-7s-cash"></i>Movimientos de la caja<small class="caja-tab-ayuda">Ingresos y retiros registrados en este turno</small></h4></div>
								</div>
								<div class="panel-body table-responsive">
									<table class="table table-bordered table-hover" id="tablaMovimientos">
										<thead><tr><th>Fecha</th><th>Motivo</th><th class="caja-num">Ingreso</th><th class="caja-num">Retiro</th></tr></thead>
										<tbody></tbody>
									</table>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- ===================== CIERRE ===================== -->
				<div class="tab-pane fade" id="tabCierre">
					<div class="row">
						<div class="col-md-6">
							<div class="panel panel-success lobidisable">
								<div class="panel-heading">
									<div class="panel-title"><h4><i class="pe-7s-graph1"></i>Resumen del sistema<small class="caja-tab-ayuda">Lo que registró el sistema en este turno</small></h4></div>
									<button class="btn btn-default btn-sm caja-btn-titulo" id="btnInformeX" title="Imprimir el resumen del turno sin cerrar la caja">
										<i class="fa fa-print"></i> Informe X
									</button>
								</div>
								<div class="panel-body">
									<table class="table table-condensed cierre-tabla">
										<tbody id="resumenCierre"></tbody>
									</table>
								</div>
							</div>
						</div>
						<div class="col-md-6">
							<div class="panel panel-success lobidisable">
								<div class="panel-heading">
									<div class="panel-title"><h4><i class="pe-7s-calculator"></i>Lo que hay en caja<small class="caja-tab-ayuda">Cuenta el dinero y los comprobantes antes de cerrar</small></h4></div>
								</div>
								<div class="panel-body caja-cierre-form">
									<div class="form-group">
										<label for="efectivoContado">Efectivo contado * (incluye el fondo inicial, ya pagadas las propinas)</label>
										<div class="input-group">
											<span class="input-group-addon">$</span>
											<input type="number" min="0" step="1" class="form-control input-lg" id="efectivoContado" placeholder="Cuenta el efectivo y escribe el total">
										</div>
										<p class="help-block">Cierre ciego: el sistema no muestra cuánto efectivo debería haber. La diferencia queda registrada al cerrar.</p>
									</div>
									<div class="row">
										<div class="col-xs-6 form-group">
											<label for="fondoSiguiente">Fondo para el próximo turno</label>
											<div class="input-group">
												<span class="input-group-addon">$</span>
												<input type="number" min="0" step="1" class="form-control" id="fondoSiguiente">
											</div>
										</div>
										<div class="col-xs-6 form-group">
											<label>Efectivo a entregar</label>
											<p class="form-control-static"><b id="lblEntregar">$0</b></p>
										</div>
									</div>
									<div id="declaradoOtros"></div>
									<div class="cierre-kpis cierre-ciego">
										<div><small>Total declarado</small><b id="lblDeclarado">$0</b></div>
									</div>
									<button class="btn btn-danger btn-lg btn-block" id="btnCerrarCaja"><i class="fa fa-lock"></i> Cerrar caja</button>
									<p class="cierre-ayuda">Después de cerrar no se pueden registrar más ventas en esta caja.</p>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Drawer de mesa: al elegir una mesa, su venta (detalle, agregar productos, enviar, precuenta, cobrar y opciones de
		     la mesa) se abre aquí, desde la derecha, sobre la pestaña Mesas. La venta (.caja-venta) se mueve a este panel
		     mientras la mesa está abierta y vuelve a la pestaña Venta al cerrarla; la venta directa que hubiera queda
		     guardada aparte y se recupera al cerrar (js.php, "drawer de mesa"). -->
		<div class="mesa-drawer-fondo" id="mesaDrawerFondo"></div>
		<aside class="mesa-drawer" id="mesaDrawer" role="dialog" aria-modal="true" aria-labelledby="mesaDrawerNombre" aria-hidden="true">
			<div class="mesa-drawer-tirador" id="mesaDrawerTirador" role="separator" aria-orientation="vertical" aria-label="Ancho del panel de la mesa" tabindex="0"
				title="Arrastra para cambiar el ancho (doble clic: ancho de siempre)"></div>
			<div class="mesa-drawer-cab">
				<div class="mesa-drawer-titulo">
					<h3><i class="fa fa-th" aria-hidden="true"></i> <span id="mesaDrawerNombre"></span></h3>
					<small id="mesaDrawerInfo"></small>
				</div>
				<button type="button" class="mesa-drawer-cerrar" id="btnCerrarDrawerMesa" title="Volver a mesas (Esc)" aria-label="Volver a mesas">&times;</button>
			</div>
			<div class="mesa-drawer-cuerpo" id="mesaDrawerCuerpo"></div>
		</aside>
	</div>
</div>

<!-- Modal pago -->
<!-- Productos por familia -->
<div class="modal fade" id="modalProductos" tabindex="-1" role="dialog">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header btn-success">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title"><i class="fa fa-th-large"></i> Productos</h4>
			</div>
			<div class="modal-body">
				<div class="caja-buscar">
					<i class="fa fa-filter"></i>
					<input type="text" id="filtroProductos" class="form-control" placeholder="Filtrar en la familia elegida" autocomplete="off">
				</div>
				<div class="caja-familias" id="familias"></div>
				<div class="caja-productos" id="productos"></div>
			</div>
			<div class="modal-footer modal-productos-pie">
				<span class="text-muted">Toca un producto para agregarlo. En la venta: <b id="modalProductosItems">0</b></span>
				<div class="modal-productos-acciones">
					<label class="caja-switch" title="Al tocar un producto se agrega a la venta y se cierra esta ventana">
						<input type="checkbox" id="chkCerrarAlElegir">
						<span class="caja-switch-pista" aria-hidden="true"></span>
						<span class="caja-switch-texto">Cerrar al seleccionar</span>
					</label>
					<button type="button" class="btn btn-success" data-dismiss="modal"><i class="fa fa-check"></i> Listo</button>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Opciones > Documentos: documentos tributarios emitidos (tdocumento.cod_sii > 10) de un día, para verlos y reimprimirlos -->
<div class="modal fade" id="modalDocumentos" tabindex="-1" role="dialog" aria-labelledby="docTitulo">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header btn-success">
				<button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">&times;</button>
				<h4 class="modal-title" id="docTitulo"><i class="fa fa-file-text-o"></i> Documentos emitidos</h4>
			</div>
			<div class="modal-body">
				<div class="doc-filtros">
					<label class="doc-fecha">Día <input type="date" id="docFecha" class="form-control"></label>
					<div class="caja-buscar doc-buscar">
						<i class="fa fa-search"></i>
						<input type="text" id="docBuscar" class="form-control" placeholder="Buscar por folio, cliente o documento" autocomplete="off">
					</div>
					<span class="doc-resumen text-muted" id="docResumen"></span>
				</div>
				<div class="doc-cuerpo">
					<div class="doc-lista">
						<table class="table table-hover no-alinear" id="tablaDocumentos">
							<thead><tr><th>Hora</th><th>Documento</th><th>Folio</th><th>Cliente</th><th class="caja-num">Total</th><th class="text-center">Acciones</th></tr></thead>
							<tbody></tbody>
						</table>
					</div>
					<div class="doc-vista">
						<div class="doc-vista-vacia" id="docVistaVacia"><i class="fa fa-file-text-o"></i>Elige un documento para verlo</div>
						<iframe id="docFrame" title="Vista del documento" style="display:none"></iframe>
						<div class="doc-vista-acciones" id="docVistaAcciones" style="display:none">
							<a class="btn btn-default" id="docPdf" target="_blank" rel="noopener"><i class="fa fa-file-pdf-o"></i> PDF</a>
							<button type="button" class="btn btn-success" id="docReimprimir"><i class="fa fa-print"></i> Reimprimir</button>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Opciones de la mesa > Dividir cuenta: por comensal (arrastrar productos), partes iguales o libre. Cada parte tiene su precuenta y
     se cobra aparte con su propio documento y cliente. Lógica en js.php (dividir cuenta) -->
<div class="modal fade" id="modalDividir" tabindex="-1" role="dialog" aria-labelledby="dcTitulo">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header btn-success">
				<button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">&times;</button>
				<h4 class="modal-title" id="dcTitulo"><i class="fa fa-code-fork"></i> Dividir cuenta · <span id="dcMesa"></span></h4>
			</div>
			<div class="modal-body">
				<div class="dc-modos" role="tablist" id="dcModos">
					<button type="button" data-dc-modo="comensal" role="tab"><i class="fa fa-users"></i> Por comensal</button>
					<button type="button" data-dc-modo="iguales" role="tab"><i class="fa fa-usd"></i> Partes iguales</button>
					<button type="button" data-dc-modo="libre" role="tab"><i class="fa fa-hand-pointer-o"></i> Libre</button>
				</div>
				<div class="dc-resumen" id="dcResumen"></div>

				<section data-dc-vista="comensal">
					<div class="dc-vacio" id="dcComVacio">
						<i class="fa fa-users" aria-hidden="true"></i>
						Organiza los <span class="dc-n"></span> de la mesa entre comensales para dividir la cuenta.<br>
						<button type="button" class="btn btn-primary" id="dcCrearComensales"><i class="fa fa-plus"></i> Crear comensales y asignar productos</button>
					</div>
					<div id="dcTablero" hidden>
						<p class="dc-ayuda">Arrastra cada producto al comensal que corresponde (de a una unidad; con Mayús, todas), o usa los botones bajo cada producto.
							<b>⑂ Dividir</b> reparte un producto entre varios comensales.</p>
						<div class="dc-cols">
							<div class="dc-sin dc-zona" data-zona="0">
								<div class="dc-titulo-col"><i class="fa fa-cube"></i> Sin asignar (<span id="dcSinN">0</span>)</div>
								<div id="dcSinLista"></div>
							</div>
							<div>
								<form class="dc-nuevo" id="dcNuevo" autocomplete="off">
									<input type="text" class="form-control" id="dcNuevoNombre" maxlength="40" placeholder="Nuevo comensal…">
									<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> Agregar</button>
								</form>
								<div class="dc-com-lista" id="dcComLista"></div>
							</div>
						</div>
					</div>
				</section>

				<section data-dc-vista="iguales" hidden>
					<div class="dc-iguales-n">
						<label for="dcPartesN">¿En cuántas partes dividir?</label>
						<button type="button" class="btn btn-default" id="dcPartesMenos" aria-label="Una parte menos"><i class="fa fa-minus"></i></button>
						<input type="number" class="form-control" id="dcPartesN" min="2" max="30" step="1" value="2">
						<button type="button" class="btn btn-default" id="dcPartesMas" aria-label="Una parte más"><i class="fa fa-plus"></i></button>
					</div>
					<table class="dc-partes" id="dcPartes">
						<thead><tr><th>Desglose</th><th></th><th class="num" id="dcPorPersona"></th><th></th></tr></thead>
						<tbody></tbody>
						<tfoot></tfoot>
					</table>
					<p class="dc-nota">Cada parte lleva la misma fracción de cada producto (por ejemplo 0,5 de cada uno) y se cobra con su propio documento. Por el redondeo de las cantidades, las partes pueden diferir en unos pesos.</p>
				</section>

				<section data-dc-vista="libre" hidden>
					<div class="dc-vacio">
						<i class="fa fa-hand-pointer-o" aria-hidden="true"></i>
						Elige qué productos y cuántos se pagan ahora; lo demás sigue en la mesa para cobrarlo después.<br>
						<button type="button" class="btn btn-primary" id="dcLibre"><i class="fa fa-list"></i> Elegir productos a cobrar</button>
					</div>
				</section>

				<section data-dc-vista="cobrar" hidden>
					<div class="dc-cobro-cab"><b id="dcCobroNombre"></b><span id="dcCobroTotal"></span></div>
					<div class="dc-cobro-detalle" id="dcCobroDetalle"></div>
					<div class="row">
						<div class="col-sm-5 form-group">
							<label for="dcDocumento">Documento</label>
							<select id="dcDocumento" class="form-control"></select>
						</div>
						<div class="col-sm-7 form-group">
							<label>Cliente</label>
							<div class="dc-cliente">
								<input type="text" class="form-control" id="dcCliente" readonly>
								<button type="button" class="btn btn-default" id="dcBuscarCliente" title="Buscar o crear cliente"><i class="fa fa-search"></i></button>
								<button type="button" class="btn btn-default" id="dcQuitarCliente" title="Quitar cliente"><i class="fa fa-times"></i></button>
							</div>
							<p class="help-block text-danger" id="dcClienteFalta" hidden>Este documento requiere el cliente.</p>
						</div>
					</div>
					<div class="dc-factura" id="dcFactura" hidden>
						<p class="help-block" style="margin:0">Factura: al continuar se piden la razón social, el giro, la dirección y quién la solicita (RUT y nombre).</p>
					</div>
					<div class="dc-acciones">
						<button type="button" class="btn btn-default" id="dcVolver"><i class="fa fa-arrow-left"></i> Volver</button>
						<button type="button" class="btn btn-default" id="dcCobroPrecuenta"><i class="fa fa-print"></i> Precuenta</button>
						<button type="button" class="btn btn-success" id="dcContinuar"><i class="fa fa-money"></i> Continuar al pago</button>
					</div>
				</section>
			</div>
		</div>
	</div>
</div>

<!-- Opciones de la mesa > Cambiar garzón -->
<div class="modal fade" id="modalGarzon" tabindex="-1" role="dialog" aria-labelledby="garzonTitulo">
	<div class="modal-dialog modal-sm" role="document">
		<div class="modal-content">
			<form id="frmGarzon" autocomplete="off">
				<div class="modal-header btn-success">
					<button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">&times;</button>
					<h4 class="modal-title" id="garzonTitulo"><i class="fa fa-user"></i> Cambiar garzón</h4>
				</div>
				<div class="modal-body">
					<p class="garzon-actual"><span id="garzonMesa"></span> · atiende <b id="garzonActual">—</b></p>
					<div class="form-group">
						<label for="garzonNuevo">Nuevo garzón</label>
						<select id="garzonNuevo" class="form-control input-lg"></select>
						<p class="help-block">Queda como garzón de la venta al cobrar (propinas por garzón). Lo ya pedido conserva quién lo envió.</p>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
					<button type="submit" class="btn btn-primary" id="btnGuardarGarzon"><i class="fa fa-check"></i> Cambiar</button>
				</div>
			</form>
		</div>
	</div>
</div>

<!-- Opciones > Poner en espera: nombre para reconocer la venta al recuperarla -->
<div class="modal fade" id="modalEspera" tabindex="-1" role="dialog" aria-labelledby="esperaTitulo">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<form id="frmEspera" autocomplete="off">
				<div class="modal-header btn-success">
					<button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">&times;</button>
					<h4 class="modal-title" id="esperaTitulo"><i class="fa fa-pause"></i> Poner venta en espera</h4>
				</div>
				<div class="modal-body">
					<p class="text-muted" id="esperaResumen"></p>
					<div class="form-group">
						<label for="esperaNombre">Nombre</label>
						<input type="text" id="esperaNombre" class="form-control input-lg" maxlength="80" placeholder="Ej: Juan, mesa de afuera, pedido teléfono">
						<p class="help-block">La venta queda en Opciones &gt; Ventas en espera y la caja vuelve a quedar libre.</p>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
					<button type="submit" class="btn btn-primary" id="btnGuardarEspera"><i class="fa fa-pause"></i> Poner en espera</button>
				</div>
			</form>
		</div>
	</div>
</div>

<!-- Opciones > Ventas en espera: lista para cargar (con la venta vacía) o anular con justificación -->
<div class="modal fade" id="modalEsperas" tabindex="-1" role="dialog" aria-labelledby="esperasTitulo">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header btn-success">
				<button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">&times;</button>
				<h4 class="modal-title" id="esperasTitulo"><i class="fa fa-clock-o"></i> Ventas en espera</h4>
			</div>
			<div class="modal-body">
				<div id="esperasVistaLista">
					<div class="esp-aviso" id="esperasAviso" hidden><i class="fa fa-info-circle"></i><span></span></div>
					<div class="esp-lista" id="esperasLista"></div>
				</div>
				<form id="esperasVistaAnular" autocomplete="off" hidden>
					<div class="esp-anular-detalle" id="anularEsperaDetalle"></div>
					<div class="form-group">
						<label for="anularEsperaMotivo">Justificación de la anulación</label>
						<textarea id="anularEsperaMotivo" class="form-control" rows="3" maxlength="250" placeholder="Ej: el cliente se retiró sin pagar, venta duplicada"></textarea>
						<p class="help-block">Queda registrada con tu usuario en Consultas &gt; Anulación de ventas y en la Auditoría de caja.</p>
					</div>
					<div class="text-right">
						<button type="button" class="btn btn-default" id="btnAnularEsperaVolver"><i class="fa fa-arrow-left"></i> Volver</button>
						<button type="submit" class="btn btn-danger" id="btnAnularEsperaConfirmar"><i class="fa fa-ban"></i> Anular venta</button>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<!-- Salir de la Caja (menú, cambiar empresa) con una venta en curso: la venta directa pasa a espera y lo no enviado de una mesa queda en la auditoría -->
<div class="modal fade" id="modalSalida" tabindex="-1" role="dialog" aria-labelledby="salidaTitulo" data-backdrop="static">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<form id="frmSalida" autocomplete="off">
				<div class="modal-header btn-success">
					<h4 class="modal-title" id="salidaTitulo"><i class="fa fa-exclamation-triangle"></i> Tienes una venta en curso</h4>
				</div>
				<div class="modal-body">
					<div class="salida-bloque directa" id="salidaDirecta">
						<i class="fa fa-shopping-cart"></i>
						<div>
							<p><b>Venta directa</b> · <span id="salidaDirectaResumen"></span></p>
							<p class="text-muted" id="salidaDirectaTexto">Al salir quedará en Opciones &gt; Ventas en espera para recuperarla.</p>
							<div class="form-group" id="salidaNombreGrupo">
								<label for="salidaNombre">Nombre de la venta en espera</label>
								<input type="text" id="salidaNombre" class="form-control" maxlength="80">
							</div>
						</div>
					</div>
					<div class="salida-bloque mesa" id="salidaMesa">
						<i class="fa fa-cutlery"></i>
						<div>
							<p><b id="salidaMesaNombre"></b> · <span id="salidaMesaResumen"></span> sin enviar</p>
							<p class="text-muted">Si sales, esos productos no se envían a la mesa y quedan registrados como omitidos en la auditoría de caja.</p>
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal" id="btnSalidaQuedarme">Quedarme en la caja</button>
					<button type="submit" class="btn btn-primary" id="btnSalidaConfirmar"></button>
				</div>
			</form>
		</div>
	</div>
</div>

<div class="modal fade" id="modalPago" tabindex="-1" role="dialog">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header btn-success">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">Cobrar <span id="pagoTotal"></span> <small id="pagoTotalDetalle"></small></h4>
			</div>
			<div class="modal-body">
				<div class="pago-montos">
					<div class="total"><small>Total a pagar</small><b id="pagoTotalConPropina">$0</b></div>
					<div><small>Pagado</small><b id="pagoPagado">$0</b></div>
					<div class="falta" id="cajaFalta"><small>Falta</small><b id="pagoFalta">$0</b></div>
					<div class="vuelto"><small>Vuelto</small><b id="pagoVuelto">$0</b></div>
				</div>
				<div class="pago-propina" id="bloquePropina" style="display:none">
					<div class="checkbox checkbox-success" style="display:inline-block; margin:0 10px 0 0">
						<input type="checkbox" id="chkPropina"><label for="chkPropina" style="font-weight:bold">Propina <span id="lblPorPropina"></span></label>
					</div>
					<input type="number" min="0" step="1" class="form-control input-sm" id="txtPropina" value="0">
				</div>
				<div class="pago-agregados" id="filasPago"></div>
				<div class="alert alert-warning" id="avisoReglaPago" style="display:none; margin:8px 0 0; padding:8px 12px"></div>

				<div class="pago-cuerpo">
					<div class="pago-formas" id="botonesFpago"></div>
					<div class="pago-monto">
						<label for="montoPago">Monto en <span id="nombreFpagoSel"></span></label>
						<div class="input-group">
							<span class="input-group-addon">$</span>
							<input type="number" min="0" step="1" class="form-control" id="montoPago" value="0">
						</div>
						<div class="pago-billetes" id="billetesPago"></div>
						<button type="button" class="btn btn-default" id="btnAgregarPago"><i class="fa fa-plus"></i> Agregar pago <small class="text-muted">(Enter)</small></button>
						<!-- Venta directa: se imprime en la comanda y en el documento (venta.nombre_retira) -->
						<div class="pago-retira" id="grupoRetira">
							<label for="txtRetira"><i class="fa fa-user"></i> Nombre de quien retira <small>(opcional)</small></label>
							<input type="text" class="form-control" id="txtRetira" maxlength="60" autocomplete="off" placeholder="Ej: Juan Pérez">
						</div>
						<button type="button" class="btn btn-success btn-pago-exacto" id="btnPagoExacto" title="Completa lo que falta con la forma de pago elegida y registra la venta">
							<i class="fa fa-bolt"></i> Pago exacto
						</button>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<div class="checkbox checkbox-success caja-chk-imprimir">
					<input type="checkbox" id="chkImprimirVenta" class="chk-imprimir"><label for="chkImprimirVenta"><i class="fa fa-print"></i> Imprimir ticket</label>
					<select class="cbo-papel" title="Ancho del papel de la impresora"><option value="80">80 mm</option><option value="58">58 mm</option></select>
				</div>
				<div class="checkbox checkbox-success caja-chk-imprimir caja-chk-comanda">
					<input type="checkbox" id="chkImprimirComanda" class="chk-comanda"><label for="chkImprimirComanda" title="Una comanda por destino de impresión (cocina, barra…) con el mensaje de cada producto"><i class="fa fa-cutlery"></i> Imprimir comanda</label>
				</div>
				<button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
				<button type="button" class="btn btn-primary btn-lg" id="btnConfirmarPago" disabled><i class="fa fa-check"></i> Confirmar venta</button>
			</div>
		</div>
	</div>
</div>

<!-- Modal productos de la mesa: cambiar o dividir mesa, cobrar una parte o anular parte de un producto -->
<div class="modal fade" id="modalMesaProductos" tabindex="-1" role="dialog">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header btn-success">
				<button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title" id="mpTitulo"></h4>
			</div>
			<div class="modal-body">
				<p class="text-muted" id="mpAyuda"></p>
				<div id="mpLineas"></div>
				<div class="mp-resumen"><span id="mpResumenTexto">Seleccionado</span><span id="mpResumenMonto">$0</span></div>
				<div class="mp-campo" id="mpCampoDestino">
					<label for="mpDestino">Pasar a la mesa</label>
					<select class="form-control" id="mpDestino"></select>
				</div>
				<div class="mp-campo" id="mpCampoMotivo">
					<label for="mpMotivo">Motivo</label>
					<input type="text" class="form-control" id="mpMotivo" maxlength="100" placeholder="Ej: el cliente no lo quiso">
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
				<button type="button" class="btn btn-primary" id="mpConfirmar"></button>
			</div>
		</div>
	</div>
</div>

<!-- Modal propina: se pregunta al presionar Cobrar -->
<div class="modal fade" id="modalPropina" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-sm" role="document">
		<div class="modal-content">
			<div class="modal-body propina-pregunta">
				<h4>¿Agregar propina?</h4>
				<p class="text-muted">Total de la venta <b id="propinaPregunta"></b></p>
				<label for="txtPropinaPregunta" id="propinaPorcentaje"></label>
				<div class="input-group input-group-lg">
					<span class="input-group-addon">$</span>
					<input type="number" min="0" step="1" class="form-control" id="txtPropinaPregunta">
				</div>
				<button type="button" class="btn btn-success btn-lg btn-block" id="btnPropinaSi"><i class="fa fa-check"></i> Sí, con propina</button>
				<button type="button" class="btn btn-default btn-lg btn-block" id="btnPropinaNo">No, sin propina</button>
			</div>
		</div>
	</div>
</div>

<!-- Datos de la factura (11-11-2026): se piden al cobrar con factura, también en las mesas y al cobrar una parte.
     Se guardan con la venta (venta_receptor): la emisión y el ticket usan estos datos. Ver js.php (datos de la factura). -->
<div class="modal fade" id="modalFactura" tabindex="-1" role="dialog" aria-labelledby="facTitulo">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header btn-success">
				<button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">&times;</button>
				<h4 class="modal-title" id="facTitulo"><i class="fa fa-file-text-o"></i> Datos de la factura</h4>
			</div>
			<form class="modal-body" id="frmFactura" autocomplete="off" novalidate>
				<div class="fac-cliente"><span>Cliente</span> <b id="facRut"></b></div>
				<div class="form-group" id="facPreviosGrupo" hidden>
					<label for="facPrevios">Usar los datos de</label>
					<select id="facPrevios" class="form-control"></select>
				</div>
				<div class="form-group">
					<label for="facRazon">Razón social *</label>
					<input type="text" id="facRazon" class="form-control" maxlength="100">
				</div>
				<!-- Giro, dirección y solicitante se eligen de las listas del cliente (Clientes > Datos para factura y sus facturas
				     anteriores); con "Otro…" se escriben (largos máximos del SII: giro 40, dirección 70, ciudad 20) -->
				<div class="form-group">
					<label for="facGiroSel">Giro *</label>
					<select id="facGiroSel" class="form-control"></select>
					<input type="text" id="facGiro" class="form-control fac-otro" maxlength="40" placeholder="Escribe el giro (hasta 40 caracteres)" hidden>
				</div>
				<div class="form-group">
					<label for="facDirSel">Dirección (sucursal del cliente) *</label>
					<select id="facDirSel" class="form-control"></select>
				</div>
				<div class="row" id="facDirOtra" hidden>
					<div class="col-sm-7 form-group">
						<label for="facDireccion">Dirección *</label>
						<input type="text" id="facDireccion" class="form-control" maxlength="70" placeholder="Calle y número (hasta 70 caracteres)">
					</div>
					<div class="col-sm-5 form-group">
						<label for="facComuna">Comuna *</label>
						<select id="facComuna" class="form-control"></select>
					</div>
				</div>
				<div class="row">
					<div class="col-sm-5 form-group">
						<label for="facCiudad">Ciudad</label>
						<input type="text" id="facCiudad" class="form-control" maxlength="20">
					</div>
					<div class="col-sm-7 form-group">
						<label for="facContacto">Contacto (teléfono o correo)</label>
						<input type="text" id="facContacto" class="form-control" maxlength="80">
					</div>
				</div>
				<fieldset class="fac-solicita">
					<legend>Quién solicita la factura</legend>
					<div class="form-group">
						<label for="facSolSel">Solicitante *</label>
						<select id="facSolSel" class="form-control"></select>
					</div>
					<div class="row" id="facSolOtro" hidden>
						<div class="col-sm-5 form-group">
							<label for="facRutSol">RUT *</label>
							<input type="text" id="facRutSol" class="form-control" maxlength="12" placeholder="12345678-9">
						</div>
						<div class="col-sm-7 form-group">
							<label for="facNombreSol">Nombre *</label>
							<input type="text" id="facNombreSol" class="form-control" maxlength="80">
						</div>
					</div>
				</fieldset>
				<div class="form-group" style="margin-top: 14px">
					<label for="facMotivo">Motivo de la solicitud</label>
					<input type="text" id="facMotivo" class="form-control" maxlength="200" placeholder="Opcional. Se imprime en la factura">
				</div>
				<div class="checkbox checkbox-success fac-guardar" id="facGuardarGrupo" hidden>
					<input type="checkbox" id="facGuardarFicha" checked><label for="facGuardarFicha">Guardar en la ficha del cliente lo nuevo (dirección, giro o solicitante)</label>
				</div>
			</form>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
				<button type="button" class="btn btn-success" id="btnFacturaContinuar"><i class="fa fa-arrow-right"></i> Continuar al pago</button>
			</div>
		</div>
	</div>
</div>

<!-- Modal agregados / mensajes del producto -->
<!-- Producto a granel (fraccionable + "Pedir cantidad en venta"): se vende por valor (lo que marca la balanza) o por peso -->
<div class="modal fade" id="modalPesable" tabindex="-1" role="dialog" aria-labelledby="pesableTitulo">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header btn-success">
				<button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">&times;</button>
				<h4 class="modal-title" id="pesableTitulo"></h4>
			</div>
			<div class="modal-body">
				<div class="pes-modo" role="tablist">
					<button type="button" data-modo="valor" role="tab"><i class="fa fa-usd"></i>Por valor</button>
					<button type="button" data-modo="peso" role="tab"><i class="fa fa-balance-scale"></i>Por peso</button>
				</div>
				<p class="pes-precio">Precio: <b id="pesablePrecio"></b></p>
				<div class="pes-entrada" id="pesableEntrada">
					<label for="txtPesable" id="pesableEtiqueta"></label>
					<span class="pes-pre" id="pesablePre"></span>
					<input type="text" inputmode="decimal" autocomplete="off" spellcheck="false" class="pes-input" id="txtPesable">
					<span class="pes-suf" id="pesableSuf"></span>
				</div>
				<p class="pes-msg" id="pesableMsg"></p>
				<div class="pes-resultado">
					<span id="pesableResEtiqueta"></span>
					<b id="pesableRes">—</b>
				</div>
				<p class="pes-nota" id="pesableNota"></p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
				<button type="button" class="btn btn-success btn-lg" id="btnConfirmarPesable" disabled><i class="fa fa-check"></i> Agregar</button>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="modalOpciones" tabindex="-1" role="dialog">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header btn-success">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title" id="opcionesTitulo"></h4>
			</div>
			<div class="modal-body">
				<div id="opcionesAgregados" style="display:none">
					<h5><b>Agregados</b> <small id="opcionesMaxAgregados" class="text-muted"></small></h5>
					<div class="opcion-lista" id="listaAgregados"></div>
					<hr>
				</div>
				<div id="opcionesMensajes" style="display:none">
					<h5><b>Indicaciones</b></h5>
					<div class="opcion-lista" id="listaMensajes"></div>
				</div>
				<div class="form-group" style="margin-top:8px">
					<label for="txtNotaProducto">Nota</label>
					<input type="text" class="form-control" id="txtNotaProducto" maxlength="100" placeholder="Ej: sin cebolla, bien cocido">
				</div>
			</div>
			<div class="modal-footer">
				<span class="pull-left" style="font-size:16px; padding-top:6px">Precio: <b id="opcionesPrecio"></b></span>
				<button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
				<button type="button" class="btn btn-success" id="btnConfirmarOpciones">Agregar</button>
			</div>
		</div>
	</div>
</div>

<!-- Modal autorización de administrador -->
<div class="modal fade" id="modalAutorizacion" tabindex="-1" role="dialog" data-backdrop="static">
	<div class="modal-dialog modal-sm" role="document">
		<div class="modal-content">
			<form id="frmAutorizacion" autocomplete="off">
				<div class="modal-header btn-success">
					<h4 class="modal-title"><i class="fa fa-lock"></i> Autorización</h4>
				</div>
				<div class="modal-body">
					<p id="autorizacionMotivo"></p>
					<p class="text-danger" id="autorizacionError" style="display:none"></p>
					<div class="form-group">
						<label for="cboAutorizador">Administrador</label>
						<select id="cboAutorizador" class="form-control"></select>
					</div>
					<div class="form-group">
						<label for="txtClaveAdmin">Clave</label>
						<input type="password" id="txtClaveAdmin" class="form-control" autocomplete="new-password">
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
					<button type="submit" class="btn btn-primary">Autorizar</button>
				</div>
			</form>
		</div>
	</div>
</div>

<!-- Modal venta registrada -->
<!-- Inventario de insumos al cierre de turno (los marcados con "Inventario en cierre de turno" en Insumos) -->
<div class="modal fade" id="modalInventarioCierre" tabindex="-1" role="dialog" data-backdrop="static">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title"><i class="fa fa-cubes"></i> Inventario de cierre</h4>
			</div>
			<div class="modal-body">
				<p class="inv-cierre-nota">Cuenta lo que queda de cada insumo. <b>Lo que ingreses reemplaza al stock del sistema</b> al cerrar la caja.</p>
				<div class="table-responsive">
					<table class="table inv-cierre-tabla">
						<thead><tr><th>Insumo</th><th class="text-right">Stock sistema</th><th class="text-right">Contado</th><th class="text-right">Diferencia</th></tr></thead>
						<tbody id="invCierreFilas"></tbody>
					</table>
				</div>
			</div>
			<div class="modal-footer">
				<span class="inv-cierre-resumen" id="invCierreResumen"></span>
				<button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
				<button type="button" class="btn btn-primary btn-lg" id="btnInvCierreContinuar"><i class="fa fa-check"></i> Continuar al cierre</button>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="modalVentaOk" tabindex="-1" role="dialog" data-backdrop="static">
	<div class="modal-dialog" role="document">
		<div class="modal-content venta-ok">
			<div class="venta-ok-icono"><i class="fa fa-check"></i></div>
			<h3 id="ventaOkTitulo">Venta registrada</h3>
			<p class="venta-ok-doc" id="ventaOkDoc"></p>
			<div class="venta-ok-vuelto" id="ventaOkVuelto"></div>
			<p id="ventaOkAviso" class="alert alert-danger" style="display:none; text-align:left; padding:8px 10px"></p>
			<div class="venta-ok-acciones">
				<a class="btn btn-default" id="ventaOkTicket" target="_blank"><i class="fa fa-print"></i> Imprimir ticket</a>
				<a class="btn btn-default" id="ventaOkPdf" target="_blank"><i class="fa fa-file-pdf-o"></i> Ver PDF</a>
			</div>
			<button class="btn btn-success btn-lg btn-block" data-dismiss="modal" id="btnNuevaVenta">Nueva venta</button>
			<div class="venta-ok-ayuda">Enter para continuar</div>
		</div>
	</div>
</div>

<!-- Modal devolución (nota de crédito interna) -->
<div class="modal fade" id="modalDevolucion" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header btn-success">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title"><i class="fa fa-undo"></i> Devolución · Nota de crédito interna</h4>
			</div>
			<div class="modal-body">
				<form id="devBusqueda" class="row">
					<div class="col-sm-6 form-group">
						<label for="devDocumento">Documento de la venta</label>
						<select id="devDocumento" class="form-control"></select>
					</div>
					<div class="col-sm-3 form-group">
						<label for="devFolio">Folio</label>
						<input type="number" min="1" step="1" id="devFolio" class="form-control">
					</div>
					<div class="col-sm-3 form-group">
						<label class="hidden-xs">&nbsp;</label>
						<button type="submit" class="btn btn-success btn-block"><i class="fa fa-search"></i> Buscar</button>
					</div>
				</form>
				<div id="devContenido" style="display:none">
					<div class="dev-info" id="devInfo"></div>
					<div class="table-responsive">
						<table class="table table-bordered table-condensed" id="devLineas">
							<thead>
								<tr><th>Producto</th><th class="text-center hidden-xs">Vendido</th><th class="text-center hidden-xs">Ya devuelto</th>
									<th class="text-center dev-col-cant">Devolver</th><th class="caja-num">Monto</th></tr>
							</thead>
							<tbody></tbody>
						</table>
					</div>
					<button type="button" class="btn btn-default btn-sm" id="btnDevTodo"><i class="fa fa-check-square-o"></i> Devolver todo</button>
					<div class="row" style="margin-top:12px">
						<div class="col-sm-7 form-group">
							<label for="devMotivo">Motivo</label>
							<input type="text" id="devMotivo" class="form-control" maxlength="150" placeholder="Ej: producto en mal estado">
						</div>
						<div class="col-sm-5 form-group">
							<label for="devFpago">Devolver el dinero en</label>
							<select id="devFpago" class="form-control"></select>
						</div>
					</div>
					<div class="caja-totales">
						<div class="fila total"><span>Total a devolver</span><b id="devTotal">$0</b></div>
					</div>
					<p class="text-muted small" style="margin:6px 0 0">Se devuelve lo pagado (con el descuento de la venta, sin propina) y el stock vuelve a bodega.</p>
				</div>
			</div>
			<div class="modal-footer">
				<div class="checkbox checkbox-success caja-chk-imprimir">
					<input type="checkbox" id="chkImprimirDev" class="chk-imprimir"><label for="chkImprimirDev"><i class="fa fa-print"></i> Imprimir ticket</label>
					<select class="cbo-papel" title="Ancho del papel de la impresora"><option value="80">80 mm</option><option value="58">58 mm</option></select>
				</div>
				<button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
				<button type="button" class="btn btn-danger" id="btnConfirmarDevolucion" disabled><i class="fa fa-undo"></i> Registrar devolución</button>
			</div>
		</div>
	</div>
</div>

<!-- Modal cliente -->
<div class="modal fade" id="modalCliente" tabindex="-1" role="dialog">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header btn-success">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">Cliente</h4>
			</div>
			<div class="modal-body">
				<ul class="nav nav-tabs">
					<li class="active"><a href="#tabBuscarCliente" data-toggle="tab">Buscar</a></li>
					<li><a href="#tabNuevoCliente" data-toggle="tab" id="tabClienteForm">Nuevo</a></li>
				</ul>
				<div class="tab-content" style="padding-top:12px">
					<div class="tab-pane active" id="tabBuscarCliente">
						<input type="text" class="form-control" id="txtBuscarCliente" placeholder="Nombre o RUT" autocomplete="off">
						<div class="list-group" id="resultadosCliente" style="max-height:300px; overflow-y:auto; margin-top:8px"></div>
					</div>
					<div class="tab-pane" id="tabNuevoCliente">
						<form id="frmCliente">
							<input type="hidden" name="id_cliente" value="">
							<div class="row">
								<div class="col-sm-7 form-group"><label>Nombre / Razón social *</label><input name="nombre_cliente" class="form-control" maxlength="100" required></div>
								<div class="col-sm-5 form-group"><label>RUT *</label><input name="rut" class="form-control" placeholder="12.345.678-9" maxlength="12" required></div>
								<div class="col-sm-6 form-group"><label>Giro</label><input name="giro" class="form-control" maxlength="100"></div>
								<div class="col-sm-6 form-group"><label>Dirección</label><input name="direccion" class="form-control" maxlength="100"></div>
								<div class="col-sm-6 form-group"><label for="cboComuna">Comuna</label><select name="id_comuna" id="cboComuna" class="form-control"></select></div>
								<div class="col-sm-6 form-group"><label>Teléfono</label><input name="telefono" class="form-control" maxlength="30"></div>
								<div class="col-sm-6 form-group"><label>Correo</label><input name="mail" type="email" class="form-control" maxlength="100"></div>
							</div>
							<p class="text-muted">Para facturas el cliente necesita RUT, giro, dirección y comuna.</p>
							<button type="submit" class="btn btn-primary">Guardar y usar</button>
							<button type="button" class="btn btn-default" id="btnCancelarEdicion" style="display:none">Cancelar edición</button>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
