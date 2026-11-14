# Cómo escribir una migración en miPDV

Léelo antes de crear una migración nueva. Son **nueve reglas**; la regla 0 define el
formato fundamental y **no es negociable**; la 1 es la que más rendimiento da y la
que más se olvida; la 6 es la que tumbó el servidor cuando no existía; la 8 decide
si la migración llega a ejecutarse o desaparece sin dejar rastro; la 9 decide si el
otro software que comparte la base llega a recibirla.

El panel **Bitácora de Migraciones** (`/index.php/BitacoraMigracion`) responde, para
cada migración y cada tenant, la pregunta *"¿hace falta ejecutarla aquí?"*. No lo
adivina: le pregunta al catálogo de PostgreSQL si los objetos que la migración crea
ya están. Estas convenciones son lo que hace posible esa respuesta.

> **Para operar el panel y leer la bitácora**, la guía es
> [`PANEL_BITACORA.md`](PANEL_BITACORA.md). Este archivo es la del autor de la
> migración; ese otro es la del que la ejecuta.

---

## 0. Usa el formato nativo de CodeIgniter 3; nunca archivos `.sql` independientes

> **Regla incondicional**: una migración **existe** solo como archivo PHP dentro de
> `application/migrations/`. Nunca se ejecuta, versiona ni verifica a partir de
> un `.sql` suelto en carpetas externas como `/sql/`.

### Requisitos técnicos mínimos

1. **Archivo PHP en la carpeta designada por CI3**
   ```
   application/migrations/YYYYMMDDHHMMSS_nombre_descriptivo.php
   ```

2. **Estructura de clase y métodos obligatorios del framework**
   - La clase debe heredar de `CI_Migration` (nativa de CI3).
   - Debe implementar **exactamente** el método público `up()` (contenido de la
     migración).
   - Debe implementar **exactamente** el método público `down()` (rollback, aunque
     el panel Bitácora solo lea `up()`).
   - Todas las sentencias SQL se ejecutan desde `up()` mediante
     `$this->db->query(...)` (driver de base de datos del framework).

3. **Patrón de nomenclatura CI3 obligatorio**
   - Prefijo numérico: 14 dígitos `YYYYMMDDHHMMSS`. Es la **versión** que el
     framework compara contra la tabla `migrations.version`.
   - Sufijo textual: nombre en `snake_case`, todo minúsculas, sin acentos ni
     espacios, descriptivo del cambio.
   - Nombre de clase: `Migration_<nombre_descriptivo>` (primera letra mayúscula,
     prefijo `Migration_`, resto igual que el sufijo del archivo).
   - **Invariante CI3**: el sufijo del archivo DEBE coincidir con el nombre de
     clase después de `Migration_`; si no coincide, CI3 no detecta la migración
     y queda invisible para `CI_Migration` y el hook automático.

4. **Integración con la funcionalidad nativa de ejecución de CI3**
   - AutoMigrationHook lee la misma lista que `CI_Migration::find_migrations()`,
     que solo escanea `APPPATH . 'migrations/*'`. Un archivo fuera de esa carpeta
     no forma parte de la cadena de versionado.
   - La Bitácora de Migraciones calcula MD5, hash drift y metadatos SOLO sobre
     los archivos PHP de esa carpeta.
   - El versionado `migrations.version` avanza solo cuando CI3 llama a
     `Migration::version()` con el timestamp del archivo PHP.

### Qué pasa con los archivos `.sql` de `sql/`

La carpeta `/sql/` y sus archivos `.sql` son **bosquejos / respaldos / documentación
humana**. No son migraciones. Si necesitas aplicar el cambio que hay en un `.sql`:

1. Crea una migración PHP nueva en `application/migrations/YYYYMMDDHHMMSS_<xxx>.php`.
2. Copia el SQL como cadenas dentro de `$this->db->query(...)` en `up()`.
3. Aplica las reglas 1-5 del resto de este documento sobre esa migración PHP.
4. (Opcional) Deja una línea de comentario en el `.sql` original apuntando al
   archivo PHP de migración que es la fuente oficial.

### Por qué

- **Idempotencia de secuencia**: CI3 lleva la contabilidad de *cuál versión está
  instalada* en `migrations.version`. Un `.sql` suelto no actualiza esa tabla y,
  por lo tanto, o se ejecuta dos veces, o no se ejecuta cuando toca, o rompe el
  orden relativo entre migraciones.
- **Multi-tenant automático**: AutoMigrationHook aplica *los mismos archivos* a
  todos los tenants en base a la lista escaneada de CI3. Un `.sql` suelto en un
  servidor no se replica a los demás tenants.
- **Trazabilidad**: Bitácora de Migraciones calcula MD5, prioridad, huérfanas y
  MD5 drift SOLO leyendo el directorio `application/migrations/`.
- **Arquitectura compartida con CI3**: El framework no conoce el directorio
  `/sql/`. No hay forma de que `$this->migration->latest()` llegue a él.

---

## 1. Marca la versión dentro del cuerpo de funciones y triggers

**Toda migración que haga `CREATE OR REPLACE FUNCTION` debe llevar esta línea dentro
del cuerpo:**

```sql
CREATE OR REPLACE FUNCTION public.f_addptocomprado() RETURNS trigger
LANGUAGE plpgsql AS $$
-- @migracion 20260902120000
DECLARE
    ...
```

El número es **el timestamp del propio archivo de migración**. Nada más.

### Por qué

Una columna se verifica mirando si existe. Una función no: `CREATE OR REPLACE` pisa
el cuerpo, así que *"existe"* no dice **cuál** está instalada.

En este proyecto eso no es teórico — `f_addptocomprado` se reemplaza en **seis**
migraciones distintas. Sin marcador, el panel no puede distinguir entre "está la
versión de septiembre" y "está la de junio con el bug del kardex multi-bodega".

### Por qué DENTRO del cuerpo y no en un comentario PHP

PostgreSQL guarda el cuerpo literal en `pg_proc.prosrc`, comentario incluido, así que
se puede leer de vuelta:

```sql
SELECT proname, substring(prosrc from '@migracion[[:space:]:]+[0-9]{14}')
  FROM pg_proc WHERE proname = 'f_addptocomprado';
```

Va dentro del cuerpo a propósito: **sobrevive a cualquier reescritura del SQL antes de
ejecutarlo**, incluido `sanitizeSqlForAppRole()`. Un comentario PHP se queda en el
archivo y nunca llega a la base.

### Qué muestra el panel según eso

| Situación | Badge | Qué hacer |
|---|---|---|
| El marcador de la base coincide con el del archivo | ✅ **Ya está** | Nada |
| Marcadores distintos (`base: 20260807… · archivo: 20260902…`) | 🔶 **Otra versión** | Ejecutarla |
| La base no tiene marcador (función anterior al versionado) | 🔁 **Reaplicable** | Reaplicar es seguro |
| Sin marcador en el archivo | 🔁 **Reaplicable** | Agregar el marcador |

---

## 2. Los nombres de objetos, literales

```php
// ✅ el panel puede verificarlo
$this->db->query("CREATE UNIQUE INDEX IF NOT EXISTS ux_venta_id_log_venta ON venta(id_log_venta)");

// ✅ también: propiedad de clase con valor literal (se resuelve)
private $indice = 'ux_venta_id_log_venta';
$this->db->query("CREATE UNIQUE INDEX IF NOT EXISTS {$this->indice} ON venta(id_log_venta)");

// ❌ el panel no puede saber el nombre -> queda en "❔ Revisar a mano"
$nombre = $this->calcularNombreIndice();
$this->db->query("CREATE UNIQUE INDEX IF NOT EXISTS {$nombre} ON venta(id_log_venta)");
```

Cuando en la posición del nombre queda una interpolación sin resolver, **la migración
entera pasa a ❔ Revisar a mano**. Es deliberado: ante la duda nunca se dice "ya
aplicada", porque un falso *"no hace falta"* hace que te saltes una migración
necesaria, mientras que un falso *"revisar"* solo cuesta una mirada.

El SQL en `base64_decode()` sí se lee — se decodifica antes de analizar.

---

## 3. Idempotente siempre

```sql
CREATE TABLE IF NOT EXISTS ...
ALTER TABLE x ADD COLUMN IF NOT EXISTS ...
CREATE INDEX IF NOT EXISTS ...
CREATE OR REPLACE FUNCTION ...
DROP TRIGGER IF EXISTS x ON t;   -- antes de recrearlo
INSERT ... ON CONFLICT DO NOTHING
```

