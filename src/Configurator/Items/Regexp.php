<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;
use InvalidArgumentException;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\FilterableConfigValue;
use s9e\TextFormatter\Configurator\Helpers\RegexpParser;
use s9e\TextFormatter\Configurator\JavaScript\Code;
use s9e\TextFormatter\Configurator\JavaScript\RegexpConvertor;
class Regexp implements ConfigProvider, FilterableConfigValue
{
	protected $isGlobal;
	protected $jsRegexp;
	protected $regexp;
	public function __construct($regexp, $isGlobal = \false)
	{
		if (@\preg_match($regexp, '') === \false)
			throw new InvalidArgumentException('Invalid regular expression ' . \var_export($regexp, \true));
		$this->regexp   = $regexp;
		$this->isGlobal = $isGlobal;
	}
	public function __toString()
	{
		return $this->regexp;
	}
	public function asConfig()
	{
		return $this;
	}
	public function filterConfig($target)
	{
		return ($target === 'JS') ? new Code($this->getJS()) : (string) $this;
	}
	public function getCaptureNames()
	{
		return RegexpParser::getCaptureNames($this->regexp);
	}
	public function getJS()
	{
		if (!isset($this->jsRegexp))
			$this->jsRegexp = RegexpConvertor::toJS($this->regexp, $this->isGlobal);
		return $this->jsRegexp;
	}
	public function getNamedCaptures()
	{
		$captures   = array();
		$regexpInfo = RegexpParser::parse($this->regexp);
		$start = $regexpInfo['delimiter'] . '^';
		$end   = '$' . $regexpInfo['delimiter'] . $regexpInfo['modifiers'];
		if (\strpos($regexpInfo['modifiers'], 'D') === \false)
			$end .= 'D';
		foreach ($this->getNamedCapturesExpressions($regexpInfo['tokens']) as $name => $expr)
			$captures[$name] = $start . $expr . $end;
		return $captures;
	}
	protected function getNamedCapturesExpressions(array $tokens)
	{
		$exprs = array();
		foreach ($tokens as $token)
		{
			if ($token['type'] !== 'capturingSubpatternStart' || !isset($token['name']))
				continue;
			$expr = $token['content'];
			if (\strpos($expr, '|') !== \false)
				$expr = '(?:' . $expr . ')';
			$exprs[$token['name']] = $expr;
		}
		return $exprs;
	}
	public function setJS($jsRegexp)
	{
		$this->jsRegexp = $jsRegexp;
	}
}