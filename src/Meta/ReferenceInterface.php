<?php

declare(strict_types=1);

namespace Slon\Container\Meta;

interface ReferenceInterface
{
    public function getId(): string;

    public function load(MetaRegistryInterface $registry): mixed;
}
