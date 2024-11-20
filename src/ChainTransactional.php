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

class ChainTransactional extends AbstractTransactional
{
    /**
     * @var array<TransactionalInterface>
     */
    private array $layers = [];

    /**
     * Constructor.
     *
     * @param array<TransactionalInterface> $layers
     */
    public function __construct(array $layers = [])
    {
        foreach ($layers as $layer) {
            $this->addTransactional($layer);
        }
    }

    public function addTransactional(TransactionalInterface $transactional): void
    {
        $this->layers[\spl_object_hash($transactional)] = $transactional;
    }

    public function begin(): void
    {
        foreach ($this->layers as $transactional) {
            $transactional->begin();
        }
    }

    public function commit(): void
    {
        $layers = $this->layers;
        $mustRollback = false;
        $firstException = null;

        while ($layer = \array_pop($layers)) {
            try {
                if ($mustRollback) {
                    $layer->rollback();
                } else {
                    $layer->commit();
                }
            } catch (\Throwable $error) {
                $mustRollback = true;
                $firstException = $firstException ?: $error;
            }
        }

        if ($firstException) {
            throw $firstException;
        }
    }

    public function rollback(): void
    {
        $layers = $this->layers;
        $firstException = null;

        while ($transactional = \array_pop($layers)) {
            try {
                $transactional->rollback();
            } catch (\Throwable $error) {
                $firstException = $firstException ?: $error;
            }
        }

        if ($firstException) {
            throw $firstException;
        }
    }
}
