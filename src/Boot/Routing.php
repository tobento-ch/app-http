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
use Tobento\App\Http\Boot\Http;
use Tobento\App\Http\RouteHandler;
use Tobento\App\Http\HttpErrorHandlersInterface;
use Tobento\Service\Routing\Middleware\Routing as RoutingMiddleware;
use Tobento\Service\Routing\Middleware\MethodOverride;
use Tobento\Service\Routing\Middleware\PreRouting;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Routing\Router;
use Tobento\Service\Routing\RequestData;
use Tobento\Service\Routing\UrlGenerator;
use Tobento\Service\Routing\UrlInterface;
use Tobento\Service\Routing\RouteFactory;
use Tobento\Service\Routing\RouteDispatcher;
use Tobento\Service\Routing\Constrainer\Constrainer;
use Tobento\Service\Routing\MatchedRouteHandler;
use Tobento\Service\Routing\RouteResponseParser;
use Tobento\Service\Routing\RouteNotFoundException;
use Tobento\Service\Routing\InvalidSignatureException;
use Tobento\Service\Routing\TranslationException;
use Tobento\Service\Middleware\MiddlewareDispatcherInterface;
use Tobento\Service\Config\ConfigInterface;
use Tobento\Service\Uri\BaseUriInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Throwable;

/**
 * Routing boot.
 */
class Routing extends Boot
{
    public const INFO = [
        'boot' => [
            RouterInterface::class.' implementation',
            'adds routing macro',
            'adds http error handler for routing exceptions',
        ],
        'terminate' => [
            'adds routing middleware if supported',
            'dispatches routes and passes response to http boot',
        ],  
    ];
    
    public const BOOT = [
        Http::class,
    ];
    
    public const REBOOTABLE = ['terminate'];
    
    /**
     * Boot application services.
     *
     * @return void
     * @psalm-suppress UndefinedMethod
     */
    public function boot(): void
    {
        // RouterInterface
        $this->app->set(RouterInterface::class, function() {
            
            $request = $this->app->get(ServerRequestInterface::class);
            
            // set base uri
            if ($this->app->has(BaseUriInterface::class)) {
                $baseUri = $this->app->get(BaseUriInterface::class);
            } else {
                $baseUri = $request->getUri()->withPath('')->withQuery('')->withFragment('');
            }

            $config = $this->app->get(ConfigInterface::class);
            $container = $this->app->get(ContainerInterface::class);
            
            $router = new Router(
                new RequestData(
                    $request->getMethod(),
                    rawurldecode($request->getUri()->getPath()),
                    $request->getUri()->getHost()
                ),
                new UrlGenerator(
                    (string)$baseUri,
                    $config->get('http.signature_key', 'a-random-32-character-secret-signature-key'),
                ),
                new RouteFactory(),
                new RouteDispatcher($container, new Constrainer()),
                new RouteHandler($this->app),
                new MatchedRouteHandler($container),
                new RouteResponseParser(),
            );
            
            $router->setRequestAttributes(['uri', 'name', 'request_uri', 'can']);
            
            // set base uri.
            if ($this->app->has(BaseUriInterface::class)) {
                $baseUri = $this->app->get(BaseUriInterface::class);
                $basePath = rtrim($baseUri->getPath(), '/').'/';
                $router->setBaseUri($basePath);
            }
            
            return $router;
        });
        
        $router = $this->app->get(RouterInterface::class);
        
        // App macros.
        $this->app->addMacro('route', [$router, 'route']);
        $this->app->addMacro('routeGroup', [$router, 'group']);
        $this->app->addMacro('routeResource', [$router, 'resource']);
        $this->app->addMacro('routeMatched', [$router, 'matched']);
        $this->app->addMacro('routeUrl', function(string $name, array $parameters = []): UrlInterface {
            return $this->get(RouterInterface::class)->url($name, $parameters);
        });
        
        // Default HttpErrorHandlers for router exceptions.
        // You may change its behaviour with adding handlers with higher priority.
        $this->app->on(HttpErrorHandlersInterface::class, function(HttpErrorHandlersInterface $handlers) {

            $handlers->add(function(Throwable $t) {
                
                $responseFactory = $this->app->get(ResponseFactoryInterface::class);
                
                if ($t instanceof RouteNotFoundException) {
                    
                    $response = $responseFactory->createResponse(404);
                    $response->getBody()->write(json_encode([
                        'statusCode' => 404,
                        'message' => 'The requested page is not found',
                    ]));
                    return $response->withHeader('Content-Type', 'application/json');
                    
                } elseif ($t instanceof InvalidSignatureException) {
                    
                    $response = $responseFactory->createResponse(403);
                    $response->getBody()->write(json_encode([
                        'statusCode' => 403,
                        'message' => 'The signature of the requested page is invalid',
                    ]));
                    return $response->withHeader('Content-Type', 'application/json');
                    
                } elseif ($t instanceof TranslationException) {
                    // ignore
                }
                
                return $t;
            });
        });
    }
    
    /**
     * Terminate application services.
     *
     * @param Http $http
     * @return void
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function terminate(Http $http): void
    {
        if ($this->app->has(MiddlewareDispatcherInterface::class)) {
            $middleware = $this->app->get(MiddlewareDispatcherInterface::class);
            $middleware->add(MethodOverride::class, priority: 5100);
            $middleware->add(PreRouting::class, priority: 5000);
            $middleware->add(RoutingMiddleware::class, priority: 1000);
        } else {
            $http->setResponse($this->processRouting());
        }
    }
    
    /**
     * Process the routing if no middleware are used.
     *
     * @return ResponseInterface
     */
    protected function processRouting(): ResponseInterface
    {
        $request = $this->app->get(ServerRequestInterface::class);
        $response = $this->app->get(ResponseInterface::class);
        $router = $this->app->get(RouterInterface::class);

        try {
            $matchedRoute = $router->dispatch();
            
            // add route parameters to request.
            foreach($router->getRequestAttributes() as $attribute) {
                if ($matchedRoute->hasParameter($attribute)) {
                    $request = $request->withAttribute('route.'.$attribute, $matchedRoute->getParameter($attribute));
                }
            }

            // call matched route handler for handling registered matched event actions.
            $router->getMatchedRouteHandler()->handle($matchedRoute, $request);

            // handle the matched route.
            $routeResponse = $router->getRouteHandler()
                                    ->handle($matchedRoute, $request);

            // parse the route response.
            return $router->getRouteResponseParser()->parse($response, $routeResponse);
            
        } catch (Throwable $e) {
            // handle exception
            $response = $this->app->get(HttpErrorHandlersInterface::class)->handleThrowable($e);

            if ($response instanceof ResponseInterface) {
                return $response;
            }
            
            throw $e;
        }
    }
}