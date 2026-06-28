<?php

declare(strict_types=1);

namespace Bizkit\PHPUnitFunctionMock\Tests\Unit\Attribute;

use Bizkit\PHPUnitFunctionMock\Attribute\RegisterFunctionMock;
use Bizkit\PHPUnitFunctionMock\Tests\Fixtures\Target\FunctionMockTarget;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RegisterFunctionMock::class)]
final class RegisterFunctionMockTest extends TestCase
{
    public function testAcceptsSingleFunction(): void
    {
        $attribute = new RegisterFunctionMock('file_exists');

        self::assertSame(['file_exists'], $attribute->functions);
        self::assertNull($attribute->class);
    }

    public function testAcceptsFunctionListAndClassOverride(): void
    {
        $attribute = new RegisterFunctionMock(['file_exists', 'is_file'], FunctionMockTarget::class);

        self::assertSame(['file_exists', 'is_file'], $attribute->functions);
        self::assertSame(FunctionMockTarget::class, $attribute->class);
    }

    public function testRejectsNonStringFunction(): void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('must be a list of strings, element 1 is int.');

        new RegisterFunctionMock(['file_exists', 42]);
    }
}
