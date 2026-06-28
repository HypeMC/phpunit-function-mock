<?php

declare(strict_types=1);

namespace Bizkit\PHPUnitFunctionMock;

use Bizkit\PHPUnitFunctionMock\Metadata\AttributeReader;
use PHPUnit\Event\Code\Test;
use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\Test\BeforeTestMethodErrored;
use PHPUnit\Event\Test\BeforeTestMethodErroredSubscriber;
use PHPUnit\Event\Test\Errored;
use PHPUnit\Event\Test\ErroredSubscriber;
use PHPUnit\Event\Test\Finished;
use PHPUnit\Event\Test\FinishedSubscriber;
use PHPUnit\Event\Test\Skipped;
use PHPUnit\Event\Test\SkippedSubscriber;
use PHPUnit\Event\TestSuite\Loaded;
use PHPUnit\Event\TestSuite\LoadedSubscriber;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

final class PHPUnitExtension implements Extension
{
    /**
     * @codeCoverageIgnore
     */
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $reader = new AttributeReader();

        $facade->registerSubscriber(new class($reader) implements LoadedSubscriber {
            public function __construct(
                private AttributeReader $reader,
            ) {
            }

            public function notify(Loaded $event): void
            {
                foreach ($event->testSuite()->tests() as $test) {
                    PHPUnitExtension::registerFunctionMock($test, $this->reader);
                }
            }
        });

        $facade->registerSubscriber(new class($reader) implements FinishedSubscriber {
            public function __construct(private AttributeReader $reader)
            {
            }

            public function notify(Finished $event): void
            {
                PHPUnitExtension::disableFunctionMock($event->test(), $this->reader);
            }
        });

        $facade->registerSubscriber(new class($reader) implements ErroredSubscriber {
            public function __construct(private AttributeReader $reader)
            {
            }

            public function notify(Errored $event): void
            {
                PHPUnitExtension::disableFunctionMock($event->test(), $this->reader);
            }
        });

        $facade->registerSubscriber(new class($reader) implements SkippedSubscriber {
            public function __construct(private AttributeReader $reader)
            {
            }

            public function notify(Skipped $event): void
            {
                PHPUnitExtension::disableFunctionMock($event->test(), $this->reader);
            }
        });

        if (\interface_exists(BeforeTestMethodErroredSubscriber::class)) {
            $facade->registerSubscriber(new class($reader) implements BeforeTestMethodErroredSubscriber {
                public function __construct(private AttributeReader $reader)
                {
                }

                public function notify(BeforeTestMethodErrored $event): void
                {
                    if (\method_exists($event, 'test')) {
                        PHPUnitExtension::disableFunctionMock($event->test(), $this->reader);
                    } else {
                        FunctionMock::reset();
                    }
                }
            });
        }
    }

    /**
     * @internal
     */
    public static function registerFunctionMock(Test $test, AttributeReader $reader): void
    {
        if (!$test instanceof TestMethod) {
            return;
        }

        foreach ($reader->forClassAndMethod($test->className(), $test->methodName()) as $attribute) {
            FunctionMock::register($attribute->class ?? $test->className(), $attribute->functions);
        }
    }

    /**
     * @internal
     */
    public static function disableFunctionMock(Test $test, AttributeReader $reader): void
    {
        if (!$test instanceof TestMethod) {
            return;
        }

        if ([] === $reader->forClassAndMethod($test->className(), $test->methodName())) {
            return;
        }

        FunctionMock::reset();
    }
}
