/**
 * Prueba en un navegador de verdad (21-11-2026): abre cada pantalla y avisa si sale un error de JavaScript al cargarla
 * (por ejemplo usar jQuery antes de que se cargue). caja_js_test.php revisa que el código esté bien escrito; esta revisa
 * que funcione al abrir la página.
 *
 *   node tests/navegador_test.mjs [http://localhost:8001/index.php]
 *
 * Necesita Node.js 22 o más nuevo y Chrome o Chromium (o el de Playwright). Otra ruta: NAVEGADOR=/ruta/al/chrome.
 * Entra con el login local (/login/dev) igual que las otras pruebas. Solo lee: no graba nada.
 */
import { spawn, execSync } from 'node:child_process';
import { existsSync, readdirSync, readFileSync, mkdtempSync } from 'node:fs';
import { tmpdir, homedir } from 'node:os';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const BASE = (process.argv[2] || 'http://localhost:8001/index.php').replace(/\/$/, '');
const RAIZ = join(dirname(fileURLToPath(import.meta.url)), '..');
const esperar = (ms) => new Promise((r) => setTimeout(r, ms));

function buscarNavegador() {
	if (process.env.NAVEGADOR && existsSync(process.env.NAVEGADOR)) return process.env.NAVEGADOR;
	const pw = join(homedir(), '.cache', 'ms-playwright');
	if (existsSync(pw)) {
		for (const d of readdirSync(pw).sort().reverse()) {
			for (const f of ['chrome-headless-shell-linux64/chrome-headless-shell', 'chrome-linux/chrome']) if (existsSync(join(pw, d, f))) return join(pw, d, f);
		}
	}
	const fijos = ['C:/Program Files/Google/Chrome/Application/chrome.exe', 'C:/Program Files (x86)/Google/Chrome/Application/chrome.exe',
		'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe', '/usr/bin/google-chrome', '/usr/bin/chromium', '/usr/bin/chromium-browser'];
	for (const f of fijos) if (existsSync(f)) return f;
	return '';
}

// Login local con cookies a mano (como lib_pruebas.php)
let cookies = {};
async function pedir(url, form) {
	const h = { 'X-Requested-With': 'XMLHttpRequest', Cookie: Object.entries(cookies).map(([k, v]) => k + '=' + v).join('; ') };
	if (form) h['Content-Type'] = 'application/x-www-form-urlencoded';
	const r = await fetch(url, { method: form ? 'POST' : 'GET', headers: h, body: form ? new URLSearchParams(form) : undefined, redirect: 'manual' });
	for (const c of r.headers.getSetCookie ? r.headers.getSetCookie() : []) { const [kv] = c.split(';'); const i = kv.indexOf('='); cookies[kv.slice(0, i)] = kv.slice(i + 1); }
	return [r.status, await r.text()];
}

const nav = buscarNavegador();
if (!nav) { console.log('No encontré Chrome/Chromium: no puedo hacer la prueba en el navegador (NAVEGADOR=/ruta/al/chrome).'); process.exit(0); }
let [c, html] = await pedir(BASE + '/login/dev');
const m = html.match(/name="token" value="([a-f0-9]+)"[\s\S]*?name="id_usuario" value="(\d+)"/);
if (!m) { console.log('No pude entrar por ' + BASE + '/login/dev (código ' + c + ')'); process.exit(1); }
await pedir(BASE + '/login/dev', { token: m[1], id_usuario: m[2] });

// Pantallas: controladores con index (menos los de entrada y los de consola)
const saltar = new Set(['Login', 'Welcome', 'Despliegue', 'Respaldo', 'ApiMovil', 'AgenteImpresion', 'ImagenImpresion']);
const pantallas = readdirSync(join(RAIZ, 'application', 'controllers')).filter((f) => f.endsWith('.php')).map((f) => f.slice(0, -4))
	.filter((n) => !saltar.has(n) && /public function index\s*\(/.test(readFileSync(join(RAIZ, 'application', 'controllers', n + '.php'), 'utf8')));

const puerto = 9400 + Math.floor(Math.random() * 400);
const perfil = mkdtempSync(join(tmpdir(), 'nav-prueba-'));
const proc = spawn(nav, ['--headless', '--no-sandbox', '--disable-gpu', `--remote-debugging-port=${puerto}`, `--user-data-dir=${perfil}`, 'about:blank'], { stdio: 'ignore' });
let ws;
for (let i = 0; i < 60 && !ws; i++) {
	try { const l = await (await fetch(`http://127.0.0.1:${puerto}/json`)).json(); const p = l.find((x) => x.type === 'page'); if (p) ws = new WebSocket(p.webSocketDebuggerUrl); } catch (e) { /* aún no */ }
	if (!ws) await esperar(250);
}
await new Promise((r) => (ws.onopen = r));
let id = 0; const pend = {}; let errores = [];
ws.onmessage = (msg) => {
	const d = JSON.parse(msg.data);
	if (d.id && pend[d.id]) { pend[d.id](d.result); delete pend[d.id]; return; }
	if (d.method === 'Runtime.exceptionThrown') {
		const e = d.params.exceptionDetails;
		errores.push(((e.exception && e.exception.description) || e.text || '').split('\n')[0] + (e.url ? ' (' + e.url.split('/').pop() + ':' + e.lineNumber + ')' : ''));
	}
};
const cmd = (method, params = {}) => new Promise((r) => { const i = ++id; pend[i] = r; ws.send(JSON.stringify({ id: i, method, params })); });
await cmd('Runtime.enable');
await cmd('Network.enable');
const dom = new URL(BASE);
for (const [k, v] of Object.entries(cookies)) await cmd('Network.setCookie', { name: k, value: v, domain: dom.hostname, path: '/' });

let fallas = 0;
console.log('Errores de JavaScript al abrir cada pantalla:');
for (const p of pantallas) {
	errores = [];
	await cmd('Page.navigate', { url: BASE + '/' + p });
	await esperar(2500);
	// Tablas: una columna de números marcada con un tipo que DataTables no sabe ordenar (ej. "num,") se ordena como texto
	// (1, 10, 2...). Pasó en v1.29.2 con el decimal por defecto.
	const malOrden = (await cmd('Runtime.evaluate', { returnByValue: true, expression: `(function () {
		if (!window.jQuery || !jQuery.fn.dataTable) return [];
		var ord = jQuery.fn.dataTable.ext.type.order, res = [];
		jQuery.fn.dataTable.tables().forEach(function (t) {
			jQuery(t).DataTable().settings()[0].aoColumns.forEach(function (c, i) {
				var tipo = c.sType || '';
				if (/num/.test(tipo) && !ord[tipo + '-pre'] && !ord[tipo + '-asc']) res.push('#' + (t.id || '?') + ' columna ' + (i + 1) + ' tipo "' + tipo + '" sin orden');
			});
		});
		return res;
	})()` })).result.value || [];
	errores.push(...malOrden);
	const ok = errores.length === 0;
	if (!ok) fallas++;
	console.log((ok ? '  ok    ' : '  FALLA ') + p + (ok ? '' : '\n         -> ' + [...new Set(errores)].slice(0, 3).join('\n         -> ')));
}
proc.kill();
console.log(fallas ? `${fallas} de ${pantallas.length} pantallas con errores de JavaScript` : `OK: ${pantallas.length} pantallas abren sin errores de JavaScript`);
process.exit(fallas ? 1 : 0);
