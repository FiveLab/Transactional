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

namespace FiveLab\Component\Transactional\Tests;

use FiveLab\Component\Transactional\AbstractTransactional;

class TestTransactional extends AbstractTransactional
{
    private array $calls = [];
    private array $errorHandlers = [];

    public function __construct()
    {
        $this->setErrorHandler(function () {
            $args = \func_get_args();

            foreach ($this->errorHandlers as $handler) {
                ($handler)(...$args);
            }
        });

        $this->errorHandlers[] = function (\Throwable $error) {
            $this->calls[] = ['handle error', \func_get_args()];
        };
    }

    public function getCalls(): array
    {
        return $this->calls;
    }

    public function setErrorHandler(?\Closure $handler): void
    {
        if (!\count($this->errorHandlers)) {
            parent::setErrorHandler($handler);

            return;
        }

        $this->errorHandlers[] = $handler;
    }

    public function begin(): void
    {
        $this->calls[] = ['begin', \func_get_args()];
    }

    public function commit(): void
    {
        $this->calls[] = ['commit', \func_get_args()];
    }

    public function rollback(): void
    {
        $this->calls[] = ['rollback', \func_get_args()];
    }
}
