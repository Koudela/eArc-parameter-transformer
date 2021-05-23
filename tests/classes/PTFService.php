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

namespace eArc\ParameterTransformerTests\classes;

use eArc\ParameterTransformer\Interfaces\ParameterTransformerFactoryServiceInterface;

class PTFService implements ParameterTransformerFactoryServiceInterface
{
    public function buildFromParameter(string $fQCN, $parameter): object|null
    {
        if (is_a($fQCN, PTFSConstructedClass::class, true)) {
            return new PTFSConstructedClass($parameter);
        }

        return null;
    }
}
