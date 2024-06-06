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
use Tobento\App\Http\ResponseEmitterInterface;
use Tobento\App\Http\Test\TestResponse;
use Tobento\App\Http\Test\Mock\ResponseEmitter;
use Tobento\Service\Middleware\MiddlewareDispatcherInterface;
use Tobento\Service\Middleware\MiddlewareFactoryInterface;
use Tobento\Service\Filesystem\Dir;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * MiddlewareTest
 */
class MiddlewareTest extends TestCase
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
        
        $app->boot(\Tobento\App\Http\Boot\Middleware::class);
        
        return $app;
    }
    
    public static function tearDownAfterClass(): void
    {
        (new Dir())->delete(__DIR__.'/../app/');
    }
    
    public function testMiddlewareInterfacesAreAvailable()
    {
        $app = $this->createApp();
        $app->booting();
        
        $this->assertInstanceof(
            MiddlewareDispatcherInterface::class,
            $app->get(MiddlewareDispatcherInterface::class)
        );
    }
    
    public function testMiddlewareMacrosAreAvailable()
    {
        $app = $this->createApp();
        $app->booting();
        
        // add middleware aliases using app macro:
        $app->middlewareAliases([
            'alias' => \FooMiddleware::class,
        ]);

        // add middleware group using app macro:
        $app->middlewareGroup(name: 'api', middlewares: [
            \SomeMiddleware::class,
        ]);

        // add middleware using app macro:
        $app->middleware(\BarMiddleware::class);
        
        $dispatcher = $app->get(MiddlewareDispatcherInterface::class);
        
        $this->assertSame(['alias' => 'FooMiddleware'], $dispatcher->getAliases());
        $this->assertSame(['api' => ['SomeMiddleware']], $dispatcher->getGroups());
    }
    
    public function testMiddlewareGroupAndAliasesAreSetViaConfig()
    {
        $app = $this->createApp();
        
        $app->dirs()
            ->dir(realpath(__DIR__.'/../config/'), 'config-dev', group: 'config', priority: 20);
        
        $app->booting();
        
        $dispatcher = $app->get(MiddlewareDispatcherInterface::class);
        
        $this->assertSame(['foo' => 'FooMiddlewareFromAliases'], $dispatcher->getAliases());
        $this->assertSame(['name' => ['MiddlewareFromGroup']], $dispatcher->getGroups());
    }
    
    public function testMiddlewaresAreAddedViaConfig()
    {
        $app = $this->createApp();
        
        $app->dirs()
            ->dir(realpath(__DIR__.'/../config/'), 'config-dev', group: 'config', priority: 20);
        
        $app->on(
            MiddlewareDispatcherInterface::class,
            function (MiddlewareDispatcherInterface $dispatcher): MiddlewareDispatcherInterface {
                return $this->createDispatcher($dispatcher);
            }
        );
        
        $app->booting();
        
        $dispatcher = $app->get(MiddlewareDispatcherInterface::class);
        
        $this->assertSame(
            [
                [
                    0 => 'Tobento\App\Http\Middleware\SecurePolicyHeaders',
                    'priority' => 8000,
                ],
            ],
            $dispatcher->getAddedMiddlewares()
        );
    }
    
    public function testMiddlewaresReplacedConfigAreAdded()
    {
        $app = $this->createApp();
        
        $app->dirs()
            ->dir(realpath(__DIR__.'/../config/'), 'config-dev', group: 'config', priority: 20);
        
        $app->booting();
        
        $this->assertSame(
            [
                'ToReplaceMiddleware' => 'ReplacedMiddleware',
                'ToReplaceNullMiddleware' => null,
            ],
            $app->get(MiddlewareFactoryInterface::class)->getReplaceMiddlewares()
        );
    }
    
    protected function createDispatcher(MiddlewareDispatcherInterface $dispatcher): MiddlewareDispatcherInterface
    {
        return new class($dispatcher) implements MiddlewareDispatcherInterface
        {
            protected array $middleware = [];
            
            public function __construct(
                private MiddlewareDispatcherInterface $dispatcher,
            ) {}
            
            public function getAddedMiddlewares(): array
            {
                return $this->middleware;
            }
            
            public function new(): static
            {
                return clone $this;
            }

            public function add(mixed ...$middleware): static
            {
                $this->middleware[] = $middleware;
                
                $this->dispatcher->add(...$middleware);
                return $this;
            }

            public function addAlias(string $alias, string $middleware): static
            {
                $this->dispatcher->addAlias($alias, $middleware);
                return $this;
            }

            public function addAliases(array $aliases): static
            {
                $this->dispatcher->addAliases($aliases);
                return $this;
            }

            public function setAliases(array $aliases): static
            {
                $this->dispatcher->setAliases($aliases);
                return $this;
            }

            public function getAliases(): array
            {
                return $this->dispatcher->getAliases();
            }

            public function addGroup(string $name, array $middlewares): static
            {
                $this->dispatcher->addGroup($name, $middlewares);
                return $this;
            }

            public function getGroups(): array
            {
                return $this->dispatcher->getGroups();
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->dispatcher->handle($request);
            }
        };
    }
}