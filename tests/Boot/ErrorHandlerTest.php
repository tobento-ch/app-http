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
use Tobento\App\AppFactory;
use Tobento\App\Http\Boot\ErrorHandler;
use Tobento\App\Http\Boot\Http;
use Tobento\App\Http\Boot\Routing;
use Tobento\App\Http\ResponseEmitterInterface;
use Tobento\App\Http\Test\TestResponse;
use Tobento\App\Http\Test\Mock\ResponseEmitter;
use Tobento\Service\Session\RemoteAddrValidation;
use Tobento\Service\Session\SessionValidationException;
use Tobento\Service\Filesystem\Dir;
use Psr\Http\Message\ServerRequestInterface;
use Nyholm\Psr7\Factory\Psr17Factory;

/**
 * ErrorHandlerTest
 */
class ErrorHandlerTest extends TestCase
{
    protected function createApp(
        array $request = [],
        null|string $accept = null,
        bool $deleteDir = true,
    ): AppInterface {
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
        
        $app->on(ServerRequestInterface::class, function() use ($request, $accept) {            
            $serverRequest = (new Psr17Factory())->createServerRequest(...$request);
            
            if ($accept) {
                return $serverRequest->withAddedHeader('Accept', $accept);
            }
            
            return $serverRequest;
        });
        
        $app->boot(ErrorHandler::class);
        $app->boot(Routing::class);
        $app->booting();

        $app->route('GET', '', function() {
            return 'home';
        })->name('home');
        
        $app->route('GET', 'unsubscribe/{user}', function() {
            return 'unsubscribed';
        })->signed('unsubscribe');
        
        return $app;
    }
    
    public static function tearDownAfterClass(): void
    {
        (new Dir())->delete(__DIR__.'/../app/');
    }
    
    public function testHandlesRouteNotFoundException()
    {
        $app = $this->createApp(request: [
            'method' => 'GET',
            'uri' => 'bar',
            'serverParams' => [],
        ]);

        $app->run();
        
        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isStatusCode(404)
            ->isContentType('text/plain; charset=utf-8')
            ->isBodySame('404 | Not Found');
    }
    
    public function testHandlesRouteNotFoundExceptionJsonResponse()
    {
        $app = $this->createApp(request: [
            'method' => 'GET',
            'uri' => 'bar',
            'serverParams' => [],
        ], accept: 'application/json');
        
        $app->run();
                
        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isStatusCode(404)
            ->isContentType('application/json')
            ->isBodySame('{"status":404,"message":"404 | Not Found"}');
    }    
    
    public function testHandlesInvalidSignatureException()
    {
        $app = $this->createApp(request: [
            'method' => 'GET',
            'uri' => 'unsubscribe/user',
            'serverParams' => [],
        ]);

        $app->run();

        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isStatusCode(403)
            ->isContentType('text/plain; charset=utf-8')
            ->isBodySame('403 | Forbidden');
    }
    
    public function testHandlesInvalidSignatureExceptionJsonResponse()
    {
        $app = $this->createApp(request: [
            'method' => 'GET',
            'uri' => 'unsubscribe/user',
            'serverParams' => [],
        ], accept: 'application/json');

        $app->run();

        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isStatusCode(403)
            ->isContentType('application/json')
            ->isBodySame('{"status":403,"message":"403 | Forbidden"}');
    }
    
    public function testHandlesSessionValidationException()
    {
        $app = $this->createApp(request: [
            'method' => 'GET',
            'uri' => '',
            'serverParams' => [],
        ]);
        
        $app->middleware(function($request, $handler) {
            throw new SessionValidationException(
                new RemoteAddrValidation(null)
            );
        });
                
        $app->run();

        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isStatusCode(403)
            ->isContentType('text/plain; charset=utf-8')
            ->isBodySame('419 | Resource Expired');
    }
    
    public function testHandlesSessionValidationExceptionJsonResponse()
    {
        $app = $this->createApp(request: [
            'method' => 'GET',
            'uri' => '',
            'serverParams' => [],
        ], accept: 'application/json');
        
        $app->middleware(function($request, $handler) {
            throw new SessionValidationException(
                new RemoteAddrValidation(null)
            );
        });
                
        $app->run();

        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isStatusCode(403)
            ->isContentType('application/json')
            ->isBodySame('{"status":403,"message":"419 | Resource Expired"}');
    }

    public function testAnyException()
    {
        $app = $this->createApp(request: [
            'method' => 'GET',
            'uri' => '',
            'serverParams' => [],
        ]);
        
        $app->middleware(function($request, $handler) {
            throw \Exception();
        });
                
        $app->run();

        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isStatusCode(500)
            ->isContentType('text/plain; charset=utf-8')
            ->isBodySame('500 | Internal Server Error');
    }
    
    public function testAnyExceptionJsonResponse()
    {
        $app = $this->createApp(request: [
            'method' => 'GET',
            'uri' => '',
            'serverParams' => [],
        ], accept: 'application/json');
        
        $app->middleware(function($request, $handler) {
            throw \Exception();
        });
                
        $app->run();

        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isStatusCode(500)
            ->isContentType('application/json')
            ->isBodySame('{"status":500,"message":"500 | Internal Server Error"}');
    }
}