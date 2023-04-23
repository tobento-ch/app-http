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
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Uri\PreviousUriInterface;
use Tobento\Service\Filesystem\Dir;
use Tobento\Service\Dir\Dir as ServiceDir;
use Tobento\Service\Dir\Dirs;
use Tobento\Service\View;
use Psr\Http\Message\ServerRequestInterface;
use Nyholm\Psr7\Factory\Psr17Factory;

/**
 * AppTest
 */
class AppTest extends TestCase
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
        
        // Replaces session middleware to ignore session start and save exceptions.
        $app->on(Middleware::class, MiddlewareBoot::class);
        
        $app->boot(\Tobento\App\Http\Boot\Middleware::class);
        $app->boot(\Tobento\App\Http\Boot\Routing::class);        
        $app->boot(\Tobento\App\Http\Boot\Session::class);
        $app->boot(\Tobento\App\Http\Boot\RequesterResponser::class);
        
        return $app;
    }
    
    protected function createView(): View\ViewInterface
    {
        return new View\View(
            new View\PhpRenderer(
                new Dirs(
                    new ServiceDir(realpath(__DIR__.'/../view/')),
                )
            ),
            new View\Data(),
            new View\Assets(__DIR__.'/../public/src/', 'https://www.example.com/src/')
        );
    }    
    
    public static function tearDownAfterClass(): void
    {
        (new Dir())->delete(__DIR__.'/../app/');
    }

    public function testResponserRenderView()
    {
        $app = $this->createApp();
        
        $app->on(ServerRequestInterface::class, function() {
            return (new Psr17Factory())->createServerRequest(
                method: 'GET',
                uri: 'foo',
                serverParams: [],
            );
        });
        
        $app->set(View\ViewInterface::class, $this->createView());
                  
        $app->booting();
        
        $app->route('GET', 'foo', function(ResponserInterface $responser) {
            return $responser->render(
                view: 'about',
                data: ['title' => 'About us']
            );
        });

        $app->run();

        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isStatusCode(200)
            ->isBodySame('<!DOCTYPE html><html><head><title>About us</title></head><body>About</body></html>');
    }
    
    public function testResponserFlashInputData()
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
        
        $app->route('GET', 'foo', function(ResponserInterface $responser) {
            return $responser
                ->withInput(['key' => 'value'])
                ->redirect('bar');
        });

        $app->run();

        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isStatusCode(302);
        
        // next request, flash input data available
        $app = $this->createApp();
        
        $app->on(ServerRequestInterface::class, function() {
            return (new Psr17Factory())->createServerRequest(
                method: 'GET',
                uri: 'bar',
                serverParams: [],
            );
        });
                
        $app->booting();
        
        $app->route('GET', 'bar', function(RequesterInterface $requester, ResponserInterface $responser) {
            return $responser->json(
                data: $requester->input(),
                code: 200,
            );
        });

        $app->run();

        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isStatusCode(200)
            ->isBodySame('{"key":"value"}');
        
        // next request, no flashed input data.
        $app = $this->createApp();
        
        $app->on(ServerRequestInterface::class, function() {
            return (new Psr17Factory())->createServerRequest(
                method: 'GET',
                uri: 'bar',
                serverParams: [],
            );
        });
                
        $app->booting();
        
        $app->route('GET', 'bar', function(RequesterInterface $requester, ResponserInterface $responser) {
            return $responser->json(
                data: $requester->input(),
                code: 200,
            );
        });

        $app->run();

        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isStatusCode(200)
            ->isBodySame('[]');
    }
    
    public function testRedirectionToPreviousUri()
    {
        $app = $this->createApp();
        
        $app->on(ServerRequestInterface::class, function() {
            return (new Psr17Factory())->createServerRequest(
                method: 'GET',
                uri: 'http://localhost/foo',
                serverParams: [
                    'SCRIPT_NAME' => '/index.php',
                ],
            );
        });
                
        $app->booting();
        
        $app->route('GET', 'foo', function(ResponserInterface $responser) {
            return $responser->json(data: [], code: 200);
        });

        $app->run();

        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isStatusCode(200);
        
        // next request: redirect
        $app = $this->createApp();
        
        $app->on(ServerRequestInterface::class, function() {
            return (new Psr17Factory())->createServerRequest(
                method: 'GET',
                uri: 'http://localhost/bar',
                serverParams: [
                    'SCRIPT_NAME' => '/index.php',
                ],
            );
        });
                
        $app->booting();
        
        $app->route('GET', 'bar', function(PreviousUriInterface $uri) {
            // would be redirect
            return (string)$uri;
        });

        $app->run();

        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isBodySame('http://localhost/foo');
    }
}