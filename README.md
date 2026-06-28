# Bizkit PHPUnit Function Mock

[![Latest Stable Version](https://poser.pugx.org/bizkit/phpunit-function-mock/v/stable)](https://packagist.org/packages/bizkit/phpunit-function-mock)
[![Build Status](https://github.com/HypeMC/phpunit-function-mock/workflows/Tests/badge.svg)](https://github.com/HypeMC/phpunit-function-mock/actions)
[![Code Coverage](https://codecov.io/gh/HypeMC/phpunit-function-mock/branch/1.x/graph/badge.svg)](https://codecov.io/gh/HypeMC/phpunit-function-mock)
[![License](https://poser.pugx.org/bizkit/phpunit-function-mock/license)](https://packagist.org/packages/bizkit/phpunit-function-mock)

Provides a small PHPUnit extension for mocking native PHP functions from tests.

The idea is based on Symfony PHPUnit Bridge's `ClockMock` and `DnsMock`: register a function in the tested namespace and
dispatch that function through a test-controlled mock. See
Symfony's [Clock Mocking](https://symfony.com/doc/current/components/phpunit_bridge.html#clock-mocking)
and [DNS-sensitive tests](https://symfony.com/doc/current/components/phpunit_bridge.html#dns-sensitive-tests)
documentation for the original pattern.

## Requirements

* [PHP 8.1](https://www.php.net/releases/8_1_0.php) or higher
* PHPUnit 10.5, 11.5, 12.5, 13, or higher

## Installation

```bash
composer require --dev bizkit/phpunit-function-mock
```

## PHPUnit Configuration

Register the extension in your PHPUnit configuration:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="vendor/autoload.php">
    <extensions>
        <bootstrap class="Bizkit\PHPUnitFunctionMock\PHPUnitExtension" />
    </extensions>
</phpunit>
```

## Usage

Use `#[RegisterFunctionMock]` on the test class or method to register the function hook. Configure the callable in the
test body with `FunctionMock`.

```php
<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\TokenGenerator;
use Bizkit\PHPUnitFunctionMock\Attribute\RegisterFunctionMock;
use Bizkit\PHPUnitFunctionMock\FunctionMock;
use PHPUnit\Framework\TestCase;

#[RegisterFunctionMock('random_int')]
final class TokenGeneratorTest extends TestCase
{
    public function testGenerateToken(): void
    {
        FunctionMock::mock('random_int', static fn (int $min, int $max): int => 1234);

        self::assertSame('token-1234', new TokenGenerator()->generate());
    }
}
```

When the test suite is loaded, the extension registers a `random_int()` function in the test namespace and in the
matching application namespace with `Tests` removed. For example, `App\Tests\Service\TokenGeneratorTest` also registers
mocks for `App\Service`.

### Multiple Functions

Pass a list when a test needs several functions:

```php
#[RegisterFunctionMock(['file_exists', 'is_file'])]
final class FileLoaderTest extends TestCase
{
    public function testLoad(): void
    {
        FunctionMock::mockMany([
            'file_exists' => static fn (string $path): bool => true,
            'is_file' => static fn (string $path): bool => true,
        ]);

        // ...
    }
}
```

`#[RegisterFunctionMock]` is repeatable, so the same class or method can declare multiple `#[RegisterFunctionMock]`
attributes.

### Class Override

Use `class:` when the tested code lives in a namespace that cannot be inferred from the test class name.

```php
use App\Service\TokenGenerator;
use Bizkit\PHPUnitFunctionMock\Attribute\RegisterFunctionMock;

#[RegisterFunctionMock('random_int', class: TokenGenerator::class)]
final class TokenGeneratorTest extends TestCase
{
}
```

The function is registered in the namespace of the class passed to `class:`.

### Manual Registration

You can also register functions without PHPUnit attributes:

```php
use App\Service\TokenGenerator;
use Bizkit\PHPUnitFunctionMock\FunctionMock;

FunctionMock::register(TokenGenerator::class, 'random_int');
FunctionMock::mock('random_int', static fn (int $min, int $max): int => 1234);
```

Pass `null` to remove a single mock and restore native fallback for that function:

```php
FunctionMock::mock('random_int', null);
```

## Cleanup

Mocks are global process state. The PHPUnit extension clears configured callables after annotated tests finish, error,
or skip. If you configure mocks manually in unannotated tests, clear them yourself:

```php
protected function tearDown(): void
{
    FunctionMock::reset();
}
```

## Known Limitations

This library relies on
PHP's [fallback to the global namespace for functions](https://www.php.net/manual/en/language.namespaces.fallback.php).
In namespaced code, an unqualified function call such as `random_int()` first checks for a function in the current
namespace, then falls back to `\random_int()`.

Because of that, mocks only work for unqualified function calls:

```php
namespace App\Service;

random_int(1, 10); // can be mocked
\random_int(1, 10); // cannot be mocked by this library
```

The namespaced function must also be registered before the target namespace calls that function for the first time. PHP
can cache the first function resolution in its literal cache, so if `App\Service\random_int()` does not exist yet and
PHP resolves the first call to `\random_int()`, later defining `App\Service\random_int()` may not affect that
already-compiled call site. See PHP bug [#64346](https://bugs.php.net/bug.php?id=64346). This is why the attribute-based
extension registers functions when PHPUnit loads the test suite, before test methods execute.

Other limitations:

- Generated namespaced functions cannot be removed once declared. Only their configured callables are reset.
- Tests that register the same namespaced functions should run in separate processes when isolation matters.

## Versioning

This project follows [Semantic Versioning 2.0.0](https://semver.org/).

## Reporting Issues

Use the project's issue tracker to report bugs or request improvements.

## License

See the [LICENSE](LICENSE) file for details (MIT).
