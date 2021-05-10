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

use Closure;
use eArc\Cast\CastService;
use Exception;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionType;
use ReflectionUnionType;

class ParameterTransformer
{
    protected CastService $castService;

    public function __construct()
    {
        $this->castService = new CastService();
    }

    /**
     * @throws ReflectionException
     */
    public function callFromTransform(callable $target, array $input = null, array|null $mapping = null): mixed
    {
        return call_user_func_array($target, $this->transform($target, $input, $mapping));
    }

    /**
     * @throws ReflectionException
     */
    public function castFromTransform(string|object $target, array $input = null, array|null $mapping = null): object
    {
        return $this->castService->castSimple($this->transform($target, $input, $mapping), $target);
    }

    /**
     * @throws ReflectionException
     */
    public function constructFromTransform(string $target, array $input = null, array|null $mapping = null): object
    {
        return new $target(...$this->transform($target, $input, $mapping));
    }

    /**
     * @throws ReflectionException
     */
    public function transform(string|object|callable $target, array $input = null, array|null $mapping = null): array
    {
        $input = $input ?? $this->getInput();

        $argv = [];
        $pos = 0;

        foreach ($this->getArguments($target) as $argument) {
            $name = $argument->getName();

            if (!is_null($mapping)) {
                if (array_key_exists($name, $mapping)) {
                    $name = $mapping[$name];
                } else if (array_key_exists($pos, $mapping)) {
                    $name = $mapping[$pos];
                }
            }

            $value = null;

            if (array_key_exists($name, $input)) {
                $value = $input[$name];
            } else if (array_key_exists($pos, $input)) {
                $value = $input[$pos];
                $pos++;
            }

            $argv[$name] = $this->transformViaArgument($value, $argument);
        }

        return $argv;
    }

    protected function getInput(): array
    {
        parse_str(file_get_contents('php://input'), $values);

        return $values;
    }

    protected function transformViaArgument($value, ReflectionParameter|ReflectionProperty $argument)
    {
        $singleType = $argument->getType();

        $types = $singleType instanceof ReflectionUnionType ? $singleType->getTypes(): [$singleType];

        foreach ($types as $type) {
            $newValue = $this->transformViaType($value, $type, $argument);

            if (!is_null($newValue) && $newValue !== $value) {
                break;
            }
        }

        if (isset($newValue)) {
            $value = $newValue;
        }

        if (is_null($value)) {
            try {
                return $argument->getDefaultValue();
            } catch (ReflectionException $exception) {
                unset($exception);

                return null;
            }
        }

        return $value;
    }

    protected function transformViaType(mixed $value, ReflectionType $type, ReflectionParameter|ReflectionProperty $argument): mixed
    {
        if ('null' === $value && $type->allowsNull()) {
            $value = null;
        }

        return $type instanceof ReflectionNamedType ? $this->transformViaNamedType($value, $type, $argument) : $value;
    }

    protected function transformViaNamedType(mixed $value, ReflectionNamedType $type, ReflectionParameter|ReflectionProperty $argument): mixed
    {
        $name = $type->getName();

        if ($type->isBuiltin()) {
            return match ($name) {
                'int', 'integer' => (int) $value,
                'bool', 'boolean' => (bool) $value,
                'float', 'double', 'real' => (float) $value,
                'string' => (string) $value,
                default => $value,
            };
        }

        $transformedName = $this->transformSpecialName($name, $argument);

        return class_exists($transformedName) ? $this->transformViaClassName($value, $transformedName) : $value;
    }

    protected function transformViaClassName(mixed $value, string $fQCN): mixed
    {
        if (is_a($fQCN, ParameterTransformerFactoryInterface::class, true)) {
            return $fQCN::buildFromParameter($value);
        }

        /** @noinspection PhpUndefinedClassInspection */
        /** @noinspection PhpUndefinedNamespaceInspection */
        if (function_exists('data_load')
            && interface_exists(eArc\Data\Entity\Interfaces\EntityInterface::class)
            && is_a($fQCN, eArc\Data\Entity\Interfaces\EntityInterface::class)
        ) {
            if ($entity = data_load($fQCN, $value)) {
                return $entity;
            }
        }

        try {
            $this->constructFromTransform($fQCN, is_null($value) ? [] : (is_array($value) ? $value : [$value]));
        } catch (Exception $exception) {
            unset($exception);
        }

        if (is_null($value) && function_exists('di_get')) {
            try {
                return di_get($fQCN);
            }
            /** @noinspection PhpUndefinedClassInspection */
            /** @noinspection PhpUndefinedNamespaceInspection */
            catch (eArc\DI\Exceptions\MakeClassException $exception) {
                unset($exception);
            }
        }

        return $value;
    }

    protected function transformSpecialName(string $name, ReflectionParameter|ReflectionProperty $argument): string
    {
        if ('parent' === $name) {
            $parentClass = $argument->getDeclaringClass()?->getParentClass();

            return $parentClass ? $parentClass->getName() : $name;
        }

        if ('self' === $name) {
            return $argument->getDeclaringClass()?->getName() ?? $name;
        }

        return $name;
    }

    /**
     * @return ReflectionProperty[]|ReflectionParameter[]
     *
     * @throws ReflectionException
     *
     * @noinspection PhpDocSignatureInspection
     */
    private function getArguments(string|object|callable $target): array
    {
        if (is_callable($target)) {
            return $this->getCallableReflection($target)->getParameters();
        }

        if (is_string($target)) {
            return (new ReflectionClass($target))->getConstructor()->getParameters();
        }

        return (new ReflectionClass($target))->getProperties();

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