Una migración se puede reintentar: el hook la reejecuta tras un fallo de red, y desde
el panel se puede relanzar a mano. Si no es idempotente, el segundo intento revienta.

**Compatibilidad: el VPS de producción corre PostgreSQL 10.** Nada por encima de eso.
En particular:

- `ADD COLUMN` con `DEFAULT` **reescribe la tabla entera** y toma `ACCESS EXCLUSIVE`
  (el *fast default* llegó en PG 11). En tablas grandes, eso es una caja detenida.
- `ADD COLUMN IF NOT EXISTS` y `to_regclass(text)` → 9.6
- `ON CONFLICT` y `CREATE INDEX IF NOT EXISTS` → 9.5
- `LOCK TABLE` solo funciona dentro de una transacción.

> **Ojo con lo que "idempotente" no significa.** Que una sentencia se pueda repetir sin
> romper nada **no quiere decir que sea gratis**: `ALTER TABLE ... ADD COLUMN IF NOT
> EXISTS` toma `ACCESS EXCLUSIVE` **aunque la columna ya exista**, y con la cola FIFO de
> PostgreSQL eso bloquea a todo lo que llegue después. En una migración está bien —
> corre una vez. Repetida en cada petición, tumba el servidor. Ver la **Regla 6**.

> **Y tampoco significa que se haya aplicado.** Una sentencia idempotente que **falla**
> no se distingue de una que funcionó si nadie mira el resultado: con `db_debug = false`
> `query()` devuelve `FALSE` en vez de lanzar. La migración termina limpia, la versión
> queda grabada y no se reintenta nunca. Ver la **Regla 7**.

> **Y si la migración va a viajar a `update_pdv`, la idempotencia obliga dos veces.**
> El patrón `if (!existeColumna(...)) { ALTER TABLE ... ADD COLUMN ... }` es correcto
> aquí --el `if` protege la sentencia--, pero al aplanar el script para el otro sistema
> **ese `if` desaparece** y la sentencia queda desnuda: aplica bien la primera vez y
> falla la segunda. Escribe `IF NOT EXISTS` aunque el `if` ya esté. Ver la **Regla 9**.

---

## 4. No edites una migración ya aplicada

El panel guarda el MD5 del archivo cuando la ejecuta. Si después lo editas, la fila
aparece con **⚠ editado** — y con razón: los tenants que ya la corrieron tienen la
versión vieja, los nuevos tendrán la nueva. Es la causa clásica de *"en mi local
funciona pero en ese tenant no"*.

**Crea una migración nueva con timestamp mayor.** Si es una función, esa nueva lleva
su propio `-- @migracion`, y el panel marcará la anterior como ⏹ **Superada**.
Mayor **que la versión ya grabada**, no que el reloj: **Regla 8**.

Lo mismo aplica cuando una migración quedó marcada como aplicada **sin haber hecho su
trabajo**: corregir el archivo no la hace volver a correr, porque la versión ya está
grabada. Hace falta una nueva con timestamp mayor. Ver la **Regla 7**.

### La única excepción: una migración que falla siempre

Una migración nueva no puede arreglar una que **lanza** en algunos tenants: CI3 migra
en orden, así que la nueva nunca llega a correr ahí. En ese caso se corrige el mismo
archivo, con dos condiciones: que el cambio sea solo en la **verificación** (el SQL que
instala queda byte a byte igual, para que los tenants que ya la aplicaron no queden
distintos) y que la cabecera diga qué se cambió y por qué. El panel la mostrará con
**⚠ editado** en esos tenants; es esperado.

Caso real (21-09-2026): `20260819120000` buscaba `OLD.id_producto_maestro` en la
definición cruda de la función, y el cuerpo que ella misma instala lo nombra en un
comentario. Fallaba siempre después de aplicar su fix, y `ccerda_market_limp` quedó
trabado en `20260815120001` con 47 pendientes. Ver la Regla 7.

---

## 5. Solo la última versión de un objeto debe correr

Cuando dos migraciones reemplazan la misma función o trigger, ejecutar la vieja no es
inofensivo: **retrocede el objeto a una versión anterior**. El panel lo detecta
comparando los archivos entre sí — sin base de datos y sin necesitar marcador — y
marca las anteriores como ⏹ **Superada**, indicando cuál las reemplaza.

Hoy, en este repositorio:

```
20260813120000_fix_kardex_multi_bodega_8_triggers.php     la reemplaza 20260902120000
20260813230000_fix_costo_pack_multiplicar_cantidad.php    la reemplaza 20260819120000
20260826120000_replace_f_addptocomprado_detalle_stock.php la reemplaza 20260902120000
```

El filtro **"Solo lo que requiere acción"** las deja fuera a propósito.

> **Cuidado al llevar esto al envío a `update_pdv`.** "Superada" se decide por la
> función, pero una migración hace más de una cosa: `20260909220000` reemplaza
> `f_transferencia_bodega` --y ahí sí está superada-- pero además es la ÚNICA que crea
> `trg_f_transferencia_bodega`. Saltarla entera dejaba la función instalada y nada que
> la dispare. Para enviar, la regla correcta es: se salta sólo si TODO lo que entrega lo
> entrega también otra que sí va. Ver la **Regla 9.5**.

---

## 6. El DDL vive aquí. Dentro de una petición, nunca sin protección

El 08-09-2026 el servidor dejó de responder. Un `SELECT` de 22 minutos sobre
`esbodega`, un `ALTER TABLE` esperándolo, y una cola detrás del `ALTER` hasta ocupar
los 30 workers de php-fpm.

El `ALTER` no venía de una migración. Venía del **constructor de un modelo**:

```php
// application/models/Mentradabodega.php -- así estaba
$this->db->query("ALTER TABLE esbodega ADD COLUMN IF NOT EXISTS generar_guia_despacho BOOLEAN DEFAULT false");
```

Parece inofensivo — *"si no existe la crea; si existe, no hace nada"*. **No es así.**

### Lo que casi nadie espera

**`ALTER TABLE ... ADD COLUMN IF NOT EXISTS` toma `ACCESS EXCLUSIVE` aunque la columna
ya exista.** PostgreSQL necesita el lock para poder comprobarlo. Medido en PG 16 con
la columna ya presente:

```
A) SELECT largo sobre esbodega ............ retiene ACCESS SHARE
B) ALTER ... IF NOT EXISTS (no hace nada) . Lock, esperando a A
C) SELECT normal de un cajero ............. Lock, esperando a B
```

El punto **C** es el que mata: la cola de locks de PostgreSQL es **FIFO**, así que un
`ALTER` en espera bloquea a todo lo que llega después, aunque solo quiera leer. Una
consulta lenta + un `ALTER` que no hace nada = tabla congelada.

Y como cada petición web reconstruye el modelo, cada petición manda otro `ALTER` a la
misma cola.

Medido con la tabla retenida por una consulta larga:

| Sentencia | |
|---|---|
| `ALTER TABLE ... ADD COLUMN IF NOT EXISTS` (ya existe) | **se bloquea** |
| `ALTER TABLE ... ALTER COLUMN ... DROP NOT NULL` | **se bloquea** |
| `CREATE TABLE IF NOT EXISTS` (ya existe) | pasa libre |
| `CREATE INDEX IF NOT EXISTS` (ya existe) | pasa libre |
| `SELECT` normal (referencia) | pasa libre |

**Solo los `ALTER TABLE` son el problema.** Los `CREATE ... IF NOT EXISTS` son baratos.

### Y las guardas `static` no sirven

```php
private static $columnasAseguradas = false;   // esto NO evita nada
```

Un `static` es del **proceso**. Con php-fpm cada petición es un proceso limpio, así
que la guarda vuelve a `false` en cada una. Doce constructores del proyecto tenían
esa guarda y las doce eran decorativas.

### La regla

> **El DDL va en `application/migrations/`.** Esa es la respuesta por defecto y casi
> siempre la única que hace falta.

Si aun así hay que asegurar algo desde el código — porque el modelo no puede asumir
que la migración ya corrió en ese tenant — se usa el helper, **nunca `$this->db->query()`
directo**:

