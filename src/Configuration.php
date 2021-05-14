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

use eArc\ParameterTransformer\Interfaces\ConfigurationInterface;
use ReflectionMethod;
use ReflectionProperty;

class Configuration implements ConfigurationInterface
{
    /** @var callable */
    protected $defaultResource = null;
    /** @var array<string, string> */
    protected array $mapping = [];
    /** @var array<string, mixed> */
    protected array $predefinedValues = [];
    protected bool $methodsFirst = true;
    protected int $filterMethods = ReflectionMethod::IS_PUBLIC;
    protected int $filterProperties = ReflectionProperty::IS_PUBLIC;
    protected int $maxParameterCount = 1;
    protected bool $nullIsAllowed = true;
    protected bool $noInputIsAllowed = false;
    protected bool $usePropertyTransformation = true;

    public function getDefaultResource(): array
    {
        $resource = $this->defaultResource;

        return is_null($resource) ? $this->getInput() : $resource();
    }

    public function setDefaultResource(callable $defaultResource): static
    {
        $this->defaultResource = $defaultResource;

        return $this;
    }

    public function getMapped(string $key): string
    {
        return array_key_exists($key, $this->mapping) ? $this->mapping[$key] : $key;
    }

    public function setMapping(array $mapping): static
    {
        $this->mapping = $mapping;

        return $this;
    }

    public function hasPredefinedValue(string $typeHint): bool
    {
        return array_key_exists($typeHint, $this->predefinedValues);
    }

    public function getPredefinedValue(string $typeHint): mixed
    {
        return $this->predefinedValues[$typeHint];
    }

    public function setPredefinedValues(array $predefinedValues): static
    {
        $this->predefinedValues = $predefinedValues;

        return $this;
    }

    public function isMethodsFirst(): bool
    {
        return $this->methodsFirst;
    }

    public function setMethodsFirst(bool $methodsFirst): static
    {
        $this->methodsFirst = $methodsFirst;

        return $this;
    }

    public function getFilterMethods(): int
    {
        return $this->filterMethods;
    }

    public function setFilterMethods(int $filterMethods): static
    {
        $this->filterMethods = $filterMethods;

        return $this;
    }

    public function getFilterProperties(): int
    {
        return $this->filterProperties;
    }

    public function setFilterProperties(int $filterProperties): static
    {
        $this->filterProperties = $filterProperties;

        return $this;
    }

    public function getMaxParameterCount(): int
    {
        return $this->maxParameterCount;
    }

    public function setMaxParameterCount(int $maxParameterCount): static
    {
        $this->maxParameterCount = $maxParameterCount;

        return $this;
    }

    public function nullIsAllowed(): bool
    {
        return $this->nullIsAllowed;
    }

    public function setNullIsAllowed(bool $nullIsAllowed): static
    {
        $this->nullIsAllowed = $nullIsAllowed;

        return $this;
    }

    public function noInputIsAllowed(): bool
    {
        return $this->noInputIsAllowed;
    }

    public function setNoInputIsAllowed(bool $noInputIsAllowed): static
    {
        $this->noInputIsAllowed = $noInputIsAllowed;

        return $this;
    }

    public function usePropertyTransformation(): bool
    {
        return $this->usePropertyTransformation;
    }

    public function setUsePropertyTransformation(bool $usePropertyTransformation): static
    {
        $this->usePropertyTransformation = $usePropertyTransformation;

        return $this;
    }

    protected function getInput(): array
    {
        parse_str(file_get_contents('php://input'), $values);

        return $values;
    }
}
