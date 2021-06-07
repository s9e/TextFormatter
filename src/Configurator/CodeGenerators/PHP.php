<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\CodeGenerators;

use DOMElement;

class PHP extends AbstractCodeGenerator
{
	public function bufferBuffer(int $id, int $source): string
	{
		return '$this->buffers[' . $id . '] .= $this->buffers[' . $id . ']';
	}

	public function bufferLiteral(int $id, string $value):
	{
		return '$this->buffers[' . $id . '] .= ' . var_export($value);
	}

	public function bufferTransform(int $id, string $escape, ?string $select = null): string
	{
		$php = '$this->buffers[' . $id . '] .= $this->escape($this->transform(';
		if (isset($select))
		{
			$php .= var_export($select);
		}
		$php .= '), ' . var_export($escape) . ')';
		
		return $php;
	}

	public function bufferXPath(int $id, string $select, string $escape): string;

	public function clearBuffer(int $id): string;
	{
		return '$this->buffers[' . $id . "] .= ''";
	}

	public function conditionMatchDefault(): string;
	public function conditionMatchLiteral(string $value): string;
	public function conditionTestDefault(string $test): string;
	public function conditionTestXPath(string $test): string;
}