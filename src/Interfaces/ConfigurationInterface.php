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

interface ConfigurationInterface extends ObjectTransformerConfigurationInterface
{
    /**
     * Returns an default input array.
     */
    public function getDefaultResource(): array;
    /**
     * If the key is mapped it returns the mapped key and the key otherwise.
     */
    public function getMapped(string $key): string;
    /**
     * Returns true if there is a predefined value for the typehint and false otherwise.
     */
    public function hasPredefinedValue(string $typeHint): bool;
    /**
     * Returns the predefined value for the typehint or null if there is no predefined value.
     */
    public function getPredefinedValue(string $typeHint): mixed;
    /**
     * Returns true if no corresponding input value should be treated as null,
     * and false if a NoInputException shall be thrown.
     */
    public function noInputIsAllowed(): bool;
    /**
     * Returns true if null is allowed as result of a callable argument transformation.
     * If false the callable transformation throws a NullValueException if an arguments
     * transformation results in a null value.
     */
    public function nullIsAllowed(): bool;
}
