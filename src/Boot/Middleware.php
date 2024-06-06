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
use Tobento\App\Http\Boot\Http;
use Tobento\App\Http\HttpErrorHandlersInterface;
use Tobento\Service\Middleware\MiddlewareDispatcherInterface;
use Tobento\Service\Middleware\MiddlewareDispatcher;
use Tobento\Service\Middleware\MiddlewareFactoryInterface;
use Tobento\Service\Middleware\AutowiringMiddlewareFactory;
use Tobento\Service\Middleware\FallbackHandler;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * Middleware boot.
 */
class Middleware extends Boot
{
    public const INFO = [
        'boot' => [
            'PSR-15 HTTP handlers (middleware) implementation',
        ],
        'terminate' => [
            'dispatches middleware',
        ],      
    ];
    
    public const BOOT = [
        Config::class,
        Http::class,
    ];
    
    public const REBOOTABLE = ['terminate'];
    
    /**
     * @var array
     */
    protected array $middlewareReplace = [];
    
    /**
     * Boot application services.
     *
     * @param Config $config
     * @return void
     */
    public function boot(Config $config): void
    {
        // Load the middleware configuration.
        $config = $config->load('middleware.php');
        
        // Interfaces:
        $this->app->set(MiddlewareFactoryInterface::class, AutowiringMiddlewareFactory::class)
            ->with(['replaces' => $config['replace'] ?? []]);
        
        $this->app->set(
            MiddlewareDispatcherInterface::class,
            static function(ContainerInterface $container) {
                return new MiddlewareDispatcher(
                    new FallbackHandler($container->get(ResponseInterface::class)),
                    $container->get(MiddlewareFactoryInterface::class),
                );
            }
        );
        
        // Adding aliases:
        $this->addAliases($config['aliases'] ?? []);
        
        // Adding groups:
        foreach($config['groups'] ?? [] as $groupName => $middlewares) {
            $this->addGroup($groupName, $middlewares);
        }
        
        // Adding middlewares:
        foreach($config['middlewares'] ?? [] as $priority => $mw) {
            $this->add($mw, priority: $priority);
        }
        
        // Adding macros:
        $this->app->addMacro('middleware', [$this, 'add']);
        $this->app->addMacro('middlewareAliases', [$this, 'addAliases']);
        $this->app->addMacro('middlewareGroup', [$this, 'addGroup']);
    }
    
    /**
     * Terminate application services.
     *
     * @param Http $http
     * @return void
     */
    public function terminate(Http $http): void
    {
        try {
            $response = $this->app->get(MiddlewareDispatcherInterface::class)->handle(
                $this->app->get(ServerRequestInterface::class)
            );
            
            $http->setResponse($response);
            
        } catch (Throwable $t) {
            // handle exception
            $response = $this->app->get(HttpErrorHandlersInterface::class)->handleThrowable($t);
            
            if ($response instanceof ResponseInterface) {
                $http->setResponse($response);
                return;
            }
            
            if ($response instanceof Throwable) {
                throw $response;
            }
            
            throw $t;
        }
    }
    
    /**
     * Add a middleware or multiple.
     *
     * @param mixed $middleware Any middleware.
     * @return static $this
     */
    public function add(mixed ...$middleware): static
    {
        if (!empty($middleware)) {
            $this->app->get(MiddlewareDispatcherInterface::class)->add(...$middleware);
        }
        
        return $this;
    }
    
    /**
     * Add multiple middleware with alias.
     *
     * @param array<string, string> $aliases ['alias' => Namespace\Middleware::class]
     * @return static $this
     */
    public function addAliases(array $aliases): static
    {
        $this->app->get(MiddlewareDispatcherInterface::class)->addAliases($aliases);
        
        return $this;
    }
    
    /**
     * Add a middleware group.
     *
     * @param string $name A group name.
     * @param array $middlewares
     * @return static $this
     */
    public function addGroup(string $name, array $middlewares): static
    {
        $this->app->get(MiddlewareDispatcherInterface::class)->addGroup($name, $middlewares);
        
        return $this;
    }
}