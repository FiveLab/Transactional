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

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\Reader;
use FiveLab\Component\Transactional\Annotation\Transactional;

/**
 * Generate proxy classes for transactional layer
 *
 * @author Vitaliy Zhuk <v.zhuk@fivelab.org>
 */
class ProxyCodeGenerator
{
    /**
     * @var \ReflectionClass
     */
    private $reflectionClass;

    /**
     * @var array|\ReflectionMethod[]
     */
    private $reflectionMethods;

    /**
     * @var array|\ReflectionMethod[]
     */
    private $reflectionProxyMethods;

    /**
     * @var Reader
     */
    private $annotationReader;

    /**
     * Construct
     *
     * @param \ReflectionClass $class
     * @param Reader           $annotationReader
     */
    public function __construct(\ReflectionClass $class, Reader $annotationReader = null)
    {
        $this->reflectionClass = $class;

        if (!$annotationReader) {
            $annotationReader = new AnnotationReader();
        }

        $this->annotationReader = $annotationReader;
    }

    /**
     * Need to generate?
     *
     * @return bool
     */
    public function needGenerate(): bool
    {
        return \count($this->getProxyMethods()) > 0;
    }

    /**
     * Generate proxy
     *
     * @return string
     */
    public function generate(): string
    {
        return $this->generateProxyClass();
    }

    /**
     * Get proxy class name
     *
     * @return string
     */
    public function getProxyClassName(): string
    {
        return 'Proxy\__Transactional__\\'.$this->reflectionClass->getName().'Proxy';
    }

    /**
     * Get proxy methods
     *
     * @return array|\ReflectionMethod[]
     */
    private function getProxyMethods(): array
    {
        if (null !== $this->reflectionProxyMethods) {
            return $this->reflectionProxyMethods;
        }

        $proxyMethods = [];
        $methods = $this->getClassMethods();

        foreach ($methods as $method) {
            $docComment = $method->getDocComment();

            if (\strpos($docComment, 'Transactional') !== false) {
                $annotation = $this->annotationReader->getMethodAnnotation(
                    $method,
                    Transactional::class
                );

                if ($annotation) {
                    $proxyMethods[] = $method;
                }
            }
        }

        $this->reflectionProxyMethods = $proxyMethods;

        return $proxyMethods;
    }

    /**
     * Get all methods from class
     *
     * @return \ReflectionMethod[]
     */
    private function getClassMethods(): array
    {
        if (null !== $this->reflectionMethods) {
            return $this->reflectionMethods;
        }

        $this->reflectionMethods = [];

        $class = $this->reflectionClass;

        do {
            $this->reflectionMethods = \array_merge(
                $this->reflectionMethods,
                $class->getMethods()
            );
        } while ($class = $class->getParentClass());

        return $this->reflectionMethods;
    }

    /**
     * Get all use statements from class
     *
     * @param \ReflectionClass $class
     *
     * @return array
     */
    private function getAllUseStatementsMarkedAsAnnotation(\ReflectionClass $class): array
    {
        $tokens = \token_get_all(\file_get_contents($class->getFileName()));

        $uses = [];
        $buffer = '';
        $startUseParse = false;

        foreach ($tokens as $token) {
            if (\is_scalar($token)) {
                // Single element
                if ($token == ';' || $token = ',') {
                    if ($startUseParse) {
                        // Add "use" statement to collection
                        $uses[] = trim($buffer);
                        // Clear buffer
                        $buffer = ''; // Clear buffer

                        if ($token == ';') {
                            // Stop parse "use"
                            $startUseParse = false;
                        }
                    }
                }

                continue;
            }

            $type = $token[0];
            $value = $token[1];

            if (\in_array($type, [T_ABSTRACT, T_CLASS, T_INTERFACE, T_TRAIT])) {
                // Start class or interface or trait
                break;
            }

            if ($type == T_USE) {
                $startUseParse = true;
                continue;
            }

            if ($startUseParse) {
                $buffer .= $value;
            }
        }

        $useFiltering = function ($class, $searchChild = true) use (&$useFiltering) {
            if (!$class) {
                return false;
            }

            $parts = \explode(' as ', $class, 2);
            $class = $parts[0];

            try {
                $reflection = new \ReflectionClass($class);
            } catch (\ReflectionException $e) {
                if ($searchChild) {
                    foreach (\get_declared_classes() as $declaredClass) {
                        if (\strpos($declaredClass, $class) === 0) {
                            if (true === $useFiltering($declaredClass, false)) {
                                return true;
                            }
                        }
                    }
                }

                return false;
            }

            if ($reflection->getName() == Transactional::class) {
                return false;
            }

            return \strpos($reflection->getDocComment(), '@Annotation') !== false;
        };

        $uses = \array_filter($uses, $useFiltering);

        return $uses;
    }

