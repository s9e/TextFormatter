<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
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
	public $branchTableThreshold = 8;
	public $branchTables = [];
	public $convertor;
	public $useMultibyteStringFunctions = \false;
	public function __construct()
	{
		$this->convertor = new XPathConvertor;
	}
	protected function convertAttributeValueTemplate($attrValue)
	{
		$phpExpressions = [];
		foreach (AVTHelper::parse($attrValue) as $token)
			if ($token[0] === 'literal')
				$phpExpressions[] = \var_export($token[1], \true);
			else
				$phpExpressions[] = $this->convertXPath($token[1]);
		return \implode('.', $phpExpressions);
	}
	public function convertCondition($expr)
	{
		$this->convertor->useMultibyteStringFunctions = $this->useMultibyteStringFunctions;
		return $this->convertor->convertCondition($expr);
	}
	public function convertXPath($expr)
	{
		$this->convertor->useMultibyteStringFunctions = $this->useMultibyteStringFunctions;
		return $this->convertor->convertXPath($expr);
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
	public function serialize(DOMElement $ir)
	{
		$this->branchTables = [];
		return $this->serializeChildren($ir);
	}
	protected function serializeChildren(DOMElement $ir)
	{
		$php = '';
		foreach ($ir->childNodes as $node)
		{
			$methodName = 'serialize' . \ucfirst($node->localName);
			$php .= $this->$methodName($node);
		}
		return $php;
	}
	protected function serializeCloseTag(DOMElement $closeTag)
	{
		$php = '';
		$id  = $closeTag->getAttribute('id');
		if ($closeTag->hasAttribute('check'))
			$php .= 'if(!isset($t' . $id . ')){';
		if ($closeTag->hasAttribute('set'))
			$php .= '$t' . $id . '=1;';
		$xpath   = new DOMXPath($closeTag->ownerDocument);
		$element = $xpath->query('ancestor::element[@id="' . $id . '"]', $closeTag)->item(0);
		if (!($element instanceof DOMElement))
			throw new RuntimeException;
		$php .= "\$this->out.='>';";
		if ($element->getAttribute('void') === 'maybe')
			$php .= 'if(!$v' . $id . '){';
		if ($closeTag->hasAttribute('check'))
			$php .= '}';
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
		$statements = [];
		foreach ($switch->getElementsByTagName('case') as $case)
		{
			if (!$case->parentNode->isSameNode($switch))
				continue;
			if ($case->hasAttribute('branch-values'))
			{
				$php = $this->serializeChildren($case);
				foreach (\unserialize($case->getAttribute('branch-values')) as $value)
					$statements[$value] = $php;
			}
		}
		if (!isset($case))
			throw new RuntimeException;
		list($branchTable, $php) = Quick::generateBranchTable('$n', $statements);
		$varName = 'bt' . \sprintf('%08X', \crc32(\serialize($branchTable)));
		$expr = 'self::$' . $varName . '[' . $this->convertXPath($switch->getAttribute('branch-key')) . ']';
		$php = 'if(isset(' . $expr . ')){$n=' . $expr . ';' . $php . '}';
		if (!$case->hasAttribute('branch-values'))
			$php .= 'else{' . $this->serializeChildren($case) . '}';
		$this->branchTables[$varName] = $branchTable;
		return $php;
	}
	protected function serializeOutput(DOMElement $output)
	{
		$php        = '';
		$escapeMode = ($output->getAttribute('escape') === 'attribute')
		            ? \ENT_COMPAT
		            : \ENT_NOQUOTES;
		if ($output->getAttribute('type') === 'xpath')
		{
			$php .= '$this->out.=htmlspecialchars(';
			$php .= $this->convertXPath($output->textContent);
			$php .= ',' . $escapeMode . ');';
		}
		else
		{
			$php .= '$this->out.=';
			$php .= \var_export(\htmlspecialchars($output->textContent, $escapeMode), \true);
			$php .= ';';
		}
		return $php;
	}
	protected function serializeSwitch(DOMElement $switch)
	{
		if ($switch->hasAttribute('branch-key')
		 && $switch->childNodes->length >= $this->branchTableThreshold)
			return $this->serializeHash($switch);
		$php  = '';
		$else = '';
		foreach ($switch->getElementsByTagName('case') as $case)
		{
			if (!$case->parentNode->isSameNode($switch))
				continue;
			if ($case->hasAttribute('test'))
				$php .= $else . 'if(' . $this->convertCondition($case->getAttribute('test')) . ')';
			else
				$php .= 'else';
			$else = 'else';
			$php .= '{';
			$php .= $this->serializeChildren($case);
			$php .= '}';
		}
		return $php;
	}
}