<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP;
class BundleGenerator
{
	protected $configurator;
	public $serializer = 'serialize';
	public $unserializer = 'unserialize';
	public function __construct(Configurator $configurator)
	{
		$this->configurator = $configurator;
	}
	public function generate($className, array $options = array())
	{
		$options += array('autoInclude' => \true);
		$objects  = $this->configurator->finalize();
		$parser   = $objects['parser'];
		$renderer = $objects['renderer'];
		$namespace = '';
		if (\preg_match('#(.*)\\\\([^\\\\]+)$#', $className, $m))
		{
			$namespace = $m[1];
			$className = $m[2];
		}
		$php = array();
		$php[] = '/**';
		$php[] = '* @package   s9e\TextFormatter';
		$php[] = '* @copyright Copyright (c) 2010-2019 The s9e Authors';
		$php[] = '* @license   http://www.opensource.org/licenses/mit-license.php The MIT License';
		$php[] = '*/';
		if ($namespace)
		{
			$php[] = 'namespace ' . $namespace . ';';
			$php[] = '';
		}
		$php[] = 'abstract class ' . $className . ' extends \\s9e\\TextFormatter\\Bundle';
		$php[] = '{';
		$php[] = '	/**';
		$php[] = '	* @var s9e\\TextFormatter\\Parser Singleton instance used by parse()';
		$php[] = '	*/';
		$php[] = '	protected static $parser;';
		$php[] = '';
		$php[] = '	/**';
		$php[] = '	* @var s9e\\TextFormatter\\Renderer Singleton instance used by render()';
		$php[] = '	*/';
		$php[] = '	protected static $renderer;';
		$php[] = '';
		$events = array(
			'beforeParse'
				=> 'Callback executed before parse(), receives the original text as argument',
			'afterParse'
				=> 'Callback executed after parse(), receives the parsed text as argument',
			'beforeRender'
				=> 'Callback executed before render(), receives the parsed text as argument',
			'afterRender'
				=> 'Callback executed after render(), receives the output as argument',
			'beforeUnparse'
				=> 'Callback executed before unparse(), receives the parsed text as argument',
			'afterUnparse'
				=> 'Callback executed after unparse(), receives the original text as argument'
		);
		foreach ($events as $eventName => $eventDesc)
			if (isset($options[$eventName]))
			{
				$php[] = '	/**';
				$php[] = '	* @var ' . $eventDesc;
				$php[] = '	*/';
				$php[] = '	public static $' . $eventName . ' = ' . \var_export($options[$eventName], \true) . ';';
				$php[] = '';
			}
		$php[] = '	/**';
		$php[] = '	* Return a new instance of s9e\\TextFormatter\\Parser';
		$php[] = '	*';
		$php[] = '	* @return s9e\\TextFormatter\\Parser';
		$php[] = '	*/';
		$php[] = '	public static function getParser()';
		$php[] = '	{';
		if (isset($options['parserSetup']))
		{
			$php[] = '		$parser = ' . $this->exportObject($parser) . ';';
			$php[] = '		' . $this->exportCallback($namespace, $options['parserSetup'], '$parser') . ';';
			$php[] = '';
			$php[] = '		return $parser;';
		}
		else
			$php[] = '		return ' . $this->exportObject($parser) . ';';
		$php[] = '	}';
		$php[] = '';
		$php[] = '	/**';
		$php[] = '	* Return a new instance of s9e\\TextFormatter\\Renderer';
		$php[] = '	*';
		$php[] = '	* @return s9e\\TextFormatter\\Renderer';
		$php[] = '	*/';
		$php[] = '	public static function getRenderer()';
		$php[] = '	{';
		if (!empty($options['autoInclude'])
		 && $this->configurator->rendering->engine instanceof PHP
		 && isset($this->configurator->rendering->engine->lastFilepath))
		{
			$className = \get_class($renderer);
			$filepath  = \realpath($this->configurator->rendering->engine->lastFilepath);
			$php[] = '		if (!class_exists(' . \var_export($className, \true) . ', false)';
			$php[] = '		 && file_exists(' . \var_export($filepath, \true) . '))';
			$php[] = '		{';
			$php[] = '			include ' . \var_export($filepath, \true) . ';';
			$php[] = '		}';
			$php[] = '';
		}
		if (isset($options['rendererSetup']))
		{
			$php[] = '		$renderer = ' . $this->exportObject($renderer) . ';';
			$php[] = '		' . $this->exportCallback($namespace, $options['rendererSetup'], '$renderer') . ';';
			$php[] = '';
			$php[] = '		return $renderer;';
		}
		else
			$php[] = '		return ' . $this->exportObject($renderer) . ';';
		$php[] = '	}';
		$php[] = '}';
		return \implode("\n", $php);
	}
	protected function exportCallback($namespace, $callback, $argument)
	{
		if (\is_array($callback) && \is_string($callback[0]))
			$callback = $callback[0] . '::' . $callback[1];
		if (!\is_string($callback))
			return 'call_user_func(' . \var_export($callback, \true) . ', ' . $argument . ')';
		if ($callback[0] !== '\\')
			$callback = '\\' . $callback;
		if (\substr($callback, 0, 2 + \strlen($namespace)) === '\\' . $namespace . '\\')
			$callback = \substr($callback, 2 + \strlen($namespace));
		return $callback . '(' . $argument . ')';
	}
	protected function exportObject($obj)
	{
		$str = \call_user_func($this->serializer, $obj);
		$str = \var_export($str, \true);
		return $this->unserializer . '(' . $str . ')';
	}
}