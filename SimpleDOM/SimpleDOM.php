<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2009 The SimpleDOM authors
* @copyright Copyright (c) 2010 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/

/**
* @package SimpleDOM
* @version $Id$
* @link    $URL$
*/
namespace
{
	/**
	* Alias for simplexml_load_file()
	*
	* @return SimpleDOM
	*/
	function simpledom_load_file($filename)
	{
		$args = func_get_args();

		if (isset($args[0]) && !isset($args[1]))
		{
			$args[1] = 's9e\\Toolkit\\SimpleDOM\\SimpleDOM';
		}

		return call_user_func_array('simplexml_load_file', $args);
	}

	/**
	* Alias for simplexml_load_string()
	*
	* @return SimpleDOM
	*/
	function simpledom_load_string($string)
	{
		$args = func_get_args();

		if (isset($args[0]) && !isset($args[1]))
		{
			$args[1] = 's9e\\Toolkit\\SimpleDOM\\SimpleDOM';
		}

		return call_user_func_array('simplexml_load_string', $args);
	}
}

namespace s9e\Toolkit\SimpleDOM
{
	use BadMethodCallException,
		DOMAttr,
		DOMDocument,
		DOMDocumentFragment,
		DOMElement,
		DOMNode,
		DOMNodeList,
		DOMText,
		DOMXPath,
		InvalidArgumentException,
		SimpleXMLElement,
		stdClass,
		XSLTCache,
		XSLTProcessor;

	/**
	* @package SimpleDOM
	*/
	class SimpleDOM extends SimpleXMLElement
	{
		//=================================
		// Factories
		//=================================

		/**
		* Create a SimpleDOM object from a HTML string
		*
		* @param  string $source  HTML source
		* @param  mixed  &$errors Passed by reference. Will be replaced by an array of
		*                         LibXMLError objects if applicable
		* @return SimpleDOM
		*/
		static public function loadHTML($source, &$errors = null)
		{
			return static::fromHTML('loadHTML', $source, $errors);
		}

		/**
		* Create a SimpleDOM object from a HTML file
		*
		* @param  string $filename Path/URL to HTML file
		* @param  mixed  &$errors  Passed by reference. Will be replaced by an array of
		*                          LibXMLError objects if applicable
		* @return SimpleDOM
		*/
		static public function loadHTMLFile($filename, &$errors = null)
		{
			return static::fromHTML('loadHTMLFile', $filename, $errors);
		}


		//=================================
		// DOM stuff
		//=================================

