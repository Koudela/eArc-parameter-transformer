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

interface ParameterTransformerFactoryInterface
{
    /**
     * Returns an instance of the current class build from a single parameter.
     */
    public static function buildFromParameter($parameter): static;
}
