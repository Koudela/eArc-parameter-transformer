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

namespace eArc\ParameterTransformer\Exceptions;

use Exception;

/**
 * Will be thrown if a callable argument resolves to null but null values are not
 * allowed by configuration.
 */
class NullValueException extends ParameterTransformerException {}
