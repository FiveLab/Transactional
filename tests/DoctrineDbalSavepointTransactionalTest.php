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
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\equalTo;

class DoctrineDbalSavepointTransactionalTest extends TestCase
{
    private Connection $connection;
    private DoctrineDbalSavepointTransactional $transactional;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->transactional = new DoctrineDbalSavepointTransactional($this->connection);
    }

    protected function tearDown(): void
    {
        // Clear $keys (static)
        $ref = new \ReflectionProperty(DoctrineDbalSavepointTransactional::class, 'keys');
        $ref->setValue(null, []);
    }

    #[Test]
    public function shouldBeginTransaction(): void
    {
        $this->connection->expects(self::once())
            ->method('exec')
            ->with('SAVEPOINT savepoint_0');

        $this->transactional->begin();
    }

    #[Test]
    public function shouldCommitTransaction(): void
    {
        $this->connection->expects(self::exactly(2))
            ->method('exec')
            ->with(self::logicalOr(
                self::equalTo('SAVEPOINT savepoint_0'),
                self::equalTo('RELEASE SAVEPOINT savepoint_0')
            ));

        $this->transactional->begin();
        $this->transactional->commit();
    }

    #[Test]
    public function shouldRollbackTransaction(): void
    {
        $this->connection->expects(self::exactly(2))
            ->method('exec')
            ->with(self::logicalOr(
                self::equalTo('SAVEPOINT savepoint_0'),
                self::equalTo('ROLLBACK TO SAVEPOINT savepoint_0')
            ));

        $this->transactional->begin();
        $this->transactional->rollback();
    }

    #[Test]
    public function shouldExecuteSuccessfully(): void
    {
        $this->connection->expects(self::exactly(2))
            ->method('exec')
            ->with(self::logicalOr(
                self::equalTo('SAVEPOINT savepoint_0'),
                self::equalTo('RELEASE SAVEPOINT savepoint_0')
            ));

        $result = $this->transactional->execute(static fn() => 'some value');

        self::assertEquals('some value', $result);
    }

    #[Test]
    public function shouldExecuteFail(): void
    {
        $this->connection->expects(self::exactly(2))
            ->method('exec')
            ->with(self::logicalOr(
                self::equalTo('SAVEPOINT savepoint_0'),
                self::equalTo('ROLLBACK TO SAVEPOINT savepoint_0')
            ));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Some exception');

        $this->transactional->execute(static fn() => throw new \InvalidArgumentException('Some exception'));
    }
}
