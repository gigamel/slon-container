<?php

declare(strict_types=1);

namespace Slon\Container;

use Closure;
use InvalidArgumentException;
use Slon\Container\Contract\InstanceInterface;
use Slon\Container\Contract\RegistryInterface;
use Slon\Container\Contract\ReferenceInterface;
use Slon\Container\Exception\CircularReferenceException;
use Slon\Container\Exception\NotFoundInstanceException;

use function array_key_exists;
use function call_user_func;
use function in_array;
use function sprintf;

class Registry implements RegistryInterface
{
    protected array $registryIds = [
        'registry',
        'container',
        'service_container',
    ];

    /** @var list<InstanceInterface> */
    protected array $instances = [];

    /** @var array<string, object> */
    protected array $services = [];
    
    protected array $options = [];
    
    protected bool $isCompiled = false;
    
    public function __construct(array $services = [])
    {
        foreach ($services as $id => $service) {
            $this->addService($id, $service);
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    public function add(InstanceInterface $instance): void
    {
        if ($this->has($instance->getId())) {
            throw new InvalidArgumentException(sprintf(
                'Instance "%s" already exists',
                $instance->getId(),
            ));
        }
        
        $this->checkContainerId(
            $instance->getId(),
            $instance->getClassName(),
        );
        
        $this->instances[$instance->getId()] = $instance;
    }

    /**
     * @throws NotFoundInstanceException
     * @throws CircularReferenceException
     */
    public function get(string $id): object
    {
        if (array_key_exists($id, $this->services)) {
            if ($this->services[$id] instanceof Closure) {
                $this->services[$id] = call_user_func(
                    $this->services[$id],
                    $this,
                );
            }
            
            return $this->services[$id];
        }
        
        if (array_key_exists($id, $this->instances)) {
            return $this->instantiate(
                $this->instances[$id],
            );
        }

        throw new NotFoundInstanceException(sprintf(
            'Undefined "%s" instance',
            $id,
        ));
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->services)
            || array_key_exists($id, $this->instances)
            || $this->isContainerId($id);
    }
    
    public function getOption(string $name, mixed $default = null): mixed
    {
        return $this->options[$name] ?? $default;
    }
    
    public function addOption(string $name, mixed $value): void
    {
        $this->options[$name] = $value;
    }
    
    public function compile(): void
    {
        if ($this->isCompiled) {
            return;
        }
        
        foreach ($this->instances as $instance) {
            $this->instantiate($instance);
        }
        
        $this->isCompiled = true;
    }
    
    public function getInstances(): array
    {
        return $this->instances;
    }
    
    public function isContainerId(string $id): bool
    {
        return in_array($id, $this->registryIds, true);
    }

    /**
     * @throws CircularReferenceException
     */
    protected function instantiate(InstanceInterface $instance): object
    {
        $arguments = [];
        
        if ($parentId = $instance->getParentId()) {
            if (!array_key_exists($parentId, $this->instances)) {
                throw new InvalidArgumentException(sprintf(
                    'Not found parent instance "%s" for "%s"',
                    $parentId,
                    $instance->getClassName(),
                ));
            }
            
            foreach (
                $this->instances[$parentId]->getArguments()
                as $name => $reference
            ) {
                $instance->argument($name, $reference);
            }
        }
        
        foreach ($instance->getArguments() as $name => $reference) {
            $this->checkCircular($instance, $reference);
            $arguments[$name] = $reference->load($this);
        }

        return $this->services[$instance->getId()] = new (
            $instance->getClassName()
        )(...$arguments);
    }

    /**
     * @throws CircularReferenceException
     */
    protected function checkCircular(
        instanceInterface $rootInstance,
        ReferenceInterface $innerReference,
        ?InstanceInterface $innerInstance = null,
    ): void {
        if (!array_key_exists($innerReference->getId(), $this->instances)) {
            return;
        }
        
        if ($rootInstance->getId() === $innerReference->getId()) {
            if ($innerInstance) {
                throw new CircularReferenceException(sprintf(
                    'Detected circular reference "%s" -> <- "%s"',
                    $rootInstance->getClassName(),
                    $innerInstance->getClassName(),
                ));
            }
            
            throw new CircularReferenceException(sprintf(
                'Detected self reference "%s"',
                $rootInstance->getClassName(),
            ));
        }
        
        $nextInstance = $this->instances[$innerReference->getId()];
        foreach ($nextInstance->getArguments() as $nextReference) {
            if ($innerInstance?->getId() === $nextReference->getId()) {
                throw new CircularReferenceException(sprintf(
                    'Detected circular reference "%s" -> <- "%s"',
                    $innerInstance->getClassName(),
                    $nextInstance->getClassName(),
                ));
            }
            
            $this->checkCircular(
                $rootInstance,
                $nextReference,
                $nextInstance,
            );
        }
    }
    
    /**
     * @throws CircularReferenceException
     * @throws InvalidArgumentException
     */
    protected function addService(string $id, object $service): void
    {
        $this->checkContainerId($id, $service::class);
        
        if ($this === $service) {
            throw new CircularReferenceException(sprintf(
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
        
        $this->services[$id] = $service;
    }
    
    protected function checkContainerId(string $id, string $className): void
    {
        if (!$this->isContainerId($id)) {
            return;
        }
        
        throw new InvalidArgumentException(sprintf(
            'Instance "%s" refers to reserved "[%s]"',
            $className,
            implode(',', $this->registryIds),
        ));
    }
}
