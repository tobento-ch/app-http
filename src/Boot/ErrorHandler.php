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
 
namespace Tobento\App\Http\Boot;

use Tobento\App\Boot;
use Tobento\App\Boot\Config;
use Tobento\App\Http\Boot\RequesterResponser;
use Tobento\App\Http\HttpErrorHandlersInterface;
use Tobento\Service\Config\ConfigInterface;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\Routing\RouteNotFoundException;
use Tobento\Service\Routing\InvalidSignatureException;
use Tobento\Service\Session\SessionValidationException;
use Tobento\Service\Form\InvalidTokenException;
use Tobento\Service\View\ViewInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * ErrorHandler boot.
 */
class ErrorHandler extends Boot
{
    public const INFO = [
        'boot' => [
            'Http error handler for rendering exceptions',
        ],
    ];
    
    public const BOOT = [
        Config::class,
        RequesterResponser::class,
    ];
    
    protected const HANDLER_PRIORITY = 1500;
    
    protected const GENERAL_VIEW = 'exception/error';
    
    protected const VIEW = 'exception/%s';
    
    protected const VIEW_EXTENSIONS = [
        'text/html' => '',
        'application/xml' => '.xml',
    ];
    
    protected const GENERAL_MESSAGE = 'Invalid Request';
    
    protected const MESSAGES = [
        400 => '400 | Bad Request',
        401 => '401 | Unauthorized',
        403 => '403 | Forbidden',
        404 => '404 | Not Found',
        405 => '405 | Method Not Allowed',
        408 => '408 | Request Timeout',
        410 => '410 | Gone',
        419 => '419 | Resource Expired', // unofficial
        429 => '429 | Too Many Requests',
        500 => '500 | Internal Server Error',
        503 => '503 | Service Unavailable',
    ];
    
    /**
     * Boot application services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Default HttpErrorHandlers for exceptions.
        // You may change its behaviour with adding handlers with higher priority.
        $this->app->on(HttpErrorHandlersInterface::class, function(HttpErrorHandlersInterface $handlers) {
            $handlers->add([$this, 'handleThrowable']);
        })->priority(static::HANDLER_PRIORITY);
    }
    
    /**
     * Handle a throwable.
     *
     * @param Throwable $t
     * @return Throwable|ResponseInterface Return throwable if cannot handle, otherwise anything.
     */
    public function handleThrowable(Throwable $t): Throwable|ResponseInterface
    {
        $requester = $this->app->get(RequesterInterface::class);
        
        if ($t instanceof RouteNotFoundException) {
            return $requester->wantsJson()
                ? $this->renderJson(code: 404)
                : $this->renderView(code: 404);
        }
        
        if ($t instanceof InvalidSignatureException) {
            return $requester->wantsJson()
                ? $this->renderJson(code: 403)
                : $this->renderView(code: 403);
        }
        
        if ($t instanceof SessionValidationException) {
            return $requester->wantsJson()
                ? $this->renderJson(code: 403, message: $this->getMessage(419))
                : $this->renderView(code: 403, message: $this->getMessage(419));
        }
        
        if ($t instanceof InvalidTokenException) {
            return $requester->wantsJson()
                ? $this->renderJson(code: 403, message: $this->getMessage(419))
                : $this->renderView(code: 403, message: $this->getMessage(419));
        }
        
        // do not handle all other if in debug mode:
        if ($this->app->get(ConfigInterface::class)->get('app.debug', false)) {
            return $t;
        }
        
        return $requester->wantsJson()
            ? $this->renderJson(code: 500)
            : $this->renderView(code: 500);
    }

    /**
     * Returns the rendered json response for the specified code.
     *
     * @param int $code
     * @param null|string $message
     * @return ResponseInterface
     */
    protected function renderJson(int $code, null|string $message = null): ResponseInterface
    {
        $message = !empty($message) ? $message : $this->getMessage(code: $code);
        
        return $this->app->get(ResponserInterface::class)->json(
            data: [
                'status' => $code,
                'message' => $message,
            ],
            code: $code,
        );
    }
    
    /**
     * Returns the rendered view response for the specified code.
     *
     * @param int $code
     * @param null|string $message
     * @return ResponseInterface
     */
    protected function renderView(int $code, null|string $message = null): ResponseInterface
    {
        $responser = $this->app->get(ResponserInterface::class);
        
        [$view, $contentType] = $this->determineView(code: $code);
        
        $message = !empty($message) ? $message : $this->getMessage(code: $code);
        
        if (is_null($view)) {
            return $responser->html(
                html: $message,
                code: $code,
                contentType: 'text/plain; charset=utf-8',
            );
        }
        
        $content = $this->app->get(ViewInterface::class)->render(
            view: $view,
            data: [
                'code' => $code,
                'message' => $message,
            ],
        );
        
        return $responser->write(data: $content, code: $code)
            ->withHeader('Content-Type', $contentType);
    }

    /**
     * Returns message for the specified code.
     *
     * @param int $code
     * @return string
     */
    protected function getMessage(int $code): string
    {
        return static::MESSAGES[$code] ?? static::GENERAL_MESSAGE;
    }
    
    /**
     * Returns the determinded view and content type.
     *
     * @param int $code
     * @return array
     */
    protected function determineView(int $code): array
    {
        if (! $this->app->has(ViewInterface::class)) {
            return [null, null];
        }
        
        $view = $this->app->get(ViewInterface::class);
        $requester = $this->app->get(RequesterInterface::class);
        $acceptHeader = $requester->acceptHeader()->all();
        
        $defaultView = static::GENERAL_VIEW;
        $specificView = sprintf(static::VIEW, $code);
        
        foreach($acceptHeader as $item) {
            
            if (! array_key_exists($item->mime(), static::VIEW_EXTENSIONS)) {
                continue;
            }
            
            $extension = static::VIEW_EXTENSIONS[$item->mime()];
            
            if ($view->exists($specificView.$extension)) {
                return [$specificView.$extension, $item->mime().'; charset=utf-8'];
            }
            
            if ($view->exists($defaultView.$extension)) {
                return [$defaultView.$extension, $item->mime().'; charset=utf-8'];
            }
        }
        
        return [null, null];
    }
}