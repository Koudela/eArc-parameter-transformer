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

use eArc\ParameterTransformer\Configuration;
use eArc\ParameterTransformer\Exceptions\NoInputException;
use eArc\ParameterTransformer\Interfaces\ConfigurationInterface;

class InputProvider
{
    protected ConfigurationInterface $config;
    /** @var array<int|string, mixed> */
    protected array $input;
    /** @var array<int|string, mixed> */
    protected array $backup = [];
    protected int $pos = 0;
    protected int $backupPosition = 0;
    protected int|string|null $lastInputKey = null;

    public function __construct(array|null $input, ConfigurationInterface|null $config)
    {
        $this->config = $config ?? di_get(Configuration::class);
        $this->input = $input ?? $this->config->getDefaultResource();
    }

    public function getConfig(): ConfigurationInterface
    {
        return $this->config;
    }

    public function inputIsEmpty(): bool
    {
        return empty($this->input);
    }

    /**
     * @throws NoInputException
     */
    public function getInput(int|string $key): mixed
    {
        $name = $this->config->getMapped($key);

        if (array_key_exists($name, $this->input)) {
            $this->lastInputKey = $key;

            return $this->input[$name];
        } elseif (array_key_exists($this->pos, $this->input)) {
            $this->lastInputKey = $this->pos;

            return $this->input[$this->pos];
        } elseif ($this->config->noInputIsAllowed()) {
            return null;
        }

        throw new NoInputException();
    }

    public function deleteLastInput(): void
    {
        if (is_string($this->lastInputKey) && array_key_exists($this->lastInputKey, $this->input)) {
            $this->backup[$this->lastInputKey] = $this->input[$this->lastInputKey];

            unset($this->input[$this->lastInputKey]);

            $this->lastInputKey = null;
        }

        if (is_int($this->lastInputKey)) {
            $this->pos++;

            $this->lastInputKey = null;
        }
    }

    public function initBackup(): void
    {
        $this->backup = [];
        $this->backupPosition = $this->pos;
    }

    public function restoreBackup(): void
    {
        $this->pos = $this->backupPosition;

        foreach ($this->backup as $key => $value) {
            $this->input[$key] = $value;
        }

        $this->backup = [];
    }
}
