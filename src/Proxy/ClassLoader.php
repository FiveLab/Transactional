<?php

/*
 * This file is part of the FiveLab Transactional package.
 *
 * (c) FiveLab <mail@fivelab.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FiveLab\Component\Transactional\Proxy;

/**
 * Class loader for load transactional proxy classes
 *
 * @author Vitaliy Zhuk <v.zhuk@fivelab.org>
 */
class ClassLoader
{
    /**
     * @var string
     */
    private $directory;

    /**
     * Construct
     *
     * @param string $directory
     */
    public function __construct($directory)
    {
        $this->directory = $directory;
    }

    /**
     * Register loader
     *
     * @param bool $prepend
     */
    public function register($prepend)
    {
        spl_autoload_register([$this, 'loadClass'], true, $prepend);
    }

    /**
     * Unregister loader
     */
    public function unregister()
    {
        spl_autoload_unregister([$this, 'loadClass']);
    }

    /**
     * Load class
     *
     * @param string $class
     *
     * @return bool|null
     */
    public function loadClass($class)
    {
        if (strpos($class, 'Proxy\__Transactional__\\') !== 0) {
            return null;
        }

        if ($file = $this->findProxyFile($class)) {
            require_once $file;

            return true;
        }

        return null;
    }

    /**
     * Find proxy file
     *
     * @param string $class
     *
     * @return string The file path
     */
    protected function findProxyFile($class)
    {
        $class = substr($class, 24);

        $filePath = $this->directory . '/Proxy/' . str_replace('\\', '/', $class) . '.php';

        if (file_exists($filePath) && is_file($filePath)) {
            return $filePath;
        }

        return null;
    }
}
