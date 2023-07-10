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
use Tobento\App\Http\Routing\ArgumentsHandlerParameters;

/**
 * ArgumentsHandlerParametersTest
 */
class ArgumentsHandlerParametersTest extends TestCase
{
    public function testConstructMethod()
    {
        $params = new ArgumentsHandlerParameters(parameters: ['key' => 'value']);
        
        $this->assertSame(['key' => 'value'], $params->getParameters());
    }
    
    public function testAddMethod()
    {
        $params = new ArgumentsHandlerParameters(parameters: []);
        $params->add(key: 'foo', value: 'Foo');
        $params->add(key: null, value: 'Bar');
        $params->add(key: 5, value: '5');
        $this->assertSame(['foo' => 'Foo', 0 => 'Bar', 5 => '5'], $params->getParameters());
    }
    
    public function testAddMethodOverwritesExisting()
    {
        $params = new ArgumentsHandlerParameters(parameters: []);
        $params->add(key: 'foo', value: 'Foo');
        $params->add(key: null, value: 'Bar');
        $params->add(key: 5, value: '5');

        $params->add(key: 'foo', value: 'New Foo');
        $params->add(key: 5, value: 'New 5');
        
        $this->assertSame(['foo' => 'New Foo', 0 => 'Bar', 5 => 'New 5'], $params->getParameters());
    }
    
    public function testRemoveMethod()
    {
        $params = new ArgumentsHandlerParameters(parameters: []);
        $params->add(key: 'foo', value: 'Foo');
        $params->add(key: 5, value: '5');
        
        $params->remove(key: 'unkown');
        $params->remove(key: 'foo');
        $params->remove(key: 5);
        
        $this->assertSame([], $params->getParameters());
    }
}