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
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\Filesystem\Dir;
use Tobento\Service\Dir\Dir as ServiceDir;
use Tobento\Service\Dir\Dirs;
use Tobento\Service\View;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Nyholm\Psr7\Factory\Psr17Factory;

/**
 * RequesterResponserTest
 */
class RequesterResponserTest extends TestCase
{
    protected function createApp(bool $deleteDir = true): AppInterface
    {
        if ($deleteDir) {
            (new Dir())->delete(__DIR__.'/../app/');
        }
        
        $app = (new AppFactory())->createApp();
        
        $app->dirs()
            ->dir(__DIR__.'/../app/', 'app')
            ->dir($app->dir('app').'config', 'config', group: 'config');
        
        // Replace response emitter for testing:
        $app->on(ResponseEmitterInterface::class, ResponseEmitter::class);
        
        return $app;
    }
    
    public static function tearDownAfterClass(): void
    {
        (new Dir())->delete(__DIR__.'/../app/');
    }
    
    public function testInterfaces()
    {
        $app = $this->createApp();
        $app->boot(\Tobento\App\Http\Boot\RequesterResponser::class);
        $app->booting();
          
        $this->assertInstanceof(
            RequesterInterface::class,
            $app->get(RequesterInterface::class)
        );
        
        $this->assertInstanceof(
            ResponserInterface::class,
            $app->get(ResponserInterface::class)
        );
    }
    
    public function testResponserUsesViewInterfaceForRenderer()
    {
        $app = $this->createApp();
        $app->boot(\Tobento\App\Http\Boot\RequesterResponser::class);
        
        $app->set(View\ViewInterface::class, function () {
            return new View\View(
                new View\PhpRenderer(
                    new Dirs(
                        new ServiceDir(__DIR__.'/../view/'),
                    )
                ),
                new View\Data(),
                new View\Assets(__DIR__.'/../public/src/', 'https://www.example.com/src/')
            );
        });
        
        $app->booting();
        
        $response = $app->get(ResponserInterface::class)->render(
            view: 'about',
            data: ['title' => 'About us'],
        );
                
        $this->assertSame(
            '<!DOCTYPE html><html><head><title>About us</title></head><body>About</body></html>',
            (string)$response->getBody()
        );
    }
}