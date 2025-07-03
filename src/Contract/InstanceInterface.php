<?php

declare(strict_types=1);

namespace Slon\Container\Contract;

interface InstanceInterface
{
    public function argument(string $name, ReferenceInterface $reference): self;

    /**
     * @return array<string, ReferenceInterface>
     */
    public function getArguments(): array;

    public function getClassName(): string;
    
    public function getId(): string;
    
    public function extends(string $id): self;
    
    public function getParentId(): ?string;
}
