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

namespace Tobento\App\Http;

use InvalidArgumentException;

/**
 * Csp
 */
class Csp implements CspInterface
{
    /**
     * @var array<string, list<string>>
     */
    protected array $directives = [];
    
    /**
     * @var string
     */
    protected string $nonce;
    
    /**
     * Create a new Csp.
     *
     * @param array $directives The directives with their sources, e.g. ['script-src' => ['nonce', 'self']].
     *                          If empty, the default directives are used.
     * @param null|string $nonce A fixed nonce, e.g. for tests. If null, a nonce is generated.
     * @throws InvalidArgumentException If a directive, a source or the nonce is invalid.
     */
    public function __construct(
        array $directives = [],
        null|string $nonce = null,
    ) {
        if (empty($directives)) {
            $directives = $this->defaultDirectives();
        }
        
        if (is_null($nonce)) {
            $nonce = base64_encode(random_bytes(32));
        }
        
        if (preg_match('/^[A-Za-z0-9+\/=_-]+$/', $nonce) !== 1) {
            throw new InvalidArgumentException('Invalid nonce');
        }
        
        $this->nonce = $nonce;
        
        foreach ($directives as $directive => $sources) {
            if (!is_string($directive) || !is_array($sources)) {
                throw new InvalidArgumentException('Directives must be a directive name with an array of sources');
            }
            
            $directive = $this->normalizeDirective($directive);
            $this->directives[$directive] ??= [];
            
            foreach ($sources as $source) {
                if (!is_string($source)) {
                    throw new InvalidArgumentException(sprintf('Sources of the directive [%s] must be strings', $directive));
                }
                
                $this->addSource($directive, $source);
            }
        }
    }
    
    /**
     * Adds sources to a directive, e.g. add('frame-src', 'https://js.stripe.com').
     *
     * Keywords such as self or none may be written without quotes,
     * and the source nonce is replaced by the nonce of the request.
     *
     * A directive not set yet starts with the sources of the directive the browser
     * would fall back to, e.g. frame-src with the sources of default-src.
     *
     * @param string $directive
     * @param string ...$sources
     * @return static $this
     * @throws InvalidArgumentException If the directive or a source is invalid.
     */
    public function add(string $directive, string ...$sources): static
    {
        $directive = $this->normalizeDirective($directive);
        
        if (!array_key_exists($directive, $this->directives)) {
            $this->directives[$directive] = $this->fallbackSources($directive);
        }
        
        foreach ($sources as $source) {
            $this->addSource($directive, $source);
        }
        
        return $this;
    }
    
    /**
     * Returns the nonce for inline scripts and styles.
     *
     * @return string
     */
    public function nonce(): string
    {
        return $this->nonce;
    }
    
    /**
     * Returns the directives with their sources as added, e.g. ['script-src' => ['nonce', 'self']].
     *
     * @return array<string, list<string>>
     */
    public function directives(): array
    {
        return $this->directives;
    }
    
    /**
     * Returns the policy as value of the Content-Security-Policy header,
     * or an empty string if there are no directives.
     *
     * @return string
     */
    public function toHeaderValue(): string
    {
        $parts = [];
        
        foreach ($this->directives as $directive => $sources) {
            // none is ignored by the browser next to other sources:
            if (count($sources) > 1) {
                $sources = array_values(array_filter($sources, static fn (string $source): bool => $source !== 'none'));
            }
            
            $sources = array_map(fn (string $source): string => $this->renderSource($source), $sources);
            
            $parts[] = trim($directive.' '.implode(' ', $sources));
        }
        
        return implode('; ', $parts);
    }
    
    /**
     * Returns the policy as value of the Content-Security-Policy header.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->toHeaderValue();
    }
    
    /**
     * Returns the default directives, used if no directives are given.
     *
     * @return array<string, list<string>>
     */
    protected function defaultDirectives(): array
    {
        return [
            'base-uri' => ['none'],
            'default-src' => ['self'],
            'img-src' => ['self', 'data:'],
            'script-src' => ['nonce', 'self'],
            'style-src' => ['nonce', 'self'],
            'object-src' => ['none'],
            'form-action' => ['self'],
            'frame-ancestors' => ['self'],
        ];
    }
    
