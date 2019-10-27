<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;
use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;
class DisallowXPathFunction extends TemplateCheck
{
	public $funcName;
	public function __construct($funcName)
	{
		$this->funcName = $funcName;
	}
	public function check(DOMElement $template, Tag $tag)
	{
		$regexp = '#(?!<\\pL)' . \preg_quote($this->funcName, '#') . '\\s*\\(#iu';
		$regexp = \str_replace('\\:', '\\s*:\\s*', $regexp);
		foreach ($this->getExpressions($template) as $expr => $node)
		{
			$expr = \preg_replace('#([\'"]).*?\\1#s', '', $expr);
			if (\preg_match($regexp, $expr))
				throw new UnsafeTemplateException('An XPath expression uses the ' . $this->funcName . '() function', $node);
		}
	}
	protected function getExpressions(DOMElement $template)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		$exprs = array();
		foreach ($xpath->query('//@*') as $attribute)
			if ($attribute->parentNode->namespaceURI === self::XMLNS_XSL)
			{
				$expr = $attribute->value;
				$exprs[$expr] = $attribute;
			}
			else
				foreach (AVTHelper::parse($attribute->value) as $token)
					if ($token[0] === 'expression')
						$exprs[$token[1]] = $attribute;
		return $exprs;
	}
}