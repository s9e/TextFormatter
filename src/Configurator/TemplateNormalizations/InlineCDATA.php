<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use s9e\SweetDOM\CdataSection;

class InlineCDATA extends AbstractNormalization
{
	/**
	* {@inheritdoc}
	*/
	protected array $queries = ['//text()'];

	/**
	* {@inheritdoc}
	*/
	protected function normalizeCdataSection(CdataSection $node): void
	{
		$node->replaceWith($this->createPolymorphicText($node->textContent));
	}
}