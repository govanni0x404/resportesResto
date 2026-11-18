<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261118110000
 * Timestamp: 2026-11-18 11:00:00
 *
 * Pasa a migración lo que se corría a mano (regla 0): sql/permiso_web.sql y sql/credito_clientes.sql. Así llega solo a
 * cada cliente nuevo (baseVaciaResto no tenía nada de esto) y deja de depender de que alguien se acuerde del psql.
 *
 * 1) Crédito a clientes: cliente.plazo_credito (días, 0 = sin plazo, por defecto 30), pago_credito_cliente (cada pago
 *    de crédito recibido en la Caja), detalle_pago_credito (qué ventas pagó y cuánto) e índices.
 *    plazo_credito lleva DEFAULT: en PG 10 reescribe cliente, pero solo donde falta la columna (se revisa en el
 *    catálogo antes; donde ya está no se manda el ALTER y no se toma el lock). cliente es una tabla chica.
 *
 * 2) permiso_web (Herramientas > Privilegios). Ojo con qué significa una fila que falta: si la tabla YA existía, un
 *    módulo sin fila es "sin acceso"; sembrarlo en true le daría acceso a alguien que gerencia no autorizó. Por eso:
 *    - Si la tabla se crea ahora: se siembra con la regla que el cliente usa HOY sin la tabla (la antigua: psistema
 *      'RESTO WEB' + pusuario; lo que no aparece ahí queda permitido salvo Usuario, Configuraciones y ControlCaja).
 *      Así nadie gana ni pierde acceso al crearla. AnulacionVentas, AuditoriaCaja y ControlCaja quedan en false, como
 *      en el .sql (gerencia decide a quién se las da).
 *    - Si ya existía (laToscana): solo se agregan esas tres en false donde falten. No cambia ningún acceso: sin fila ya
 *      era "sin acceso". Nunca se pisa lo configurado (ON CONFLICT DO NOTHING).
 *    El rol 1 (gerencia) no tiene filas: entra a todo.
 *
 * Idempotente. Regla 7: comprueba en el catálogo y lanza si algo falta.
 */
