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
use Tobento\App\Http\Boot\Http;
use Tobento\App\Http\Boot\Middleware;
use Tobento\App\Http\ResponseEmitterInterface;
use Tobento\App\Http\Test\Mock\ResponseEmitter;
use Tobento\App\Http\Test\Mock\SessionMiddleware;
use Tobento\App\Http\Test\Mock\MiddlewareBoot;
use Tobento\App\Http\Test\TestResponse;
use Tobento\Service\Session\SessionInterface;
use Tobento\Service\Session\SessionStartException;
use Tobento\Service\Session\SessionSaveException;
use Tobento\Service\Filesystem\Dir;
use Psr\Http\Message\ServerRequestInterface;
use Nyholm\Psr7\Factory\Psr17Factory;

/**
 * SessionTest
 */
class SessionTest extends TestCase
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
        
        return $app;
    }
    
    public static function tearDownAfterClass(): void
    {
        (new Dir())->delete(__DIR__.'/../app/');
    }
    
    public function testInterface()
    {
        $app = $this->createApp();
        $app->boot(\Tobento\App\Http\Boot\Session::class);
        $app->booting();
        
        $this->assertInstanceof(
            SessionInterface::class,
            $app->get(SessionInterface::class)
        );
    }
    
    public function testWithRoutingGetSession()
    {
        $app = $this->createApp();
        $app->boot(\Tobento\App\Http\Boot\Middleware::class);
        $app->boot(\Tobento\App\Http\Boot\Routing::class);        
        $app->boot(\Tobento\App\Http\Boot\Session::class);
        
        $app->on(ServerRequestInterface::class, function() {
            return (new Psr17Factory())->createServerRequest(
                method: 'GET',
                uri: 'foo',
                serverParams: [],
            );
        });
        
        // Replaces session middleware to ignore session start and save exceptions.
        $app->on(Middleware::class, MiddlewareBoot::class);
                
        $app->booting();
        
        $app->route('GET', 'foo', function(SessionInterface $session) {
            $session->set('key', 'value');
            return 'foo';
        });

        $app->run();

        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isStatusCode(200)
            ->isBodySame('foo');
    }
    
    public function testWithRoutingGetSessionFromRequestAttribute()
    {
        $app = $this->createApp();
        $app->boot(\Tobento\App\Http\Boot\Middleware::class);
        $app->boot(\Tobento\App\Http\Boot\Routing::class);        
        $app->boot(\Tobento\App\Http\Boot\Session::class);
        
        $app->on(ServerRequestInterface::class, function() {
            return (new Psr17Factory())->createServerRequest(
                method: 'GET',
                uri: 'foo',
                serverParams: [],
            );
        });
        
        // Replaces session middleware to ignore session start and save exceptions.
        $app->on(Middleware::class, MiddlewareBoot::class);
                
        $app->booting();
        
        $app->route('GET', 'foo', function(ServerRequestInterface $request) {
            $session = $request->getAttribute(SessionInterface::class);
            $session->set('key', 'value');
            return 'foo';
        });

        $app->run();

        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isStatusCode(200)
            ->isBodySame('foo');
    }
}