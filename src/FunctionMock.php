<?php

declare(strict_types=1);

namespace Bizkit\PHPUnitFunctionMock;

final class FunctionMock
{
    /** @var array<callable-string, callable> */
    private static array $functions = [];

    /**
     * @param callable-string $function
     */
    public static function mock(string $function, ?callable $callable): void
    {
        if (null !== $callable) {
            self::$functions[$function] = $callable;
        } else {
            unset(self::$functions[$function]);
        }
    }

    /**
     * @param array<callable-string, callable> $functions
     */
    public static function mockMany(array $functions): void
    {
        self::reset();

        foreach ($functions as $function => $callable) {
            self::mock($function, $callable);
        }
    }

    public static function reset(): void
    {
        self::$functions = [];
    }

    /**
     * @internal
     *
     * @param callable-string $function
     */
    public static function run(string $function, mixed ...$functionArgs): mixed
    {
        return isset(self::$functions[$function])
            ? self::$functions[$function](...$functionArgs)
            : ('\\'.$function)(...$functionArgs);
    }

    /**
     * @param class-string                 $class
     * @param string|list<callable-string> $functions
     */
    public static function register(string $class, string|array $functions): void
    {
        if (false === $pos = \strrpos($class, '\\')) {
            throw new \ValueError(\sprintf('Argument 1 passed to %s() must be a FQCN string, %s given.', __METHOD__, $class));
        }

        $mockedNs = [\substr($class, 0, $pos)];
        if (0 < \strpos($class, '\\Tests\\')) {
            $ns = \str_replace('\\Tests\\', '\\', $class);
            $mockedNs[] = \substr($ns, 0, \strrpos($ns, '\\'));
        } elseif (\str_starts_with($class, 'Tests\\')) {
            $mockedNs[] = \substr($class, 6, $pos - 6);
        }
        foreach ((array) $functions as $function) {
            self::registerFunction($function, $mockedNs);
        }
    }

    /**
     * @param list<string> $mockedNs
     */
    private static function registerFunction(string $function, array $mockedNs): void
    {
        if (!\function_exists('\\'.$function)) {
            throw new \ValueError(\sprintf('Cannot register undefined function %s().', $function));
        }

        $self = self::class;

        foreach ($mockedNs as $ns) {
            if (\function_exists($ns.'\\'.$function)) {
                continue;
            }
            eval(
                <<<EOPHP
                    namespace $ns;

                    function $function(...\$functionArgs)
                    {
                        return \\$self::run('$function', ...\$functionArgs);
                    }
                    EOPHP
            );
        }
    }
}
