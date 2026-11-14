/* Cliente del agente de impresión local (http://localhost:5050), el mismo que usa mipdv_web
   (mipdv_web/vendor/pluginImpresion/LocalPrintClient.js). En resportesResto la URL y la clave vienen de
   config/impresion_local.php (variables de entorno): aquí la clave por defecto queda vacía. */
(function(global) {
  'use strict';

  var runtimeConfig = global.LOCAL_PRINT_CONFIG || {};
  var DEFAULT_BASE_URL = 'http://localhost:5050';
  var DEFAULT_API_KEY = '';   // resportesResto: la clave la entrega el servidor (RESTO_IMPRESION_LOCAL_CLAVE), no va en el código
  var pingCache = {
    ts: 0,
    value: false
  };

  function trimTrailingSlash(value) {
    return String(value || '').replace(/\/+$/, '');
  }

  function getBaseUrl() {
    return trimTrailingSlash(runtimeConfig.baseUrl || DEFAULT_BASE_URL);
  }

  function getApiKey(explicitApiKey) {
    if (explicitApiKey) {
      return explicitApiKey;
    }

    return runtimeConfig.apiKey || DEFAULT_API_KEY;
  }

  function buildUrl(path) {
    if (/^https?:\/\//i.test(String(path || ''))) {
      return String(path);
    }

    var normalizedPath = String(path || '').replace(/^\/+/, '');
    return getBaseUrl() + '/' + normalizedPath;
  }

  function buildHeaders(apiKey, extraHeaders) {
    var headers = Object.assign({
      'Content-Type': 'application/json'
    }, extraHeaders || {});
    var resolvedApiKey = getApiKey(apiKey);

    if (resolvedApiKey) {
      headers.Authorization = 'Basic ' + btoa(resolvedApiKey);
    }

    return headers;
  }

  async function parseResponse(res) {
    var text = await res.text();

    if (!res.ok) {
      throw new Error('Error del servidor (' + res.status + '): ' + text);
    }

    try {
      return JSON.parse(text);
    } catch (error) {
      return text;
    }
  }

  async function request(path, options) {
    var config = options || {};
    var method = config.method || 'POST';
    var body = Object.prototype.hasOwnProperty.call(config, 'body') ? config.body : {};
    var headers = buildHeaders(config.apiKey, config.headers);
    var fetchOptions = {
      method: method,
      headers: headers
    };

    if (method !== 'GET' && body !== undefined && body !== null) {
      fetchOptions.body = typeof body === 'string' ? body : JSON.stringify(body);
    }

    return parseResponse(await fetch(buildUrl(path), fetchOptions));
  }

  async function ping(options) {
    var config = options || {};
    var maxAgeMs = Math.max(0, parseInt(config.maxAgeMs, 10) || 0);
    var now = Date.now();

    if (maxAgeMs > 0 && (now - pingCache.ts) < maxAgeMs) {
      return pingCache.value;
    }

    try {
      var response = await fetch(buildUrl('ping'), {
        method: 'GET',
        headers: buildHeaders(config.apiKey)
      });
      var data = await parseResponse(response);
      pingCache.value = !!(data && (data.estado === 'ok' || data.status === 'ok' || data.ok === true));
    } catch (error) {
      pingCache.value = false;
    }

    pingCache.ts = now;
    return pingCache.value;
  }

  function setConfig(nextConfig) {
    runtimeConfig = Object.assign({}, runtimeConfig, nextConfig || {});
    global.LOCAL_PRINT_CONFIG = runtimeConfig;
  }

  global.LOCAL_PRINT_CONFIG = Object.assign({
    baseUrl: DEFAULT_BASE_URL,
    apiKey: DEFAULT_API_KEY
  }, runtimeConfig || {});

  global.LocalPrintClient = {
    DEFAULT_BASE_URL: DEFAULT_BASE_URL,
    DEFAULT_API_KEY: DEFAULT_API_KEY,
    setConfig: setConfig,
    getConfig: function() {
      return Object.assign({}, global.LOCAL_PRINT_CONFIG || runtimeConfig);
    },
    getBaseUrl: getBaseUrl,
    getApiKey: getApiKey,
    buildUrl: buildUrl,
    buildHeaders: buildHeaders,
    request: request,
    ping: ping,
    resetPingCache: function() {
      pingCache.ts = 0;
      pingCache.value = false;
    }
  };
})(window);
