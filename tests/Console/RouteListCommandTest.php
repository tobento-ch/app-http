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

namespace Tobento\App\Http\Test\Console;

use PHPUnit\Framework\TestCase;
use Tobento\App\Http\Console\RouteListCommand;
use Tobento\Service\Console\Test\TestCommand;
use Tobento\Service\Container\Container;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Routing\Router;
use Tobento\Service\Routing\RequestData;
use Tobento\Service\Routing\UrlGenerator;
use Tobento\Service\Routing\RouteFactory;
use Tobento\Service\Routing\RouteDispatcher;
use Tobento\Service\Routing\Constrainer\Constrainer;
use Tobento\Service\Routing\RouteHandler;
use Tobento\Service\Routing\MatchedRouteHandler;
use Tobento\Service\Routing\RouteResponseParser;

class RouteListCommandTest extends TestCase
{
    public function testCommand()
    {
        $container = new Container();
        
        $router = new Router(
            new RequestData(
                'GET',
                '',
                'example.com',
            ),
            new UrlGenerator(
                'https://example.com/basepath',
                'a-random-32-character-secret-signature-key',
            ),
            new RouteFactory(),
            new RouteDispatcher($container, new Constrainer()),
            new RouteHandler($container),
            new MatchedRouteHandler($container),
            new RouteResponseParser(),
        );
        
        $router->get('blog', 'Controller::method')->name('blog');
        
        $container->set(RouterInterface::class, $router);
        
        $rows = [];
        
        foreach($router->getRoutes() as $route) {
            $data = $route->toArray();
            $rows[] = [$data['method'], $data['uri'], $route->getName(), $data['handler']];
        }
        
        (new TestCommand(
            command: RouteListCommand::class,
        ))
        ->expectsTable(
            headers: ['Method', 'Uri', 'Name', 'Handler'],
            rows: $rows,
        )
        ->expectsExitCode(0)
        ->execute($container);
    }
    
    public function testCommandWithNameOption()
    {
        $container = new Container();
        
        $router = new Router(
            new RequestData(
                'GET',
                '',
                'example.com',
            ),
            new UrlGenerator(
                'https://example.com/basepath',
                'a-random-32-character-secret-signature-key',
            ),
            new RouteFactory(),
            new RouteDispatcher($container, new Constrainer()),
            new RouteHandler($container),
            new MatchedRouteHandler($container),
            new RouteResponseParser(),
        );
        
        $router->get('blog', 'Controller::method')->name('blog');
        
        $container->set(RouterInterface::class, $router);
        
        $data = [];
        $route = $router->getRoute('blog');
        $data['blog'] = $route->toArray();
        $data['blog']['urls']['default'] = (string)$router->url('blog');
        $data['blog']['urls']['translated'] = $router->url('blog')->translated();
        //$data['blog']['urls']['domained'] = $router->url('blog')->domained();

        (new TestCommand(
            command: RouteListCommand::class,
            input: ['--name' => ['blog']],
        ))
        ->expectsOutput(json_encode($data, JSON_PRETTY_PRINT))
        ->expectsExitCode(0)
        ->execute($container);
    }
    
    public function testCommandWithNameOptionUsingDomainedRoute()
    {
        $container = new Container();
        
        $router = new Router(
            new RequestData(
                'GET',
                '',
                'example.com',
            ),
            new UrlGenerator(
                'https://example.com/basepath',
                'a-random-32-character-secret-signature-key',
            ),
            new RouteFactory(),
            new RouteDispatcher($container, new Constrainer()),
            new RouteHandler($container),
            new MatchedRouteHandler($container),
            new RouteResponseParser(),
        );
        
        $router
            ->get('{?locale}/blog', 'Controller::method')
            ->name('blog')
            ->locales(['de', 'en'])
            ->localeOmit('en')
            ->domain('example.com');
        
        $container->set(RouterInterface::class, $router);
        
        $route = $router->getRoute('blog');
        $url = $router->url('blog');
        $domained = $url->domained();
        $data = [];
        $data['blog'] = $route->toArray();
        foreach(array_keys($domained) as $domain) {
            $url = $url->domain($domain);
            $data['blog']['urls'][$domain]['default'] = (string)$url->get();
            $data['blog']['urls'][$domain]['translated'] = $url->translated();
        }

        (new TestCommand(
            command: RouteListCommand::class,
            input: ['--name' => ['blog']],
        ))
        ->expectsOutput(json_encode($data, JSON_PRETTY_PRINT))
        ->expectsExitCode(0)
        ->execute($container);
    }
}