<?php

declare(strict_types=1);

namespace Bizkit\FunctionMock\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class MockedFunctions
{
    /** @var list<string> */
    private array $functions;

    public function __construct(string ...$functions)
    {
        $this->functions = $functions;
    }

    /**
     * @return list<string>
     */
    public function getFunctions(): array
    {
        return $this->functions;
    }
}
