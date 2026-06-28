<?php

declare(strict_types=1);

namespace Bizkit\PHPUnitFunctionMock\Tests\Unit\Metadata;

use Bizkit\PHPUnitFunctionMock\Attribute\RegisterFunctionMock;
use Bizkit\PHPUnitFunctionMock\Metadata\AttributeReader;
use Bizkit\PHPUnitFunctionMock\Tests\Fixtures\ClassOverrideMockTest;
use Bizkit\PHPUnitFunctionMock\Tests\Fixtures\MockTest;
use Bizkit\PHPUnitFunctionMock\Tests\Fixtures\RepeatableAttributesTest;
use Bizkit\PHPUnitFunctionMock\Tests\Fixtures\Target\FunctionMockTarget;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AttributeReader::class)]
final class AttributeReaderTest extends TestCase
{
    public function testForClass(): void
    {
        $reader = new AttributeReader();

        $attributes = $reader->forClass(MockTest::class);

        self::assertCount(1, $attributes);
        self::assertSame(['file_exists', 'is_file'], $attributes[0]->functions);
        self::assertIsCached($attributes, $reader, MockTest::class);
    }

    public function testForMethod(): void
    {
        $reader = new AttributeReader();

        $attributes = $reader->forMethod(MockTest::class, 'testFoo');

        self::assertCount(1, $attributes);
        self::assertSame(['is_dir'], $attributes[0]->functions);
        self::assertIsCached($attributes, $reader, MockTest::class.'::testFoo');
    }

    public function testForClassAndMethod(): void
    {
        $reader = new AttributeReader();

        $attributes = $reader->forClassAndMethod(MockTest::class, 'testFoo');

        self::assertCount(2, $attributes);
        self::assertSame(['file_exists', 'is_file'], $attributes[0]->functions);
        self::assertSame(['is_dir'], $attributes[1]->functions);
        self::assertIsCached([$attributes[0]], $reader, MockTest::class);
        self::assertIsCached([$attributes[1]], $reader, MockTest::class.'::testFoo');
    }

    public function testReadsRepeatedAttributes(): void
    {
        $reader = new AttributeReader();

        $attributes = $reader->forClass(RepeatableAttributesTest::class);

        self::assertCount(2, $attributes);
        self::assertSame(['file_exists'], $attributes[0]->functions);
        self::assertSame(['is_file', 'is_dir'], $attributes[1]->functions);
    }

    public function testReadsRepeatedClassAndMethodAttributesInOrder(): void
    {
        $reader = new AttributeReader();

        $attributes = $reader->forClassAndMethod(RepeatableAttributesTest::class, 'testFoo');

        self::assertCount(4, $attributes);
        self::assertSame(['file_exists'], $attributes[0]->functions);
        self::assertNull($attributes[0]->class);
        self::assertSame(['is_file', 'is_dir'], $attributes[1]->functions);
        self::assertNull($attributes[1]->class);
        self::assertSame(['is_executable'], $attributes[2]->functions);
        self::assertNull($attributes[2]->class);
        self::assertSame(['strlen'], $attributes[3]->functions);
        self::assertSame(FunctionMockTarget::class, $attributes[3]->class);
    }

    public function testReadsClassOverride(): void
    {
        $reader = new AttributeReader();

        $attributes = $reader->forClass(ClassOverrideMockTest::class);

        self::assertCount(1, $attributes);
        self::assertSame(['strlen'], $attributes[0]->functions);
        self::assertSame(FunctionMockTarget::class, $attributes[0]->class);
    }

    /**
     * @param list<RegisterFunctionMock> $expected
     */
    private static function assertIsCached(array $expected, AttributeReader $reader, string $key): void
    {
        $reflection = new \ReflectionProperty(AttributeReader::class, 'cache');
        $cache = $reflection->getValue($reader);

        self::assertArrayHasKey($key, $cache);
        self::assertSame($expected, $cache[$key]);
    }
}
