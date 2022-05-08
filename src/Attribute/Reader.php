<?php

declare(strict_types=1);

namespace Bizkit\FunctionMock\Attribute;

final class Reader
{
    private static self $instance;
    /** @var array<class-string, list<string>> */
    private array $classMockedFunctions = [];
    /** @var array<class-string, array<string, list<string>>> */
    private array $methodMockedFunctions = [];

    public static function instance(): self
    {
        return self::$instance ?? self::$instance = new self();
    }

    private function __construct()
    {
    }

    /**
     * @param class-string $class
     *
     * @return list<string>
     */
    public function forClass(string $class): array
    {
        return $this->classMockedFunctions[$class]
            ?? $this->classMockedFunctions[$class] = $this->getAttribute(new \ReflectionClass($class));
    }

    /**
     * @param class-string $class
     *
     * @return list<string>
     */
    public function forMethod(string $class, string $method): array
    {
        return $this->methodMockedFunctions[$class][$method]
            ?? $this->methodMockedFunctions[$class][$method] = $this->getAttribute(
                new \ReflectionMethod($class, $method)
            );
    }

    /**
     * @param class-string $class
     *
     * @return list<string>
     */
    public function forClassAndMethod(string $class, string $method): array
    {
        return \array_merge($this->forClass($class), $this->forMethod($class, $method));
    }

    /**
     * @return list<string>
     */
    private function getAttribute(\ReflectionClass|\ReflectionMethod $r): array
    {
        /** @var \ReflectionAttribute<MockedFunctions>|null $rAttribute */
        $rAttribute = $r->getAttributes(MockedFunctions::class, \ReflectionAttribute::IS_INSTANCEOF)[0] ?? null;

        return $rAttribute?->newInstance()?->getFunctions() ?? [];
    }
}
