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

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class TransactionalTest extends TestCase
{
    #[Test]
    public function shouldSuccessHandleError(): void
    {
        $handledError = null;
        $throwError = new \RuntimeException('bla bla');

        $transactional = new TestTransactional();

        $transactional->setErrorHandler(static function (\Throwable $error) use (&$handledError): void {
            $handledError = $error;
        });

        try {
            $transactional->execute(static function () use ($throwError): void {
                throw $throwError;
            });
        } catch (\RuntimeException $error) {
            // Normal flow.
            self::assertEquals($throwError, $error);
        }

        $calls = $transactional->getCalls();

        self::assertEquals([
            ['begin', []],
            ['rollback', []],
            ['handle error', [$handledError, $transactional]],
        ], $calls);
    }
}
