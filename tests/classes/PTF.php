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

use eArc\ParameterTransformer\Interfaces\ParameterTransformerFactoryInterface;

class PTF implements ParameterTransformerFactoryInterface
{
    public string $myId;

    public static function buildFromParameter($parameter): static
    {
        $ptf = new PTF();
        $ptf->myId = $parameter;

        return $ptf;
    }
}
