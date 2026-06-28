<?php

declare(strict_types=1);

namespace Bizkit\PHPUnitFunctionMock\Tests\Fixtures;

use Bizkit\PHPUnitFunctionMock\Attribute\RegisterFunctionMock;
use Bizkit\PHPUnitFunctionMock\Tests\Fixtures\Target\FunctionMockTarget;

#[RegisterFunctionMock('strlen', class: FunctionMockTarget::class)]
final class ClassOverrideMockTest
{
    public function testFoo(): void
    {
    }
}