    /**
     * Returns the keywords written with quotes in the header.
     *
     * @return list<string>
     */
    protected function keywords(): array
    {
        return [
            'self',
            'none',
            'unsafe-inline',
            'unsafe-eval',
            'unsafe-hashes',
            'strict-dynamic',
            'report-sample',
            'wasm-unsafe-eval',
            'inline-speculation-rules',
        ];
    }
    
    /**
     * Returns the directives a new directive starts from, in the order the browser falls back.
     * Directives not listed do not fall back, e.g. form-action or frame-ancestors.
     *
     * @return array<string, list<string>>
     */
    protected function fallbacks(): array
    {
        return [
            'script-src-elem' => ['script-src', 'default-src'],
            'script-src-attr' => ['script-src', 'default-src'],
            'style-src-elem' => ['style-src', 'default-src'],
            'style-src-attr' => ['style-src', 'default-src'],
            'worker-src' => ['child-src', 'script-src', 'default-src'],
            'frame-src' => ['child-src', 'default-src'],
            'child-src' => ['default-src'],
            'connect-src' => ['default-src'],
            'font-src' => ['default-src'],
            'img-src' => ['default-src'],
            'manifest-src' => ['default-src'],
            'media-src' => ['default-src'],
            'object-src' => ['default-src'],
            'script-src' => ['default-src'],
            'style-src' => ['default-src'],
        ];
    }
    
    /**
     * Returns the normalized directive name.
     *
     * @param string $directive
     * @return string
     */
    protected function normalizeDirective(string $directive): string
    {
        $directive = strtolower(trim($directive));
        
        if (preg_match('/^[a-z][a-z-]*$/', $directive) !== 1) {
            throw new InvalidArgumentException(sprintf('Invalid directive [%s]', $directive));
        }
        
        return $directive;
    }
    
    /**
     * Adds a source to the directive if not added yet.
     *
     * @param string $directive
     * @param string $source
     * @return void
     */
    protected function addSource(string $directive, string $source): void
    {
        $source = $this->normalizeSource($directive, $source);
        
        if (!in_array($source, $this->directives[$directive] ?? [], true)) {
            $this->directives[$directive][] = $source;
        }
    }
    
    /**
     * Returns the source without quotes, keywords in lowercase.
     *
     * @param string $directive
     * @param string $source
     * @return string
     */
    protected function normalizeSource(string $directive, string $source): string
    {
        $source = trim($source);
        
        if (strlen($source) > 1 && str_starts_with($source, "'") && str_ends_with($source, "'")) {
            $source = substr($source, 1, -1);
        }
        
        // no separators, so a source cannot add other directives to the header:
        if ($source === '' || preg_match('/[\s;,\']/', $source) === 1) {
            throw new InvalidArgumentException(sprintf('Invalid source [%s] for the directive [%s]', $source, $directive));
        }
        
        $lower = strtolower($source);
        
        if ($lower === 'nonce' || in_array($lower, $this->keywords(), true)) {
            return $lower;
        }
        
        return $source;
    }
    
    /**
     * Returns the sources a new directive starts with.
     *
     * @param string $directive
     * @return list<string>
     */
    protected function fallbackSources(string $directive): array
    {
        foreach ($this->fallbacks()[$directive] ?? [] as $fallback) {
            if (array_key_exists($fallback, $this->directives)) {
                return $this->directives[$fallback];
            }
        }
        
        return [];
    }
    
    /**
     * Returns the source as written in the header.
     *
     * @param string $source
     * @return string
     */
    protected function renderSource(string $source): string
    {
        if ($source === 'nonce') {
            return "'nonce-".$this->nonce."'";
        }
        
        if (
            in_array($source, $this->keywords(), true)
            || str_starts_with($source, 'nonce-')
            || preg_match('/^sha(256|384|512)-/', $source) === 1
        ) {
            return "'".$source."'";
        }
        
        return $source;
    }
}