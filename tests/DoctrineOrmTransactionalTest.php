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
    private $em;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
    }

    /**
     * Test begin transaction
     */
    public function testBeginTransaction(): void
    {
        $this->em->expects(self::once())
            ->method('beginTransaction');

        $transactional = new DoctrineOrmTransactional($this->em);
        $transactional->begin();
    }

    /**
     * Test commit transaction
     */
    public function testCommitTransaction(): void
    {
        $this->em->expects(self::at(0))
            ->method('flush');

        $this->em->expects(self::at(1))
            ->method('commit');

        $transactional = new DoctrineOrmTransactional($this->em);
        $transactional->commit();
    }

    /**
     * Test rollback
     */
    public function testRollbackTransaction(): void
    {
        $this->em->expects(self::once())
            ->method('rollback');

        $transactional = new DoctrineOrmTransactional($this->em);
        $transactional->rollback();
    }

    /**
     * Test successfully execute
     */
    public function testExecuteSuccessfully(): void
    {
        $this->em->expects(self::at(0))
            ->method('beginTransaction');

        $this->em->expects(self::at(1))
            ->method('flush');

        $this->em->expects(self::at(2))
            ->method('commit');

        $transactional = new DoctrineOrmTransactional($this->em);
        $result = $transactional->execute(static function () {
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

        $this->em->expects(self::at(0))
            ->method('beginTransaction');

        $this->em->expects(self::at(1))
            ->method('rollback');

        $transactional = new DoctrineOrmTransactional($this->em);
        $transactional->execute(static function () {
            throw new \InvalidArgumentException('Some exception');
        });
    }
}
