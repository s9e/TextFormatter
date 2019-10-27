<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\PHP;
use DOMElement;
use DOMXPath;
use RuntimeException;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;
use s9e\TextFormatter\Configurator\Helpers\TemplateParser;
class Serializer
{
	public $convertor;
	protected $isVoid;
	protected $xpath;
	public function __construct()
	{
		$this->convertor = new XPathConvertor;
	}
	public function convertCondition($expr)
	{
		return $this->convertor->convertCondition($expr);
	}
	public function convertXPath($expr)
	{
		$php = $this->convertor->convertXPath($expr);
		if (\is_numeric($php))
			$php = "'" . $php . "'";
		return $php;
	}
	public function serialize(DOMElement $ir)
	{
		$this->xpath  = new DOMXPath($ir->ownerDocument);
		$this->isVoid = array();
		foreach ($this->xpath->query('//element') as $element)
			$this->isVoid[$element->getAttribute('id')] = $element->getAttribute('void');
		return $this->serializeChildren($ir);
	}
	protected function convertAttributeValueTemplate($attrValue)
	{
		$phpExpressions = array();
		foreach (AVTHelper::parse($attrValue) as $token)
			if ($token[0] === 'literal')
				$phpExpressions[] = \var_export($token[1], \true);
			else
				$phpExpressions[] = $this->convertXPath($token[1]);
		return \implode('.', $phpExpressions);
	}
	protected function escapeLiteral($text, $context)
	{
		if ($context === 'raw')
			return $text;
		$escapeMode = ($context === 'attribute') ? \ENT_COMPAT : \ENT_NOQUOTES;
		return \htmlspecialchars($text, $escapeMode);
	}
	protected function escapePHPOutput($php, $context)
	{
		if ($context === 'raw')
			return $php;
		$escapeMode = ($context === 'attribute') ? \ENT_COMPAT : \ENT_NOQUOTES;
		return 'htmlspecialchars(' . $php . ',' . $escapeMode . ')';
	}
	protected function hasMultipleCases(DOMElement $switch)
	{
		return $this->xpath->evaluate('count(case[@test]) > 1', $switch);
	}
	protected function serializeApplyTemplates(DOMElement $applyTemplates)
	{
		$php = '$this->at($node';
		if ($applyTemplates->hasAttribute('select'))
			$php .= ',' . \var_export($applyTemplates->getAttribute('select'), \true);
		$php .= ');';
		return $php;
	}
	protected function serializeAttribute(DOMElement $attribute)
	{
		$attrName = $attribute->getAttribute('name');
		$phpAttrName = $this->convertAttributeValueTemplate($attrName);
		$phpAttrName = 'htmlspecialchars(' . $phpAttrName . ',' . \ENT_QUOTES . ')';
		return "\$this->out.=' '." . $phpAttrName . ".'=\"';"
		     . $this->serializeChildren($attribute)
		     . "\$this->out.='\"';";
	}
	protected function serializeChildren(DOMElement $ir)
	{
		$php = '';
		foreach ($ir->childNodes as $node)
			if ($node instanceof DOMElement)
			{
				$methodName = 'serialize' . \ucfirst($node->localName);
				$php .= $this->$methodName($node);
			}
		return $php;
	}
	protected function serializeCloseTag(DOMElement $closeTag)
	{
		$php = "\$this->out.='>';";
		$id  = $closeTag->getAttribute('id');
		if ($closeTag->hasAttribute('set'))
			$php .= '$t' . $id . '=1;';
		if ($closeTag->hasAttribute('check'))
			$php = 'if(!isset($t' . $id . ')){' . $php . '}';
		if ($this->isVoid[$id] === 'maybe')
			$php .= 'if(!$v' . $id . '){';
		return $php;
	}
	protected function serializeComment(DOMElement $comment)
	{
		return "\$this->out.='<!--';"
		     . $this->serializeChildren($comment)
		     . "\$this->out.='-->';";
	}
	protected function serializeCopyOfAttributes(DOMElement $copyOfAttributes)
	{
		return 'foreach($node->attributes as $attribute){'
		     . "\$this->out.=' ';\$this->out.=\$attribute->name;\$this->out.='=\"';\$this->out.=htmlspecialchars(\$attribute->value," . \ENT_COMPAT . ");\$this->out.='\"';"
		     . '}';
	}
	protected function serializeElement(DOMElement $element)
	{
		$php     = '';
		$elName  = $element->getAttribute('name');
		$id      = $element->getAttribute('id');
		$isVoid  = $element->getAttribute('void');
		$isDynamic = (bool) (\strpos($elName, '{') !== \false);
		$phpElName = $this->convertAttributeValueTemplate($elName);
		$phpElName = 'htmlspecialchars(' . $phpElName . ',' . \ENT_QUOTES . ')';
		if ($isDynamic)
		{
			$varName = '$e' . $id;
			$php .= $varName . '=' . $phpElName . ';';
			$phpElName = $varName;
		}
		if ($isVoid === 'maybe')
			$php .= '$v' . $id . '=preg_match(' . \var_export(TemplateParser::$voidRegexp, \true) . ',' . $phpElName . ');';
		$php .= "\$this->out.='<'." . $phpElName . ';';
		$php .= $this->serializeChildren($element);
		if ($isVoid !== 'yes')
			$php .= "\$this->out.='</'." . $phpElName . ".'>';";
		if ($isVoid === 'maybe')
			$php .= '}';
		return $php;
	}
	protected function serializeHash(DOMElement $switch)
	{
		$statements = array();
		foreach ($this->xpath->query('case[@branch-values]', $switch) as $case)
			foreach (\unserialize($case->getAttribute('branch-values')) as $value)
				$statements[$value] = $this->serializeChildren($case);
		if (!isset($case))
			throw new RuntimeException;
		$defaultCase = $this->xpath->query('case[not(@branch-values)]', $switch)->item(0);
		$defaultCode = ($defaultCase instanceof DOMElement) ? $this->serializeChildren($defaultCase) : '';
		$expr        = $this->convertXPath($switch->getAttribute('branch-key'));
		return SwitchStatement::generate($expr, $statements, $defaultCode);
	}
	protected function serializeOutput(DOMElement $output)
	{
		$context = $output->getAttribute('escape');
		$php = '$this->out.=';
		if ($output->getAttribute('type') === 'xpath')
			$php .= $this->escapePHPOutput($this->convertXPath($output->textContent), $context);
		else
			$php .= \var_export($this->escapeLiteral($output->textContent, $context), \true);
		$php .= ';';
		return $php;
	}
	protected function serializeSwitch(DOMElement $switch)
	{
		if ($switch->hasAttribute('branch-key') && $this->hasMultipleCases($switch))
			return $this->serializeHash($switch);
		$php   = '';
		$if    = 'if';
		foreach ($this->xpath->query('case', $switch) as $case)
		{
			if ($case->hasAttribute('test'))
				$php .= $if . '(' . $this->convertCondition($case->getAttribute('test')) . ')';
			else
				$php .= 'else';
			$php .= '{' . $this->serializeChildren($case) . '}';
			$if   = 'elseif';
		}
		return $php;
	}
}