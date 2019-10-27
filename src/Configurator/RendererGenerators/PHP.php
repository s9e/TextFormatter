<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
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
use s9e\TextFormatter\Configurator\RendererGenerators\PHP\SwitchStatement;
use s9e\TextFormatter\Configurator\Rendering;
use s9e\TextFormatter\Configurator\TemplateNormalizer;
class PHP implements RendererGenerator
{
	const XMLNS_XSL = 'http://www.w3.org/1999/XSL/Transform';
	public $cacheDir;
	public $className;
	public $controlStructuresOptimizer;
	public $defaultClassPrefix = 'Renderer_';
	public $enableQuickRenderer = \true;
	public $filepath;
	public $lastClassName;
	public $lastFilepath;
	protected $normalizer;
	public $optimizer;
	public $serializer;
	public function __construct($cacheDir = \null)
	{
		$this->cacheDir = (isset($cacheDir)) ? $cacheDir : \sys_get_temp_dir();
		if (\extension_loaded('tokenizer'))
		{
			$this->controlStructuresOptimizer = new ControlStructuresOptimizer;
			$this->optimizer = new Optimizer;
		}
		$this->serializer = new Serializer;
		$this->normalizer = new TemplateNormalizer(array('RemoveLivePreviewAttributes'));
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
		return new $this->lastClassName;
	}
	public function generate(Rendering $rendering)
	{
		$compiledTemplates = \array_map(array($this, 'compileTemplate'), $rendering->getTemplates());
		$php = array();
		$php[] = ' extends \\s9e\\TextFormatter\\Renderers\\PHP';
		$php[] = '{';
		$php[] = '	protected $params=' . self::export($rendering->getAllParameters()) . ';';
		$php[] = '	protected function renderNode(\\DOMNode $node)';
		$php[] = '	{';
		$php[] = '		' . SwitchStatement::generate('$node->nodeName', $compiledTemplates, '$this->at($node);');
		$php[] = '	}';
		if ($this->enableQuickRenderer)
			$php[] = Quick::getSource($compiledTemplates);
		$php[] = '}';
		$php = \implode("\n", $php);
		if (isset($this->controlStructuresOptimizer))
			$php = $this->controlStructuresOptimizer->optimize($php);
		$className = (isset($this->className))
		           ? $this->className
		           : $this->defaultClassPrefix . \sha1($php);
		$this->lastClassName = $className;
		$header = "\n/**\n* @package   s9e\TextFormatter\n* @copyright Copyright (c) 2010-2019 The s9e Authors\n* @license   http://www.opensource.org/licenses/mit-license.php The MIT License\n*/\n";
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
		$pairs = array();
		foreach ($value as $k => $v)
			$pairs[] = \var_export($k, \true) . '=>' . \var_export($v, \true);
		return 'array(' . \implode(',', $pairs) . ')';
	}
	protected function compileTemplate($template)
	{
		$template = $this->normalizer->normalizeTemplate($template);
		$ir = TemplateParser::parse($template);
		$php = $this->serializer->serialize($ir->documentElement);
		if (isset($this->optimizer))
			$php = $this->optimizer->optimize($php);
		return $php;
	}
}