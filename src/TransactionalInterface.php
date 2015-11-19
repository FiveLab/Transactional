<?php

/*
 * This file is part of the FiveLab Transactional package.
 *
 * (c) FiveLab <mail@fivelab.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FiveLab\Component\Transactional;

/**
 * All transactional layers should implement this interface
 *
 * @author Vitaliy Zhuk <v.zhuk@fivelab.org>
 */
interface TransactionalInterface
{
    /**
     * Begin transaction
     *
     * @param string $key
     * @param array  $options
     */
    public function begin($key = null, array $options = []);

    /**
     * Commit transaction
     *
     * @param string $key
     */
    public function commit($key = null);

    /**
     * Rollback
     *
     * @param string $key
     */
    public function rollback($key = null);

    /**
     * Execute callback in transactional
     *
     * @param callable $callback
     * @param string   $key
     *
     * @return mixed
     */
    public function execute($callback, $key = null);
}
