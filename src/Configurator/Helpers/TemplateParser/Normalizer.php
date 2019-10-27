<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers\TemplateParser;
use DOMDocument;
use DOMElement;
use DOMNode;
use s9e\TextFormatter\Configurator\Helpers\XPathHelper;
class Normalizer extends IRProcessor
{
	protected $optimizer;
	public $voidRegexp = '/^(?:area|base|br|col|command|embed|hr|img|input|keygen|link|meta|param|source|track|wbr)$/Di';
	public function __construct(Optimizer $optimizer)
	{
		$this->optimizer = $optimizer;
	}
	public function normalize(DOMDocument $ir)
	{
		$this->createXPath($ir);
		$this->addDefaultCase($ir);
		$this->addElementIds($ir);
		$this->addCloseTagElements($ir);
		$this->markVoidElements($ir);
		$this->optimizer->optimize($ir);
		$this->markConditionalCloseTagElements($ir);
		$this->setOutputContext($ir);
		$this->markBranchTables($ir);
	}
	protected function addCloseTagElements(DOMDocument $ir)
	{
		$exprs = array(
			'//applyTemplates[not(ancestor::attribute)]',
			'//comment',
			'//element',
			'//output[not(ancestor::attribute)]'
		);
		foreach ($this->query(\implode('|', $exprs)) as $node)
		{
			$parentElementId = $this->getParentElementId($node);
			if (isset($parentElementId))
				$node->parentNode
				     ->insertBefore($ir->createElement('closeTag'), $node)
				     ->setAttribute('id', $parentElementId);
			if ($node->nodeName === 'element')
			{
				$id = $node->getAttribute('id');
				$this->appendElement($node, 'closeTag')->setAttribute('id', $id);
			}
		}
	}
	protected function addDefaultCase(DOMDocument $ir)
	{
		foreach ($this->query('//switch[not(case[not(@test)])]') as $switch)
			$this->appendElement($switch, 'case');
	}
	protected function addElementIds(DOMDocument $ir)
	{
		$id = 0;
		foreach ($this->query('//element') as $element)
			$element->setAttribute('id', ++$id);
	}
	protected function getOutputContext(DOMNode $output)
	{
		$contexts = array(
			'boolean(ancestor::attribute)'             => 'attribute',
			'@disable-output-escaping="yes"'           => 'raw',
			'count(ancestor::element[@name="script"])' => 'raw'
		);
		foreach ($contexts as $expr => $context)
			if ($this->evaluate($expr, $output))
				return $context;
		return 'text';
	}
	protected function getParentElementId(DOMNode $node)
	{
		$parentNode = $node->parentNode;
		while (isset($parentNode))
		{
			if ($parentNode->nodeName === 'element')
				return $parentNode->getAttribute('id');
			$parentNode = $parentNode->parentNode;
		}
	}
	protected function markBranchTables(DOMDocument $ir)
	{
		foreach ($this->query('//switch[case[2][@test]]') as $switch)
			$this->markSwitchTable($switch);
	}
	protected function markSwitchTable(DOMElement $switch)
	{
		$cases = array();
		$maps  = array();
		foreach ($this->query('./case[@test]', $switch) as $i => $case)
		{
			$map = XPathHelper::parseEqualityExpr($case->getAttribute('test'));
			if ($map === \false)
				return;
			$maps     += $map;
			$cases[$i] = array($case, \end($map));
		}
		if (\count($maps) !== 1)
			return;
		$switch->setAttribute('branch-key', \key($maps));
		foreach ($cases as $_6920557c)
		{
			list($case, $values) = $_6920557c;
			\sort($values);
			$case->setAttribute('branch-values', \serialize($values));
		}
	}
	protected function markConditionalCloseTagElements(DOMDocument $ir)
	{
		foreach ($this->query('//closeTag') as $closeTag)
		{
			$id = $closeTag->getAttribute('id');
			$query = 'ancestor::switch/following-sibling::*/descendant-or-self::closeTag[@id = "' . $id . '"]';
			foreach ($this->query($query, $closeTag) as $following)
			{
				$following->setAttribute('check', '');
				$closeTag->setAttribute('set', '');
			}
		}
	}
	protected function markVoidElements(DOMDocument $ir)
	{
		foreach ($this->query('//element') as $element)
		{
			$elName = $element->getAttribute('name');
			if (\strpos($elName, '{') !== \false)
				$element->setAttribute('void', 'maybe');
			elseif (\preg_match($this->voidRegexp, $elName))
				$element->setAttribute('void', 'yes');
		}
	}
	protected function setOutputContext(DOMDocument $ir)
	{
		foreach ($this->query('//output') as $output)
			$output->setAttribute('escape', $this->getOutputContext($output));
	}
}