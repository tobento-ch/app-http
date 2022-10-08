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
use Tobento\App\Http\Boot\Middleware;
use Tobento\App\Http\SessionFactory;
use Tobento\Service\Session\SessionInterface;
use Tobento\Service\Config\ConfigInterface;
use Tobento\Service\Config\ConfigLoadException;

/**
 * Session boot.
 */
class Session extends Boot
{
    public const INFO = [
        'boot' => [
            'Session implementation',
            'adds session to middleware',
        ],
    ];
    
    public const BOOT = [
        Middleware::class,
    ];
    
    /**
     * Boot application services.
     *
     * @param Middleware $middleware
     * @return void
     */
    public function boot(Middleware $middleware): void
    {
        $config = $this->app->get(ConfigInterface::class);

        try {            
            $config->load('session.php', 'session');
        } catch (ConfigLoadException $e) {
            // ignore
        }
        
        $this->app->set(SessionInterface::class, function() use ($config): SessionInterface {

            $sessionFactory = $this->app->make($config->get('session.factory', SessionFactory::class));
                        
            $session = $sessionFactory->createSession(
                $config->get('session.name', 'sess'),
                $config->get('session.config', []),
            );
            
            return $session;
        });
        
        $middleware->add(
            $config->get(
                'session.middleware',
                \Tobento\Service\Session\Middleware\Session::class
            ),
            priority: 6000
        );
    }
}