```php
// una columna
mipdv_asegurar_columna($this->db, 'esbodega', 'generar_guia_despacho', 'BOOLEAN DEFAULT false');

// un lote (lo que antes era un ensureSchema() con veinte sentencias)
mipdv_asegurar_esquema($this->db, 'sorteo', array(
    "CREATE TABLE IF NOT EXISTS sorteo (...)",
    "ALTER TABLE sorteo ADD COLUMN IF NOT EXISTS nombre TEXT",
));

// DDL que no es "asegurar una columna", o un relleno de datos de una sola vez
mipdv_ddl_una_vez($this->db, "ALTER TABLE x ADD CONSTRAINT ...", 'etiqueta');
mipdv_ddl_una_vez($this->db, "UPDATE x SET y = ... WHERE y IS NULL", 'relleno_y', true);
```

El helper se autocarga (`$autoload['helper']` incluye `'esquema'`), así que no hace
falta `$this->load->helper()`.

### Qué hace el helper

1. **¿Ya se resolvió?** → no hace nada, ni una consulta. Es el caso normal.
2. Si no: **pregunta al catálogo** — una lectura que **no toma locks** — y descarta lo
   que ya está. Normalmente se descarta todo.
3. Lo que quede se ejecuta con `lock_timeout`, y solo en **un** worker
   (`pg_try_advisory_lock`), para que al vencer la caché los 30 procesos de php-fpm no
   manden el mismo lote a la vez.

Reconoce y descarta contra el catálogo: `ADD COLUMN IF NOT EXISTS`,
`ALTER COLUMN ... DROP/SET NOT NULL`, `DROP DEFAULT`, `SET DEFAULT x`, `TYPE x`, y
`CREATE TABLE/INDEX/SEQUENCE/VIEW IF NOT EXISTS`. Cualquier otra cosa —varias columnas
en una sentencia, `TYPE ... USING`, SQL armado con variables— **no se analiza y se
ejecuta tal cual**. Ante la duda ejecuta: nunca se salta una sentencia por adivinar.

`mipdv_asegurar_esquema()` **se reaplica solo cuando el lote cambia**: la huella de la
caché es el hash del propio array. Agregas una sentencia y se vuelve a aplicar sin que
haya que tocar ninguna versión a mano.

### Cuándo NO usar el helper

- **Un interruptor, no un esquema.** `ALTER TABLE producto ENABLE/DISABLE TRIGGER` no
  es idempotente ni cacheable: tiene que ejecutarse cada vez. Va directo, y lo protege
  el `lock_timeout` global.
- **Datos de negocio.** El helper es para DDL. Un `UPDATE` que corrige datos según
  reglas de negocio no va aquí; si es un relleno de una sola vez, `mipdv_ddl_una_vez()`
  con el cuarto parámetro en `true`.

### La válvula global

`DbManager` pone `lock_timeout` en cada conexión de tenant:

```php
$config['db_lock_timeout_ms'] = 10000;   // config.php, 0 = desactivado
```

Una sentencia que no consigue su lock en 10 s falla, en vez de quedarse en la cola
bloqueando a todo el que llegue detrás. Convierte *"el servidor no responde"* en *"una
petición falló"*. En un POS los bloqueos legítimos duran milisegundos: solo muerde en
el caso patológico.

### Lo que se convirtió

290 sentencias DDL que salían en cada petición, en 39 archivos. Las dos peores:

| Dónde | Qué pasaba |
|---|---|
| `Mentradabodega.php` | 4 `ALTER` en el constructor, en cada petición. Es el que tumbó el servidor |
| `Venta.php` → `ensureVentaCotizacionEstadoSchema()` | La llama `grabarNewVenta()`: **cada venta tomaba `ACCESS EXCLUSIVE` sobre `cotizacion` cuatro veces**, más un `UPDATE` que recorría la tabla entera |

Medido sobre los cinco `ensure*Schema()` reales de `grabarNewVenta()`:

| | Consultas por venta | De ellas DDL | `ALTER TABLE` |
|---|---|---|---|
| Antes | **39** | 26 | **4** |
| Primera venta tras un deploy | 11 | 0 | 0 |
| El resto de las ventas | **0** | 0 | 0 |

El detalle completo, en [`../../docs/ddl_en_peticiones_locks.md`](../../docs/ddl_en_peticiones_locks.md).

### Si vuelve a pasar

```sql
SELECT pid, state, wait_event_type, pg_blocking_pids(pid), left(query, 80)
  FROM pg_stat_activity WHERE state <> 'idle' ORDER BY query_start;
```

Se corta **el primero de la cadena** (el que no tiene `pg_blocking_pids`), no los que
esperan: matar a los que esperan deja al bloqueador intacto y la cola se rehace.

---

## 7. Una migración que no pudo hacer su trabajo tiene que **lanzar**

El 09-09-2026, al guardar una transferencia de bodega:

```
ERROR: record "new" has no field "id_usuario"
CONTEXT: SQL statement "SELECT coalesce(NEW.id_usuario, 0)"
         PL/pgSQL function f_transferencia_bodega() line 26
```

La migración que arregla justamente eso ya estaba aplicada. Y seguía apareciendo el
error — en ese tenant, y en todos los demás al cambiarse de empresa.

### Por qué no se reintentaba nunca

La migración hacía esto:

```php
$this->db->db_debug = false;          // en up(), para no cortar la petición

try {
    $this->db->query($stmt);
    $okCount++;                        // <-- sube pase lo que pase
} catch (Throwable $e) {
    throw $e;                          // <-- nunca entra aquí
}
```

**Con `db_debug = false`, `query()` no lanza: devuelve `FALSE`.** El `catch` no se
dispara, el contador sube igual y la migración escribe en el log
*"OK. statements ejecutados=N"* sin haber aplicado nada.

Y ahí está lo que lo vuelve permanente — `CI_Migration::version()`:

```php
call_user_func($migration);              // llama a up()
$current_version = $number;              // y a la línea siguiente
$this->_update_version($current_version);
```

**No mira lo que devolvió `up()`.** Solo una **excepción** evita que se registre la
versión. Como `up()` volvió limpio, la versión quedó grabada, y desde ese momento la
base ya no está "atrasada": ni el próximo login, ni el relogin, ni otro tenant la
vuelven a ejecutar.

> Un `try/catch` alrededor de `$this->db->query()` con `db_debug = false` **no
> protege nada**. Es código muerto.

### La regla

> Una migración que no pudo hacer su trabajo **lanza**. Lo que no puede es terminar
> en silencio: eso registra la versión y cierra la puerta para siempre.

Dos formas de cumplirla, y basta con una:

**a) Mirar el retorno de cada `query()`**

```php
$resultado = null;
$errorSql  = '';
try {
    $resultado = $this->db->query($stmt);
} catch (Throwable $e) {
    $resultado = false;
    $errorSql  = $e->getMessage();
}
if ($resultado === false) {
    if ($errorSql === '' && method_exists($this->db, 'error')) {
        $err = $this->db->error();
        $errorSql = is_array($err) && !empty($err['message']) ? $err['message'] : 'sin detalle';
    }
    throw new RuntimeException("$tag: statement [$idx] no se aplicó: $errorSql");
}
$okCount++;
```

**b) Comprobar el resultado en el catálogo antes de darse por aplicada**

Es lo razonable cuando la migración tiene caminos tolerantes a propósito
(`CREATE INDEX CONCURRENTLY` con respaldo, `DROP` con fallback): una sola
comprobación al final cubre todos los caminos.

```php
$q = $this->db->query(
    "SELECT pg_get_functiondef(p.oid) AS def
       FROM pg_proc p JOIN pg_namespace n ON n.oid = p.pronamespace
      WHERE n.nspname='public' AND p.proname='f_transferencia_bodega' LIMIT 1");
$def = ($q && method_exists($q, 'row')) ? $q->row() : null;

if (!$def || preg_match('/NEW\.id_usuario/i', (string) $def->def)) {
    throw new RuntimeException(
        "$tag: la función SIGUE leyendo NEW.id_usuario después del fix. " .
        "No se marca como aplicada: se reintenta en el próximo login.");
}
```

**Lanzar no tumba la petición del usuario.** `AutoMigrationHook` envuelve la ejecución
en su propio `try/catch`, registra la excepción y sigue. Lo único que cambia es que la
versión **no** se graba — así el hook la vuelve a intentar en el próximo
`login/postloginwarmup` o `login/relogin`, con un freno progresivo (30 s, 1, 2, 5, 10,
15 min) que se reinicia en cuanto hay avance.

### `return;` en `up()` significa "no había nada que hacer aquí"

Nunca "no se pudo". Un `return` deja la migración marcada como aplicada, igual que si
hubiera funcionado.

| | |
|---|---|
| `return;` legítimo | *este tenant no tiene esas tablas* — no usa el módulo |
| `return;` que hay que cambiar por `throw` | *faltan las tablas que necesito*, *el split salió vacío*, *el DDL falló* |

