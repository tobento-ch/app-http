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
        - [Swap PSR-7 And PSR-17 Implementation](#swap-psr-7-and-psr-17-implementation)
    - [Requester And Responser Boot](#requester-and-responser-boot)
    - [Middleware Boot](#middleware-boot)
        - [Add Middleware via Boot](#add-middleware-via-boot)
        - [Middleware Aliases](#middleware-aliases)
    - [Routing Boot](#routing-boot)
        - [Routing via Boot](#routing-via-boot)
        - [Domain Routing](#domain-routing)
    - [Session Boot](#session-boot)
        - [Session Config](#session-config)
        - [Session Lifecycle](#session-lifecycle)
        - [Session Error Handling](#session-error-handling)
    - [Cookies Boot](#cookies-boot)
        - [Cookies Config](#cookies-config)
        - [Cookies Usage](#cookies-usage)
        - [Cookies Encryption](#cookies-encryption)
    - [Area Boot](#area-boot)
        - [Create And Boot Area](#create-and-boot-area)
        - [Area Config](#area-config)
    - [Error Handler Boot](#error-handler-boot)
        - [Render Exception Views](#render-exception-views)
        - [Handle Other Exceptions](#handle-other-exceptions)
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
use Tobento\Service\Uri\PreviousUriInterface;

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
$previousUri = $app->get(PreviousUriInterface::class);
// Session Boot is needed, otherwise it is always same as base uri.

// PSR-17
$responseFactory = $app->get(ResponseFactoryInterface::class);
$streamFactory = $app->get(StreamFactoryInterface::class);
$uploadedFileFactory = $app->get(UploadedFileFactoryInterface::class);
$uriFactory = $app->get(UriFactoryInterface::class);

// Run the app
$app->run();
```

Check out the [**Uri Service**](https://github.com/tobento-ch/service-uri) to learn more about the base and current uri.

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

## Routing Boot

The routing boot does the following:

* boots http and middleware boot
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

### Domain Routing

You may specify the domains for routing in the ```app/config/http.php``` file.

Check out the [**Routing Service - Domain Routing**](https://github.com/tobento-ch/service-routing#domain-routing) section to learn more about domain routing.

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

You may handle these exceptions with the [**Error Handler - Handle Other Exceptions**](#handle-other-exceptions) instead.

Check out the [**Throwable Handlers**](https://github.com/tobento-ch/service-error-handler#throwable-handlers) to learn more about handlers in general.

## Cookies Boot

The cookies boot does the following:

* implements [*cookie interfaces*](https://github.com/tobento-ch/service-cookie) based on cookies config file
* adds middleware based on cookies config file

```php
use Tobento\App\AppFactory;
use Tobento\Service\Cookie\CookiesFactoryInterface;
use Tobento\Service\Cookie\CookieFactoryInterface;
use Tobento\Service\Cookie\CookieValuesFactoryInterface;
use Tobento\Service\Cookie\CookiesProcessorInterface;

// Create the app
$app = (new AppFactory())->createApp();

// Adding boots
$app->boot(\Tobento\App\Http\Boot\Cookies::class);
$app->booting();

// The following interfaces are available after booting:
$cookiesFactory = $app->get(CookiesFactoryInterface::class);
$cookieFactory = $app->get(CookieFactoryInterface::class);
$cookieValuesFactory = $app->get(CookieValuesFactoryInterface::class);
$cookiesProcessor = $app->get(CookiesProcessorInterface::class);

// Run the app
$app->run();
```

Check out the [**Cookie Service**](https://github.com/tobento-ch/service-cookie) to learn more it.

### Cookies Config

Check out ```app/config/cookies.php``` to change needed values.

### Cookies Usage

**Read and write cookies**

```php
use Tobento\App\AppFactory;
use Tobento\Service\Cookie\CookieValuesInterface;
use Tobento\Service\Cookie\CookiesInterface;
use Psr\Http\Message\ServerRequestInterface;

// Create the app
$app = (new AppFactory())->createApp();

// Adding boots
$app->boot(\Tobento\App\Http\Boot\Routing::class);
$app->boot(\Tobento\App\Http\Boot\Cookies::class);
$app->booting();

$app->route('GET', 'bar', function(ServerRequestInterface $request) {

    // read cookies:
    $cookieValues = $request->getAttribute(CookieValuesInterface::class);
    
    $value = $cookieValues->get('foo');
    
    // or
    var_dump($request->getCookieParams());
    
    // write cookies:
    $cookies = $request->getAttribute(CookiesInterface::class);
    
    $cookies->add('name', 'value');
    
    return ['page' => 'bar'];
});

// Run the app
$app->run();
```

Check out the [**Cookie Values**](https://github.com/tobento-ch/service-cookie#cookie-values) to learn more it.

Check out the [**Cookies**](https://github.com/tobento-ch/service-cookie#cookies) to learn more it.

### Cookies Encryption

First install the app-encryption bundle:

```
composer require tobento/app-encryption
```

Then, just boot the ```Encryption::class``` if you want to encrypt and decrypt all cookies values. That's all.

```php
// ...

$app->boot(\Tobento\App\Encryption\Boot\Encryption::class);
$app->boot(\Tobento\App\Http\Boot\Cookies::class);

// ...
```

**Whitelist cookie**

To whitelist a cookie (disable encryption), use the ```CookiesProcessorInterface::class``` after the booting:

```php
use Tobento\Service\Cookie\CookiesProcessorInterface;

$cookiesProcessor = $app->get(CookiesProcessorInterface::class);

$cookiesProcessor->whitelistCookie(name: 'name');

// or
$cookiesProcessor->whitelistCookie(name: 'name[foo]');
$cookiesProcessor->whitelistCookie(name: 'name[bar]');
```

**Configuration**

The encrypting and decrypting is done with the implemented ```CookiesProcessor::class``` processed by the specified middleware in the ```app/config/cookies.php``` file.

```php
use Tobento\Service\Cookie;
use Tobento\Service\Encryption;
use Psr\Container\ContainerInterface;

return [

    'middlewares' => [
        Cookie\Middleware\Cookies::class,
    ],

    'interfaces' => [

        //...

        Cookie\CookiesProcessorInterface::class => Cookie\CookiesProcessor::class,

        // or you may use a specified encrypter only for cookies:
        Cookie\CookiesProcessorInterface::class => static function(ContainerInterface $c): Cookie\CookiesProcessorInterface {

            $encrypter = null;

            if (
                $c->has(Encryption\EncryptersInterface::class)
                && $c->get(Encryption\EncryptersInterface::class)->has('cookies')
            ) {
                $encrypter = $c->get(Encryption\EncryptersInterface::class)->get('cookies');
            }

            return new Cookie\CookiesProcessor(
                encrypter: $encrypter,
                whitelistedCookies: [],
            );
        },
    ],

    //...
];
```

You may check out the [**App Encryption**](https://github.com/tobento-ch/app-encryption) to learn more about it.

## Area Boot

The area boot may be used to create complex admin areas and other applications areas. The boots within the area running in its own application.

### Create And Boot Area

First, create your area boot by extending the ```AreaBoot::class```.

```php
use Tobento\App\Http\AreaBoot;

class BackendBoot extends AreaBoot
{
    public const INFO = [
        'boot' => [
            'Backend Area',
        ],
    ];
    
    // Specify your area boots:
    protected const AREA_BOOT = [
        \Tobento\App\Boot\App::class,
        \Tobento\App\Http\Boot\Middleware::class,
        \Tobento\App\Http\Boot\Routing::class,
    ];
    
    protected const AREA_KEY = 'backend';
    
    protected const AREA_SLUG = 'private';
    
    // You may set a domain for the routing e.g. api.example.com
    // In addition, you may the slug to an empty string,
    // otherwise it gets appended e.g. api.example.com/slug
    protected const AREA_DOMAIN = '';
    
    // You may set a migration to be installed on booting e.g Migration::class
    protected const MIGRATION = '';
}
```

Next, boot your area:

```php
use Tobento\App\AppFactory;

// Create the app
$app = (new AppFactory())->createApp();

// Adding boots
$app->boot(BackendBoot::class);

// Run the app
$app->run();
```

You may also boot your area boot by another boot:

```php
use Tobento\App\Boot;

class ShopBoot extends Boot
{
    public const INFO = [
        'boot' => [
            'Shop',
        ],
    ];

    public const BOOT = [
        BackendBoot::class,
        FrontendBoot::class,
    ];
    
    public function boot(BackendBoot $backend, FrontendBoot $frontend): void
    {
        $backend->addBoot(ShopBackend::class);
        $frontend->addBoot(ShopFrontend::class);
    }
}
```

### Area Config

You may copy ```config/area.php``` to ```app/config/``` directory and rename it to your specified ```AREA_KEY``` contstant. You may do so by using a migration.

## Error Handler Boot

By default, the error handler will render exceptions in json or plain text format. If you want to render exception views to support html and xml formats check out the [Render Exception Views](#render-exception-views) section.

```php
// ...
$app->boot(\Tobento\App\Http\Boot\ErrorHandler::class);
// ...
```

It handles the following exceptions:

| As Code | Exception |
| --- | --- |
| 404 | ```Tobento\Service\Routing\RouteNotFoundException``` |
| 403 | ```Tobento\Service\Routing\InvalidSignatureException``` |
| 403 | ```Tobento\Service\Session\SessionValidationException``` |
| 403 | ```Tobento\Service\Form\InvalidTokenException``` |
| 500 | Any other not handled before |

### Render Exception Views

In order to render exceptions in html or xml format, the ```ViewInterface::class``` must be available within the app. You might install the [App View](https://github.com/tobento-ch/app-view) bundle or just implement the ```ViewInterface::class```:

```
composer require tobento/service-view
```

```php
use Tobento\Service\View;
use Tobento\Service\Dir\Dirs;
use Tobento\Service\Dir\Dir;

// ...
$app->set(View\ViewInterface::class, function() {
    return new View\View(
        new View\PhpRenderer(
            new Dirs(
                new Dir('home/private/views/'),
            )
        ),
        new View\Data(),
        new View\Assets('home/public/src/', 'https://www.example.com/src/')
    );
});
// ...
```

It renders the following view if exist:

| View | Description |
| --- | --- |
| ```exception/403.php``` | Any specific error with the named code. |
| ```exception/403.xml.php``` | Any specific error with the named code in xml format. |
| ```exception/error.php``` | If specific does not exist. |
| ```exception/error.xml.php``` | If specific does not exist in xml format. |

### Handle Other Exceptions

You might handle other exceptions by just exending the error handler:

```php
use Tobento\App\Http\Boot\ErrorHandler;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Responser\ResponserInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class CustomErrorHandler extends ErrorHandler
{
    public function handleThrowable(Throwable $t): Throwable|ResponseInterface
    {
        $requester = $this->app->get(RequesterInterface::class);
        
        if ($t instanceof SomeException) {
            return $requester->wantsJson()
                ? $this->renderJson(code: 404)
                : $this->renderView(code: 404);
        }
        
        // using the responser:
        if ($t instanceof SomeOtherException) {
            $responser = $this->app->get(ResponserInterface::class);
            
            return $responser->json(
                data: ['key' => 'value'],
                code: 200,
            );
        }        
        
        return parent::handleThrowable($t);
    }
}
```

And boot your custom error handler instead of the default:

```php
// ...
$app->boot(CustomErrorHandler::class);
// ...
```

# Credits

- [Tobias Strub](https://www.tobento.ch)
- [All Contributors](../../contributors)