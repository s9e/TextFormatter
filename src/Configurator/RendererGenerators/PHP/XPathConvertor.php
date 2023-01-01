<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\PHP;

use RuntimeException;
use s9e\TextFormatter\Configurator\RecursiveParser;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors\BooleanFunctions;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors\BooleanOperators;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors\Comparisons;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors\Core;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors\Math;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors\MultiByteStringManipulation;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors\PHP80Functions;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors\SingleByteStringFunctions;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors\SingleByteStringManipulation;

class XPathConvertor
{
	/**
	* @var array Array of togglable PHP features ("mbstring" and "php80")
	*/
	public $features;

	/**
	* @var RecursiveParser
	*/
	protected $parser;

	/**
	* Constructor
	*/
	public function __construct(RecursiveParser $parser = null)
	{
		$this->features = [
			'mbstring' => extension_loaded('mbstring'),
			'php80'    => version_compare(PHP_VERSION, '8.0', '>=')
		];
		if (isset($parser))
		{
			$this->parser = $parser;
		}
	}

	/**
	* Convert an XPath expression (used in a condition) into PHP code
	*
	* This method is similar to convertXPath() but it selectively replaces some simple conditions
	* with the corresponding DOM method for performance reasons
	*
	* @param  string $expr XPath expression
	* @return string       PHP code
	*/
	public function convertCondition($expr)
	{
		// Replace @attr with boolean(@attr) in boolean expressions
		$expr = preg_replace(
			'((^|(?<!\\bboolean)\\(\\s*|\\b(?:and|or)\\s*)([\\(\\s]*)([$@][-\\w]+|@\\*)([\\)\\s]*)(?=$|\\s+(?:and|or)))',
			'$1$2boolean($3)$4',
			trim($expr)
		);

		// Replace not(boolean(@attr)) with not(@attr)
		$expr = preg_replace(
			'(not\\(boolean\\(([$@][-\\w]+)\\)\\))',
			'not($1)',
			$expr
		);

		try
		{
			return $this->getParser()->parse($expr)['value'];
		}
		catch (RuntimeException $e)
		{
			// Do nothing
		}

		// If the condition does not seem to contain a relational expression, or start with a
		// function call, we wrap it inside of a boolean() call
		if (!preg_match('([=<>]|\\bor\\b|\\band\\b|^[-\\w]+\\s*\\()', $expr))
		{
			$expr = 'boolean(' . $expr . ')';
		}

		return '$this->xpath->evaluate(' . $this->exportXPath($expr) . ',$node)';
	}

	/**
	* Convert an XPath expression (used as value) into PHP code
	*
	* @param  string $expr XPath expression
	* @return string       PHP code
	*/
	public function convertXPath($expr)
	{
		$expr = trim($expr);
		try
		{
			return $this->getParser()->parse($expr)['value'];
		}
		catch (RuntimeException $e)
		{
			// Do nothing
		}

		// Make sure the expression evaluates as a string
		if (!preg_match('(^[-\\w]*s(?:late|pace|tring)[-\\w]*\\()', $expr))
		{
			$expr = 'string(' . $expr . ')';
		}

		return '$this->xpath->evaluate(' . $this->exportXPath($expr) . ',$node)';
	}

	/**
	* Export an XPath expression as PHP with special consideration for XPath variables
	*
	* Will return PHP source representing the XPath expression, with special consideration for XPath
	* variables which are returned as a method call to XPath::export()
	*
	* @param  string $expr XPath expression
	* @return string       PHP representation of the expression
	*/
	protected function exportXPath($expr)
	{
		$phpTokens = [];
		foreach ($this->tokenizeXPathForExport($expr) as [$type, $content])
		{
			$methodName  = 'exportXPath' . $type;
			$phpTokens[] = $this->$methodName($content);
		}

		return implode('.', $phpTokens);
	}

	/**
	* Convert a "current()" XPath expression to its PHP source representation
	*
	* @return string
	*/
	protected function exportXPathCurrent()
	{
		return '$node->getNodePath()';
	}

	/**
	* Convert a fragment of an XPath expression to its PHP source representation
	*
	* @param  string $fragment
	* @return string
	*/
	protected function exportXPathFragment($fragment)
	{
		return var_export($fragment, true);
	}

	/**
	* Convert an XSLT parameter to its PHP source representation
	*
	* @param  string $param Parameter, including the leading $
	* @return string
	*/
	protected function exportXPathParam($param)
	{
		$paramName = ltrim($param, '$');

		return '$this->getParamAsXPath(' . var_export($paramName, true) . ')';
	}

	/**
	* Generate and return the a parser with the default set of matchers
	*
	* @return RecursiveParser
	*/
	protected function getDefaultParser()
	{
		$parser     = new RecursiveParser;
		$matchers   = [];
		$matchers[] = new SingleByteStringFunctions($parser);
		$matchers[] = new BooleanFunctions($parser);
		$matchers[] = new BooleanOperators($parser);
		$matchers[] = new Comparisons($parser);
		$matchers[] = new Core($parser);
		$matchers[] = new Math($parser);
		if (!empty($this->features['mbstring']))
		{
			$matchers[] = new MultiByteStringManipulation($parser);
		}
		$matchers[] = new SingleByteStringManipulation($parser);
		if (!empty($this->features['php80']))
		{
			$matchers[] = new PHP80Functions($parser);
		}

		$parser->setMatchers($matchers);

		return $parser;
	}

	/**
	* Return (and if necessary, create) the cached instance of the XPath parser
	*
	* @return RecursiveParser
	*/
	protected function getParser(): RecursiveParser
	{
		if (!isset($this->parser))
		{
			$this->parser = $this->getDefaultParser();
		}

		return $this->parser;
	}

	/**
	* Tokenize an XPath expression for use in PHP
	*
	* @param  string $expr XPath expression
	* @return array
	*/
	protected function tokenizeXPathForExport($expr)
	{
		$tokenExprs = [
			'(*:Current)\\bcurrent\\(\\)',
			'(*:Param)\\$\\w+',
			'(*:Fragment)(?:"[^"]*"|\'[^\']*\'|(?!current\\(\\)|\\$\\w).)++'
		];
		preg_match_all('(' . implode('|', $tokenExprs) . ')s', $expr, $matches, PREG_SET_ORDER);

		$tokens = [];
		foreach ($matches as $m)
		{
			$tokens[] = [$m['MARK'], $m[0]];
		}

		return $tokens;
	}
}