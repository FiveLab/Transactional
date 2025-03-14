<?php

declare(strict_types = 1);

/*
 * This file is part of the FiveLab Transactional package.
 *
 * (c) FiveLab <mail@fivelab.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FiveLab\Component\Transactional;

interface TransactionalInterface
{
    /**
     * Begin transaction
     */
    public function begin(): void;

    /**
     * Commit transaction
     */
    public function commit(): void;

    /**
     * Rollback
     */
    public function rollback(): void;

    /**
     * Execute callback in transactional
     *
     * @param \Closure $callback
     *
     * @return mixed
     */
    public function execute(\Closure $callback);
}
