<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter;

use DOMDocument;
use InvalidArgumentException;

abstract class Renderer
{
	protected $htmlOutput = \true;

	public $metaElementsRegexp = '(<[eis]>[^<]*</[eis]>)';

	protected $params = [];

	protected function loadXML($xml)
	{
		$this->preventDTD($xml);

		$flags = (\LIBXML_VERSION >= 20700) ? \LIBXML_COMPACT | \LIBXML_PARSEHUGE : 0;

		$dom = new DOMDocument;
		$dom->loadXML($xml, $flags);

		return $dom;
	}

	public function render($xml)
	{
		if (\substr($xml, 0, 3) === '<t>')
			return $this->renderPlainText($xml);
		else
			return $this->renderRichText(\preg_replace($this->metaElementsRegexp, '', $xml));
	}

	public function renderMulti(array $arr)
	{
		$keys   = [];
		$render = [];

		foreach ($arr as $k => $xml)
			if (\substr($xml, 0, 3) === '<t>')
				$arr[$k] = $this->renderPlainText($xml);
			else
			{
				$keys[]   = $k;
				$render[] = $xml;
			}

		if (!empty($render))
		{
			$uid = \sha1(\uniqid(\mt_rand(), \true));
			$xml = '<m>' . \implode($uid, $render) . '</m>';

			foreach (\explode($uid, $this->renderRichText($xml)) as $i => $html)
				$arr[$keys[$i]] = $html;
		}

		return $arr;
	}

	protected function renderPlainText($xml)
	{
		$html = \substr($xml, 3, -4);

		if ($this->htmlOutput)
			$html = \str_replace('<br/>', '<br>', $html);

		return $html;
	}

	abstract protected function renderRichText($xml);

	public function getParameter($paramName)
	{
		return (isset($this->params[$paramName])) ? $this->params[$paramName] : '';
	}

	public function getParameters()
	{
		return $this->params;
	}

	public function setParameter($paramName, $paramValue)
	{
		$this->params[$paramName] = (string) $paramValue;
	}

	public function setParameters(array $params)
	{
		foreach ($params as $paramName => $paramValue)
			$this->setParameter($paramName, $paramValue);
	}

	protected function preventDTD($xml)
	{
		if (\strpos($xml, '<!') !== \false && \preg_match('(<!(?!\\[CDATA\\[))', $xml))
			throw new InvalidArgumentException('DTDs are not allowed');
	}
}