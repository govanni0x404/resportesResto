<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261118120000
 * Timestamp: 2026-11-18 12:00:00
 *
 * Delivery con descuento que se cobra al entregar (pedido del usuario, 18-11-2026): el descuento puesto al tomar el
 * pedido se perdía y había que aplicarlo otra vez al cobrar. delivery_pedido.descuento guarda el monto (pesos) del
 * descuento; al cobrar se carga solo y no vuelve a pedir la autorización si no se cambia.
 *
 * Sin DEFAULT (no reescribe la tabla; NULL = sin descuento). Idempotente. El código no asume que corrió.
 */
class Migration_add_delivery_descuento extends CI_Migration
{
    public function up()
    {
        if (!$this->existe('delivery_pedido', 'id_delivery')) {
            throw new RuntimeException(__METHOD__ . ': falta delivery_pedido (migración 20261118100000). Se reintenta en la próxima ejecución.');
        }
        if (!$this->existe('delivery_pedido', 'descuento')) {
            $ok = $this->db->query('ALTER TABLE public.delivery_pedido ADD COLUMN IF NOT EXISTS descuento integer');
            if ($ok === false) {
                $e = $this->db->error();
                throw new RuntimeException(__METHOD__ . ': ADD COLUMN delivery_pedido.descuento no se aplicó: ' . (isset($e['message']) ? $e['message'] : 'sin detalle'));
            }
        }
        // Regla 7: comprobar en el catálogo antes de darse por aplicada.
        if (!$this->existe('delivery_pedido', 'descuento')) {
            throw new RuntimeException(__METHOD__ . ': falta delivery_pedido.descuento después de crearla. Se reintenta en la próxima ejecución.');
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

    public function down()
    {
        // Sin bajada automática: borraría los descuentos de los pedidos guardados.
    }
}