		/** @ignore **/
		public function __call($name, $args)
		{
			$passthrough = array(
				// From DOMElement
				'getAttribute'           => 'method',
				'getAttributeNS'         => 'method',
				'getElementsByTagName'   => 'method',
				'getElementsByTagNameNS' => 'method',
				'hasAttribute'           => 'method',
				'hasAttributeNS'         => 'method',
				'removeAttribute'        => 'method',
				'removeAttributeNS'      => 'method',
				'setAttribute'           => 'method',
				'setAttributeNS'         => 'method',

				// From DOMNode
				'appendChild'        => 'insert',
				'insertBefore'       => 'insert',
				'replaceChild'       => 'insert',

				'cloneNode'          => 'method',
				'getLineNo'          => 'method',
				'hasAttributes'      => 'method',
				'hasChildNodes'      => 'method',
				'isSameNode'         => 'method',
				'lookupNamespaceURI' => 'method',
				'lookupPrefix'       => 'method',
				'normalize'          => 'method',
				'removeChild'        => 'method',

				'nodeName'        => 'property',
				'nodeValue'       => 'property',
				'nodeType'        => 'property',
				'parentNode'      => 'property',
				'childNodes'      => 'property',
				'firstChild'      => 'property',
				'lastChild'       => 'property',
				'previousSibling' => 'property',
				'nextSibling'     => 'property',
				'namespaceURI'    => 'property',
				'prefix'          => 'property',
				'localName'       => 'property',
				'baseURI'         => 'property',
				'textContent'     => 'property'
			);

			$dom = dom_import_simplexml($this);

			if (!isset($passthrough[$name]))
			{
				if (method_exists($dom, $name))
				{
					throw new BadMethodCallException('DOM method ' . $name . '() is not supported');
				}

				if (property_exists($dom, $name))
				{
					throw new BadMethodCallException('DOM property ' . $name . ' is not supported');
				}

				throw new BadMethodCallException('Undefined method ' . get_class($this) . '::' . $name . '()');
			}

			switch ($passthrough[$name])
			{
				case 'insert':
					if (!empty($args)
					 && $args[0] instanceof SimpleXMLElement)
					{
						$args[0] = $dom->ownerDocument->importNode(
							dom_import_simplexml($args[0]),
							true
						);
					}
					// no break; here

				case 'method':
					foreach ($args as &$arg)
					{
						if ($arg instanceof SimpleXMLElement)
						{
							$arg = dom_import_simplexml($arg);
						}
					}
					unset($arg);

					$ret = call_user_func_array(array($dom, $name), $args);
					break;

				case 'property':
					if (!empty($args))
					{
						$dom->$name = $args[0];
					}

					$ret = $dom->$name;
					break;
			}

			if ($ret instanceof DOMText)
			{
				return $ret->textContent;
			}

			if ($ret instanceof DOMNode)
			{
				if ($ret instanceof DOMAttr)
				{
					/**
					* Methods that affect attributes can't return the attributes themselves.
					* Instead, we make them chainable
					*/
					return $this;
				}

				return static::import($ret);
			}

			if ($ret instanceof DOMNodeList)
			{
				$class = get_class($this);
				$list  = array();
				$i     = -1;

				while (++$i < $ret->length)
				{
					$node = $ret->item($i);
					$list[$i] = ($node instanceof DOMText)
					          ? $node->textContent
					          : simplexml_import_dom($node, $class);
				}

				return $list;
			}

			return $ret;
		}


		//=================================
		// DOM convenience methods
		//=================================

		/**
		* Add a new sibling before this node
		*
		* This is a convenience method. The same result can be achieved with
		* <code>
		* $node->parentNode()->insertBefore($new, $node);
		* </code>
		*
		* @param  SimpleXMLElement $new New node
		* @return SimpleDOM             The inserted node
		*/
		public function insertBeforeSelf(SimpleXMLElement $new)
		{
			$tmp = dom_import_simplexml($this);
			$node = $tmp->ownerDocument->importNode(dom_import_simplexml($new), true);

			return static::import($this->insertNode($tmp, $node, 'before'));
		}

		/**
		* Add a new sibling after this node
		*
		* This is a convenience method. The same result can be achieved with
		* <code>
		* $node->parentNode()->insertBefore($new, $node->nextSibling());
		* </code>
		*
		* @param  SimpleXMLElement $new New node
		* @return SimpleDOM             The inserted node
		*/
		public function insertAfterSelf(SimpleXMLElement $new)
		{
			$tmp = dom_import_simplexml($this);
			$node = $tmp->ownerDocument->importNode(dom_import_simplexml($new), true);

			return static::import($this->insertNode($tmp, $node, 'after'));
		}

		/**
		* Remove this node from document
		*
		* This is a convenience method. The same result can be achieved with
		* <code>
		* $node->parentNode()->removeChild($node);
		* </code>
		*
		* @return SimpleDOM The removed node
		*/
		public function remove()
		{
			$tmp = dom_import_simplexml($this);

			if ($tmp->isSameNode($tmp->ownerDocument->documentElement))
			{
				throw new BadMethodCallException('remove() cannot be used to remove the root node');
			}

			return static::import($tmp->parentNode->removeChild($tmp));
		}

		/**
		* Replace this node
		*
		* This is a convenience method. The same result can be achieved with
		* <code>
		* $node->parentNode()->replaceChild($new, $node);
		* </code>
		*
		* @param  SimpleXMLElement $new New node
		* @return SimpleDOM             Replaced node on success
		*/
		public function replace(SimpleXMLElement $new)
		{
			$old = dom_import_simplexml($this);
			$new = $old->ownerDocument->importNode(dom_import_simplexml($new), true);

			return static::import($old->parentNode->replaceChild($new, $old));
		}

