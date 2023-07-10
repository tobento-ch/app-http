<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);

namespace Tobento\App\Http\Routing;

use Tobento\Service\Routing\RouteHandlerInterface as ServiceRouteHandlerInterface;

/**
 * RouteHandlerInterface
 */
interface RouteHandlerInterface extends ServiceRouteHandlerInterface
{
    /**
     * Add a route handler.
     *
     * @param string|ServiceRouteHandlerInterface $handler
     * @return static $this
     */
    public function addHandler(string|ServiceRouteHandlerInterface $handler): static;
    
    /**
     * Remove a route handler ny name.
     *
     * @param string $name
     * @return static $this
     */
    public function removeHandler(string $name): static;    
}