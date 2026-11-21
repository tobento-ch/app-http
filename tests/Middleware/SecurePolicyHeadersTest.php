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

namespace Tobento\App\Http\Test\Middleware;

use PHPUnit\Framework\TestCase;
use Tobento\App\AppInterface;
use Tobento\App\AppFactory;
use Tobento\App\Http\Boot\Http;
use Tobento\App\Http\CspInterface;
use Tobento\App\Http\Middleware\SecurePolicyHeaders;
use Tobento\App\Http\ResponseEmitterInterface;
use Tobento\App\Http\Test\Mock\ResponseEmitter;
use Tobento\Service\Filesystem\Dir;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Responser\ResponserInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use RuntimeException;

class SecurePolicyHeadersTest extends TestCase
{
    protected function createApp(
        string $uri = '',
        bool $withView = true,
        array $csp = [],
        null|string|array $middleware = SecurePolicyHeaders::class,
    ): AppInterface {
        new Dir()->delete(__DIR__.'/../app/');
        new Dir()->create(__DIR__.'/../app/');
        new Dir()->create(__DIR__.'/../app/config/');
        new Dir()->create(__DIR__.'/../app/config-test/');
        new Dir()->create(__DIR__.'/../app/views/');
        new Dir()->create(__DIR__.'/../app/public/');
        
        // the middleware as added by the config of an app, only this one:
        file_put_contents(
            __DIR__.'/../app/config-test/middleware.php',
            '<?php return '.var_export(['middlewares' => is_null($middleware) ? [] : [8000 => $middleware]], true).';'
        );
        
        if (!empty($csp)) {
            file_put_contents(
                __DIR__.'/../app/config-test/http.php',
                '<?php return '.var_export(['csp' => $csp], true).';'
            );
        }
        
        $app = new AppFactory()->createApp();
        
        $app->dirs()
            ->dir(realpath(__DIR__.'/../../'), 'root')
            ->dir(realpath(__DIR__.'/../app/'), 'app')
            ->dir($app->dir('app').'config', 'config', group: 'config')
            ->dir($app->dir('app').'config-test', 'config-test', group: 'config', priority: 20)
            ->dir($app->dir('app').'views', 'views', group: 'views')
            ->dir($app->dir('root').'vendor', 'vendor')
            // for testing only we add public within app dir.
            ->dir($app->dir('app').'public', 'public');
        
        // Replace response emitter for testing:
        $app->on(ResponseEmitterInterface::class, ResponseEmitter::class);
        
        $app->on(ServerRequestInterface::class, static function () use ($uri): ServerRequestInterface {
            return new Psr17Factory()->createServerRequest('GET', $uri)->withAddedHeader('Accept', 'text/html');
        });
        
        $app->boot(\Tobento\App\Http\Boot\ErrorHandler::class);
        $app->boot(\Tobento\App\Http\Boot\Routing::class);
        $app->boot(\Tobento\App\Http\Boot\RequesterResponser::class);
        
        if ($withView) {
            $app->boot(\Tobento\App\View\Boot\View::class);
        }
        
        $app->booting();
        
        return $app;
    }
    
    protected function createView(string $name, string $content): void
    {
        file_put_contents(__DIR__.'/../app/views/'.$name.'.php', $content);
    }
    
    protected function runApp(AppInterface $app): ResponseInterface
    {
        $app->run();
        
        return $app->get(Http::class)->getResponse();
    }
    
    public static function tearDownAfterClass(): void
    {
        new Dir()->delete(__DIR__.'/../app/');
    }
    
    public function testAddsHeaders()
    {
        $app = $this->createApp();
        $app->route('GET', '', fn () => 'home');
        
        $response = $this->runApp($app);
        $nonce = $app->get(CspInterface::class)->nonce();
        
        $this->assertSame(
            "base-uri 'none'; default-src 'self'; img-src 'self' data:; script-src 'nonce-".$nonce."' 'self'; style-src 'nonce-".$nonce."' 'self'; object-src 'none'; form-action 'self'; frame-ancestors 'self'",
            $response->getHeaderLine('Content-Security-Policy')
        );
        $this->assertSame('max-age=31536000; includeSubDomains; preload', $response->getHeaderLine('Strict-Transport-Security'));
        $this->assertSame('SAMEORIGIN', $response->getHeaderLine('X-Frame-Options'));
        $this->assertSame('nosniff', $response->getHeaderLine('X-Content-Type-Options'));
        $this->assertSame('same-origin', $response->getHeaderLine('Referrer-Policy'));
    }
    
