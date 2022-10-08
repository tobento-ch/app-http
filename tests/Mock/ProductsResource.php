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

/**
 * ProductsResource
 */
class ProductsResource
{
    /**
     * Index action
     *
     * @return string
     */
    public function index(): string
    {
        return 'index';
    }
    
    /**
     * Create action
     *
     * @return string
     */
    public function create(): string
    {
        return 'create';
    }
    
    /**
     * Store action
     *
     * @return string
     */
    public function store(): string
    {
        return 'store';
    }
    
    /**
     * Show action
     *
     * @return string
     */
    public function show($id): string
    {
        return 'show/'.$id;
    }

    /**
     * Edit action
     *
     * @return string
     */
    public function edit($id): string
    {
        return 'edit/'.$id;
    }
    
    /**
     * Update action
     *
     * @return string
     */
    public function update($id): string
    {
        return 'update/'.$id;
    }
    
    /**
     * Delete action
     *
     * @return string
     */
    public function delete($id): string
    {
        return 'delete/'.$id;
    }
}