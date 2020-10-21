<?php declare(strict_types=1);
$finder = PhpCsFixer\Finder::create()
    ->ignoreUnreadableDirs()
    ->exclude([
        __DIR__ . '/tests/Integration/ScriptRuntime/_non_writable',
    ])
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests')
    ->append([__FILE__]);

return PhpCsFixer\Config::create()
    ->setRules([
        '@Symfony' => true,

        // Fix declare style
        'blank_line_after_opening_tag' => false,

        // override @Symonfy
        'phpdoc_align' => false,
        'phpdoc_separation' => false,
        'yoda_style' => false,
        'phpdoc_summary' => false,
        'increment_style' => false,
        'php_unit_fqcn_annotation' => false,
        'single_line_throw' => false,

        'array_syntax' => [
            'syntax' => 'short',
        ],
        'class_definition' => [
            'single_line' => true,
        ],
        'comment_to_phpdoc' => true,
        'concat_space' => [
            'spacing' => 'one',
        ],
        'declare_strict_types' => true,
        'dir_constant' => true,
        'is_null' => true,
        'no_null_property_initialization' => true,
        'no_superfluous_phpdoc_tags' => true,
        'no_useless_return' => true,
        'no_useless_else' => true,
        'multiline_whitespace_before_semicolons' => true,
        'mb_str_functions' => true,
        'ordered_class_elements' => false,
        'ordered_imports' => [
            'imports_order' => [
                'class',
                'function',
                'const',
            ],
        ],
        'native_function_invocation' => [
            'exclude' => [
                'call_user_func_array',
            ],
        ],
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => false,
            'import_functions' => true,
        ],
        'php_unit_ordered_covers' => true,
        'php_unit_namespaced' => true,
        'php_unit_construct' => true,
        'phpdoc_add_missing_param_annotation' => [
            'only_untyped' => true,
        ],
        'phpdoc_order' => true,
        'phpdoc_var_annotation_correct_order' => true,
        'strict_comparison' => true,
        'strict_param' => true,
        'general_phpdoc_annotation_remove' => [
            'annotations' => ['inheritdoc'],
        ],
        'class_attributes_separation' => [
            'elements' => [
                'method',
                'property',
            ],
        ],
        'void_return' => true,
        'php_unit_test_case_static_method_calls' => [
            'call_type' => 'self',
        ],
        'yoda_style' => [
            'equal' => false,
            'identical' => false,
            'less_and_greater' => false,
        ],
        'nullable_type_declaration_for_default_null_value' => true,
    ])
    ->setRiskyAllowed(true)
    ->setUsingCache(true)
    ->setFinder($finder);
