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

namespace Tobento\App\Http\Test\Boot\Routing;

use PHPUnit\Framework\TestCase;
use Tobento\App\AppFactory;
use Tobento\App\Http\Routing\RouteHandler;
use Tobento\Service\Routing\Route;
use Tobento\Service\Routing\UrlGenerator;

/**
 * RouteHandlerTest
 */
class RouteHandlerTest extends TestCase
{
    public function testHandleMethodUsingRouteClosureHandler()
    {
        $app = (new AppFactory())->createApp();
        $handler = new RouteHandler(app: $app);
        
        $route = new Route(
            urlGenerator: new UrlGenerator(urlBase: '', signatureKey: ''),
            method: 'GET',
            uri: 'foo',
            handler: function () {
                return 'Foo';
            },
        );

        $this->assertSame('Foo', $handler->handle(route: $route));
    }
    
    public function testHandleMethodUsingRouteArrayClassHandler()
    {
        $app = (new AppFactory())->createApp();
        $handler = new RouteHandler(app: $app);
        
        $route = new Route(
            urlGenerator: new UrlGenerator(urlBase: '', signatureKey: ''),
            method: 'GET',
            uri: 'foo',
            handler: [RouteHandlerController::class, 'foo'],
        );

        $this->assertSame('Foo', $handler->handle(route: $route));
    }
    
    public function testHandleMethodUsingRouteArrayClassHandlerWithAppOn()
    {
        $app = (new AppFactory())->createApp();
        $app->on(RouteHandlerController::class, CustomRouteHandlerController::class);
        $handler = new RouteHandler(app: $app);
        
        $route = new Route(
            urlGenerator: new UrlGenerator(urlBase: '', signatureKey: ''),
            method: 'GET',
            uri: 'foo',
            handler: [RouteHandlerController::class, 'foo'],
        );

        $this->assertSame('CustomFoo', $handler->handle(route: $route));
    }
    
    public function testHandleMethodUsingRouteInvokableClassHandler()
    {
        $app = (new AppFactory())->createApp();
        $handler = new RouteHandler(app: $app);
        
        $route = new Route(
            urlGenerator: new UrlGenerator(urlBase: '', signatureKey: ''),
            method: 'GET',
            uri: 'foo',
            handler: RouteHandlerController::class,
        );

        $this->assertSame('Invoked', $handler->handle(route: $route));
    }
    
    public function testHandleMethodUsingRouteInvokableClassHandlerWithAppOn()
    {
        $app = (new AppFactory())->createApp();
        $app->on(RouteHandlerController::class, CustomRouteHandlerController::class);
        $handler = new RouteHandler(app: $app);
        
        $route = new Route(
            urlGenerator: new UrlGenerator(urlBase: '', signatureKey: ''),
            method: 'GET',
            uri: 'foo',
            handler: RouteHandlerController::class,
        );

        $this->assertSame('CustomInvoked', $handler->handle(route: $route));
    }
    
    public function testHandleMethodUsingRouteClassMethodHandler()
    {
        $app = (new AppFactory())->createApp();
        $handler = new RouteHandler(app: $app);
        
        $route = new Route(
            urlGenerator: new UrlGenerator(urlBase: '', signatureKey: ''),
            method: 'GET',
            uri: 'foo',
            handler: __NAMESPACE__.'\RouteHandlerController::foo',
        );

        $this->assertSame('Foo', $handler->handle(route: $route));
    }
    
    public function testHandleMethodUsingRouteClassMethodHandlerWithAppOn()
    {
        $app = (new AppFactory())->createApp();
        $app->on(RouteHandlerController::class, CustomRouteHandlerController::class);
        $handler = new RouteHandler(app: $app);
        
        $route = new Route(
            urlGenerator: new UrlGenerator(urlBase: '', signatureKey: ''),
            method: 'GET',
            uri: 'foo',
            handler: __NAMESPACE__.'\RouteHandlerController::foo',
        );

        $this->assertSame('CustomFoo', $handler->handle(route: $route));
    }
}

class RouteHandlerController
{
    public function foo(): string
    {
        return 'Foo';
    }
    
    public function __invoke()
    {
        return 'Invoked';
    }
}

class CustomRouteHandlerController
{
    public function foo(): string
    {
        return 'CustomFoo';
    }
    
    public function __invoke()
    {
        return 'CustomInvoked';
    }
}