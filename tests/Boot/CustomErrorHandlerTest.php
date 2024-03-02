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
use Tobento\App\Http\Boot\Routing;
use Tobento\App\Http\ResponseEmitterInterface;
use Tobento\App\Http\Test\TestResponse;
use Tobento\App\Http\Test\Mock\ResponseEmitter;
use Tobento\App\Http\Test\Mock\CustomErrorHandler;
use Tobento\App\Translation\Boot\Translation;
use Tobento\Service\Translation\TranslatorInterface;
use Tobento\Service\Translation\Resource;
use Tobento\Service\Session\RemoteAddrValidation;
use Tobento\Service\Session\SessionValidationException;
use Tobento\Service\Filesystem\Dir;
use Psr\Http\Message\ServerRequestInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Tobento\App\Http\Boot\ErrorHandler;

/**
 * CustomErrorHandlerTest
 */
class CustomErrorHandlerTest extends TestCase
{
    protected function createApp(
        array $request = [],
        null|string $accept = null,
        bool $deleteDir = true,
        bool $translation = false,
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
            
            if ($accept) {
                return $serverRequest->withAddedHeader('Accept', $accept);
            }
            
            return $serverRequest;
        });
        
        if ($translation) {
            $app->boot(Translation::class);
            $app->on(TranslatorInterface::class, function (TranslatorInterface $translator) {
                $translator->setLocale('de');
                
                $translator->resources()->add(new Resource('*', 'de', [
                    'Message :value' => 'Nachricht :value',
                ]));
            });
        }
        
        $app->boot(CustomErrorHandler::class);
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
    
    public function testAnyExceptionWithMessage()
    {
        $app = $this->createApp(request: [
            'method' => 'GET',
            'uri' => '',
            'serverParams' => [],
        ]);
        
        $app->middleware(function($request, $handler) {
            throw new \RuntimeException();
        });
                
        $app->run();

        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isStatusCode(500)
            ->isContentType('text/plain; charset=utf-8')
            ->isBodySame('Custom message');
    }
    
    public function testAnyExceptionWithMessageJsonResponse()
    {
        $app = $this->createApp(request: [
            'method' => 'GET',
            'uri' => '',
            'serverParams' => [],
        ], accept: 'application/json');
        
        $app->middleware(function($request, $handler) {
            throw new \RuntimeException();
        });
                
        $app->run();

        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isStatusCode(500)
            ->isContentType('application/json')
            ->isBodySame('{"status":500,"message":"Custom message"}');
    }
    
    public function testTranslatesMessage()
    {
        $app = $this->createApp(request: [
            'method' => 'GET',
            'uri' => '',
            'serverParams' => [],
        ], translation: true);
        
        $app->middleware(function($request, $handler) {
            throw new \LogicException();
        });
                
        $app->run();

        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isStatusCode(500)
            ->isContentType('text/plain; charset=utf-8')
            ->isBodySame('Nachricht foo');
    }
    
    public function testTranslatesMessageJsonResponse()
    {
        $app = $this->createApp(request: [
            'method' => 'GET',
            'uri' => '',
            'serverParams' => [],
        ], accept: 'application/json', translation: true);
        
        $app->middleware(function($request, $handler) {
            throw new \LogicException();
        });
                
        $app->run();

        (new TestResponse($app->get(Http::class)->getResponse()))
            ->isStatusCode(500)
            ->isContentType('application/json')
            ->isBodySame('{"status":500,"message":"Nachricht foo"}');
    }
}