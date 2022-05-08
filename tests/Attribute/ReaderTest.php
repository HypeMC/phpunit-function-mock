<?php

declare(strict_types=1);

namespace Bizkit\FunctionMock\Tests\Attribute;

use Bizkit\FunctionMock\Attribute\Reader;
use Bizkit\FunctionMock\Tests\Attribute\Fixtures\MockTest;
use Bizkit\FunctionMock\Tests\TestCase;

/**
 * @covers \Bizkit\FunctionMock\Attribute\MockedFunctions
 * @covers \Bizkit\FunctionMock\Attribute\Reader
 *
 * @runTestsInSeparateProcesses
 */
final class ReaderTest extends TestCase
{
    public function testSameInstanceIsAlwaysReturned(): void
    {
        $this->assertSame(Reader::instance(), Reader::instance());
    }

    public function testForClass(): void
    {
        $expected = ['file_exists', 'is_file'];

        $this->assertSame($expected, Reader::instance()->forClass(MockTest::class));
        $this->assertIsCachedForClass($expected, MockTest::class);
    }

    public function testForMethod(): void
    {
        $expected = ['is_dir'];
        $method = 'testFoo';

        $this->assertSame($expected, Reader::instance()->forMethod(MockTest::class, $method));
        $this->assertIsCachedForMethod($expected, MockTest::class, $method);
    }

    public function testForClassAndMethod(): void
    {
        $method = 'testFoo';

        $this->assertSame(
            ['file_exists', 'is_file', 'is_dir'],
            Reader::instance()->forClassAndMethod(MockTest::class, $method)
        );
        $this->assertIsCachedForClass(['file_exists', 'is_file'], MockTest::class);
        $this->assertIsCachedForMethod(['is_dir'], MockTest::class, $method);
    }

    /**
     * @param list<string> $expected
     * @param class-string $class
     */
    private function assertIsCachedForClass(array $expected, string $class): void
    {
        $r = new \ReflectionProperty(Reader::class, 'classMockedFunctions');
        $r->setAccessible(true);

        $this->assertSame([$class => $expected], $r->getValue(Reader::instance()));
    }

    /**
     * @param list<string> $expected
     * @param class-string $class
     */
    private function assertIsCachedForMethod(array $expected, string $class, string $method): void
    {
        $r = new \ReflectionProperty(Reader::class, 'methodMockedFunctions');
        $r->setAccessible(true);

        $this->assertSame([$class => [$method => $expected]], $r->getValue(Reader::instance()));
    }
}
