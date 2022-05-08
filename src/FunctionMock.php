<?php

declare(strict_types=1);

namespace Bizkit\FunctionMock;

class FunctionMock
{
    /** @var array<string, callable> */
    private static array $functions = [];

    /**
     * @param array<string, ?callable> $functions
     */
    public static function withMockedFunctions(array $functions): void
    {
        foreach ($functions as $function => $callable) {
            static::withMockedFunction($function, $callable);
        }
    }

    public static function withMockedFunction(string $function, ?callable $callable): void
    {
        if (null !== $callable) {
            self::$functions[$function] = $callable;
        } else {
            unset(self::$functions[$function]);
        }
    }

    public static function run(string $function, ...$functionArgs): mixed
    {
        return isset(self::$functions[$function])
            ? self::$functions[$function](...$functionArgs)
            : ('\\'.$function)(...$functionArgs);
    }

    /**
     * @param class-string        $class
     * @param string|list<string> $functions
     */
    public static function register(string $class, string|array $functions): void
    {
        $mockedNs = [\substr($class, 0, \strrpos($class, '\\'))];
        if (0 < \strpos($class, '\\Tests\\')) {
            $ns = \str_replace('\\Tests\\', '\\', $class);
            $mockedNs[] = \substr($ns, 0, \strrpos($ns, '\\'));
        } elseif (\str_starts_with($class, 'Tests\\')) {
            $mockedNs[] = \substr($class, 6, \strrpos($class, '\\') - 6);
        }
        foreach ((array) $functions as $function) {
            static::registerFunction($function, $mockedNs);
        }
    }

    /**
     * @param list<string> $mockedNs
     */
    protected static function registerFunction(string $function, array $mockedNs): void
    {
        if (!\function_exists('\\'.$function)) {
            throw new \ValueError(\sprintf('Cannot register undefined function %s().', $function));
        }

        $self = static::class;

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
