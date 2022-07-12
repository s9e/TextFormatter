<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use Exception;
use s9e\TextFormatter\Utils\XPath;

class FoldConstantXPathExpressions extends AbstractConstantFolding
{
	/**
	* @var string[] List of supported XPath functions
	*/
	protected $supportedFunctions = [
		'boolean',
		'ceiling',
		'concat',
		'contains',
		'floor',
		'normalize-space',
		'not',
		'number',
		'round',
		'starts-with',
		'string',
		'string-length',
		'substring',
		'substring-after',
		'substring-before',
		'translate'
	];

	/**
	* {@inheritdoc}
	*/
	protected function getOptimizationPasses()
	{
		return [
			'(^(?:"[^"]*"|\'[^\']*\'|\\.[0-9]|[^"$&\'./:@[\\]])++$)' => 'foldConstantXPathExpression'
		];
	}

	/**
	* Evaluate given expression without raising any warnings
	*
	* @param  string $expr
	* @return mixed
	*/
	protected function evaluate($expr)
	{
		$useErrors = libxml_use_internal_errors(true);
		$result    = $this->xpath->evaluate($expr);
		libxml_use_internal_errors($useErrors);

		return $result;
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
		if ($this->isConstantExpression($expr))
		{
			try
			{
				$result     = $this->evaluate($expr);
				$foldedExpr = XPath::export($result);
				$expr       = $this->selectReplacement($expr, $foldedExpr);
			}
			catch (Exception $e)
			{
				// Do nothing
			}
		}

		return $expr;
	}

	/**
	* Test whether given expression seems to be constant
	*
	* @param  string $expr
	* @return bool
	*/
	protected function isConstantExpression($expr)
	{
		// Replace strings to avoid false-positives
		$expr = preg_replace('("[^"]*"|\'[^\']*\')', '0', $expr);

		// Match function calls against the list of supported functions
		preg_match_all('(\\w[-\\w]+(?=\\())', $expr, $m);
		if (count(array_diff($m[0], $this->supportedFunctions)) > 0)
		{
			return false;
		}

		// Match unsupported characters and keywords, as well as function calls without arguments
		return !preg_match('([^\\s!\\-0-9<=>a-z\\(-.]|\\.(?![0-9])|\\b[-a-z](?![-\\w]+\\()|\\(\\s*\\))i', $expr);
	}

	/**
	* Select the best replacement for given expression
	*
	* @param  string $expr       Original expression
	* @param  string $foldedExpr Folded expression
	* @return string
	*/
	protected function selectReplacement($expr, $foldedExpr)
	{
		// Use the folded expression if it's smaller or it's a boolean
		if (strlen($foldedExpr) < strlen($expr) || $foldedExpr === 'false()' || $foldedExpr === 'true()')
		{
			return $foldedExpr;
		}

		return $expr;
	}
}