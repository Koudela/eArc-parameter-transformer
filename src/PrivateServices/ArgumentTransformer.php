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

use eArc\ParameterTransformer\Exceptions\DiException;
use eArc\ParameterTransformer\Exceptions\FactoryException;
use eArc\ParameterTransformer\Interfaces\ParameterTransformerFactoryInterface;
use eArc\ParameterTransformer\Interfaces\ParameterTransformerFactoryServiceInterface;
use ReflectionException;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionType;
use ReflectionUnionType;

class ArgumentTransformer
{
    /**
     * @throws DiException | FactoryException
     */
    public function transformViaArgument($value, ReflectionParameter|ReflectionProperty $argument): mixed
    {
        $rawType = $argument->getType();

        $types = $rawType instanceof ReflectionUnionType ? $rawType->getTypes(): [$rawType];

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
            } catch (ReflectionException) {
                return null;
            }
        }

        return $value;
    }

    /**
     * @throws DiException | FactoryException
     */
    protected function transformViaType(mixed $value, ReflectionType $type, ReflectionParameter|ReflectionProperty $argument): mixed
    {
        if ('null' === $value && $type->allowsNull()) {
            $value = null;
        }

        return $type instanceof ReflectionNamedType ? $this->transformViaNamedType($value, $type, $argument) : $value;
    }

    /**
     * @throws DiException | FactoryException
     */
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

        $transformedName = $this->mapSpecialName($name, $argument);

        return class_exists($transformedName) ? $this->transformViaClassName($value, $transformedName) : $value;
    }

    /**
     * @throws DiException | FactoryException
     */
    protected function transformViaClassName(mixed $value, string $fQCN): mixed
    {
        if (is_subclass_of($fQCN, ParameterTransformerFactoryInterface::class, true)) {
            return $fQCN::buildFromParameter($value);
        }

        foreach (di_get_tagged(ParameterTransformerFactoryServiceInterface::class) as $serviceClassName => $args) {
            if (!is_subclass_of($serviceClassName, ParameterTransformerFactoryServiceInterface::class)) {
                throw new DiException(sprintf(
                    '{5ea8bc75-25cd-4830-ae8d-ff933abf92a6} %s has to implement %s.',
                    $fQCN,
                    ParameterTransformerFactoryServiceInterface::class
                ));
            }
            /** @var ParameterTransformerFactoryServiceInterface $service */
            $service = di_get($serviceClassName);
            if ($result = $service->buildFromParameter($fQCN, $value)) {
                if (!$result instanceof $fQCN) {
                    throw new FactoryException(sprintf(
                        '{cbd154e9-4bc7-43ef-b441-11e219a86a87} %s::buildFromParameter() has to return an instance of the given class %s',
                        $serviceClassName,
                        $fQCN
                    ));
                }

                return $result;
            }
        }
        return $value;
    }

    /**
     * Maps special class names like `parent` or `self` to their real class name.
     */
    protected function mapSpecialName(string $name, ReflectionParameter|ReflectionProperty $argument): string
    {
        if ('parent' === $name) {
            $parentClass = $argument->getDeclaringClass()?->getParentClass();

            return $parentClass ? $parentClass->getName() : $name;
        }

        if ('self' === $name) {
            $selfClass = $argument->getDeclaringClass();

            return $selfClass ? $selfClass->getName() : $name;
        }

        return $name;
    }
}
