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
        $config = $config ?? di_get(Configuration::class);
        $input = $input ?? $config->getDefaultResource();

        // no positional arguments
        foreach ($input as $key => $value) {
            if (!is_string($key)) {
                unset($input[$key]);
            }
        }

        try {
            if ($config->isMethodsFirst()) {
                $this->processMethods($input, $target, $config);
                $this->processProperties($input, $target, $config);
            } else {
                $this->processProperties($input, $target, $config);
                $this->processMethods($input, $target, $config);
            }
        } catch (InputIsEmptyException) {
        }

        return $target;
    }

    /**
     * @throws InputIsEmptyException | DiException | FactoryException
     */
    protected function processMethods(array &$input, object $target, ConfigurationInterface $config): void
    {
        $callableTransformer = di_get(CallableTransformer::class);

        foreach ($this->getMethods($target, $config) as $method) {
            if (empty($input)) {
                throw new InputIsEmptyException();
            }

            try {
                $argv = $callableTransformer->callableTransform($method);

                foreach ($argv as $key => $value) {
                    unset($input[$config->getMapped($key)]);
                }

                $method->invoke($target, $argv);
            } catch (ReflectionException | NullValueException | NoInputException) {
                // ReflectionException is never thrown since target is an real object
            }
        }
    }

    /**
     * @throws InputIsEmptyException | DiException | FactoryException
     */
    protected function processProperties(array &$input, object $target, ConfigurationInterface $config): void
    {
        $argumentTransformer = di_get(ArgumentTransformer::class);

        foreach ($this->getProperties($target, $config) as $property) {
            if (empty($input)) {
                throw new InputIsEmptyException();
            }

            $name = $config->getMapped($property->getName());

            if (!array_key_exists($name, $input)) {
                continue;
            }

            $property->setAccessible(true);

            if ($config->hasPredefinedValue($name)) {
                $property->setValue($target, $config->getPredefinedValue($name));

                continue;
            }

            if (array_key_exists($name, $input)) {
                $inputValue = $input[$name];
            } elseif ($config->noInputIsAllowed()) {
                $inputValue = null;
            } else {
                continue;
            }

            $value = $argumentTransformer->transformViaArgument($inputValue, $property);

            if (is_null($value) && !$config->nullIsAllowed()) {
                continue;
            }

            $property->setValue($target, $value);
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
            if ($method->getNumberOfParameters() <= $maxParameterCnt) {
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
