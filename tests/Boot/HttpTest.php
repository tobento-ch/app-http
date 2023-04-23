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
use Tobento\App\Http\HttpErrorHandlersInterface;
use Tobento\Service\Filesystem\Dir;
use Tobento\Service\Uri\BaseUriInterface;
use Tobento\Service\Uri\CurrentUriInterface;
use Tobento\Service\Uri\PreviousUriInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Nyholm\Psr7\Factory\Psr17Factory;

/**
 * HttpTest
 */
class HttpTest extends TestCase
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
                        
        $app->boot(Http::class);
        
        return $app;
    }
    
    public static function tearDownAfterClass(): void
    {
        (new Dir())->delete(__DIR__.'/../app/');
    }
    
    public function testInterfaces()
    {
        $app = $this->createApp();
        $app->booting();
        
        // PSR-7
        $this->assertInstanceof(
            ServerRequestInterface::class,
            $app->get(ServerRequestInterface::class)
        );
        
        $this->assertInstanceof(
            ResponseInterface::class,
            $app->get(ResponseInterface::class)
        );

        // UriInterface
        $this->assertInstanceof(
            BaseUriInterface::class,
            $app->get(BaseUriInterface::class)
        );
        
        $this->assertInstanceof(
            CurrentUriInterface::class,
            $app->get(CurrentUriInterface::class)
        );
        
        $this->assertInstanceof(
            PreviousUriInterface::class,
            $app->get(PreviousUriInterface::class)
        );

        // PSR-17
        $this->assertInstanceof(
            ResponseFactoryInterface::class,
            $app->get(ResponseFactoryInterface::class)
        );
        
        $this->assertInstanceof(
            StreamFactoryInterface::class,
            $app->get(StreamFactoryInterface::class)
        );
        
        $this->assertInstanceof(
            UploadedFileFactoryInterface::class,
            $app->get(UploadedFileFactoryInterface::class)
        );
        
        $this->assertInstanceof(
            UriFactoryInterface::class,
            $app->get(UriFactoryInterface::class)
        );
        
        // Error Handler
        $this->assertInstanceof(
            HttpErrorHandlersInterface::class,
            $app->get(HttpErrorHandlersInterface::class)
        );
        
        // ResponseEmitterInterface
        $this->assertInstanceof(
            ResponseEmitterInterface::class,
            $app->get(ResponseEmitterInterface::class)
        );
    }
    
    public function testBaseUriResolvesBasePath()
    {
        $app = $this->createApp();
        
        $app->on(ServerRequestInterface::class, function() {
            return (new Psr17Factory())->createServerRequest(
                method: 'GET',
                uri: 'http://localhost/foo/bar/blog/15/edit',
                serverParams: [
                    'SCRIPT_NAME' => '/foo/bar/index.php',
                ],
            );
        });
        
        $app->booting();
        
        $baseUri = $app->get(BaseUriInterface::class);
        
        $this->assertSame(
            'http://localhost/foo/bar',
            (string)$baseUri
        );
    }
    
    public function testCurrentUri()
    {
        $app = $this->createApp();
        
        $app->on(ServerRequestInterface::class, function() {
            return (new Psr17Factory())->createServerRequest(
                method: 'GET',
                uri: 'http://localhost/foo/bar/blog/15/edit',
                serverParams: [
                    'SCRIPT_NAME' => '/foo/bar/index.php',
                ],
            );
        });
        
        $app->booting();
        
        $currentUri = $app->get(CurrentUriInterface::class);
        
        $this->assertFalse($currentUri->isHome());
            
        $this->assertSame(
            'http://localhost/foo/bar/blog/15/edit',
            (string)$currentUri
        );
    }

    public function testCurrentUriIsHomeTrue()
    {
        $app = $this->createApp();
        
        $app->on(ServerRequestInterface::class, function() {
            return (new Psr17Factory())->createServerRequest(
                method: 'GET',
                uri: 'http://localhost',
                serverParams: [
                    'SCRIPT_NAME' => '/index.php',
                ],
            );
        });
        
        $app->booting();
        
        $currentUri = $app->get(CurrentUriInterface::class);
        
        $this->assertTrue($currentUri->isHome());
    }
    
    public function testCurrentUriIsHomeTrueWithBasePath()
    {
        $app = $this->createApp();
        
        $app->on(ServerRequestInterface::class, function() {
            return (new Psr17Factory())->createServerRequest(
                method: 'GET',
                uri: 'http://localhost/foo/bar/',
                serverParams: [
                    'SCRIPT_NAME' => '/foo/bar/index.php',
                ],
            );
        });
        
        $app->booting();
        
        $currentUri = $app->get(CurrentUriInterface::class);
        
        $this->assertTrue($currentUri->isHome());
    }
}