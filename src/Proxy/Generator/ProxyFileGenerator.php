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

namespace FiveLab\Component\Transactional\Proxy\Generator;

/**
 * Proxy file generator
 *
 * @author Vitaliy Zhuk <v.zhuk@fivelab.org>
 */
class ProxyFileGenerator
{
    /**
     * @var ProxyCodeGenerator
     */
    private $codeGenerator;

    /**
     * @var \ReflectionClass
     */
    private $reflectionClass;

    /**
     * @var string
     */
    private $directory;

    /**
     * Construct
     *
     * @param string $directory
     * @param string $class
     */
    public function __construct(string $directory, string $class)
    {
        $this->reflectionClass = new \ReflectionClass($class);
        $this->codeGenerator = new ProxyCodeGenerator($this->reflectionClass);
        $this->directory = $directory;
    }

    /**
     * Need generate?
     *
     * @return bool
     */
    public function needGenerate(): bool
    {
        return $this->codeGenerator->needGenerate();
    }

    /**
     * Generate proxy file
     *
     * @return string
     */
    public function generate(): string
    {
        $namespace = $this->reflectionClass->getNamespaceName();
        $fileDirectory = \rtrim($this->directory, '/') . '/Proxy/' . \str_replace('\\', '/', $namespace);
        $fileName = $this->reflectionClass->getShortName() . 'Proxy.php';
        $filePath = $fileDirectory . '/' . $fileName;

        if (!\is_dir($fileDirectory)) {
            // Try create directory
            if (false === @\mkdir($fileDirectory, 0777, true)) {
                throw new \RuntimeException(sprintf(
                    'Could not create directory "%s" for proxy file. Maybe not rights?',
                    $fileDirectory
                ));
            }
        } else if (!\is_writable($fileDirectory)) {
            throw new \RuntimeException(sprintf(
                'Cannot write to "%s" directory for save proxy file.',
                $fileDirectory
            ));
        }

        // Create file
        if (false === @\touch($filePath)) {
            throw new \RuntimeException(sprintf(
                'Could not create file "%s" for proxy class. Maybe not rights?',
                $filePath
            ));
        }

        \file_put_contents($filePath, $this->codeGenerator->generate());

        return $filePath;
    }

    /**
     * Get proxy file name
     *
     * @return string
     */
    public function getProxyClassName(): string
    {
        return $this->codeGenerator->getProxyClassName();
    }
}
