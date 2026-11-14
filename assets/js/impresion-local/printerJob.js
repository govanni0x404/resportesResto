/* Trabajo de impresión para el agente local (copia de mipdv_web/vendor/pluginImpresion/printerJob.js, sin cambios). */
(function(global) {
  'use strict';

  function normalizeLegacyPrintResponse(response) {
    if (response === true) {
      return true;
    }

    if (response === false || response === null || response === undefined) {
      return false;
    }

    if (typeof response === 'string') {
      var normalized = response.trim().toLowerCase();
      return normalized === 'ok' || normalized === 'true';
    }

    if (typeof response === 'object') {
      if (response.ok === true || response.status === 'ok' || response.estado === 'ok') {
        return true;
      }
    }

    return false;
  }

  class PrinterJob {
    static get DEFAULT_URL() {
      return global.LocalPrintClient ? global.LocalPrintClient.buildUrl('imprimir') : 'http://localhost:5050/imprimir';
    }

    static get URL_IMPRESORAS() {
      return global.LocalPrintClient ? global.LocalPrintClient.buildUrl('impresoras') : 'http://localhost:5050/impresoras';
    }

    constructor(config) {
      var options = config || {};
      this.tipo_conexion = options.tipo_conexion || 'ethernet';
      this.dispositivo = options.dispositivo;
      this.ip = options.ip;
      this.puerto = options.puerto;
      this.raster_max_width = options.raster_max_width;
      this.operaciones = [];
      this._url_imprimir = options.url_imprimir || PrinterJob.DEFAULT_URL;
      this._url_listar_impresoras = options.url_listar_impresoras || PrinterJob.URL_IMPRESORAS;
    }

    _add(accion, valor) {
      this.operaciones.push({ accion: accion, valor: valor });
      return this;
    }

    abrir_gaveta() { return this._add('abrir_gaveta', ''); }
    img_url(url) { return this._add('img_url', url); }
    fontsize(n) { return this._add('fontsize', n); }
    textaling(a) { return this._add('textaling', a); }
    texto(t) { return this._add('texto', t); }
    fontbold(v) { return this._add('fontbold', v); }
    feed(n) { return this._add('feed', n); }
    justificar(v) { return this._add('justificar', v); }
    cut() { return this._add('cut', ''); }
    subrayado(v) { return this._add('subrayado', v !== false); }

    imprimir_logo(imprimir) {
      return this._add('imprimir_logo', { imprimir: imprimir !== false });
    }

    linea(config) {
      var options = config || {};
      var valor = {
        caracter: String(options.caracter || '-').slice(0, 1),
        alineacion: String(options.alineacion || 'left').toLowerCase()
      };

      if (options.ancho !== undefined) valor.ancho = Number(options.ancho);
      if (options.ancho_maximo !== undefined) valor.ancho_maximo = Number(options.ancho_maximo);
      return this._add('linea', valor);
    }

    texto_formateado(columnas) {
      return this._add('texto_formateado', columnas);
    }

    texto_raster(valor) {
      return this._add('texto_raster', valor || {});
    }

    recuadro(cfg) {
      return this._add('recuadro', cfg);
    }

    pdf417(config) {
      var options = config || {};
      var payload = { valor: options.valor };
      if (options.ancho !== undefined) payload.ancho = options.ancho;
      if (options.alto !== undefined) payload.alto = options.alto;
      return this._add('pdf417', payload);
    }

    barcode(config) {
      var options = config || {};
      var normalizedType = String(options.tipo || 'EAN13').toUpperCase();
      var normalizedValue = options.valor;

      if (normalizedType === 'EAN13') {
        normalizedValue = String(options.valor).trim();
        if (!/^\d{12,13}$/.test(normalizedValue)) {
          throw new Error('EAN13 debe contener 12 o 13 digitos numericos.');
        }
        if (normalizedValue.length === 12) {
          normalizedValue += calcularDigitoEAN13(normalizedValue);
        }
        if (normalizedValue.length === 13) {
          var base = normalizedValue.slice(0, 12);
          var dv = normalizedValue.slice(12);
          var dvCalc = String(calcularDigitoEAN13(base));
          if (dv !== dvCalc) {
            throw new Error('EAN13 invalido: digito verificador incorrecto. Debe ser ' + dvCalc + '.');
          }
        }
      }

      return this._add('barcode', {
        tipo: normalizedType,
        valor: normalizedValue,
        ancho: options.ancho,
        alto: options.alto,
        alineacion: options.alineacion,
        fontsize_text: options.fontsize
      });
    }

    qr(value) {
      if (value && typeof value === 'object' && !Array.isArray(value)) {
        var payload = Object.assign({}, value);
        if (!payload.data && payload.cbarra) {
          payload.data = payload.cbarra;
          delete payload.cbarra;
        }
        return this._add('qr', payload);
      }

      return this._add('qr', {
        data: value,
        box_size: 4,
        border: 4
      });
    }

    build() {
      var payload = {
        tipo_conexion: this.tipo_conexion,
        dispositivo: this.dispositivo,
        ip: this.ip,
        puerto: this.puerto,
        operaciones: this.operaciones
      };

      if (this.raster_max_width !== undefined && this.raster_max_width !== null) {
        payload.raster_max_width = this.raster_max_width;
      }

      return payload;
    }

    toJSON() {
      return this.build();
    }

    toString() {
      return JSON.stringify(this.build());
    }

    async imprimir(api_key) {
      if (global.LocalPrintClient && typeof global.LocalPrintClient.request === 'function') {
        return global.LocalPrintClient.request(this._url_imprimir, {
          body: this.build(),
          apiKey: api_key
        });
      }

      var headers = { 'Content-Type': 'application/json' };
      if (api_key) headers.Authorization = 'Basic ' + btoa(api_key);
      var res = await fetch(this._url_imprimir, {
        method: 'POST',
        headers: headers,
        body: JSON.stringify(this.build())
      });
      var text = await res.text();
      if (!res.ok) throw new Error('Error del servidor (' + res.status + '): ' + text);
      try {
        return JSON.parse(text);
      } catch (error) {
        return text;
      }
    }

    async listar_impresoras(api_key) {
      if (global.LocalPrintClient && typeof global.LocalPrintClient.request === 'function') {
        return global.LocalPrintClient.request(this._url_listar_impresoras, {
          body: {},
          apiKey: api_key
        });
      }

      var headers = { 'Content-Type': 'application/json' };
      if (api_key) headers.Authorization = 'Basic ' + btoa(api_key);
      var res = await fetch(this._url_listar_impresoras, {
        method: 'POST',
        headers: headers,
        body: JSON.stringify({})
      });
      var text = await res.text();
      if (!res.ok) throw new Error('Error del servidor (' + res.status + '): ' + text);
      try {
        return JSON.parse(text);
      } catch (error) {
        return text;
      }
    }
  }

  class printer_plugin {
    constructor(urlBase) {
      this._urlBase = urlBase || (global.LocalPrintClient ? global.LocalPrintClient.getBaseUrl() : 'http://localhost:5050');
      this.operaciones = [];
      this.raster_max_width = undefined;
    }

    _add(accion, valor) {
      this.operaciones.push({ accion: accion, valor: valor });
      return this;
    }

    text(value) { return this._add('texto', value); }
    fontsize(value) { return this._add('fontsize', value); }
    textaling(value) { return this._add('textaling', value); }
    feed(value) { return this._add('feed', value); }
    img_url(value) { return this._add('img_url', value); }
    cut() { return this._add('cut', ''); }
    abrir_gaveta() { return this._add('abrir_gaveta', ''); }
    imprimir_logo(imprimir) { return this._add('imprimir_logo', { imprimir: imprimir !== false }); }

    qr(value) {
      return PrinterJob.prototype.qr.call(this, value);
    }

    pdf417(value) {
      if (value && typeof value === 'object' && !Array.isArray(value)) {
        return this._add('pdf417', value);
      }
      return this._add('pdf417', { valor: value });
    }

    barcode_ean13(value) {
      return PrinterJob.prototype.barcode.call(this, { tipo: 'EAN13', valor: value });
    }

    barcode_39(value) {
      return PrinterJob.prototype.barcode.call(this, { tipo: 'CODE39', valor: value });
    }

    barcode_128(value) {
      return PrinterJob.prototype.barcode.call(this, { tipo: 'CODE128', valor: value });
    }

    async imprimir(nombreImpresora, api_key) {
      var job = new PrinterJob({
        tipo_conexion: 'usb',
        dispositivo: nombreImpresora,
        url_imprimir: this._urlBase.replace(/\/+$/, '') + '/imprimir',
        raster_max_width: this.raster_max_width
      });

      job.operaciones = this.operaciones.slice();
      return normalizeLegacyPrintResponse(await job.imprimir(api_key));
    }

    static async obtenerImpresoras(urlBase, api_key) {
      var baseUrl = (urlBase || (global.LocalPrintClient ? global.LocalPrintClient.getBaseUrl() : 'http://localhost:5050')).replace(/\/+$/, '');
      var job = new PrinterJob({
        url_listar_impresoras: baseUrl + '/impresoras'
      });
      return job.listar_impresoras(api_key);
    }
  }

  function calcularDigitoEAN13(code12) {
    var suma = 0;
    var normalized = String(code12 || '').slice(0, 12);

    for (var i = 0; i < normalized.length; i += 1) {
      var n = parseInt(normalized.charAt(i), 10) || 0;
      suma += (i % 2 === 0) ? n : n * 3;
    }

    var resto = suma % 10;
    return resto === 0 ? 0 : 10 - resto;
  }

  global.PrinterJob = PrinterJob;
  global.printer_plugin = printer_plugin;
})(window);
