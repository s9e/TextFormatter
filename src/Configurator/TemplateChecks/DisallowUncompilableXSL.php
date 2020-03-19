<?php declare(strict_types=1);

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;

use DOMElement;

class DisallowUncompilableXSL extends AbstractXSLSupportCheck
{
	/**
	* @var string[] 
	*/
	protected $supportedElements = [
		'apply-templates',
		'attribute',
		'choose',
		'comment',
		'copy-of',
		'element',
		'if',
		'otherwise',
		'text',
		'value-of',
		'when'
	];

	protected function checkXslCopyOfElement(DOMElement $copyOf): void
	{
		$expr = $node->getAttribute('select');
		if (!preg_match('#^@[-\\w]+(?:\\s*\\|\\s*@[-\\w]+)*$#', $expr) && $expr !== '@*')
		{
			throw new RuntimeException("Unsupported xsl:copy-of expression '" . $expr . "'");
		}
	}
}