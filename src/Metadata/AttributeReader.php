<?php

declare(strict_types=1);

namespace Bizkit\PHPUnitFunctionMock\Metadata;

use Bizkit\PHPUnitFunctionMock\Attribute\RegisterFunctionMock;

/**
 * @internal
 */
final class AttributeReader
{
    /**
     * @var array<string, list<RegisterFunctionMock>>
     */
    private array $cache = [];

    /**
     * @param class-string $className
     *
     * @return list<RegisterFunctionMock>
     */
    public function forClass(string $className): array
    {
        return $this->cache[$className] ??= $this->readAttributes(new \ReflectionClass($className));
    }

    /**
     * @param class-string $className
     *
     * @return list<RegisterFunctionMock>
     */
    public function forMethod(string $className, string $methodName): array
    {
        return $this->cache[$className.'::'.$methodName] ??= $this->readAttributes(new \ReflectionMethod($className, $methodName));
    }

    /**
     * @param class-string $className
     *
     * @return list<RegisterFunctionMock>
     */
    public function forClassAndMethod(string $className, string $methodName): array
    {
        return [
            ...$this->forClass($className),
            ...$this->forMethod($className, $methodName),
        ];
    }

    /**
     * @param \ReflectionClass<object>|\ReflectionMethod $reflection
     *
     * @return list<RegisterFunctionMock>
     */
    private function readAttributes(\ReflectionClass|\ReflectionMethod $reflection): array
    {
        $attributes = [];
        foreach ($reflection->getAttributes(RegisterFunctionMock::class, \ReflectionAttribute::IS_INSTANCEOF) as $reflectionAttribute) {
            $attributes[] = $reflectionAttribute->newInstance();
        }

        return $attributes;
    }
}