		/**
		* Remove all elements matching a XPath expression
		*
		* @param  string $xpath XPath expression
		* @return array         Array of removed nodes on success or FALSE on failure
		*/
		public function removeNodes($xpath)
		{
			return $this->zapNodes($xpath, 'removeNodes');
		}

		/**
		* Remove all elements matching a XPath expression
		*
		* @param  string           $xpath XPath expression
		* @param  SimpleXMLElement $new   Replacement node
		* @return array                   Array of replaced nodes on success or FALSE on failure
		*/
		public function replaceNodes($xpath, SimpleXMLElement $new)
		{
			return $this->zapNodes($xpath, 'replaceNodes', $new);
		}

		/**
		* Copy all attributes from a node to current node
		*
		* @param  SimpleXMLElement $src       Source node
		* @param  bool             $overwrite If TRUE, overwrite existing attributes.
		*                                     Otherwise, ignore duplicate attributes
		* @return SimpleDOM                   Current node
		*/
		public function copyAttributesFrom(SimpleXMLElement $src, $overwrite = true)
		{
			$dom = dom_import_simplexml($this);

			foreach (dom_import_simplexml($src)->attributes as $attr)
			{
				if ($overwrite
				 || !$dom->hasAttributeNS($attr->namespaceURI, $attr->nodeName))
				{
					$dom->setAttributeNS($attr->namespaceURI, $attr->nodeName, $attr->nodeValue);
				}
			}

			return $this;
		}

		/**
		* Clone all children from a node and add them to current node
		*
		* This method takes a snapshot of the children nodes then append them in order to avoid infinite
		* recursion if the destination node is a descendant of or the source node it
		*
		* @param  SimpleXMLElement $src  Source node
		* @param  bool             $deep If TRUE, clone descendant nodes as well
		* @return SimpleDOM              Current node
		*/
		public function cloneChildrenFrom(SimpleXMLElement $src, $deep = true)
		{
			$src = dom_import_simplexml($src);
			$dst = dom_import_simplexml($this);
			$doc = $dst->ownerDocument;

			$fragment = $doc->createDocumentFragment();
			foreach ($src->childNodes as $child)
			{
				$fragment->appendChild($doc->importNode($child->cloneNode($deep), $deep));
			}
			$dst->appendChild($fragment);

			return $this;
		}

		/**
		* Return the first node of the result of an XPath expression
		*
		* @param  string $xpath XPath expression
		* @return mixed         SimpleDOM object if any node was returned, NULL otherwise
		*/
		public function firstOf($xpath)
		{
			$nodes = $this->xpath($xpath);
			return (isset($nodes[0])) ? $nodes[0] : null;
		}


		//=================================
		// DOM extra
		//=================================

		/**
		* Insert a CDATA section
		*
		* @param  string    $content CDATA content
		* @param  string    $mode    Where to add this node: 'append' to current node,
		*                            'before' current node or 'after' current node
		* @return SimpleDOM          Current node
		*/
		public function insertCDATA($content, $mode = 'append')
		{
			$this->insert('CDATASection', $content, $mode);
			return $this;
		}

		/**
		* Insert a comment node
		*
		* @param  string    $content Comment content
		* @param  string    $mode    Where to add this node: 'append' to current node,
		*                            'before' current node or 'after' current node
		* @return SimpleDOM          Current node
		*/
		public function insertComment($content, $mode = 'append')
		{
			$this->insert('Comment', $content, $mode);
			return $this;
		}

		/**
		* Insert a text node
		*
		* @param  string    $content CDATA content
		* @param  string    $mode    Where to add this node: 'append' to current node,
		*                            'before' current node or 'after' current node
		* @return SimpleDOM          Current node
		*/
		public function insertText($content, $mode = 'append')
		{
			$this->insert('TextNode', $content, $mode);
			return $this;
		}


