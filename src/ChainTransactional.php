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

/**
 * Chain transactional
 *
 * @author Vitaliy Zhuk <v.zhuk@fivelab.org>
 */
class ChainTransactional extends AbstractTransactional
{
    /**
     * @var array|TransactionalInterface[]
     */
    private $layers = [];

    /**
     * Construct
     *
     * @param array|TransactionalInterface[] $layers
     */
    public function __construct(array $layers = [])
    {
        foreach ($layers as $layer) {
            $this->addTransactional($layer);
        }
    }

    /**
     * Add transactional layer
     *
     * @param TransactionalInterface $transactional
     */
    public function addTransactional(TransactionalInterface $transactional): void
    {
        $this->layers[spl_object_hash($transactional)] = $transactional;
    }

    /**
     * {@inheritDoc}
     */
    public function begin(): void
    {
        foreach ($this->layers as $transactional) {
            $transactional->begin();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function commit(): void
    {
        $layers = $this->layers;
        $mustRollback = false;

        while ($layer = \array_shift($layers)) {
            try {
                if ($mustRollback) {
                    $layer->rollback();
                } else {
                    $layer->commit();
                }
            } catch (\Throwable $error) {
                $mustRollback = true;
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function rollback(): void
    {
        foreach ($this->layers as $transactional) {
            try {
                $transactional->rollback();
            } catch (\Throwable $error) {
                // nothing action, all next layers should rollback also
            }
        }
    }
}
