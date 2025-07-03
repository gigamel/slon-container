<?php

declare(strict_types=1);

namespace Slon\Container\Reference;

use Slon\Container\Contract\RegistryInterface;
use Slon\Container\Contract\ReferenceInterface;

class OptionReference implements ReferenceInterface
{
    public function __construct(protected string $name) {}
    
    public function getId(): string
    {
        return $this->name;
    }
    
    public function load(RegistryInterface $registry): mixed
    {
        return $registry->getOption($this->getId());
    }
}
