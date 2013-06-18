<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators;

use DOMDocument;
use DOMNode;
use DOMText;
use DOMXPath;
use RuntimeException;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\RendererGenerator;
use s9e\TextFormatter\Configurator\Stylesheet;

class PHP implements RendererGenerator
{
	/**
	* XSL namespace
	*/
	const XMLNS_XSL = 'http://www.w3.org/1999/XSL/Transform';

	/**
	* @var string Name of the class to be created. If null, a random name will be generated
	*/
	public $className;

	/**
	* @var string Name of the last class generated
	*/
	public $lastClassName;

	/**
	* @var string Output method
	*/
	protected $outputMethod;

	/**
	* @var string PHP source of generated renderer
	*/
	protected $php;

	/**
	* Constructor
	*
	* @param  string $className Name of the class to be created
	* @return void
	*/
	public function __construct($className = null)
	{
		if (isset($className))
		{
			$this->className = $className;
		}
	}

	/**
	* {@inheritdoc}
	*/
	public function getRenderer(Stylesheet $stylesheet)
	{
		// Generate the source file
		$php = $this->generate($stylesheet->get());

		// Execute the source to create the class
		eval($php);

		// Create an instance and copy the source into the instance
		$renderer = new $this->lastClassName;
		$renderer->source = $php;

		return $renderer;
	}

