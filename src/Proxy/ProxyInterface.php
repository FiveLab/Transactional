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

namespace FiveLab\Component\Transactional\Proxy;

use FiveLab\Component\Transactional\TransactionalInterface;

/**
 * Indicate proxy classes
 *
 * @author Vitaliy ZHuk <zhuk2205@gmail.com>
 */
interface ProxyInterface
{
    /**
     * Set transactional instance
     *
     * @param TransactionalInterface $transactional
     */
    public function ___setTransactional(TransactionalInterface $transactional): void;

    /**
     * Get real class name
     *
     * @return string
     */
    public function ___getRealClassName(): string;
}
