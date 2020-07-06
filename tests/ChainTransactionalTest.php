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
use PHPUnit\Framework\TestCase;

/**
 * Chain transactional testing
 *
 * @author Vitaliy Zhuk <v.zhuk@fivelab.org>
 */
class ChainTransactionalTest extends TestCase
{
    /**
     * @var TransactionalInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $first;

    /**
     * @var TransactionalInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $second;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->first = $this->createMock(TransactionalInterface::class);
        $this->second = $this->createMock(TransactionalInterface::class);
    }

    /**
     * Test begin transaction
     */
    public function testBeginTransaction(): void
    {
        $this->first->expects(self::once())->method('begin');

        $this->second->expects(self::once())->method('begin');

        $transactional = new ChainTransactional([
            $this->first,
            $this->second
        ]);

        $transactional->begin();
    }

    /**
     * Test commit transaction
     */
    public function testCommitTransaction(): void
    {
        $this->first->expects(self::once())->method('commit');

        $this->second->expects(self::once())->method('commit');

        $transactional = new ChainTransactional([
            $this->first,
            $this->second
        ]);

        $transactional->commit();
    }

    /**
     * Test rollback transaction
     */
    public function testRollbackTransaction(): void
    {
        $this->first->expects(self::once())->method('rollback');

        $this->second->expects(self::once())->method('rollback');

        $transactional = new ChainTransactional([
            $this->first,
            $this->second
        ]);

        $transactional->rollback();
    }

    /**
     * Test successfully execute
     */
    public function testExecuteSuccessfully(): void
    {
        $this->first->expects(self::at(0))->method('begin');

        $this->first->expects(self::at(1))->method('commit');

        $this->second->expects(self::at(0))->method('begin');

        $this->second->expects(self::at(1))->method('commit');

        $transactional = new ChainTransactional([
            $this->first,
            $this->second
        ]);

        $result = $transactional->execute(static function () {
            return 'some value';
        });

        self::assertEquals('some value', $result);
    }

    /**
     * Test fail execute
     */
    public function testExecuteFail(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Some exception');

        $this->first->expects(self::at(0))->method('begin');

        $this->first->expects(self::at(1))->method('rollback');

        $this->second->expects(self::at(0))->method('begin');

        $this->second->expects(self::at(1))->method('rollback');

        $transactional = new ChainTransactional([
            $this->first,
            $this->second
        ]);

        $transactional->execute(static function () {
            throw new \InvalidArgumentException('Some exception');
        });
    }
}
