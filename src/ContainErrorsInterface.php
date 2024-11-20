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

interface ContainErrorsInterface
{
    /**
     * Get threw errors
     *
     * @return iterable<\Throwable>
     */
    public function getErrors(): iterable;

    /**
     * Reset threw errors
     */
    public function reset(): void;
}
