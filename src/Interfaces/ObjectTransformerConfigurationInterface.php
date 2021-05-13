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

namespace eArc\ParameterTransformer\Interfaces;

interface ObjectTransformerConfigurationInterface
{
    /**
     * Returns true if the methods should be used for parameter transformation
     * before the properties.
     */
    public function isMethodsFirst(): bool;
    /**
     * Filter to use with \ReflectionClass::getMethods() e.g.
     * \ReflectionMethod::IS_PUBLIC.
     */
    public function getFilterMethods(): int;
    /**
     * Filter to use with \ReflectionClass::getProperties() e.g.
     * \ReflectionProperty::IS_PUBLIC.
     */
    public function getFilterProperties(): int;
    /**
     * Only methods with a parameter count less or equal will be evaluated during
     * transformation.
     */
    public function getMaxParameterCount(): int;
}
