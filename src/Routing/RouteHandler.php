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

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tobento\App\AppInterface;
use Tobento\Service\Routing\RouteInterface;
use Tobento\Service\Routing\RouteHandlerInterface as ServiceRouteHandlerInterface;
use Tobento\Service\Routing\Middleware\RouteHandler as MiddlewareRouteHandler;
use Tobento\Service\Routing\RouteResponseParserInterface;
use Tobento\Service\Middleware\MiddlewareDispatcherInterface;

/**
 * RouteHandler
 */
class RouteHandler implements RouteHandlerInterface
{
    /**
     * @var array<string, string|ServiceRouteHandlerInterface>
     */
    protected array $handlers = [];
    
    /**
     * Create a new RouteHandler
     *
     * @param AppInterface $app,
     */
    public function __construct(
        protected AppInterface $app
    ) {}
    
    /**
     * Add a route handler.
     *
     * @param string|ServiceRouteHandlerInterface $handler
     * @return static $this
     */
    public function addHandler(string|ServiceRouteHandlerInterface $handler): static
    {
        $handlerName = is_string($handler) ? $handler : $handler::class;
        $this->handlers[$handlerName] = $handler;
        return $this;
    }
    
    /**
     * Remove a route handler ny name.
     *
     * @param string $name
     * @return static $this
     */
    public function removeHandler(string $name): static
    {
        unset($this->handlers[$name]);
        return $this;
    }
    
    /**
     * Handles the route.
     *
     * @param RouteInterface $route
     * @param null|ServerRequestInterface $request
     * @return mixed The return value of the handler called.
     */    
    public function handle(RouteInterface $route, null|ServerRequestInterface $request = null): mixed
    {
        // Handle middleware if any.
        if (is_array($route->getParameter('middleware'))) {
            if (
                ! $this->app->has(MiddlewareDispatcherInterface::class)
                || is_null($request)
            ) {
                return $this->processHandlers($route, $request);
            }
            
            $middlewareDispatcher = $this->app->get(MiddlewareDispatcherInterface::class)->new();
            
            $middlewareDispatcher->add(...$route->getParameter('middleware'));
            
            $middlewareDispatcher->add([MiddlewareRouteHandler::class, 'route' => $route]);

            $response = $middlewareDispatcher->handle($request);
            
            return $response;
        }
        
        return $this->processHandlers($route, $request);
    }

    /**
     * Process the handlers.
     *
     * @param RouteInterface $route
     * @param null|ServerRequestInterface $request
     * @return mixed The called function result.
     */
    protected function processHandlers(RouteInterface $route, null|ServerRequestInterface $request): mixed
    {
        $routeHandler = $route->getHandler();
        $arguments = $route->getParameter('request_parameters', []);
        
        if (
            is_array($routeHandler)
            && isset($routeHandler[2])
            && is_array($routeHandler[2])
        ) {
            $arguments = array_merge($arguments, $routeHandler[2]);
        }
        
        $arguments = new ArgumentsHandlerParameters($arguments);
        $route->parameter('_arguments', $arguments);
        $route->parameter('_declared', new DeclaredHandlerParameters($routeHandler));
        
        foreach($this->handlers as $handler) {
            
            $handler = $this->createHandler($handler);
            
            $result = $handler->handle($route, $request);
            
            if (!is_array($result)) {
                continue;
            }
            
            $route = $result[0] ?? $route;
            $request = $result[1] ?? $request;
            
            if (array_key_exists(2, $result)) {
                return $result[2];
            }
        }
        
        return $this->app->call($routeHandler, $arguments->getParameters());
    }
    
    /**
     * Create the handler.
     *
     * @param string|ServiceRouteHandlerInterface $handler
     * @return ServiceRouteHandlerInterface
     */
    protected function createHandler(string|ServiceRouteHandlerInterface $handler): ServiceRouteHandlerInterface
    {
        if (!is_string($handler)) {
            return $handler;
        }
        
        return $this->app->get($handler);
    }
}