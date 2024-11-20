<?php

declare(strict_types = 1);

/*
 * This file is part of the FiveLab Transactional package
 *
 * (c) FiveLab
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code
 */

namespace FiveLab\Component\Transactional;

abstract class AbstractTransactional implements TransactionalInterface, ContainErrorHandlerInterface
{
    private ?\Closure $errorHandler = null;

    public function execute(\Closure $callback)
    {
        $this->begin();

        try {
            $result = $callback();
        } catch (\Throwable $e) {
            if ($this->errorHandler) {
                ($this->errorHandler)($e, $this);
            }

            $this->rollback();

            throw $e;
        }

        $this->commit();

        return $result;
    }

    public function setErrorHandler(?\Closure $handler): void
    {
        $this->errorHandler = $handler;
    }
}
