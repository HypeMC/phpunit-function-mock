<?php

declare(strict_types=1);

namespace Bizkit\PHPUnitFunctionMock\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class RegisterFunctionMock
{
    /**
     * @var list<callable-string>
     */
    public readonly array $functions;

    /**
     * @param callable-string|list<callable-string> $functions
     * @param ?class-string                         $class
     */
    public function __construct(
        string|array $functions,
        public readonly ?string $class = null,
    ) {
        /** @var list<callable-string> $functions */
        $functions = (array) $functions;
        foreach ($functions as $i => $function) {
            if (!\is_string($function)) {
                throw new \TypeError(\sprintf('Argument 1 passed to %s() must be a list of strings, element %d is %s.', __METHOD__, $i, \get_debug_type($function)));
            }
        }

        $this->functions = $functions;
    }
}
