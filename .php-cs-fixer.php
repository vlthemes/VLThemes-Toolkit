<?php

declare(strict_types=1);
use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

$finder = Finder::create()
	->in( __DIR__ )
	->exclude( [
		'vendor',
		'node_modules',
		'inc/demo',
	] )
	->name( '*.php' )
	->notName( '*.blade.php' )
	->ignoreDotFiles( true )
	->ignoreVCS( true )
;

return ( new Config() )
	->setRiskyAllowed( true )
	->setParallelConfig( ParallelConfigFactory::detect() )
	->setRules( [
		'phpdoc_align'                  => true,
		'phpdoc_annotation_without_dot' => true,
		'phpdoc_indent'                 => true,
		'no_blank_lines_after_phpdoc'   => true,
		'ordered_class_elements'        => true,
		'blank_line_before_statement'   => [
			'statements' => [
				'break',
				'case',
				'continue',
				'declare',
				'default',
				'exit',
				'goto',
				'include',
				'include_once',
				'phpdoc',
				'require',
				'require_once',
				'return',
				'switch',
				'throw',
				'try',
				'yield',
				'yield_from',
			],
		],
		'no_extra_blank_lines' => [
			'tokens' => [
				'attribute',
				'break',
				'case',
				'continue',
				'curly_brace_block',
				'default',
				'extra',
				'parenthesis_brace_block',
				'return',
				'square_brace_block',
				'switch',
				'throw',
				'use',
			],
		],
		'control_structure_braces'                => true,
		'control_structure_continuation_position' => ['position' => 'same_line'],
		'declare_parentheses'                     => true,
		'no_multiple_statements_per_line'         => true,
		'braces_position'                         => [
			'classes_opening_brace'                     => 'same_line',
			'functions_opening_brace'                   => 'same_line',
			'anonymous_functions_opening_brace'         => 'same_line',
			'control_structures_opening_brace'          => 'same_line',
			'anonymous_classes_opening_brace'           => 'same_line',
			'allow_single_line_empty_anonymous_classes' => true,
			'allow_single_line_anonymous_functions'     => true,
		],
		'statement_indentation'           => true,
		'unary_operator_spaces'           => true,
		'whitespace_after_comma_in_array' => true,
		'yoda_style'                      => true,
		'array_syntax'                    => ['syntax' => 'short'],
		'binary_operator_spaces'          => [
			'default'   => 'single_space',
			'operators' => [
				'=>' => 'align_single_space_minimal',
				'='  => 'align_single_space_minimal',
			],
		],
		'blank_line_after_namespace' => true,
		'ternary_operator_spaces'    => true,
		'spaces_inside_parentheses'  => [
			'space' => 'single'
		],
		'single_space_around_construct' => [
			'constructs_followed_by_a_single_space' => [
				'abstract',
				'as',
				'case',
				'catch',
				'class',
				'do',
				'else',
				'elseif',
				'final',
				'for',
				'foreach',
				'function',
				'if',
				'interface',
				'namespace',
				'private',
				'protected',
				'public',
				'static',
				'switch',
				'trait',
				'try',
				'use_lambda',
				'while',
			],
			'constructs_preceded_by_a_single_space' => [
				'as',
				'else',
				'elseif',
				'use_lambda',
			],
		],
	] )
	->setIndent( '	' )
	->setLineEnding( "\n" )
	->setFinder( $finder );
