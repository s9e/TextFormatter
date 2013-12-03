<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators;

use DOMDocument;
use DOMNode;
use DOMXPath;
use s9e\TextFormatter\Configurator\Helpers\TemplateParser;
use s9e\TextFormatter\Configurator\RendererGenerator;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP\Optimizer;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP\Serializer;
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
	* @var Optimizer Optimizer
	*/
	public $optimizer;

	/**
	* @var string Output method
	*/
	protected $outputMethod;

	/**
	* @var string PHP source of generated renderer
	*/
	protected $php;

	/**
	* @var Serializer Serializer
	*/
	public $serializer;

	/**
	* @var bool Whether to use the empty-element tag syntax with non-void elements in XML mode
	*/
	public $useEmptyElements = true;

	/**
	* @var bool Whether to use the mbstring functions as a replacement for XPath expressions
	*/
	public $useMultibyteStringFunctions;

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

		if (extension_loaded('tokenizer'))
		{
			$this->optimizer = new Optimizer;
		}

		$this->useMultibyteStringFunctions = extension_loaded('mbstring');

		$this->serializer = new Serializer;
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

		// Copy some options to the serializer
		$this->serializer->forceEmptyElements          = $this->forceEmptyElements;
		$this->serializer->outputMethod                = $this->outputMethod;
		$this->serializer->useEmptyElements            = $this->useEmptyElements;
		$this->serializer->useMultibyteStringFunctions = $this->useMultibyteStringFunctions;

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
					unset($this->xpath);

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
					$condition = '(' . $condition . '&&' . $this->serializer->convertCondition($predicate) . ')';
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
				$this->php .= $this->serializer->serializeChildren($ir->documentElement);
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

		// Remove the references to $this->xpath if it's never used
		if (strpos($this->php, '$this->xpath->') === false)
		{
			$this->php = preg_replace(
				[
					'#\\s*\\$this->xpath\\s*=.*#',
					'#\\s*unset\\(\\$this->xpath\\);#'
				],
				'',
				$this->php
			);
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
		if (isset($this->optimizer))
		{
			$this->php = $this->optimizer->optimize($this->php);
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
}