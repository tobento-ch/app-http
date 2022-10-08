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
 
namespace Tobento\App\Http\Test\Mock;

use Tobento\App\Boot;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Routing\RouteGroupInterface;

class RoutesBoot extends Boot
{
    public const BOOT = [
        // you may ensure the routing boot.
        \Tobento\App\Http\Boot\Routing::class,
    ];
    
    public function boot(RouterInterface $router)
    {
        // Add routes on the router
        $router->get('bar', function() {
            return 'bar';
        })->name('bar');
        
        // Add routes with the provided app macros
        $this->app->route('GET', 'foo', function() {
            return 'foo';
        })->name('foo');
    }
}