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
use Tobento\App\Http\Test\Mock\ResponseEmitter;
use Tobento\App\Http\Test\TestResponse;
use Tobento\Service\Filesystem\Dir;
use Tobento\Service\Cookie\CookieValuesInterface;
use Tobento\Service\Cookie\CookiesInterface;
use Tobento\Service\Cookie\CookiesFactoryInterface;
use Tobento\Service\Cookie\CookieFactoryInterface;
use Tobento\Service\Encryption\EncrypterInterface;
use Psr\Http\Message\ServerRequestInterface;
use Nyholm\Psr7\Factory\Psr17Factory;

/**
 * CookiesTest
 */
class CookiesTest extends TestCase
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
    
    public function testFactoryInterfacesAreAvailable()
    {
        $app = $this->createApp();
        $app->boot(\Tobento\App\Http\Boot\Cookies::class);
        $app->booting();
        
        $this->assertInstanceof(
            CookiesFactoryInterface::class,
            $app->get(CookiesFactoryInterface::class)
        );
        
        $this->assertInstanceof(
            CookieFactoryInterface::class,
            $app->get(CookieFactoryInterface::class)
        );
    }
    
    public function testWriteCookies()
    {
        $app = $this->createApp();
        $app->boot(\Tobento\App\Http\Boot\Cookies::class);
        $app->boot(\Tobento\App\Http\Boot\Routing::class);
        
        $app->on(ServerRequestInterface::class, function() {
            return (new Psr17Factory())->createServerRequest(
                method: 'GET',
                uri: 'foo',
                serverParams: [],
            );
        });
        
        $app->booting();
        
        $app->route('GET', 'foo', function(ServerRequestInterface $request) {
            $cookies = $request->getAttribute(CookiesInterface::class);
            $cookies?->add('bar', 'value');
            return 'foo';
        });

        $app->run();
        
        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isStatusCode(200)
            ->hasHeader('Set-Cookie', 'bar=value; Path=/; HttpOnly; SameSite=Lax')
            ->isBodySame('foo');
    }
    
    public function testReadCookies()
    {
        $app = $this->createApp();
        $app->boot(\Tobento\App\Http\Boot\Cookies::class);
        $app->boot(\Tobento\App\Http\Boot\Routing::class);
        
        $app->on(ServerRequestInterface::class, function() {
            $request = (new Psr17Factory())->createServerRequest(
                method: 'GET',
                uri: 'foo',
                serverParams: [],
            );
            
            return $request->withCookieParams(['bar' => 'value']);
        });
        
        $app->booting();
        
        $app->route('GET', 'foo', function(ServerRequestInterface $request) {
            $cookieValues = $request->getAttribute(CookieValuesInterface::class);
            return $cookieValues?->get('bar');
        });

        $app->run();
        
        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isStatusCode(200)
            ->isBodySame('value');
    }
    
    public function testWriteCookiesWithEncryption()
    {
        $app = $this->createApp();
        $app->boot(\Tobento\App\Encryption\Boot\Encryption::class);
        $app->boot(\Tobento\App\Http\Boot\Cookies::class);
        $app->boot(\Tobento\App\Http\Boot\Routing::class);
        
        $app->on(ServerRequestInterface::class, function() {
            return (new Psr17Factory())->createServerRequest(
                method: 'GET',
                uri: 'foo',
                serverParams: [],
            );
        });
        
        $app->booting();
        
        $app->route('GET', 'foo', function(ServerRequestInterface $request) {
            $cookies = $request->getAttribute(CookiesInterface::class);
            $cookies?->add('bar', 'value');
            return 'foo';
        });

        $app->run();
        
        $response = $app->get(Http::class)->getResponse();
        
        $headerValue = $response->getHeaderLine('Set-Cookie');
        
        $this->assertStringNotContainsString('value', $headerValue);
        
        (new TestResponse($response))
            ->isStatusCode(200)
            ->hasHeader('Set-Cookie')
            ->isBodySame('foo');
    }
    
    public function testReadCookiesWithEncryption()
    {
        $app = $this->createApp();
        $app->boot(\Tobento\App\Encryption\Boot\Encryption::class);
        $app->boot(\Tobento\App\Http\Boot\Cookies::class);
        $app->boot(\Tobento\App\Http\Boot\Routing::class);
        
        $app->on(ServerRequestInterface::class, function() use ($app) {
            $request = (new Psr17Factory())->createServerRequest(
                method: 'GET',
                uri: 'foo',
                serverParams: [],
            );
            
            $encrypter = $app->get(EncrypterInterface::class);
            
            return $request->withCookieParams(['bar' => $encrypter->encrypt('value')]);
        });
        
        $app->booting();
        
        $app->route('GET', 'foo', function(ServerRequestInterface $request) {
            $cookieValues = $request->getAttribute(CookieValuesInterface::class);
            return $cookieValues?->get('bar');
        });

        $app->run();
        
        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isStatusCode(200)
            ->isBodySame('value');
    }
}