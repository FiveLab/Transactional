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
    public function setUp()
    {
        $this->first = $this->createMock(TransactionalInterface::class);
        $this->second = $this->createMock(TransactionalInterface::class);
    }

    /**
     * Test begin transaction
     */
    public function testBeginTransaction()
    {
        $key = 'some-key';
        $options = ['opt1', 'opt2'];

        $this->first->expects(self::once())->method('begin')
            ->with($key, $options);

        $this->second->expects(self::once())->method('begin')
            ->with($key, $options);

        $transactional = new ChainTransactional([
            $this->first,
            $this->second
        ]);

        $transactional->begin($key, $options);
    }

    /**
     * Test commit transaction
     */
    public function testCommitTransaction()
    {
        $this->first->expects(self::once())->method('commit')
            ->with('some-key');

        $this->second->expects(self::once())->method('commit')
            ->with('some-key');

        $transactional = new ChainTransactional([
            $this->first,
            $this->second
        ]);

        $transactional->commit('some-key');
    }

    /**
     * Test rollback transaction
     */
    public function testRollbackTransaction()
    {
        $this->first->expects(self::once())->method('rollback')
            ->with('some-key');

        $this->second->expects(self::once())->method('rollback')
            ->with('some-key');

        $transactional = new ChainTransactional([
            $this->first,
            $this->second
        ]);

        $transactional->rollback('some-key');
    }

    /**
     * Test successfully execute
     */
    public function testExecuteSuccessfully()
    {
        $this->first->expects(self::at(0))->method('begin')
            ->with('some-key');

        $this->first->expects(self::at(1))->method('commit')
            ->with('some-key');

        $this->second->expects(self::at(0))->method('begin')
            ->with('some-key');

        $this->second->expects(self::at(1))->method('commit')
            ->with('some-key');

        $transactional = new ChainTransactional([
            $this->first,
            $this->second
        ]);

        $result = $transactional->execute(function () {
            return 'some value';
        }, 'some-key');

        self::assertEquals('some value', $result);
    }

    /**
     * Test fail execute
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Some exception
     */
    public function testExecuteFail()
    {
        $this->first->expects(self::at(0))->method('begin')
            ->with('some-key');

        $this->first->expects(self::at(1))->method('rollback')
            ->with('some-key');

        $this->second->expects(self::at(0))->method('begin')
            ->with('some-key');

        $this->second->expects(self::at(1))->method('rollback')
            ->with('some-key');

        $transactional = new ChainTransactional([
            $this->first,
            $this->second
        ]);

        $transactional->execute(function () {
            throw new \InvalidArgumentException('Some exception');
        }, 'some-key');
    }
}
