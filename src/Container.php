<?php

declare(strict_types=1);

namespace Slon\Container;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Slon\Container\Contract\RegistryInterface;
use Slon\Container\Exception\NotFoundInstanceException;
use Slon\Container\Exception\ServiceNotFoundException;

use function sprintf;

class Container implements ContainerInterface
{
    public function __construct(
        protected RegistryInterface $registry,
    ) {}
    
    /**
     * @throws NotFoundExceptionInterface
     */
    public function get(string $id): object
    {
        try {
            return $this->registry->get($id);
        } catch (NotFoundInstanceException $e) {
            throw new ServiceNotFoundException(
                sprintf('Service "%s" not found', $id),
                previous: $e,
            );
        }
    }
    
    public function has(string $id): bool
    {
        return $this->registry->has($id);
    }
}
