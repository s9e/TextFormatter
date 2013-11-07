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
use s9e\TextFormatter\Configurator\Helpers\TemplateParser;
use s9e\TextFormatter\Configurator\RendererGenerator;
use s9e\TextFormatter\Configurator\Stylesheet;

/**
* @see docs/DifferencesInRendering.md
*/
class PHP implements RendererGenerator
{
	/**
	* XSL namespace
	*/
	const XMLNS_XSL = 'http://www.w3.org/1999/XSL/Transform';

	/**
	* @var string Directory where the renderer's source is automatically saved if set, and if filepath is not set
	*/
	public $cacheDir;

	/**
	* @var string Name of the class to be created. If null, a random name will be generated
	*/
	public $className;

	/**
	* @var array Custom XPath representations as [xpath => php]
	*/
	protected $customXPath = [
		// BBcodes: LIST
		"contains('upperlowerdecim',substring(@type,1,5))"
			=> "strpos('upperlowerdecim',substr(\$node->getAttribute('type'),0,5))!==false",

		// MediaEmbed: Bandcamp
		'120-78*boolean(@track_num)'
			=> "(\$node->hasAttribute('track_num')?42:120)",

		// MediaEmbed: Grooveshark
		"substring('songWw',6-5*boolean(@songid),5)"
			=> "(\$node->hasAttribute('songid')?'songW':'w')",

		"250-210*boolean(@songid)"
			=> "(\$node->hasAttribute('songid')?40:250)",

		// MediaEmbed: Spotify
		"380-300*(contains(@uri,':track:')orcontains(@path,'/track/'))"
			=> "(strpos(\$node->getAttribute('uri'),':track:')!==false||strpos(\$node->getAttribute('path'),'/track/')!==false?80:380)",

		// MediaEmbed: Twitch
		"substring('archl',5-4*boolean(@archive_id|@chapter_id),4)"
			=> "(\$node->hasAttribute('archive_id')||\$node->hasAttribute('chapter_id')?'arch':'l')"
	];

	/**
	* @var string Prefix used when generating a default class name
	*/
	public $defaultClassPrefix = 'Renderer_';

	/**
	* @var string If set, path to the file where the renderer will be saved
	*/
	public $filepath;

	/**
	* @var bool Whether to force non-void, empty elements to use the empty-element tag syntax in XML mode
	*/
	public $forceEmptyElements = true;

	/**
	* @var string Name of the last class generated
	*/
	public $lastClassName;

	/**
	* @var string Path to the last file saved
	*/
	public $lastFilepath;

	/**
	* @var string Output method
	*/
	protected $outputMethod;

	/**
	* @var string PHP source of generated renderer
	*/
	protected $php;

	/**
	* @var bool Whether to use the empty-element tag syntax with non-void elements in XML mode
	*/
	public $useEmptyElements = true;

	/**
	* @var bool Whether to use the mbstring functions as a replacement for XPath expressions
	*/
	public $useMultibyteStringFunctions = true;

	/**
	* Constructor
	*
	* @param  string $className Name of the class to be created
	* @param  string $filepath  If set, path to the file where the renderer will be saved
	* @return void
	*/
	public function __construct($cacheDir = null)
	{
		if (isset($cacheDir))
		{
			$this->cacheDir = $cacheDir;
		}
	}

