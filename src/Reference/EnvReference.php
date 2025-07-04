<?php

declare(strict_types=1);

namespace Slon\Container\Reference;

use Slon\Container\Contract\RegistryInterface;
use Slon\Container\Contract\ReferenceInterface;

class EnvReference implements ReferenceInterface
{
    public function __construct(protected string $name) {}

    public function getId(): string
    {
        return $this->name;
    }

    public function load(RegistryInterface $registry): string|int|float|null
    {
        return $_ENV[$this->getId()] ?? null;
    }
}
