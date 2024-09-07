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

namespace Tobento\App\Http\Console;

use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Console\AbstractCommand;
use Tobento\Service\Console\InteractorInterface;

class RouteListCommand extends AbstractCommand
{
    /**
     * The signature of the console command.
     */
    public const SIGNATURE = '
        route:list | List all registered routes
        {--N|name[] : The route names to list in detail}
    ';
    
    /**
     * Handle the command.
     *
     * @param InteractorInterface $io
     * @param RouterInterface $router
     * @return int The exit status code: 
     *     0 SUCCESS
     *     1 FAILURE If some error happened during the execution
     *     2 INVALID To indicate incorrect command usage e.g. invalid options
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function handle(InteractorInterface $io, RouterInterface $router): int
    {
        if (!empty($io->option(name: 'name'))) {
            $data = [];
            
            foreach($io->option(name: 'name') as $name) {
                if ($route = $router->getRoute($name)) {
                    $data[$name] = $route->toArray();
                    
                    $url = $router->url($name);
                    $domained = $url->domained();
                    
                    if (empty($domained)) {
                        $data[$name]['urls']['default'] = $url->get();
                        $data[$name]['urls']['translated'] = $url->translated();
                    } else {
                        foreach(array_keys($domained) as $domain) {
                            $url = $url->domain($domain);
                            $data[$name]['urls'][$domain]['default'] = $url->get();
                            $data[$name]['urls'][$domain]['translated'] = $url->translated();
                        }
                    }
                }
            }
            
            $io->write(json_encode($data, JSON_PRETTY_PRINT));
            return 0;
        }
        
        $rows = [];
        
        foreach($router->getRoutes() as $route) {
            $data = $route->toArray();
            $rows[] = [$data['method'], $data['uri'], $route->getName(), $data['handler']];
        }
        
        $io->table(
            headers: ['Method', 'Uri', 'Name', 'Handler'],
            rows: $rows,
        );
        
        return 0;
    }
}