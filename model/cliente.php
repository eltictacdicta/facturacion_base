<?php
/*
 * This file is part of facturacion_base
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  neorazorx@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'plugins/clientes_core/model/core/cliente.php';
require_once 'plugins/clientes_facturacion/model/cliente_facturacion.php';

/**
 * El cliente. Puede tener una o varias direcciones y subcuentas asociadas.
 * 
 * Delegado a clientes_core. Esta clase extiende el modelo base para añadir
 * integraciones contables propias de facturacion_base (subcuentas, proveedor).
 * 
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class cliente extends FSFramework\model\cliente
{
    /**
     * Devuelve un array con todas las subcuentas asociadas al cliente.
     * Una para cada ejercicio.
     * Integración contable: requiere subcuenta_cliente y subcuenta.
     * @return \subcuenta[]
     */
    public function get_subcuentas()
    {
        $subclist = array();

        if (!class_exists('subcuenta_cliente')) {
            return $subclist;
        }

        $subc = new \subcuenta_cliente();
        foreach ($subc->all_from_cliente($this->codcliente) as $s) {
            $s2 = $s->get_subcuenta();
            if ($s2) {
                $subclist[] = $s2;
            } else {
                $s->delete();
            }
        }

        return $subclist;
    }

    /**
     * Devuelve la subcuenta asociada al cliente para el ejercicio $eje.
     * Si no existe intenta crearla. Si falla devuelve FALSE.
     * Integración contable: requiere cuenta, subcuenta_cliente, ejercicio.
     * @param string $codejercicio
     * @return \subcuenta|false
     */
    public function get_subcuenta($codejercicio)
    {
        $subcuenta = FALSE;

        foreach ($this->get_subcuentas() as $s) {
            if ($s->codejercicio == $codejercicio) {
                $subcuenta = $s;
                break;
            }
        }

        if (!$subcuenta) {
            if (!class_exists('cuenta') || !class_exists('subcuenta_cliente') || !class_exists('ejercicio')) {
                return FALSE;
            }

            $cuenta = new \cuenta();
            $ccli = $cuenta->get_cuentaesp('CLIENT', $codejercicio);
            if ($ccli) {
                $continuar = FALSE;

                $subc0 = $ccli->new_subcuenta($this->codcliente);
                if ($subc0) {
                    $subc0->descripcion = $this->razonsocial;
                    if ($subc0->save()) {
                        $continuar = TRUE;
                    }
                }

                if ($continuar) {
                    $sccli = new \subcuenta_cliente();
                    $sccli->codcliente = $this->codcliente;
                    $sccli->codejercicio = $codejercicio;
                    $sccli->codsubcuenta = $subc0->codsubcuenta;
                    $sccli->idsubcuenta = $subc0->idsubcuenta;
                    if ($sccli->save()) {
                        $subcuenta = $subc0;
                    } else {
                        $this->new_error_msg('Imposible asociar la subcuenta para el cliente ' . $this->codcliente);
                    }
                } else {
                    $this->new_error_msg('Imposible crear la subcuenta para el cliente ' . $this->codcliente);
                }
            } else {
                $eje_url = '';
                $eje0 = new \ejercicio();
                $ejercicio = $eje0->get($codejercicio);
                if ($ejercicio) {
                    $eje_url = $ejercicio->url();
                }

                $this->new_error_msg('No se encuentra ninguna cuenta especial para clientes en el ejercicio '
                    . $codejercicio . ' ¿<a href="' . $eje_url . '">Has importado los datos del ejercicio</a>?');
            }
        }

        return $subcuenta;
    }

    /**
     * Correcciones extendidas: incluye desvinculación de proveedores inexistentes.
     */
    public function fix_db()
    {
        parent::fix_db();

        if ($this->db->table_exists('proveedores')) {
            $this->db->exec("UPDATE " . $this->table_name . " SET codproveedor = null WHERE codproveedor IS NOT NULL"
                . " AND codproveedor NOT IN (SELECT codproveedor FROM proveedores);");
        }

        $extension = new \cliente_facturacion();
        if ($this->db->table_exists($extension->table_name())) {
            $this->db->exec("UPDATE " . $extension->table_name() . " SET codproveedor = null WHERE codproveedor IS NOT NULL"
                . " AND codproveedor NOT IN (SELECT codproveedor FROM proveedores);");
        }
    }

}
