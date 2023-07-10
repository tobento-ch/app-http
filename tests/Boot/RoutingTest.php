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

namespace Tobento\App\Http\Test\Boot;

use PHPUnit\Framework\TestCase;
use Tobento\App\AppInterface;
use Tobento\App\Http\Boot\Http;
use Tobento\App\Http\ResponseEmitterInterface;
use Tobento\App\Http\Routing\RouteHandlerInterface;
use Tobento\App\Http\Test\TestResponse;
use Tobento\App\Http\Test\Mock\ResponseEmitter;
use Tobento\App\Http\Test\Mock\ProductsResource;
use Tobento\App\Http\Test\Mock\RoutesBoot;
use Tobento\App\Http\Test\Mock\ControllerRequestAttribute;
use Tobento\App\Http\Test\Mock\ParamsRouteHandler;
use Tobento\App\Http\Test\Mock\ReturnsResultRouteHandler;
use Tobento\App\AppFactory;
use Tobento\Service\Filesystem\Dir;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Routing\RouteGroupInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Nyholm\Psr7\Factory\Psr17Factory;

/**
 * RoutingTest
 */
class RoutingTest extends TestCase
{
    protected function createApp(bool $deleteDir = true): AppInterface
    {
        if ($deleteDir) {
            (new Dir())->delete(__DIR__.'/../app/');
        }
        
        (new Dir())->create(__DIR__.'/../app/');
        (new Dir())->create(__DIR__.'/../app/config/');
        
        $app = (new AppFactory())->createApp();
        
        $app->dirs()
            ->dir(realpath(__DIR__.'/../app/'), 'app')
            ->dir($app->dir('app').'config', 'config', group: 'config');
        
        // Replace response emitter for testing:
        $app->on(ResponseEmitterInterface::class, ResponseEmitter::class);
        
        $app->boot(\Tobento\App\Http\Boot\Routing::class);
        
        return $app;
    }
    
    public static function tearDownAfterClass(): void
    {
        (new Dir())->delete(__DIR__.'/../app/');
    }
    
    public function testRouterInterface()
    {
        $app = $this->createApp();
        $app->booting();
        
        $this->assertInstanceof(
            RouterInterface::class,
            $app->get(RouterInterface::class)
        );
    }
    
    public function testGetRoute()
    {
        $app = $this->createApp();
        
        $app->on(ServerRequestInterface::class, function() {
            return (new Psr17Factory())->createServerRequest(
                method: 'GET',
                uri: 'foo',
                serverParams: [],
            );
        });

        $app->booting();
        
        $app->route('GET', 'foo', function() {
            return 'foo';
        });

        $app->run();

        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isStatusCode(200)
            ->isBodySame('foo');
    }
    
    public function testRouteGroup()
    {
        $app = $this->createApp();
        
        $app->on(ServerRequestInterface::class, function() {
            return (new Psr17Factory())->createServerRequest(
                method: 'GET',
                uri: 'admin/blog/555',
                serverParams: [],
            );
        });

        $app->booting();
        
        $app->routeGroup('admin', function(RouteGroupInterface $group) {
            $group->get('blog/{id}', function($id) {
                return $id;
            });
        });

        $app->run();

        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isStatusCode(200)
            ->isBodySame('555');    
    }
    
    public function testRouteResource()
    {
        $app = $this->createApp();
        
        $app->on(ServerRequestInterface::class, function() {
            return (new Psr17Factory())->createServerRequest(
                method: 'GET',
                uri: 'products/15/edit',
                serverParams: [],
            );
        });

        $app->booting();
        
        $app->routeResource('products', ProductsResource::class);

        $app->run();

        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isStatusCode(200)
            ->isBodySame('edit/15');
    }
    
