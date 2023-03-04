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
use Tobento\App\Http\Boot\Middleware;
use Tobento\App\Http\RouteHandler;
use Tobento\App\Http\HttpErrorHandlersInterface;
use Tobento\Service\Routing\Middleware\Routing as RoutingMiddleware;
use Tobento\Service\Routing\Middleware\MethodOverride;
use Tobento\Service\Routing\Middleware\PreRouting;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Routing\Router;
use Tobento\Service\Routing\RouteInterface;
use Tobento\Service\Routing\RouteGroupInterface;
use Tobento\Service\Routing\RouteResourceInterface;
use Tobento\Service\Routing\RequestData;
use Tobento\Service\Routing\UrlGenerator;
use Tobento\Service\Routing\UrlInterface;
use Tobento\Service\Routing\UrlException;
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
use Closure;
use Throwable;

/**
 * Routing boot.
 */
class Routing extends Boot
{
    public const INFO = [
        'boot' => [
            'boots http and middleware boot',
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
        Middleware::class,
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

        // App macros.
        $this->app->addMacro('route', [$this, 'route']);
        $this->app->addMacro('routeGroup', [$this, 'group']);
        $this->app->addMacro('routeResource', [$this, 'resource']);
        $this->app->addMacro('routeMatched', [$this, 'matched']);
        $this->app->addMacro('routeUrl', [$this, 'url']);

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
     * Create a new Route.
     * 
     * @param string $method The method such as 'GET'
     * @param string $uri The route uri such as 'foo/{id}'
     * @param mixed $handler The handler if route is matching.
     * @return RouteInterface
     */
    public function route(string $method, string $uri, mixed $handler): RouteInterface
    {
        return $this->app->get(RouterInterface::class)->route($method, $uri, $handler);
    }
    
    /**
     * Create a new RouteGroup.
     * 
     * @param string $uri The route uri such as 'foo/{id}'
     * @param Closure $callback
     * @return RouteGroupInterface
     */
    public function group(string $uri, Closure $callback): RouteGroupInterface
    {
        return $this->app->get(RouterInterface::class)->group($uri, $callback);
    }
    
    /**
     * Create a new RouteResource.
     * 
     * @param string $name The resource name
     * @param string $controller The controller
     * @param string $placeholder The placeholder name for the uri
     * @return RouteResourceInterface
     */
    public function resource(string $name, string $controller, string $placeholder = 'id'): RouteResourceInterface
    {
        return $this->app->get(RouterInterface::class)->resource($name, $controller, $placeholder);
    }
    
    /**
     * Register a matched event listener.
     *
     * @param string $routeName The route name or null for any route.
     * @param callable $callable
     * @param int $priority The priority. Highest first.
     * @return void
     */
    public function matched(string $routeName, callable $callable, int $priority = 0): void
    {
        $this->app->get(RouterInterface::class)->matched($routeName, $callable, $priority);
    }
    
    /**
     * Create a new Url.
     *
     * @param string $name The route name.
     * @param array $parameters The paramters to build the url.
     *
     * @throws UrlException
     *
     * @return UrlInterface
     */    
    public function url(string $name, array $parameters = []): UrlInterface
    {
        return $this->app->get(RouterInterface::class)->url($name, $parameters);
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