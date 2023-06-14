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
use Tobento\App\Http\Test\Mock\BackendBoot;
use Tobento\App\AppFactory;
use Tobento\Service\Filesystem\Dir;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Config\ConfigInterface;
use Psr\Http\Message\ServerRequestInterface;
use Nyholm\Psr7\Factory\Psr17Factory;

/**
 * AreaBootTest
 */
class AreaBootTest extends TestCase
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
        
        $app->boot(BackendBoot::class);
        
        $app->booting();
        
        $backend = $app->get(BackendBoot::class);
        
        $this->assertSame('backend', $backend->areaKey());
        $this->assertSame('Backend', $backend->areaName());
        $this->assertSame('private', $backend->areaSlug());
        $this->assertSame(null, $backend->areaDomain());
    }
    
    public function testAreaAppIsNotSameAsRootApp()
    {
        $app = $this->createApp();
        
        $app->boot(BackendBoot::class);
        
        $app->booting();
        
        $backend = $app->get(BackendBoot::class);
        $backend->booting();
        
        $this->assertTrue($backend->app() !== $backend->rootApp());
        $this->assertTrue($backend->app() !== $app);
    }

    public function testRoutesAreaWithSlug()
    {
        $app = $this->createApp();
        
        $backend = new BackendBoot($app);
        $backend->afterBooting(function($backend) {
            $backend->app()->on(ResponseEmitterInterface::class, ResponseEmitter::class);
        });
        
        $app->boot($backend);
        
        $app->on(ServerRequestInterface::class, function() {
            return (new Psr17Factory())->createServerRequest(
                method: 'GET',
                uri: 'private',
                serverParams: [],
            );
        });
        
        $app->run();
                
        $this->assertSame(
            'backend',
            $app->get(RouterInterface::class)->getMatchedRoute()?->getName()
        );
    }
    
    public function testRoutesAreaWithDomain()
    {
        $app = $this->createApp();
        
        $backend = new BackendBoot($app);
        $backend->afterBooting(function($backend) {
            $backend->app()->on(ResponseEmitterInterface::class, ResponseEmitter::class);
            
            $backend->app()->on(ServerRequestInterface::class, function() {
                return (new Psr17Factory())->createServerRequest(
                    method: 'GET',
                    uri: 'https://backend.example.com/foo',
                    serverParams: [],
                );
            });

            $backend->booting();
            
            $backend->app()->route('GET', 'foo', function() {
                return 'foo';
            })->name('foo');
        });
        
        $app->boot($backend);
        
        $app->on(ServerRequestInterface::class, function() {
            $request = (new Psr17Factory())->createServerRequest(
                method: 'GET',
                uri: 'https://backend.example.com/foo',
                serverParams: [],
            );
            
            return $request;
        });
                
        $app->on(ConfigInterface::class, function($config) {
            $config->set('backend.domain', 'backend.example.com');
            $config->set('backend.slug', null);
        });

        $app->run();
        
        $this->assertSame('https://backend.example.com', (string)$backend->url());
        
        $this->assertSame(
            'backend',
            $app->get(RouterInterface::class)->getMatchedRoute()?->getName()
        );
        
        $this->assertSame(
            'foo',
            $backend->app()->get(RouterInterface::class)->getMatchedRoute()?->getName()
        );
    }
    
    public function testRoutesAreaWithDomainAndSlug()
    {
        $app = $this->createApp();
        
        $backend = new BackendBoot($app);
        $backend->afterBooting(function($backend) {
            $backend->app()->on(ResponseEmitterInterface::class, ResponseEmitter::class);
            
            $backend->app()->on(ServerRequestInterface::class, function() {
                return (new Psr17Factory())->createServerRequest(
                    method: 'GET',
                    uri: 'https://backend.example.com/foo',
                    serverParams: [],
                );
            });

            $backend->booting();
            
            $backend->app()->route('GET', 'foo', function() {
                return 'foo';
            })->name('foo');
        });
        
        $app->boot($backend);
        
        $app->on(ServerRequestInterface::class, function() {
            $request = (new Psr17Factory())->createServerRequest(
                method: 'GET',
                uri: 'https://backend.example.com/private/foo',
                serverParams: [],
            );
            
            return $request;
        });
                
        $app->on(ConfigInterface::class, function($config) {
            $config->set('backend.domain', 'backend.example.com');
        });

        $app->run();
        
        $this->assertSame('https://backend.example.com/private', (string)$backend->url());
        
        $this->assertSame(
            'backend',
            $app->get(RouterInterface::class)->getMatchedRoute()?->getName()
        );
        
        $this->assertSame(
            'foo',
            $backend->app()->get(RouterInterface::class)->getMatchedRoute()?->getName()
        );
    }
    
    public function testHomeRoute()
    {
        $app = $this->createApp();
        
        $backend = new BackendBoot($app);
        $backend->afterBooting(function($backend) {
            $backend->app()->on(ResponseEmitterInterface::class, ResponseEmitter::class);
            
            $backend->app()->on(ServerRequestInterface::class, function() {
                return (new Psr17Factory())->createServerRequest(
                    method: 'GET',
                    uri: 'private/',
                    serverParams: [],
                );
            });

            $backend->booting();
            
            $backend->app()->route('GET', '', function() {
                return 'home';
            });
        });
        
        $app->boot($backend);
        
        $app->on(ServerRequestInterface::class, function() {
            return (new Psr17Factory())->createServerRequest(
                method: 'GET',
                uri: 'private/',
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

        $backend = new BackendBoot($app);
        $backend->afterBooting(function($backend) {
            $backend->app()->on(ResponseEmitterInterface::class, ResponseEmitter::class);
            
            $backend->app()->on(ServerRequestInterface::class, function() {
                return (new Psr17Factory())->createServerRequest(
                    method: 'GET',
                    uri: 'private/foo',
                    serverParams: [],
                );
            });

            $backend->booting();
            
            $backend->app()->route('GET', 'foo', function() {
                return 'foo';
            });
        });
        
        $app->boot($backend);
        
        $app->on(ServerRequestInterface::class, function() {
            return (new Psr17Factory())->createServerRequest(
                method: 'GET',
                uri: 'private/foo',
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
        
        $backend = new BackendBoot($app);
        $backend->afterBooting(function($backend) {
            $backend->app()->on(ResponseEmitterInterface::class, ResponseEmitter::class);
            
            $backend->app()->on(ServerRequestInterface::class, function() {
                return (new Psr17Factory())->createServerRequest(
                    method: 'GET',
                    uri: 'private/foo',
                    serverParams: [],
                );
            });
        });
        
        $app->boot($backend);
        
        $app->on(ServerRequestInterface::class, function() {
            return (new Psr17Factory())->createServerRequest(
                method: 'GET',
                uri: 'private/foo',
                serverParams: [],
            );
        });
        
        $app->run();

        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isStatusCode(404);
    }
}