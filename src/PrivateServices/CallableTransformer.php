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

use Closure;
use eArc\ParameterTransformer\Exceptions\DiException;
use eArc\ParameterTransformer\Exceptions\FactoryException;
use eArc\ParameterTransformer\Exceptions\NoInputException;
use eArc\ParameterTransformer\Exceptions\NullValueException;
use eArc\ParameterTransformer\Interfaces\ConfigurationInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;

class CallableTransformer
{
    /**
     * @throws ReflectionException | NullValueException | NoInputException | DiException | FactoryException
     */
    public function callableTransform(string|callable|ReflectionFunctionAbstract $target, array $input = null, ConfigurationInterface $config = null): array
    {
        $inputProvider = new InputProvider($input, $config);
        $argumentTransformer = di_get(ArgumentTransformer::class);

        $argv = [];

        foreach ($this->getCallableArguments($target) as $argument) {
            $value = $argumentTransformer->transform($argument, $inputProvider);

            $argv[$argument->getName()] = $value;
        }

        return $argv;
    }

    /**
     * @return ReflectionProperty[]|ReflectionParameter[]
     *
     * @throws ReflectionException
     *
     * @noinspection PhpDocSignatureInspection
     */
    protected function getCallableArguments(string|callable|ReflectionFunctionAbstract $target): array
    {
        if ($target instanceof ReflectionFunctionAbstract) {
            return $target->getParameters();
        }

        return is_callable($target)
            ? $this->getCallableReflection($target)->getParameters()
            : (new ReflectionClass($target))->getConstructor()->getParameters();
    }

    /**
     * @throws ReflectionException
     */
    protected function getCallableReflection(callable $callable): ReflectionFunctionAbstract
    {
        if ($callable instanceof Closure) {
            return new ReflectionFunction($callable);
        }

        if (is_string($callable)) {
            $parts = explode('::', $callable);
            return count($parts) > 1 ? new ReflectionMethod($parts[0], $parts[1]) : new ReflectionFunction($callable);
        }

        if (!is_array($callable)) {
            return new ReflectionMethod($callable, '__invoke');
        }

        return new ReflectionMethod($callable[0], $callable[1]);
    }
}
