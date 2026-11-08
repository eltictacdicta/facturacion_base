<?php
/**
 * This file is part of FSFramework originally based on Facturascript 2017
 * Copyright (C) 2025 Javier Trujillo <mistertekcom@gmail.com>
 * Copyright (C) 2013-2020 Carlos Garcia Gomez <neorazorx@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FSFramework\Plugins\facturacion_base;

/**
 * Inicialización del plugin facturacion_base.
 *
 * init() lo ejecuta src/Core/Plugins.php::init() en cada request, dentro de
 * Kernel::boot() y antes de despachar cualquier controlador.
 */
class Init
{
    public function init(): void
    {
        // Los controladores legacy del plugin incluyen `extras/...` con rutas
        // relativas. Antes el núcleo añadía este directorio al include_path
        // (Kernel::configureLegacyIncludePaths); ahora es responsabilidad del
        // plugin, para que el núcleo no conozca plugins concretos.
        $path = __DIR__;
        $current = explode(PATH_SEPARATOR, (string) get_include_path());
        if (!in_array($path, $current, true)) {
            set_include_path(get_include_path() . PATH_SEPARATOR . $path);
        }
    }
}
