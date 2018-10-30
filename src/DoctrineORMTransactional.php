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

use Doctrine\ORM\EntityManagerInterface;

/**
 * Doctrine ORM Transactional layer
 *
 * @author Vitaliy Zhuk <v.zhuk@fivelab.org>
 */
class DoctrineORMTransactional extends AbstractTransactional
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritDoc}
     */
    public function begin($key = null, array $options = []): void
    {
        $this->entityManager->beginTransaction();
    }

    /**
     * {@inheritDoc}
     */
    public function commit($key = null): void
    {
        $this->entityManager->flush();
        $this->entityManager->commit();
    }

    /**
     * {@inheritDoc}
     */
    public function rollback($key = null): void
    {
        $this->entityManager->rollback();
    }
}
