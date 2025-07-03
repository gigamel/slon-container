<?php

declare(strict_types=1);

namespace Slon\Container\Contract;

use Slon\Container\Exception\NotFoundInstanceException;

interface RegistryInterface
{
    public function add(InstanceInterface $instance): void;

    /**
     * @throws NotFoundInstanceException
     */
    public function get(string $id): object;

    public function has(string $id): bool;
    
    public function getOption(string $name, mixed $default = null): mixed;
    
    public function addOption(string $name, mixed $value): void;
    
    public function compile(): void;
    
    /**
     * @return array<string, InstanceInterface>|list<InstanceInterface>
     */
    public function getInstances(): array;
    
    public function isContainerId(string $id): bool;
}
