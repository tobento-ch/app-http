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
use Tobento\App\Http\Csp;
use Tobento\App\Http\CspInterface;
use InvalidArgumentException;
use Stringable;

class CspTest extends TestCase
{
    public function testThatImplementsInterfaces()
    {
        $csp = new Csp();
        
        $this->assertInstanceof(CspInterface::class, $csp);
        $this->assertInstanceof(Stringable::class, $csp);
    }
    
    public function testDefaultDirectives()
    {
        $csp = new Csp(nonce: 'abc');
        
        $this->assertSame(
            "base-uri 'none'; default-src 'self'; img-src 'self' data:; script-src 'nonce-abc' 'self'; style-src 'nonce-abc' 'self'; object-src 'none'; form-action 'self'; frame-ancestors 'self'",
            $csp->toHeaderValue()
        );
    }
    
    public function testEmptyDirectivesUseDefaultDirectives()
    {
        $this->assertSame(
            new Csp(nonce: 'abc')->toHeaderValue(),
            new Csp(directives: [], nonce: 'abc')->toHeaderValue()
        );
    }
    
    public function testDirectivesAreUsedAsGiven()
    {
        $csp = new Csp(directives: [
            'default-src' => ['self'],
            'script-src' => ['https://cdn.example.com'],
        ]);
        
        $this->assertSame("default-src 'self'; script-src https://cdn.example.com", $csp->toHeaderValue());
    }
    
    public function testToStringReturnsHeaderValue()
    {
        $csp = new Csp(directives: ['default-src' => ['self']]);
        
        $this->assertSame("default-src 'self'", (string) $csp);
    }
    
    public function testDirectivesMethod()
    {
        $csp = new Csp(directives: ['default-src' => ["'self'"], 'script-src' => ['nonce', 'SELF']]);
        
        $this->assertSame(
            ['default-src' => ['self'], 'script-src' => ['nonce', 'self']],
            $csp->directives()
        );
    }
    
    public function testNonceMethod()
    {
        $this->assertSame('abc', new Csp(nonce: 'abc')->nonce());
    }
    
    public function testGeneratesNonceIfNoneGiven()
    {
        $csp = new Csp();
        $nonce = $csp->nonce();
        
        $this->assertSame(44, strlen($nonce));
        $this->assertNotSame($nonce, new Csp()->nonce());
        $this->assertStringContainsString("'nonce-".$nonce."'", $csp->toHeaderValue());
    }
    
    public function testAddMethodAddsSourcesToExistingDirective()
    {
        $csp = new Csp(directives: ['script-src' => ['nonce', 'self']], nonce: 'abc');
        $csp->add('script-src', 'https://js.example.com', 'https://*.js.example.com');
        
        $this->assertSame(
            "script-src 'nonce-abc' 'self' https://js.example.com https://*.js.example.com",
            $csp->toHeaderValue()
        );
    }
    
    public function testAddMethodStartsNewDirectiveWithFallbackSources()
    {
        $csp = new Csp(directives: ['default-src' => ['self']]);
        $csp->add('frame-src', 'https://js.example.com');
        $csp->add('connect-src', 'https://api.example.com');
        
        $this->assertSame(
            "default-src 'self'; frame-src 'self' https://js.example.com; connect-src 'self' https://api.example.com",
            $csp->toHeaderValue()
        );
    }
    
    public function testAddMethodStartsNewDirectiveWithClosestFallback()
    {
        $csp = new Csp(directives: ['default-src' => ['self'], 'script-src' => ['nonce', 'self']], nonce: 'abc');
        $csp->add('script-src-elem', 'https://cdn.example.com');
        
        $this->assertSame(
            "default-src 'self'; script-src 'nonce-abc' 'self'; script-src-elem 'nonce-abc' 'self' https://cdn.example.com",
            $csp->toHeaderValue()
        );
    }
    
    public function testAddMethodStartsNewDirectiveWithoutFallbackEmpty()
    {
        $csp = new Csp(directives: ['default-src' => ['self']]);
        $csp->add('form-action', 'https://checkout.example.com');
        
        $this->assertSame(
            "default-src 'self'; form-action https://checkout.example.com",
            $csp->toHeaderValue()
        );
    }
    
