<?php

declare(strict_types=1);

// @php-cs-fixer-ignore native_function_invocation

namespace Bizkit\PHPUnitFunctionMock\Tests\Fixtures\Target;

/**
 * Kept in a dedicated Target subnamespace so tests can distinguish class override registration
 * from the default registration based on ClassOverrideMockTest::class.
 */
final class FunctionMockTarget
{
    public static function strlen(string $string): int
    {
        return strlen($string);
    }
}
