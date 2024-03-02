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
 
namespace Tobento\App\Http\Test\Mock;

use Tobento\App\Http\Boot\ErrorHandler;
use Tobento\Service\Requester\RequesterInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class CustomErrorHandler extends ErrorHandler
{
    public const INFO = [
        'boot' => [
            'Custom Error Handler',
        ],
    ];
    
    protected const HANDLER_PRIORITY = 3000;
    
    public function handleThrowable(Throwable $t): Throwable|ResponseInterface
    {
        $requester = $this->app->get(RequesterInterface::class);
        
        if ($t instanceof \RuntimeException) {
            return $requester->wantsJson()
                ? $this->renderJson(code: 500, message: 'Custom message')
                : $this->renderView(code: 500, message: 'Custom message');
        }
        
        if ($t instanceof \LogicException) {
            return $requester->wantsJson()
                ? $this->renderJson(code: 500, message: 'Message :value', parameters: [':value' => 'foo'])
                : $this->renderView(code: 500, message: 'Message :value', parameters: [':value' => 'foo']);
        }
        
        return parent::handleThrowable($t);
    }
}