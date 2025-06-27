<?php

declare(strict_types=1);

namespace Slon\Container\Reference;

use Slon\Container\Meta\MetaRegistryInterface;
use Slon\Container\Meta\ReferenceInterface;

class EnvReference implements ReferenceInterface
{
    public function __construct(protected string $name) {}

    public function getId(): string
    {
        return $this->name;
    }

    public function load(MetaRegistryInterface $registry): string|int|float|null
    {
        return $_ENV[$this->getId()] ?? null;
    }
}
