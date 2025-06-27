<?php

declare(strict_types=1);

namespace Slon\Container;

use Closure;
use InvalidArgumentException;
use Slon\Container\Exception\CircularReferenceException;
use Slon\Container\Exception\MetaInstanceNotFoundException;
use Slon\Container\Meta\MetaInstanceInterface;
use Slon\Container\Meta\MetaRegistryInterface;
use Slon\Container\Meta\ReferenceInterface;

use function array_key_exists;
use function call_user_func;
use function sprintf;

class MetaRegistry implements MetaRegistryInterface
{
    /** @var list<MetaInstanceInterface> */
    protected array $metaInstances = [];

    /** @var array<string, object> */
    protected array $instances = [];
    
    protected array $parameters = [];
    
    protected bool $isCompiled = false;
    
    public function __construct(array $services = [])
    {
        foreach ($services as $id => $service) {
            $this->addInstance($id, $service);
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    public function addMeta(MetaInstanceInterface $metaInstance): void
    {
        if ($this->has($metaInstance->getId())) {
            throw new InvalidArgumentException(sprintf(
                'Meta instance "%s" already exists',
                $metaInstance->getId(),
            ));
        }
        
        $this->metaInstances[$metaInstance->getId()] = $metaInstance;
    }

    /**
     * @throws MetaInstanceNotFoundException
     * @throws CircularReferenceException
     */
    public function get(string $id): object
    {
        if (array_key_exists($id, $this->instances)) {
            if ($this->instances[$id] instanceof Closure) {
                $this->instances[$id] = call_user_func(
                    $this->instances[$id],
                    $this,
                );
            }
            
            return $this->instances[$id];
        }
        
        if (array_key_exists($id, $this->metaInstances)) {
            return $this->instantiate(
                $this->metaInstances[$id],
            );
        }

        throw new MetaInstanceNotFoundException(sprintf(
            'Undefined "%s" instance',
            $id,
        ));
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->instances)
            || array_key_exists($id, $this->metaInstances);
    }
    
    public function getParameter(string $name, mixed $default = null): mixed
    {
        return $this->parameters[$name] ?? $default;
    }
    
    public function setParameter(string $name, mixed $value): void
    {
        $this->parameters[$name] = $value;
    }
    
    public function compile(): void
    {
        if ($this->isCompiled) {
            return;
        }
        
        foreach ($this->metaInstances as $metaInstance) {
            $this->instantiate($metaInstance);
        }
        
        $this->isCompiled = true;
    }
    
    public function getMetaInstances(): array
    {
        return $this->metaInstances;
    }

    /**
     * @throws CircularReferenceException
     */
    protected function instantiate(MetaInstanceInterface $metaInstance): object
    {
        $arguments = [];
        foreach ($metaInstance->getArguments() as $name => $reference) {
            $this->checkCircular($metaInstance, $reference);
            $arguments[$name] = $reference->load($this);
        }

        return $this->instances[$metaInstance->getId()] = new (
            $metaInstance->getClassName()
        )(...$arguments);
    }

    /**
     * @throws CircularReferenceException
     */
    protected function checkCircular(
        MetaInstanceInterface $rootMetaInstance,
        ReferenceInterface $innerReference,
        ?MetaInstanceInterface $innerMetaInstance = null,
    ): void {
        if (!array_key_exists($innerReference->getId(), $this->metaInstances)) {
            return;
        }
        
        if ($rootMetaInstance->getId() === $innerReference->getId()) {
            if ($innerMetaInstance) {
                throw new CircularReferenceException(sprintf(
                    'Detected circular reference "%s" -> <- "%s"',
                    $rootMetaInstance->getClassName(),
                    $innerMetaInstance->getClassName(),
                ));
            }
            
            throw new CircularReferenceException(sprintf(
                'Detected self reference "%s"',
                $rootMetaInstance->getClassName(),
            ));
        }
        
        $nextMetaInstance = $this->metaInstances[$innerReference->getId()];
        foreach ($nextMetaInstance->getArguments() as $nextReference) {
            if ($innerMetaInstance?->getId() === $nextReference->getId()) {
                throw new CircularReferenceException(sprintf(
                    'Detected circular reference "%s" -> <- "%s"',
                    $innerMetaInstance->getClassName(),
                    $nextMetaInstance->getClassName(),
                ));
            }
            
            $this->checkCircular(
                $rootMetaInstance,
                $nextReference,
                $nextMetaInstance,
            );
        }
    }
    
    /**
     * @throws CircularReferenceException
     * @throws InvalidArgumentException
     */
    protected function addInstance(string $id, object $service): void
    {
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
        
        $this->instances[$id] = $service;
    }
}
