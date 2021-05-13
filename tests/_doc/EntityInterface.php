<?php /** @noinspection PhpIllegalPsrClassPathInspection */
declare(strict_types=1);
/**
 * e-Arc Framework - the explicit Architecture Framework
 * router component
 *
 * @package earc/parameter-transformer
 * @link https://github.com/Koudela/eArc-parameter-transformer/
 * @copyright Copyright (c) 2018-2021 Thomas Koudela
 * @license http://opensource.org/licenses/MIT MIT License
 */

namespace {
    use eArc\Data\Entity\Interfaces\EntityInterface;

    function data_load($fQCN, $pk): EntityInterface|null {}
}

namespace eArc\Data\Entity\Interfaces {
    interface EntityInterface {
        public function getRepository($fQCN);
    }
}