Si hay un `return` en `up()`, que quede escrito al lado por qué es legítimo.

### Y comprueba que quedó **un solo** trigger

Al reemplazar un trigger no basta con `DROP TRIGGER IF EXISTS <el nombre canónico>`.
Si en ese tenant se llama distinto — parche viejo, creación a mano — el `DROP` no lo
encuentra, el `CREATE` añade el nuevo, y quedan **dos** apuntando a la misma función.

Eso no falla: **corrompe**. Cada transferencia mueve el doble de stock y escribe el
kardex dos veces. Medido: una transferencia de 1 unidad dejó la bodega origen en 8 en
vez de 9, y 4 filas de kardex en vez de 2.

Se borra por **la función que llaman**, no por el nombre:

```sql
DO $limpia_trg$
DECLARE r record;
BEGIN
    FOR r IN
        SELECT t.tgname
          FROM pg_trigger t
          JOIN pg_class  c ON c.oid = t.tgrelid
          JOIN pg_namespace n ON n.oid = c.relnamespace
          JOIN pg_proc   p ON p.oid = t.tgfoid
         WHERE NOT t.tgisinternal
           AND n.nspname = 'public'
           AND c.relname = 'detalle_transferencia_bodega'
           AND p.proname = 'f_transferencia_bodega'
    LOOP
        EXECUTE format('DROP TRIGGER IF EXISTS %I ON public.detalle_transferencia_bodega', r.tgname);
    END LOOP;
END
$limpia_trg$;
```

Y después se cuenta: si no queda exactamente **1**, se lanza.
(`20260826120000` ya lo hacía para `detalle_compra` — de ahí salió el patrón.)

### Cuidado con el regex que descarta comentarios

Las migraciones que parten un bloque SQL en sentencias saltan las que son solo
comentario. Este regex, que estaba en tres de ellas, **se comía la sentencia entera**:

```php
preg_match('/^\s*(--.*|\/\*.*\*\/)\s*$/s', $stmt)   // MAL
```

Con el flag `/s` el `.` también casa saltos de línea, así que cualquier sentencia que
empezara con una línea `--` casaba completa y se descartaba en silencio. Le pasó al
paso 0 de `20260822120000` — el `DO` que crea `toma_inventario_bkp.lotes_json` iba
detrás de 40 líneas de comentario y **nunca se ejecutó en ningún tenant**. La
migración quedó marcada como aplicada igual.

Lo correcto es quitar los comentarios y mirar si queda algo:

```php
$sinComentarios = preg_replace(array('/\/\*.*?\*\//s', '/--[^\n]*/'), '', $stmt);
if (trim((string) $sinComentarios, " \t\n\r\0\x0B;") === '') {
    $skipCount++;
    continue;
}
```

### Y el post-check mira el código, no los comentarios

`pg_get_functiondef()` devuelve el cuerpo tal cual, **comentarios incluidos**. Si el
post-check busca un patrón prohibido y el cuerpo nuevo lo menciona en un comentario
(*"-- ya no usa NEW/OLD.id_producto_maestro"*), la migración falla después de hacer
bien su trabajo, y en el login siguiente el pre-check la toma por rota y repite. Quita
los comentarios antes de buscar, con el mismo regex de arriba.

### Si una migración ya quedó marcada sin haber hecho nada

Corregir el archivo **no basta**: `AutoMigrationHook` solo ejecuta cuando la versión
de la base va por detrás, y esa versión ya está grabada. Hace falta una **migración
nueva con timestamp mayor** (Reglas 4 y 8), que además empiece comprobando el catálogo
para no repetir trabajo donde ya está bien.

Ejemplo en este repositorio: `20260909220000_reaplicar_fix_f_transferencia_bodega.php`
reaplica lo que `20260909180000` dio por hecho.

---

## 8. El timestamp sale de la carpeta, no del reloj

Las reglas 4 y 7 dicen *"crea una migración nueva con timestamp mayor"*. Mayor **que
qué** es la parte que se olvida: no mayor que la hora actual, ni mayor que el archivo
que tenías abierto. Mayor que **la versión que ya está grabada en la base**.

### Cómo se elige la versión objetivo

`application/config/migration.php` no fija un número a mano: lo calcula recorriendo la
carpeta y quedándose con el **máximo**.

```php
$config['migration_version'] = call_user_func(function () {
    $piso = 20260822120000;          // nunca se baja de aquí
    $max  = $piso;
    foreach (@scandir(APPPATH . 'migrations/') as $archivo) {
        if (!preg_match('/^(\d{14})_.+\.php$/', (string) $archivo, $m)) continue;
        if ((int) $m[1] > $max) { $max = (int) $m[1]; }
    }
    return $max;
});
```

Y `CI_Migration::version($objetivo)`, subiendo, ejecuta **solo** las migraciones que
cumplen las dos condiciones a la vez:

```
numero > version_actual   &&   numero <= objetivo
```

Una migración con número **por debajo** de la versión ya grabada no entra en ese
rango. No se ejecuta, **no da error** y **no queda registrada**. Simplemente no pasa
nada — que es el peor modo de fallo que hay, porque no deja rastro que mirar.

### El caso real (10-09-2026)

Esa madrugada se crearon dos migraciones. La primera se numeró `20260910100000` y se
aplicó en el acto: el log la muestra corriendo a las 21:39 y a las 22:40. Un rato
después se creó la segunda tomando **la hora UTC del momento**, `20260910040000`, sin
mirar qué versión ya estaba puesta.

| | |
|---|---|
| `migration_version` (máximo de la carpeta) | 20260910100000 |
| versión ya grabada en la base | 20260910100000 |
| la migración nueva | 20260910040000 |
| ¿cumple `numero > actual`? | **no** |

Se habría saltado en silencio: nada en el log, nada en el panel, la función sin
reemplazar, y la impresión de que el trabajo estaba hecho. Se detectó comparando los
dos archivos a mano, no porque el sistema avisara. Se corrigió renumerando a
`20260910110000`.

### La regla

Antes de elegir el número, **mira la carpeta**:

```bash
ls -1 application/migrations/*.php | tail -1
```

El timestamp nuevo va por encima de ese. El reloj no interviene: un `20260910040000`
creado a las 04:00 es válido *si* nada superior se aplicó todavía, e inútil en cuanto
algo superior existe.

> Cuidado con dos migraciones de la misma jornada. Es cuando más fácil pasa: un
> archivo con la hora local, otro con la UTC, otro con un número redondo elegido a
> ojo — y el orden de los números deja de tener que ver con el orden en que se
> escribieron.

### Si ya la creaste con el número equivocado

Renumérala **antes** de que se aplique nada, y actualiza la marca en los **tres**
sitios donde vive:

| dónde | qué |
|---|---|
| el docblock | `* Migracion: <nuevo>` |
| la constante | `const MARCA = '<nuevo>';` |
| dentro del cuerpo de la función | `-- @migracion <nuevo>` (Regla 1) |

Si los tres no coinciden, la comprobación de catálogo de la Regla 7 no encuentra su
marca, lanza, y la migración se reintenta en cada login para siempre.

El archivo viejo **no se deja en `application/migrations/`**: dos migraciones
definiendo el mismo objeto es exactamente lo que previene la Regla 5. Sácalo de la
carpeta (a `_respaldos/`, por ejemplo) o bórralo.

---
---

## 9. Si la migración tiene que llegar al otro sistema, escríbela para que se pueda aplanar

Hay un segundo software que comparte estas mismas bases y **no ejecuta PHP**: lee la
tabla `update_pdv` de la intranet y corre el texto SQL que encuentra en `mi_script`.
Para que reciba un cambio de esquema, alguien tiene que dejarle ese SQL ahí.

El panel lo hace solo: lee el `up()` de la migración con el tokenizador de PHP, saca
el SQL, lo parte en sentencias y manda cada una a
`api/UpdatePdv/guardar`. Pero **sólo puede leer lo que está escrito
de forma que se pueda leer sin ejecutar PHP**. Esta regla es qué escribir para que eso
ocurra, y qué hacer cuando no se puede.

> Esta regla **no cambia** la Regla 0. La migración sigue siendo el archivo PHP, y es
> lo único que se ejecuta en los tenants de miPDV. Lo que viaja a `update_pdv` es un
> **reflejo** suyo, material de envío, y nunca al revés.

---

### 9.1 Una sentencia por fila. No es una preferencia, es la única forma correcta

