<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_fpago_pago_electronico_web extends CI_Migration
{
    public function up()
    {
        // NULL en las formas existentes equivale a desmarcado y evita reescribir la tabla en PostgreSQL 10.
        if ($this->db->query('ALTER TABLE public.fpago ADD COLUMN IF NOT EXISTS pago_electronico_web boolean') === false
            || !$this->db->field_exists('pago_electronico_web', 'fpago')) {
            throw new RuntimeException('No se pudo crear fpago.pago_electronico_web.');
        }
    }

    public function down()
    {
        log_message('info', __METHOD__ . ': se conserva la configuración de las formas de pago.');
    }
}
