<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators;

use DOMElement;
use s9e\TextFormatter\Configurator\Helpers\TemplateParser;
use s9e\TextFormatter\Configurator\RendererGenerator;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP\Optimizer;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP\Serializer;
use s9e\TextFormatter\Configurator\Rendering;

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
	* @param  string $cacheDir If set, path to the directory where the renderer will be saved
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
	public function getRenderer(Rendering $rendering)
	{
		// Generate the source file
		$php = $this->generate($rendering);

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
	* given rendering configuration
	*
	* @param  Rendering $rendering
	* @return string
	*/
	public function generate(Rendering $rendering)
	{
		// Copy some options to the serializer
		$this->serializer->outputMethod                = $rendering->type;
		$this->serializer->useEmptyElements            = $this->useEmptyElements;
		$this->serializer->useMultibyteStringFunctions = $this->useMultibyteStringFunctions;

		// Group templates by content to deduplicate them
		$groupedTemplates = [];
		foreach ($rendering->getTemplates() as $tagName => $template)
		{
			$groupedTemplates[$template][] = '$nodeName===' . self::export($tagName);
		}

		// Record whether the template has a <xsl:apply-templates/> with a select attribute
		$hasApplyTemplatesSelect = false;

		// Parse each template and serialize it to PHP
		$templatesSource = '';
		foreach ($groupedTemplates as $template => $conditions)
		{
			/**
			* @todo temp hack
			*/
			$template = '<xsl:stylesheet xmlns:xsl="' . self::XMLNS_XSL . '"><xsl:output method="' . $rendering->type . '"/><xsl:template match="X">' . $template . '</xsl:template></xsl:stylesheet>';

			// Parse the template
			$ir = TemplateParser::parse($template);

			// Apply the empty-element options
			if ($rendering->type === 'xhtml')
			{
				$this->fixEmptyElements($ir->documentElement);
			}

			// Test whether this template uses an <xsl:apply-templates/> element with a select
			if (!$hasApplyTemplatesSelect)
			{
				foreach ($ir->getElementsByTagName('applyTemplates') as $applyTemplates)
				{
					if ($applyTemplates->hasAttribute('select'))
					{
						$hasApplyTemplatesSelect = true;
					}
				}
			}

			// Serialize the representation to PHP
			$templateSource = $this->serializer->serializeChildren($ir->documentElement->firstChild);
			if (isset($this->optimizer))
			{
				$templateSource = $this->optimizer->optimize($templateSource);
			}

			$templatesSource .= 'if(' . implode('||', $conditions) . '){' . $templateSource . '}else';
		}
		unset($groupedTemplates, $ir);

		// Append the default handling of unknown tags
		$templatesSource .= ' $this->at($node);';

		// Test whether any templates needs an XPath engine
		if ($hasApplyTemplatesSelect)
		{
			$needsXPath = true;
		}
		elseif (strpos($templatesSource, '$this->getParamAsXPath') !== false)
		{
			$needsXPath = true;
		}
		elseif (strpos($templatesSource, '$this->xpath') !== false)
		{
			$needsXPath = true;
		}
		else
		{
			$needsXPath = false;
		}

		// Start the code right after the class name, we'll prepend the header when we're done
		$php = [];
		$php[] = ' extends \\s9e\\TextFormatter\\Renderer';
		$php[] = '{';
		$php[] = '	protected $htmlOutput=' . self::export($rendering->type === 'html') . ';';
		$php[] = '	protected $params=' . self::export($rendering->getAllParameters()) . ';';

		if ($needsXPath)
		{
			$php[] = '	protected $xpath;';
		}

		$php[] = '	public function __sleep()';
		$php[] = '	{';
		$php[] = '		$props = get_object_vars($this);';
		$php[] = "		unset(\$props['out'], \$props['proc'], \$props['source']" . (($needsXPath) ? ", \$props['xpath']" : '') . ');';
		$php[] = '		return array_keys($props);';
		$php[] = '	}';
		$php[] = '	public function renderRichText($xml)';
		$php[] = '	{';
		$php[] = '		$dom = $this->loadXML($xml);';

		if ($needsXPath)
		{
			$php[] = '		$this->xpath = new \\DOMXPath($dom);';
		}

		$php[] = "		\$this->out = '';";
		$php[] = '		$this->at($dom->documentElement);';

		if ($needsXPath)
		{
			$php[] = '		unset($this->xpath);';
		}

		$php[] = '		return $this->out;';
		$php[] = '	}';

		if ($hasApplyTemplatesSelect)
		{
			$php[] = '	protected function at(\\DOMNode $root, $xpath = null)';
		}
		else
		{
			$php[] = '	protected function at(\\DOMNode $root)';
		}

		$php[] = '	{';
		$php[] = '		if ($root->nodeType === 3)';
		$php[] = '		{';
		$php[] = '			$this->out .= htmlspecialchars($root->textContent,' . ENT_NOQUOTES . ');';
		$php[] = '		}';
		$php[] = '		else';
		$php[] = '		{';

		if ($hasApplyTemplatesSelect)
		{
			$php[] = '			foreach (isset($xpath) ? $this->xpath->query($xpath, $root) : $root->childNodes as $node)';
		}
		else
		{
			$php[] = '			foreach ($root->childNodes as $node)';
		}

		$php[] = '			{';
		$php[] = '				$nodeName = $node->nodeName;' . $templatesSource;
		$php[] = '			}';
		$php[] = '		}';
		$php[] = '	}';

		// Add the getParamAsXPath() method if necessary
		if (strpos($templatesSource, '$this->getParamAsXPath') !== false)
		{
			$php[] = '	protected function getParamAsXPath($k)';
			$php[] = '	{';
			$php[] = '		if (!isset($this->params[$k]))';
			$php[] = '		{';
			$php[] = '			return "\'\'";';
			$php[] = '		}';
			$php[] = '		$str = $this->params[$k];';
			$php[] = '		if (strpos($str, "\'") === false)';
			$php[] = '		{';
			$php[] = '			return "\'$str\'";';
			$php[] = '		}';
			$php[] = '		if (strpos($str, \'"\') === false)';
			$php[] = '		{';
			$php[] = '			return "\\"$str\\"";';
			$php[] = '		}';
			$php[] = '		$toks = [];';
			$php[] = '		$c = \'"\';';
			$php[] = '		$pos = 0;';
			$php[] = '		while ($pos < strlen($str))';
			$php[] = '		{';
			$php[] = '			$spn = strcspn($str, $c, $pos);';
			$php[] = '			if ($spn)';
			$php[] = '			{';
			$php[] = '				$toks[] = $c . substr($str, $pos, $spn) . $c;';
			$php[] = '				$pos += $spn;';
			$php[] = '			}';
			$php[] = '			$c = ($c === \'"\') ? "\'" : \'"\';';
			$php[] = '		}';
			$php[] = '		return \'concat(\' . implode(\',\', $toks) . \')\';';
			$php[] = '	}';
		}

		// Close the class definition
		$php[] = '}';

		// Assemble the source
		$php = implode("\n", $php);

		// Finally, optimize the control structures
		if (isset($this->optimizer))
		{
			$php = $this->optimizer->optimizeControlStructures($php);
		}

		// Generate a name for that class if necessary, and save it
		$className = (isset($this->className))
		           ? $this->className
		           : $this->defaultClassPrefix . sha1($php);
		$this->lastClassName = $className;

		// Prepare the header
		$header = "/**\n"
		        . "* @package   s9e\TextFormatter\n"
		        . "* @copyright Copyright (c) 2010-2014 The s9e Authors\n"
		        . "* @license   http://www.opensource.org/licenses/mit-license.php The MIT License\n"
		        . "*/\n\n";

		// Declare the namespace and class name
		$pos = strrpos($className, '\\');
		if ($pos !== false)
		{
			$header .= 'namespace ' . substr($className, 0, $pos) . ";\n\n";
			$className = substr($className, 1 + $pos);
		}

		// Prepend the header and the class name
		$php = $header . 'class ' . $className . $php;

		return $php;
	}

	/**
	* Export given value as PHP code
	*
	* @param  mixed  $value Original value
	* @return string        PHP code
	*/
	protected static function export($value)
	{
		if (is_array($value))
		{
			$pairs = [];
			foreach ($value as $k => $v)
			{
				$pairs[] = var_export($k, true) . '=>' . var_export($v, true);
			}

			return '[' . implode(',', $pairs) . ']';
		}

		return var_export($value, true);
	}

	/**
	* Change the IR to respect the empty-element options
	*
	* @param  DOMElement $ir
	* @return void
	*/
	protected function fixEmptyElements(DOMElement $ir)
	{
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