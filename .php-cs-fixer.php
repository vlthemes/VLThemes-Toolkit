<?php

$finder = PhpCsFixer\Finder::create()
	->in(__DIR__)
	->exclude([
		'vendor',
		'node_modules',
		'inc/demo',
	])
	->name('*.php')
	->notName('*.blade.php')
	->ignoreDotFiles(true)
	->ignoreVCS(true);

return (new PhpCsFixer\Config())
	->setRiskyAllowed(true)
	->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
	->setRules([
		// Основной современный стандарт
		'@PSR12' => true,

		// Короткий синтаксис массивов — это must-have в 2025 (кроме старого WordPress)
		'array_syntax' => ['syntax' => 'short'],

		// Идеальное выравнивание => и = в массивах и присваиваниях
		'binary_operator_spaces' => [
			'operators' => [
				'=>' => 'align_single_space_minimal',
				'='  => 'align_single_space_minimal',
			],
		],

		// Запятая в конце многострочных массивов (очень важно!)
		'trailing_comma_in_multiline' => ['elements' => ['arrays', 'arguments', 'parameters']],

		// Отступы внутри массивов
		'array_indentation' => true,

		// Один пробел после concat .
		'concat_space' => ['spacing' => 'one'],

		// Умная работа с пустыми строками
		'no_extra_blank_lines' => [
			'tokens' => ['extra', 'throw', 'use', 'break', 'continue', 'return'],
		],
		'blank_line_before_statement' => [
			'statements' => ['return', 'throw', 'try', 'if', 'switch'],
		],

		// PHPDoc — красиво и строго
		'phpdoc_align'      => ['align' => 'vertical'],
		'phpdoc_separation' => ['groups' => [
			['var', 'property', 'property-read', 'property-write'],
			['param'],
			['return'],
			['throws'],
		]],
		'phpdoc_order'      => true,
		'phpdoc_to_comment' => false, // оставляем нормальные докблоки

		// Импорты
		'ordered_imports'   => ['sort_algorithm' => 'alpha', 'imports_order' => ['class', 'function', 'const']],
		'no_unused_imports' => true,

		// Полезные современные правила
		'single_quote'               => true,
		'no_superfluous_phpdoc_tags' => ['remove_inheritdoc' => true],
		// 'global_namespace_import'                          => ['import_classes' => true, 'import_constants' => true, 'import_functions' => true],
		'fully_qualified_strict_types'                     => true,
		'heredoc_indentation'                              => true,
		'heredoc_to_nowdoc'                                => true,
		'nullable_type_declaration_for_default_null_value' => true,
		'void_return'                                      => true,
		// 'native_function_invocation'                       => ['include' => ['@all']], // Отключено для WordPress
		'no_useless_else'      => true,
		'no_useless_return'    => true,
		'simplified_if_return' => true,
		'yoda_style'           => ['equal' => false, 'identical' => false, 'less_and_greater' => false],

		// Отключаем только то, что реально мешает
		'blank_line_after_opening_tag' => false,
		'single_blank_line_at_eof'     => true,
	])
	->setIndent('	')
	->setLineEnding("\n")
	->setFinder($finder);
