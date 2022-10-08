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

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

/**
 * TestResponse
 */
class TestResponse
{
    public function __construct(
        private ResponseInterface $response
    ) {}

    /**
     * Asserts if the response is the same as the specified status code.
     *
     * @param int $statusCode
     * @return static
     */
    public function isStatusCode(int $statusCode): static
    {
        TestCase::assertSame(
            $statusCode,
            $this->response->getStatusCode(),
            sprintf(
                'Received response status code [%s] but expected [%s].',
                $this->response->getStatusCode(),
                $statusCode
            )
        );
        
        return $this;
    }
    
    /**
     * Asserts if the response body is the same as the specified body.
     *
     * @param string $body
     * @return static
     */
    public function isBodySame(string $body): static
    {
        TestCase::assertSame(
            $body,
            (string)$this->response->getBody(),
            sprintf('Response is not same with [%s]', $body)
        );
        
        return $this;
    }    
    
    /**
     * Asserts if the response is the same as the specified content type.
     *
     * @param string $contentType The content type such as 'application/json'
     * @return static
     */
    public function isContentType(string $contentType): static
    {
        $responseContentType = $this->response->getHeaderLine('Content-Type');
            
        TestCase::assertSame(
            $contentType,
            strstr($responseContentType, $responseContentType),
            sprintf(
                'Response does not contain content type [%s].',
                $contentType
            )
        );
        
        return $this;
    }
    
    /**
     * Asserts if the response has the same specified header (and value).
     *
     * @param string $name
     * @param null|string $value
     * @return static
     */    
    public function hasHeader(string $name, null|string $value = null): static
    {
        TestCase::assertTrue(
            $this->response->hasHeader($name),
            sprintf('Response does not contain header with name [%s].', $name)
        );

        $headerValue = $this->response->getHeaderLine($name);

        if ($value) {
            TestCase::assertSame(
                $value,
                $headerValue,
                sprintf(
                    'Header [%s] was found, but value [%s] does not match [%s].',
                    $name,
                    $headerValue,
                    $value
                )
            );
        }

        return $this;
    }    
}