    public function testRouteMatched()
    {
        $app = $this->createApp();
        
        $app->on(ServerRequestInterface::class, function() {
            return (new Psr17Factory())->createServerRequest(
                method: 'GET',
                uri: 'foo',
                serverParams: [],
            );
        });

        $app->booting();
        
        $app->route('GET', 'foo', function() {
            return 'foo';
        })->name('foo');
        
        $app->routeMatched('foo', function() {
            $this->assertTrue(true);
        });
        
        $app->run();
    }

    public function testRouteUrl()
    {
        $app = $this->createApp();
        
        $app->on(ServerRequestInterface::class, function() {
            return (new Psr17Factory())->createServerRequest(
                method: 'GET',
                uri: 'http://localhost/blog',
                serverParams: [
                    'SCRIPT_NAME' => '/index.php',
                ],
            );
        });

        $app->booting();
        
        $app->route('GET', 'blog/{id}/edit', function() {
            return '';
        })->name('blog.edit');
        
        $this->assertSame(
            'http://localhost/blog/5/edit',
            $app->routeUrl('blog.edit', ['id' => 5])->get()
        );
    }
    
    public function testRouteUrlResolvesBaseUri()
    {
        $app = $this->createApp();
        
        $app->on(ServerRequestInterface::class, function() {
            return (new Psr17Factory())->createServerRequest(
                method: 'GET',
                uri: 'http://localhost/foo/bar/blog',
                serverParams: [
                    'SCRIPT_NAME' => '/foo/bar/index.php',
                    //'REQUEST_URI' => '/foo/bar/',
                    //'SERVER_NAME' => 'tobento.localhost',
                    //'HTTP_HOST' => 'tobento.localhost'
                ],
            );
        });

        $app->booting();
        
        $app->route('GET', 'blog/{id}/edit', function() {
            return '';
        })->name('blog.edit');
        
        $this->assertSame(
            'http://localhost/foo/bar/blog/5/edit',
            $app->routeUrl('blog.edit', ['id' => 5])->get()
        );
    }
    
    public function testRouteNotFoundExceptionIsCatchedByErrorHandlerReturningJsonResponse()
    {
        $app = $this->createApp();
        
        $app->on(ServerRequestInterface::class, function() {
            return (new Psr17Factory())->createServerRequest(
                method: 'GET',
                uri: 'foo',
                serverParams: [],
            );
        });

        $app->booting();
        $app->run();
        
        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isContentType('application/json')
            ->isStatusCode(404)
            ->isBodySame('{"status":404,"message":"404 | Not Found"}');
    }
    
    public function testInvalidSignatureExceptionIsCatchedByErrorHandlerReturningJsonResponse()
    {
        $app = $this->createApp();
        
        $app->on(ServerRequestInterface::class, function() {
            return (new Psr17Factory())->createServerRequest(
                method: 'GET',
                uri: 'unsubscribe/user',
                serverParams: [],
            );
        });

        $app->booting();
        
        $app->route('GET', 'unsubscribe/{user}', function() {
            return 'unsubscribed';
        })->signed('unsubscribe');

        $app->run();
        
        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isContentType('application/json')
            ->isStatusCode(403)
            ->isBodySame('{"status":403,"message":"403 | Forbidden"}');
    }
    
    public function testRouteSignature()
    {
        // first request, create signed url.
        $app = $this->createApp(deleteDir: false);
        
        $app->on(ServerRequestInterface::class, function() {
            return (new Psr17Factory())->createServerRequest(
                method: 'GET',
                uri: 'foo',
                serverParams: [],
            );
        });
        
        $app->booting();
        
        $app->route('GET', 'unsubscribe/{user}', function() {
            return 'unsubscribed';
        })->signed('unsubscribe');
        
        $signedUrl = $app->routeUrl('unsubscribe', ['user' => 5])->sign()->get();
        
        $app->run();
        
        // second request.
        $app = $this->createApp(deleteDir: false);
        
        $app->on(ServerRequestInterface::class, function() use ($signedUrl) {
            return (new Psr17Factory())->createServerRequest(
                method: 'GET',
                uri: $signedUrl,
                serverParams: [],
            );
        });

        $app->booting();
        
        $app->route('GET', 'unsubscribe/{user}', function() {
            return 'unsubscribed';
        })->signed('unsubscribe');

        $app->run();
        
        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isStatusCode(200)
            ->isBodySame('unsubscribed');
    }
    
