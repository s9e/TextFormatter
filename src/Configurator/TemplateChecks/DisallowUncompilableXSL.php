<?php declare(strict_types=1);

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;

use DOMElement;
use RuntimeException;

class DisallowUncompilableXSL extends AbstractXSLSupportCheck
{
	protected $supportedElements = ['apply-templates', 'attribute', 'choose', 'comment', 'copy-of', 'element', 'if', 'otherwise', 'text', 'value-of', 'when'];

	protected $supportedFunctions = ['boolean', 'ceiling', 'concat', 'contains', 'count', 'current', 'false', 'floor', 'last', 'local-name', 'name', 'normalize-space', 'not', 'number', 'position', 'round', 'starts-with', 'string', 'string-length', 'substring', 'substring-after', 'substring-before', 'sum', 'system-property', 'translate', 'true'];

	protected function checkXslCopyOfElement(DOMElement $copyOf): void
	{
		$expr = $copyOf->getAttribute('select');
		if (!preg_match('#^@[-\\w]+(?:\\s*\\|\\s*@[-\\w]+)*$#', $expr) && $expr !== '@*')
		{
			throw new RuntimeException("Unsupported xsl:copy-of expression '" . $expr . "'");
		}
	}
}