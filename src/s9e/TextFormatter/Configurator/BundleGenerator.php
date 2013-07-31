<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Parser;

class BundleGenerator
{
	/**
	* @var callback Callback used to serialize the objects
	*/
	public $serializer = 'serialize';

	/**
	* @var string Callback used to unserialize the serialized objects (must be a string)
	*/
	public $unserializer = 'unserialize';

	/**
	* Create and return the source of a bundle based on given Configurator instance
	*
	* @param  Configurator $configurator Configured instance of Configurator
	* @param  string       $className    Name of the bundle class
	* @return string                     PHP source for the bundle
	*/
	public function generate(Configurator $configurator, $className)
	{
		// Create a renderer
		$renderer = $configurator->getRenderer();

		// Add the automatic HTML5 rules
		$configurator->addHTML5Rules(['renderer' => $renderer]);

		// Cleanup the config and create a parser
		$config = $configurator->asConfig();
		ConfigHelper::filterVariants($config);
		ConfigHelper::optimizeArray($config);
		$parser = new Parser($config);

		// Generate the source for the JavaScript live preview feature
		$js = ';(function(){' . $configurator->javascript->getParser() . '})()';

		// Split the bundle's class name and its namespace
		$namespace = '';
		if (preg_match('#(.*)\\\\([^\\\\]+)$#', $className, $m))
		{
			$namespace = $m[1];
			$className = $m[2];
		}

		// Start with the standard header
		$php = "/**\n"
		     . "* @package   s9e\TextFormatter\n"
		     . "* @copyright Copyright (c) 2010-2013 The s9e Authors\n"
		     . "* @license   http://www.opensource.org/licenses/mit-license.php The MIT License\n"
		     . "*/\n";

		if ($namespace)
		{
			$php .= 'namespace ' . $namespace . ";\n\n";
		}

		// Generate and append the bundle class
		$php .= 'abstract class ' . $className . " extends \\s9e\\TextFormatter\\Bundle\n";
		$php .= "{\n";
		$php .= "	/**\n";
		$php .= "	* @var Parser Singleton instance used by parse()\n";
		$php .= "	*/\n";
		$php .= "	public static \$parser;\n";
		$php .= "\n";
		$php .= "	/**\n";
		$php .= "	* @var Renderer Singleton instance used by render() and renderMulti()\n";
		$php .= "	*/\n";
		$php .= "	public static \$renderer;\n";
		$php .= "\n";
		$php .= "	/**\n";
		$php .= "	* {@inheritdoc}\n";
		$php .= "	*/\n";
		$php .= "	public static function getParser()\n";
		$php .= "	{\n";
		$php .= "		return " . $this->export($parser) . ";\n";
		$php .= "	}\n";
		$php .= "\n";
		$php .= "	/**\n";
		$php .= "	* {@inheritdoc}\n";
		$php .= "	*/\n";
		$php .= "	public static function getRenderer()\n";
		$php .= "	{\n";
		$php .= "		return " . $this->export($renderer) . ";\n";
		$php .= "	}\n";
		$php .= '}';

		return $php;
	}

	/**
	* Serialize and export a given object as PHP code
	*
	* @param  string $obj Original object
	* @return string      PHP code
	*/
	protected function export($obj)
	{
		// Serialize the object
		$str = call_user_func($this->serializer, $obj);

		// Escape control characters, bytes >= 0x7f and characters \ $ and "
		$str = '"' . addcslashes($str, "\x00..\x1f\x7f..\xff\\\$\"") . '"';

		return $this->unserializer . '(' . $str . ')';
	}
}