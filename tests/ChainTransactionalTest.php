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

use FiveLab\Component\Transactional\ChainTransactional;
use FiveLab\Component\Transactional\TransactionalInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ChainTransactionalTest extends TestCase
{
    private TransactionalInterface $first;
    private TransactionalInterface $second;

    protected function setUp(): void
    {
        $this->first = $this->createMock(TransactionalInterface::class);
        $this->second = $this->createMock(TransactionalInterface::class);
    }

    #[Test]
    public function shouldBeginTransaction(): void
    {
        $this->first->expects(self::once())->method('begin');

        $this->second->expects(self::once())->method('begin');

        $transactional = new ChainTransactional([
            $this->first,
            $this->second,
        ]);

        $transactional->begin();
    }

    #[Test]
    public function shouldCommitTransaction(): void
    {
        $this->first->expects(self::once())->method('commit');

        $this->second->expects(self::once())->method('commit');

        $transactional = new ChainTransactional([
            $this->first,
            $this->second,
        ]);

        $transactional->commit();
    }

    #[Test]
    public function shouldRollbackTransaction(): void
    {
        $this->first->expects(self::once())->method('rollback');

        $this->second->expects(self::once())->method('rollback');

        $transactional = new ChainTransactional([
            $this->first,
            $this->second,
        ]);

        $transactional->rollback();
    }

    #[Test]
    public function shouldExecuteSuccessfully(): void
    {
        $this->first->expects(self::once())->method('begin');
        $this->first->expects(self::once())->method('commit');

        $this->second->expects(self::once())->method('begin');
        $this->second->expects(self::once())->method('commit');

        $transactional = new ChainTransactional([
            $this->first,
            $this->second,
        ]);

        $result = $transactional->execute(static function () {
            return 'some value';
        });

        self::assertEquals('some value', $result);
    }

    #[Test]
    public function shouldExecuteFail(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Some exception');

        $this->first->expects(self::once())->method('begin');
        $this->first->expects(self::once())->method('rollback');

        $this->second->expects(self::once())->method('begin');
        $this->second->expects(self::once())->method('rollback');

        $transactional = new ChainTransactional([
            $this->first,
            $this->second,
        ]);

        $transactional->execute(static function () {
            throw new \InvalidArgumentException('Some exception');
        });
    }

    #[Test]
    public function shouldRollBackAllLayersIfCommitFails(): void
    {
        $this->second
            ->expects(self::exactly(1))
            ->method('commit')
            ->willThrowException(new \RuntimeException('some exception'));

        $this->second->expects(self::exactly(0))->method('rollback');
        $this->second->expects(self::exactly(1))->method('commit');

        $this->first->expects(self::exactly(1))->method('rollback');
        $this->first->expects(self::exactly(0))->method('commit');

        $transactional = new ChainTransactional([
            $this->first,
            $this->second,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('some exception');

        $transactional->commit();
    }

    #[Test]
    public function shouldRollbackAllLayersIfOneRollbackFails(): void
    {
        $this->second
            ->expects(self::exactly(1))
            ->method('rollback')
            ->willThrowException(new \RuntimeException('some exception'));

        $this->first->expects(self::exactly(1))->method('rollback');

        $transactional = new ChainTransactional([
            $this->first,
            $this->second,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('some exception');

        $transactional->rollback();
    }

    #[Test]
    public function shouldCommitInCorrectOrder(): void
    {
        $order = [];

        $this->second->expects(self::once())->method('commit')
            ->willReturnCallback(static function () use (&$order) {
                $order[] = 'second';
            });

        $this->first->expects(self::once())->method('commit')
            ->willReturnCallback(static function () use (&$order) {
                $order[] = 'first';
            });

        $transactional = new ChainTransactional([
            $this->first,
            $this->second,
        ]);

        $transactional->commit();

        self::assertEquals(['second', 'first'], $order);
    }

    #[Test]
    public function shouldRollbackInCorrectOrder(): void
    {
        $order = [];

        $this->second->expects(self::once())->method('rollback')
            ->willReturnCallback(static function () use (&$order) {
                $order[] = 'second';
            });

        $this->first->expects(self::once())->method('rollback')
            ->willReturnCallback(static function () use (&$order) {
                $order[] = 'first';
            });

        $transactional = new ChainTransactional([
            $this->first,
            $this->second,
        ]);

        $transactional->rollback();

        self::assertEquals(['second', 'first'], $order);
    }

    #[Test]
    public function shouldCorrectHandleError(): void
    {
        $error = new \TypeError('some exception');
        $callback = static fn () => throw $error;

        $this->first->expects(self::once())->method('begin');
        $this->first->expects(self::once())->method('rollback');

        $transactional = new ChainTransactional([$this->first]);

        $handledError = false;

        $transactional->setErrorHandler(static function () use (&$handledError) {
            $handledError = true;
        });

        try {
            $transactional->execute($callback);

            self::fail('should throw error');
        } catch (\TypeError $catchedError) {
            self::assertEquals($error, $catchedError);
            $this->addToAssertionCount(1);
        }

        self::assertTrue($handledError, 'Error not handled in transactional');
    }
}