    public function testUsesCspFromConfig()
    {
        $app = $this->createApp(csp: ['default-src' => ['self'], 'img-src' => ['self', 'https://cdn.example.com']]);
        $app->route('GET', '', fn () => 'home');
        
        $response = $this->runApp($app);
        
        $this->assertSame(
            "default-src 'self'; img-src 'self' https://cdn.example.com",
            $response->getHeaderLine('Content-Security-Policy')
        );
    }
    
    public function testSourcesAddedInRouteAreInHeader()
    {
        $app = $this->createApp(uri: 'pay');
        $app->route('GET', 'pay', function (CspInterface $csp) {
            $csp->add('frame-src', 'https://js.example.com');
            return 'pay';
        });
        
        $response = $this->runApp($app);
        
        $this->assertStringContainsString(
            "frame-src 'self' https://js.example.com",
            $response->getHeaderLine('Content-Security-Policy')
        );
    }
    
    public function testSourcesAddedInViewAreInHeader()
    {
        $app = $this->createApp();
        $this->createView('video', '<?php $view->csp()->add(\'frame-src\', \'https://video.example.com\'); ?>video');
        $app->route('GET', '', fn (ResponserInterface $responser) => $responser->render('video'));
        
        $response = $this->runApp($app);
        
        $this->assertSame('video', (string) $response->getBody());
        $this->assertStringContainsString(
            "frame-src 'self' https://video.example.com",
            $response->getHeaderLine('Content-Security-Policy')
        );
    }
    
    public function testViewHasNonceOfHeader()
    {
        $app = $this->createApp();
        $this->createView('nonce', '<style nonce="<?= $view->esc($view->get(\'cspNonce\', \'\')) ?>"></style>');
        $app->route('GET', '', fn (ResponserInterface $responser) => $responser->render('nonce'));
        
        $response = $this->runApp($app);
        $nonce = $app->get(CspInterface::class)->nonce();
        
        $this->assertSame('<style nonce="'.$nonce.'"></style>', (string) $response->getBody());
        $this->assertStringContainsString("'nonce-".$nonce."'", $response->getHeaderLine('Content-Security-Policy'));
    }
    
    public function testRequestHasNonceAttribute()
    {
        $app = $this->createApp();
        $app->route('GET', '', function (RequesterInterface $requester) {
            return $requester->request()->getAttribute('csp_nonce');
        });
        
        $response = $this->runApp($app);
        
        $this->assertSame($app->get(CspInterface::class)->nonce(), (string) $response->getBody());
    }
    
    public function testHeaderSetByResponseIsKept()
    {
        $app = $this->createApp();
        $app->route('GET', '', function (ResponseFactoryInterface $responseFactory) {
            return $responseFactory->createResponse(200)
                ->withHeader('X-Frame-Options', 'DENY')
                ->withHeader('Content-Security-Policy', "default-src 'none'");
        });
        
        $response = $this->runApp($app);
        
        $this->assertSame('DENY', $response->getHeaderLine('X-Frame-Options'));
        $this->assertSame("default-src 'none'", $response->getHeaderLine('Content-Security-Policy'));
        $this->assertSame('nosniff', $response->getHeaderLine('X-Content-Type-Options'));
    }
    
    public function testHeadersParameter()
    {
        $app = $this->createApp(middleware: [SecurePolicyHeaders::class, 'headers' => ['X-Frame-Options' => 'DENY']]);
        $app->route('GET', '', fn () => 'home');
        
        $response = $this->runApp($app);
        
        $this->assertSame('DENY', $response->getHeaderLine('X-Frame-Options'));
        $this->assertSame('', $response->getHeaderLine('Referrer-Policy'));
        $this->assertNotSame('', $response->getHeaderLine('Content-Security-Policy'));
    }
    
    public function testAddsHeadersIfExceptionIsThrown()
    {
        $app = $this->createApp(withView: false);
        $app->route('GET', '', function () {
            throw new RuntimeException('Failed');
        });
        
        $response = $this->runApp($app);
        
        $this->assertSame(500, $response->getStatusCode());
        $this->assertNotSame('', $response->getHeaderLine('Content-Security-Policy'));
        $this->assertSame('SAMEORIGIN', $response->getHeaderLine('X-Frame-Options'));
    }
    
    public function testViewMacroWorksWithoutMiddleware()
    {
        $app = $this->createApp(middleware: null);
        $this->createView('video', '<?php $view->csp()->add(\'frame-src\', \'https://video.example.com\'); ?>video');
        $app->route('GET', '', fn (ResponserInterface $responser) => $responser->render('video'));
        
        $response = $this->runApp($app);
        
        $this->assertSame('video', (string) $response->getBody());
        $this->assertSame('', $response->getHeaderLine('Content-Security-Policy'));
        $this->assertStringContainsString(
            'https://video.example.com',
            $app->get(CspInterface::class)->toHeaderValue()
        );
    }
}