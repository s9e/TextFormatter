<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators;
use DOMElement;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\Helpers\TemplateParser;
use s9e\TextFormatter\Configurator\RendererGenerator;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP\ControlStructuresOptimizer;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP\Optimizer;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP\Quick;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP\Serializer;
use s9e\TextFormatter\Configurator\Rendering;
class PHP implements RendererGenerator
{
	const XMLNS_XSL = 'http://www.w3.org/1999/XSL/Transform';
	public $cacheDir;
	public $className;
	public $controlStructuresOptimizer;
	public $defaultClassPrefix = 'Renderer_';
	public $enableQuickRenderer = \false;
	public $filepath;
	public $lastClassName;
	public $lastFilepath;
	public $optimizer;
	public $serializer;
	public $useMultibyteStringFunctions;
	public function __construct($cacheDir = \null)
	{
		$this->cacheDir = (isset($cacheDir)) ? $cacheDir : \sys_get_temp_dir();
		if (\extension_loaded('tokenizer'))
		{
			$this->controlStructuresOptimizer = new ControlStructuresOptimizer;
			$this->optimizer = new Optimizer;
		}
		$this->useMultibyteStringFunctions = \extension_loaded('mbstring');
		$this->serializer = new Serializer;
	}
	public function getRenderer(Rendering $rendering)
	{
		$php = $this->generate($rendering);
		if (isset($this->filepath))
			$filepath = $this->filepath;
		else
			$filepath = $this->cacheDir . '/' . \str_replace('\\', '_', $this->lastClassName) . '.php';
		\file_put_contents($filepath, "<?php\n" . $php);
		$this->lastFilepath = \realpath($filepath);
		if (!\class_exists($this->lastClassName, \false))
			include $filepath;
		$renderer = new $this->lastClassName;
		$renderer->source = $php;
		return $renderer;
	}
	public function generate(Rendering $rendering)
	{
		$this->serializer->useMultibyteStringFunctions = $this->useMultibyteStringFunctions;
		$templates = $rendering->getTemplates();
		$groupedTemplates = [];
		foreach ($templates as $tagName => $template)
			$groupedTemplates[$template][] = $tagName;
		$hasApplyTemplatesSelect = \false;
		$tagBranch   = 0;
		$tagBranches = [];
		$compiledTemplates = [];
		$branchTables = [];
		foreach ($groupedTemplates as $template => $tagNames)
		{
			$ir = TemplateParser::parse($template);
			if (!$hasApplyTemplatesSelect)
				foreach ($ir->getElementsByTagName('applyTemplates') as $applyTemplates)
					if ($applyTemplates->hasAttribute('select'))
						$hasApplyTemplatesSelect = \true;
			$templateSource = $this->serializer->serialize($ir->documentElement);
			if (isset($this->optimizer))
				$templateSource = $this->optimizer->optimize($templateSource);
			$branchTables += $this->serializer->branchTables;
			$compiledTemplates[$tagBranch] = $templateSource;
			foreach ($tagNames as $tagName)
				$tagBranches[$tagName] = $tagBranch;
			++$tagBranch;
		}
		unset($groupedTemplates, $ir, $quickRender);
		$quickSource = \false;
		if ($this->enableQuickRenderer)
		{
			$quickRender = [];
			foreach ($tagBranches as $tagName => $tagBranch)
				$quickRender[$tagName] = $compiledTemplates[$tagBranch];
			$quickSource = Quick::getSource($quickRender);
			unset($quickRender);
		}
		$templatesSource = Quick::generateConditionals('$tb', $compiledTemplates);
		unset($compiledTemplates);
		if ($hasApplyTemplatesSelect)
			$needsXPath = \true;
		elseif (\strpos($templatesSource, '$this->getParamAsXPath') !== \false)
			$needsXPath = \true;
		elseif (\strpos($templatesSource, '$this->xpath') !== \false)
			$needsXPath = \true;
		else
			$needsXPath = \false;
		$php = [];
		$php[] = ' extends \\s9e\\TextFormatter\\Renderer';
		$php[] = '{';
		$php[] = '	protected $params=' . self::export($rendering->getAllParameters()) . ';';
		$php[] = '	protected static $tagBranches=' . self::export($tagBranches) . ';';
		foreach ($branchTables as $varName => $branchTable)
			$php[] = '	protected static $' . $varName . '=' . self::export($branchTable) . ';';
		if ($needsXPath)
			$php[] = '	protected $xpath;';
		$php[] = '	public function __sleep()';
		$php[] = '	{';
		$php[] = '		$props = get_object_vars($this);';
		$php[] = "		unset(\$props['out'], \$props['proc'], \$props['source']" . (($needsXPath) ? ", \$props['xpath']" : '') . ');';
		$php[] = '		return array_keys($props);';
		$php[] = '	}';
		$php[] = '	public function renderRichText($xml)';
		$php[] = '	{';
		if ($quickSource !== \false)
		{
			$php[] = '		if (!isset($this->quickRenderingTest) || !preg_match($this->quickRenderingTest, $xml))';
			$php[] = '		{';
			$php[] = '			try';
			$php[] = '			{';
			$php[] = '				return $this->renderQuick($xml);';
			$php[] = '			}';
			$php[] = '			catch (\\Exception $e)';
			$php[] = '			{';
			$php[] = '			}';
			$php[] = '		}';
		}
		$php[] = '		$dom = $this->loadXML($xml);';
		if ($needsXPath)
			$php[] = '		$this->xpath = new \\DOMXPath($dom);';
		$php[] = "		\$this->out = '';";
		$php[] = '		$this->at($dom->documentElement);';
		if ($needsXPath)
			$php[] = '		$this->xpath = null;';
		$php[] = '		return $this->out;';
		$php[] = '	}';
		if ($hasApplyTemplatesSelect)
			$php[] = '	protected function at(\\DOMNode $root, $xpath = null)';
		else
			$php[] = '	protected function at(\\DOMNode $root)';
		$php[] = '	{';
		$php[] = '		if ($root->nodeType === 3)';
		$php[] = '		{';
		$php[] = '			$this->out .= htmlspecialchars($root->textContent,' . \ENT_NOQUOTES . ');';
		$php[] = '		}';
		$php[] = '		else';
		$php[] = '		{';
		if ($hasApplyTemplatesSelect)
			$php[] = '			foreach (isset($xpath) ? $this->xpath->query($xpath, $root) : $root->childNodes as $node)';
		else
			$php[] = '			foreach ($root->childNodes as $node)';
		$php[] = '			{';
		$php[] = '				if (!isset(self::$tagBranches[$node->nodeName]))';
		$php[] = '				{';
		$php[] = '					$this->at($node);';
		$php[] = '				}';
		$php[] = '				else';
		$php[] = '				{';
		$php[] = '					$tb = self::$tagBranches[$node->nodeName];';
		$php[] = '					' . $templatesSource;
		$php[] = '				}';
		$php[] = '			}';
		$php[] = '		}';
		$php[] = '	}';
		if (\strpos($templatesSource, '$this->getParamAsXPath') !== \false)
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
		if ($quickSource !== \false)
			$php[] = $quickSource;
		$php[] = '}';
		$php = \implode("\n", $php);
		if (isset($this->controlStructuresOptimizer))
			$php = $this->controlStructuresOptimizer->optimize($php);
		$className = (isset($this->className))
		           ? $this->className
		           : $this->defaultClassPrefix . \sha1($php);
		$this->lastClassName = $className;
		$header = "/**\n* @package   s9e\TextFormatter\n* @copyright Copyright (c) 2010-2015 The s9e Authors\n* @license   http://www.opensource.org/licenses/mit-license.php The MIT License\n*/\n\n";
		$pos = \strrpos($className, '\\');
		if ($pos !== \false)
		{
			$header .= 'namespace ' . \substr($className, 0, $pos) . ";\n\n";
			$className = \substr($className, 1 + $pos);
		}
		$php = $header . 'class ' . $className . $php;
		return $php;
	}
	protected static function export(array $value)
	{
		$pairs = [];
		foreach ($value as $k => $v)
			$pairs[] = \var_export($k, \true) . '=>' . \var_export($v, \true);
		return '[' . \implode(',', $pairs) . ']';
	}
}