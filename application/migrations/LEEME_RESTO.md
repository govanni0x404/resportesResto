# Migraciones en resportesResto

Las reglas para **escribir** una migración son las de [`CONVENCIONES.md`](CONVENCIONES.md) (vienen de miPDV).
Este archivo dice **cómo se ejecutan aquí**, porque resportesResto no tiene todo lo de miPDV: no hay panel de
bitácora, ni envío a `update_pdv`, ni helper `mipdv_asegurar_*`.

## Qué hay

| Pieza | Dónde |
|---|---|
| Configuración | `config/migration.php`: habilitadas, tipo `timestamp`, tabla **`migrations_resto`**, versión objetivo = el número más alto de esta carpeta (Regla 8), `migration_auto_latest = FALSE` |
| Ejecutor | `libraries/MigracionesResto.php` |
| Disparo web | `Login::postLoginWarmup`, que `menuFooter.php` llama por AJAX en segundo plano |
| Consola | `controllers/Despliegue.php` → `php index.php despliegue estado` / `esquema` |
| Bitácora | `application/logs/migraciones_AAAAMM.log` (una línea JSON por migración aplicada o fallida) |

La tabla de versión se llama `migrations_resto` y no `migrations` a propósito: si alguna base la compartiera con
miPDV, las versiones de los dos sistemas no se mezclan (una versión ajena más alta haría que las de aquí se
saltaran en silencio, Regla 8).

## Cuándo se aplican

1. **Al desplegar (recomendado):** `php index.php despliegue esquema`. Recorre las empresas del diccionario local
   de conexiones (`application/cache/conexiones_tenant.php`, se llena solo con cada login). Para una base que
   no está ahí: `php index.php despliegue esquema <servidor> <base>`. Devuelve 0 o 1.
   Las credenciales salen del entorno (`MIPDV_DB_<SERVIDOR>_*`) igual que en la web: si en el servidor están
   solo como `SetEnv` de Apache, hay que exportarlas también en la consola.
2. **En la web, una vez por sesión y empresa:** la primera página que se abre con una empresa (al entrar o al
   cambiar de empresa) llama a `Login/postLoginWarmup` por AJAX. No bloquea el login ni la pantalla (la sesión
   se libera antes de migrar). Ninguna otra petición toca el esquema.

## Cómo se comporta el ejecutor

- Solo sube: aplica las versiones mayores que la grabada y menores o iguales que la objetivo, **una a una**,
  releyendo la versión antes de cada una. Nunca pide una versión menor o igual que la actual, así que `down()`
  no se ejecuta.
- Si una falla (lanza, o `query()` devuelve `FALSE`), se corta ahí y la versión no avanza: se reintenta después.
  En la web, con freno progresivo por base: 30 s, 1, 2, 5, 10 y 15 min. Gerencia ve el aviso en pantalla.
- Mientras corre: `db_debug` en `FALSE` (para que el error llegue a la migración y ésta lance, Regla 7), un
  solo proceso por base (`pg_try_advisory_lock`) y `lock_timeout` de 10 s (Regla 6).

## Código que depende de una migración

El código no debe asumir que la migración ya corrió en esa base: pregunta al catálogo si la columna existe
(lectura sin locks) y, si no está, se comporta como antes. Ejemplo: `producto.favorito`
(`20261110190000_add_producto_favorito.php`) en `Mcaja::catalogo` y `Mproductos`. Nada de DDL dentro de las
peticiones (Regla 6).
