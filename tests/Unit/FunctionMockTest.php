<?php

declare(strict_types=1);

namespace Bizkit\PHPUnitFunctionMock\Tests\Unit;

use Bizkit\PHPUnitFunctionMock\FunctionMock;
use Bizkit\PHPUnitFunctionMock\Tests\Fixtures\Target\FunctionMockTarget;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

#[CoversClass(FunctionMock::class)]
final class FunctionMockTest extends TestCase
{
    protected function tearDown(): void
    {
        FunctionMock::reset();
    }

    public function testRunsMock(): void
    {
        FunctionMock::mock('time', static fn (): int => 123);

        self::assertSame(123, FunctionMock::run('time'));
    }

    public function testFallsBackToNativeFunction(): void
    {
        self::assertSame(3, FunctionMock::run('strlen', 'foo'));
    }

    public function testReplacingAllMocksClearsPreviousMocks(): void
    {
        FunctionMock::mockMany([
            'strlen' => static fn (string $value): int => 99,
            'strtoupper' => static fn (string $value): string => 'mocked',
        ]);

        FunctionMock::mockMany([
            'strlen' => static fn (string $value): int => 4,
        ]);

        self::assertSame(4, FunctionMock::run('strlen', 'foo'));
        self::assertSame('FOO', FunctionMock::run('strtoupper', 'foo'));
    }

    public function testRemovingMockRestoresNativeFallback(): void
    {
        FunctionMock::mock('strlen', static fn (string $value): int => 99);
        FunctionMock::mock('strlen', null);

        self::assertSame(3, FunctionMock::run('strlen', 'foo'));
    }

    public function testRegisterThrowsForNonNamespacedClass(): void
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('must be a FQCN string, stdClass given.');

        FunctionMock::register('stdClass', 'strlen');
    }

    public function testRegisterThrowsForUndefinedFunction(): void
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('Cannot register undefined function undefined_function_for_function_mock_test().');

        FunctionMock::register(FunctionMockTarget::class, 'undefined_function_for_function_mock_test');
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testRegisteredNamespaceFunctionDispatchesToMock(): void
    {
        FunctionMock::register(FunctionMockTarget::class, 'strlen');
        FunctionMock::mock('strlen', static fn (string $value): int => 40 + \strlen($value));

        self::assertTrue(\function_exists('Bizkit\PHPUnitFunctionMock\Fixtures\Target\strlen'));
        self::assertTrue(\function_exists('Bizkit\PHPUnitFunctionMock\Tests\Fixtures\Target\strlen'));
        self::assertSame(43, \Bizkit\PHPUnitFunctionMock\Fixtures\Target\strlen('foo'));
        self::assertSame(43, \Bizkit\PHPUnitFunctionMock\Tests\Fixtures\Target\strlen('foo'));
        self::assertSame(43, FunctionMockTarget::strlen('foo'));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testRegisterMapsRootTestsNamespaceToTestedNamespace(): void
    {
        eval('namespace Tests\FunctionMockFixtures; final class RootTestsTarget {}');

        FunctionMock::register('Tests\FunctionMockFixtures\RootTestsTarget', 'strlen');
        FunctionMock::mock('strlen', static fn (string $value): int => 50 + \strlen($value));

        self::assertTrue(\function_exists('Tests\FunctionMockFixtures\strlen'));
        self::assertTrue(\function_exists('FunctionMockFixtures\strlen'));
        self::assertSame(53, \Tests\FunctionMockFixtures\strlen('foo'));
        self::assertSame(53, \FunctionMockFixtures\strlen('foo'));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testRegisterIgnoresAlreadyRegisteredNamespaceFunctions(): void
    {
        FunctionMock::register(FunctionMockTarget::class, 'strlen');
        FunctionMock::register(FunctionMockTarget::class, 'strlen');
        FunctionMock::mock('strlen', static fn (string $value): int => 60 + \strlen($value));

        self::assertSame(63, \Bizkit\PHPUnitFunctionMock\Tests\Fixtures\Target\strlen('foo'));
    }
}
