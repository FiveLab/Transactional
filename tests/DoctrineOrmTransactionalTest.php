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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Doctrine ORM Transactional tests
 *
 * @author Vitaliy Zhuk <v.zhuk@fivelab.org>
 */
class DoctrineOrmTransactionalTest extends TestCase
{
    /**
     * @var EntityManagerInterface|MockObject
     */
    private EntityManagerInterface $em;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
    }

    /**
     * @test
     */
    public function shouldBeginTransaction(): void
    {
        $this->em->expects(self::once())
            ->method('beginTransaction');

        $transactional = new DoctrineOrmTransactional($this->em);
        $transactional->begin();
    }

    /**
     * @test
     */
    public function shouldCommitTransaction(): void
    {
        $this->em->expects(self::once())
            ->method('flush');

        $this->em->expects(self::once())
            ->method('commit');

        $transactional = new DoctrineOrmTransactional($this->em);
        $transactional->commit();
    }

    /**
     * @test
     */
    public function shouldRollbackTransaction(): void
    {
        $this->em->expects(self::once())
            ->method('rollback');

        $transactional = new DoctrineOrmTransactional($this->em);
        $transactional->rollback();
    }

    /**
     * @test
     */
    public function shouldExecuteSuccessfully(): void
    {
        $this->em->expects(self::once())
            ->method('beginTransaction');

        $this->em->expects(self::once())
            ->method('flush');

        $this->em->expects(self::once())
            ->method('commit');

        $transactional = new DoctrineOrmTransactional($this->em);
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

        $this->em->expects(self::once())
            ->method('beginTransaction');

        $this->em->expects(self::once())
            ->method('rollback');

        $transactional = new DoctrineOrmTransactional($this->em);
        $transactional->execute(static function () {
            throw new \InvalidArgumentException('Some exception');
        });
    }
}
