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

namespace Tobento\App\Http\Test\Boot\Routing;

use PHPUnit\Framework\TestCase;
use Tobento\App\Http\Routing\DeclaredHandlerParameters;
use Tobento\App\Http\Test\Mock\ProductsResource;
use Tobento\App\Http\Test\Mock\ControllerRequestAttribute;
use ReflectionParameter;

/**
 * DeclaredHandlerParametersTest
 */
class DeclaredHandlerParametersTest extends TestCase
{
    public function testWithNull()
    {
        $params = new DeclaredHandlerParameters(handler: null);
        
        $this->assertSame([], $params->getParameters());
    }
    
    public function testWithClosure()
    {
        $params = new DeclaredHandlerParameters(handler: function (Foo $foo) {
            
        });
        
        $this->assertInstanceof(ReflectionParameter::class, $params->getParameters()[0] ?? null);
    }
    
    public function testWithClosureWithoutDeclared()
    {
        $params = new DeclaredHandlerParameters(handler: function () {
            
        });
        
        $this->assertSame([], $params->getParameters());
    }
    
    public function testWithArrayEmpty()
    {
        $params = new DeclaredHandlerParameters(handler: []);
        
        $this->assertSame([], $params->getParameters());
    }

    public function testWithArrayInvalidString()
    {
        $params = new DeclaredHandlerParameters(handler: [Invalid::class]);
        
        $this->assertSame([], $params->getParameters());
    }
    
    public function testWithArrayStringInvoke()
    {
        $params = new DeclaredHandlerParameters(handler: [ControllerRequestAttribute::class]);
        
        $this->assertInstanceof(ReflectionParameter::class, $params->getParameters()[0] ?? null);
    }
    
    public function testWithArrayStringAndMethod()
    {
        $params = new DeclaredHandlerParameters(handler: [ProductsResource::class, 'edit']);
        
        $this->assertInstanceof(ReflectionParameter::class, $params->getParameters()[0] ?? null);
    }
    
    public function testWithInvalidString()
    {
        $params = new DeclaredHandlerParameters(handler: Invalid::class);
        
        $this->assertSame([], $params->getParameters());
    }
    
    public function testWithStringInvoke()
    {
        $params = new DeclaredHandlerParameters(handler: ControllerRequestAttribute::class);
        
        $this->assertInstanceof(ReflectionParameter::class, $params->getParameters()[0] ?? null);
    }
    
    public function testWithStringAndMethod()
    {
        $params = new DeclaredHandlerParameters(handler: '\Tobento\App\Http\Test\Mock\ProductsResource::edit');
        
        $this->assertInstanceof(ReflectionParameter::class, $params->getParameters()[0] ?? null);
    }
}