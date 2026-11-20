<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261120120000
 * Timestamp: 2026-11-20 12:00:00
 *
 * Tipo de mesa por defecto (pedido del usuario, 20-11-2026, v1.22.0): en Mesas no debe salir "Sin tipo". Se crea el
 * tipo "General" sin texto (en la Caja la mesa sigue saliendo "Mesa 1"), marcado como por defecto, y las mesas sin
 * tipo pasan a él. Las mesas nuevas nacen con ese tipo; las que cree el POS de escritorio (no conoce los tipos) se
 * muestran también como "General" (Mtipomesa::idSql).
 *
 * - tipo_mesa_web.por_defecto: a lo más uno (índice único parcial).
 * - Si ya hay un tipo "General" sin texto, ese pasa a ser el por defecto (no se repite el nombre).
 *
 * Idempotente: cada sentencia revisa antes lo que ya existe. Una sentencia por llamada (Regla 9).
 */
class Migration_tipo_mesa_por_defecto extends CI_Migration
{
    public function up()
    {
        if (!$this->existe('tipo_mesa_web', 'id_tipo_mesa_web')) {
            throw new RuntimeException(__METHOD__ . ': falta tipo_mesa_web (migración 20261119110000). Se reintenta en la próxima ejecución.');
        }

        $this->ejecutar('ALTER TABLE public.tipo_mesa_web ADD COLUMN IF NOT EXISTS por_defecto boolean NOT NULL DEFAULT false',
            'ADD COLUMN tipo_mesa_web.por_defecto');
        $this->ejecutar('CREATE UNIQUE INDEX IF NOT EXISTS ux_tipo_mesa_web_por_defecto ON public.tipo_mesa_web ((true)) WHERE por_defecto',
            'CREATE INDEX ux_tipo_mesa_web_por_defecto');

        // Un "General" sin texto que ya exista pasa a ser el por defecto
        $this->ejecutar("UPDATE public.tipo_mesa_web SET por_defecto = true, vigente = true, fecha_modificacion = now()
            WHERE id_tipo_mesa_web = (SELECT min(id_tipo_mesa_web) FROM public.tipo_mesa_web
                                       WHERE upper(trim(nombre)) = 'GENERAL' AND trim(prefijo) = '')
              AND NOT EXISTS (SELECT 1 FROM public.tipo_mesa_web WHERE por_defecto)", 'UPDATE General existente');

        // Si no, se crea (con otro nombre si "General" ya está ocupado por un tipo con texto)
        $this->ejecutar("INSERT INTO public.tipo_mesa_web (nombre, prefijo, espacio, vigente, por_defecto)
            SELECT CASE WHEN EXISTS (SELECT 1 FROM public.tipo_mesa_web WHERE upper(trim(nombre)) = 'GENERAL')
                        THEN 'General (por defecto)' ELSE 'General' END, '', false, true, true
            WHERE NOT EXISTS (SELECT 1 FROM public.tipo_mesa_web WHERE por_defecto)", 'INSERT tipo General');

        if ($this->existe('mesa', 'id_tipo_mesa_web')) {
            $this->ejecutar('UPDATE public.mesa SET id_tipo_mesa_web = (SELECT id_tipo_mesa_web FROM public.tipo_mesa_web WHERE por_defecto LIMIT 1)
                WHERE id_tipo_mesa_web IS NULL', 'UPDATE mesas sin tipo');
        }

        // Regla 7: comprobar antes de darse por aplicada
        $q = $this->db->query('SELECT count(*) AS n FROM public.tipo_mesa_web WHERE por_defecto');
        $fila = ($q && method_exists($q, 'row')) ? $q->row() : null;
        if (!$fila || (int)$fila->n !== 1) {
            throw new RuntimeException(__METHOD__ . ': no quedó un tipo de mesa por defecto. Se reintenta en la próxima ejecución.');
        }
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

    /** Regla 7: con db_debug en false query() no lanza, devuelve FALSE: se revisa y se lanza. */
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
            throw new RuntimeException("$tag: $etiqueta no se aplicó: $errorSql");
        }
        return $resultado;
    }

    public function down()
    {
        // No destructivo: las mesas quedan con su tipo. Si hiciera falta, va en una migración nueva.
        log_message('info', __METHOD__ . ': sin cambios (down no destructivo).');
    }
}
