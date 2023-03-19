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
use Tobento\Service\Filesystem\Dir;
use Tobento\Service\View;
use Tobento\Service\Dir\Dir as ServiceDir;
use Tobento\Service\Dir\Dirs;
use Psr\Http\Message\ServerRequestInterface;
use Nyholm\Psr7\Factory\Psr17Factory;

/**
 * ErrorHandlerViewsTest
 */
class ErrorHandlerViewsTest extends TestCase
{
    protected function createApp(
        array $request = [],
        null|string $accept = null,
        bool $specificViewDir = false,
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
            
            if (is_null($accept)) {
                $accept = 'text/html';
            }
            
            return $serverRequest->withAddedHeader('Accept', $accept);
        });
        
        if ($specificViewDir) {
            $viewDir = new ServiceDir(realpath(__DIR__.'/../view-specific/'));
        } else {
            $viewDir = new ServiceDir(realpath(__DIR__.'/../view/'));
        }
        
        $app->set(View\ViewInterface::class, function () use ($viewDir) {
            return new View\View(
                new View\PhpRenderer(new Dirs($viewDir)),
                new View\Data(),
                new View\Assets(__DIR__.'/../public/src/', 'https://www.example.com/src/')
            );
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
    
    public function testUsesGeneralViewIfNoSpecificExists()
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
            ->isContentType('text/html; charset=utf-8')
            ->isBodySame('<!DOCTYPE html><html><head><title>500</title></head><body>500 | Internal Server Error</body></html>');
    }
    
    public function testUsesSpecificViewIfExists()
    {
        $app = $this->createApp(request: [
            'method' => 'GET',
            'uri' => '',
            'serverParams' => [],
        ], specificViewDir: true);
        
        $app->middleware(function($request, $handler) {
            throw \Exception();
        });
                
        $app->run();

        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isStatusCode(500)
            ->isContentType('text/html; charset=utf-8')
            ->isBodySame('<!DOCTYPE html><html><head><title>500</title></head><body>Specific:500 | Internal Server Error</body></html>');
    }
    
    public function testUsesGeneralViewSpecificExtensionIfExists()
    {
        $app = $this->createApp(request: [
            'method' => 'GET',
            'uri' => '',
            'serverParams' => [],
        ], accept: 'application/xml');
        
        $app->middleware(function($request, $handler) {
            throw \Exception();
        });
                
        $app->run();

        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isStatusCode(500)
            ->isContentType('application/xml; charset=utf-8')
            ->isBodySame('<error><code>500</code><message>500 | Internal Server Error</message></error>');
    }
    
    public function testUsesSpecificViewAndSpecificExtensionIfExists()
    {
        $app = $this->createApp(request: [
            'method' => 'GET',
            'uri' => '',
            'serverParams' => [],
        ], accept: 'application/xml', specificViewDir: true);
        
        $app->middleware(function($request, $handler) {
            throw \Exception();
        });
                
        $app->run();

        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isStatusCode(500)
            ->isContentType('application/xml; charset=utf-8')
            ->isBodySame('<error><code>500</code><message>Specific:500 | Internal Server Error</message></error>');
    }    
}