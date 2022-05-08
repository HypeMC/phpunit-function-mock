<?php

declare(strict_types=1);

namespace Bizkit\FunctionMock\Tests\Attribute\Fixtures;

use Bizkit\FunctionMock\Attribute\MockedFunctions;

#[MockedFunctions('file_exists', 'is_file')]
final class MockTest
{
    #[MockedFunctions('is_dir')]
    public function testFoo(): void
    {
    }
}