El consumidor ejecuta cada fila de `update_pdv` así
(`application/models/Mupdate.php`, `runPendingUpdates`):

```php
$okExec = $this->db->simple_query($elScript);
```

**Todo el script en una sola llamada.** PostgreSQL trata un mensaje con varias
sentencias como **una transacción implícita**: si la tercera falla, se deshacen también
la primera y la segunda, que habían funcionado.

Y lo que lo vuelve grave: `Mupdate::isIgnorableUpdateError()` considera ignorables los
errores de "ya existe" —

```php
if (preg_match('/already\s+exists/i', $message) && preg_match('/relation\s+"[^"]+"/i', $message)) return true;
```

— así que la fila termina en `correcto = true`, `msg_error = 'OK'`, `ejecutado = true`,
**con la base sin tocar**. Un fallo mudo disfrazado de éxito. Nadie va a ir a buscarlo.

**Reproducido el 26-09-2026 contra PostgreSQL 16.** Cuatro sentencias, con
`kardex_reconstruccion_detalle` ya existente en el destino:

```
CREATE TABLE                                         ← kardex_reconstruccion, correcta
ERROR:  relation "kardex_reconstruccion_detalle" already exists
→ kardex_reconstruccion existe: 0                    ← la primera también se deshizo
```

Las mismas cuatro, una por fila: **3 de 4 aplican** y la que falla se ve sola, con su
mensaje, en su renglón de `update_pdv`.

Por eso el panel parte cada script y manda **una sentencia por envío**, cada una con su
correlativo. No tienes que hacer nada para que eso ocurra —
lo hace `MigracionSqlExtractor::partirEnSentencias()`, que respeta el dollar-quoting,
así que el cuerpo `$$ … $$` de una función con cuarenta punto y coma dentro sigue
siendo **una** sentencia.

Lo que sí tienes que saber: **se pierde la atomicidad**. Tu migración puede quedar a
medias en el otro sistema. Se acepta a cambio de que el fallo sea visible, y por eso la
9.3 (idempotencia) deja de ser una buena costumbre y pasa a ser obligatoria.

---

### 9.2 Qué sabe leer el extractor, y qué no

El extractor entra al `up()`, lo sigue a los métodos privados que llame, y resuelve
todo lo que se puede saber **leyendo**. Esta lista es exacta: está medida sobre las 133
migraciones de la carpeta.

#### ✅ Lo que se lee bien

```php
// 1. Heredoc / nowdoc literal — la forma preferida
$this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS public.x (id serial PRIMARY KEY)
SQL
);

// 2. Cadena literal, simple o doble
$this->db->query('ALTER TABLE public.x ADD COLUMN IF NOT EXISTS y integer');

// 3. Propiedad de clase con valor fijo (también interpolada dentro del SQL)
private $indice = 'ux_venta_id_log_venta';
$this->db->query("CREATE UNIQUE INDEX IF NOT EXISTS {$this->indice} ON venta(id_log_venta)");

// 4. Constante de clase
const NOMBRE_USUARIO = 'api_pedidos';
$this->db->query("INSERT INTO usuario (nombre_usuario) VALUES ('" . self::NOMBRE_USUARIO . "')");

// 5. Método SIN argumentos cuyo cuerpo es un único return de literal
private function sqlAnularCompra()
{
    return <<<'SQL'
CREATE OR REPLACE FUNCTION public.anular_compra() RETURNS integer AS $a$
-- @migracion 20260923100000
BEGIN RETURN 0; END
$a$ LANGUAGE plpgsql
SQL;
}
$this->ejecutar($this->sqlAnularCompra(), 'función anular_compra');

// 6. Un helper propio, se llame como se llame, y en la posición que sea
private function ejecutar($sql, $etiqueta) { return $this->db->query($sql); }
private function createIndex($tabla, $sql) { return $this->db->query($sql); }    // el SQL va en el 2º argumento
private function contar($sql)              { return $this->ejecutar($sql, 'x'); } // encadenado: también se resuelve

// 7. foreach sobre un arreglo literal: se DESENROLLA, una sentencia por fila
private $rows = [
    ['cliente.ver',    'Clientes (ver)',    'Mantención', 2301],
    ['cliente.editar', 'Clientes (editar)', 'Mantención', 2302],
];
foreach ($this->rows as $r) {
    $this->db->query(
        'INSERT INTO permisos_menu (clave, descripcion, grupo, orden) VALUES (?, ?, ?, ?)',
        [$r[0], $r[1], $r[2], (int) $r[3]]
    );
}

// 8. foreach con clave => valor interpolados
private $columnas = array(
    'descuento_pct'  => 'numeric(5,2)',
    'descuento_neto' => 'numeric(18,4)',
);
foreach ($this->columnas as $columna => $definicion) {
    $this->db->query("ALTER TABLE public.preventa ADD COLUMN IF NOT EXISTS {$columna} {$definicion}");
}

// 9. Los ? de CodeIgniter, si los valores son constantes: se sustituyen
$this->db->query('INSERT INTO condicion_pago (id, nombre) VALUES (?, ?)', array(1, 'Contado'));

// 10. escape() / escape_str() / escape_identifiers() con argumento legible
$comentario = $this->db->escape($this->comentarios[$columna]);
$this->db->query("COMMENT ON COLUMN public.x.{$columna} IS {$comentario}");
```

#### ❌ Lo que NO se puede leer, y por qué

```php
// A. SQL leído de un archivo externo
$sql = file_get_contents($sqlPath);          // el contenido no está en el .php
$this->runPgSqlScript($sql);

// B. SQL que pasa por un normalizador antes de ejecutarse
$norm = $this->splitStatementsPostgresSafe($sql);
$this->db->query($norm);                     // el texto final no existe hasta ejecutar

// C. Nombres que salen de una consulta al catálogo
foreach ($triggersEncontrados as $tn) {
    $this->db->query('DROP TRIGGER ' . $this->db->escape_identifiers($tn) . ' ON public.x');
}

// D. Valores que dependen de lo que haya en la base
$unica = (int) $fila->id_sucursal;           // salió de un SELECT
$this->db->query('UPDATE public.merma SET id_sucursal = ' . $unica . ' WHERE …');

// E. Query Builder (insert/update/where) en vez de query()
$this->db->where('id', 1);
$this->db->update('etiqueta_zpl', $payload); // no hay texto SQL que leer
```

> **Los `SELECT` no cuentan.** Un guard —
> `SELECT count(*) FROM information_schema.columns WHERE …` — es una **pregunta**, no una
> acción. El extractor los omite aunque no pueda resolverlos, y no bloquean nada. Sólo
> los `CREATE / ALTER / DROP / INSERT / UPDATE / DELETE / COMMENT / GRANT / DO` viajan.

**Si tu migración cae en A–E, no es un error tuyo**: a veces el trabajo *es* dinámico
y no hay forma honesta de aplanarlo. Para eso está la 9.4.

---

### 9.3 Idempotencia: ahora obliga dos veces

La Regla 3 ya pedía que toda sentencia se pudiera repetir. Al aplanar, hay un caso que
la rompe sin que se note, y es el patrón que este proyecto usa bien:

```php
// En el PHP esto es CORRECTO: el if protege la sentencia,
// y por eso el ALTER no lleva IF NOT EXISTS.
if (!$this->existeColumna('pago_ctacte_cliente', 'id_sucursal_intranet')) {
    $this->ejecutar('ALTER TABLE public.pago_ctacte_cliente ADD COLUMN id_sucursal_intranet integer', '…');
}
```

**Al aplanar, el `if` desaparece.** La sentencia queda desnuda: aplica bien la primera
vez y revienta la segunda con `column "id_sucursal_intranet" already exists`, y con
una sentencia por fila eso deja un renglón en error. Medido en `20260924100000`.

El extractor lo corrige solo — le agrega `IF NOT EXISTS` / `IF EXISTS` a las sentencias
de **una sola acción** y lo dice en la cabecera del script generado —, pero **escríbelo
tú igualmente**:

```php
// ✅ así, siempre
'ALTER TABLE public.x ADD COLUMN IF NOT EXISTS y integer'
'CREATE INDEX IF NOT EXISTS ix_x_y ON public.x (y)'
'DROP TRIGGER IF EXISTS trg_x ON public.x'
'CREATE TABLE IF NOT EXISTS public.x (…)'
```

Lo que el extractor **no** toca, y por tanto tienes que escribir bien tú:

