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
use Tobento\App\Http\Test\Mock\FrontendBoot;
use Tobento\App\AppFactory;
use Tobento\Service\Filesystem\Dir;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Config\ConfigInterface;
use Psr\Http\Message\ServerRequestInterface;
use Nyholm\Psr7\Factory\Psr17Factory;

/**
 * AreaBootFrontendTest
 */
class AreaBootFrontendTest extends TestCase
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

    public function testAreaMethods()
    {
        $app = $this->createApp();
        
        $app->boot(FrontendBoot::class);
        
        $app->booting();
        
        $frontend = $app->get(FrontendBoot::class);
        
        $this->assertSame('frontend', $frontend->areaKey());
        $this->assertSame('Frontend', $frontend->areaName());
        $this->assertSame('', $frontend->areaSlug());
        $this->assertSame(null, $frontend->areaDomain());
    }
    
    public function testAreaAppIsNotSameAsRootApp()
    {
        $app = $this->createApp();
        
        $app->boot(FrontendBoot::class);
        
        $app->booting();
        
        $frontend = $app->get(FrontendBoot::class);
        $frontend->booting();
        
        $this->assertTrue($frontend->app() !== $frontend->rootApp());
        $this->assertTrue($frontend->app() !== $app);
    }

    public function testRoutesAreaWithSlug()
    {
        $app = $this->createApp();
        
        $frontend = new FrontendBoot($app);
        $frontend->afterBooting(function($frontend) {
            $frontend->app()->on(ResponseEmitterInterface::class, ResponseEmitter::class);
        });
        
        $app->boot($frontend);
        
        $app->on(ServerRequestInterface::class, function() {
            return (new Psr17Factory())->createServerRequest(
                method: 'GET',
                uri: '',
                serverParams: [],
            );
        });
        
        $app->run();
                
        $this->assertSame(
            'frontend',
            $app->get(RouterInterface::class)->getMatchedRoute()?->getName()
        );
    }
    
    public function testRoutesAreaWithDomain()
    {
        $app = $this->createApp();
        
        $frontend = new FrontendBoot($app);
        $frontend->afterBooting(function($frontend) {
            $frontend->app()->on(ResponseEmitterInterface::class, ResponseEmitter::class);
            
            $frontend->app()->on(ServerRequestInterface::class, function() {
                return (new Psr17Factory())->createServerRequest(
                    method: 'GET',
                    uri: 'https://frontend.example.com/foo',
                    serverParams: [],
                );
            });

            $frontend->booting();
            
            $frontend->app()->route('GET', 'foo', function() {
                return 'foo';
            })->name('foo');
        });
        
        $app->boot($frontend);
        
        $app->on(ServerRequestInterface::class, function() {
            $request = (new Psr17Factory())->createServerRequest(
                method: 'GET',
                uri: 'https://frontend.example.com/foo',
                serverParams: [],
            );
            
            return $request;
        });
                
        $app->on(ConfigInterface::class, function($config) {
            $config->set('frontend.domain', 'frontend.example.com');
        });

        $app->run();
        
        $this->assertSame('https://frontend.example.com', (string)$frontend->url());
        
        $this->assertSame(
            'frontend',
            $app->get(RouterInterface::class)->getMatchedRoute()?->getName()
        );
        
        $this->assertSame(
            'foo',
            $frontend->app()->get(RouterInterface::class)->getMatchedRoute()?->getName()
        );
    }    
    
    public function testHomeRoute()
    {
        $app = $this->createApp();
        
        $frontend = new FrontendBoot($app);
        $frontend->afterBooting(function($frontend) {
            $frontend->app()->on(ResponseEmitterInterface::class, ResponseEmitter::class);
            
            $frontend->app()->on(ServerRequestInterface::class, function() {
                return (new Psr17Factory())->createServerRequest(
                    method: 'GET',
                    uri: '',
                    serverParams: [],
                );
            });

            $frontend->booting();
            
            $frontend->app()->route('GET', '', function() {
                return 'home';
            });
        });
        
        $app->boot($frontend);
        
        $app->on(ServerRequestInterface::class, function() {
            return (new Psr17Factory())->createServerRequest(
                method: 'GET',
                uri: '',
                serverParams: [],
            );
        });
        
        $app->run();

        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isStatusCode(200)
            ->isBodySame('home');
    }
    
    public function testFooRoute()
    {
        $app = $this->createApp();

        $frontend = new FrontendBoot($app);
        $frontend->afterBooting(function($frontend) {
            $frontend->app()->on(ResponseEmitterInterface::class, ResponseEmitter::class);
            
            $frontend->app()->on(ServerRequestInterface::class, function() {
                return (new Psr17Factory())->createServerRequest(
                    method: 'GET',
                    uri: 'foo',
                    serverParams: [],
                );
            });

            $frontend->booting();
            
            $frontend->app()->route('GET', 'foo', function() {
                return 'foo';
            });
        });
        
        $app->boot($frontend);
        
        $app->on(ServerRequestInterface::class, function() {
            return (new Psr17Factory())->createServerRequest(
                method: 'GET',
                uri: 'foo',
                serverParams: [],
            );
        });
        
        $app->run();

        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isStatusCode(200)
            ->isBodySame('foo');
    }
    
    public function testRouteNotFound()
    {
        $app = $this->createApp();
        
        $frontend = new FrontendBoot($app);
        $frontend->afterBooting(function($frontend) {
            $frontend->app()->on(ResponseEmitterInterface::class, ResponseEmitter::class);
            
            $frontend->app()->on(ServerRequestInterface::class, function() {
                return (new Psr17Factory())->createServerRequest(
                    method: 'GET',
                    uri: 'foo',
                    serverParams: [],
                );
            });
        });
        
        $app->boot($frontend);
        
        $app->on(ServerRequestInterface::class, function() {
            return (new Psr17Factory())->createServerRequest(
                method: 'GET',
                uri: 'foo',
                serverParams: [],
            );
        });
        
        $app->run();

        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isStatusCode(404);
    }
}