<?php

declare(strict_types=1);

namespace Slon\Container\Contract;

interface ReferenceInterface
{
    public function getId(): string;

    public function load(RegistryInterface $registry): mixed;
}
