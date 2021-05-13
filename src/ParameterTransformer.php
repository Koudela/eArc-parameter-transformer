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

namespace eArc\ParameterTransformer;

use eArc\ParameterTransformer\Exceptions\DiException;
use eArc\ParameterTransformer\Exceptions\FactoryException;
use eArc\ParameterTransformer\Exceptions\NoInputException;
use eArc\ParameterTransformer\Exceptions\NullValueException;
use eArc\ParameterTransformer\Interfaces\ConfigurationInterface;
use eArc\ParameterTransformer\PrivateServices\CallableTransformer;
use eArc\ParameterTransformer\PrivateServices\ObjectTransformer;
use ReflectionException;
use ReflectionFunctionAbstract;

class ParameterTransformer
{
    /**
     * @throws ReflectionException | NullValueException | NoInputException | DiException | FactoryException
     */
    public function castFromTransform(string $target, array $input = null, Configuration $config = null): object
    {
        $target = $this->constructFromTransform($target, $input, $config);

        return $this->objectTransform($target, $input, $config);
    }

    /**
     * @throws ReflectionException | NullValueException | NoInputException | DiException | FactoryException
     */
    public function constructFromTransform(string $target, array $input = null, Configuration $config = null): object
    {
        return new $target(...di_get(CallableTransformer::class)->callableTransform($target, $input, $config));
    }

    /**
     * @throws DiException | FactoryException | NoInputException | NullValueException
     */
    public function objectTransform(object $target, array $input = null, ConfigurationInterface $config = null): object
    {
        return di_get(ObjectTransformer::class)->objectTransform($target, $input, $config);
    }

    /**
     * @throws ReflectionException | NullValueException | NoInputException | DiException | FactoryException
     */
    public function callFromTransform(callable $target, array $input = null, ConfigurationInterface $config = null): mixed
    {
        return call_user_func_array($target, $this->callableTransform($target, $input, $config));
    }

    /**
     * @throws ReflectionException | NullValueException | NoInputException | DiException | FactoryException
     */
    public function callableTransform(string|callable|ReflectionFunctionAbstract $target, array $input = null, ConfigurationInterface $config = null): array
    {
        return di_get(CallableTransformer::class)->callableTransform($target, $input, $config);
    }
}