    public function testRoutesBootRouteFromApp()
    {
        $app = $this->createApp();
        
        $app->on(ServerRequestInterface::class, function() {
            return (new Psr17Factory())->createServerRequest(
                method: 'GET',
                uri: 'foo',
                serverParams: [],
            );
        });
        
        $app->boot(RoutesBoot::class);

        $app->run();

        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isStatusCode(200)
            ->isBodySame('foo');
    }
    
    public function testRoutesBootRouteFromRouter()
    {
        $app = $this->createApp();
        
        $app->on(ServerRequestInterface::class, function() {
            return (new Psr17Factory())->createServerRequest(
                method: 'GET',
                uri: 'bar',
                serverParams: [],
            );
        });
        
        $app->boot(RoutesBoot::class);

        $app->run();

        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isStatusCode(200)
            ->isBodySame('bar');
    }
    
    public function testRequestIsSameOnHandlerCall()
    {
        $app = $this->createApp();
        
        $app->on(ServerRequestInterface::class, function() {
            return (new Psr17Factory())->createServerRequest(
                method: 'GET',
                uri: 'foo',
                serverParams: [],
            );
        });

        $app->booting();
        
        $app->route('GET', 'foo', function(ServerRequestInterface $request) {
            return $request->getAttribute('name');
        })->middleware(function(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
            $request = $request->withAttribute('name', 'value');
            return $handler->handle($request);
        });

        $app->run();

        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isStatusCode(200)
            ->isBodySame('value');
    }
    
    public function testRequestIsSameOnHandlerConstruct()
    {
        $app = $this->createApp();
        
        $app->on(ServerRequestInterface::class, function() {
            return (new Psr17Factory())->createServerRequest(
                method: 'GET',
                uri: 'foo',
                serverParams: [],
            );
        });

        $app->booting();
        
        $app->route('GET', 'foo', ControllerRequestAttribute::class)
            ->middleware(function(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
                $request = $request->withAttribute('name', 'value');
                return $handler->handle($request);
            });

        $app->run();

        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isStatusCode(200)
            ->isBodySame('value');
    }
    
    public function testParamsRouteHandler()
    {
        $app = $this->createApp();
        
        $app->on(ServerRequestInterface::class, function() {
            return (new Psr17Factory())->createServerRequest(
                method: 'GET',
                uri: 'foo',
                serverParams: [],
            );
        });

        $app->on(RouteHandlerInterface::class, function(RouteHandlerInterface $handler) {
            $handler->addHandler(ParamsRouteHandler::class);
        })->priority(1500);
        
        $app->booting();
        
        $app->route('GET', 'foo', function(ServerRequestInterface $request) {
            return [
                $request->getAttribute('_arguments'),
                $request->getAttribute('_declared'),
            ];
        });

        $app->run();

        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isStatusCode(200)
            ->isBodySame('[true,true]');
    }
    
    public function testRouteHandlerReturnsResult()
    {
        $app = $this->createApp();
        
        $app->on(ServerRequestInterface::class, function() {
            return (new Psr17Factory())->createServerRequest(
                method: 'GET',
                uri: 'foo',
                serverParams: [],
            );
        });

        $app->on(RouteHandlerInterface::class, function(RouteHandlerInterface $handler) {
            $handler->addHandler(ReturnsResultRouteHandler::class);
        });
        
        $app->booting();
        
        // int $request for test request handler not injecting request object
        $app->route('GET', 'foo', function(int $request) {
            return 'foo';
        });

        $app->run();

        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isStatusCode(200)
            ->isBodySame('result');
    }    
}