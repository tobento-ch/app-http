# App Http

Http, routing, middleware and session support for the app.

## Table of Contents

- [Getting Started](#getting-started)
    - [Requirements](#requirements)
- [Documentation](#documentation)
    - [App](#app)
    - [Http Boot](#http-boot)
        - [Http Config](#http-config)
        - [Request And Response](#request-and-response)
        - [Error Handling](#error-handling)
        - [Swap PSR-7 And PSR-17 Implementation](#swap-psr-7-and-psr-17-implementation)
    - [Requester And Responser Boot](#requester-and-responser-boot)
    - [Middleware Boot](#middleware-boot)
        - [Add Middleware via Boot](#add-middleware-via-boot)
        - [Middleware Aliases](#middleware-aliases)
        - [Middleware Error Handling](#middleware-error-handling)    
    - [Routing Boot](#routing-boot)
        - [Routing via Boot](#routing-via-boot)
        - [Routing And Middleware](#routing-and-middleware)
        - [Router Error Handling](#router-error-handling)
    - [Session Boot](#session-boot)
        - [Session Config](#session-config)
        - [Session Lifecycle](#session-lifecycle)
        - [Session Error Handling](#session-error-handling)
- [Credits](#credits)
___

# Getting Started

Add the latest version of the app http project running this command.

```
composer require tobento/app-http
```

## Requirements

- PHP 8.0 or greater

# Documentation

## App

Check out the [**App Skeleton**](https://github.com/tobento-ch/app-skeleton) if you are using the skeleton.

You may also check out the [**App**](https://github.com/tobento-ch/app) to learn more about the app in general.

## Http Boot

The http boot does the following:

* PSR-7 implementations
* PSR-17 implementations
* installs and loads http config file
* base and current uri implementation
* http error handling implementation
* emits response

```php
use Tobento\App\AppFactory;

// Create the app
$app = (new AppFactory())->createApp();

// Adding boots
$app->boot(\Tobento\App\Http\Boot\Http::class);

// Run the app
$app->run();
```

### Http Config

Check out ```app/config/http.php``` to change needed values.

### Request And Response

You may access the PSR-7 and PSR-17 interfaces by the app:

```php
use Tobento\App\AppFactory;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Tobento\Service\Uri\BaseUriInterface;
use Tobento\Service\Uri\CurrentUriInterface;

// Create the app
$app = (new AppFactory())->createApp();

// Adding boots
$app->boot(\Tobento\App\Http\Boot\Http::class);
$app->booting();

// PSR-7
$request = $app->get(ServerRequestInterface::class);
$response = $app->get(ResponseInterface::class);

// returns UriInterface
$baseUri = $app->get(BaseUriInterface::class);
$currentUri = $app->get(CurrentUriInterface::class);

// PSR-17
$responseFactory = $app->get(ResponseFactoryInterface::class);
$streamFactory = $app->get(StreamFactoryInterface::class);
$uploadedFileFactory = $app->get(UploadedFileFactoryInterface::class);
$uriFactory = $app->get(UriFactoryInterface::class);

// Run the app
$app->run();
```

Check out the [**Uri Service**](https://github.com/tobento-ch/service-uri) to learn more about the base and current uri.

### Error Handling

Check out the [**Router Error Handling**](#router-error-handling) to handle errors caused by the router.

Check out the [**Middleware Error Handling**](#middleware-error-handling) to handle errors caused by middlewares.

### Swap PSR-7 And PSR-17 Implementation

You might swap the PSR-7 and PSR-17 implementation to any alternative.\
Check out the [**App - Customization**](https://github.com/tobento-ch/app#customization) to learn more about it.

## Requester And Responser Boot

The requester and responser boot does the following:

* [*RequesterInterface*](https://github.com/tobento-ch/service-requester) implementation
* [*ResponserInterface*](https://github.com/tobento-ch/service-responser) implementation and adds its middleware

```php
use Tobento\App\AppFactory;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Responser\ResponserInterface;

// Create the app
$app = (new AppFactory())->createApp();

// You may add the session boot to enable
// flash messages and flash input data.
$app->boot(\Tobento\App\Http\Boot\Session::class);

$app->boot(\Tobento\App\Http\Boot\RequesterResponser::class);
$app->booting();

$requester = $app->get(RequesterInterface::class);
$responser = $app->get(ResponserInterface);

// Run the app
$app->run();
```

Check out the [**Requester Service**](https://github.com/tobento-ch/service-requester) to learn more about it.

Check out the [**Responser Service**](https://github.com/tobento-ch/service-responser) to learn more about it.

**Added Middleware**

| Class | Description |
| --- | --- |
| Tobento\Service\Responser\Middleware\Responser::class | Adds the responser to the request attributes. |
| Tobento\Service\Responser\Middleware\ResponserMergeInput::class | Merges the responser input with the request input. |

## Middleware Boot

The middleware boot does the following:

* PSR-15 HTTP handlers (middleware) implementation
* dispatches middleware

```php
use Tobento\App\AppFactory;

// Create the app
$app = (new AppFactory())->createApp();

// Adding boots
$app->boot(\Tobento\App\Http\Boot\Middleware::class);
$app->booting();

// add middleware aliases using app macro:
$app->middlewareAliases([
    'alias' => FooMiddleware::class,
]);

// add middleware using app macro:
$app->middleware(BarMiddleware::class);

// Run the app
$app->run();
```

Check out the [**Middleware Service**](https://github.com/tobento-ch/service-middleware) to learn more about the middleware implementation.

### Add Middleware via Boot

You might create a boot for adding middleware:

```php
use Tobento\App\Boot;
use Tobento\App\Http\Boot\Middleware;

class MyMiddlewareBoot extends Boot
{
    public const BOOT = [
        // you may ensure the middleware boot.
        Middleware::class,
    ];
    
    public function boot(Middleware $middleware)
    {
        $middleware->add(MyMiddleware::class);
    }
}
```

### Middleware Aliases

```php
use Tobento\App\Boot;
use Tobento\App\Http\Boot\Middleware;

class MyMiddlewareBoot extends Boot
{
    public const BOOT = [
        // you may ensure the middleware boot.
        Middleware::class,
    ];
    
    public function boot(Middleware $middleware)
    {
        $middleware->addAliases([
            'alias' => MyMiddleware::class,
        ]);
        
        // add by alias.
        $middleware->add('alias');
    }
}
```

### Middleware Error Handling

You may add an error handler for handling exceptions caused by any middleware.

```php
use Tobento\App\Boot;
use Tobento\App\Http\HttpErrorHandlersInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Throwable;

class HttpErrorHandlerBoot extends Boot
{
    public const BOOT = [
        // you may ensure the http boot.
        \Tobento\App\Http\Boot\Http::class,
    ];
    
    public function boot()
    {
        $this->app->on(HttpErrorHandlersInterface::class, function(HttpErrorHandlersInterface $handlers) {

            $handlers->add(function(Throwable $t) {
                
                $responseFactory = $this->app->get(ResponseFactoryInterface::class);
                
                if ($t instanceof SomeMiddlewareException) {
                    $response = $responseFactory->createResponse(404);
                    $response->getBody()->write('Exception handled');
                    return $response;
                }
                
                return $t;
            })->priority(2000); // you might add a priority.
        });
    }
}
```

Check out the [**Throwable Handlers**](https://github.com/tobento-ch/service-error-handler#throwable-handlers) to learn more about handlers in general.

## Routing Boot

The routing boot does the following:

* [*RouterInterface*](https://github.com/tobento-ch/service-routing) implementation
* adds routing macro
* adds http error handler for routing exceptions

```php
use Tobento\App\AppFactory;
use Tobento\Service\Routing\RouterInterface;

// Create the app
$app = (new AppFactory())->createApp();

// Adding boots
$app->boot(\Tobento\App\Http\Boot\Routing::class);
$app->booting();

// using interface:
$router = $app->get(RouterInterface::class);
$router->get('blog', function() {
    return ['page' => 'blog'];
});

// using macros:
$app->route('GET', 'foo', function() {
    return ['page' => 'foo'];
});

// Run the app
$app->run();
```

Check out the [**Routing Service**](https://github.com/tobento-ch/service-routing) to learn more about the routing.

### Routing via Boot

You might create a boot for defining routes:

```php
use Tobento\App\Boot;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Routing\RouteGroupInterface;

class RoutesBoot extends Boot
{
    public const BOOT = [
        // you may ensure the routing boot.
        \Tobento\App\Http\Boot\Routing::class,
    ];
    
    public function boot(RouterInterface $router)
    {
        // Add routes on the router
        $router->get('blog', [Controller::class, 'method']);
        
        // Add routes with the provided app macros
        $this->app->route('GET', 'blog', [Controller::class, 'method']);
        
        $this->app->routeGroup('admin', function(RouteGroupInterface $group) {

            $group->get('blog/{id}', function($id) {
                // do something
            });
        });
        
        $this->app->routeResource('products', ProductsController::class);
        
        $this->app->routeMatched('blog.edit', function() {
            // do something after the route has been matched.
        });
        
        $url = $this->app->routeUrl('blog.edit', ['id' => 5])->get();
    }
}
```

Then adding your routes boot on the app:

```php
use Tobento\App\AppFactory;

// Create the app
$app = (new AppFactory())->createApp();

// Adding boots
$app->boot(RoutesBoot::class);

// Run the app
$app->run();
```

### Routing And Middleware

> :warning: **If you want to use routing and middleware it is important to add the routing boot after the middleware boot.**

```php
use Tobento\App\AppFactory;
use Tobento\Service\Routing\RouterInterface;

// Create the app
$app = (new AppFactory())->createApp();

// Adding boots
$app->boot(\Tobento\App\Http\Boot\Middleware::class);
// add routing boot after middleware boot:
$app->boot(\Tobento\App\Http\Boot\Routing::class);
$app->booting();

// Add middlware for every request:
$app->middleware(BarMiddleware::class);

// Add to specific route:
$app->route('GET', 'foo', function() {
    return ['page' => 'foo'];
})->middleware(MyMiddleware::class);

// Run the app
$app->run();
```

### Router Error Handling

You may add an error handler for handling exceptions caused by the router. Make sure your handler has a higher priority so as to get executed before the default set on the \Tobento\App\Http\Boot\Routing::class.

```php
use Tobento\App\Boot;
use Tobento\App\Http\HttpErrorHandlersInterface;
use Tobento\Service\Routing\RouteNotFoundException;
use Tobento\Service\Routing\InvalidSignatureException;
use Tobento\Service\Routing\TranslationException;
use Psr\Http\Message\ResponseFactoryInterface;
use Throwable;

class HttpErrorHandlerBoot extends Boot
{
    public const BOOT = [
        // you may ensure the http boot.
        \Tobento\App\Http\Boot\Http::class,
    ];
    
    public function boot()
    {
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
            })->priority(2000); // add higher priority as default which is 1000.
        });
    }
}
```

Check out the [**Throwable Handlers**](https://github.com/tobento-ch/service-error-handler#throwable-handlers) to learn more about handlers in general.

## Session Boot

The session boot does the following:

* [*SessionInterface*](https://github.com/tobento-ch/service-session) implementation
* adds session middleware

```php
use Tobento\App\AppFactory;
use Tobento\Service\Session\SessionInterface;
use Psr\Http\Message\ServerRequestInterface;

// Create the app
$app = (new AppFactory())->createApp();

// Adding boots
$app->boot(\Tobento\App\Http\Boot\Middleware::class);
$app->boot(\Tobento\App\Http\Boot\Routing::class);
$app->boot(\Tobento\App\Http\Boot\Session::class);
$app->booting();

$app->route('GET', 'foo', function(SessionInterface $session) {
    $session->set('key', 'value');
    return ['page' => 'foo'];
});

// or you may get the session from the request attributes:
$app->route('GET', 'bar', function(ServerRequestInterface $request) {
    $session = $request->getAttribute(SessionInterface::class);
    $session->set('key', 'value');
    return ['page' => 'bar'];
});

// Run the app
$app->run();
```

Check out the [**Session Service**](https://github.com/tobento-ch/service-session) to learn more about the session in general.

### Session Config

Check out ```app/config/session.php``` to change needed values.

### Session Lifecycle

The session gets started and saved by the session middleware whereby interacting with session data is available after.

### Session Error Handling

You may add an error handler for handling exceptions caused by the session middleware.

```php
use Tobento\App\Boot;
use Tobento\App\Http\HttpErrorHandlersInterface;
use Tobento\Service\Session\SessionStartException;
use Tobento\Service\Session\SessionExpiredException;
use Tobento\Service\Session\SessionValidationException;
use Tobento\Service\Session\SessionSaveException;
use Throwable;

class HttpErrorHandlerBoot extends Boot
{
    public const BOOT = [
        // you may ensure the http boot.
        \Tobento\App\Http\Boot\Http::class,
    ];
    
    public function boot()
    {
        $this->app->on(HttpErrorHandlersInterface::class, function(HttpErrorHandlersInterface $handlers) {

            $handlers->add(function(Throwable $t) {
                
                if ($t instanceof SessionStartException) {
                    // You may do something if starting session fails.
                } elseif ($t instanceof SessionExpiredException) {
                    // This is already handled by the session middleware,
                    // so you might check it out.
                } elseif ($t instanceof SessionValidationException) {
                    // You may do something if session validation fails. 
                } elseif ($t instanceof SessionSaveException) {
                    // You may do something if saving session fails. 
                }
                
                return $t;
            })->priority(2000); // you might add a priority.
        });
    }
}
```

You may check out the [**Middleware Error Handling**](#middleware-error-handling) to handle errors caused by any middleware too.

# Credits

- [Tobias Strub](https://www.tobento.ch)
- [All Contributors](../../contributors)