<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use  s9e\SweetDOM\Text;

/**
* Remove all inter-element whitespace except for single space characters
*/
class PreserveSingleSpaces extends AbstractNormalization
{
	/**
	* {@inheritdoc}
	*/
	protected array $queries = ['//text()[. = " "][not(parent::xsl:text)]'];

	/**
	* {@inheritdoc}
	*/
	protected function normalizeText(Text $node): void
	{
		$node->replaceWithXslText(' ');
	}
}