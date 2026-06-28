<?php

declare(strict_types=1);

use PhpCsFixer\Config\RuleCustomisationPolicyInterface;
use PhpCsFixer\Fixer\FunctionNotation\NativeFunctionInvocationFixer;

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->append([__FILE__])
;

return (new PhpCsFixer\Config())
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setUsingCache(true)
    ->setRules([
        '@PHP8x1Migration' => true,
        '@PHPUnit10x0Migration:risky' => true,
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'attribute_empty_parentheses' => true,
        'declare_strict_types' => true,
        'heredoc_to_nowdoc' => true,
        'no_superfluous_phpdoc_tags' => true,
        'php_unit_test_case_static_method_calls' => ['call_type' => 'self'],
        'self_static_accessor' => true,
        'single_line_throw' => false,
        'trailing_comma_in_multiline' => [
            'after_heredoc' => true,
            'elements' => ['arguments', 'array_destructuring', 'arrays', 'match', 'parameters'],
        ],
        'whitespace_after_comma_in_array' => ['ensure_single_space' => true],
    ])
    ->setRiskyAllowed(true)
    ->setRuleCustomisationPolicy(new class implements RuleCustomisationPolicyInterface {
        public function getPolicyVersionForCache(): string
        {
            return hash_file('xxh128', __FILE__);
        }

        public function getRuleCustomisers(): array
        {
            return [
                'native_function_invocation' => static function (SplFileInfo $file) {
                    if (str_starts_with($file->getPathname(), __DIR__.'/src/')) {
                        $fixer = new NativeFunctionInvocationFixer();
                        $fixer->configure(['include' => ['@all']]);

                        return $fixer;
                    }

                    return true;
                },
            ];
        }
    })
    ->setFinder($finder)
;
