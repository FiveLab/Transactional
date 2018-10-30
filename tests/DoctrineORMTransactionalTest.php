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
use FiveLab\Component\Transactional\DoctrineORMTransactional;
use PHPUnit\Framework\TestCase;

/**
 * Doctrine ORM Transactional tests
 *
 * @author Vitaliy Zhuk <v.zhuk@fivelab.org>
 */
class DoctrineORMTransactionalTest extends TestCase
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $em;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
    }

    /**
     * Test begin transaction
     */
    public function testBeginTransaction()
    {
        $this->em->expects(self::once())
            ->method('beginTransaction');

        $transactional = new DoctrineORMTransactional($this->em);
        $transactional->begin();
    }

    /**
     * Test commit transaction
     */
    public function testCommitTransaction()
    {
        $this->em->expects(self::at(0))
            ->method('flush');

        $this->em->expects(self::at(1))
            ->method('commit');

        $transactional = new DoctrineORMTransactional($this->em);
        $transactional->commit();
    }

    /**
     * Test rollback
     */
    public function testRollbackTransaction()
    {
        $this->em->expects(self::once())
            ->method('rollback');

        $transactional = new DoctrineORMTransactional($this->em);
        $transactional->rollback();
    }

    /**
     * Test successfully execute
     */
    public function testExecuteSuccessfully()
    {
        $this->em->expects(self::at(0))
            ->method('beginTransaction');

        $this->em->expects(self::at(1))
            ->method('flush');

        $this->em->expects(self::at(2))
            ->method('commit');

        $transactional = new DoctrineORMTransactional($this->em);
        $result = $transactional->execute(function () {
            return 'some value';
        });

        $this->assertEquals('some value', $result);
    }

    /**
     * Test fail execute
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Some exception
     */
    public function testExecuteFail()
    {
        $this->em->expects(self::at(0))
            ->method('beginTransaction');

        $this->em->expects(self::at(1))
            ->method('rollback');

        $transactional = new DoctrineORMTransactional($this->em);
        $transactional->execute(function () {
            throw new \InvalidArgumentException('Some exception');
        });
    }
}
