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

namespace FiveLab\Component\Transactional\Tests\Proxy\Generator;


use FiveLab\Component\Transactional\Proxy\Generator\ProxyCodeGenerator;
use FiveLab\Component\Transactional\Tests\Proxy\Generator\Resources\ServiceForGenerateProxy;
use PHPUnit\Framework\TestCase;

/**
 * Proxy generator test
 *
 * @author Vitaliy Zhuk <v.zhuk@fivelab.org>
 */
class ProxyCodeGeneratorTest extends TestCase
{
    public function testSuccessGenerate(): void
    {
        $generator = new ProxyCodeGenerator(new \ReflectionClass(ServiceForGenerateProxy::class));

        self::assertTrue($generator->needGenerate());

        $code = $generator->generate();

        self::assertEquals(\file_get_contents(__DIR__.'/Resources/ExpectedServiceForGenerateProxy.php'), $code);

        include __DIR__.'/Resources/ExpectedServiceForGenerateProxy.php';
    }
}
