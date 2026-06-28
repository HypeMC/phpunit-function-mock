<?php

declare(strict_types=1);

namespace Bizkit\PHPUnitFunctionMock\Tests\Fixtures;

use Bizkit\PHPUnitFunctionMock\Attribute\RegisterFunctionMock;
use Bizkit\PHPUnitFunctionMock\Tests\Fixtures\Target\FunctionMockTarget;

#[RegisterFunctionMock('file_exists')]
#[RegisterFunctionMock(['is_file', 'is_dir'])]
final class RepeatableAttributesTest
{
    #[RegisterFunctionMock('is_executable')]
    #[RegisterFunctionMock('strlen', class: FunctionMockTarget::class)]
    public function testFoo(): void
    {
    }
}
