<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\CodeGenerators;

use s9e\SweetDOM\Element;

interface CodeGeneratorInterface
{
	public function bufferBuffer(Element $context, int $id, int $source): string;
	public function bufferLiteral(Element $context, int $id, string $value): string;
	public function bufferTransform(Element $context, int $id, string $escape, string $select = null): string;
	public function bufferXPath(Element $context, int $id, string $select, string $escape): string;
	public function clearBuffer(Element $context, int $id): string;
	public function conditionMatchDefault(Element $context): string;
	public function conditionMatchLiteral(Element $context, string $value): string;
	public function conditionTestDefault(Element $context, string $test): string;
	public function conditionTestXPath(Element $context, string $test): string;
}