<?php declare(strict_types=1);
/**
 * e-Arc Framework - the explicit Architecture Framework
 * router component
 *
 * @package earc/parameter-transformer
 * @link https://github.com/Koudela/eArc-parameter-transformer/
 * @copyright Copyright (c) 2018-2021 Thomas Koudela
 * @license http://opensource.org/licenses/MIT MIT License
 */

namespace eArc\ParameterTransformer\PrivateServices;

use eArc\ParameterTransformer\Configuration;
use eArc\ParameterTransformer\Exceptions\DiException;
use eArc\ParameterTransformer\Exceptions\FactoryException;
use eArc\ParameterTransformer\Exceptions\InputIsEmptyException;
use eArc\ParameterTransformer\Exceptions\NoInputException;
use eArc\ParameterTransformer\Exceptions\NullValueException;
use eArc\ParameterTransformer\Interfaces\ConfigurationInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;

class ObjectTransformer
{

    /**
     * @throws DiException | FactoryException
     */
    public function objectTransform(object $target, array $input = null, ConfigurationInterface $config = null): object
    {
        $inputProvider = new InputProvider($input, $config);
        $config = $inputProvider->getConfig();

        try {
            if ($config->isMethodsFirst()) {
                $this->processMethods($target, $inputProvider);
                if ($config->usePropertyTransformation()) {
                    $this->processProperties($target, $inputProvider);
                }
            } else {
                if ($config->usePropertyTransformation()) {
                    $this->processProperties($target, $inputProvider);
                }
                $this->processMethods($target, $inputProvider);
            }
        } catch (InputIsEmptyException) {
        }

        return $target;
    }

    /**
     * @throws InputIsEmptyException | DiException | FactoryException
     */
    protected function processMethods(object $target, InputProvider $inputProvider): void
    {
        $config = $inputProvider->getConfig();
        $callableTransformer = di_get(CallableTransformer::class);

        foreach ($this->getMethods($target, $config) as $method) {
            if ($inputProvider->inputIsEmpty()) {
                throw new InputIsEmptyException();
            }

            try {
                $inputProvider->initBackup();
                $argv = $callableTransformer->callableTransformWithProvider($method, $inputProvider);
                $method->setAccessible(true);
                $method->invokeArgs($target, $argv);
            } catch (ReflectionException | NullValueException | NoInputException) {
                // ReflectionException is never thrown since target is an real object
                $inputProvider->restoreBackup();
            }
        }
    }

    /**
     * @throws DiException | FactoryException | InputIsEmptyException
     */
    protected function processProperties(object $target, InputProvider $inputProvider): void
    {
        $config = $inputProvider->getConfig();
        $argumentTransformer = di_get(ArgumentTransformer::class);

        foreach ($this->getProperties($target, $config) as $property) {
            if ($inputProvider->inputIsEmpty()) {
                throw new InputIsEmptyException();
            }

            try {
                $inputProvider->initBackup();
                $value = $argumentTransformer->transform($property, $inputProvider);

                $property->setAccessible(true);
                $property->setValue($target, $value);
            } catch (NullValueException | NoInputException) {
                $inputProvider->restoreBackup();
            }

        }
    }

    /**
     * @return ReflectionMethod[]
     */
    protected function getMethods(object $target, Configuration $config): array
    {
        $methods = [];

        $reflectionMethods = (new ReflectionClass($target))->getMethods($config->getFilterMethods());
        $maxParameterCnt = $config->getMaxParameterCount();

        foreach ($reflectionMethods as $method) {
            if ($method->getNumberOfParameters() <= $maxParameterCnt && $method->getNumberOfParameters() > 0) {
                $methods[] = $method;
            }
        }

        if ($maxParameterCnt > 1) {
            usort($methods, function (ReflectionMethod $a, ReflectionMethod $b) {
                return $a->getNumberOfParameters() <=> $b->getNumberOfParameters();
            });
        }

        return $methods;
    }

    /**
     * @return ReflectionProperty[]
     */
    protected function getProperties(object $target, Configuration $config): array
    {
        return (new ReflectionClass($target))->getProperties($config->getFilterProperties());
    }
}
