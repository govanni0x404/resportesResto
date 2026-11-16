<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261116100000
 * Timestamp: 2026-11-16 10:00:00
 *
 * Objetos que la web creaba DENTRO de una petición (Regla 6). Ahora se crean aquí, una vez, y el código ya no hace DDL:
 *  - configuracion.defectos_web            (antes: ALTER en cada inicio de sesión mientras faltara, Mconfiguraciones)
 *  - configuracion.usuario_dte, clave_usuario_dte, terminal_dte, modo_caja_web
 *                                          (antes: Mconfiguraciones::asegurar_columnas_dte al abrir Configuraciones)
 *  - mesa_plano (+ nombre)                 (antes: CREATE/ALTER en CADA guardado del plano de mesas, Mcaja)
 *  - agente_impresion, cola_impresion      (antes: Mcola_impresion al abrir la pantalla de agentes)
 * Mismas definiciones que tenía el código. Idempotente (IF NOT EXISTS): en las bases que ya los tenían no cambia nada.
 */
class Migration_add_objetos_caja_web extends CI_Migration
{
    private $columnasConfiguracion = array(
        'defectos_web' => 'boolean NOT NULL DEFAULT false',
        'usuario_dte' => 'character varying(100)',
        'clave_usuario_dte' => 'character varying(100)',
        'terminal_dte' => 'character varying(60)',
        'modo_caja_web' => 'character varying(10)',
    );

    public function up()
    {
        if ($this->existeTabla('configuracion')) {
            foreach ($this->columnasConfiguracion as $col => $tipo) {
                $this->ejecutar("ALTER TABLE public.configuracion ADD COLUMN IF NOT EXISTS $col $tipo", "ADD COLUMN configuracion.$col");
            }
        } else {
            log_message('info', __METHOD__ . ': sin tabla configuracion, se omiten sus columnas.');
        }

        $this->ejecutar('CREATE TABLE IF NOT EXISTS public.mesa_plano (
                id_mesa integer PRIMARY KEY,
                x integer NOT NULL DEFAULT 0,
                y integer NOT NULL DEFAULT 0)', 'CREATE TABLE mesa_plano');
        $this->ejecutar('ALTER TABLE public.mesa_plano ADD COLUMN IF NOT EXISTS nombre text', 'ADD COLUMN mesa_plano.nombre');

        $this->ejecutar("CREATE TABLE IF NOT EXISTS public.agente_impresion (
                id_agente serial PRIMARY KEY,
                nombre text NOT NULL DEFAULT '',
                token_hash text NOT NULL,
                impresoras text NOT NULL DEFAULT '[]',
                ultimo_contacto timestamp NULL,
                creado timestamp NOT NULL DEFAULT now(),
                activo boolean NOT NULL DEFAULT true)", 'CREATE TABLE agente_impresion');
        $this->ejecutar("CREATE TABLE IF NOT EXISTS public.cola_impresion (
                id_cola serial PRIMARY KEY,
                destino text NOT NULL DEFAULT '',
                impresora text NOT NULL DEFAULT '',
                ip text NOT NULL DEFAULT '',
                puerto integer NOT NULL DEFAULT 0,
                datos_b64 text NOT NULL,
                estado text NOT NULL DEFAULT 'pendiente',
                intentos integer NOT NULL DEFAULT 0,
                id_agente integer NULL,
                error text NOT NULL DEFAULT '',
                creado timestamp NOT NULL DEFAULT now(),
                reclamado timestamp NULL,
                impreso timestamp NULL)", 'CREATE TABLE cola_impresion');

        // Regla 7: comprobar en el catálogo antes de darse por aplicada.
        $faltan = array();
        if ($this->existeTabla('configuracion')) {
            foreach (array_keys($this->columnasConfiguracion) as $col) {
                if (!$this->existe('configuracion', $col)) $faltan[] = "configuracion.$col";
            }
        }
        foreach (array('mesa_plano' => 'nombre', 'agente_impresion' => 'token_hash', 'cola_impresion' => 'datos_b64') as $t => $c) {
            if (!$this->existe($t, $c)) $faltan[] = "$t.$c";
        }
        if ($faltan) {
            throw new RuntimeException(__METHOD__ . ': faltan ' . implode(', ', $faltan) . ' después de crearlos. ' .
                'No se marca como aplicada: se reintenta en la próxima ejecución.');
        }
    }

    private function existeTabla($tabla)
    {
        $q = $this->db->query('SELECT to_regclass(?) IS NOT NULL AS hay', array('public.' . $tabla));
        $f = ($q && method_exists($q, 'row')) ? $q->row() : null;
        return $f && ($f->hay === 't' || $f->hay === true);
    }

    private function existe($tabla, $columna)
    {
        $q = $this->db->query(
            "SELECT count(*) AS n FROM information_schema.columns
              WHERE table_schema = 'public' AND table_name = ? AND column_name = ?",
            array($tabla, $columna));
        $fila = ($q && method_exists($q, 'row')) ? $q->row() : null;
        return $fila && (int) $fila->n > 0;
    }

    /** Regla 7: con db_debug en false query() devuelve FALSE; se lanza para no grabar la versión. */
    private function ejecutar($sql, $etiqueta)
    {
        $resultado = null;
        $errorSql = '';
        try {
            $resultado = $this->db->query($sql);
        } catch (Throwable $e) {
            $resultado = false;
            $errorSql = $e->getMessage();
        }
        if ($resultado === false) {
            if ($errorSql === '' && method_exists($this->db, 'error')) {
                $err = $this->db->error();
                $errorSql = is_array($err) && !empty($err['message']) ? $err['message'] : 'sin detalle';
            }
            throw new RuntimeException(__CLASS__ . ": $etiqueta no se aplicó: $errorSql");
        }
        return $resultado;
    }

    public function down()
    {
        // No destructivo: se perderían el plano de mesas, los agentes y la cola de impresión.
        log_message('info', __METHOD__ . ': sin cambios (down no destructivo).');
    }
}