		/**
		* Insert raw XML data
		*
		* @param  string    $xml  XML to insert
		* @param  string    $mode Where to add this tag: 'append' to current node,
		*                         'before' current node or 'after' current node
		* @return SimpleDOM       Current node
		*/
		public function insertXML($xml, $mode = 'append')
		{
			$tmp = dom_import_simplexml($this);
			$fragment = $tmp->ownerDocument->createDocumentFragment();

			/**
			* Use internal errors while we append the XML
			*/
			$useErrors = libxml_use_internal_errors(true);
			$success   = $fragment->appendXML($xml);
			libxml_use_internal_errors($useErrors);

			if (!$success)
			{
				throw new InvalidArgumentException(libxml_get_last_error()->message);
			}

			$this->insertNode($tmp, $fragment, $mode);

			return $this;
		}

		/**
		* Insert a Processing Instruction
		*
		* The content of the PI can be passed either as string or as an associative array.
		*
		* @param  string       $target Target of the processing instruction
		* @param  string|array $data   Content of the processing instruction
		* @return bool                 TRUE on success, FALSE on failure
		*/
		public function insertPI($target, $data = null, $mode = 'before')
		{
			$tmp = dom_import_simplexml($this);
			$doc = $tmp->ownerDocument;

			if (isset($data))
			{
				if (is_array($data))
				{
					$str = '';
					foreach ($data as $k => $v)
					{
						$str .= $k . '="' . htmlspecialchars($v) . '" ';
					}

					$data = substr($str, 0, -1);
				}
				else
				{
					$data = (string) $data;
				}

				$pi = $doc->createProcessingInstruction($target, $data);
			}
			else
			{
				$pi = $doc->createProcessingInstruction($target);
			}

			if ($pi !== false)
			{
				$this->insertNode($tmp, $pi, $mode);
			}

			return $this;
		}

		/**
		* Set several attributes at once
		*
		* @param  array     $attr Attributes as name => value pairs
		* @param  string    $ns   Namespace for the attributes
		* @return SimpleDOM       Current node
		*/
		public function setAttributes(array $attr, $ns = null)
		{
			$dom = dom_import_simplexml($this);
			foreach ($attr as $k => $v)
			{
				$dom->setAttributeNS($ns, $k, $v);
			}
			return $this;
		}

		/**
		* Return the content of current node as a string
		*
		* Roughly emulates the innerHTML property found in browsers, although it is not meant to
		* perfectly match any specific implementation.
		*
		* @todo Write a test for HTML entities that can't be represented in the document's encoding
		*
		* @return string Content of current node
		*/
		public function innerHTML()
		{
			$dom = dom_import_simplexml($this);
			$doc = $dom->ownerDocument;

			$html = '';
			foreach ($dom->childNodes as $child)
			{
				$html .= ($child instanceof DOMText) ? $child->textContent : $doc->saveXML($child);
			}

			return $html;
		}

		/**
		* Return the XML content of current node as a string
		*
		* @return string Content of current node
		*/
		public function innerXML()
		{
			$xml = $this->outerXML();
			$pos = 1 + strpos($xml, '>');
			$len = strrpos($xml, '<') - $pos;
			return substr($xml, $pos, $len);
		}

		/**
		* Return the XML representing this node and its child nodes
		*
		* NOTE: unlike asXML() it doesn't return the XML prolog
		*
		* @return string Content of current node
		*/
		public function outerXML()
		{
			$dom = dom_import_simplexml($this);
			return $dom->ownerDocument->saveXML($dom);
		}

		/**
		* Return all elements with the given class name
		*
		* Should work like DOM0's method
		*
		* @param  string $class Class name
		* @return array         Array of SimpleDOM nodes
		*/
		public function getElementsByClassName($class)
		{
			if (strpos($class, '"') !== false
			 || strpos($class, "'") !== false)
			{
				return array();
			}

			$xpath = './/*[contains(concat(" ", @class, " "), " ' . htmlspecialchars($class) . ' ")]';
			return $this->xpath($xpath);
		}

		/**
		* Test whether current node has given class
		*
		* @param  string $class Class name
		* @return bool
		*/
		public function hasClass($class)
		{
			return in_array($class, explode(' ', $this['class']));
		}

