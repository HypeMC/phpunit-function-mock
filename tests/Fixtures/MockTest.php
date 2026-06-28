<?php

declare(strict_types=1);

namespace Bizkit\PHPUnitFunctionMock\Tests\Fixtures;

use Bizkit\PHPUnitFunctionMock\Attribute\RegisterFunctionMock;

#[RegisterFunctionMock(['file_exists', 'is_file'])]
final class MockTest
{
    #[RegisterFunctionMock('is_dir')]
    public function testFoo(): void
    {
    }
}
