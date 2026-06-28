<?php

declare(strict_types=1);

namespace Bizkit\PHPUnitFunctionMock\Tests\Unit;

use Bizkit\PHPUnitFunctionMock\FunctionMock;
use Bizkit\PHPUnitFunctionMock\Metadata\AttributeReader;
use Bizkit\PHPUnitFunctionMock\PHPUnitExtension;
use Bizkit\PHPUnitFunctionMock\Tests\Fixtures\ClassOverrideMockTest;
use Bizkit\PHPUnitFunctionMock\Tests\Fixtures\RepeatableAttributesTest;
use Bizkit\PHPUnitFunctionMock\Tests\Fixtures\UnannotatedTest;
use PHPUnit\Event\Code\Test as CodeTest;
use PHPUnit\Event\Code\TestDox;
use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\TestData\TestDataCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use PHPUnit\Metadata\MetadataCollection;
use PHPUnit\Runner\Version;

#[CoversClass(PHPUnitExtension::class)]
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class PHPUnitExtensionTest extends TestCase
{
    protected function tearDown(): void
    {
        FunctionMock::reset();
    }

    public function testRegisterFunctionMockUsesAttributeClassOverride(): void
    {
        PHPUnitExtension::registerFunctionMock(
            self::testMethod(ClassOverrideMockTest::class, 'testFoo'),
            new AttributeReader(),
        );

        self::assertFalse(\function_exists('Bizkit\PHPUnitFunctionMock\Fixtures\strlen'));
        self::assertFalse(\function_exists('Bizkit\PHPUnitFunctionMock\Tests\Fixtures\strlen'));
        self::assertTrue(\function_exists('Bizkit\PHPUnitFunctionMock\Fixtures\Target\strlen'));
        self::assertTrue(\function_exists('Bizkit\PHPUnitFunctionMock\Tests\Fixtures\Target\strlen'));
    }

    public function testRegisterFunctionMockUsesRepeatedClassAndMethodAttributes(): void
    {
        PHPUnitExtension::registerFunctionMock(
            self::testMethod(RepeatableAttributesTest::class, 'testFoo'),
            new AttributeReader(),
        );

        self::assertTrue(\function_exists('Bizkit\PHPUnitFunctionMock\Fixtures\file_exists'));
        self::assertTrue(\function_exists('Bizkit\PHPUnitFunctionMock\Tests\Fixtures\file_exists'));
        self::assertTrue(\function_exists('Bizkit\PHPUnitFunctionMock\Fixtures\is_file'));
        self::assertTrue(\function_exists('Bizkit\PHPUnitFunctionMock\Tests\Fixtures\is_file'));
        self::assertTrue(\function_exists('Bizkit\PHPUnitFunctionMock\Fixtures\is_dir'));
        self::assertTrue(\function_exists('Bizkit\PHPUnitFunctionMock\Tests\Fixtures\is_dir'));
        self::assertTrue(\function_exists('Bizkit\PHPUnitFunctionMock\Fixtures\is_executable'));
        self::assertTrue(\function_exists('Bizkit\PHPUnitFunctionMock\Tests\Fixtures\is_executable'));
        self::assertFalse(\function_exists('Bizkit\PHPUnitFunctionMock\Fixtures\strlen'));
        self::assertFalse(\function_exists('Bizkit\PHPUnitFunctionMock\Tests\Fixtures\strlen'));
        self::assertTrue(\function_exists('Bizkit\PHPUnitFunctionMock\Fixtures\Target\strlen'));
        self::assertTrue(\function_exists('Bizkit\PHPUnitFunctionMock\Tests\Fixtures\Target\strlen'));
    }

    public function testRegisterFunctionMockIgnoresNonMethodTests(): void
    {
        PHPUnitExtension::registerFunctionMock(self::nonMethodTestWithClassAndMethodNames(), new AttributeReader());

        self::assertFalse(\function_exists('Bizkit\PHPUnitFunctionMock\Fixtures\strlen'));
        self::assertFalse(\function_exists('Bizkit\PHPUnitFunctionMock\Tests\Fixtures\strlen'));
        self::assertFalse(\function_exists('Bizkit\PHPUnitFunctionMock\Fixtures\Target\strlen'));
        self::assertFalse(\function_exists('Bizkit\PHPUnitFunctionMock\Tests\Fixtures\Target\strlen'));
    }

    public function testDisableFunctionMockClearsMocksForAnnotatedMethod(): void
    {
        FunctionMock::mock('strlen', static fn (string $value): int => 99);

        self::assertSame(99, FunctionMock::run('strlen', 'foo'));

        PHPUnitExtension::disableFunctionMock(
            self::testMethod(ClassOverrideMockTest::class, 'testFoo'),
            new AttributeReader(),
        );

        self::assertSame(3, FunctionMock::run('strlen', 'foo'));
    }

    public function testDisableFunctionMockKeepsMocksForUnannotatedMethod(): void
    {
        FunctionMock::mock('strlen', static fn (string $value): int => 99);

        PHPUnitExtension::disableFunctionMock(
            self::testMethod(UnannotatedTest::class, 'testFoo'),
            new AttributeReader(),
        );

        self::assertSame(99, FunctionMock::run('strlen', 'foo'));
    }

    public function testDisableFunctionMockIgnoresNonMethodTests(): void
    {
        FunctionMock::mock('strlen', static fn (string $value): int => 99);

        PHPUnitExtension::disableFunctionMock(self::nonMethodTest(), new AttributeReader());

        self::assertSame(99, FunctionMock::run('strlen', 'foo'));
    }

    /**
     * @param class-string $className
     */
    private static function testMethod(string $className, string $methodName): TestMethod
    {
        return new TestMethod(
            $className,
            $methodName,
            __FILE__,
            __LINE__,
            new TestDox($className, $methodName, $methodName),
            MetadataCollection::fromArray([]),
            TestDataCollection::fromArray([]),
        );
    }

    private static function nonMethodTest(): CodeTest
    {
        if (10 === Version::majorVersionNumber()) {
            return new class(__FILE__) extends CodeTest {
                public function id(): string
                {
                    return 'non-method-test';
                }

                public function name(): string
                {
                    return 'non-method-test';
                }
            };
        }

        return eval('
        use PHPUnit\Event\Code\Test as CodeTest;

        readonly class MyCodeTest extends CodeTest
        {
            public function id(): string
            {
                return \'non-method-test\';
            }

            public function name(): string
            {
                return \'non-method-test\';
            }
        }

        return new MyCodeTest(__FILE__);
        ');
    }

    private static function nonMethodTestWithClassAndMethodNames(): CodeTest
    {
        if (10 === Version::majorVersionNumber()) {
            return new class(__FILE__) extends CodeTest {
                public function id(): string
                {
                    return 'non-method-test';
                }

                public function name(): string
                {
                    return 'non-method-test';
                }

                public function className(): string
                {
                    return ClassOverrideMockTest::class;
                }

                public function methodName(): string
                {
                    return 'testFoo';
                }
            };
        }

        return eval('
        use PHPUnit\Event\Code\Test as CodeTest;

        readonly class MyCodeTest extends CodeTest
        {
            public function id(): string
            {
                return \'non-method-test\';
            }

            public function name(): string
            {
                return \'non-method-test\';
            }

            public function className(): string
            {
                return ClassOverrideMockTest::class;
            }

            public function methodName(): string
            {
                return \'testFoo\';
            }
        }

        return new MyCodeTest(__FILE__);
        ');
    }
}
