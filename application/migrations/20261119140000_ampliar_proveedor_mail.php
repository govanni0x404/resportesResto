<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261119140000
 * Timestamp: 2026-11-19 14:00:00
 *
 * proveedor.mail pasa de varchar(20) a varchar(100), igual que cliente.mail. Con 20 no cabía un correo normal
 * (ej. ventas@lacteosdelsur.cl = 23 letras): la pantalla lo cortaba y, si llegaba entero, la base daba
 * "el valor es demasiado largo para el tipo character varying(20)". Solo agranda: si la columna ya mide 100 o más
 * (o no tiene límite) no se toca, para no achicarla nunca. Agrandar un varchar no reescribe la tabla.
 */
class Migration_ampliar_proveedor_mail extends CI_Migration
{
    public function up()
    {
        $largo = $this->largoMail();
        if ($largo === null) return;   // sin tabla o sin columna: nada que hacer
        if ($largo > 0 && $largo < 100) {
            $this->ejecutar("ALTER TABLE public.proveedor ALTER COLUMN mail TYPE character varying(100)", 'ALTER proveedor.mail varchar(100)');
        }

        // Regla 7: comprobar en el catálogo antes de darse por aplicada.
        $despues = $this->largoMail();
        if ($despues !== 0 && $despues < 100) {
            throw new RuntimeException(__METHOD__ . ': proveedor.mail sigue en varchar(' . $despues . '). ' .
                'No se marca como aplicada: se reintenta en el próximo login.');
        }
    }

    /** null = no existe la columna; 0 = sin límite; n = varchar(n). */
    private function largoMail()
    {
        $q = $this->db->query("SELECT character_maximum_length AS n FROM information_schema.columns
            WHERE table_schema = 'public' AND table_name = 'proveedor' AND column_name = 'mail'");
        $f = ($q && method_exists($q, 'row')) ? $q->row() : null;
        if (!$f) return null;
        return $f->n === null ? 0 : (int)$f->n;
    }

    /**
     * Regla 7. Con db_debug en false `query()` NO lanza: devuelve FALSE. Por eso se mira el RETORNO.
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
            throw new RuntimeException("$tag: $etiqueta no se aplicó: $errorSql");
        }
        return $resultado;
    }

    public function down()
    {
        // No se achica: podría cortar correos ya grabados.
    }
}