	/**
	* Generate the source for a PHP class that renders an intermediate representation according to
	* given stylesheet
	*
	* @param  string $xsl XSL stylesheet
	* @return string
	*/
	public function generate($xsl)
	{
		$dom = new DOMDocument;
		$dom->loadXML($xsl);

		$this->outputMethod = $dom->getElementsByTagNameNS(self::XMLNS_XSL, 'output')->item(0)->getAttribute('method');

		// Generate the arrays of parameters, sorted by whether they are static or dynamic
		$dynamicParams = [];
		$staticParams  = [];

		foreach ($dom->getElementsByTagNameNS(self::XMLNS_XSL, 'param') as $param)
		{
			$paramName  = $param->getAttribute('name');
			$paramValue = ($param->hasAttribute('select')) ? $param->getAttribute('select') : "''";

			// Test whether the param value is a literal
			if (preg_match('#^(?:\'[^\']*\'|"[^"]*"|[0-9]+)$#', $paramValue))
			{
				$staticParams[] = var_export($paramName, true) . '=>' . $paramValue;
			}
			else
			{
				$dynamicParams[] = var_export($paramName, true) . '=>' . var_export($paramValue, true);
			}
		}

		// Generate a random name for that class if necessary, and save it
		$className = (isset($this->className)) ? $this->className : uniqid('Renderer_');
		$this->lastClassName = $className;

		$this->php = 'class ' . $className . ' extends \\s9e\\TextFormatter\\Renderer {
			protected $htmlOutput=' . var_export($this->outputMethod === 'html', true) . ';
			protected $dynamicParams=[' . implode(',', $dynamicParams) . '];
			protected $params=[' . implode(',', $staticParams) . '];
			protected $xpath;
			public function setParameter($paramName, $paramValue)
			{
				$this->params[$paramName] = $paramValue;
				unset($this->dynamicParams[$paramName]);
			}
			public function renderRichText($xml)
			{
				$dom = $this->loadXML($xml);
				$this->xpath = new \\DOMXPath($dom);
				$this->out = "";';

		if ($dynamicParams)
		{
			$this->php .= '
				foreach ($this->dynamicParams as $k => $v)
				{
					$this->params[$k] = $this->xpath->evaluate("string($v)", $dom);
				}';
		}

		$this->php .= '
				$this->at($dom->documentElement);

				return $this->out;
			}
			protected function getParamAsXPath($k)
			{
				if (isset($this->dynamicParams[$k]))
				{
					return $this->dynamicParams[$k];
				}
				if (!isset($this->params[$k]))
				{
					return "\'\'";
				}
				$str = $this->params[$k];
				if (strpos($str, "\'") === false)
				{
					return "\'" . $str . "\'";
				}
				if (strpos($str, \'"\') === false)
				{
					return \'"\' . $str . \'"\';
				}

				$toks = [];
				$c = \'"\';
				$pos = 0;
				while ($pos < strlen($str))
				{
					$spn = strcspn($str, $c, $pos);
					if ($spn)
					{
						$toks[] = $c . substr($str, $pos, $spn) . $c;
						$pos += $spn;
					}
					$c = ($c === \'"\') ? "\'" : \'"\';
				}

				return "concat(" . implode(",", $toks) . ")";
			}
			protected function at($root, $xpath = null)
			{
				if ($root->nodeType === 3)
				{
					$this->out .= htmlspecialchars($root->textContent,' . ENT_NOQUOTES . ');
				}
				else
				{
					$nodes = (isset($xpath)) ? $this->xpath->query($xpath, $root) : $root->childNodes;
					foreach ($nodes as $node)
					{
						$nodeName = $node->nodeName;';

		// Remove the excess indentation
		$this->php = str_replace("\n\t\t\t", "\n", $this->php);

		// Collect and sort templates
		$templates = [];
		foreach ($dom->getElementsByTagNameNS(self::XMLNS_XSL, 'template') as $template)
		{
			// Parse this template and save its internal representation
			$irXML = $this->parseTemplate($template)->saveXML();

			// Get the template's match value
			$match = $template->getAttribute('match');

			// Capture the values separated by |
			preg_match_all('#(?:[^|"\']+(?:"[^"]*"|\'[^\']*\')?)+#', $match, $m);

			foreach ($m[0] as $expr)
			{
				/**
				* Compute this template's priority
				*
				* @link http://www.w3.org/TR/xslt#conflict
				*/
				if (preg_match('#^(?:\\w+:)?\\w+$#', $expr))
				{
					$priority = 0;
				}
				elseif (preg_match('#^\\w+:\\*#', $expr))
				{
					$priority = -0.25;
				}
				else
				{
					$priority = 0.5;
				}

				// Separate the tagName from the predicate, if any
				if (preg_match('#^(\\w+)\\[(.*)\\]$#s', $expr, $m))
				{
					$tagName   = $m[1];
					$predicate = $m[2];
				}
				else
				{
					$tagName   = $expr;
					$predicate = '';
				}

				// Test whether this is a wildcard template
				if (preg_match('#^(\\w+):\\*#', $tagName, $m))
				{
					$condition = '$node->prefix===' . var_export($m[1], true);
				}
				else
				{
					$condition = '$nodeName===' . var_export($tagName, true);
				}

				// Add the predicate to the condition
				if ($predicate !== '')
				{
					$condition = '(' . $condition . '&&' . $this->convertCondition($predicate) . ')';
				}

				// Record this template -- cast the float as string so it can be used as array key
				$priority = (string) $priority;
				$templates[$priority][$irXML][] = $condition;
			}
		}

		// Sort templates by priority descending
		krsort($templates);

		// Build the big if/else structure
		$else = '';
		foreach ($templates as $groupedTemplates)
		{
			// Process the grouped templates in reverse order so that the last templates apply first
			// to match XSLT's default behaviour
			foreach (array_reverse($groupedTemplates) as $irXML => $conditions)
			{
				$ir = new DOMDocument;
				$ir->loadXML($irXML);

				$this->php .= $else;
				$else = 'else';

				$this->php .= 'if(' . implode('||', $conditions) . ')';
				$this->php .= '{';
				$this->serializeChildren($ir->documentElement);
				$this->php .= '}';
			}
		}

		// Add the default handling
		$this->php .= 'else $this->at($node);}}}}';

		// Optimize the generated code
		$this->optimizeCode();

		return $this->php;
	}

	//==========================================================================
	// Template parsing
	//==========================================================================

	/**
	* Parse an <xsl:template/> node
	*
	* @param  DOMNode $template <xsl:template/> node
	* @return DOMNode           Internal representation of the template
	*/
	protected function parseTemplate(DOMNode $template)
	{
		$ir = new DOMDocument;
		$ir->loadXML('<ir/>');
		$xpath = new DOMXPath($ir);

		// Parse this template's content
		$this->parseChildren($ir->documentElement, $template);

		// Add an empty default <case/> to <switch/> nodes that don't have one
		foreach ($xpath->query('//switch[not(case[not(@test)])]') as $switch)
		{
			$switch->appendChild($ir->createElement('case'));
		}

		// Add an id attribute to <element/> nodes
		$id = 0;
		foreach ($ir->getElementsByTagName('element') as $element)
		{
			$element->setAttribute('id', ++$id);
		}

		// Add <closeTag/> elements to the internal representation, everywhere an open start tag
		// should be closed
		$query = '//applyTemplates[not(ancestor::attribute)]'
		       . '|'
		       . '//element'
		       . '|'
		       . '//output[not(ancestor::attribute)]';

		foreach ($xpath->query($query) as $node)
		{
			// Climb through this node's ascendants to find the closest <element/>, if applicable
			$parentNode = $node->parentNode;
			while ($parentNode)
			{
				if ($parentNode->nodeName === 'element')
				{
					$node->parentNode->insertBefore(
						$ir->createElement('closeTag'),
						$node
					)->setAttribute('id', $parentNode->getAttribute('id'));

					break;
				}

				$parentNode = $parentNode->parentNode;
			}

			// Append a <closeTag/> to <element/> nodes to ensure that empty elements get closed
			if ($node->nodeName === 'element')
			{
				$node->appendChild($ir->createElement('closeTag'))
				     ->setAttribute('id', $node->getAttribute('id'));
			}
		}

		// Get a snapshot of current internal representation
		$xml = $ir->saveXML();

		// Set a maximum number of loops to ward against infinite loops
		$remainingLoops = 10;

		// From now on, keep looping until no further modifications are applied
		do
		{
			$old = $xml;

			// If there's a <closeTag/> right after a <switch/>, clone the <closeTag/> at the end of
			// the every <case/> that does not end with a <closeTag/>
			$query = '//switch[name(following-sibling::*) = "closeTag"]';
			foreach ($xpath->query($query) as $switch)
			{
				$closeTag = $switch->nextSibling;

				foreach ($switch->childNodes as $case)
				{
					if (!$case->lastChild || $case->lastChild->nodeName !== 'closeTag')
					{
						$case->appendChild($closeTag->cloneNode());
					}
				}
			}

			// If there's a <closeTag/> at the beginning of every <case/>, clone it and insert it
			// right before the <switch/> unless there's already one
			$query = '//switch[not(preceding-sibling::closeTag)]';
			foreach ($xpath->query($query) as $switch)
			{
				foreach ($switch->childNodes as $case)
				{
					if (!$case->firstChild || $case->firstChild->nodeName !== 'closeTag')
					{
						// This case is either empty or does not start with a <closeTag/> so we skip
						// to the next <switch/>
						continue 2;
					}
				}

				// Insert the first child of the last <case/>, which should be the same <closeTag/>
				// as every other <case/>
				$switch->parentNode->insertBefore(
					$case->firstChild->cloneNode(),
					$switch
				);
			}

			// If there's a <closeTag/> right after a <switch/>, remove all <closeTag/> nodes at the
			// end of every <case/>
			$query = '//switch[name(following-sibling::*) = "closeTag"]';
			foreach ($xpath->query($query) as $switch)
			{
				foreach ($switch->childNodes as $case)
				{
					while ($case->lastChild && $case->lastChild->nodeName === 'closeTag')
					{
						$case->removeChild($case->lastChild);
					}
				}
			}

			// Finally, for each <closeTag/> remove duplicate <closeTag/> nodes that are either
			// siblings or descendants of a sibling
			$query = '//closeTag';
			foreach ($xpath->query($query) as $closeTag)
			{
				$id    = $closeTag->getAttribute('id');
				$query = 'following-sibling::*/descendant-or-self::closeTag[@id="' . $id . '"]';

				foreach ($xpath->query($query, $closeTag) as $dupe)
				{
					$dupe->parentNode->removeChild($dupe);
				}
			}

			$xml = $ir->saveXML();
		}
		while (--$remainingLoops > 0 && $xml !== $old);

		// Mark conditional <closeTag/> nodes
		foreach ($ir->getElementsByTagName('closeTag') as $closeTag)
		{
			$id = $closeTag->getAttribute('id');

			// For each <switch/> ancestor, look for a <closeTag/> and that is either a sibling or
			// the descendant of a sibling, and that matches the id
			$query = 'ancestor::switch/'
			       . 'following-sibling::*/'
			       . 'descendant-or-self::closeTag[@id = "' . $id . '"]';

			foreach ($xpath->query($query, $closeTag) as $following)
			{
				// Mark following <closeTag/> nodes to indicate that the status of this tag must
				// be checked before it is closed
				$following->setAttribute('check', '');

				// Mark the current <closeTag/> to indicate that it must set a flag to indicate
				// that its tag has been closed
				$closeTag->setAttribute('set', '');
			}
		}

		return $ir;
	}

	/**
	* Parse all the children of a given node
	*
	* @param  DOMNode $ir     Node in the internal representation that represents the parent node
	* @param  DOMNode $parent Parent node
	* @return void
	*/
	protected function parseChildren(DOMNode $ir, DOMNode $parent)
	{
		foreach ($parent->childNodes as $child)
		{
			switch ($child->nodeType)
			{
				case XML_COMMENT_NODE:
					// Do nothing
					break;

				case XML_TEXT_NODE:
					$ir->appendChild(
						$ir->ownerDocument->createElement(
							'output',
							htmlspecialchars(
								var_export(
									htmlspecialchars($child->textContent, ENT_NOQUOTES),
									true
								),
								ENT_NOQUOTES
							)
						)
					);
					break;

				case XML_ELEMENT_NODE:
					$this->parseNode($ir, $child);
					break;

				default:
					throw new RuntimeException("Cannot parse node '" . $child->nodeName . "'");
			}
		}
	}

	/**
	* Parse a given node into the internal representation of its template
	*
	* @param  DOMNode $ir     Node in the internal representation that represents the node's parent
	* @param  DOMNode $parent Node
	* @return void
	*/
	protected function parseNode(DOMNode $ir, DOMNode $node)
	{
		// XSL elements are parsed by the corresponding parseXsl* method
		if ($node->namespaceURI === self::XMLNS_XSL)
		{
			$methodName = 'parseXsl' . str_replace(' ', '', ucwords(str_replace('-', ' ', $node->localName)));

			if (!method_exists($this, $methodName))
			{
				throw new RuntimeException("Element '" . $node->nodeName . "' is not supported");
			}

			return $this->$methodName($ir, $node);
		}

		// Namespaced elements are not supported
		if (isset($node->namespaceURI))
		{
			throw new RuntimeException("Namespaced element '" . $node->nodeName . "' is not supported");
		}

		// Create an <element/> with a name attribute equal to given node's name
		$element = $ir->appendChild($ir->ownerDocument->createElement('element'));
		$element->setAttribute('name', var_export($node->localName, true));

		// Append an <attribute/> element for each of this node's attribute
		foreach ($node->attributes as $attribute)
		{
			$irAttribute = $element->appendChild($ir->ownerDocument->createElement('attribute'));
			$irAttribute->setAttribute('name', var_export($attribute->name, true));

			// Append an <output/> element to represent the attribute's value
			$irAttribute->appendChild(
				$ir->ownerDocument->createElement(
					'output',
					htmlspecialchars(
						$this->convertAttributeValueTemplate($attribute->value),
						ENT_NOQUOTES
					)
				)
			);
		}

		// Parse the content of this node
		$this->parseChildren($element, $node);
	}

	/**
	* Parse an <xsl:apply-templates/> into the internal representation
	*
	* @param  DOMNode $ir   Node in the internal representation that represents the node's parent
	* @param  DOMNode $node <xsl:apply-templates/> node
	* @return void
	*/
	protected function parseXslApplyTemplates(DOMNode $ir, DOMNode $node)
	{
		$applyTemplates = $ir->appendChild($ir->ownerDocument->createElement('applyTemplates'));

		if ($node->hasAttribute('select'))
		{
			$applyTemplates->setAttribute(
				'select',
				$node->getAttribute('select')
			);
		}
	}

	/**
	* Parse an <xsl:attribute/> into the internal representation
	*
	* @param  DOMNode $ir   Node in the internal representation that represents the node's parent
	* @param  DOMNode $node <xsl:attribute/> node
	* @return void
	*/
	protected function parseXslAttribute(DOMNode $ir, DOMNode $node)
	{
		$attribute = $ir->appendChild($ir->ownerDocument->createElement('attribute'));

		// Set this attribute's name
		$attribute->setAttribute(
			'name',
			$this->convertAttributeValueTemplate($node->getAttribute('name'))
		);

		// Parse this attribute's content
		$this->parseChildren($attribute, $node);
	}

	/**
	* Parse an <xsl:choose/> and its <xsl:when/> and <xsl:otherwise> children into the internal
	* representation
	*
	* @param  DOMNode $ir   Node in the internal representation that represents the node's parent
	* @param  DOMNode $node <xsl:choose/> node
	* @return void
	*/
	protected function parseXslChoose(DOMNode $ir, DOMNode $node)
	{
		$switch = $ir->appendChild($ir->ownerDocument->createElement('switch'));

		foreach ($node->getElementsByTagNameNS(self::XMLNS_XSL, 'when') as $when)
		{
			$case = $switch->appendChild($ir->ownerDocument->createElement('case'));
			$case->setAttribute('test', $this->convertCondition($when->getAttribute('test')));

			// Parse this branch's content
			$this->parseChildren($case, $when);
		}

		// Add the default branch, which is presumed to be last
		foreach ($node->getElementsByTagNameNS(self::XMLNS_XSL, 'otherwise') as $otherwise)
		{
			$case = $switch->appendChild($ir->ownerDocument->createElement('case'));

			// Parse this branch's content
			$this->parseChildren($case, $otherwise);

			// There should be only one <xsl:otherwise/> but we'll break anyway
			break;
		}
	}

	/**
	* Parse an <xsl:comment/> into the internal representation
	*
	* @param  DOMNode $ir   Node in the internal representation that represents the node's parent
	* @param  DOMNode $node <xsl:comment/> node
	* @return void
	*/
	protected function parseXslComment(DOMNode $ir, DOMNode $node)
	{
		$comment = $ir->appendChild($ir->ownerDocument->createElement('comment'));

		// Parse this branch's content
		$this->parseChildren($comment, $node);
	}

	/**
	* Parse an <xsl:copy-of/> into the internal representation
	*
	* NOTE: only attributes are supported
	*
	* @param  DOMNode $ir   Node in the internal representation that represents the node's parent
	* @param  DOMNode $node <xsl:copy-of/> node
	* @return void
	*/
	protected function parseXslCopyOf(DOMNode $ir, DOMNode $node)
	{
		$expr = $node->getAttribute('select');

		// <xsl:copy-of select="@foo"/>
		if (preg_match('#^@([-\\w]+)$#', $expr, $m))
		{
			$switch = $ir->appendChild($ir->ownerDocument->createElement('switch'));
			$case   = $switch->appendChild($ir->ownerDocument->createElement('case'));
			$case->setAttribute('test', $this->convertCondition($expr));

			$attribute = $case->appendChild($ir->ownerDocument->createElement('attribute'));
			$attribute->appendChild(
				$ir->ownerDocument->createElement(
					'output',
					$this->convertXPath($expr)
				)
			);
			$attribute->setAttribute('name', var_export($m[1], true));

			return;
		}

		// <xsl:copy-of select="@*"/>
		if ($expr === '@*')
		{
			$ir->appendChild($ir->ownerDocument->createElement('copyOfAttributes'));

			return;
		}

		throw new RuntimeException("Unsupported <xsl:copy-of/> expression '" . $expr . "'");
	}

	/**
	* Parse an <xsl:element/> into the internal representation
	*
	* @param  DOMNode $ir   Node in the internal representation that represents the node's parent
	* @param  DOMNode $node <xsl:element/> node
	* @return void
	*/
	protected function parseXslElement(DOMNode $ir, DOMNode $node)
	{
		$element = $ir->appendChild($ir->ownerDocument->createElement('element'));
		$element->setAttribute(
			'name',
			$this->convertAttributeValueTemplate($node->getAttribute('name'))
		);

		// Parse this element's content
		$this->parseChildren($element, $node);
	}

	/**
	* Parse an <xsl:if/> into the internal representation
	*
	* @param  DOMNode $ir   Node in the internal representation that represents the node's parent
	* @param  DOMNode $node <xsl:if/> node
	* @return void
	*/
	protected function parseXslIf(DOMNode $ir, DOMNode $node)
	{
		$switch = $ir->appendChild($ir->ownerDocument->createElement('switch'));
		$case   = $switch->appendChild($ir->ownerDocument->createElement('case'));
		$case->setAttribute('test', $this->convertCondition($node->getAttribute('test')));

		// Parse this branch's content
		$this->parseChildren($case, $node);
	}

	/**
	* Parse an <xsl:text/> into the internal representation
	*
	* @param  DOMNode $ir   Node in the internal representation that represents the node's parent
	* @param  DOMNode $node <xsl:text/> node
	* @return void
	*/
	protected function parseXslText(DOMNode $ir, DOMNode $node)
	{
		if ($node->textContent === '')
		{
			return;
		}

		$ir->appendChild(
			$ir->ownerDocument->createElement(
				'output',
				htmlspecialchars(var_export($node->textContent, true), ENT_NOQUOTES)
			)
		);
	}

	/**
	* Parse an <xsl:value-of/> into the internal representation
	*
	* @param  DOMNode $ir   Node in the internal representation that represents the node's parent
	* @param  DOMNode $node <xsl:value-of/> node
	* @return void
	*/
	protected function parseXslValueOf(DOMNode $ir, DOMNode $node)
	{
		$xpath      = new DOMXPath($ir->ownerDocument);
		$escapeMode = ($xpath->evaluate('count(ancestor::attribute)')) ? ENT_COMPAT : ENT_NOQUOTES;

		$ir->appendChild(
			$ir->ownerDocument->createElement(
				'output',
				htmlspecialchars(
					'htmlspecialchars(' . $this->convertXPath($node->getAttribute('select')) . ',' . $escapeMode . ")",
					ENT_NOQUOTES
				)
			)
		);
	}

	//==========================================================================
	// Serialization of the internal representation into PHP
	//==========================================================================

	/**
	* Serialize an <applyTemplates/> node
	*
	* @param  DOMNode $applyTemplates <applyTemplates/> node
	* @return void
	*/
	protected function serializeApplyTemplates(DOMNode $applyTemplates)
	{
		$this->php .= '$this->at($node';

		if ($applyTemplates->hasAttribute('select'))
		{
			$this->php .= ',' . var_export($applyTemplates->getAttribute('select'), true);
		}

		$this->php .= ');';
	}

	/**
	* Serialize an <attribute/> node
	*
	* @param  DOMNode $attribute <attribute/> node
	* @return void
	*/
	protected function serializeAttribute(DOMNode $attribute)
	{
		$this->php .= "\$this->out.=' '." . $attribute->getAttribute('name') . ".'=\"';";
		$this->serializeChildren($attribute);
		$this->php .= "\$this->out.='\"';";
	}

	/**
	* Serialize all the children of given node into PHP
	*
	* @param  DOMNode $ir Internal representation
	* @return void
	*/
	protected function serializeChildren(DOMNode $ir)
	{
		foreach ($ir->childNodes as $node)
		{
			$methodName = 'serialize' . ucfirst($node->localName);
			$this->$methodName($node);
		}
	}

	/**
	* Serialize a <closeTag/> node
	*
	* @param  DOMNode $closeTag <closeTag/> node
	* @return void
	*/
	protected function serializeCloseTag(DOMNode $closeTag)
	{
		$varName = '$t' . $closeTag->getAttribute('id');

		if ($closeTag->hasAttribute('check'))
		{
			$this->php .= 'if(!isset(' . $varName . ')){';
		}

		if ($closeTag->hasAttribute('set'))
		{
			$this->php .= $varName . '=1;';
		}

		$this->php .= "\$this->out.='>';";

		if ($closeTag->hasAttribute('check'))
		{
			$this->php .= '}';
		}
	}

	/**
	* Serialize a <comment/> node
	*
	* @param  DOMNode $comment <comment/> node
	* @return void
	*/
	protected function serializeComment(DOMNode $comment)
	{
		$this->php .= "\$this->out.='<!--';";
		$this->serializeChildren($comment);
		$this->php .= "\$this->out.='-->';";
	}

	/**
	* Serialize a <copyOfAttributes/> node
	*
	* @param  DOMNode $copyOfAttributes <copyOfAttributes/> node
	* @return void
	*/
	protected function serializeCopyOfAttributes(DOMNode $copyOfAttributes)
	{
		$this->php .= 'foreach($node->attributes as $attribute)';
		$this->php .= '{';
		$this->php .= '$this->out.=\' \';';
		$this->php .= '$this->out.=$attribute->name;';
		$this->php .= '$this->out.=\'="\';';
		$this->php .= '$this->out.=htmlspecialchars($attribute->value,' . ENT_COMPAT . ");";
		$this->php .= '$this->out.=\'"\';';
		$this->php .= '}';
	}

	/**
	* Serialize an <element/> node
	*
	* @param  DOMNode $element <element/> node
	* @return void
	*/
	protected function serializeElement(DOMNode $element)
	{
		$elName = $element->getAttribute('name');

		if (!preg_match("#^'[^']*'$#", $elName))
		{
			$varName    = uniqid('$e');
			$this->php .= $varName . '=' . $elName . ';';
			$elName     = $varName;
		}

		$this->php .= "\$this->out.='<'." . $elName . ';';

		// Whether we should check for voidness
		$checkVoid = false;

		// Test whether this element is empty
		if ($element->lastChild->nodeName === 'closeTag'
		 && $element->getElementsByTagName('closeTag')->length === 1)
		{
			$checkVoid = true;

			// Remove the <closeTag/> element
			$element->removeChild($element->lastChild);
		}

		// Serialize this element's content
		$this->serializeChildren($element);

		// Test whether this is a void element
		if ($checkVoid)
		{
			/**
			* Matches the names of all void elements
			* @link http://www.w3.org/TR/html-markup/syntax.html#void-elements
			*/
			$regexp = '/^(?:area|base|br|col|command|embed|hr|img|input|'
					. 'keygen|link|meta|param|source|track|wbr)$/D';

			// Test whether the element name is fixed
			if (preg_match("/^'([^']+)'$/", $elName, $m))
			{
				// Test whether the element is a void element
				if (preg_match($regexp, $m[1]))
				{
					$this->php .= "\$this->out.='";
					$this->php .= ($this->outputMethod !== 'html') ? '/' : '';
					$this->php .= ">';";

					return;
				}
			}
			else
			{
				// This is an empty element with a dynamic name, we need some PHP to determine
				// whether this is a void element
				$this->php .= 'if(preg_match(' . var_export($regexp, true) . ',' . $elName . ')){';
				$this->php .= '$this->out.=\'';
				$this->php .= ($this->outputMethod !== 'html') ? '/' : '';
				$this->php .= ">';}else{";
				$this->php .= "\$this->out.='></'." . $elName . ".'>';";
				$this->php .= '}';

				return;
			}

			// Since the only <closeTag/> element has been removed, this branch MUST close the start
			// tag of non-void elements
			$this->php .= "\$this->out.='>';";
		}

		$this->php .= "\$this->out.='</'." . $elName . ".'>';";
	}

	/**
	* Serialize an <output/> node
	*
	* @param  DOMNode $output <output/> node
	* @return void
	*/
	protected function serializeOutput(DOMNode $output)
	{
		if ($output->textContent !== '')
		{
			$this->php .= '$this->out.=' . $output->textContent . ';';
		}
	}

	/**
	* Serialize a <switch/> node
	*
	* @param  DOMNode $switch <switch/> node
	* @return void
	*/
	protected function serializeSwitch(DOMNode $switch)
	{
		$else = '';

		foreach ($switch->getElementsByTagName('case') as $case)
		{
			if ($case->hasAttribute('test'))
			{
				$this->php .= $else . 'if(' . $case->getAttribute('test') . ')';
			}
			elseif (!$case->firstChild)
			{
				// Empty default case
				continue;
			}
			else
			{
				$this->php .= 'else';
			}

			$else = 'else';

			$this->php .= '{';
			$this->serializeChildren($case);
			$this->php .= '}';
		}
	}

	//==========================================================================
	// Optimization of the generated code
	//==========================================================================

	/**
	* Optimize the generated code
	*
	* @return void
	*/
	protected function optimizeCode()
	{
		$tokens = token_get_all('<?php ' . $this->php);

		// Limit the number of loops to 100, in case something would make it loop indefinitely
		$remainingLoops = 100;
		do
		{
			$old = $tokens;
			$this->optimizeAssignments($tokens);
			$this->optimizeConcatenations($tokens);
			$this->optimizeLiterals($tokens);
		}
		while (--$remainingLoops && $tokens !== $old);

		// Remove the first token, which should be T_OPEN_TAG, aka "<?php"
		unset($tokens[0]);

		// Rebuild the source;
		$this->php = '';
		foreach ($tokens as $token)
		{
			$this->php .= (is_string($token)) ? $token : $token[1];
		}
	}

	/**
	* Optimize variable assignments in an array of PHP tokens
	*
	* @para   array &$tokens PHP tokens from tokens_get_all()
	* @return void
	*/
	protected function optimizeAssignments(array &$tokens)
	{
		$cnt = count($tokens);

		$i = 0;
		while (++$i < $cnt)
		{
			if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_CONCAT_EQUAL)
			{
				continue;
			}

			// Get the index of the last T_VARIABLE token and build the name of the var
			$varIndex = $i;
			$varName  = '';
			do
			{
				--$varIndex;

				$str = (is_string($tokens[$varIndex])) ? $tokens[$varIndex] : $tokens[$varIndex][1];
				$varName = $str . $varName;
			}
			while (!is_array($tokens[$varIndex]) || $tokens[$varIndex][0] !== T_VARIABLE);

			// Capture the tokens used for the assignment from the variable to the operator
			$assignment = array_slice($tokens, $varIndex, 1 + $i - $varIndex);

			// We're only interested in $this->out assignments
			if ($varName !== '$this->out')
			{
				continue;
			}

			// Move the cursor to next semicolon
			while ($tokens[++$i] !== ';');

			// Move the cursor past the semicolon
			++$i;

			// Test whether the assignment is followed by another compatible assignment
			if (array_slice($tokens, $i, count($assignment)) === $assignment)
			{
				// Remove the following assignment and replace the semicolon with a concatenation
				// operator
				array_splice($tokens, $i, count($assignment));
				$tokens[$i - 1] = '.';

				// Adjust the tokens count
				$cnt = count($tokens);

				// Rewind the cursor to the beginning and continue
				$i = $varIndex;

				continue;
			}
		}

		$tokens = array_values($tokens);
	}

	/**
	* Optimize string concatenations in an array of PHP tokens
	*
	* @para   array &$tokens PHP tokens from tokens_get_all()
	* @return void
	*/
	protected function optimizeConcatenations(array &$tokens)
	{
		$cnt = count($tokens);

		$i = 0;
		while (++$i < $cnt)
		{
			if ($tokens[$i] !== '.')
			{
				continue;
			}

			// Merge concatenated strings
			if ($tokens[$i - 1][0]    === T_CONSTANT_ENCAPSED_STRING
			 && $tokens[$i + 1][0]    === T_CONSTANT_ENCAPSED_STRING
			 && $tokens[$i - 1][1][0] === $tokens[$i + 1][1][0])
			{
				// Merge both strings into the right string
				$tokens[$i + 1][1] = substr($tokens[$i - 1][1], 0, -1)
				                   . substr($tokens[$i + 1][1], 1);

				// Unset the tokens that have been optimized away
				unset($tokens[$i - 1]);
				unset($tokens[$i]);

				// Advance the cursor
				++$i;

				continue;
			}

			// Merge htmlspecialchars() calls
			if ($tokens[$i + 1][0] === T_STRING
			 && $tokens[$i + 1][1] === 'htmlspecialchars'
			 && $tokens[$i + 2]    === '('
			 && $tokens[$i - 1]    === ')'
			 && $tokens[$i - 2][0] === T_LNUMBER
			 && $tokens[$i - 3]    === ',')
			{
				// Save the escape mode of the first call
				$escapeMode = $tokens[$i - 2][1];

				// Save the index of the comma that comes after the first argument of the first call
				$startIndex = $i - 3;

				// Save the index of the parenthesis that follows the second htmlspecialchars
				$endIndex = $i + 2;

				// Move the cursor to the first comma of the second call
				$i = $endIndex;
				$parens = 0;
				while (++$i < $cnt)
				{
					if ($tokens[$i] === ',' && !$parens)
					{
						break;
					}

					if ($tokens[$i] === '(')
					{
						++$parens;
					}
					elseif ($tokens[$i] === ')')
					{
						--$parens;
					}
				}

				if ($tokens[$i + 1][0] === T_LNUMBER
				 && $tokens[$i + 1][1] === $escapeMode)
				{
					 // Replace the first comma of the first call with a concatenator operator
					 $tokens[$startIndex] = '.';

					 // Move the cursor back to the first comma then advance it and delete
					 // everything up till the parenthesis of the second call, included
					 $i = $startIndex;
					 while (++$i <= $endIndex)
					 {
						 unset($tokens[$i]);
					 }

					 continue;
				}

				// @codeCoverageIgnoreStart
				// This part is unreachable because it is currently impossible to produce two
				// consecutive htmlspecialchars() calls with different escape modes
			}
			// @codeCoverageIgnoreEnd
		}

		$tokens = array_values($tokens);
	}

	/**
	* Optimize htmlspecialchars() calls on literals
	*
	* @para   array &$tokens PHP tokens from tokens_get_all()
	* @return void
	*/
	protected function optimizeLiterals(array &$tokens)
	{
		$cnt = count($tokens);

		$i = 0;
		while (++$i < $cnt)
		{
			if (is_array($tokens[$i])
			 && $tokens[$i    ][0] === T_CONSTANT_ENCAPSED_STRING
			 && $tokens[$i - 1]    === '('
			 && $tokens[$i - 2][0] === T_STRING
			 && $tokens[$i - 2][1] === 'htmlspecialchars'
			 && $tokens[$i + 1]    === ','
			 && $tokens[$i + 2][0] === T_LNUMBER
			 && $tokens[$i + 3]    === ')')
			{
				$tokens[$i][1] = var_export(
					htmlspecialchars(
						stripslashes(substr($tokens[$i][1], 1, -1)),
						$tokens[$i + 2][1]
					),
					true
				);

				unset($tokens[$i - 1]);
				unset($tokens[$i - 2]);
				unset($tokens[$i + 1]);
				unset($tokens[$i + 2]);
				unset($tokens[$i + 3]);

				$i += 3;
			}
		}

		$tokens = array_values($tokens);
	}

	//==========================================================================
	// XPath conversion
	//==========================================================================

	/**
	* Convert an attribute value template into PHP
	*
	* @link http://www.w3.org/TR/xslt#dt-attribute-value-template
	*
	* @param  string $attrValue Attribute value template
	* @return void
	*/
	protected function convertAttributeValueTemplate($attrValue)
	{
		if ($attrValue === '')
		{
			return "''";
		}

		$phpExpressions = [];
		foreach (TemplateHelper::parseAttributeValueTemplate($attrValue) as $token)
		{
			if ($token[0] === 'literal')
			{
				$phpExpressions[] = var_export(htmlspecialchars($token[1], ENT_COMPAT), true);
			}
			else
			{
				$phpExpressions[] = 'htmlspecialchars(' . $this->convertXPath($token[1]) . ',' . ENT_COMPAT . ")";
			}
		}

		return implode('.', $phpExpressions);
	}

	/**
	* Convert an XPath condition into a PHP condition
	*
	* This method is similar to convertXPath() but it selectively replaces some simple conditions
	* with the corresponding DOM method for performance reasons
	*
	* @param  string $expr XPath expression
	* @return string       PHP code
	*/
	protected function convertCondition($expr)
	{
		// <xsl:if test="@foo">
		// if ($node->hasAttribute('foo'))
		if (preg_match('#^@([-\\w]+)$#', $expr, $m))
		{
			return '$node->hasAttribute(' . var_export($m[1], true) . ')';
		}

		// <xsl:if test="not(@foo)">
		// if (!$node->hasAttribute('foo'))
		if (preg_match('#^not\\(@([-\\w]+)\\)$#', $expr, $m))
		{
			return '!$node->hasAttribute(' . var_export($m[1], true) . ')';
		}

		// <xsl:if test="$foo">
		// if (!empty($this->params['foo']))
		if (preg_match('#^\\$(\\w+)$#', $expr, $m))
		{
			return '!empty($this->params[' . var_export($m[1], true) . '])';
		}

		// <xsl:if test="not($foo)">
		// if (empty($this->params['foo']))
		if (preg_match('#^not\\(\\$(\\w+)\\)$#', $expr, $m))
		{
			return 'empty($this->params[' . var_export($m[1], true) . '])';
		}

		// <xsl:if test=".=':)'">
		// if ($node->textContent===':)')
		//
		// <xsl:if test="@foo=':)'">
		// if ($node->getAttribute('foo')===':)')
		//
		// NOTE: this optimization is mainly for the Emoticons plugin
		$operandExpr = '(@[-\\w]+|\\.|"[^"]*"|\'[^\']*\')';
		$testExpr    = $operandExpr . '\\s*=\\s*' . $operandExpr;
		if (preg_match('#^' . $testExpr . '(?:\\s*or\\s*' . $testExpr . ')*$#', $expr))
		{
			preg_match_all('#' . $testExpr . '#', $expr, $matches, PREG_SET_ORDER);

			$tests = [];
			foreach ($matches as $m)
			{
				$tests[] = $this->convertXPath($m[1]) . '===' . $this->convertXPath($m[2]);
			}

			return implode('||', $tests);
		}

		// If the condition does not seem to contain a relational expression, or start with a
		// function call, we wrap it inside of a boolean() call
		if (!preg_match('#[=<>]|\\bor\\b|\\band\\b|^[-\\w]+\\(#', $expr))
		{
			// <xsl:if test="parent::foo">
			// if ($this->xpath->evaluate("boolean(parent::foo)",$node))
			$expr = 'boolean(' . $expr . ')';
		}

		// <xsl:if test="@foo='bar'">
		// if ($this->xpath->evaluate("@foo='bar'",$node))
		return $this->convertXPath($expr);
	}

	/**
	* Convert an XPath expression into PHP code
	*
	* @param  string $expr XPath expression
	* @return string       PHP code
	*/
	protected function convertXPath($expr)
	{
		// <xsl:value-of select="@foo"/>
		// $this->out .= $node->getAttribute('foo');
		if (preg_match('#^@([-\\w]+)$#', $expr, $m))
		{
			return '$node->getAttribute(' . var_export($m[1], true) . ')';
		}

		// <xsl:value-of select="."/>
		// $this->out .= $node->textContent;
		if ($expr === '.')
		{
			return '$node->textContent';
		}

		// <xsl:value-of select="$foo"/>
		// $this->out .= $this->params['foo'];
		if (preg_match('#^\\$(\\w+)$#', $expr, $m))
		{
			return '$this->params[' . var_export($m[1], true) . ']';
		}

		// <xsl:value-of select="'foo'"/>
		// $this->out .= 'foo';
		if (preg_match('#^\\s*("[^"]*"|\'[^\']*\')\\s*#', $expr, $m))
		{
			return var_export(substr($m[1], 1, -1), true);
		}

		// If the condition does not seem to contain a relational expression, or start with a
		// function call, we wrap it inside of a string() call
		if (!preg_match('#[=<>]|\\bor\\b|\\band\\b|^[-a-z]+\\(#', $expr))
		{
			$expr = 'string(' . $expr . ')';
		}

		// Parse the expression for variables
		$phpTokens = [];
		$pos = 0;
		$len = strlen($expr);
		while ($pos < $len)
		{
			// If we have a string literal, capture it and add its PHP representation
			if ($expr[$pos] === "'" || $expr[$pos] === '"')
			{
				$nextPos = strpos($expr, $expr[$pos], 1 + $pos);
				if ($nextPos === false)
				{
					throw new RuntimeException('Unterminated string literal in XPath expression ' . var_export($expr, true));
				}

				// Capture the string
				$phpTokens[] = var_export(substr($expr, $pos, $nextPos + 1 - $pos), true);

				// Move the cursor past the string
				$pos = $nextPos + 1;

				continue;
			}

			// Variables in XPath expressions have to be resolved at runtime via getParamAsXPath()
			if ($expr[$pos] === '$' && preg_match('/\\$(\\w+)/', $expr, $m, 0, $pos))
			{
				$phpTokens[] = '$this->getParamAsXPath(' . var_export($m[1], true) . ')';
				$pos += strlen($m[0]);

				continue;
			}

			// Capture everything up to the next interesting character
			$spn = strcspn($expr, '\'"$', $pos);
			if ($spn)
			{
				$phpTokens[] = var_export(substr($expr, $pos, $spn), true);
				$pos += $spn;
			}
		}

		return '$this->xpath->evaluate(' . implode('.', $phpTokens) . ',$node)';
	}
}