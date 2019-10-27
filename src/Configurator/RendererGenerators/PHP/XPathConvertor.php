<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
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
use s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors\SingleByteStringFunctions;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors\SingleByteStringManipulation;
class XPathConvertor
{
	protected $parser;
	public function __construct(RecursiveParser $parser = \null)
	{
		$this->parser = $parser ?: $this->getDefaultParser();
	}
	public function convertCondition($expr)
	{
		$expr = \preg_replace(
			'((^|\\(\\s*|\\b(?:and|or)\\s*)([\\(\\s]*)([$@][-\\w]+|@\\*)([\\)\\s]*)(?=$|\\s+(?:and|or)))',
			'$1$2boolean($3)$4',
			\trim($expr)
		);
		$expr = \preg_replace(
			'(not\\(boolean\\(([$@][-\\w]+)\\)\\))',
			'not($1)',
			$expr
		);
		try
		{
			return $this->parser->parse($expr)array('value');
		}
		catch (RuntimeException $e)
		{
			}
		if (!\preg_match('([=<>]|\\bor\\b|\\band\\b|^[-\\w]+\\s*\\()', $expr))
			$expr = 'boolean(' . $expr . ')';
		return '$this->xpath->evaluate(' . $this->exportXPath($expr) . ',$node)';
	}
	public function convertXPath($expr)
	{
		$expr = \trim($expr);
		try
		{
			return $this->parser->parse($expr)array('value');
		}
		catch (RuntimeException $e)
		{
			}
		if (!\preg_match('(^[-\\w]*s(?:late|pace|tring)[-\\w]*\\()', $expr))
			$expr = 'string(' . $expr . ')';
		return '$this->xpath->evaluate(' . $this->exportXPath($expr) . ',$node)';
	}
	protected function exportXPath($expr)
	{
		$phpTokens = array();
		foreach ($this->tokenizeXPathForExport($expr) as array($type, $content))
		{
			$methodName  = 'exportXPath' . $type;
			$phpTokens[] = $this->$methodName($content);
		}
		return \implode('.', $phpTokens);
	}
	protected function exportXPathCurrent()
	{
		return '$node->getNodePath()';
	}
	protected function exportXPathFragment($fragment)
	{
		return \var_export($fragment, \true);
	}
	protected function exportXPathParam($param)
	{
		$paramName = \ltrim($param, '$');
		return '$this->getParamAsXPath(' . \var_export($paramName, \true) . ')';
	}
	protected function getDefaultParser()
	{
		$parser     = new RecursiveParser;
		$matchers   = array();
		$matchers[] = new SingleByteStringFunctions($parser);
		$matchers[] = new BooleanFunctions($parser);
		$matchers[] = new BooleanOperators($parser);
		$matchers[] = new Comparisons($parser);
		$matchers[] = new Core($parser);
		$matchers[] = new Math($parser);
		if (\extension_loaded('mbstring'))
			$matchers[] = new MultiByteStringManipulation($parser);
		$matchers[] = new SingleByteStringManipulation($parser);
		$parser->setMatchers($matchers);
		return $parser;
	}
	protected function tokenizeXPathForExport($expr)
	{
		$tokenExprs = array(
			'(*:Current)\\bcurrent\\(\\)',
			'(*:Param)\\$\\w+',
			'(*:Fragment)(?:"[^"]*"|\'[^\']*\'|(?!current\\(\\)|\\$\\w).)++'
		);
		\preg_match_all('(' . \implode('|', $tokenExprs) . ')s', $expr, $matches, \PREG_SET_ORDER);
		$tokens = array();
		foreach ($matches as $m)
			$tokens[] = array($m['MARK'], $m[0]);
		return $tokens;
	}
}