- Un `ALTER TABLE … ADD COLUMN a int, ADD COLUMN b int` con varias acciones.
- Cualquier cosa indentada dentro de un `DO $$ … $$` o de un `EXECUTE`.

> **`CREATE TABLE IF NOT EXISTS` no arregla una tabla incompleta: se la salta entera.**
> Y el destino **no es una base nueva** — es la misma que usa el POS, donde tu tabla
> puede llevar tiempo creada con menos columnas. Si tu migración crea una tabla y
> después le pone índices, añade detrás un
> `ALTER TABLE … ADD COLUMN IF NOT EXISTS` por cada columna. Sin eso, el `CREATE` se
> salta y el `CREATE INDEX` muere con *"column … does not exist"*. Pasó con
> `kardex_reconstruccion_detalle`.

---

### 9.4 Cuando no se puede aplanar: el `.sql` gemelo

Si tu migración cae en los casos A–E de la 9.2, escribe un archivo `.sql` **con el
mismo nombre, al lado del `.php`**:

```
application/migrations/20260922120000_kardex_respaldo_y_reconstruccion.php
application/migrations/20260922120000_kardex_respaldo_y_reconstruccion.sql   ← éste
```

Si existe, el panel envía **ese archivo, literal**, sin analizar el PHP. No se ejecuta
nunca en miPDV: es sólo material de envío.

**No empieces en blanco.** En la vista previa de la migración hay un botón
**«Generar borrador .sql»** que escribe el archivo con todo lo que sí se pudo leer, en
orden, y un bloque marcado en el sitio exacto donde el PHP hacía algo irreproducible:

```sql
-- ---------------------------------------------------------------------
-- FALTA (linea 201 del .php):
--   'UPDATE public.merma SET id_sucursal_intranet = ' . $unica . ' WHERE …'
-- Escribe aqui el SQL equivalente, o borra este bloque si no hace falta.
-- ---------------------------------------------------------------------
```

**Mientras el archivo conserve una línea `-- FALTA (`, el panel se niega a enviarlo** y
te dice cuántos huecos quedan. Borrar esas líneas es el gesto con el que declaras que
lo terminaste. (La marca es exactamente `-- FALTA (` en mayúsculas; un comentario en
prosa que empiece con «falta…» no bloquea nada.)

#### Cómo se rellena un hueco

Lo dinámico casi siempre se puede expresar en SQL con un bloque `DO`, y **conservando
la decisión del PHP**, que es lo que importa. Ejemplo real: el PHP no elegía una
sucursal, comprobaba que hubiera **exactamente una** vigente y sólo entonces rellenaba;
con dos o más se rendía a propósito. Esa condición *es* la migración:

```sql
DO $rellena_merma$
DECLARE v_cuantas integer; v_unica integer;
BEGIN
    IF to_regclass('public.merma') IS NULL
       OR to_regclass('public.sucursal_cliente_vps') IS NULL THEN
        RETURN;                                    -- Regla 6: preguntar al catálogo
    END IF;

    SELECT count(DISTINCT id_sucursal_vps)::int, min(id_sucursal_vps)::int
      INTO v_cuantas, v_unica
      FROM public.sucursal_cliente_vps
     WHERE vigente = true AND coalesce(id_sucursal_vps, 0) > 0;

    IF coalesce(v_cuantas, 0) <> 1 THEN
        RAISE NOTICE 'Hay % sucursales vigentes: no se rellena.', coalesce(v_cuantas, 0);
        RETURN;                                    -- adivinar sería peor
    END IF;

    UPDATE public.merma SET id_sucursal_intranet = v_unica
     WHERE coalesce(id_sucursal_intranet, 0) = 0;
END
$rellena_merma$;
```

Un bloque `DO` es **una sola sentencia**, así que viaja entero en una fila.

---

### 9.5 Una migración hace más de una cosa. Dilo en su cabecera

La Regla 5 dice que sólo la última versión de un objeto debe correr. Para **ejecutar**
eso es correcto. Para **enviar**, aplicarlo a la migración entera es un error, y estuvo
a punto de costar caro:

`20260909220000` reemplaza `f_transferencia_bodega` — superada por `20260918110000`,
correcto. Pero **además es la única que crea `trg_f_transferencia_bodega`**, y la
posterior sólo toca la función. Saltarla dejaba la función instalada y **nada que la
dispare**: las transferencias dejarían de mover stock, sin un solo error a la vista.

El panel ya no salta una superada sin mirar: compara los objetos que entrega contra los
que entregan las demás y, si trae algo único —una tabla, una columna, un índice, un
trigger—, la envía igual. Las funciones no cuentan, porque la posterior las pisa y el
orden cronológico decide cuál queda.

**Lo que te toca a ti**: si tu migración reemplaza una función **y además** crea su
trigger, o una columna, o un índice, escríbelo en la cabecera del archivo. El panel lo
deduce del SQL, pero el que lea el archivo dentro de seis meses no.

---

### 9.6 Los topes, todos

| Qué | Tope | Dónde |
|---|---|---|
| `update_pdv.mi_script` | **200.000** caracteres en base64 ≈ 150 KB de SQL | `UpdatePdv::MAX_MI_SCRIPT_B64` |
| `update_pdv.migracion` | **20** caracteres, y es UNIQUE | columna `varchar(20)` |
| `update_pdv.descripcion` | **5.000** caracteres | columna `varchar(5000)` |
| Sentencias por migración | **999** (el correlativo es de 3 dígitos) | `versión.001` … `versión.999` |
| Peticiones a la intranet | **90 por minuto y por IP** | `ApiRateGuard`, `validar_cliente_ddos_max_requests` |

El correlativo se rellena con ceros — `20260822120000.001` — para que el orden
alfabético coincida con el numérico. Son 18 caracteres: caben.

Hoy, las 113 migraciones enviables dan **673 sentencias**; la más larga tiene 40 y la
más grande pesa 63 KB en base64 (`f_addconsolidado`). Hay holgura de sobra, pero si
escribes una función que se acerque a los 150 KB, pártela.

> **Ojo con la palabra `postgres` en minúscula.** El consumidor hace
> `str_replace("postgres", "ccerda", $script)` sobre el texto completo antes de
> ejecutarlo (`Mupdate::runPendingUpdates`). Es ciego: no distingue un nombre de rol de
> una palabra en un comentario. Hoy ninguna migración la trae en minúscula y el
> extractor avisa si alguna la trae. `PostgreSQL` con mayúscula es seguro.

---

### 9.7 Desde dónde se envía

La sincronización se hace **desde una sola base**, la que fija
`$config['sincronizacion_db']` (por omisión `ccerda_pruebas_sincronizacion`; se puede
cambiar con `MIPDV_SINCRONIZACION_DB` en el `.env`).

No es un permiso: el registro de "esto ya salió" vive en la base desde la que se envió.
Repartido entre tenants deja de responder la pregunta que existe para responder —
enviado desde A, en B aparece como pendiente y se manda dos veces. El panel comprueba a
qué base está conectada la sesión y, si no es ésa, muestra el motivo en vez del botón.

Antes de cada envío se le pregunta a la intranet (`api/UpdatePdv/existeMigracion`) si ya
tiene esa clave. Si la tiene, **se omite y se sigue**: ya está puesta, y reenviarla sólo
pisaría la fila. Reenviar es posible, pero como acto deliberado desde la vista previa.

---

### 9.8 Antes de dar por terminada una migración

Recórrelo entero. Cada punto viene de algo que falló de verdad.

- [ ] ¿El SQL está en heredoc/nowdoc o en cadena literal, y los nombres de objeto son
      literales? (Reglas 0 y 2, y es lo que hace que el extractor la lea.)
- [ ] ¿Toda sentencia lleva `IF NOT EXISTS` / `IF EXISTS`, aunque un `if` de PHP ya la
      proteja? (9.3 — el `if` no viaja.)
- [ ] Si creas una tabla y luego le pones índices, ¿añadiste un
      `ALTER TABLE … ADD COLUMN IF NOT EXISTS` por columna detrás del `CREATE`?
      (9.3 — la tabla puede existir ya, con otra forma.)
- [ ] ¿Ningún `ADD COLUMN … DEFAULT`? (Regla 3 — en PG 10 reescribe la tabla entera.)
- [ ] ¿La función lleva su `-- @migracion <versión>` dentro del cuerpo? (Regla 1.)
- [ ] ¿`verificar()` lanza si no pudo hacer su trabajo? (Regla 7.)
- [ ] ¿El timestamp es mayor que el máximo **grabado**, no que el reloj? (Regla 8.)
- [ ] **Abre la vista previa del panel** (botón `⇪` de su fila) y mira el SQL que
      saldría. Si dice «no se puede enviar», pulsa **Generar borrador .sql**, rellena
      los huecos y vuelve a mirar.