    public function testAddMethodSkipsDuplicates()
    {
        $csp = new Csp(directives: ['script-src' => ['self']]);
        $csp->add('script-src', 'self', "'self'", 'SELF', 'https://cdn.example.com', 'https://cdn.example.com');
        
        $this->assertSame("script-src 'self' https://cdn.example.com", $csp->toHeaderValue());
    }
    
    public function testAddMethodReturnsSameInstance()
    {
        $csp = new Csp();
        
        $this->assertSame($csp, $csp->add('img-src', 'https://cdn.example.com'));
    }
    
    public function testNoneIsDroppedIfOtherSources()
    {
        $csp = new Csp(directives: ['frame-ancestors' => ['none']]);
        $csp->add('frame-ancestors', 'self');
        
        $this->assertSame("frame-ancestors 'self'", $csp->toHeaderValue());
    }
    
    public function testKeywordsAreQuoted()
    {
        $csp = new Csp(directives: [
            'script-src' => ['self', 'unsafe-inline', 'unsafe-eval', 'unsafe-hashes', 'strict-dynamic', 'report-sample', 'wasm-unsafe-eval'],
        ]);
        
        $this->assertSame(
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' 'unsafe-hashes' 'strict-dynamic' 'report-sample' 'wasm-unsafe-eval'",
            $csp->toHeaderValue()
        );
    }
    
    public function testHashesAndFixedNoncesAreQuoted()
    {
        $csp = new Csp(directives: [
            'script-src' => ['sha256-AbC+/=', "'sha384-Def'", 'nonce-fixed'],
        ]);
        
        $this->assertSame("script-src 'sha256-AbC+/=' 'sha384-Def' 'nonce-fixed'", $csp->toHeaderValue());
    }
    
    public function testHostsAndSchemesAreNotQuoted()
    {
        $csp = new Csp(directives: [
            'img-src' => ['data:', 'https:', 'https://*.example.com', 'https://example.com:8080/path/'],
        ]);
        
        $this->assertSame("img-src data: https: https://*.example.com https://example.com:8080/path/", $csp->toHeaderValue());
    }
    
    public function testDirectiveWithoutSources()
    {
        $csp = new Csp(directives: ['default-src' => ['self'], 'upgrade-insecure-requests' => []]);
        
        $this->assertSame("default-src 'self'; upgrade-insecure-requests", $csp->toHeaderValue());
    }
    
    public function testDirectiveNamesAreNormalized()
    {
        $csp = new Csp(directives: ['Default-Src' => ['self']]);
        $csp->add(' Frame-Src ', 'https://example.com');
        
        $this->assertSame(['default-src', 'frame-src'], array_keys($csp->directives()));
    }
    
    public function testAddMethodThrowsInvalidArgumentExceptionIfInvalidDirective()
    {
        $this->expectException(InvalidArgumentException::class);
        
        new Csp()->add('script-src; img-src', 'self');
    }
    
    public function testAddMethodThrowsInvalidArgumentExceptionIfSourceAddsDirective()
    {
        $this->expectException(InvalidArgumentException::class);
        
        new Csp()->add('script-src', 'https://example.com; script-src *');
    }
    
    public function testAddMethodThrowsInvalidArgumentExceptionIfSourceHasSpaces()
    {
        $this->expectException(InvalidArgumentException::class);
        
        new Csp()->add('script-src', 'self https://example.com');
    }
    
    public function testAddMethodThrowsInvalidArgumentExceptionIfEmptySource()
    {
        $this->expectException(InvalidArgumentException::class);
        
        new Csp()->add('script-src', '');
    }
    
    public function testThrowsInvalidArgumentExceptionIfInvalidNonce()
    {
        $this->expectException(InvalidArgumentException::class);
        
        new Csp(nonce: "abc' 'unsafe-inline");
    }
    
    public function testThrowsInvalidArgumentExceptionIfSourcesAreNotAnArray()
    {
        $this->expectException(InvalidArgumentException::class);
        
        new Csp(directives: ['script-src' => 'self']);
    }
    
    public function testThrowsInvalidArgumentExceptionIfSourceIsNotAString()
    {
        $this->expectException(InvalidArgumentException::class);
        
        new Csp(directives: ['script-src' => [1]]);
    }
    
    public function testThrowsInvalidArgumentExceptionIfDirectiveIsNotAString()
    {
        $this->expectException(InvalidArgumentException::class);
        
        new Csp(directives: [['self']]);
    }
}