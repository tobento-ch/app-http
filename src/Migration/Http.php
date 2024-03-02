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

namespace Tobento\App\Http\Migration;

use Tobento\Service\Migration\MigrationInterface;
use Tobento\Service\Migration\ActionsInterface;
use Tobento\Service\Migration\Actions;
use Tobento\Service\Migration\Action\FilesCopy;
use Tobento\Service\Migration\Action\FilesDelete;
use Tobento\Service\Migration\Action\FileStringReplacer;
use Tobento\Service\Dir\DirsInterface;

/**
 * Http
 */
class Http implements MigrationInterface
{
    protected array $configFiles;

    protected array $transFiles;
    
    /**
     * Create a new Migration.
     *
     * @param DirsInterface $dirs
     */    
    public function __construct(
        protected DirsInterface $dirs,
    ) {
        $resources = realpath(__DIR__.'/../../').'/resources/';
        
        $this->configFiles = [
            $this->dirs->get('config') => [
                $resources.'config/http.php',
                $resources.'config/session.php',
                $resources.'config/cookies.php',
            ],
        ];
        
        // Add trans dir if not exists:
        if (! $this->dirs->has('trans')) {
            $this->dirs->dir(
                dir: $this->dirs->get('app').'trans/',
                name: 'trans',
                group: 'trans',
                priority: 100,
            );
        }
        
        $this->transFiles = [
            $this->dirs->get('trans').'en/' => [
                $resources.'trans/en/en-http.json',
            ],
            $this->dirs->get('trans').'de/' => [
                $resources.'trans/de/de-http.json',
            ],
        ];
    }
    
    /**
     * Return a description of the migration.
     *
     * @return string
     */    
    public function description(): string
    {
        return 'Http config and translation files.';
    }
        
    /**
     * Return the actions to be processed on install.
     *
     * @return ActionsInterface
     */    
    public function install(): ActionsInterface
    {        
        return new Actions(
            new FilesCopy(
                files: $this->configFiles,
                type: 'config',
                description: 'Http config files.',
            ),
            new FilesCopy(
                files: $this->transFiles,
                type: 'trans',
                description: 'Http translation files.',
            ),
            new FileStringReplacer(
                file: $this->dirs->get('config').'http.php',
                replace: [
                    '{signature_key}' => base64_encode(random_bytes(32)),
                ],
                type: 'config',
                description: 'signature_key generation.',
            ),
        );
    }

    /**
     * Return the actions to be processed on uninstall.
     *
     * @return ActionsInterface
     */    
    public function uninstall(): ActionsInterface
    {
        return new Actions(
            new FilesDelete(
                files: $this->configFiles,
                type: 'config',
                description: 'Http config files.',
            ),
            new FilesDelete(
                files: $this->transFiles,
                type: 'trans',
                description: 'Http translation files.',
            ),
        );
    }
}