- [ ] Si tu migración crea un trigger, una columna o un índice **además** de reemplazar
      una función, dilo en la cabecera. (9.5.)
- [ ] ¿La palabra `postgres` en minúscula no aparece en el SQL? (9.6.)

Y lo único que no se puede comprobar leyendo: **aplica el script generado dos veces
seguidas contra una base de prueba.** Si la segunda pasada falla, no es idempotente, y
en el otro sistema eso es un renglón en error que alguien tendrá que ir a mirar.


## Cuándo se aplican las migraciones

**Al entrar y al reconectar la sesión. En ninguna otra petición.**

| Momento | Ruta | Qué pasa |
|---|---|---|
| Primera página tras hacer login | `Login/postLoginWarmup` | `menuFooter.php` la dispara por AJAX; ahí corre el hook |
| Cambio de empresa / reconexión | `Login/relogin` | El hook corre **dos veces**: automático al principio (tenant de origen) y a mano al final, ya con el tenant de destino. Ver abajo |
| A mano | Panel de bitácora → **▶ Ejecutar pendientes** | Una a una, con registro |
| **Despliegue** | **`php index.php despliegue esquema`** | **Todos los tenants, por consola, antes de que entre nadie** |
| Despliegue (legacy) | `MigrationUtil/deploy` | El script viejo |

### Por qué ahí y no en el POST del formulario de login

`AutoMigrationHook` corre en `post_controller_constructor`, o sea **antes** del método
del controlador. En el POST del login la sesión todavía no existe, y sin sesión no hay
`db_server` ni `bd_name` — el hook no sabría a qué tenant conectarse.

`postLoginWarmup` sí: llega por AJAX en la primera página después de entrar, con la
sesión ya establecida, y la espera no bloquea nada que el usuario esté mirando.

### Y por eso el relogin necesita una llamada a mano

Esa misma razón tiene una consecuencia que costó un día entero encontrar.

En un **relogin** la sesión sí existe cuando corre el hook — pero apunta al tenant que
estás **dejando**. El hook migra ese, `Login::relogin()` cambia la sesión al tenant de
destino, y al de destino **no lo migra nadie**. Tener `login/relogin` en la lista blanca
hacía parecer lo contrario.

Se vio el 09-09-2026 (log de las 15:47):

```
15:47:22  AutoMigrationHook          sesión = ccerda_pruebas_sincronizacion
          "Migrating up ... 20260909220000"        ← migra el tenant de ORIGEN
15:47:23  Login::relogin arranca
15:47:24  S7 resuelve el destino: bd_name = ccerda_kovo
15:47:25  la sesión ya apunta a ccerda_kovo → redirect
15:49:11  ERROR: record "new" has no field "id_usuario"   ← en ccerda_kovo
```

La corrección es una segunda llamada, **después** de fijar la sesión del tenant nuevo y
**antes** del redirect:

```php
// Login::relogin(), tras _enforceSingleSession()
$this->_ejecutarMigracionesDelTenantActual('relogin');
```

`Login::new_user()` ya hacía esta llamada manual desde antes, por la otra cara del mismo
problema: ahí la sesión no existe hasta después del login. El relogin era el caso que
faltaba.

El helper **nunca** deja caer la petición: si una migración falla, lo registra
(`[MIG-INLINE][relogin][ERROR]`) y el cambio de empresa sigue adelante. La migración se
reintenta en el siguiente login o relogin.

> **Si escribes otro sitio donde se cambie de tenant dentro de una petición**, ese sitio
> también necesita la llamada. El hook automático solo ve la sesión que había al empezar.

### `down()` se ejecuta más de lo que crees

`migration_auto_latest = TRUE` hace que el **constructor** de `CI_Migration` ejecute
`latest()` en cuanto se instancia la librería. El hook calcula `$pendientes` *antes* de
eso, así que al volver de `load->library('migration')` la base ya puede estar arriba y la
primera vuelta del bucle uno-a-uno apunta **por debajo** — y `version(N)` con `N` menor
que la versión actual **baja ejecutando `down()`**.

Visto el 09-09-2026 en `ccerda_botilleria_el_trece`:

```
Finished migrating to 20260909220000              ← latest() del constructor
Migrating down ... to 20260909210000
Migrating down ... to 20260909180000
Migration_fix_trigger_..._from_mother::down NO IMPLEMENTADO
Finished migrating to 20260908134000              ← bajó tres versiones
Migrating up ... hasta 20260909220000             ← y las repitió
```

Triplicaba el trabajo (22,5 s en ese relogin) y, sobre todo, **ejecutaba `down()`**. Es
la misma forma del incidente del 28-08-2026, cuando un `down()` con `DROP COLUMN` gastó
796 `attnum` de `venta`.

Corregido releyendo `migrations.version` **después** de cargar la librería y descartando
lo ya aplicado, de modo que el bucle solo avanza. Lo que aplicó el constructor queda en
la bitácora como tal.

> **La regla, que ya estaba escrita en `migracionesPendientes()`:** nunca pidas
> `version(N)` con `N` menor o igual a la versión actual. Lo que falta es leer esa
> versión en el momento correcto — justo antes de usarla, no antes de cargar la librería.

Y por eso importa que **`down()` no destruya**: se ejecuta en situaciones que no
esperabas. Un `down()` vacío con un `log_message` es una respuesta perfectamente válida
para una corrección estructural (Regla 4).

### Qué había antes, y por qué se cambió

Era una **lista negra**: correr en toda petición menos en las cinco que graban
documentos. Eso dejaba el mantenimiento del esquema repartido por toda la aplicación —
podía caer en un listado, en un poller o en una búsqueda de productos — y bastaba una
ruta nueva sin agregar a la lista para volver a meter una cadena de `ALTER` dentro de
una petición que el usuario está esperando. Que es exactamente lo que pasó el
31/08/2026 con `Venta/grabarNewVenta`.

Ahora es una **lista blanca**. Fuera del login, ninguna petición toca el esquema.

### El hook NO exige superadmin

Conviene decirlo explícito porque es fácil confundirlo: **`id_rol = 1` es el requisito
para abrir el panel de bitácora, no para que se apliquen las migraciones.**

`AutoMigrationHook` solo necesita una sesión con `db_server` y `bd_name`. Sirve
**cualquier usuario**: el cajero que abre la caja a las 8:00 dispara la cadena igual que
tú. No hay que esperar a que entre un administrador.

### El riesgo que sí queda

Que el hook no dependa del rol no significa que el problema esté resuelto. Después de
subir código al VPS quedan tres cosas abiertas:

1. **El primero que entra paga.** Si el despliegue trae migraciones pesadas — un
   `ADD COLUMN` con `DEFAULT` sobre una tabla grande, en PostgreSQL 10, reescribe la
   tabla entera — el primer login del día espera. Es mucho mejor que pagarlo a mitad de
   una venta, pero sigue siendo alguien esperando.

2. **Los que nunca cerraron sesión no migran.** Con la lista blanca, sus peticiones ya
   no tocan el esquema. Si una caja lleva días abierta, corre contra el esquema viejo
   hasta que alguien vuelva a entrar en ese tenant.

3. **Si la primera migración falla, entra el freno.** El backoff
   (`0 · 30 · 60 · 120 · 300 · 600 · 900` s) espacia los reintentos, así que un fallo a
   las 8:00 puede no volver a intentarse hasta pasados 15 minutos. Ver
   [`PANEL_BITACORA.md`](PANEL_BITACORA.md).

### Qué hacer después de desplegar

**Migrar en el despliegue, antes de que llegue nadie.** El `git pull` no es el
despliegue completo; esto sí:

```bash
git pull && php index.php despliegue esquema && systemctl reload php-fpm
```

Ese comando recorre **todos** los tenants por consola. Devuelve 0 o 1, así que si algo
falla la cadena se corta y te enteras antes de que entre el primer cajero.

Si prefieres hacerlo a mano, o quieres mirar antes de aplicar:

1. Desplegar **fuera de horario**, no a las 7:55.
2. `php index.php despliegue estado` para ver qué falta sin cambiar nada. O, desde el
   navegador, el panel → **🌐 Recorrer tenants** → **▶ Ejecutar pendientes**. Tú absorbes
   el costo, no el cajero, y queda registrado (`origen = 'admin'`).
