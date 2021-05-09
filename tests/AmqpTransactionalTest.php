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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * AMQP Transactional tests
 *
 * @author Vitaliy Zhuk <v.zhuk@fivelab.org>
 */
class AmqpTransactionalTest extends TestCase
{
    /**
     * @var \AMQPChannel|MockObject
     */
    private \AMQPChannel $channel;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (!\class_exists(\AMQPChannel::class)) {
            self::markTestSkipped('The AMQP not installed.');
        }

        $this->channel = $this->createMock(\AMQPChannel::class);
    }

    /**
     * @test
     */
    public function shouldBeginAndCommitTransaction(): void
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
     * @test
     */
    public function shouldBeginAndRollbackTransaction(): void
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
     * @test
     */
    public function shouldExceptionOnCommitWithoutActiveTransaction(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No active transaction.');

        $transactional = new AmqpTransactional($this->channel);
        $transactional->commit();
    }

    /**
     * @test
     */
    public function shouldExceptionOnRollbackWithoutActiveTransaction(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No active transaction.');

        $transactional = new AmqpTransactional($this->channel);
        $transactional->rollback();
    }

    /**
     * @test
     */
    public function shouldExecuteSuccessfully(): void
    {
        $this->channel->expects(self::once())
            ->method('startTransaction');

        $this->channel->expects(self::once())
            ->method('commitTransaction');

        $transactional = new AmqpTransactional($this->channel);
        $result = $transactional->execute(static function () {
            return 'some value';
        });

        self::assertEquals('some value', $result);
    }

    /**
     * @test
     */
    public function shouldExecuteSuccessfullyWithHierarchicallyCall(): void
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
     * @test
     */
    public function shouldExecuteFail(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Some exception');

        $this->channel->expects(self::once())
            ->method('startTransaction');

        $this->channel->expects(self::once())
            ->method('rollbackTransaction');

        $transactional = new AmqpTransactional($this->channel);

        $transactional->execute(static function () {
            throw new \InvalidArgumentException('Some exception');
        });
    }
}
