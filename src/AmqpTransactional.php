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

class AmqpTransactional extends AbstractTransactional
{
    private int $nestingLevel = 0;

    public function __construct(private readonly \AMQPChannel $channel)
    {
    }

    public function begin(): void
    {
        if (0 === $this->nestingLevel) {
            $this->channel->startTransaction();
        }

        $this->nestingLevel++;
    }

    public function commit(): void
    {
        if (0 === $this->nestingLevel) {
            throw new \RuntimeException('No active transaction.');
        }

        $this->nestingLevel--;

        if (0 === $this->nestingLevel) {
            $this->channel->commitTransaction();
        }
    }

    public function rollback(): void
    {
        if (0 === $this->nestingLevel) {
            throw new \RuntimeException('No active transaction.');
        }

        $this->nestingLevel--;

        $this->channel->rollbackTransaction();
    }
}
