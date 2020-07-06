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

use Doctrine\DBAL\Driver\Connection;
use FiveLab\Component\Transactional\DoctrineDbalSavepointTransactional;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Doctrine ORM Transactional tests
 *
 * @author Vitaliy Zhuk <v.zhuk@fivelab.org>
 */
class DoctrineDbalSavepointTransactionalTest extends TestCase
{
    /**
     * @var Connection|MockObject
     */
    private $connection;

    /**
     * @var DoctrineDbalSavepointTransactional
     */
    private $transactional;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->transactional = new DoctrineDbalSavepointTransactional($this->connection);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        // Clear $keys (static)
        $ref = new \ReflectionProperty(DoctrineDbalSavepointTransactional::class, 'keys');
        $ref->setAccessible(true);
        $ref->setValue(null, []);
    }

    /**
     * Test begin transaction
     */
    public function testBeginTransaction(): void
    {
        $this->connection->expects(self::once())
            ->method('exec')
            ->with('SAVEPOINT savepoint_0');

        $this->transactional->begin();
    }

    /**
     * Test commit transaction
     */
    public function testCommitTransaction(): void
    {
        $this->connection->expects(self::at(1))
            ->method('exec')
            ->with('RELEASE SAVEPOINT savepoint_0');

        $this->transactional->begin();
        $this->transactional->commit();
    }

    /**
     * Test rollback
     */
    public function testRollbackTransaction(): void
    {
        $this->connection->expects(self::at(1))
            ->method('exec')
            ->with('ROLLBACK TO SAVEPOINT savepoint_0');

        $this->transactional->begin();
        $this->transactional->rollback();
    }

    /**
     * Test successfully execute
     */
    public function testExecuteSuccessfully(): void
    {
        $this->connection->expects(self::at(0))
            ->method('exec')
            ->with('SAVEPOINT savepoint_0');

        $this->connection->expects(self::at(1))
            ->method('exec')
            ->with('RELEASE SAVEPOINT savepoint_0');

        $result = $this->transactional->execute(static function () {
            return 'some value';
        });

        $this->assertEquals('some value', $result);
    }

    /**
     * Test fail execute
     */
    public function testExecuteFail(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Some exception');

        $this->connection->expects(self::at(0))
            ->method('exec')
            ->with('SAVEPOINT savepoint_0');

        $this->connection->expects(self::at(1))
            ->method('exec')
            ->with('ROLLBACK TO SAVEPOINT savepoint_0');

        $this->transactional->execute(static function () {
            throw new \InvalidArgumentException('Some exception');
        });
    }
}
