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
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AmqpTransactionalTest extends TestCase
{
    private \AMQPChannel $channel;

    protected function setUp(): void
    {
        parent::setUp();

        if (!\class_exists(\AMQPChannel::class)) {
            self::markTestSkipped('The AMQP not installed.');
        }

        $this->channel = $this->createMock(\AMQPChannel::class);
    }

    #[Test]
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

    #[Test]
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

    #[Test]
    public function shouldExceptionOnCommitWithoutActiveTransaction(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No active transaction.');

        $transactional = new AmqpTransactional($this->channel);
        $transactional->commit();
    }

    #[Test]
    public function shouldExceptionOnRollbackWithoutActiveTransaction(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No active transaction.');

        $transactional = new AmqpTransactional($this->channel);
        $transactional->rollback();
    }

    #[Test]
    public function shouldExecuteSuccessfully(): void
    {
        $this->channel->expects(self::once())
            ->method('startTransaction');

        $this->channel->expects(self::once())
            ->method('commitTransaction');

        $transactional = new AmqpTransactional($this->channel);
        $result = $transactional->execute(static fn() => 'some value');

        self::assertEquals('some value', $result);
    }

    #[Test]
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

    #[Test]
    public function shouldExecuteFail(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Some exception');

        $this->channel->expects(self::once())
            ->method('startTransaction');

        $this->channel->expects(self::once())
            ->method('rollbackTransaction');

        $transactional = new AmqpTransactional($this->channel);

        $transactional->execute(static fn() => throw new \InvalidArgumentException('Some exception'));
    }
}
