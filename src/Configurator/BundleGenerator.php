<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP;

class BundleGenerator
{
	/**
	* @var Configurator Configurator this instance belongs to
	*/
	protected $configurator;

	/**
	* @var callable Callback used to serialize the objects
	*/
	public $serializer = 'serialize';

	/**
	* @var string Callback used to unserialize the serialized objects (must be a string)
	*/
	public $unserializer = 'unserialize';

	/**
	* Constructor
	*
	* @param  Configurator $configurator Configurator
	*/
	public function __construct(Configurator $configurator)
	{
		$this->configurator = $configurator;
	}

	/**
	* Create and return the source of a bundle based on given Configurator instance
	*
	* Options:
	*
	*  - autoInclude: automatically load the source of the PHP renderer (default: true)
	*
	* @param  string $className Name of the bundle class
	* @param  array  $options   Associative array of optional settings
	* @return string            PHP source for the bundle
	*/
	public function generate($className, array $options = [])
	{
		// Add default options
		$options += ['autoInclude' => true];

		// Copy the PHP files header if applicable
		if ($this->configurator->rendering->engine instanceof PHP)
		{
			$this->configurator->rendering->engine->phpHeader = $this->configurator->phpHeader;
		}

		// Get the parser and renderer
		$objects  = $this->configurator->finalize();
		$parser   = $objects['parser'];
		$renderer = $objects['renderer'];

		// Split the bundle's class name and its namespace
		$namespace = '';
		if (preg_match('#(.*)\\\\([^\\\\]+)$#', $className, $m))
		{
			$namespace = $m[1];
			$className = $m[2];
		}

		// Start with the standard header
		$php   = [];
		$php[] = $this->configurator->phpHeader;

		if ($namespace)
		{
			$php[] = 'namespace ' . $namespace . ';';
			$php[] = '';
		}

		// Generate and append the bundle class
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

		// Add the event callbacks if applicable
		$events = [
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
		];
		foreach ($events as $eventName => $eventDesc)
		{
			if (isset($options[$eventName]))
			{
				$php[] = '	/**';
				$php[] = '	* @var ' . $eventDesc;
				$php[] = '	*/';
				$php[] = '	public static $' . $eventName . ' = ' . var_export($options[$eventName], true) . ';';
				$php[] = '';
			}
		}

		if (isset($objects['js']))
		{
			$php[] = '	/**';
			$php[] = '	* {@inheritdoc}';
			$php[] = '	*/';
			$php[] = '	public static function getJS()';
			$php[] = '	{';
			$php[] = '		return ' . var_export($objects['js'], true) . ';';
			$php[] = '	}';
			$php[] = '';
		}

		$php[] = '	/**';
		$php[] = '	* {@inheritdoc}';
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
		{
			$php[] = '		return ' . $this->exportObject($parser) . ';';
		}

		$php[] = '	}';
		$php[] = '';
		$php[] = '	/**';
		$php[] = '	* {@inheritdoc}';
		$php[] = '	*/';
		$php[] = '	public static function getRenderer()';
		$php[] = '	{';

		// If this is a PHP renderer and we know where it's saved, automatically load it as needed
		if (!empty($options['autoInclude'])
		 && $this->configurator->rendering->engine instanceof PHP
		 && isset($this->configurator->rendering->engine->lastFilepath))
		{
			$className = get_class($renderer);
			$filepath  = realpath($this->configurator->rendering->engine->lastFilepath);

			$php[] = '		if (!class_exists(' . var_export($className, true) . ', false)';
			$php[] = '		 && file_exists(' . var_export($filepath, true) . '))';
			$php[] = '		{';
			$php[] = '			include ' . var_export($filepath, true) . ';';
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
		{
			$php[] = '		return ' . $this->exportObject($renderer) . ';';
		}

		$php[] = '	}';
		$php[] = '}';

		return implode("\n", $php);
	}

	/**
	* Export a given callback as PHP code
	*
	* @param  string   $namespace Namespace in which the callback is execute
	* @param  callable $callback  Original callback
	* @param  string   $argument  Callback's argument (as PHP code)
	* @return string              PHP code
	*/
	protected function exportCallback($namespace, callable $callback, $argument)
	{
		if (is_array($callback) && is_string($callback[0]))
		{
			// Replace ['foo', 'bar'] with 'foo::bar'
			$callback = $callback[0] . '::' . $callback[1];
		}

		if (!is_string($callback))
		{
			return 'call_user_func(' . var_export($callback, true) . ', ' . $argument . ')';
		}

		// Ensure that the callback starts with a \
		if ($callback[0] !== '\\')
		{
			$callback = '\\' . $callback;
		}

		// Replace \foo\bar::baz() with bar::baz() if we're in namespace foo
		if (substr($callback, 0, 2 + strlen($namespace)) === '\\' . $namespace . '\\')
		{
			$callback = substr($callback, 2 + strlen($namespace));
		}

		return $callback . '(' . $argument . ')';
	}

	/**
	* Serialize and export a given object as PHP code
	*
	* @param  object $obj Original object
	* @return string      PHP code
	*/
	protected function exportObject($obj)
	{
		// Serialize the object
		$str = call_user_func($this->serializer, $obj);

		// Export the object's source
		$str = var_export($str, true);

		return $this->unserializer . '(' . $str . ')';
	}
}