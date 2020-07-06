<?php

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
        if (!\is_callable($callback)) {
            throw new \InvalidArgumentException(sprintf(
                'The callback must be a callable, but "%s" given.',
                is_scalar($callback) ? $callback : gettype($callback)
            ));
        }

        $this->begin();

        try {
            $result = \call_user_func($callback);
            $this->commit();

        } catch (\Exception $e) {
            $this->rollback();

            throw $e;
        }

        return $result;
    }
}