class Migration_add_permiso_web_credito_clientes extends CI_Migration
{
    public function up()
    {
        // ---------- 1) crédito a clientes ----------
        if (!$this->existeColumna('cliente', 'plazo_credito')) {
            $this->ejecutar('ALTER TABLE public.cliente ADD COLUMN IF NOT EXISTS plazo_credito integer NOT NULL DEFAULT 30', 'ADD COLUMN cliente.plazo_credito');
        }
        $this->ejecutar('CREATE TABLE IF NOT EXISTS public.pago_credito_cliente (
            id_pago_credito serial PRIMARY KEY,
            id_cliente integer NOT NULL,
            id_caja integer NOT NULL,
            id_usuario integer NOT NULL,
            id_fpago integer NOT NULL,
            monto integer NOT NULL,
            paga_con integer NOT NULL DEFAULT 0,
            vuelto integer NOT NULL DEFAULT 0,
            fecha timestamp NOT NULL DEFAULT now(),
            observacion varchar(200),
            id_detalle_caja integer
        )', 'CREATE pago_credito_cliente');
        $this->ejecutar('CREATE INDEX IF NOT EXISTS pago_credito_cliente_cliente ON public.pago_credito_cliente (id_cliente)', 'INDEX pago_credito_cliente_cliente');
        $this->ejecutar('CREATE INDEX IF NOT EXISTS pago_credito_cliente_caja ON public.pago_credito_cliente (id_caja)', 'INDEX pago_credito_cliente_caja');
        $this->ejecutar('CREATE TABLE IF NOT EXISTS public.detalle_pago_credito (
            id_detalle_pago_credito serial PRIMARY KEY,
            id_pago_credito integer NOT NULL REFERENCES public.pago_credito_cliente (id_pago_credito) ON DELETE CASCADE,
            id_ctacte_cliente integer NOT NULL,
            id_venta integer NOT NULL,
            monto integer NOT NULL
        )', 'CREATE detalle_pago_credito');
        $this->ejecutar('CREATE INDEX IF NOT EXISTS detalle_pago_credito_pago ON public.detalle_pago_credito (id_pago_credito)', 'INDEX detalle_pago_credito_pago');
        if ($this->existeTabla('ctacte_cliente')) {
            $this->ejecutar('CREATE INDEX IF NOT EXISTS ctacte_cliente_cliente ON public.ctacte_cliente (id_cliente)', 'INDEX ctacte_cliente_cliente');
        }

        // ---------- 2) permiso_web ----------
        $existia = $this->existeTabla('permiso_web');
        $this->ejecutar('CREATE TABLE IF NOT EXISTS public.permiso_web (
            id_rol integer NOT NULL,
            modulo varchar(60) NOT NULL,
            autoriza boolean NOT NULL DEFAULT false,
            PRIMARY KEY (id_rol, modulo)
        )', 'CREATE permiso_web');

        if (!$existia) {
            // Regla antigua (helpers/permisos_web_helper.php, modo 'antiguo'): lo que dice psistema 'RESTO WEB' + pusuario
            // para el rol; si el módulo no está ahí, permitido salvo los sensibles.
            $antigua = "NULL::boolean";
            if ($this->existeTabla('psistema') && $this->existeTabla('pusuario')) {
                $antigua = "(SELECT bool_or(COALESCE(pu.autoriza, false)) FROM public.psistema ps
                    LEFT JOIN public.pusuario pu ON pu.id_psistema = ps.id_psistema AND pu.id_rol = r.id_rol
                    WHERE ps.vigente = true AND ps.nombre_psistema = 'RESTO WEB' AND lower(trim(ps.nombre_vb)) = lower(m.modulo))";
            }
            $this->ejecutar("INSERT INTO public.permiso_web (id_rol, modulo, autoriza)
                SELECT r.id_rol, m.modulo, COALESCE($antigua, lower(m.modulo) NOT IN ('usuario', 'privilegiosusuario', 'configuraciones', 'controlcaja'))
                FROM (SELECT DISTINCT id_rol FROM public.rol WHERE id_rol IS NOT NULL) r
                CROSS JOIN (VALUES ('Dashboard'), ('Caja'), ('Mesas'), ('EntradaBodega'), ('SalidaBodega'), ('Compras'), ('Inventario'),
                    ('Productos'), ('Insumos'), ('Recetas'), ('ListaDePrecios'), ('Cliente'), ('Proveedor'), ('Recintos'), ('MesasPorRecinto'),
                    ('Agregados'), ('Bodegas'), ('CanalDeVenta'), ('CentroCosto'), ('Comuna'), ('Provincias'), ('Delivery'), ('DestinoDeImpresion'),
                    ('GastoEventos'), ('IngresoEventos'), ('TipoEvento'), ('Familias'), ('FamiliasInsumos'), ('FamiliasReceta'), ('SubFamilias'),
                    ('FormasDePago'), ('DocumentosTributarios'), ('Impuestos'), ('MensajesPorProductos'), ('Moneda'), ('Turnos'), ('Umedida'),
                    ('ItemGastosMensuales'), ('InsumosPorBodega'), ('DetalleOperaciones'), ('CierresDeCaja'), ('ResumenDeCompras'),
                    ('ConsolidadoVentas'), ('VentasPorProducto'), ('PropinasPorGarzon'), ('ResumenAnulaciones'), ('ListadoDeClientes'),
                    ('VentasMensualesTotalizadas'), ('ResumenVentasProductosMes'), ('ResumenEntradaBodega'), ('ResumenSalidaBodega'),
                    ('Usuario'), ('Configuraciones')) AS m (modulo)
                WHERE r.id_rol <> 1
                ON CONFLICT (id_rol, modulo) DO NOTHING", 'INSERT permiso_web (siembra con la regla antigua)');
        }
        $this->ejecutar("INSERT INTO public.permiso_web (id_rol, modulo, autoriza)
            SELECT r.id_rol, m.modulo, false
            FROM (SELECT DISTINCT id_rol FROM public.rol WHERE id_rol IS NOT NULL) r
            CROSS JOIN (VALUES ('AnulacionVentas'), ('AuditoriaCaja'), ('ControlCaja')) AS m (modulo)
            WHERE r.id_rol <> 1
            ON CONFLICT (id_rol, modulo) DO NOTHING", 'INSERT permiso_web (pantallas de gestión en false)');

        // Regla 7: comprobar en el catálogo antes de darse por aplicada.
        foreach (array(array('cliente', 'plazo_credito'), array('pago_credito_cliente', 'id_pago_credito'),
            array('detalle_pago_credito', 'id_pago_credito'), array('permiso_web', 'autoriza')) as $tc) {
            if (!$this->existeColumna($tc[0], $tc[1])) {
                throw new RuntimeException(__METHOD__ . ": falta {$tc[0]}.{$tc[1]} después de crearla. " .
                    'No se marca como aplicada: se reintenta en la próxima ejecución.');
            }
        }
    }

    private function existeTabla($tabla)
    {
        $q = $this->db->query('SELECT to_regclass(?) IS NOT NULL AS hay', array('public.' . $tabla));
        $f = ($q && method_exists($q, 'row')) ? $q->row() : null;
        return $f && ($f->hay === 't' || $f->hay === true);
    }

    private function existeColumna($tabla, $columna)
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
        // Sin bajada automática: borraría los permisos configurados y los pagos de crédito registrados.
    }
}
