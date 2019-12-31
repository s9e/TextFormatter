<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers\TemplateParser;

use DOMDocument;
use DOMElement;
use DOMNode;
use s9e\TextFormatter\Configurator\Helpers\XPathHelper;

class Normalizer extends IRProcessor
{
	/**
	* @var Optimizer
	*/
	protected $optimizer;

	/**
	* @var string Regexp that matches the names of all void elements
	* @link http://www.w3.org/TR/html-markup/syntax.html#void-elements
	*/
	public $voidRegexp = '/^(?:area|base|br|col|command|embed|hr|img|input|keygen|link|meta|param|source|track|wbr)$/Di';

	/**
	* @param  Optimizer $optimizer
	* @return void
	*/
	public function __construct(Optimizer $optimizer)
	{
		$this->optimizer = $optimizer;
	}

	/**
	* Normalize an IR
	*
	* @param  DOMDocument $ir
	* @return void
	*/
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

	/**
	* Add <closeTag/> elements everywhere an open start tag should be closed
	*
	* @param  DOMDocument $ir
	* @return void
	*/
	protected function addCloseTagElements(DOMDocument $ir)
	{
		$exprs = [
			'//applyTemplates[not(ancestor::attribute)]',
			'//comment',
			'//element',
			'//output[not(ancestor::attribute)]'
		];
		foreach ($this->query(implode('|', $exprs)) as $node)
		{
			$parentElementId = $this->getParentElementId($node);
			if (isset($parentElementId))
			{
				$node->parentNode
				     ->insertBefore($ir->createElement('closeTag'), $node)
				     ->setAttribute('id', $parentElementId);
			}

			// Append a <closeTag/> to <element/> nodes to ensure that empty elements get closed
			if ($node->nodeName === 'element')
			{
				$id = $node->getAttribute('id');
				$this->appendElement($node, 'closeTag')->setAttribute('id', $id);
			}
		}
	}

	/**
	* Add an empty default <case/> to <switch/> nodes that don't have one
	*
	* @param  DOMDocument $ir
	* @return void
	*/
	protected function addDefaultCase(DOMDocument $ir)
	{
		foreach ($this->query('//switch[not(case[not(@test)])]') as $switch)
		{
			$this->appendElement($switch, 'case');
		}
	}

	/**
	* Add an id attribute to <element/> nodes
	*
	* @param  DOMDocument $ir
	* @return void
	*/
	protected function addElementIds(DOMDocument $ir)
	{
		$id = 0;
		foreach ($this->query('//element') as $element)
		{
			$element->setAttribute('id', ++$id);
		}
	}

	/**
	* Get the context type for given output element
	*
	* @param  DOMNode $output
	* @return string
	*/
	protected function getOutputContext(DOMNode $output)
	{
		$contexts = [
			'boolean(ancestor::attribute)'             => 'attribute',
			'@disable-output-escaping="yes"'           => 'raw',
			'count(ancestor::element[@name="script"])' => 'raw'
		];
		foreach ($contexts as $expr => $context)
		{
			if ($this->evaluate($expr, $output))
			{
				return $context;
			}
		}

		return 'text';
	}

	/**
	* Get the ID of the closest "element" ancestor
	*
	* @param  DOMNode     $node Context node
	* @return string|null
	*/
	protected function getParentElementId(DOMNode $node)
	{
		$parentNode = $node->parentNode;
		while (isset($parentNode))
		{
			if ($parentNode->nodeName === 'element')
			{
				return $parentNode->getAttribute('id');
			}
			$parentNode = $parentNode->parentNode;
		}
	}

	/**
	* Mark switch elements that are used as branch tables
	*
	* If a switch is used for a series of equality tests against the same attribute or variable, the
	* attribute/variable is stored within the switch as "branch-key" and the values it is compared
	* against are stored JSON-encoded in the case as "branch-values". It can be used to create
	* optimized branch tables
	*
	* @param  DOMDocument $ir
	* @return void
	*/
	protected function markBranchTables(DOMDocument $ir)
	{
		// Iterate over switch elements that have at least two case children with a test attribute
		foreach ($this->query('//switch[case[2][@test]]') as $switch)
		{
			$this->markSwitchTable($switch);
		}
	}

	/**
	* Mark given switch element if it's used as a branch table
	*
	* @param  DOMElement $switch
	* @return void
	*/
	protected function markSwitchTable(DOMElement $switch)
	{
		$cases = [];
		$maps  = [];
		foreach ($this->query('./case[@test]', $switch) as $i => $case)
		{
			$map = XPathHelper::parseEqualityExpr($case->getAttribute('test'));
			if ($map === false)
			{
				return;
			}
			$maps     += $map;
			$cases[$i] = [$case, end($map)];
		}
		if (count($maps) !== 1)
		{
			return;
		}

		$switch->setAttribute('branch-key', key($maps));
		foreach ($cases as list($case, $values))
		{
			sort($values);
			$case->setAttribute('branch-values', serialize($values));
		}
	}

	/**
	* Mark conditional <closeTag/> nodes
	*
	* @param  DOMDocument $ir
	* @return void
	*/
	protected function markConditionalCloseTagElements(DOMDocument $ir)
	{
		foreach ($this->query('//closeTag') as $closeTag)
		{
			$id = $closeTag->getAttribute('id');

			// For each <switch/> ancestor, look for a <closeTag/> and that is either a sibling or
			// the descendant of a sibling, and that matches the id
			$query = 'ancestor::switch/'
			       . 'following-sibling::*/'
			       . 'descendant-or-self::closeTag[@id = "' . $id . '"]';
			foreach ($this->query($query, $closeTag) as $following)
			{
				// Mark following <closeTag/> nodes to indicate that the status of this tag must
				// be checked before it is closed
				$following->setAttribute('check', '');

				// Mark the current <closeTag/> to indicate that it must set a flag to indicate
				// that its tag has been closed
				$closeTag->setAttribute('set', '');
			}
		}
	}

	/**
	* Mark void elements
	*
	* @param  DOMDocument $ir
	* @return void
	*/
	protected function markVoidElements(DOMDocument $ir)
	{
		foreach ($this->query('//element') as $element)
		{
			// Test whether this element is (maybe) void
			$elName = $element->getAttribute('name');
			if (strpos($elName, '{') !== false)
			{
				// Dynamic element names must be checked at runtime
				$element->setAttribute('void', 'maybe');
			}
			elseif (preg_match($this->voidRegexp, $elName))
			{
				// Static element names can be checked right now
				$element->setAttribute('void', 'yes');
			}
		}
	}

	/**
	* Fill in output context
	*
	* @param  DOMDocument $ir
	* @return void
	*/
	protected function setOutputContext(DOMDocument $ir)
	{
		foreach ($this->query('//output') as $output)
		{
			$output->setAttribute('escape', $this->getOutputContext($output));
		}
	}
}