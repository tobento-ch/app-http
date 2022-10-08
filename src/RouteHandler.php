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

namespace Tobento\App\Http;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tobento\App\AppInterface;
use Tobento\Service\Routing\RouteInterface;
use Tobento\Service\Routing\RouteHandlerInterface;
use Tobento\Service\Routing\Middleware\RouteHandler as MiddlewareRouteHandler;
use Tobento\Service\Routing\RouteResponseParserInterface;
use Tobento\Service\Middleware\MiddlewareDispatcherInterface;

/**
 * RouteHandler
 */
class RouteHandler implements RouteHandlerInterface
{
    /**
     * Create a new RouteHandler
     *
     * @param AppInterface $app,
     */    
    public function __construct(
        protected AppInterface $app
    ) {}
    
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
        if (is_array($route->getParameter('middleware')))
        {            
            if (
                ! $this->app->has(MiddlewareDispatcherInterface::class)
                || is_null($request)
            ) {
                return $this->callRouteHandler($route, $request);
            }
            
            $middlewareDispatcher = $this->app->get(MiddlewareDispatcherInterface::class);
            
            $middlewareDispatcher->add(...$route->getParameter('middleware'));
                
            $middlewareDispatcher->add([MiddlewareRouteHandler::class, 'route' => $route]);

            $response = $middlewareDispatcher->handle($request);
            
            return $response;
        }
                
        return $this->callRouteHandler($route, $request);
    }
    
    /**
     * Call the route handler.
     *
     * @param RouteInterface $route
     * @param null|ServerRequestInterface $request
     * @return mixed The called function result.
     */
    protected function callRouteHandler(RouteInterface $route, null|ServerRequestInterface $request): mixed
    {
        $handler = $route->getHandler();
        $requestParams = $route->getParameter('request_parameters', []);
        
        if (is_array($handler) && isset($handler[2]) && is_array($handler[2]))
        {
            $requestParams = array_merge($requestParams, $handler[2]);
        }
        
        if (!is_null($request)) {
            $requestParams['request'] = $request;
        }
        
        $this->app->set(ServerRequestInterface::class, $request)->prototype();
        
        return $this->app->call($handler, $requestParams);
    }
}