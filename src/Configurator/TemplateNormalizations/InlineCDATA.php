<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) The s9e authors
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
	protected function normalizeCdataSection(CdataSection $cdata): void
	{
		$cdata->replaceWith($this->createPolymorphicText($cdata->textContent));
	}
}