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

use FiveLab\Component\Transactional\AmqpTransactional;
use PHPUnit\Framework\TestCase;

/**
 * AMQP Transactional tests
 *
 * @author Vitaliy Zhuk <v.zhuk@fivelab.org>
 */
class AmqpTransactionalTest extends TestCase
{
    /**
     * @var \AMQPChannel|\PHPUnit_Framework_MockObject_MockObject
     */
    private $channel;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (!\class_exists(\AMQPChannel::class)) {
            $this->markTestSkipped('The AMQP not installed.');
        }

        $this->channel = $this->createMock(\AMQPChannel::class);
    }

    /**
     * Test begin transaction
     */
    public function testBeginAndCommitTransaction(): void
    {
        $this->channel->expects(self::once())
            ->method('startTransaction');

        $this->channel->expects(self::once())
            ->method('commitTransaction');

        $transactional = new AmqpTransactional($this->channel);
        $transactional->begin();
        $transactional->commit();
    }

    /**
     * Test rollback
     */
    public function testBeginAndRollbackTransaction(): void
    {
        $this->channel->expects(self::once())
            ->method('startTransaction');

        $this->channel->expects(self::once())
            ->method('rollbackTransaction');

        $transactional = new AmqpTransactional($this->channel);
        $transactional->begin();
        $transactional->rollback();
    }

    /**
     * Test exception on commit without active transaction.
     */
    public function testExceptionOnCommitWithoutActiveTransaction(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No active transaction.');

        $transactional = new AmqpTransactional($this->channel);
        $transactional->commit();
    }

    /**
     * Test exception on rollback without active transaction.
     */
    public function testExceptionOnRollbackWithoutActiveTransaction(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No active transaction.');

        $transactional = new AmqpTransactional($this->channel);
        $transactional->rollback();
    }

    /**
     * Test successfully execute
     */
    public function testExecuteSuccessfully(): void
    {
        $this->channel->expects(self::at(0))
            ->method('startTransaction');

        $this->channel->expects(self::at(1))
            ->method('commitTransaction');

        $transactional = new AmqpTransactional($this->channel);
        $result = $transactional->execute(static function () {
            return 'some value';
        });

        self::assertEquals('some value', $result);
    }

    public function testExecuteSuccessfullyWithHierarchicallyCall(): void
    {
        $this->channel->expects(self::once())
            ->method('startTransaction');

        $this->channel->expects(self::once())
            ->method('commitTransaction');

        $transactional = new AmqpTransactional($this->channel);

        $result = $transactional->execute(static function () use ($transactional) {
            return $transactional->execute(static function () use ($transactional) {
                return $transactional->execute(function () {
                    return 'foo bar';
                });
            });
        });

        self::assertEquals('foo bar', $result);
    }

    /**
     * Test fail execute
     */
    public function testExecuteFail(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Some exception');

        $this->channel->expects(self::at(0))
            ->method('startTransaction');

        $this->channel->expects(self::at(1))
            ->method('rollbackTransaction');

        $transactional = new AmqpTransactional($this->channel);

        $transactional->execute(static function () {
            throw new \InvalidArgumentException('Some exception');
        });
    }
}
