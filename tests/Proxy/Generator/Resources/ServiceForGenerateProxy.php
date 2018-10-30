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

namespace FiveLab\Component\Transactional\Tests\Proxy\Generator\Resources;

use FiveLab\Component\Transactional\Annotation\Transactional;

/**
 * @author Vitaliy Zhuk <v.zhuk@fivelab.org>
 */
class ServiceForGenerateProxy
{
    /**
     * @Transactional()
     */
    public function simpleMethod()
    {
    }

    /**
     * @Transactional()
     *
     * @param int       $int
     * @param string    $string
     * @param float     $float
     * @param array     $array
     * @param \stdClass $object
     */
    public function simpleMethodWithArguments(int $int, string $string, float $float, array $array, \stdClass $object)
    {
    }

    /**
     * @Transactional()
     *
     * @param ServiceForGenerateProxy $inner
     */
    public function methodWithCustomInputType(ServiceForGenerateProxy $inner)
    {
    }

    /**
     * @Transactional()
     */
    public function methodWithVoidReturnType(): void
    {
    }

    /**
     * @Transactional()
     */
    public function methodWithIntReturnType(): int
    {
        return 0;
    }

    /**
     * @Transactional()
     *
     * @return ServiceForGenerateProxy
     */
    public function methodWithCustomReturnType(): ServiceForGenerateProxy
    {
        return new ServiceForGenerateProxy();
    }
}
