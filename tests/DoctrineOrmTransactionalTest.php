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

use Doctrine\ORM\EntityManagerInterface;
use FiveLab\Component\Transactional\DoctrineOrmTransactional;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DoctrineOrmTransactionalTest extends TestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
    }

    #[Test]
    public function shouldBeginTransaction(): void
    {
        $this->em->expects(self::once())
            ->method('beginTransaction');

        $transactional = new DoctrineOrmTransactional($this->em);
        $transactional->begin();
    }

    #[Test]
    public function shouldCommitTransaction(): void
    {
        $this->em->expects(self::once())
            ->method('flush');

        $this->em->expects(self::once())
            ->method('commit');

        $transactional = new DoctrineOrmTransactional($this->em);
        $transactional->commit();
    }

    #[Test]
    public function shouldRollbackTransaction(): void
    {
        $this->em->expects(self::once())
            ->method('rollback');

        $transactional = new DoctrineOrmTransactional($this->em);
        $transactional->rollback();
    }

    #[Test]
    public function shouldExecuteSuccessfully(): void
    {
        $this->em->expects(self::once())
            ->method('beginTransaction');

        $this->em->expects(self::once())
            ->method('flush');

        $this->em->expects(self::once())
            ->method('commit');

        $transactional = new DoctrineOrmTransactional($this->em);

        $result = $transactional->execute(static fn() => 'some value');

        self::assertEquals('some value', $result);
    }

    #[Test]
    public function shouldExecuteFail(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Some exception');

        $this->em->expects(self::once())
            ->method('beginTransaction');

        $this->em->expects(self::once())
            ->method('rollback');

        $transactional = new DoctrineOrmTransactional($this->em);

        $transactional->execute(static fn() => throw new \InvalidArgumentException('Some exception'));
    }
}
