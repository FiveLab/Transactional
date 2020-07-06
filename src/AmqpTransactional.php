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

/**
 * AMQP Transactional
 *
 * @author Vitaliy Zhuk <v.zhuk@fivelab.org>
 */
class AmqpTransactional extends AbstractTransactional
{
    /**
     * @var \AMQPChannel
     */
    private $channel;

    /**
     * @var int
     */
    private $nestingLevel = 0;

    /**
     * Construct
     *
     * @param \AMQPChannel $channel
     */
    public function __construct(\AMQPChannel $channel)
    {
        $this->channel = $channel;
    }

    /**
     * {@inheritDoc}
     */
    public function begin(): void
    {
        if (0 === $this->nestingLevel) {
            $this->channel->startTransaction();
        }

        $this->nestingLevel++;
    }

    /**
     * {@inheritDoc}
     */
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

    /**
     * {@inheritDoc}
     */
    public function rollback(): void
    {
        if (0 === $this->nestingLevel) {
            throw new \RuntimeException('No active transaction.');
        }

        $this->nestingLevel--;

        $this->channel->rollbackTransaction();
    }
}
