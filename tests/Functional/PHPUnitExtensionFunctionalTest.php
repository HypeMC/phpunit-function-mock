<?php

declare(strict_types=1);

namespace Bizkit\PHPUnitFunctionMock\Tests\Functional;

use Bizkit\PHPUnitFunctionMock\Attribute\RegisterFunctionMock;
use Bizkit\PHPUnitFunctionMock\FunctionMock;
use Bizkit\PHPUnitFunctionMock\PHPUnitExtension;
use Bizkit\PHPUnitFunctionMock\Tests\Fixtures\Target\FunctionMockTarget;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

#[CoversClass(PHPUnitExtension::class)]
#[RegisterFunctionMock('file_exists')]
#[RegisterFunctionMock(['is_file', 'is_dir'])]
final class PHPUnitExtensionFunctionalTest extends TestCase
{
    public function testExtensionRegistersClassAttributes(): void
    {
        $calls = [];
        FunctionMock::mockMany([
            'file_exists' => static function (string $path) use (&$calls): bool {
                $calls[] = ['file_exists', $path];

                return true;
            },
            'is_file' => static function (string $path) use (&$calls): bool {
                $calls[] = ['is_file', $path];

                return false;
            },
            'is_dir' => static function (string $path) use (&$calls): bool {
                $calls[] = ['is_dir', $path];

                return true;
            },
        ]);

        self::assertTrue(namespace\file_exists('class-single'));
        self::assertFalse(namespace\is_file('class-list-first'));
        self::assertTrue(namespace\is_dir('class-list-second'));
        self::assertSame([
            ['file_exists', 'class-single'],
            ['is_file', 'class-list-first'],
            ['is_dir', 'class-list-second'],
        ], $calls);
    }

    #[RegisterFunctionMock('is_executable')]
    public function testExtensionRegistersMethodAttributes(): void
    {
        $calledPath = null;
        FunctionMock::mock(
            'is_executable',
            static function (string $path) use (&$calledPath): bool {
                $calledPath = $path;

                return true;
            },
        );

        self::assertTrue(namespace\is_executable('method-single'));
        self::assertSame('method-single', $calledPath);
    }

    #[RegisterFunctionMock('strlen', class: FunctionMockTarget::class)]
    public function testExtensionRegistersClassOverrideAttributes(): void
    {
        FunctionMock::mock('strlen', static fn (string $value): int => 100 + \strlen($value));

        self::assertFalse(\function_exists(__NAMESPACE__.'\\strlen'));
        self::assertSame(103, \Bizkit\PHPUnitFunctionMock\Fixtures\Target\strlen('foo'));
        self::assertSame(103, \Bizkit\PHPUnitFunctionMock\Tests\Fixtures\Target\strlen('foo'));
    }

    #[Depends('testExtensionRegistersClassOverrideAttributes')]
    public function testExtensionClearsMocksAfterAnnotatedTests(): void
    {
        self::assertSame(3, FunctionMock::run('strlen', 'foo'));
    }
}
