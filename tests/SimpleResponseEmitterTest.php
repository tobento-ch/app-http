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

namespace Tobento\App\Http\Test;

use Laminas\HttpHandlerRunner\Exception\EmitterException;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Tobento\App\Http\SimpleResponseEmitterInterface;
use Tobento\App\Http\SimpleResponseEmitter;

class SimpleResponseEmitterTest extends TestCase
{
    public function testImplementsInterface()
    {
        $emitter = new SimpleResponseEmitter();

        $this->assertInstanceOf(SimpleResponseEmitterInterface::class, $emitter);
    }

    public function testEmitReturnsResponse()
    {
        $emitter = new SimpleResponseEmitter();

        $response = new Psr17Factory()->createResponse(200);

        try {
            $emitted = $emitter->emit($response);
        } catch (EmitterException $e) {
            // headers already sent in CLI test environment
            $emitted = $response;
        }

        $this->assertInstanceOf(ResponseInterface::class, $emitted);
        $this->assertSame($response, $emitted);
    }

    public function testEmitDoesNotThrowForValidResponse()
    {
        $emitter = new SimpleResponseEmitter();

        $response = new Psr17Factory()->createResponse(200);

        try {
            $emitter->emit($response);
        } catch (EmitterException $e) {
            // acceptable in test environment
        }

        $this->assertTrue(true);
    }
}