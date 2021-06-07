<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\CodeGenerators;

interface CodeGeneratorInterface
{
	public function bufferBuffer(int $id, int $source): string;
	public function bufferLiteral(int $id, string $value): string;
	public function bufferTransform(int $id, string $escape, ?string $select = null): string;
	public function bufferXPath(int $id, string $select, string $escape): string;
	public function clearBuffer(int $id): string;
	public function conditionMatchDefault(): string;
	public function conditionMatchLiteral(string $value): string;
	public function conditionTestDefault(string $test): string;
	public function conditionTestXPath(string $test): string;
}