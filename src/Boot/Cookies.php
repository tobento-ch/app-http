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
 
namespace Tobento\App\Http\Boot;

use Tobento\App\Boot;
use Tobento\App\Boot\Config;
use Tobento\App\Http\Boot\Middleware;

/**
 * Cookies boot.
 */
class Cookies extends Boot
{
    public const INFO = [
        'boot' => [
            'implements cookie interfaces based on cookies config file',
            'adds middleware based on cookies config file',
        ],
    ];
    
    public const BOOT = [
        Config::class,
        Middleware::class,
    ];

    /**
     * Boot application services.
     *
     * @param Config $config
     * @param Middleware $middleware
     * @return void
     */
    public function boot(Config $config, Middleware $middleware): void
    {
        // load the cookies config:
        $config = $config->load('cookies.php');
        
        // adding middlewares:
        foreach($config['middlewares'] ?? [] as $mw) {
            $middleware->add($mw, priority: 6100);
        }
        
        // setting interfaces:
        foreach($config['interfaces'] ?? [] as $interface => $implementation) {
            $this->app->set($interface, $implementation);
        }
    }
}