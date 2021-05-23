<?php /** @noinspection PhpUnusedParameterInspection */
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

namespace eArc\ParameterTransformerTests\classes;

class AccessCountTestClass
{
    public int $hello = 0;
    public int $world = 0;
    public int $twofold = 0;
    public int $two = 0;
    public int $fold = 0;
    public int $public = 0;
    protected int $protected = 0;

    public function setAnotherHello(string $hello): void
    {
        $this->hello++;
    }

    public function setHello(string $hello): void
    {
        $this->hello++;
    }

    public function setWorld(string $world): void
    {
        $this->world++;
    }

    public function setTwoFold(int $two, int $fold)
    {
        $this->twofold++;
    }

    protected function setTwo(int $two)
    {
        $this->two++;
    }

    protected function setFold(int $fold)
    {
        $this->fold++;
    }

    public function getProtected(): int
    {
        return $this->protected;
    }
}
