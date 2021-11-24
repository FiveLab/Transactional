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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Chain transactional testing
 *
 * @author Vitaliy Zhuk <v.zhuk@fivelab.org>
 */
class ChainTransactionalTest extends TestCase
{
    /**
     * @var TransactionalInterface|MockObject
     */
    private TransactionalInterface $first;

    /**
     * @var TransactionalInterface|MockObject
     */
    private TransactionalInterface $second;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->first = $this->createMock(TransactionalInterface::class);
        $this->second = $this->createMock(TransactionalInterface::class);
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
    public function shouldRollBackAllLayersIfCommitFails(): void
    {
        $this->second
            ->expects(self::exactly(1))
            ->method('commit')
            ->willThrowException(new \RuntimeException('some exception'));

        $this->second->expects(self::exactly(0))->method('rollback');

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

    /**
     * @test
     */
    public function shouldRollbackAllLayersIfOneRollbackFails(): void
    {
        $this->first
            ->expects(self::exactly(1))
            ->method('rollback')
            ->willThrowException(new \RuntimeException('some exception'));

        $this->second->expects(self::exactly(1))->method('rollback');

        $transactional = new ChainTransactional([
            $this->first,
            $this->second,
        ]);

        $transactional->rollback();
    }
}
