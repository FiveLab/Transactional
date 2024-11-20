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

use Doctrine\DBAL\Driver\Connection;

class DoctrineDbalSavepointTransactional extends AbstractTransactional
{
    /**
     * @var array<string>
     */
    private static $keys = [];

    public function __construct(private readonly Connection $connection)
    {
    }

    public function begin(): void
    {
        $savepointKey = 'savepoint_'.\count(self::$keys);

        self::$keys[] = $savepointKey;

        $this->connection->exec('SAVEPOINT '.$savepointKey);
    }

    public function commit(): void
    {
        $savepointKey = \array_pop(self::$keys);

        $this->connection->exec('RELEASE SAVEPOINT '.$savepointKey);
    }

    public function rollback(): void
    {
        $savepointKey = \array_pop(self::$keys);

        $this->connection->exec('ROLLBACK TO SAVEPOINT '.$savepointKey);
    }
}
