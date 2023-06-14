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
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Routing\DomainsInterface;
use Psr\Http\Message\ServerRequestInterface;
use Nyholm\Psr7\Factory\Psr17Factory;

/**
 * RoutingWithDomainsTest
 */
class RoutingWithDomainsTest extends TestCase
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
            ->dir(realpath(__DIR__.'/../config/'), 'config-dev', group: 'config', priority: 20)
            ->dir($app->dir('app').'config', 'config', group: 'config', priority: 10);
        
        // Replace response emitter for testing:
        $app->on(ResponseEmitterInterface::class, ResponseEmitter::class);
        
        $app->boot(\Tobento\App\Http\Boot\Routing::class);
        
        return $app;
    }
    
    public static function tearDownAfterClass(): void
    {
        (new Dir())->delete(__DIR__.'/../app/');
    }
    
    public function testRoutingWithDomains()
    {
        $app = $this->createApp();
    
        $app->on(ServerRequestInterface::class, function() use ($app) {
            return (new Psr17Factory())->createServerRequest(
                method: 'GET',
                uri: 'http://ch.localhost/foo',
                serverParams: [],
            );
        });
        
        $app->booting();
        
        $this->assertSame(['ch.localhost', 'de.localhost'], $app->get(DomainsInterface::class)->domains());
        $this->assertTrue(in_array('ch.localhost', $app->get(Http::class)->getValidHosts()));
        
        $app->route('GET', 'foo', function() {
            return 'foo';
        })->domain('ch.localhost');

        $app->run();

        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isStatusCode(200)
            ->isBodySame('foo');
    }
}