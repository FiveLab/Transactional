<?php

/*
 * This file is part of the FiveLab Transactional package.
 *
 * (c) FiveLab <mail@fivelab.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FiveLab\Component\Transactional;

/**
 * AMQP Transactional tests
 *
 * @author Vitaliy Zhuk <v.zhuk@fivelab.org>
 */
class AmqpTransactionalTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \AMQPChannel|\PHPUnit_Framework_MockObject_MockObject
     */
    private $channel;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        if (!class_exists('AMQPChannel')) {
            $this->markTestSkipped('The AMQP not installed.');
        }

        $this->channel = $this->getMock('AMQPChannel', [], [], '', false);
    }

    /**
     * Test begin transaction
     */
    public function testBeginTransaction()
    {
        $this->channel->expects($this->once())->method('startTransaction');

        $transactional = new AmqpTransactional($this->channel);
        $transactional->begin();
    }

    /**
     * Test commit transaction
     */
    public function testCommitTransaction()
    {
        $this->channel->expects($this->once())->method('commitTransaction');

        $transactional = new AmqpTransactional($this->channel);
        $transactional->commit();
    }

    /**
     * Test rollback
     */
    public function testRollbackTransaction()
    {
        $this->channel->expects($this->once())->method('rollbackTransaction');

        $transactional = new AmqpTransactional($this->channel);
        $transactional->rollback();
    }

    /**
     * Test successfully execute
     */
    public function testExecuteSuccessfully()
    {
        $this->channel->expects($this->at(0))->method('startTransaction');
        $this->channel->expects($this->at(1))->method('commitTransaction');

        $transactional = new AmqpTransactional($this->channel);
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
        $this->channel->expects($this->at(0))->method('startTransaction');
        $this->channel->expects($this->at(1))->method('rollbackTransaction');

        $transactional = new AmqpTransactional($this->channel);
        $transactional->execute(function () {
            throw new \InvalidArgumentException('Some exception');
        });
    }
}
