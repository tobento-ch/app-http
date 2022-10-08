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
use Tobento\App\Http\Test\Mock\ResponseEmitter;
use Tobento\App\AppFactory;
use Tobento\Service\Filesystem\Dir;

/**
 * RoutingWithMiddlewareBaseTest
 */
class RoutingWithMiddlewareBaseTest extends RoutingTest
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
        
        $app->boot(\Tobento\App\Http\Boot\Middleware::class);
        $app->boot(\Tobento\App\Http\Boot\Routing::class);
        
        return $app;
    }
}