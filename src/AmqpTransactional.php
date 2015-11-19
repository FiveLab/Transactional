<?php

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
    public function begin($key = null, array $options = [])
    {
        $this->channel->startTransaction();
    }

    /**
     * {@inheritDoc}
     */
    public function commit($key = null)
    {
        $this->channel->commitTransaction();
    }

    /**
     * {@inheritDoc}
     */
    public function rollback($key = null)
    {
        $this->channel->rollbackTransaction();
    }
}
