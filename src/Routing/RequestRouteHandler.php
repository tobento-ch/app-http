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

use Tobento\App\AppInterface;
use Tobento\Service\Routing\RouteInterface;
use Tobento\Service\Routing\RouteHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionNamedType;

/**
 * Adds request object handler argument for request declaration parameters.
 */
class RequestRouteHandler implements RouteHandlerInterface
{
    /**
     * Create a new RequestRouteHandler.
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
        if (is_null($request)) {
            return null;
        }
        
        $this->app->set(ServerRequestInterface::class, $request)->prototype();
        
        $arguments = $route->getParameter('_arguments');
        $declared = $route->getParameter('_declared');
        
        if (! $arguments instanceof ArgumentsHandlerParameters) {
            return null;
        }
        
        if (! $declared instanceof DeclaredHandlerParameters) {
            return null;
        }
        
        foreach($declared->getParameters() as $parameter) {
            $type = $parameter->getType();
            
            if (!$type instanceof ReflectionNamedType) {
                
                if ($parameter->getName() === 'request') {
                    $arguments->add($parameter->getName(), $request);
                }
                
                continue;
            }
    
            if ($type->isBuiltin()) {
                continue;
            }
            
            if ($type->getName() === ServerRequestInterface::class) {
                $arguments->add($parameter->getName(), $request);
            }
        }
        
        return [$route, $request];
    }
}