		/**
		* Add given class to current node
		*
		* @param  string    $class Class name
		* @return SimpleDOM        Current node
		*/
		public function addClass($class)
		{
			if (!$this->hasClass($class))
			{
				$current = (string) $this['class'];

				if ($current !== ''
				 && substr($current, -1) !== ' ')
				{
					$this['class'] .= ' ';
				}

				$this['class'] .= $class;
			}

			return $this;
		}

		/**
		* Remove given class from current node
		*
		* @param  string    $class Class name
		* @return SimpleDOM        Current node
		*/
		public function removeClass($class)
		{
			while ($this->hasClass($class))
			{
				$this['class'] = substr(str_replace(' ' . $class . ' ', ' ', ' ' . $this['class'] . ' '), 1, -1);
			}
			return $this;
		}


		//=================================
		// Utilities
		//=================================

		/**
		* Return the current element as a DOMElement
		*
		* @return DOMElement
		*/
		public function asDOM()
		{
			return dom_import_simplexml($this);
		}

		/**
		* Return the current node slightly prettified
		*
		* Elements will be indented, empty elements will be minified. The result isn't mean to be
		* perfect, I'm sure there are better prettifiers out there.
		*
		* @param  string $filepath If set, save the result to this file
		* @return mixed            If $filepath is set, will return TRUE if the file was succesfully
		*                          written or FALSE otherwise. If $filepath isn't set, it returns
		*                          the result as a string
		*/
		public function asPrettyXML($filepath = null)
		{
			$dom = dom_import_simplexml($this);
			$doc = $dom->ownerDocument;
			$doc->formatOutput = true;

			if (isset($filepath))
			{
				return (bool) $doc->save($filepath);
			}

			return $doc->saveXML();
		}

		/**
		* Transform current node and return the result
		*
		* Will take advantage of {@link http://pecl.php.net/package/xslcache PECL's xslcache}
		* if available
		*
		* @param  string $filepath    Path to stylesheet
		* @param  bool   $useXSLCache If TRUE, use the XSL Cache extension if available
		* @return string              Result
		*/
		public function XSLT($filepath, $useXSLCache = true)
		{
			if ($useXSLCache && extension_loaded('xslcache'))
			{
				$xslt = new XSLTCache;
				$xslt->importStylesheet($filepath);
			}
			else
			{
				$xsl = new DOMDocument;
				$xsl->load($filepath);

				$xslt = new XSLTProcessor;
				$xslt->importStylesheet($xsl);
			}

			return $xslt->transformToXML(dom_import_simplexml($this));
		}

		/**
		* Run an XPath query and sort the result
		*
		* This method accepts any number of arguments in a way similar to {@link
		* http://docs.php.net/manual/en/function.array-multisort.php array_multisort()}
		*
		* <code>
		* // Retrieve all <x/> nodes, sorted by @foo ascending, @bar descending
		* $root->sortedXPath('//x', '@foo', '@bar', SORT_DESC);
		*
		* // Same, but sort @foo numerically and @bar as strings
		* $root->sortedXPath('//x', '@foo', SORT_NUMERIC, '@bar', SORT_STRING, SORT_DESC);
		* </code>
		*
		* @param  string $xpath XPath expression
		* @return void
		*/
		public function sortedXPath($xpath)
		{
			$nodes   =  $this->xpath($xpath);
			$args    =  func_get_args();
			$args[0] =& $nodes;

			call_user_func_array(array(get_class($this), 'sort'), $args);

			return $nodes;
		}

		/**
		* Sort this node's children
		*
		* ATTENTION: text nodes are not supported. If current node has text nodes, they may be lost
		*            in the process
		*
		* @return SimpleDOM This node
		*/
		public function sortChildren()
		{
			$nodes = $this->removeNodes('*');
			$args  = func_get_args();

			array_unshift($args, null);
			$args[0] =& $nodes;

			call_user_func_array(array(get_class($this), 'sort'), $args);

			foreach ($nodes as $node)
			{
				$this->appendChild($node);
			}

			return $this;
		}