    /**
     * Generate proxy class
     *
     * @return string
     */
    private function generateProxyClass(): string
    {
        $class = $this->reflectionClass;

        if ($class->isFinal()) {
            throw new \RuntimeException(sprintf(
                'Can not generate proxy for final class "%s".',
                $class->getName()
            ));
        }

        if ($class->isAbstract()) {
            throw new \RuntimeException(sprintf(
                'Can not generate proxy for abstract class "%s".',
                $class->getName()
            ));
        }

        $methodCodes = [];

        $uses = $this->getAllUseStatementsMarkedAsAnnotation($class);
        $uses[] = 'FiveLab\Component\Transactional\Proxy\ProxyInterface as FiveLabTransactionalProxyInterface';

        $uses = \array_map(function ($use) {
            return 'use '.$use.';';
        }, $uses);

        $interfaces = [
            'FiveLabTransactionalProxyInterface',
        ];

        foreach ($this->getProxyMethods() as $method) {
            $methodCodes[] = $this->generateProxyMethod($method);
        }

        $docComment = $class->getDocComment();

        $templateVariables = [
            'uses'           => \implode("\n", $uses),
            'namespace'      => $class->getNamespaceName(),
            'docComment'     => $docComment,
            'proxyClassName' => $class->getShortName().'Proxy',
            'className'      => '\\'.$class->getName(),
            'proxyMethods'   => \implode("\n\n", $methodCodes),
            'interfaces'     => \implode(', ', $interfaces),
            'realClassName'  => $class->getName(),
        ];

        $template = $this->getTemplateForProxyClass();
        $code = $this->replaceVariables($template, $templateVariables);

        $lines = \explode("\n", $code);
        $lines = \array_map('rtrim', $lines);

        return \implode("\n", $lines);
    }

    /**
     * Generate proxy method
     *
     * @param \ReflectionMethod $method
     *
     * @return string
     */
    private function generateProxyMethod(\ReflectionMethod $method): string
    {
        if ($method->isConstructor()) {
            throw new \RuntimeException(sprintf(
                'Can not generate proxy method for constructor in class "%s".',
                $method->getDeclaringClass()->getName()
            ));
        }

        if ($method->isStatic()) {
            throw new \RuntimeException(sprintf(
                'Can not generate proxy method for static method "%s" in class "%s".',
                $method->getName(),
                $method->getDeclaringClass()->getName()
            ));
        }

        if ($method->isPrivate()) {
            throw new \RuntimeException(sprintf(
                'Can not generate proxy for private method "%s" in class "%s".',
                $method->getName(),
                $method->getDeclaringClass()->getName()
            ));
        }

        $proxyMethodParameters = [];
        $methodParameters = [];

        foreach ($method->getParameters() as $methodParameter) {
            $methodParameters[] = '$'.$methodParameter->getName();

            if ($methodParameter->getClass()) {
                $proxyMethodParameter = sprintf(
                    '\\%s %s',
                    $methodParameter->getClass()->getName(),
                    '$'.$methodParameter->getName()
                );
            } else if ($methodParameter->getType()) {
                $proxyMethodParameter = \sprintf(
                    '%s%s $%s',
                    $methodParameter->getType()->allowsNull() ? '?' : '',
                    $methodParameter->getType()->getName(),
                    $methodParameter->getName()
                );
            } else {
                $proxyMethodParameter = '$'.$methodParameter->getName();
            }

            if ($methodParameter->isOptional()) {
                $proxyMethodParameter .= ' = '.\var_export($methodParameter->getDefaultValue(), true);
            }

            $proxyMethodParameters[] = $proxyMethodParameter;
        }

        if ($method->isPublic()) {
            $availability = 'public';
        } else if ($method->isProtected()) {
            $availability = 'protected';
        } else {
            throw new \RuntimeException(sprintf(
                'Not support availability for method "%s" in class "%s".',
                $method->getName(),
                $method->getDeclaringClass()->getName()
            ));
        }

        $docComment = "/**\n * {@inheritdoc}\n */";
        $returnTypeHint = '';
        $returnResult = "\n\n    return \$result;";

        if ($method->getReturnType()) {
            $returnTypeHint = sprintf(
                ': %s%s%s',
                \class_exists($method->getReturnType()->getName()) ? '\\' : '',
                $method->getReturnType()->allowsNull() ? '?' : '',
                $method->getReturnType()->getName()
            );
        }

        if ($method->getReturnType() && 'void' === $method->getReturnType()->getName()) {
            $returnResult = '';
        }

        $templateVariables = [
            'docComment'            => $docComment,
            'availability'          => $availability,
            'name'                  => $method->getName(),
            'proxyMethodParameters' => \implode(', ', $proxyMethodParameters),
            'parameters'            => \implode(', ', $methodParameters),
            'beginArguments'        => null,
            'rollbackArguments'     => null,
            'commitArguments'       => null,
            'return_type_hint'      => $returnTypeHint,
            'return_result'         => $returnResult,
        ];

        $methodTemplate = $this->getTemplateForProxyMethod();
        $methodCode = $this->replaceVariables($methodTemplate, $templateVariables);
        $methodCode = $this->appendTabulationCharacter($methodCode, 1);

        return $methodCode;
    }

