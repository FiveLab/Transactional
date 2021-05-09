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
    private Connection $connection;

    /**
     * @var DoctrineDbalSavepointTransactional
     */
    private DoctrineDbalSavepointTransactional $transactional;

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
     * @test
     */
    public function shouldBeginTransaction(): void
    {
        $this->connection->expects(self::once())
            ->method('exec')
            ->with('SAVEPOINT savepoint_0');

        $this->transactional->begin();
    }

    /**
     * @test
     */
    public function shouldCommitTransaction(): void
    {
        $this->connection->expects(self::exactly(2))
            ->method('exec')
            ->withConsecutive(
                ['SAVEPOINT savepoint_0'],
                ['RELEASE SAVEPOINT savepoint_0']
            );

        $this->transactional->begin();
        $this->transactional->commit();
    }

    /**
     * @test
     */
    public function shouldRollbackTransaction(): void
    {
        $this->connection->expects(self::exactly(2))
            ->method('exec')
            ->withConsecutive(
                ['SAVEPOINT savepoint_0'],
                ['ROLLBACK TO SAVEPOINT savepoint_0']
            );

        $this->transactional->begin();
        $this->transactional->rollback();
    }

    /**
     * @test
     */
    public function shouldExecuteSuccessfully(): void
    {
        $this->connection->expects(self::exactly(2))
            ->method('exec')
            ->withConsecutive(
                ['SAVEPOINT savepoint_0'],
                ['RELEASE SAVEPOINT savepoint_0']
            );

        $result = $this->transactional->execute(static function () {
            return 'some value';
        });

        self::assertEquals('some value', $result);
    }

    /**
     * @test
     */
    public function shouldExecuteFail(): void
    {
        $this->connection->expects(self::exactly(2))
            ->method('exec')
            ->withConsecutive(
                ['SAVEPOINT savepoint_0'],
                ['ROLLBACK TO SAVEPOINT savepoint_0']
            );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Some exception');

        $this->transactional->execute(static function () {
            throw new \InvalidArgumentException('Some exception');
        });
    }
}
