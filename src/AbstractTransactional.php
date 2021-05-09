<?php

declare(strict_types = 1);

/*
 * This file is part of the FiveLab Transactional package
 *
 * (c) FiveLab
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code
 */

namespace FiveLab\Component\Transactional;

/**
 * Abstract transactional
 *
 * @author Vitaliy Zhuk <v.zhuk@fivelab.org>
 */
abstract class AbstractTransactional implements TransactionalInterface
{
    /**
     * {@inheritdoc}
     */
    public function execute(\Closure $callback)
    {
        $this->begin();

        try {
            $result = $callback();
            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();

            throw $e;
        }

        return $result;
    }
}
