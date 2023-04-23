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
use Tobento\App\Http\SessionFactory;
use Tobento\Service\Session\SessionInterface;
use Tobento\Service\Uri\PreviousUriInterface;
use Tobento\Service\Uri\PreviousUri;
use Psr\Http\Message\UriFactoryInterface;

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
        $config = $config->load('session.php');
        
        $this->app->set(SessionInterface::class, function() use ($config): SessionInterface {
            
            $sessionFactory = $this->app->make($config['factory'] ?? SessionFactory::class);
                        
            $session = $sessionFactory->createSession(
                $config['name'] ?? 'sess',
                $config['config'] ?? [],
            );
            
            return $session;
        });
        
        // Handle previous uri:
        $this->app->on(PreviousUriInterface::class, function(PreviousUriInterface $prevUri) {
            
            $session = $this->app->get(SessionInterface::class);

            if ($session->has('_previous_uri')) {
                $uriFactory = $this->app->get(UriFactoryInterface::class);
                $uri = $uriFactory->createUri($session->get('_previous_uri'));
                return new PreviousUri($uri);
            }
            
            return $prevUri;
        });

        // adding middlewares:
        foreach($config['middlewares'] ?? [] as $mw) {
            $middleware->add($mw, priority: 6000);
        }
    }
}