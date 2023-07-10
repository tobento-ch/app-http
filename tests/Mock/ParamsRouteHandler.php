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

use Tobento\Service\Routing\RouteInterface;
use Tobento\Service\Routing\RouteHandlerInterface;
use Tobento\App\Http\Routing\DeclaredHandlerParameters;
use Tobento\App\Http\Routing\ArgumentsHandlerParameters;
use Psr\Http\Message\ServerRequestInterface;

final class ParamsRouteHandler implements RouteHandlerInterface
{
    /**
     * Handles the route.
     *
     * @param RouteInterface $route
     * @param null|ServerRequestInterface $request
     * @return mixed The return value of the handler called.
     */
    public function handle(RouteInterface $route, null|ServerRequestInterface $request = null): mixed
    {
        $arguments = $route->getParameter('_arguments');
        $declared = $route->getParameter('_declared');
        
        $request = $request->withAttribute('_arguments', $arguments instanceof ArgumentsHandlerParameters);
        
        $request = $request->withAttribute('_declared', $declared instanceof DeclaredHandlerParameters);
        
        return [$route, $request];
    }
}