		/**
		* Sort an array of nodes
		*
		* Note that nodes are sorted in place, nothing is returned
		*
		* @see sortedXPath
		*
		* @param  array &$nodes Array of SimpleXMLElement
		* @return void
		*/
		static public function sort(array &$nodes)
		{
			$args = func_get_args();
			unset($args[0]);

			$sort = array();
			$tmp  = array();

			foreach ($args as $k => $arg)
			{
				if (is_string($arg))
				{
					$tmp[$k] = array();

					foreach ($nodes as $node)
					{
						if ($node instanceof SimpleXMLElement)
						{
							$node = dom_import_simplexml($node);
						}
						elseif (!($node instanceof DOMNode))
						{
							throw new InvalidArgumentException(__METHOD__ . ' only works on nodes from SimpleXML or DOM');
						}

						$dxp = new DOMXPath($node->ownerDocument);
						$tmp[$k][] = $dxp->evaluate('string(' . $arg . ')', $node);
					}
				}
				else
				{
					$tmp[$k] = $arg;
				}

				/**
				* array_multisort() wants everything to be passed as reference so we have to cheat
				*/
				$sort[] =& $tmp[$k];
			}

			$sort[] =& $nodes;

			call_user_func_array('array_multisort', $sort);
		}


		//=================================
		// Internal stuff
		//=================================

		/**#@+
		* @ignore
		*/
		protected function insert($type, $content, $mode)
		{
			$tmp	= dom_import_simplexml($this);
			$method = 'create' . $type;
			$new    = $tmp->ownerDocument->$method($content);

			return $this->insertNode($tmp, $new, $mode);
		}

		protected function insertNode(DOMNode $tmp, DOMNode $node, $mode)
		{
			if ($mode === 'before'
			 || $mode === 'after')
			{
				if ($node instanceof DOMText
				 || $node instanceof DOMElement
				 || $node instanceof DOMDocumentFragment)
				{
					if ($tmp->isSameNode($tmp->ownerDocument->documentElement))
					{
						throw new BadMethodCallException('Cannot insert a ' . get_class($node) . ' node outside of the root node');
					}
				}

				if ($mode === 'before')
				{
					return $tmp->parentNode->insertBefore($node, $tmp);
				}

				if ($tmp->nextSibling)
				{
					return $tmp->parentNode->insertBefore($node, $tmp->nextSibling);
				}

				return $tmp->parentNode->appendChild($node);
			}

			return $tmp->appendChild($node);
		}

		protected function zapNodes($xpath, $method, SimpleXMLElement $new = null)
		{
			if (!is_string($xpath))
			{
				throw new InvalidArgumentException('Argument 1 passed to ' . $method . '() must be a string, ' . gettype($xpath) . ' given');
			}

			$dom = dom_import_simplexml($this);
			$doc = $dom->ownerDocument;
			$dxp = new DOMXPath($doc);

			$cnt = 0;
			$ret = array();

			$useErrors = libxml_use_internal_errors(true);
			$nodes = $dxp->query($xpath, $dom);
			libxml_use_internal_errors($useErrors);

			if ($nodes === false)
			{
				throw new InvalidArgumentException('Invalid XPath expression ' . $xpath);
			}

			foreach ($nodes as $node)
			{
				if ($node->isSameNode($node->ownerDocument->documentElement))
				{
					throw new BadMethodCallException($method . '() cannot be used to remove the root node');
				}

				switch ($method)
				{
					case 'removeNodes':
						$ret[] = static::import($node->parentNode->removeChild($node));
						break;

					case 'replaceNodes':
						$ret[] = static::import(
							$node->parentNode->replaceChild(
								$doc->importNode(dom_import_simplexml($new), true),
								$node
							)
						);
						break;
				}
			}

			return $ret;
		}

		static protected function fromHTML($method, $arg, &$errors)
		{
			$old = libxml_use_internal_errors(true);
			$cnt = count(libxml_get_errors());

			$dom = new DOMDocument;
			$dom->$method($arg);

			$errors = array_slice(libxml_get_errors(), $cnt);
			libxml_use_internal_errors($old);

			return static::import($dom);
		}

		static protected function import(DOMNode $node)
		{
			return simplexml_import_dom($node, get_called_class());
		}
		/**#@-*/
	}
}