    /**
     * Get template for proxy class
     *
     * @return string
     */
    private function getTemplateForProxyClass(): string
    {
        return <<<PHP
<?php

/**
 * DO NOT EDIT THIS FILE - IT WAS CREATED BY TRANSACTIONAL PROXY GENERATOR
 */

namespace Proxy\__Transactional__\%namespace%;

%uses%

%docComment%
class %proxyClassName% extends %className% implements %interfaces%
{
    /**
     * @var \FiveLab\Component\Transactional\TransactionalInterface
     */
    private \$___transactional;

    /**
     * Real class name
     *
     * @var string
     */
    private \$___realClassName = '%realClassName%';

    /**
     * Set transactional
     *
     * @param \FiveLab\Component\Transactional\TransactionalInterface \$transactional
     */
    public function ___setTransactional(\FiveLab\Component\Transactional\TransactionalInterface \$transactional): void
    {
        \$this->___transactional = \$transactional;
    }

    /**
     * Get real class name
     *
     * @return string
     */
    public function ___getRealClassName(): string
    {
        return \$this->___realClassName;
    }

%proxyMethods%
}

PHP;

    }

    /**
     * Get template for proxy method
     *
     * @return string
     */
    private function getTemplateForProxyMethod(): string
    {
        return <<<PHP
%docComment%
%availability% function %name%(%proxyMethodParameters%)%return_type_hint%
{
    // Begin transaction
    \$this->___transactional->begin(%beginArguments%);

    try {
        \$result = parent::%name%(%parameters%);
    } catch (\Exception \$e) {
        // Rollback transaction
        \$this->___transactional->rollback(%rollbackArguments%);

        throw \$e;
    }

    // Commit transaction
    \$this->___transactional->commit(%commitArguments%);%return_result%
}
PHP;

    }

    /**
     * Replace variables
     *
     * @param string $template
     * @param array  $variables
     *
     * @return string
     */
    private function replaceVariables($template, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $key = '%'.$key.'%';

            $template = \str_replace($key, $value, $template);
        }

        return $template;
    }

    /**
     * Append tabulation character for lines
     *
     * @param string  $text
     * @param integer $count
     *
     * @return string
     */
    private function appendTabulationCharacter($text, $count): string
    {
        $lines = \explode("\n", $text);

        foreach ($lines as $index => $line) {
            $lines[$index] = \str_repeat("    ", $count).rtrim($line);
        }

        return \implode(PHP_EOL, $lines);
    }
}