	/**
	* {@inheritdoc}
	*/
	public function getRenderer(Stylesheet $stylesheet)
	{
		// Generate the source file
		$php = $this->generate($stylesheet->get());

		// Save the file if applicable
		if (isset($this->filepath))
		{
			$filepath = $this->filepath;
		}
		elseif (isset($this->cacheDir))
		{
			$filepath = $this->cacheDir . '/' . str_replace('\\', '_', $this->lastClassName) . '.php';
		}

		if (isset($filepath))
		{
			file_put_contents($filepath, "<?php\n" . $php);
			$this->lastFilepath = realpath($filepath);
		}

		// Execute the source to create the class if it doesn't exist
		if (!class_exists($this->lastClassName, false))
		{
			eval($php);
		}

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
		$header = "/**\n"
		        . "* @package   s9e\TextFormatter\n"
		        . "* @copyright Copyright (c) 2010-2013 The s9e Authors\n"
		        . "* @license   http://www.opensource.org/licenses/mit-license.php The MIT License\n"
		        . "*/\n\n";

		// Parse the stylesheet
		$ir    = TemplateParser::parse($xsl);
		$xpath = new DOMXPath($ir);

		// Set the output method
		$this->outputMethod = $ir->documentElement->getAttribute('outputMethod');

		// Apply the empty-element options
		$this->fixEmptyElements($ir);

		// Generate the arrays of parameters, sorted by whether they are static or dynamic
		$dynamicParams = [];
		$staticParams  = [];

		foreach ($ir->getElementsByTagName('param') as $param)
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

		// Start the code right after the class name, we'll prepend the header when we're done
		$this->php = ' extends \\s9e\\TextFormatter\\Renderer
			{
				protected $htmlOutput=' . var_export($this->outputMethod === 'html', true) . ';
				protected $dynamicParams=[' . implode(',', $dynamicParams) . '];
				protected $params=[' . implode(',', $staticParams) . '];
				protected $xpath;
				public function __sleep()
				{
					$props = get_object_vars($this);
					unset($props["out"], $props["proc"], $props["source"], $props["xpath"]);

					return array_keys($props);
				}
				public function setParameter($paramName, $paramValue)
				{
					$this->params[$paramName] = (string) $paramValue;
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

		if ($xpath->evaluate('count(//applyTemplates[@select])'))
		{
			$nodesPHP = '(isset($xpath)) ? $this->xpath->query($xpath, $root) : $root->childNodes';
		}
		else
		{
			$nodesPHP = '$root->childNodes';
		}

		$this->php .= '
					$this->at($dom->documentElement);

					return $this->out;
				}
				protected function at($root, $xpath = null)
				{
					if ($root->nodeType === 3)
					{
						$this->out .= htmlspecialchars($root->textContent,' . ENT_NOQUOTES . ');
					}
					else
					{
						foreach (' . $nodesPHP . ' as $node)
						{
							$nodeName = $node->nodeName;';

		// Remove the excess indentation
		$this->php = str_replace("\n\t\t\t", "\n", $this->php);

		// Collect and sort templates
		$templates = [];
		foreach ($ir->getElementsByTagName('template') as $template)
		{
			// Parse this template and save its internal representation
			$irXML = $template->ownerDocument->saveXML($template);

			// Get the template's match values
			foreach ($template->getElementsByTagName('match') as $match)
			{
				$expr     = $match->textContent;
				$priority = $match->getAttribute('priority');

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

				// Record this template
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

				// If there's only one condition, remove its parentheses if applicable
				if (count($conditions) === 1
				 && $conditions[0][0] === '('
				 && substr($conditions[0], -1) === ')')
				{
					 $conditions[0] = substr($conditions[0], 1, -1);
				}

				$this->php .= 'if(' . implode('||', $conditions) . ')';
				$this->php .= '{';
				$this->serializeChildren($ir->documentElement);
				$this->php .= '}';
			}
		}

		// Add the default handling and close the method
		$this->php .= "else \$this->at(\$node);\n\t\t\t}\n\t\t}\n\t}";

		// Add the getParamAsXPath() method if necessary
		if (strpos($this->php, '$this->getParamAsXPath(') !== false)
		{
			$this->php .= str_replace(
				"\n\t\t\t\t",
				"\n",
				<<<'EOT'
				protected function getParamAsXPath($k)
				{
					if (isset($this->dynamicParams[$k]))
					{
						return $this->dynamicParams[$k];
					}
					if (!isset($this->params[$k]))
					{
						return "''";
					}
					$str = $this->params[$k];
					if (strpos($str, "'") === false)
					{
						return "'" . $str . "'";
					}
					if (strpos($str, '"') === false)
					{
						return '"' . $str . '"';
					}

					$toks = [];
					$c = '"';
					$pos = 0;
					while ($pos < strlen($str))
					{
						$spn = strcspn($str, $c, $pos);
						if ($spn)
						{
							$toks[] = $c . substr($str, $pos, $spn) . $c;
							$pos += $spn;
						}
						$c = ($c === '"') ? "'" : '"';
					}

					return 'concat(' . implode(',', $toks) . ')';
				}
EOT
			);
		}

		// Remove the instantiation of $this->xpath if it's never used
		if (strpos($this->php, '$this->xpath->') === false)
		{
			$this->php = preg_replace('#\\s*\\$this->xpath\\s*=.*#', '', $this->php);
		}

		// Close the class definition
		$this->php .= "\n}";

		// Generate a name for that class if necessary, and save it
		$className = (isset($this->className))
		           ? $this->className
		           : $this->defaultClassPrefix . sha1($this->php);
		$this->lastClassName = $className;

		// Declare the namespace and class name
		$pos = strrpos($className, '\\');
		if ($pos !== false)
		{
			$header .= 'namespace ' . substr($className, 0, $pos) . ";\n\n";
			$className = substr($className, 1 + $pos);
		}

		// Prepend the header and the class name
		$this->php = $header . 'class ' . $className . $this->php;

		// Optimize the generated code
		if (extension_loaded('tokenizer'))
		{
			$this->optimizeCode();
		}

		return $this->php;
	}

	/**
	* Change the IR to respect the empty-element options
	*
	* @param  DOMNode $ir
	* @return void
	*/
	protected function fixEmptyElements(DOMNode $ir)
	{
		if ($this->outputMethod !== 'xml')
		{
			return;
		}

		foreach ($ir->getElementsByTagName('element') as $element)
		{
			$isEmpty = $element->getAttribute('empty');
			$isVoid  = $element->getAttribute('void');

			if ($isVoid || $isEmpty === 'no')
			{
				continue;
			}

			if (!$this->useEmptyElements)
			{
				$element->setAttribute('empty', 'no');
			}
			elseif ($isEmpty === 'maybe' && !$this->forceEmptyElements)
			{
				$element->setAttribute('empty', 'no');
			}
		}
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
		$attrName = $attribute->getAttribute('name');

		// PHP representation of this attribute's name
		$phpAttrName = $this->convertAttributeValueTemplate($attrName);

		// NOTE: the attribute name is escaped by default to account for dynamically-generated names
		$phpAttrName = 'htmlspecialchars(' . $phpAttrName . ',' . ENT_QUOTES . ')';

		$this->php .= "\$this->out.=' '." . $phpAttrName . ".'=\"';";
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
		$id = $closeTag->getAttribute('id');

		if ($closeTag->hasAttribute('check'))
		{
			$this->php .= 'if(!isset($t' . $id . ')){';
		}

		if ($closeTag->hasAttribute('set'))
		{
			$this->php .= '$t' . $id . '=1;';
		}

		// Get the element that's being closed
		$xpath   = new DOMXPath($closeTag->ownerDocument);
		$element = $xpath->query('ancestor::element[@id="' . $id . '"]', $closeTag)->item(0);
		$isVoid  = $element->getAttribute('void');
		$isEmpty = $element->getAttribute('empty');

		if ($this->outputMethod === 'html')
		{
			$this->php .= "\$this->out.='>';";

			if ($isVoid === 'maybe')
			{
				// Check at runtime whether this element is not void
				$this->php .= 'if(!$v' . $id . '){';
			}
		}
		else
		{
			// In XML mode, we only care about whether this element is empty
			if ($isEmpty === 'yes')
			{
				// Definitely empty, use a self-closing tag
				$this->php .= "\$this->out.='/>';";
			}
			else
			{
				// Since it's not definitely empty, we'll close this start tag normally
				$this->php .= "\$this->out.='>';";

				if ($isEmpty === 'maybe')
				{
					// Maybe empty, record the length of the output and if it doesn't grow we'll
					// change the start tag into a self-closing tag
					$this->php .= '$l' . $id . '=strlen($this->out);';
				}
			}
		}

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
		$this->php .= '$this->out.=htmlspecialchars($attribute->value,' . ENT_COMPAT . ');';
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
		$elName  = $element->getAttribute('name');
		$id      = $element->getAttribute('id');
		$isVoid  = $element->getAttribute('void');
		$isEmpty = $element->getAttribute('empty');

		// Test whether this element name is dynamic
		$isDynamic = (bool) (strpos($elName, '{') !== false);

		// PHP representation of this element's name
		$phpElName = $this->convertAttributeValueTemplate($elName);

		// NOTE: the element name is escaped by default to account for dynamically-generated names
		$phpElName = 'htmlspecialchars(' . $phpElName . ',' . ENT_QUOTES . ')';

		// If the element name is dynamic, we cache its name for convenience and performance
		if ($isDynamic)
		{
			$varName = '$e' . $id;

			// Add the var declaration to the source
			$this->php .= $varName . '=' . $phpElName . ';';

			// Replace the element name with the var
			$phpElName = $varName;
		}

		// Test whether this element is void if we need this information
		if ($this->outputMethod === 'html' && $isVoid === 'maybe')
		{
			$this->php .= '$v' . $id . '=preg_match(' . var_export(TemplateParser::$voidRegexp, true) . ',' . $phpElName . ');';
		}

		// Open the start tag
		$this->php .= "\$this->out.='<'." . $phpElName . ';';

		// Serialize this element's content
		$this->serializeChildren($element);

		// If we're in XML mode and the element is or may be empty, we may not need to close it at
		// all
		if ($this->outputMethod === 'xml')
		{
			// If this element is definitely empty, it has already been closed with a self-closing
			// tag in serializeCloseTag()
			if ($isEmpty === 'yes')
			{
				return;
			}

			// If this element may be empty, we need to check at runtime whether we turn its start
			// tag into a self-closing tag or append an end tag
			if ($isEmpty === 'maybe')
			{
				$this->php .= 'if($l' . $id . '===strlen($this->out)){';
				$this->php .= "\$this->out=substr(\$this->out,0,-1).'/>';";
				$this->php .= '}else{';
				$this->php .= "\$this->out.='</'." . $phpElName . ".'>';";
				$this->php .= '}';

				return;
			}
		}

		// Close that element, unless we're in HTML mode and we know it's void
		if ($this->outputMethod !== 'html' || $isVoid !== 'yes')
		{
			$this->php .= "\$this->out.='</'." . $phpElName . ".'>';";
		}

		// If this element was maybe void, serializeCloseTag() has put its content within an if
		// block. We need to close that block
		if ($this->outputMethod === 'html' && $isVoid === 'maybe')
		{
			$this->php .= '}';
		}
	}

	/**
	* Unused
	*/
	protected function serializeMatch()
	{
	}

	/**
	* Serialize an <output/> node
	*
	* @param  DOMNode $output <output/> node
	* @return void
	*/
	protected function serializeOutput(DOMNode $output)
	{
		$xpath      = new DOMXPath($output->ownerDocument);
		$escapeMode = ($xpath->evaluate('count(ancestor::attribute)', $output))
		            ? ENT_COMPAT
		            : ENT_NOQUOTES;

		if ($output->getAttribute('type') === 'xpath')
		{
			$this->php .= '$this->out.=htmlspecialchars(';
			$this->php .= $this->convertXPath($output->textContent);
			$this->php .= ',' . $escapeMode . ');';
		}
		else
		{
			// Literal
			$this->php .= '$this->out.=';
			$this->php .= var_export(htmlspecialchars($output->textContent, $escapeMode), true);
			$this->php .= ';';
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
			if ($case->parentNode !== $switch)
			{
				continue;
			}

			if ($case->hasAttribute('test'))
			{
				$this->php .= $else . 'if(' . $this->convertCondition($case->getAttribute('test')) . ')';
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

		// Optimization passes, in order of execution
		$passes = [
			'optimizeOutConcatEqual',
			'optimizeConcatenations',
			'optimizeHtmlspecialchars'
		];

		// Limit the number of loops to 10, in case something would make it loop indefinitely
		$remainingLoops = 10;
		do
		{
			$continue = false;

			foreach ($passes as $pass)
			{
				// Count the tokens
				$cnt = count($tokens);

				// Run the pass
				$this->$pass($tokens);

				// If the array was modified, reset the keys and keep going
				if ($cnt !== count($tokens))
				{
					$tokens   = array_values($tokens);
					$continue = true;
				}
			}
		}
		while ($continue && --$remainingLoops);

		// Remove the first token, which should be T_OPEN_TAG, aka "<?php"
		unset($tokens[0]);

		// Rebuild the source
		$this->php = '';
		foreach ($tokens as $token)
		{
			$this->php .= (is_string($token)) ? $token : $token[1];
		}
	}

	/**
	* Optimize T_CONCAT_EQUAL assignments in an array of PHP tokens
	*
	* Will only optimize $this->out.= assignments
	*
	* @param  array &$tokens PHP tokens from tokens_get_all()
	* @return void
	*/
	protected function optimizeOutConcatEqual(array &$tokens)
	{
		$cnt = count($tokens);

		$i = 0;
		while (++$i < $cnt)
		{
			if ($tokens[$i][0] !== T_CONCAT_EQUAL)
			{
				continue;
			}

			// Test whether this T_CONCAT_EQUAL is preceded with $this->out
			if ($tokens[$i - 1][0] !== T_STRING
			 || $tokens[$i - 1][1] !== 'out'
			 || $tokens[$i - 2][0] !== T_OBJECT_OPERATOR
			 || $tokens[$i - 3][0] !== T_VARIABLE
			 || $tokens[$i - 3][1] !== '$this')
			{
				 continue;
			}

			while ($i < $cnt)
			{
				// Move the cursor to next semicolon
				while ($tokens[++$i] !== ';');

				// Move the cursor past the semicolon
				++$i;

				// Test whether the assignment is followed by another $this->out.= assignment
				if ($tokens[$i    ][0] !== T_VARIABLE
				 || $tokens[$i    ][1] !== '$this'
				 || $tokens[$i + 1][0] !== T_OBJECT_OPERATOR
				 || $tokens[$i + 2][0] !== T_STRING
				 || $tokens[$i + 2][1] !== 'out'
				 || $tokens[$i + 3][0] !== T_CONCAT_EQUAL)
				{
					 break;
				}

				// Replace the semicolon between assignments with a concatenation operator
				$tokens[$i - 1] = '.';

				// Remove the following $this->out.= assignment and move the cursor past it
				unset($tokens[$i]);
				unset($tokens[$i + 1]);
				unset($tokens[$i + 2]);
				unset($tokens[$i + 3]);
				$i += 3;
			}
		}
	}

	/**
	* Optimize concatenations in an array of PHP tokens
	*
	* - Will precompute the result of the concatenation of constant strings
	* - Will replace the concatenation of two compatible htmlspecialchars() calls with one call to
	*   htmlspecialchars() on the concatenation of their first arguments
	*
	* @param  array &$tokens PHP tokens from tokens_get_all()
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
			}
		}
	}

	/**
	* Optimize htmlspecialchars() calls
	*
	* - The result of htmlspecialchars() on literals is precomputed
	* - By default, the generator escapes all values, including variables that cannot contain
	*   special characters such as $node->localName. This pass removes those calls
	*
	* @param  array &$tokens PHP tokens from tokens_get_all()
	* @return void
	*/
	protected function optimizeHtmlspecialchars(array &$tokens)
	{
		$cnt = count($tokens);

		$i = 0;
		while (++$i < $cnt)
		{
			// Skip this token if it's not the first of the "htmlspecialchars(" sequence
			if ($tokens[$i    ][0] !== T_STRING
			 || $tokens[$i    ][1] !== 'htmlspecialchars'
			 || $tokens[$i + 1]    !== '(')
			{
				continue;
			}

			// Test whether a constant string is being escaped
			if ($tokens[$i + 2][0] === T_CONSTANT_ENCAPSED_STRING
			 && $tokens[$i + 3]    === ','
			 && $tokens[$i + 4][0] === T_LNUMBER
			 && $tokens[$i + 5]    === ')')
			{
				// Escape the content of the T_CONSTANT_ENCAPSED_STRING token
				$tokens[$i + 2][1] = var_export(
					htmlspecialchars(
						stripslashes(substr($tokens[$i + 2][1], 1, -1)),
						$tokens[$i + 4][1]
					),
					true
				);

				// Remove the htmlspecialchars() call, except for the T_CONSTANT_ENCAPSED_STRING
				// token
				unset($tokens[$i]);
				unset($tokens[$i + 1]);
				unset($tokens[$i + 3]);
				unset($tokens[$i + 4]);
				unset($tokens[$i + 5]);

				// Move the cursor past the call
				$i += 5;

				continue;
			}

			// Test whether a variable is being escaped
			if ($tokens[$i + 2][0] === T_VARIABLE
			 && $tokens[$i + 2][1]  === '$node'
			 && $tokens[$i + 3][0]  === T_OBJECT_OPERATOR
			 && $tokens[$i + 4][0]  === T_STRING
			 && ($tokens[$i + 4][1] === 'localName' || $tokens[$i + 4][1] === 'nodeName')
			 && $tokens[$i + 5]     === ','
			 && $tokens[$i + 6][0]  === T_LNUMBER
			 && $tokens[$i + 7]     === ')')
			{
				// Remove the htmlspecialchars() call, except for its first argument
				unset($tokens[$i]);
				unset($tokens[$i + 1]);
				unset($tokens[$i + 5]);
				unset($tokens[$i + 6]);
				unset($tokens[$i + 7]);

				// Move the cursor past the call
				$i += 7;

				continue;
			}
		}
	}

	//==========================================================================
	// XPath conversion
	//==========================================================================

	/**
	* Convert an attribute value template into PHP
	*
	* NOTE: escaping must be performed by the caller
	*
	* @link http://www.w3.org/TR/xslt#dt-attribute-value-template
	*
	* @param  string $attrValue Attribute value template
	* @return void
	*/
	protected function convertAttributeValueTemplate($attrValue)
	{
		$phpExpressions = [];
		foreach (TemplateHelper::parseAttributeValueTemplate($attrValue) as $token)
		{
			if ($token[0] === 'literal')
			{
				$phpExpressions[] = var_export($token[1], true);
			}
			else
			{
				$phpExpressions[] = $this->convertXPath($token[1]);
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
		$expr = trim($expr);

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

		// If the condition does not seem to contain a relational expression, or start with a
		// function call, we wrap it inside of a boolean() call
		if (!preg_match('#[=<>]|\\bor\\b|\\band\\b|^[-\\w]+\\s*\\(#', $expr))
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
		static $regexp;

		$expr = trim($expr);

		// Use the custom representation if applicable
		if (isset($this->customXPath[$expr]))
		{
			return $this->customXPath[$expr];
		}

		if (!isset($regexp))
		{
			$patterns = [
				'attr'      => ['@', '(?<attrName>[-\\w]+)'],
				'dot'       => '\\.',
				'not'       => ['not', '\\(', '(?&value)', '\\)'],
				'name'      => 'name\\(\\)',
				'lname'     => 'local-name\\(\\)',
				'param'     => ['\\$', '(?<paramName>\\w+)'],
				'string'    => '"[^"]*"|\'[^\']*\'',
				'number'    => ['-?', '\\d++'],
				'contains'  => [
					'contains',
					'\\(',
					'(?<contains0>(?&value))',
					',',
					'(?<contains1>(?&value))',
					'\\)'
				],
				'translate' => [
					'translate',
					'\\(',
					'(?<translate0>(?&value))',
					',',
					'(?<translate1>(?&string))',
					',',
					'(?<translate2>(?&string))',
					'\\)'
				]
			];

			if (function_exists('mb_strlen'))
			{
				$patterns['strlen'] = ['string-length', '\\(', '(?<strlen0>(?&value))?', '\\)'];
			}

			if (function_exists('mb_substr'))
			{
				$patterns['substr'] = [
					'substring',
					'\\(',
					'(?<substr0>(?&value))',
					',',
					'(?<substr1>(?&value))',
					'(?:, (?<substr2>(?&value)))?',
					'\\)'
				];
			}

			// Create a regexp that matches values, such as "@foo" or "42"
			$valueRegexp = '(?<value>';
			foreach ($patterns as $name => $pattern)
			{
				if (is_array($pattern))
				{
					$pattern = implode(' ', $pattern);
				}

				$valueRegexp .= '(?<' . $name . '>' . str_replace(' ', '\\s*', $pattern) . ')|';
			}
			$valueRegexp = substr($valueRegexp, 0, -1) . ')';

			// Create a regexp that matches a comparison such as "@foo = 1"
			// NOTE: cannot support < or > because of NaN -- (@foo<5) returns false if @foo=''
			$cmpRegexp = '(?<cmp>(?<cmp0>(?&value)) (?<cmp1>!?=) (?<cmp2>(?&value)))';

			// Create a regexp that matches boolean operations
			$boolRegexp = '(?<bool>(?<bool0>(?&cmp)|(?&value)) (?<bool1>and|or) (?<bool2>(?&cmp)|(?&value)|(?&bool)))';

			// Assemble the final regexp
			$regexp = '#^(?:' . $valueRegexp . '|' . $cmpRegexp . '|' . $boolRegexp . ')$#S';

			// Replace spaces with any amount of whitespace
			$regexp = str_replace(' ', '\\s*', $regexp);
		}

		if (preg_match($regexp, $expr, $m))
		{
			if (!empty($m['attrName']))
			{
				// <xsl:value-of select="@foo"/>
				// $this->out .= $node->getAttribute('foo');
				return '$node->getAttribute(' . var_export($m['attrName'], true) . ')';
			}

			// <xsl:value-of select="."/>
			// $this->out .= $node->textContent;
			if (!empty($m['dot']))
			{
				return '$node->textContent';
			}

			// <xsl:value-of select="$foo"/>
			// $this->out .= $this->params['foo'];
			if (!empty($m['paramName']))
			{
				return '$this->params[' . var_export($m['paramName'], true) . ']';
			}

			// <xsl:value-of select="'foo'"/>
			// <xsl:value-of select='"foo"'/>
			// $this->out .= 'foo';
			if (!empty($m['string']))
			{
				return var_export(substr($m['string'], 1, -1), true);
			}

			// <xsl:value-of select="local-name()"/>
			// $this->out .= $node->localName;
			if (!empty($m['lname']))
			{
				return '$node->localName';
			}

			// <xsl:value-of select="name()"/>
			// $this->out .= $node->nodeName;
			if (!empty($m['name']))
			{
				return '$node->nodeName';
			}

			// <xsl:value-of select="3"/>
			// $this->out .= '3';
			if (!empty($m['number']))
			{
				return "'" . $expr . "'";
			}

			// <xsl:value-of select="string-length(@foo)"/>
			// $this->out .= mb_strlen($node->getAttribute('foo'),'utf-8');
			if (!empty($m['strlen']) && $this->useMultibyteStringFunctions)
			{
				if (!isset($m['strlen0']))
				{
					$m['strlen0'] = '.';
				}

				return 'mb_strlen(' . $this->convertXPath($m['strlen0']) . ",'utf-8')";
			}

			// <xsl:value-of select="substring(@foo, 1, 2)"/>
			// $this->out .= mb_substring($node->getAttribute('foo'),0,2,'utf-8');
			//
			// NOTE: negative values for the second argument do not produce the same result as
			//       specified in XPath if the argument is not a literal number
			if (!empty($m['substr']) && $this->useMultibyteStringFunctions)
			{
				$php = 'mb_substr(' . $this->convertXPath($m['substr0']) . ',';

				// Hardcode the value if possible
				if (preg_match('#^\\d+$#D', $m['substr1']))
				{
					$php .= max(0, $m['substr1'] - 1);
				}
				else
				{
					$php .= 'max(0,' . $this->convertXPath($m['substr1']) . '-1)';
				}

				$php .= ',';

				if (isset($m['substr2']))
				{
					if (preg_match('#^\\d+$#D', $m['substr2']))
					{
						// Handles substring(0,2) as per XPath
						if (preg_match('#^\\d+$#D', $m['substr1']) && $m['substr1'] < 1)
						{
							$php .= max(0, $m['substr1'] + $m['substr2'] - 1);
						}
						else
						{
							$php .= max(0, $m['substr2']);
						}
					}
					else
					{
						$php .= 'max(0,' . $this->convertXPath($m['substr2']) . ')';
					}
				}
				else
				{
					$php .= 'null';
				}

				$php .= ",'utf-8')";

				return $php;
			}

			if (!empty($m['contains']))
			{
				return '(strpos(' . $this->convertXPath($m['contains0']) . ',' . $this->convertXPath($m['contains1']) . ')!==false)';
			}

			if (!empty($m['cmp1']))
			{
				$operators = [
					'='  => '===',
					'!=' => '!==',
					'>'  => '>',
					'>=' => '>=',
					'<'  => '<',
					'<=' => '<='
				];

				// If either operand is a number, represent it as a PHP number and replace the
				// identity operators
				foreach (['cmp0', 'cmp2'] as $k)
				{
					if (preg_match('#^\\d+$#', $m[$k]))
					{
						$operators['=']  = '==';
						$operators['!='] = '!=';

						$m[$k] = ltrim($m[$k], '0');
					}
					else
					{
						$m[$k] = $this->convertXPath($m[$k]);
					}
				}

				return $m['cmp0'] . $operators[$m['cmp1']] . $m['cmp2'];
			}

			if (!empty($m['bool1']))
			{
				$operators = [
					'and' => '&&',
					'or'  => '||'
				];

				return $this->convertCondition($m['bool0']) . $operators[$m['bool1']] . $this->convertCondition($m['bool2']);
			}

			if (!empty($m['translate']))
			{
				preg_match_all('/./u', substr($m['translate1'], 1, -1), $matches);
				$from = $matches[0];

				preg_match_all('/./u', substr($m['translate2'], 1, -1), $matches);
				$to = $matches[0];

				// We adjust $to to match the number of elements in $from, either by truncating it
				// or by padding it with empty strings
				if (count($to) > count($from))
				{
					$to = array_slice($to, 0, count($from));
				}
				else
				{
					// NOTE: we don't use array_merge() because of potential side-effects when
					//       translating digits
					while (count($from) > count($to))
					{
						$to[] = '';
					}
				}

				// Remove duplicates in $from, as well as the corresponding elements in $to
				$from = array_unique($from);
				$to   = array_intersect_key($to, $from);

				// Start building the strtr() call
				$php = 'strtr(' . $this->convertXPath($m['translate0']) . ',';

				// Test whether all elements in $from and $to are exactly 1 byte long, meaning they
				// are ASCII and with no empty strings. If so, we can use the scalar version of
				// strtr(), otherwise we have to use the array version
				if ([1] === array_unique(array_map('strlen', $from))
				 && [1] === array_unique(array_map('strlen', $to)))
				{
					$php .= var_export(implode('', $from), true) . ',' . var_export(implode('', $to), true);
				}
				else
				{
					$php .= '[';

					$cnt = count($from);
					for ($i = 0; $i < $cnt; ++$i)
					{
						if ($i)
						{
							$php .= ',';
						}

						$php .= var_export($from[$i], true) . '=>' . var_export($to[$i], true);
					}

					$php .= ']';
				}

				$php .= ')';

				return $php;
			}
		}

		// If the condition does not seem to contain a relational expression, or start with a
		// function call, we wrap it inside of a string() call
		if (!preg_match('#[=<>]|\\bor\\b|\\band\\b|^[-\\w]+\\s*\\(#', $expr))
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