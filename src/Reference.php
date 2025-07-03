<?php

declare(strict_types=1);

namespace Slon\Container;

use InvalidArgumentException;
use Slon\Container\Contract\RegistryInterface;
use Slon\Container\Contract\ReferenceInterface;

use function sprintf;

class Reference implements ReferenceInterface
{
    protected string $id;

    public function __construct(string $id)
    {
        if (empty($id)) {
            throw new InvalidArgumentException(sprintf(
                'Reference id "%s" is empty',
                $id,
            ));
        }

        $this->id = $id;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function load(RegistryInterface $registry): object
    {
        if ($registry->isContainerId($this->getId())) {
            return clone $registry;
        }
        
        return $registry->get($this->id);
    }
}
