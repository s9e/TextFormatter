<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMDocument;
use DOMXPath;
use s9e\TextFormatter\Configurator\Helpers\XPathHelper;

class FoldConstantXPathExpressions extends AbstractConstantFolding
{
	/**
	* @var DOMXPath
	*/
	protected $xpath;

	/**
	* Constructor
	*/
	public function __construct()
	{
		$this->xpath = new DOMXPath(new DOMDocument);
	}

	/**
	* {@inheritdoc}
	*/
	protected function getOptimizationPasses()
	{
		return [
			'(^(?:"[^"]*"|\'[^\']*\'|\\.[0-9]|[^"$&\'./:<=>@[\\]])++$)' => 'foldConstantXPathExpression'
		];
	}

	/**
	* Test whether given string contains an unsupported expression
	*
	* Will test for keywords, nodes and node functions as well as a few unsupported functions
	* such as format-number()
	*
	* @link https://www.w3.org/TR/xpath/#section-Node-Set-Functions
	* @link https://www.w3.org/TR/xslt#add-func
	*
	* @param  string $expr
	* @return bool
	*/
	protected function containsUnsupportedExpression($expr)
	{
		// Remove strings to avoid false-positives
		$expr = preg_replace('("[^"]*"|\'[^\']*\')', '', $expr);

		return (bool) preg_match('([a-z](?![a-z\\(])|(?:comment|text|processing-instruction|node|last|position|count|id|local-name|namespace-uri|name|document|key|format-number|current|unparsed-entity-uri|generate-id|system-property)\\()i', $expr);
	}

	/**
	* Evaluate and replace a constant XPath expression
	*
	* @param  array  $m
	* @return string
	*/
	protected function foldConstantXPathExpression(array $m)
	{
		$expr = $m[0];
		if (!$this->containsUnsupportedExpression($expr))
		{
			$foldedExpr = XPathHelper::export($this->xpath->evaluate($expr));
			if (strlen($foldedExpr) < strlen($expr))
			{
				$expr = $foldedExpr;
			}
		}

		return $expr;
	}
}