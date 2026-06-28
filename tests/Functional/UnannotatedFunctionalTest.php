<?php

declare(strict_types=1);

namespace Bizkit\PHPUnitFunctionMock\Tests\Functional;

use Bizkit\PHPUnitFunctionMock\FunctionMock;
use Bizkit\PHPUnitFunctionMock\PHPUnitExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

#[CoversClass(PHPUnitExtension::class)]
final class UnannotatedFunctionalTest extends TestCase
{
    public static function tearDownAfterClass(): void
    {
        FunctionMock::reset();
    }

    public function testExtensionKeepsMocksAfterUnannotatedTests(): void
    {
        FunctionMock::mock('strlen', static fn (string $value): int => 100 + \strlen($value));

        self::assertSame(103, FunctionMock::run('strlen', 'foo'));
    }

    #[Depends('testExtensionKeepsMocksAfterUnannotatedTests')]
    public function testExtensionDoesNotClearMocksForUnannotatedTests(): void
    {
        self::assertSame(103, FunctionMock::run('strlen', 'foo'));
    }
}
