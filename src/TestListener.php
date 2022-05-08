<?php

declare(strict_types=1);

namespace Bizkit\FunctionMock;

use Bizkit\FunctionMock\Attribute\Reader;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestListener as TestListenerInterface;
use PHPUnit\Framework\TestListenerDefaultImplementation;
use PHPUnit\Framework\TestSuite;

class TestListener implements TestListenerInterface
{
    use TestListenerDefaultImplementation;

    private bool $runsInSeparateProcess = false;

    /**
     * @param array<string, string|list<string>> $mockedNamespaces
     */
    public function __construct(array $mockedNamespaces = [])
    {
        $mockedClasses = [];

        foreach ($mockedNamespaces as $function => $namespaces) {
            foreach ((array) $namespaces as $ns) {
                $class = $ns.'\DummyClass';
                $mockedClasses[$class][] = $function;
            }
        }

        foreach ($mockedClasses as $class => $functions) {
            FunctionMock::register($class, $functions);
        }
    }

    public function startTestSuite(TestSuite $suite): void
    {
        $testSuites = [$suite];
        for ($i = 0; isset($testSuites[$i]); ++$i) {
            foreach ($testSuites[$i]->tests() as $test) {
                if ($test instanceof TestSuite) {
                    if (!\class_exists($test->getName(), false)) {
                        $testSuites[] = $test;
                        continue;
                    }
                    if ([] !== $mockedFunctions = Reader::instance()->forClass($test->getName())) {
                        FunctionMock::register($test->getName(), $mockedFunctions);
                    }
                }
            }
        }
    }

    public function startTest(Test $test): void
    {
        if (!$test instanceof TestCase) {
            return;
        }

        if ($this->willBeIsolated($test)) {
            $this->runsInSeparateProcess = true;
        }

        if (
            !$this->runsInSeparateProcess
            &&
            [] !== $mockedFunctions = Reader::instance()->forClassAndMethod($test::class, $test->getName(false))
        ) {
            FunctionMock::register($test::class, $mockedFunctions);
        }
    }

    public function endTest(Test $test, float $time): void
    {
        if (!$test instanceof TestCase) {
            return;
        }

        if (!$this->runsInSeparateProcess) {
            if ([] !== $mockedFunctions = Reader::instance()->forClassAndMethod($test::class, $test->getName(false))) {
                FunctionMock::withMockedFunctions(\array_fill_keys($mockedFunctions, null));
            }
        } else {
            $this->runsInSeparateProcess = false;
        }
    }

    private function willBeIsolated(TestCase $test): bool
    {
        if ($test->isInIsolation()) {
            return false;
        }

        $r = new \ReflectionProperty($test, 'runTestInSeparateProcess');
        $r->setAccessible(true);

        return $r->getValue($test);
    }
}
