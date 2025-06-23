<?php

declare(strict_types=1);

namespace Slon\Container;

use Closure;
use InvalidArgumentException;
use Slon\ODR\MetaRegistry;

use function array_key_exists;
use function call_user_func;
use function sprintf;

class ContainerRegistry extends MetaRegistry
{
    /**
     * @throws InvalidArgumentException
     */
    public function __construct(array $services = [])
    {
        foreach ($services as $id => $service) {
            $this->addInstance($id, $service);
        }
    }
    
    public function get(string $id): object
    {
        if (
            array_key_exists($id, $this->instances)
            && $this->instances[$id] instanceof Closure
        ) {
            $this->instances[$id] = call_user_func(
                $this->instances[$id],
                $this,
            );
        }
        
        return parent::get($id);
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function addInstance(string $id, object $service): void
    {
        if ($this === $service) {
            throw new InvalidArgumentException(sprintf(
                'Detected circular reference "%s"',
                $service::class,
            ));
        }
        
        if ($this->has($id)) {
            throw new InvalidArgumentException(sprintf(
                'Instance "%s" already exists',
                $id,
            ));
        }
        
        $this->instances[$id] = $service;
    }
}