3. Comprobar con el filtro **⛔ Falta** que cada tenant quedó en cero.

### El comando


```bash
php index.php despliegue estado      # qué falta, sin cambiar nada
php index.php despliegue esquema     # aplicarlo a todos los tenants
```

Por cada tenant aplica las migraciones pendientes (las registra en la bitácora igual
que el panel, con `origen = 'despliegue_cli'`), aplica el esquema que el código se
asegura de tener, y deja la caché caliente. Devuelve 0 o 1, así que encadena:

```bash
git pull && php index.php despliegue esquema && systemctl reload php-fpm
```

Opciones: `--servidor=`, `--base=`, `--sin-migraciones`, `--sin-esquema`, `--simular`,
`--detalle`, `--estricto`.

**Por HTTP no responde.** Migra bases sin pedir contraseña: expuesto en la web sería
una puerta abierta. Comprueba `is_cli()` y devuelve 404.

El panel sigue estando para lo mismo desde el navegador: **🌐 Recorrer tenants**
descubre los tenants preguntando a cada servidor qué bases tiene y quedándose con las
que tienen tabla `migrations`. No hacía falta el registro central que parecía faltar:
los servidores están en `DbManager::serverKeys()`, y a un servidor PostgreSQL se le
puede preguntar por sus bases. Ver *🌐 Recorrer tenants* en
[`PANEL_BITACORA.md`](PANEL_BITACORA.md).

Un tenant que no tiene un módulo (sin tabla `sorteo`, por ejemplo) se **avisa** pero no
hace fallar el despliegue — si no, un cliente sin un módulo bloquearía el despliegue de
todos. Con `--estricto` se puede exigir que aplique todo.

---

## Plantilla

> Cumplimiento Regla 0: este es el **único formato válido** de migración. Nada de
> archivos `.sql` independientes. Crea el archivo en `application/migrations/` y
> copia este esqueleto.

> Y antes de darla por terminada, pasa por la lista de la **Regla 9.8**: son nueve
> comprobaciones cortas, y cada una viene de algo que falló de verdad.

```php
<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20260910093000
 * Timestamp: 2026-09-10 09:30:00
 *
 * Qué hace y POR QUÉ. El panel muestra esta primera línea como resumen.
 */
class Migration_add_ejemplo_columna extends CI_Migration
{
    public function up()
    {
        $this->ejecutar("
            ALTER TABLE venta
              ADD COLUMN IF NOT EXISTS mi_columna double precision DEFAULT 0
        ", 'ALTER venta.mi_columna');

        $this->ejecutar("
            CREATE INDEX IF NOT EXISTS idx_venta_mi_columna ON venta(mi_columna)
        ", 'CREATE INDEX idx_venta_mi_columna');

        // Si toca una función, el marcador va DENTRO del cuerpo (Regla 1):
        $this->ejecutar("
            CREATE OR REPLACE FUNCTION public.f_ejemplo() RETURNS trigger
            LANGUAGE plpgsql AS $$
            -- @migracion 20260910093000
            BEGIN
                RETURN NEW;
            END $$
        ", 'CREATE OR REPLACE f_ejemplo');

        // Regla 7: comprobar en el catálogo antes de darse por aplicada.
        $q = $this->db->query(
            "SELECT count(*) AS n FROM information_schema.columns
              WHERE table_schema='public' AND table_name='venta'
                AND column_name='mi_columna'");
        $fila = ($q && method_exists($q, 'row')) ? $q->row() : null;
        if (!$fila || (int) $fila->n === 0) {
            throw new RuntimeException(
                __METHOD__ . ': venta.mi_columna no existe después del ALTER. ' .
                'No se marca como aplicada: se reintenta en el próximo login.');
        }
    }

    /**
     * Regla 7. En producción `db_debug` está en false y entonces `query()` NO
     * lanza: devuelve FALSE. Un try/catch alrededor no se dispara nunca, la
     * migración termina limpia y CI_Migration registra la versión — para
     * siempre. Por eso se mira el RETORNO.
     */
    private function ejecutar($sql, $etiqueta)
    {
        $tag = __CLASS__;
        $resultado = null;
        $errorSql  = '';
        try {
            $resultado = $this->db->query($sql);
        } catch (Throwable $e) {
            $resultado = false;
            $errorSql  = $e->getMessage();
        }
        if ($resultado === false) {
            if ($errorSql === '' && method_exists($this->db, 'error')) {
                $err = $this->db->error();
                $errorSql = is_array($err) && !empty($err['message']) ? $err['message'] : 'sin detalle';
            }
            // AutoMigrationHook atrapa esta excepción: no tumba la petición.
            throw new RuntimeException("$tag: $etiqueta no se aplicó: $errorSql");
        }
        return $resultado;
    }

    public function down()
    {
        // Opcional. El panel solo lee up(): down() se ignora al verificar.
    }
}
```

---

## Lo que el panel NO puede verificar

Las migraciones que solo **mueven datos** — sembrar permisos, plantillas ZPL, flags de
configuración — no crean objetos, así que no hay nada que buscar en el catálogo.
Quedan en ❔ **Revisar a mano**, y esa es la respuesta correcta, no una limitación que
se pueda tapar.

Si necesitas que una migración de datos sea verificable, hazla idempotente con
`ON CONFLICT DO NOTHING` y comprueba a mano la primera vez.

> Aplica también la Regla 0: los archivos `.sql` de la carpeta `/sql/` no forman
> parte del lote escaneado. Por definición **no pueden** mostrarse como "Ya está",
> "Otra versión" ni "Superada" en la bitácora. Cualquier SQL fuera de
> `application/migrations/*.php` queda en ❔ **Revisar a mano** siempre.

> **No confundas eso con el `.sql` gemelo de la Regla 9.4.** Son cosas distintas:
> `/sql/*.sql` son bosquejos y respaldos, no se ejecutan ni se verifican; el gemelo
> `application/migrations/<versión>_<nombre>.sql` tampoco se ejecuta NUNCA en miPDV --
> la migración sigue siendo el `.php`-- pero sí es lo que el panel manda a `update_pdv`
> cuando el PHP no se puede aplanar. Uno es documentación; el otro, material de envío.

---

## Y una nota de fondo

**Las migraciones no deberían correr dentro de una petición de usuario.** Pasó dos
veces:

- **31/08/2026** — tras un despliegue, la primera petición de cada tenant arrastró la
  cadena completa de `ALTER`, y cuando esa petición era `Venta/grabarNewVenta` el
  cajero se quedó esperando 45 segundos.
- **08-09-2026** — el `ALTER` de un constructor se puso detrás de un `SELECT` de 22
  minutos y la cola FIFO de PostgreSQL se llevó por delante los 30 workers de php-fpm.
  Ese no venía de una migración: venía de DDL suelto en un modelo. Ver la **Regla 6**.
- **09-09-2026** — una transferencia de bodega no se podía guardar. La migración que
  arreglaba justamente eso figuraba como aplicada: se había tragado sus errores y la
  versión quedó grabada igual, así que no se reintentaba en ningún tenant ni al hacer
  relogin. Al revisarlo aparecieron dos cosas más: un `DROP TRIGGER` por nombre que
  podía dejar **dos** triggers moviendo el stock, y un regex que descartaba sentencias
  enteras en silencio. Ver la **Regla 7**.

Lo correcto es ejecutarlas en el despliegue, y ahora se puede:

```bash
php index.php despliegue esquema
```

Cuando ese comando forme parte de tu despliegue, las peticiones pueden dejar de tocar
el esquema por completo:

```php
// application/config/config.php
$config['esquema_en_peticion'] = FALSE;
```

Con eso, `mipdv_asegurar_esquema()`, `mipdv_asegurar_columna()` y `mipdv_ddl_una_vez()`
no hacen **nada** en las peticiones web: ni catálogo, ni caché, ni archivo. En consola
el interruptor no manda — si apagara también al comando de despliegue, no quedaría
nadie que hiciera el trabajo.

Viene en `TRUE`, que es el comportamiento de siempre. Ponerlo en `FALSE` en un servidor
donde el comando no se ejecute dejaría a los tenants sin las columnas nuevas.

El hook con bitácora sigue siendo la red mientras el comando no esté en el despliegue.
La Regla 0 (formato PHP CI3 nativo en `application/migrations/`) es la precondición
para cualquiera de las dos cosas: sin ella ni la Bitácora, ni AutoMigrationHook, ni
`CI_Migration::latest()` llegan a ver el cambio.
