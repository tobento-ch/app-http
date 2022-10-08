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
use Tobento\App\Http\Test\TestResponse;
use Tobento\App\Http\Test\Mock\ResponseEmitter;
use Tobento\App\AppFactory;
use Tobento\Service\Filesystem\Dir;
use Psr\Http\Message\ServerRequestInterface;
use Nyholm\Psr7\Factory\Psr17Factory;

/**
 * RoutingWithMiddlewareTest
 */
class RoutingWithMiddlewareTest extends TestCase
{
    protected function createApp(bool $deleteDir = true): AppInterface
    {
        if ($deleteDir) {
            (new Dir())->delete(__DIR__.'/../app/');
        }
        
        $app = (new AppFactory())->createApp();
        
        $app->dirs()
            ->dir(__DIR__.'/../app/', 'app')
            ->dir($app->dir('app').'config', 'config', group: 'config');
        
        // Replace response emitter for testing:
        $app->on(ResponseEmitterInterface::class, ResponseEmitter::class);
        
        $app->boot(\Tobento\App\Http\Boot\Middleware::class);
        $app->boot(\Tobento\App\Http\Boot\Routing::class);
        
        return $app;
    }
    
    public static function tearDownAfterClass(): void
    {
        (new Dir())->delete(__DIR__.'/../app/');
    }    
    
    public function testMethodOverwriteMiddleware()
    {
        $app = $this->createApp();
        
        $app->on(ServerRequestInterface::class, function() {
            return (new Psr17Factory())->createServerRequest(
                method: 'POST',
                uri: 'foo',
                serverParams: [],
            )->withParsedBody(['_method' => 'PUT']);
        });

        $app->booting();
        
        $app->route('PUT', 'foo', function() {
            return 'foo';
        });

        $app->run();

        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isStatusCode(200)
            ->isBodySame('foo');
    }
    
    public function testMethodOverwriteMiddlewareWithHeaderMethodName()
    {
        $app = $this->createApp();
        
        $app->on(ServerRequestInterface::class, function() {
            return (new Psr17Factory())->createServerRequest(
                method: 'POST',
                uri: 'foo',
                serverParams: [],
            )->withHeader('X-Http-Method-Override', 'PUT');
        });

        $app->booting();
        
        $app->route('PUT', 'foo', function() {
            return 'foo';
        });

        $app->run();

        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isStatusCode(200)
            ->isBodySame('foo');
    }    
}