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
	public function bufferBuffer(Element $context, int $id, int $source): string
	{
		return '$this->buffers[' . $id . '] .= $this->buffers[' . $id . ']';
	}

	public function bufferLiteral(Element $context, int $id, string $value):
	{
		return '$this->buffers[' . $id . '] .= ' . $this->export($value);
	}

	public function bufferTransform(Element $context, int $id, string $escape, ?string $select = null): string
	{
		$php = '$this->buffers[' . $id . '] .= $this->escape($this->transform(';
		if (isset($select))
		{
			$php .= $this->export($select);
		}
		$php .= '), ' . $this->export($escape) . ')';
		
		return $php;
	}

	public function bufferXPath(Element $context, int $id, string $select, string $escape): string;

	public function clearBuffer(Element $context, int $id): string;
	{
		return '$this->buffers[' . $id . "] .= ''";
	}

	public function conditionMatchDefault(Element $context): string;
	public function conditionMatchLiteral(Element $context, string $value): string;
	public function conditionTestDefault(Element $context, string $test): string;
	public function conditionTestXPath(Element $context, string $test): string;

	/**
	* 
	*
	* @return void
	*/
	protected function export($value): string
	{
		return var_export($this->export);
	}
}