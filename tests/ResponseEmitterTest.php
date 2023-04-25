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
use Tobento\App\Http\ResponseEmitterInterface;
use Tobento\App\Http\ResponseEmitter;
use Tobento\App\Http\HttpErrorHandlers;
use Tobento\Service\ErrorHandler\AutowiringThrowableHandlerFactory;
use Tobento\Service\Container\Container;
use Nyholm\Psr7\Factory\Psr17Factory;
use Laminas\HttpHandlerRunner\Exception\EmitterException;
use Tobento\Service\Collection\Collection;
use Psr\Http\Message\ResponseInterface;
use Exception;
use Throwable;

/**
 * ResponseEmitterTest
 */
class ResponseEmitterTest extends TestCase
{
    public function testImplementsInterface()
    {
        $httpErrorHandlers = new HttpErrorHandlers(
            new AutowiringThrowableHandlerFactory(new Container())
        );
        
        $emitter = new ResponseEmitter($httpErrorHandlers);
          
        $this->assertInstanceof(ResponseEmitterInterface::class, $emitter);
    }
    
    public function testBeforeAndAfterMethods()
    {
        $httpErrorHandlers = new HttpErrorHandlers(
            new AutowiringThrowableHandlerFactory(new Container())
        );
        
        $emitter = new ResponseEmitter($httpErrorHandlers);
        
        $collection = new Collection();
        
        $emitter->before(function() use ($collection) {
            $collection->add('before', true);
        });
        
        $emitter->before(function(ResponseInterface $response) use ($collection) {
            $collection->add('before1', true);
        });
        
        $emitter->after(function() use ($collection) {
            $collection->add('after', true);
        });

        $response = (new Psr17Factory())->createResponse(code: 200);
        
        try {
            $emitter->emit($response);
        } catch (EmitterException $e) {
            // headers already sent
        }
        
        $this->assertTrue($collection->has('before'));
        $this->assertTrue($collection->has('before1'));
        
        // get never executed as Exception is thrown.
        //$this->assertTrue($collection->has('after'));
    }
    
    public function testExceptionsCausedByBeforeMethodAreHandledByHttpErrorHandlers()
    {
        $httpErrorHandlers = new HttpErrorHandlers(
            new AutowiringThrowableHandlerFactory(new Container())
        );
        
        $httpErrorHandlers->add(function(Throwable $t): mixed {
            if ($t instanceof Exception) {
                return (new Psr17Factory())->createResponse(code: 200);
            }
            
            return $t;
        });
        
        $emitter = new ResponseEmitter($httpErrorHandlers);
        
        $emitter->before(function() {
            throw new Exception('message');
        });
        
        $response = (new Psr17Factory())->createResponse(code: 200);
        
        try {
            $emitter->emit($response);
        } catch (EmitterException $e) {
            // headers already sent
        }
        
        $this->assertTrue(true);
    }
}