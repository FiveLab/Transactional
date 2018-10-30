<?php

/*
 * This file is part of the FiveLab Transactional package.
 *
 * (c) FiveLab <mail@fivelab.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FiveLab\Component\Transactional\Annotation;

/**
 * Indicate for run method in transaction layer
 *
 * @Annotation()
 * @Target({"METHOD"})
 *
 * @author Vitaliy Zhuk <v.zhuk@fivelab.org>
 */
class Transactional
{
    /** @var string */
    public $key;
}
