<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter;

use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Configurator\BundleGenerator;
use s9e\TextFormatter\Configurator\Collections\AttributeFilterCollection;
use s9e\TextFormatter\Configurator\Collections\PluginCollection;
use s9e\TextFormatter\Configurator\Collections\Ruleset;
use s9e\TextFormatter\Configurator\Collections\TagCollection;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Helpers\RulesHelper;
use s9e\TextFormatter\Configurator\JavaScript;
use s9e\TextFormatter\Configurator\JavaScript\Dictionary;
use s9e\TextFormatter\Configurator\Rendering;
use s9e\TextFormatter\Configurator\RulesGenerator;
use s9e\TextFormatter\Configurator\TemplateChecker;
use s9e\TextFormatter\Configurator\TemplateNormalizer;
use s9e\TextFormatter\Configurator\UrlConfig;

/**
* @property Plugins\Autoemail\Configurator $Autoemail Autoemail plugin's configurator
* @property Plugins\Autolink\Configurator $Autolink Autolink plugin's configurator
* @property Plugins\BBCodes\Configurator $BBCodes BBCodes plugin's configurator
* @property Plugins\Censor\Configurator $Censor Censor plugin's configurator
* @property Plugins\Emoji\Configurator $Emoji Emoji plugin's configurator
* @property Plugins\Emoticons\Configurator $Emoticons Emoticons plugin's configurator
* @property Plugins\Escaper\Configurator $Escaper Escaper plugin's configurator
* @property Plugins\FancyPants\Configurator $FancyPants FancyPants plugin's configurator
* @property Plugins\HTMLComments\Configurator $HTMLComments HTMLComments plugin's configurator
* @property Plugins\HTMLElements\Configurator $HTMLElements HTMLElements plugin's configurator
* @property Plugins\HTMLEntities\Configurator $HTMLEntities HTMLEntities plugin's configurator
* @property Plugins\Keywords\Configurator $Keywords Keywords plugin's configurator
* @property Plugins\Litedown\Configurator $Litedown Litedown plugin's configurator
* @property Plugins\MediaEmbed\Configurator $MediaEmbed MediaEmbed plugin's configurator
* @property Plugins\Preg\Configurator $Preg Preg plugin's configurator
* @property UrlConfig $urlConfig Default URL config
*/
class Configurator implements ConfigProvider
{
	/**
	* @var AttributeFilterCollection Dynamically-populated collection of AttributeFilter instances
	*/
	public $attributeFilters;

	/**
	* @var BundleGenerator Default bundle generator
	*/
	public $bundleGenerator;

	/**
	* @var JavaScript JavaScript manipulation object
	*/
	public $javascript;

	/**
	* @var PluginCollection Loaded plugins
	*/
	public $plugins;

	/**
	* @var array Array of variables that are available to the filters during parsing
	*/
	public $registeredVars;

	/**
	* @var Rendering Rendering configuration
	*/
	public $rendering;

	/**
	* @var Ruleset Rules that apply at the root of the text
	*/
	public $rootRules;

	/**
	* @var RulesGenerator Generator used by $this->getRenderer()
	*/
	public $rulesGenerator;

	/**
	* @var TagCollection Tags repository
	*/
	public $tags;

	/**
	* @var TemplateChecker Default template checker
	*/
	public $templateChecker;

	/**
	* @var TemplateNormalizer Default template normalizer
	*/
	public $templateNormalizer;

	/**
	* Constructor
	*
	* Prepares the collections that hold tags and filters, the UrlConfig object as well as the
	* various helpers required to generate a full config.
	*/
	public function __construct()
	{
		$this->attributeFilters   = new AttributeFilterCollection;
		$this->bundleGenerator    = new BundleGenerator($this);
		$this->plugins            = new PluginCollection($this);
		$this->registeredVars     = ['urlConfig' => new UrlConfig];
		$this->rendering          = new Rendering($this);
		$this->rootRules          = new Ruleset;
		$this->rulesGenerator     = new RulesGenerator;
		$this->tags               = new TagCollection;
		$this->templateChecker    = new TemplateChecker;
		$this->templateNormalizer = new TemplateNormalizer;
	}

	//==========================================================================
	// Magic methods
	//==========================================================================

	/**
	* Magic __get automatically loads plugins, returns registered vars
	*
	* @param  string $k Property name
	* @return mixed
	*/
	public function __get($k)
	{
		if (preg_match('#^[A-Z][A-Za-z_0-9]+$#D', $k))
		{
			return (isset($this->plugins[$k]))
			     ? $this->plugins[$k]
			     : $this->plugins->load($k);
		}

		if (isset($this->registeredVars[$k]))
		{
			return $this->registeredVars[$k];
		}

		throw new RuntimeException("Undefined property '" . __CLASS__ . '::$' . $k . "'");
	}

	/**
	* Magic __isset checks existence in the plugins collection and registered vars
	*
	* @param  string $k Property name
	* @return bool
	*/
	public function __isset($k)
	{
		if (preg_match('#^[A-Z][A-Za-z_0-9]+$#D', $k))
		{
			return isset($this->plugins[$k]);
		}

		return isset($this->registeredVars[$k]);
	}

	/**
	* Magic __set adds to the plugins collection, registers vars
	*
	* @param  string $k Property name
	* @param  mixed  $v Property value
	* @return mixed
	*/
	public function __set($k, $v)
	{
		if (preg_match('#^[A-Z][A-Za-z_0-9]+$#D', $k))
		{
			$this->plugins[$k] = $v;
		}
		else
		{
			$this->registeredVars[$k] = $v;
		}
	}

	/**
	* Magic __set removes plugins from the plugins collection, unregisters vars
	*
	* @param  string $k Property name
	* @return mixed
	*/
	public function __unset($k)
	{
		if (preg_match('#^[A-Z][A-Za-z_0-9]+$#D', $k))
		{
			unset($this->plugins[$k]);
		}
		else
		{
			unset($this->registeredVars[$k]);
		}
	}

	//==========================================================================
	// API
	//==========================================================================

	/**
	* Enable the creation of a JavaScript parser
	*
	* @return void
	*/
	public function enableJavaScript()
	{
		if (!isset($this->javascript))
		{
			$this->javascript = new JavaScript($this);
		}
	}

	/**
	* Finalize this configuration and return all the relevant objects
	*
	* Options: (also see addHTMLRules() options)
	*
	*  - addHTML5Rules:    whether to call addHTML5Rules()
	*  - finalizeParser:   callback executed after the parser is created (gets the parser as arg)
	*  - finalizeRenderer: same with the renderer
	*  - optimizeConfig:   whether to optimize the parser's config using references
	*  - returnParser:     whether to return an instance of Parser in the "parser" key
	*  - returnRenderer:   whether to return an instance of Renderer in the "renderer" key
	*
	* @param  array $options
	* @return array One "parser" element and one "renderer" element unless specified otherwise
	*/
	public function finalize(array $options = [])
	{
		$return = [];

		// Add default options
		$options += [
			'addHTML5Rules'  => true,
			'optimizeConfig' => true,
			'returnJS'       => isset($this->javascript),
			'returnParser'   => true,
			'returnRenderer' => true
		];

		// Add the HTML5 rules if applicable
		if ($options['addHTML5Rules'])
		{
			$this->addHTML5Rules($options);
		}

		// Create a renderer as needed
		if ($options['returnRenderer'])
		{
			// Create a renderer
			$renderer = $this->getRenderer();

			// Execute the renderer callback if applicable
			if (isset($options['finalizeRenderer']))
			{
				$options['finalizeRenderer']($renderer);
			}

			$return['renderer'] = $renderer;
		}

		if ($options['returnJS'] || $options['returnParser'])
		{
			$config = $this->asConfig();

			if ($options['returnJS'])
			{
				// Copy the config before replacing variants with their JS value
				$jsConfig = $config;
				ConfigHelper::filterVariants($jsConfig, 'JS');

				$return['js'] = $this->javascript->getParser($jsConfig);
			}

			if ($options['returnParser'])
			{
				// Remove JS-specific data from the config
				ConfigHelper::filterVariants($config);

				if ($options['optimizeConfig'])
				{
					ConfigHelper::optimizeArray($config);
				}

				// Create a parser
				$parser = new Parser($config);

				// Execute the parser callback if applicable
				if (isset($options['finalizeParser']))
				{
					$options['finalizeParser']($parser);
				}

				$return['parser'] = $parser;
			}
		}

		return $return;
	}

	/**
	* Return an instance of Parser based on the current config
	*
	* @return \s9e\TextFormatter\Parser
	*/
	public function getParser()
	{
		// Generate the config array
		$config = $this->asConfig();

		// Remove variants
		ConfigHelper::filterVariants($config);

		return new Parser($config);
	}

	/**
	* Return an instance of Renderer based on the current config
	*
	* @return \s9e\TextFormatter\Renderer
	*/
	public function getRenderer()
	{
		return $this->rendering->getRenderer();
	}

	/**
	* Load a bundle into this configuration
	*
	* @param  string $bundleName Name of the bundle
	* @return void
	*/
	public function loadBundle($bundleName)
	{
		if (!preg_match('#^[A-Z][A-Za-z0-9]+$#D', $bundleName))
		{
			throw new InvalidArgumentException("Invalid bundle name '" . $bundleName . "'");
		}

		$className = __CLASS__ . '\\Bundles\\' . $bundleName;

		$bundle = new $className;
		$bundle->configure($this);
	}

	/**
	* Create and save a bundle based on this configuration
	*
	* @param  string $className Name of the bundle class
	* @param  string $filepath  Path where to save the bundle file
	* @param  array  $options   Options passed to the bundle generator
	* @return bool              Whether the write succeeded
	*/
	public function saveBundle($className, $filepath, array $options = [])
	{
		$file = "<?php\n\n" . $this->bundleGenerator->generate($className, $options);

		return (file_put_contents($filepath, $file) !== false);
	}

	/**
	* Add the rules that are generated based on HTML5 specs
	*
	* @see s9e\TextFormatter\ConfigBuilder\RulesGenerator
	*
	* @param  array $options Options passed to RulesGenerator::getRules()
	* @return void
	*/
	public function addHTML5Rules(array $options = [])
	{
		// Add the default options
		$options += ['rootRules' => $this->rootRules];

		// Finalize the plugins' config
		$this->plugins->finalize();

		// Normalize the tags' templates
		foreach ($this->tags as $tag)
		{
			$this->templateNormalizer->normalizeTag($tag);
		}

		// Get the rules
		$rules = $this->rulesGenerator->getRules($this->tags, $options);

		// Add the rules pertaining to the root
		$this->rootRules->merge($rules['root'], false);

		// Add the rules pertaining to each tag
		foreach ($rules['tags'] as $tagName => $tagRules)
		{
			$this->tags[$tagName]->rules->merge($tagRules, false);
		}
	}

	/**
	* Generate and return the complete config array
	*
	* @return array
	*/
	public function asConfig()
	{
		// Finalize the plugins' config
		$this->plugins->finalize();

		// Remove properties that shouldn't be turned into config arrays
		$properties = get_object_vars($this);
		unset($properties['attributeFilters']);
		unset($properties['bundleGenerator']);
		unset($properties['javascript']);
		unset($properties['rendering']);
		unset($properties['rulesGenerator']);
		unset($properties['registeredVars']);
		unset($properties['templateChecker']);
		unset($properties['templateNormalizer']);
		unset($properties['stylesheet']);

		// Create the config array
		$config    = ConfigHelper::toArray($properties);
		$bitfields = RulesHelper::getBitfields($this->tags, $this->rootRules);

		// Save the root context
		$config['rootContext'] = $bitfields['root'];
		$config['rootContext']['flags'] = $config['rootRules']['flags'];

		// Save the registered vars (including the empty ones)
		$config['registeredVars'] = ConfigHelper::toArray($this->registeredVars, true);

		// Make sure those keys exist even if they're empty
		$config += [
			'plugins' => [],
			'tags'    => []
		];

		// Remove unused tags
		$config['tags'] = array_intersect_key($config['tags'], $bitfields['tags']);

		// Add the bitfield information to each tag
		foreach ($bitfields['tags'] as $tagName => $tagBitfields)
		{
			$config['tags'][$tagName] += $tagBitfields;
		}

		// Remove unused entries
		unset($config['rootRules']);

		return $config;
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
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
	* @var callback Callback used to serialize the objects
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
	* @return void
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

		// Get the parser and renderer
		$objects  = $this->configurator->finalize($options);
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
		$php = [];
		$php[] = '/**';
		$php[] = '* @package   s9e\TextFormatter';
		$php[] = '* @copyright Copyright (c) 2010-2015 The s9e Authors';
		$php[] = '* @license   http://www.opensource.org/licenses/mit-license.php The MIT License';
		$php[] = '*/';

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
		$php[] = '	public static $parser;';
		$php[] = '';
		$php[] = '	/**';
		$php[] = '	* @var s9e\\TextFormatter\\Renderer Singleton instance used by render()';
		$php[] = '	*/';
		$php[] = '	public static $renderer;';
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
		{
			$php[] = '		return ' . $this->exportObject($parser) . ';';
		}

		$php[] = '	}';
		$php[] = '';
		$php[] = '	/**';
		$php[] = '	* Return a new instance of s9e\\TextFormatter\\Renderer';
		$php[] = '	*';
		$php[] = '	* @return s9e\\TextFormatter\\Renderer';
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
	* @param  string $obj Original object
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

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;

interface ConfigProvider
{
	/**
	* Return an array-based representation of this object to be used for parsing
	*
	* NOTE: if this method was named getConfig() it could interfere with magic getters from
	*       the Configurable trait
	*
	* @return array|\s9e\TextFormatter\Configurator\JavaScript\Dictionary|null
	*/
	public function asConfig();
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;

use DOMAttr;
use RuntimeException;

abstract class AVTHelper
{
	/**
	* Parse an attribute value template
	*
	* @link http://www.w3.org/TR/xslt#dt-attribute-value-template
	*
	* @param  string $attrValue Attribute value
	* @return array             Array of tokens
	*/
	public static function parse($attrValue)
	{
		$tokens  = [];
		$attrLen = strlen($attrValue);

		$pos = 0;
		while ($pos < $attrLen)
		{
			// Look for opening brackets
			if ($attrValue[$pos] === '{')
			{
				// Two brackets = one literal bracket
				if (substr($attrValue, $pos, 2) === '{{')
				{
					$tokens[] = ['literal', '{'];
					$pos += 2;

					continue;
				}

				// Move the cursor past the left bracket
				++$pos;

				// We're inside an inline XPath expression. We need to parse it in order to find
				// where it ends
				$expr = '';
				while ($pos < $attrLen)
				{
					// Capture everything up to the next "interesting" char: ', " or }
					$spn = strcspn($attrValue, '\'"}', $pos);
					if ($spn)
					{
						$expr .= substr($attrValue, $pos, $spn);
						$pos += $spn;
					}

					if ($pos >= $attrLen)
					{
						throw new RuntimeException('Unterminated XPath expression');
					}

					// Capture the character then move the cursor
					$c = $attrValue[$pos];
					++$pos;

					if ($c === '}')
					{
						// Done with this expression
						break;
					}

					// Look for the matching quote
					$quotePos = strpos($attrValue, $c, $pos);
					if ($quotePos === false)
					{
						throw new RuntimeException('Unterminated XPath expression');
					}

					// Capture the content of that string then move the cursor past it
					$expr .= $c . substr($attrValue, $pos, $quotePos + 1 - $pos);
					$pos = 1 + $quotePos;
				}

				$tokens[] = ['expression', $expr];
			}

			$spn = strcspn($attrValue, '{', $pos);
			if ($spn)
			{
				// Capture this chunk of attribute value
				$str = substr($attrValue, $pos, $spn);

				// Unescape right brackets
				$str = str_replace('}}', '}', $str);

				// Add the value and move the cursor
				$tokens[] = ['literal', $str];
				$pos += $spn;
			}
		}

		return $tokens;
	}

	/**
	* Replace the value of an attribute via the provided callback
	*
	* The callback will receive an array containing the type and value of each token in the AVT.
	* Its return value should use the same format
	*
	* @param  DOMAttr  $attribute
	* @param  callable $callback
	* @return void
	*/
	public static function replace(DOMAttr $attribute, callable $callback)
	{
		$tokens = self::parse($attribute->value);
		foreach ($tokens as $k => $token)
		{
			$tokens[$k] = $callback($token);
		}

		$attribute->value = htmlspecialchars(self::serialize($tokens), ENT_NOQUOTES, 'UTF-8');
	}

	/**
	* Serialize an array of AVT tokens back into an attribute value
	*
	* @param  array  $tokens
	* @return string
	*/
	public static function serialize(array $tokens)
	{
		$attrValue = '';
		foreach ($tokens as $token)
		{
			if ($token[0] === 'literal')
			{
				$attrValue .= preg_replace('([{}])', '$0$0', $token[1]);
			}
			elseif ($token[0] === 'expression')
			{
				$attrValue .= '{' . $token[1] . '}';
			}
			else
			{
				throw new RuntimeException('Unknown token type');
			}
		}

		return $attrValue;
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;

use RuntimeException;
use Traversable;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Items\Variant;
use s9e\TextFormatter\Configurator\JavaScript\Dictionary;

abstract class ConfigHelper
{
	/**
	* Recursively filter a config array to replace variants with the desired value
	*
	* @param  array|Traversable &$config  Config array
	* @param  string             $variant Preferred variant
	* @return void
	*/
	public static function filterVariants(&$config, $variant = null)
	{
		foreach ($config as $name => $value)
		{
			// Use while instead of if to handle recursive variants. This is not supposed to happen
			// though
			while ($value instanceof Variant)
			{
				$value = $value->get($variant);

				// A null value indicates that the value is not supposed to exist for given variant.
				// This is different from having no specific value for given variant
				if ($value === null)
				{
					unset($config[$name]);

					continue 2;
				}
			}

			if ($value instanceof Dictionary && $variant !== 'JS')
			{
				$value = (array) $value;
			}

			if (is_array($value) || $value instanceof Traversable)
			{
				self::filterVariants($value, $variant);
			}

			$config[$name] = $value;
		}
	}

	/**
	* Generate a quickMatch string from a list of strings
	*
	* This is basically a LCS implementation, tuned for small strings and fast failure
	*
	* @param  array $strings Array of strings
	* @return mixed          quickMatch string, or FALSE if none could be generated
	*/
	public static function generateQuickMatchFromList(array $strings)
	{
		foreach ($strings as $string)
		{
			$stringLen  = strlen($string);
			$substrings = [];

			for ($len = $stringLen; $len; --$len)
			{
				$pos = $stringLen - $len;

				do
				{
					$substrings[substr($string, $pos, $len)] = 1;
				}
				while (--$pos >= 0);
			}

			if (isset($goodStrings))
			{
				$goodStrings = array_intersect_key($goodStrings, $substrings);

				if (empty($goodStrings))
				{
					break;
				}
			}
			else
			{
				$goodStrings = $substrings;
			}
		}

		if (empty($goodStrings))
		{
			return false;
		}

		// The strings are stored by length descending, so we return the first in the list
		return strval(key($goodStrings));
	}

	/**
	* Optimize the size of a deep array by deduplicating identical structures
	*
	* This method is meant to be used on a config array which is only read and never modified
	*
	* @param  array &$config
	* @param  array &$cache
	* @return array
	*/
	public static function optimizeArray(array &$config, array &$cache = [])
	{
		foreach ($config as $k => &$v)
		{
			if (!is_array($v))
			{
				continue;
			}

			// Dig deeper into this array
			self::optimizeArray($v, $cache);

			// Look for a matching structure
			$cacheKey = serialize($v);
			if (!isset($cache[$cacheKey]))
			{
				// Record this value in the cache
				$cache[$cacheKey] = $v;
			}

			// Replace the entry in $config with a reference to the cached value
			$config[$k] =& $cache[$cacheKey];
		}
		unset($v);
	}

	/**
	* Convert a structure to a (possibly multidimensional) array
	*
	* @param  mixed $value
	* @param  bool  $keepEmpty Whether to keep empty arrays instead of removing them
	* @param  bool  $keepNull  Whether to keep NULL values instead of removing them
	* @return array
	*/
	public static function toArray($value, $keepEmpty = false, $keepNull = false)
	{
		$array = [];

		foreach ($value as $k => $v)
		{
			if ($v instanceof ConfigProvider)
			{
				$v = $v->asConfig();
			}
			elseif ($v instanceof Traversable || is_array($v))
			{
				$v = self::toArray($v, $keepEmpty, $keepNull);
			}
			elseif (is_scalar($v) || is_null($v))
			{
				// Do nothing
			}
			else
			{
				$type = (is_object($v))
				      ? 'an instance of ' . get_class($v)
				      : 'a ' . gettype($v);

				throw new RuntimeException('Cannot convert ' . $type . ' to array');
			}

			if (!isset($v) && !$keepNull)
			{
				// We don't record NULL values
				continue;
			}

			if (!$keepEmpty && $v === [])
			{
				// We don't record empty structures
				continue;
			}

			$array[$k] = $v;
		}

		return $array;
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;

use RuntimeException;

abstract class RegexpBuilder
{
	/**
	* Create a regexp pattern that matches a list of words
	*
	* @param  array  $words   Words to sort (must be UTF-8)
	* @param  array  $options
	* @return string
	*/
	public static function fromList(array $words, array $options = [])
	{
		if (empty($words))
		{
			return '';
		}

		$options += [
			'delimiter'       => '/',
			'caseInsensitive' => false,
			'specialChars'    => [],
			'useLookahead'    => false
		];

		// Normalize ASCII if the regexp is meant to be case-insensitive
		if ($options['caseInsensitive'])
		{
			foreach ($words as &$word)
			{
				$word = strtr(
					$word,
					'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
					'abcdefghijklmnopqrstuvwxyz'
				);
			}
			unset($word);
		}

		// Deduplicate words in advance because some routines such as mergeChains() make assumptions
		// based on the size of some chains without deduplicating them first
		$words = array_unique($words);

		// Sort the words in order to produce the same regexp regardless of the words' order
		sort($words);

		// Used to store the first character of each word so that we can generate the lookahead
		// assertion
		$initials = [];

		// Used to store the escaped representation of each character, e.g. "a"=>"a", "."=>"\\."
		// Also used to give a special meaning to some characters, e.g. "*" => ".*?"
		$esc  = $options['specialChars'];
		$esc += [$options['delimiter'] => '\\' . $options['delimiter']];

		// preg_quote() errs on the safe side when escaping characters that could have a special
		// meaning in some situations. Since we're building the regexp in a controlled environment,
		// we don't have to escape those characters.
		$esc += [
			'!' => '!',
			'-' => '-',
			':' => ':',
			'<' => '<',
			'=' => '=',
			'>' => '>',
			'}' => '}'
		];

		// List of words, split by character
		$splitWords = [];

		foreach ($words as $word)
		{
			if (preg_match_all('#.#us', $word, $matches) === false)
			{
				throw new RuntimeException("Invalid UTF-8 string '" . $word . "'");
			}

			$splitWord = [];
			foreach ($matches[0] as $pos => $c)
			{
				if (!isset($esc[$c]))
				{
					$esc[$c] = preg_quote($c);
				}

				if ($pos === 0)
				{
					// Store the initial for later
					$initials[] = $esc[$c];
				}

				$splitWord[] = $esc[$c];
			}

			$splitWords[] = $splitWord;
		}

		$regexp = self::assemble([self::mergeChains($splitWords)]);

		if ($options['useLookahead']
		 && count($initials) > 1
		 && $regexp[0] !== '[')
		{
			$useLookahead = true;

			foreach ($initials as $initial)
			{
				if (!self::canBeUsedInCharacterClass($initial))
				{
					$useLookahead = false;
					break;
				}
			}

			if ($useLookahead)
			{
				$regexp = '(?=' . self::generateCharacterClass($initials) . ')' . $regexp;
			}
		}

		return $regexp;
	}

	/**
	* Merge a 2D array of split words into a 1D array of expressions
	*
	* Each element in the passed array is called a "chain". It starts as an array where each element
	* is a character (a sort of UTF-8 aware str_split()) but successive iterations replace
	* individual characters with an equivalent expression.
	*
	* How it works:
	*
	* 1. Remove the longest prefix shared by all the chains
	* 2. Remove the longest suffix shared by all the chains
	* 3. Group each chain by their first element, e.g. all the chains that start with "a" (or in
	*    some cases, "[xy]") are grouped together
	* 4. If no group has more than 1 chain, we assemble them in a regexp, such as (aa|bb). If any
	*    group has more than 1 chain, for each group we merge the chains from that group together so
	*    that no group has more than 1 chain. When we're done, we remerge all the chains together.
	*
	* @param  array $chains
	* @return array
	*/
	protected static function mergeChains(array $chains)
	{
		// If there's only one chain, there's nothing to merge
		if (!isset($chains[1]))
		{
			return $chains[0];
		}

		// The merged chain starts with the chains' common prefix
		$mergedChain = self::removeLongestCommonPrefix($chains);

		if (!isset($chains[0][0])
		 && !array_filter($chains))
		{
			// The chains are empty, either they were already empty or they were identical and their
			// content was removed as their prefix. Nothing left to merge
			return $mergedChain;
		}

		// Remove the longest common suffix and save it for later
		$suffix = self::removeLongestCommonSuffix($chains);

		// Optimize the joker thing
		if (isset($chains[1]))
		{
			self::optimizeDotChains($chains);
			self::optimizeCatchallChains($chains);
		}

		// Whether one of the chain has been completely optimized away by prefix/suffix removal.
		// Signals that the middle part of the regexp is optional, e.g. (prefix)(foo)?(suffix)
		$endOfChain = false;

		// Whether these chains need to be remerged
		$remerge = false;

		// Here we group chains by their first atom (head of chain)
		$groups = [];
		foreach ($chains as $chain)
		{
			if (!isset($chain[0]))
			{
				$endOfChain = true;
				continue;
			}

			$head = $chain[0];

			if (isset($groups[$head]))
			{
				// More than one chain in a group means that we need to remerge
				$remerge = true;
			}

			$groups[$head][] = $chain;
		}

		// See if we can replace single characters with a character class
		$characterClass = [];
		foreach ($groups as $head => $groupChains)
		{
			$head = (string) $head;

			if ($groupChains === [[$head]]
			 && self::canBeUsedInCharacterClass($head))
			{
				// The whole chain is composed of exactly one token, a token that can be used in a
				// character class
				$characterClass[$head] = $head;
			}
		}

		// Sort the characters and reset their keys
		sort($characterClass);

		// Test whether there is more than 1 character in the character class
		if (isset($characterClass[1]))
		{
			// Remove each of those characters from the groups
			foreach ($characterClass as $char)
			{
				unset($groups[$char]);
			}

			// Create a new group for this character class
			$head = self::generateCharacterClass($characterClass);
			$groups[$head][] = [$head];

			// Ensure that the character class is first in the alternation. Not only it looks nice
			// and might be more performant, it's also how assemble() does it, so normalizing it
			// might help with generating identical regexps (or subpatterns that would then be
			// optimized away as a prefix/suffix)
			$groups = [$head => $groups[$head]]
			        + $groups;
		}

		if ($remerge)
		{
			// Merge all chains sharing the same head together
			$mergedChains = [];
			foreach ($groups as $head => $groupChains)
			{
				$mergedChains[] = self::mergeChains($groupChains);
			}

			// Merge the tails of all chains if applicable. Helps with [ab][xy] (two chains with
			// identical tails)
			self::mergeTails($mergedChains);

			// Now merge all chains together and append it to our merged chain
			$regexp = implode('', self::mergeChains($mergedChains));

			if ($endOfChain)
			{
				$regexp = self::makeRegexpOptional($regexp);
			}

			$mergedChain[] = $regexp;
		}
		else
		{
			self::mergeTails($chains);
			$mergedChain[] = self::assemble($chains);
		}

		// Add the common suffix
		foreach ($suffix as $atom)
		{
			$mergedChain[] = $atom;
		}

		return $mergedChain;
	}

	/**
	* Merge the tails of an array of chains wherever applicable
	*
	* This method optimizes (a[xy]|b[xy]|c) into ([ab][xy]|c). The expression [xy] is not a suffix
	* to every branch of the alternation (common suffix), so it's not automatically removed. What we
	* do here is group chains by their last element (their tail) and then try to merge them together
	* group by group. This method should only be called AFTER chains have been group-merged by head.
	*
	* @param array &$chains
	*/
	protected static function mergeTails(array &$chains)
	{
		// (a[xy]|b[xy]|c) => ([ab][xy]|c)
		self::mergeTailsCC($chains);

		// (axx|ayy|bbxx|bbyy|c) => ((a|bb)(xx|yy)|c)
		self::mergeTailsAltern($chains);

		// Don't forget to reset the keys
		$chains = array_values($chains);
	}

	/**
	* Merge the tails of an array of chains if their head can become a character class
	*
	* @param array &$chains
	*/
	protected static function mergeTailsCC(array &$chains)
	{
		$groups = [];

		foreach ($chains as $k => $chain)
		{
			if (isset($chain[1])
			 && !isset($chain[2])
			 && self::canBeUsedInCharacterClass($chain[0]))
			{
				$groups[$chain[1]][$k] = $chain;
			}
		}

		foreach ($groups as $groupChains)
		{
			if (count($groupChains) < 2)
			{
				// Only 1 element, skip this group
				continue;
			}

			// Remove this group's chains from the original list
			$chains = array_diff_key($chains, $groupChains);

			// Merge this group's chains and add the result to the list
			$chains[] = self::mergeChains(array_values($groupChains));
		}
	}

	/**
	* Merge the tails of an array of chains if it makes the end result shorter
	*
	* This kind of merging used to be specifically avoided due to performance concerns but some
	* light benchmarking showed that there isn't any measurable difference in performance between
	*   (?:c|a(?:xx|yy)|bb(?:xx|yy))
	* and
	*   (?:c|(?:a|bb)(?:xx|yy))
	*
	* @param array &$chains
	*/
	protected static function mergeTailsAltern(array &$chains)
	{
		$groups = [];
		foreach ($chains as $k => $chain)
		{
			if (!empty($chain))
			{
				$tail = array_slice($chain, -1);
				$groups[$tail[0]][$k] = $chain;
			}
		}

		foreach ($groups as $tail => $groupChains)
		{
			if (count($groupChains) < 2)
			{
				// Only 1 element, skip this group
				continue;
			}

			// Create a single chain for this group
			$mergedChain = self::mergeChains(array_values($groupChains));

			// Test whether the merged chain is shorter than the sum of its components
			$oldLen = 0;
			foreach ($groupChains as $groupChain)
			{
				$oldLen += array_sum(array_map('strlen', $groupChain));
			}

			if ($oldLen <= array_sum(array_map('strlen', $mergedChain)))
			{
				continue;
			}

			// Remove this group's chains from the original list
			$chains = array_diff_key($chains, $groupChains);

			// Merge this group's chains and add the result to the list
			$chains[] = $mergedChain;
		}
	}

	/**
	* Remove the longest common prefix from an array of chains
	*
	* @param  array &$chains
	* @return array          Removed elements
	*/
	protected static function removeLongestCommonPrefix(array &$chains)
	{
		// Length of longest common prefix
		$pLen = 0;

		while (1)
		{
			// $c will be used to store the character we're matching against
			$c = null;

			foreach ($chains as $chain)
			{
				if (!isset($chain[$pLen]))
				{
					// Reached the end of a word
					break 2;
				}

				if (!isset($c))
				{
					$c = $chain[$pLen];
					continue;
				}

				if ($chain[$pLen] !== $c)
				{
					// Does not match -- don't increment sLen and break out of the loop
					break 2;
				}
			}

			// We have confirmed that all the words share a same prefix of at least ($pLen + 1)
			++$pLen;
		}

		if (!$pLen)
		{
			return [];
		}

		// Store prefix
		$prefix = array_slice($chains[0], 0, $pLen);

		// Remove prefix from each word
		foreach ($chains as &$chain)
		{
			$chain = array_slice($chain, $pLen);
		}
		unset($chain);

		return $prefix;
	}

	/**
	* Remove the longest common suffix from an array of chains
	*
	* NOTE: this method is meant to be called after removeLongestCommonPrefix(). If it's not, then
	*       the longest match return may be off by 1.
	*
	* @param  array &$chains
	* @return array          Removed elements
	*/
	protected static function removeLongestCommonSuffix(array &$chains)
	{
		// Cache the length of every word
		$chainsLen = array_map('count', $chains);

		// Length of the longest possible suffix
		$maxLen = min($chainsLen);

		// If all the words are the same length, the longest suffix is 1 less than the length of the
		// words because we've already extracted the longest prefix
		if (max($chainsLen) === $maxLen)
		{
			--$maxLen;
		}

		// Length of longest common suffix
		$sLen = 0;

		// Try to find the longest common suffix
		while ($sLen < $maxLen)
		{
			// $c will be used to store the character we're matching against
			$c = null;

			foreach ($chains as $k => $chain)
			{
				$pos = $chainsLen[$k] - ($sLen + 1);

				if (!isset($c))
				{
					$c = $chain[$pos];
					continue;
				}

				if ($chain[$pos] !== $c)
				{
					// Does not match -- don't increment sLen and break out of the loop
					break 2;
				}
			}

			// We have confirmed that all the words share a same suffix of at least ($sLen + 1)
			++$sLen;
		}

		if (!$sLen)
		{
			return [];
		}

		// Store suffix
		$suffix = array_slice($chains[0], -$sLen);

		// Remove suffix from each word
		foreach ($chains as &$chain)
		{
			$chain = array_slice($chain, 0, -$sLen);
		}
		unset($chain);

		return $suffix;
	}

	/**
	* Assemble an array of chains into one expression
	*
	* @param  array  $chains
	* @return string
	*/
	protected static function assemble(array $chains)
	{
		$endOfChain = false;

		$regexps        = [];
		$characterClass = [];

		foreach ($chains as $chain)
		{
			if (empty($chain))
			{
				$endOfChain = true;
				continue;
			}

			if (!isset($chain[1])
			 && self::canBeUsedInCharacterClass($chain[0]))
			{
				$characterClass[$chain[0]] = $chain[0];
			}
			else
			{
				$regexps[] = implode('', $chain);
			}
		}

		if (!empty($characterClass))
		{
			// Sort the characters and reset their keys
			sort($characterClass);

			// Use a character class if there are more than 1 characters in it
			$regexp = (isset($characterClass[1]))
					? self::generateCharacterClass($characterClass)
					: $characterClass[0];

			// Prepend the character class to the list of regexps
			array_unshift($regexps, $regexp);
		}

		if (empty($regexps))
		{
			return '';
		}

		if (isset($regexps[1]))
		{
			// There are several branches, coalesce them
			$regexp = implode('|', $regexps);

			// Put the expression in a subpattern
			$regexp = ((self::canUseAtomicGrouping($regexp)) ? '(?>' : '(?:') . $regexp . ')';
		}
		else
		{
			$regexp = $regexps[0];
		}

		// If we've reached the end of a chain, it means that the branches are optional
		if ($endOfChain)
		{
			$regexp = self::makeRegexpOptional($regexp);
		}

		return $regexp;
	}

	/**
	* Make an entire regexp optional through the use of the ? quantifier
	*
	* @param  string $regexp
	* @return string
	*/
	protected static function makeRegexpOptional($regexp)
	{
		// .+ and .+? become .* and .*?
		if (preg_match('#^\\.\\+\\??$#', $regexp))
		{
			return str_replace('+', '*', $regexp);
		}

		// Special case: xx? becomes x?x?, \w\w? becomes \w?\w?
		// It covers only the most common case of repetition, it's not a panacea
		if (preg_match('#^(\\\\?.)((?:\\1\\?)+)$#Du', $regexp, $m))
		{
			return $m[1] . '?' . $m[2];
		}

		// Optional assertions are a no-op
		if (preg_match('#^(?:[$^]|\\\\[bBAZzGQEK])$#', $regexp))
		{
			return '';
		}

		// One single character, optionally escaped
		if (preg_match('#^\\\\?.$#Dus', $regexp))
		{
			$isAtomic = true;
		}
		// At least two characters, but it's not a subpattern or a character class
		elseif (preg_match('#^[^[(].#s', $regexp))
		{
			$isAtomic = false;
		}
		else
		{
			$def    = RegexpParser::parse('#' . $regexp . '#');
			$tokens = $def['tokens'];

			switch (count($tokens))
			{
				// One character class
				case 1:
					$startPos = $tokens[0]['pos'];
					$len      = $tokens[0]['len'];

					$isAtomic = (bool) ($startPos === 0 && $len === strlen($regexp));

					// If the regexp is [..]+ it becomes [..]* (to which a ? will be appended)
					if ($isAtomic && $tokens[0]['type'] === 'characterClass')
					{
						$regexp = rtrim($regexp, '+*?');

						if (!empty($tokens[0]['quantifiers']) && $tokens[0]['quantifiers'] !== '?')
						{
							$regexp .= '*';
						}
					}
					break;

				// One subpattern covering the entire regexp
				case 2:
					if ($tokens[0]['type'] === 'nonCapturingSubpatternStart'
					 && $tokens[1]['type'] === 'nonCapturingSubpatternEnd')
					{
						$startPos = $tokens[0]['pos'];
						$len      = $tokens[1]['pos'] + $tokens[1]['len'];

						$isAtomic = (bool) ($startPos === 0 && $len === strlen($regexp));

						// If the tokens are not a non-capturing subpattern, we let it fall through
						break;
					}
					// no break; here

				default:
					$isAtomic = false;
			}
		}

		if (!$isAtomic)
		{
			$regexp = ((self::canUseAtomicGrouping($regexp)) ? '(?>' : '(?:') . $regexp . ')';
		}

		$regexp .= '?';

		return $regexp;
	}

	/**
	* Generate a character class from an array of characters
	*
	* @param  array  $chars
	* @return string
	*/
	protected static function generateCharacterClass(array $chars)
	{
		// Flip for convenience
		$chars = array_flip($chars);

		// Those characters do not need to be escaped inside of a character class.
		$unescape = str_split('$()*+.?[{|^', 1);

		foreach ($unescape as $c)
		{
			if (isset($chars['\\' . $c]))
			{
				unset($chars['\\' . $c]);
				$chars[$c] = 1;
			}
		}

		// Sort characters so that class with the same content produce the same representation
		ksort($chars);

		// "-" should be the first character of the class to avoid ambiguity
		if (isset($chars['-']))
		{
			$chars = ['-' => 1] + $chars;
		}

		// Ensure that ^ is at the end of the class to prevent it from negating the class
		if (isset($chars['^']))
		{
			unset($chars['^']);
			$chars['^'] = 1;
		}

		return '[' . implode('', array_keys($chars)) . ']';
	}

	/**
	* Test whether a given expression (usually one character) can be used in a character class
	*
	* @param  string $char
	* @return bool
	*/
	protected static function canBeUsedInCharacterClass($char)
	{
		/**
		* Encoded non-printable characters and generic character classes are allowed
		* @link http://docs.php.net/manual/en/regexp.reference.escape.php
		*/
		if (preg_match('#^\\\\[aefnrtdDhHsSvVwW]$#D', $char))
		{
			return true;
		}

		// Escaped literals are allowed (escaped sequences excluded)
		if (preg_match('#^\\\\[^A-Za-z0-9]$#Dus', $char))
		{
			return true;
		}

		// More than 1 character => cannot be used in a character class
		if (preg_match('#..#Dus', $char))
		{
			return false;
		}

		// Special characters such as $ or ^ are rejected, but we need to check for characters that
		// get escaped by preg_quote() even though it's not necessary, such as ! or =
		if (preg_quote($char) !== $char
		 && !preg_match('#^[-!:<=>}]$#D', $char))
		{
			return false;
		}

		return true;
	}

	/**
	* Remove chains that overlap with dot chains
	*
	* Will remove chains that are made redundant by the use of the dot metacharacter, e.g.
	* ["a","b","c"] and ["a",".","c"] or ["a","b","c"], ["a","c"] and ["a",".?","c"]
	*
	* @param  array &$chains
	* @return void
	*/
	protected static function optimizeDotChains(array &$chains)
	{
		/**
		* @var array List of valid atoms that should be matched by a dot but happen to be
		*            represented by more than one character
		*/
		$validAtoms = [
			// Escape sequences
			'\\d' => 1, '\\D' => 1, '\\h' => 1, '\\H' => 1,
			'\\s' => 1, '\\S' => 1, '\\v' => 1, '\\V' => 1,
			'\\w' => 1, '\\W' => 1,

			// Special chars that need to be escaped in order to be used as literals
			'\\^' => 1, '\\$' => 1, '\\.' => 1, '\\?' => 1,
			'\\[' => 1, '\\]' => 1, '\\(' => 1, '\\)' => 1,
			'\\+' => 1, '\\*' => 1, '\\\\' => 1
		];

		// First we replace chains such as ["a",".?","b"] with ["a",".","b"] and ["a","b"]
		do
		{
			$hasMoreDots = false;
			foreach ($chains as $k1 => $dotChain)
			{
				$dotKeys = array_keys($dotChain, '.?', true);

				if (!empty($dotKeys))
				{
					// Replace the .? atom in the original chain with a .
					$dotChain[$dotKeys[0]] = '.';
					$chains[$k1] = $dotChain;

					// Create a new chain without the atom
					array_splice($dotChain, $dotKeys[0], 1);
					$chains[] = $dotChain;

					if (isset($dotKeys[1]))
					{
						$hasMoreDots = true;
					}
				}
			}
		}
		while ($hasMoreDots);

		foreach ($chains as $k1 => $dotChain)
		{
			$dotKeys = array_keys($dotChain, '.', true);

			if (empty($dotKeys))
			{
				continue;
			}

			foreach ($chains as $k2 => $tmpChain)
			{
				if ($k2 === $k1)
				{
					continue;
				}

				foreach ($dotKeys as $dotKey)
				{
					if (!isset($tmpChain[$dotKey]))
					{
						// The chain is too short to match, skip this chain
						continue 2;
					}

					// Skip if the dot is neither a literal nor a valid atom
					if (!preg_match('#^.$#Du', preg_quote($tmpChain[$dotKey]))
					 && !isset($validAtoms[$tmpChain[$dotKey]]))
					{
						continue 2;
					}

					// Replace the atom with a dot
					$tmpChain[$dotKey] = '.';
				}

				if ($tmpChain === $dotChain)
				{
					// The chain matches our dot chain, which means we can remove it
					unset($chains[$k2]);
				}
			}
		}
	}

	/**
	* Remove chains that overlap with chains that contain a catchall expression such as .*
	*
	* NOTE: cannot handle possessive expressions such as .++ because we don't know whether that
	*       chain had its suffix/tail stashed by an earlier iteration
	*
	* @param  array &$chains
	* @return void
	*/
	protected static function optimizeCatchallChains(array &$chains)
	{
		// This is how catchall expressions will trump each other in our routine. For instance,
		// instead of (?:.*|.+) we will emit (?:.*). Zero-or-more trumps one-or-more and greedy
		// trumps non-greedy. In some cases, (?:.+|.*?) might be preferable to (?:.*?) but it does
		// not seem like a common enough case to warrant the extra logic
		$precedence = [
			'.*'  => 3,
			'.*?' => 2,
			'.+'  => 1,
			'.+?' => 0
		];

		$tails = [];

		foreach ($chains as $k => $chain)
		{
			if (!isset($chain[0]))
			{
				continue;
			}

			$head = $chain[0];

			// Test whether the head is a catchall expression by looking up its precedence
			if (!isset($precedence[$head]))
			{
				continue;
			}

			$tail = implode('', array_slice($chain, 1));
			if (!isset($tails[$tail])
			 || $precedence[$head] > $tails[$tail]['precedence'])
			{
				$tails[$tail] = [
					'key'        => $k,
					'precedence' => $precedence[$head]
				];
			}
		}

		$catchallChains = [];
		foreach ($tails as $tail => $info)
		{
			$catchallChains[$info['key']] = $chains[$info['key']];
		}

		foreach ($catchallChains as $k1 => $catchallChain)
		{
			$headExpr = $catchallChain[0];
			$tailExpr = false;
			$match    = array_slice($catchallChain, 1);

			// Test whether the catchall chain has another catchall expression at the end
			if (isset($catchallChain[1])
			 && isset($precedence[end($catchallChain)]))
			{
				// Remove the catchall expression from the end
				$tailExpr = array_pop($match);
			}

			$matchCnt = count($match);

			foreach ($chains as $k2 => $chain)
			{
				if ($k2 === $k1)
				{
					continue;
				}

				/**
				* @var integer Offset of the first atom we're trying to match the tail against
				*/
				$start = 0;

				/**
				* @var integer
				*/
				$end = count($chain);

				// If the catchall at the start of the chain must match at least one character, we
				// ensure the chain has at least one character at its beginning
				if ($headExpr[1] === '+')
				{
					$found = false;

					foreach ($chain as $start => $atom)
					{
						if (self::matchesAtLeastOneCharacter($atom))
						{
							$found = true;
							break;
						}
					}

					if (!$found)
					{
						continue;
					}
				}

				// Test whether the catchall chain has another catchall expression at the end
				if ($tailExpr === false)
				{
					$end = $start;
				}
				else
				{
					// If the expression must match at least one character, we ensure that the
					// chain satisfy the requirement and we adjust $end accordingly so the same atom
					// isn't used twice (e.g. used by two consecutive .+ expressions)
					if ($tailExpr[1] === '+')
					{
						$found = false;

						while (--$end > $start)
						{
							if (self::matchesAtLeastOneCharacter($chain[$end]))
							{
								$found = true;
								break;
							}
						}

						if (!$found)
						{
							continue;
						}
					}

					// Now, $start should point to the first atom we're trying to match the catchall
					// chain against, and $end should be equal to the index right after the last
					// atom we can match against. We adjust $end to point to the last position our
					// match can start at
					$end -= $matchCnt;
				}

				while ($start <= $end)
				{
					if (array_slice($chain, $start, $matchCnt) === $match)
					{
						unset($chains[$k2]);
						break;
					}

					++$start;
				}
			}
		}
	}

	/**
	* Test whether a given expression can never match an empty space
	*
	* Only basic checks are performed and it only returns true if we're certain the expression
	* will always match/consume at least one character. For instance, it doesn't properly recognize
	* the expression [ab]+ as matching at least one character.
	*
	* @param  string $expr
	* @return bool
	*/
	protected static function matchesAtLeastOneCharacter($expr)
	{
		if (preg_match('#^[$*?^]$#', $expr))
		{
			return false;
		}

		// A single character should be fine
		if (preg_match('#^.$#u', $expr))
		{
			return true;
		}

		// Matches anything that starts with ".+", "a+", etc...
		if (preg_match('#^.\\+#u', $expr))
		{
			return true;
		}

		// Matches anything that starts with "\d", "\+", "\d+", etc... We avoid matching back
		// references as we can't be sure they matched at least one character themselves
		if (preg_match('#^\\\\[^bBAZzGQEK1-9](?![*?])#', $expr))
		{
			return true;
		}

		// Anything else is either too complicated and too circumstancial to investigate further so
		// we'll just return false
		return false;
	}

	/**
	* Test whether given expression can be safely used with atomic grouping
	*
	* @param  string $expr
	* @return bool
	*/
	protected static function canUseAtomicGrouping($expr)
	{
		if (preg_match('#(?<!\\\\)(?>\\\\\\\\)*\\.#', $expr))
		{
			// An unescaped dot should disable atomic grouping. Technically, we could still allow it
			// depending on what comes next in the regexp but it's a lot of work for something very
			// situational
			return false;
		}

		if (preg_match('#(?<!\\\\)(?>\\\\\\\\)*[+*]#', $expr))
		{
			// A quantifier disables atomic grouping. Again, this could be enabled depending on the
			// context
			return false;
		}

		if (preg_match('#(?<!\\\\)(?>\\\\\\\\)*\\(?(?<!\\()\\?#', $expr))
		{
			// An unescaped ? is a quantifier, unless it's preceded by an unescaped (
			return false;
		}

		if (preg_match('#(?<!\\\\)(?>\\\\\\\\)*\\\\[a-z0-9]#', $expr))
		{
			// Escape sequences disable atomic grouping because they might overlap with another
			// branch
			return false;
		}

		// The regexp looks harmless enough to enable atomic grouping
		return true;
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;

use s9e\TextFormatter\Configurator\Collections\Ruleset;
use s9e\TextFormatter\Configurator\Collections\TagCollection;

abstract class RulesHelper
{
	/**
	* Generate the allowedChildren and allowedDescendants bitfields for every tag and for the root context
	*
	* @param  TagCollection $tags
	* @param  Ruleset       $rootRules
	* @return array
	*/
	public static function getBitfields(TagCollection $tags, Ruleset $rootRules)
	{
		$rules = ['*root*' => iterator_to_array($rootRules)];
		foreach ($tags as $tagName => $tag)
		{
			$rules[$tagName] = iterator_to_array($tag->rules);
		}

		// Create a matrix that contains all of the tags and whether every other tag is allowed as
		// a child and as a descendant
		$matrix = self::unrollRules($rules);

		// Remove unusable tags from the matrix
		self::pruneMatrix($matrix);

		// Group together tags are allowed in the exact same contexts
		$groupedTags = [];
		foreach (array_keys($matrix) as $tagName)
		{
			if ($tagName === '*root*')
			{
				continue;
			}

			$k = '';
			foreach ($matrix as $tagMatrix)
			{
				$k .= $tagMatrix['allowedChildren'][$tagName];
				$k .= $tagMatrix['allowedDescendants'][$tagName];
			}

			$groupedTags[$k][] = $tagName;
		}

		// Record the bit number of each tag, and the name of a tag for each bit
		$bitTag     = [];
		$bitNumber  = 0;
		$tagsConfig = [];
		foreach ($groupedTags as $tagNames)
		{
			foreach ($tagNames as $tagName)
			{
				$tagsConfig[$tagName]['bitNumber'] = $bitNumber;
				$bitTag[$bitNumber] = $tagName;
			}

			++$bitNumber;
		}

		// Build the bitfields of each tag, including the *root* pseudo-tag
		foreach ($matrix as $tagName => $tagMatrix)
		{
			$allowedChildren    = '';
			$allowedDescendants = '';
			foreach ($bitTag as $targetName)
			{
				$allowedChildren    .= $tagMatrix['allowedChildren'][$targetName];
				$allowedDescendants .= $tagMatrix['allowedDescendants'][$targetName];
			}

			$tagsConfig[$tagName]['allowed'] = self::pack($allowedChildren, $allowedDescendants);
		}

		// Prepare the return value
		$return = [
			'root' => $tagsConfig['*root*'],
			'tags' => $tagsConfig
		];
		unset($return['tags']['*root*']);

		return $return;
	}

	/**
	* Initialize a matrix of settings
	*
	* @param  array $rules Rules for each tag
	* @return array        Multidimensional array of [tagName => [scope => [targetName => setting]]]
	*/
	protected static function initMatrix(array $rules)
	{
		$matrix   = [];
		$tagNames = array_keys($rules);

		foreach ($rules as $tagName => $tagRules)
		{
			if ($tagRules['defaultDescendantRule'] === 'allow')
			{
				$childValue      = (int) ($tagRules['defaultChildRule'] === 'allow');
				$descendantValue = 1;
			}
			else
			{
				$childValue      = 0;
				$descendantValue = 0;
			}

			$matrix[$tagName]['allowedChildren']    = array_fill_keys($tagNames, $childValue);
			$matrix[$tagName]['allowedDescendants'] = array_fill_keys($tagNames, $descendantValue);
		}

		return $matrix;
	}

	/**
	* Apply given rule from each applicable tag
	*
	* For each tag, if the rule has any target we set the corresponding value for each target in the
	* matrix
	*
	* @param  array  &$matrix   Settings matrix
	* @param  array   $rules    Rules for each tag
	* @param  string  $ruleName Rule name
	* @param  string  $key      Key in the matrix
	* @param  integer $value    Value to be set
	* @return void
	*/
	protected static function applyTargetedRule(array &$matrix, $rules, $ruleName, $key, $value)
	{
		foreach ($rules as $tagName => $tagRules)
		{
			if (!isset($tagRules[$ruleName]))
			{
				continue;
			}

			foreach ($tagRules[$ruleName] as $targetName)
			{
				$matrix[$tagName][$key][$targetName] = $value;
			}
		}
	}

	/**
	* @param  array $rules
	* @return array
	*/
	protected static function unrollRules(array $rules)
	{
		// Initialize the matrix with default values
		$matrix = self::initMatrix($rules);

		// Convert ignoreTags and requireParent to denyDescendant and denyChild rules
		$tagNames = array_keys($rules);
		foreach ($rules as $tagName => $tagRules)
		{
			if (!empty($tagRules['ignoreTags']))
			{
				$rules[$tagName]['denyDescendant'] = $tagNames;
			}

			if (!empty($tagRules['requireParent']))
			{
				$denyParents = array_diff($tagNames, $tagRules['requireParent']);
				foreach ($denyParents as $parentName)
				{
					$rules[$parentName]['denyChild'][] = $tagName;
				}
			}
		}

		// Apply "allow" rules to grant usage, overwriting the default settings
		self::applyTargetedRule($matrix, $rules, 'allowChild',      'allowedChildren',    1);
		self::applyTargetedRule($matrix, $rules, 'allowDescendant', 'allowedChildren',    1);
		self::applyTargetedRule($matrix, $rules, 'allowDescendant', 'allowedDescendants', 1);

		// Apply "deny" rules to remove usage
		self::applyTargetedRule($matrix, $rules, 'denyChild',      'allowedChildren',    0);
		self::applyTargetedRule($matrix, $rules, 'denyDescendant', 'allowedChildren',    0);
		self::applyTargetedRule($matrix, $rules, 'denyDescendant', 'allowedDescendants', 0);

		return $matrix;
	}

	/**
	* Remove unusable tags from the matrix
	*
	* @param  array &$matrix
	* @return void
	*/
	protected static function pruneMatrix(array &$matrix)
	{
		$usableTags = ['*root*' => 1];

		// Start from the root and keep digging
		$parentTags = $usableTags;
		do
		{
			$nextTags = [];
			foreach (array_keys($parentTags) as $tagName)
			{
				// Accumulate the names of tags that are allowed as children of our parent tags
				$nextTags += array_filter($matrix[$tagName]['allowedChildren']);
			}

			// Keep only the tags that are in the matrix but aren't in the usable array yet, then
			// add them to the array
			$parentTags  = array_diff_key($nextTags, $usableTags);
			$parentTags  = array_intersect_key($parentTags, $matrix);
			$usableTags += $parentTags;
		}
		while (!empty($parentTags));

		// Remove unusable tags from the matrix
		$matrix = array_intersect_key($matrix, $usableTags);
		unset($usableTags['*root*']);

		// Remove unusable tags from the targets
		foreach ($matrix as $tagName => &$tagMatrix)
		{
			$tagMatrix['allowedChildren']
				= array_intersect_key($tagMatrix['allowedChildren'], $usableTags);

			$tagMatrix['allowedDescendants']
				= array_intersect_key($tagMatrix['allowedDescendants'], $usableTags);
		}
		unset($tagMatrix);
	}

	/**
	* Convert a binary representation such as "101011" to an array of integer
	*
	* Each bitfield is split in groups of 8 bits, then converted to a 16-bit integer where the
	* allowedChildren bitfield occupies the least significant bits and the allowedDescendants
	* bitfield occupies the most significant bits
	*
	* @param  string    $allowedChildren
	* @param  string    $allowedDescendants
	* @return integer[]
	*/
	protected static function pack($allowedChildren, $allowedDescendants)
	{
		$allowedChildren    = str_split($allowedChildren,    8);
		$allowedDescendants = str_split($allowedDescendants, 8);

		$allowed = [];
		foreach (array_keys($allowedChildren) as $k)
		{
			$allowed[] = bindec(sprintf(
				'%1$08s%2$08s',
				strrev($allowedDescendants[$k]),
				strrev($allowedChildren[$k])
			));
		}

		return $allowed;
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
* This class helps the RulesGenerator by analyzing a given template in order to answer questions
* such as "can this tag be a child/descendant of that other tag?" and others related to the HTML5
* content model.
*
* We use the HTML5 specs to determine which children or descendants should be allowed or denied
* based on HTML5 content models. While it does not exactly match HTML5 content models, it gets
* pretty close. We also use HTML5 "optional end tag" rules to create closeParent rules.
*
* Currently, this method does not evaluate elements created with <xsl:element> correctly, or
* attributes created with <xsl:attribute> and may never will due to the increased complexity it
* would entail. Additionally, it does not evaluate the scope of <xsl:apply-templates/>. For
* instance, it will treat <xsl:apply-templates select="LI"/> as if it was <xsl:apply-templates/>
*
* @link http://dev.w3.org/html5/spec/content-models.html#content-models
* @link http://dev.w3.org/html5/spec/syntax.html#optional-tags
* @see  /scripts/patchTemplateForensics.php
*/
class TemplateForensics
{
	/**
	* @var string allowChild bitfield (all branches)
	*/
	protected $allowChildBitfield = "\0";

	/**
	* @var bool Whether elements are allowed as children
	*/
	protected $allowsChildElements = true;

	/**
	* @var bool Whether text nodes are allowed as children
	*/
	protected $allowsText = true;

	/**
	* @var string OR-ed bitfield representing all of the categories used by this template
	*/
	protected $contentBitfield = "\0";

	/**
	* @var string denyDescendant bitfield
	*/
	protected $denyDescendantBitfield = "\0";

	/**
	* @var DOMDocument Document containing the template
	*/
	protected $dom;

	/**
	* @var bool Whether this template contains any HTML elements
	*/
	protected $hasElements = false;

	/**
	* @var bool Whether this template renders non-whitespace text nodes at its root
	*/
	protected $hasRootText = false;

	/**
	* @var bool Whether this template should be considered a block-level element
	*/
	protected $isBlock = false;

	/**
	* @var bool Whether the template uses the "empty" content model
	*/
	protected $isEmpty = true;

	/**
	* @var bool Whether this template adds to the list of active formatting elements
	*/
	protected $isFormattingElement = false;

	/**
	* @var bool Whether this template lets content through via an xsl:apply-templates element
	*/
	protected $isPassthrough = false;

	/**
	* @var bool Whether all branches use the transparent content model
	*/
	protected $isTransparent = false;

	/**
	* @var bool Whether all branches have an ancestor that is a void element
	*/
	protected $isVoid = true;

	/**
	* @var array Names of every last HTML element that precedes an <xsl:apply-templates/> node
	*/
	protected $leafNodes = [];

	/**
	* @var bool Whether any branch has an element that preserves new lines by default (e.g. <pre>)
	*/
	protected $preservesNewLines = false;

	/**
	* @var array Bitfield of the first HTML element of every branch
	*/
	protected $rootBitfields = [];

	/**
	* @var array Names of every HTML element that have no HTML parent
	*/
	protected $rootNodes = [];

	/**
	* @var DOMXPath XPath engine associated with $this->dom
	*/
	protected $xpath;

	/**
	* Constructor
	*
	* @param  string $template Template content
	* @return void
	*/
	public function __construct($template)
	{
		$this->dom   = TemplateHelper::loadTemplate($template);
		$this->xpath = new DOMXPath($this->dom);

		$this->analyseRootNodes();
		$this->analyseBranches();
		$this->analyseContent();
	}

	/**
	* Return whether this template allows a given child
	*
	* @param  self $child
	* @return bool
	*/
	public function allowsChild(self $child)
	{
		// Sometimes, a template can technically be allowed as a child but denied as a descendant
		if (!$this->allowsDescendant($child))
		{
			return false;
		}

		foreach ($child->rootBitfields as $rootBitfield)
		{
			if (!self::match($rootBitfield, $this->allowChildBitfield))
			{
				return false;
			}
		}

		if (!$this->allowsText && $child->hasRootText)
		{
			return false;
		}

		return true;
	}

	/**
	* Return whether this template allows a given descendant
	*
	* @param  self $descendant
	* @return bool
	*/
	public function allowsDescendant(self $descendant)
	{
		// Test whether the descendant is explicitly disallowed
		if (self::match($descendant->contentBitfield, $this->denyDescendantBitfield))
		{
			return false;
		}

		// Test whether the descendant contains any elements and we disallow elements
		if (!$this->allowsChildElements && $descendant->hasElements)
		{
			return false;
		}

		return true;
	}

	/**
	* Return whether this template allows elements as children
	*
	* @return bool
	*/
	public function allowsChildElements()
	{
		return $this->allowsChildElements;
	}

	/**
	* Return whether this template allows text nodes as children
	*
	* @return bool
	*/
	public function allowsText()
	{
		return $this->allowsText;
	}

	/**
	* Return whether this template automatically closes given parent template
	*
	* @param  self $parent
	* @return bool
	*/
	public function closesParent(self $parent)
	{
		foreach ($this->rootNodes as $rootName)
		{
			if (empty(self::$htmlElements[$rootName]['cp']))
			{
				continue;
			}

			foreach ($parent->leafNodes as $leafName)
			{
				if (in_array($leafName, self::$htmlElements[$rootName]['cp'], true))
				{
					// If any of this template's root node closes one of the parent's leaf node, we
					// consider that this template closes the other one
					return true;
				}
			}
		}

		return false;
	}

	/**
	* Return the source template as a DOMDocument
	*
	* NOTE: the document should not be modified
	*
	* @return DOMDocument
	*/
	public function getDOM()
	{
		return $this->dom;
	}

	/**
	* Return whether this template should be considered a block-level element
	*
	* @return bool
	*/
	public function isBlock()
	{
		return $this->isBlock;
	}

	/**
	* Return whether this template adds to the list of active formatting elements
	*
	* @return bool
	*/
	public function isFormattingElement()
	{
		return $this->isFormattingElement;
	}

	/**
	* Return whether this template uses the "empty" content model
	*
	* @return bool
	*/
	public function isEmpty()
	{
		return $this->isEmpty;
	}

	/**
	* Return whether this template lets content through via an xsl:apply-templates element
	*
	* @return bool
	*/
	public function isPassthrough()
	{
		return $this->isPassthrough;
	}

	/**
	* Return whether this template uses the "transparent" content model
	*
	* @return bool
	*/
	public function isTransparent()
	{
		return $this->isTransparent;
	}

	/**
	* Return whether all branches have an ancestor that is a void element
	*
	* @return bool
	*/
	public function isVoid()
	{
		return $this->isVoid;
	}

	/**
	* Return whether this template preserves the whitespace in its descendants
	*
	* @return bool
	*/
	public function preservesNewLines()
	{
		return $this->preservesNewLines;
	}

	/**
	* Analyses the content of the whole template and set $this->contentBitfield accordingly
	*/
	protected function analyseContent()
	{
		// Get all non-XSL elements
		$query = '//*[namespace-uri() != "http://www.w3.org/1999/XSL/Transform"]';

		foreach ($this->xpath->query($query) as $node)
		{
			$this->contentBitfield |= $this->getBitfield($node->localName, 'c', $node);
			$this->hasElements = true;
		}

		// Test whether this template is passthrough
		$this->isPassthrough = (bool) $this->xpath->evaluate('count(//xsl:apply-templates)');
	}

	/**
	* Records the HTML elements (and their bitfield) rendered at the root of the template
	*/
	protected function analyseRootNodes()
	{
		// Get every non-XSL element with no non-XSL ancestor. This should return us the first
		// HTML element of every branch
		$query = '//*[namespace-uri() != "http://www.w3.org/1999/XSL/Transform"]'
		       . '[not(ancestor::*[namespace-uri() != "http://www.w3.org/1999/XSL/Transform"])]';

		foreach ($this->xpath->query($query) as $node)
		{
			$elName = $node->localName;

			// Save the actual name of the root node
			$this->rootNodes[] = $elName;

			if (!isset(self::$htmlElements[$elName]))
			{
				// Unknown elements are treated as if they were a <span> element
				$elName = 'span';
			}

			// If any root node is a block-level element, we'll mark the template as such
			if ($this->hasProperty($elName, 'b', $node))
			{
				$this->isBlock = true;
			}

			$this->rootBitfields[] = $this->getBitfield($elName, 'c', $node);
		}

		// Test for non-whitespace text nodes at the root. For that we need a predicate that filters
		// out: nodes with a non-XSL ancestor,
		$predicate = '[not(ancestor::*[namespace-uri() != "http://www.w3.org/1999/XSL/Transform"])]';

		// ..and nodes with an <xsl:attribute/>, <xsl:comment/> or <xsl:variable/> ancestor
		$predicate .= '[not(ancestor::xsl:attribute | ancestor::xsl:comment | ancestor::xsl:variable)]';

		$query = '//text()[normalize-space() != ""]' . $predicate
		       . '|'
		       . '//xsl:text[normalize-space() != ""]' . $predicate
		       . '|'
		       . '//xsl:value-of' . $predicate;

		if ($this->evaluate($query, $this->dom->documentElement))
		{
			$this->hasRootText = true;
		}
	}

	/**
	* Analyses each branch that leads to an <xsl:apply-templates/> tag
	*/
	protected function analyseBranches()
	{
		/**
		* @var array allowChild bitfield for each branch
		*/
		$branchBitfields = [];

		/**
		* @var bool Whether this template should be considered a formatting element
		*/
		$isFormattingElement = true;

		// Consider this template transparent unless we find out there are no branches or that one
		// of the branches is not transparent
		$this->isTransparent = true;

		// For each <xsl:apply-templates/> element...
		foreach ($this->getXSLElements('apply-templates') as $applyTemplates)
		{
			// ...we retrieve all non-XSL ancestors
			$nodes = $this->xpath->query(
				'ancestor::*[namespace-uri() != "http://www.w3.org/1999/XSL/Transform"]',
				$applyTemplates
			);

			/**
			* @var bool Whether this branch allows elements
			*/
			$allowsChildElements = true;

			/**
			* @var bool Whether this branch allows text nodes
			*/
			$allowsText = true;

			/**
			* @var string allowChild bitfield for current branch. Starts with the value associated
			*             with <div> in order to approximate a value if the whole branch uses the
			*             transparent content model
			*/
			$branchBitfield = self::$htmlElements['div']['ac'];

			/**
			* @var bool Whether this branch denies all non-text descendants
			*/
			$isEmpty = false;

			/**
			* @var bool Whether this branch contains a void element
			*/
			$isVoid = false;

			/**
			* @var string Name of the last node of this branch
			*/
			$leafNode = null;

			/**
			* @var boolean Whether this branch preserves new lines
			*/
			$preservesNewLines = false;

			foreach ($nodes as $node)
			{
				$elName = $leafNode = $node->localName;

				if (!isset(self::$htmlElements[$elName]))
				{
					// Unknown elements are treated as if they were a <span> element
					$elName = 'span';
				}

				// Test whether the element is void
				if ($this->hasProperty($elName, 'v', $node))
				{
					$isVoid = true;
				}

				// Test whether the element uses the "empty" content model
				if ($this->hasProperty($elName, 'e', $node))
				{
					$isEmpty = true;
				}

				if (!$this->hasProperty($elName, 't', $node))
				{
					// If the element isn't transparent, we reset its bitfield
					$branchBitfield = "\0";

					// Also, it means that the template itself isn't transparent
					$this->isTransparent = false;
				}

				// Test whether this element is a formatting element
				if (!$this->hasProperty($elName, 'fe', $node)
				 && !$this->isFormattingSpan($node))
				{
					$isFormattingElement = false;
				}

				// Test whether this branch allows elements
				$allowsChildElements = !$this->hasProperty($elName, 'to', $node);

				// Test whether this branch allows text nodes
				$allowsText = !$this->hasProperty($elName, 'nt', $node);

				// allowChild rules are cumulative if transparent, and reset above otherwise
				$branchBitfield |= $this->getBitfield($elName, 'ac', $node);

				// denyDescendant rules are cumulative
				$this->denyDescendantBitfield |= $this->getBitfield($elName, 'dd', $node);

				// Test whether this branch preserves whitespace by inspecting the current element
				// and the value of its style attribute. Technically, this block of code also tests
				// this element's descendants' style attributes but the result is the same as we
				// need to check every element of this branch in order
				$style = '';

				if ($this->hasProperty($elName, 'pre', $node))
				{
					$style .= 'white-space:pre;';
				}

				if ($node->hasAttribute('style'))
				{
					$style .= $node->getAttribute('style') . ';';
				}

				$attributes = $this->xpath->query('.//xsl:attribute[@name="style"]', $node);
				foreach ($attributes as $attribute)
				{
					$style .= $attribute->textContent;
				}

				preg_match_all(
					'/white-space\\s*:\\s*(no|pre)/i',
					strtolower($style),
					$matches
				);
				foreach ($matches[1] as $match)
				{
					// TRUE:  "pre", "pre-line" and "pre-wrap"
					// FALSE: "normal", "nowrap"
					$preservesNewLines = ($match === 'pre');
				}
			}

			// Add this branch's bitfield to the list
			$branchBitfields[] = $branchBitfield;

			// Save the name of the last node processed
			if (isset($leafNode))
			{
				$this->leafNodes[] = $leafNode;
			}

			// If any branch disallows elements, the template disallows elements
			if (!$allowsChildElements)
			{
				$this->allowsChildElements = false;
			}

			// If any branch disallows text, the template disallows text
			if (!$allowsText)
			{
				$this->allowsText = false;
			}

			// If any branch is not empty, the template is not empty
			if (!$isEmpty)
			{
				$this->isEmpty = false;
			}

			// If any branch is not void, the template is not void
			if (!$isVoid)
			{
				$this->isVoid = false;
			}

			// If any branch preserves new lines, the template preserves new lines
			if ($preservesNewLines)
			{
				$this->preservesNewLines = true;
			}
		}

		if (empty($branchBitfields))
		{
			// No branches => not transparent
			$this->isTransparent = false;
		}
		else
		{
			// Take the bitfield of each branch and reduce them to a single ANDed bitfield
			$this->allowChildBitfield = $branchBitfields[0];

			foreach ($branchBitfields as $branchBitfield)
			{
				$this->allowChildBitfield &= $branchBitfield;
			}

			// Set the isFormattingElement property to our final value, but only if this template
			// had any branches
			if (!empty($this->leafNodes))
			{
				$this->isFormattingElement = $isFormattingElement;
			}
		}
	}

	/**
	* Evaluate a boolean XPath query
	*
	* @param  string     $query XPath query
	* @param  DOMElement $node  Context node
	* @return boolean
	*/
	protected function evaluate($query, DOMElement $node)
	{
		return $this->xpath->evaluate('boolean(' . $query . ')', $node);
	}

	/**
	* Get all XSL elements of given name
	*
	* @param  string      $elName XSL element's name, e.g. "apply-templates"
	* @return \DOMNodeList
	*/
	protected function getXSLElements($elName)
	{
		return $this->dom->getElementsByTagNameNS('http://www.w3.org/1999/XSL/Transform', $elName);
	}

	/**
	* Test whether given node is a span element used for formatting
	*
	* Will return TRUE if the node is a span element with a class attribute and/or a style attribute
	* and no other attributes
	*
	* @param  DOMElement $node
	* @return boolean
	*/
	protected function isFormattingSpan(DOMElement $node)
	{
		if ($node->nodeName !== 'span')
		{
			return false;
		}

		if ($node->getAttribute('class') === ''
		 && $node->getAttribute('style') === '')
		{
			return false;
		}

		foreach ($node->attributes as $attrName => $attribute)
		{
			if ($attrName !== 'class' && $attrName !== 'style')
			{
				return false;
			}
		}

		return true;
	}

	/**
	* "What is this?" you might ask. This is basically a compressed version of the HTML5 content
	* models and rules, with some liberties taken.
	*
	* For each element, up to three bitfields are defined: "c", "ac" and "dd". Bitfields are stored
	* as raw bytes, formatted using the octal notation to keep the sources ASCII.
	*
	*   "c" represents the categories the element belongs to. The categories are comprised of HTML5
	*   content models (such as "phrasing content" or "interactive content") plus a few special
	*   categories created to cover the parts of the specs that refer to "a group of X and Y
	*   elements" rather than a specific content model.
	*
	*   "ac" represents the categories that are allowed as children of given element.
	*
	*   "dd" represents the categories that must not appear as a descendant of given element.
	*
	* Sometimes, HTML5 specifies some restrictions on when an element can accept certain children,
	* or what categories the element belongs to. For example, an <img> element is only part of the
	* "interactive content" category if it has a "usemap" attribute. Those restrictions are
	* expressed as an XPath expression and stored using the concatenation of the key of the bitfield
	* plus the bit number of the category. For instance, if "interactive content" got assigned to
	* bit 2, the definition of the <img> element will contain a key "c2" with value "@usemap".
	*
	* Additionally, other flags are set:
	*
	*   "t" indicates that the element uses the "transparent" content model.
	*   "e" indicates that the element uses the "empty" content model.
	*   "v" indicates that the element is a void element.
	*   "nt" indicates that the element does not accept text nodes. (no text)
	*   "to" indicates that the element should only contain text. (text-only)
	*   "fe" indicates that the element is a formatting element. It will automatically be reopened
	*   when closed by an end tag of a different name.
	*   "b" indicates that the element is not phrasing content, which makes it likely to act like
	*   a block element.
	*
	* Finally, HTML5 defines "optional end tag" rules, where one element automatically closes its
	* predecessor. Those are used to generate closeParent rules and are stored in the "cp" key.
	*
	* @var array
	* @see /scripts/patchTemplateForensics.php
	*/
	protected static $htmlElements = [
		'a'=>['c'=>"\17",'ac'=>"\0",'dd'=>"\10",'t'=>1,'fe'=>1],
		'abbr'=>['c'=>"\7",'ac'=>"\4"],
		'address'=>['c'=>"\3\10",'ac'=>"\1",'dd'=>"\100\12",'b'=>1,'cp'=>['p']],
		'area'=>['c'=>"\5",'nt'=>1,'e'=>1,'v'=>1],
		'article'=>['c'=>"\3\2",'ac'=>"\1",'b'=>1,'cp'=>['p']],
		'aside'=>['c'=>"\3\2",'ac'=>"\1",'dd'=>"\0\0\0\200",'b'=>1,'cp'=>['p']],
		'audio'=>['c'=>"\57",'c3'=>'@controls','c1'=>'@controls','ac'=>"\0\0\200\4",'ac23'=>'not(@src)','ac26'=>'@src','t'=>1],
		'b'=>['c'=>"\7",'ac'=>"\4",'fe'=>1],
		'base'=>['c'=>"\20",'nt'=>1,'e'=>1,'v'=>1,'b'=>1],
		'bdi'=>['c'=>"\7",'ac'=>"\4"],
		'bdo'=>['c'=>"\7",'ac'=>"\4"],
		'blockquote'=>['c'=>"\3\1",'ac'=>"\1",'b'=>1,'cp'=>['p']],
		'body'=>['c'=>"\0\1\2",'ac'=>"\1",'b'=>1],
		'br'=>['c'=>"\5",'nt'=>1,'e'=>1,'v'=>1],
		'button'=>['c'=>"\17",'ac'=>"\4",'dd'=>"\10"],
		'canvas'=>['c'=>"\47",'ac'=>"\0",'t'=>1],
		'caption'=>['c'=>"\200",'ac'=>"\1",'dd'=>"\0\0\0\10",'b'=>1],
		'cite'=>['c'=>"\7",'ac'=>"\4"],
		'code'=>['c'=>"\7",'ac'=>"\4",'fe'=>1],
		'col'=>['c'=>"\0\0\4",'nt'=>1,'e'=>1,'v'=>1,'b'=>1],
		'colgroup'=>['c'=>"\200",'ac'=>"\0\0\4",'ac18'=>'not(@span)','nt'=>1,'e'=>1,'e0'=>'@span','b'=>1],
		'data'=>['c'=>"\7",'ac'=>"\4"],
		'datalist'=>['c'=>"\5",'ac'=>"\4\0\0\1"],
		'dd'=>['c'=>"\0\0\20",'ac'=>"\1",'b'=>1,'cp'=>['dd','dt']],
		'del'=>['c'=>"\5",'ac'=>"\0",'t'=>1],
		'dfn'=>['c'=>"\7\0\0\0\2",'ac'=>"\4",'dd'=>"\0\0\0\0\2"],
		'div'=>['c'=>"\3",'ac'=>"\1",'b'=>1,'cp'=>['p']],
		'dl'=>['c'=>"\3",'ac'=>"\0\40\20",'nt'=>1,'b'=>1,'cp'=>['p']],
		'dt'=>['c'=>"\0\0\20",'ac'=>"\1",'dd'=>"\100\2\1",'b'=>1,'cp'=>['dd','dt']],
		'em'=>['c'=>"\7",'ac'=>"\4",'fe'=>1],
		'embed'=>['c'=>"\57",'nt'=>1,'e'=>1,'v'=>1],
		'fieldset'=>['c'=>"\3\1",'ac'=>"\1\0\0\2",'b'=>1,'cp'=>['p']],
		'figcaption'=>['c'=>"\0\0\0\0\40",'ac'=>"\1",'b'=>1],
		'figure'=>['c'=>"\3\1",'ac'=>"\1\0\0\0\40",'b'=>1],
		'footer'=>['c'=>"\3\30\1",'ac'=>"\1",'dd'=>"\0\20",'b'=>1,'cp'=>['p']],
		'form'=>['c'=>"\3\0\0\0\1",'ac'=>"\1",'dd'=>"\0\0\0\0\1",'b'=>1,'cp'=>['p']],
		'h1'=>['c'=>"\103",'ac'=>"\4",'b'=>1,'cp'=>['p']],
		'h2'=>['c'=>"\103",'ac'=>"\4",'b'=>1,'cp'=>['p']],
		'h3'=>['c'=>"\103",'ac'=>"\4",'b'=>1,'cp'=>['p']],
		'h4'=>['c'=>"\103",'ac'=>"\4",'b'=>1,'cp'=>['p']],
		'h5'=>['c'=>"\103",'ac'=>"\4",'b'=>1,'cp'=>['p']],
		'h6'=>['c'=>"\103",'ac'=>"\4",'b'=>1,'cp'=>['p']],
		'head'=>['c'=>"\0\0\2",'ac'=>"\20",'nt'=>1,'b'=>1],
		'header'=>['c'=>"\3\30\1",'ac'=>"\1",'dd'=>"\0\20",'b'=>1,'cp'=>['p']],
		'hr'=>['c'=>"\1",'nt'=>1,'e'=>1,'v'=>1,'b'=>1,'cp'=>['p']],
		'html'=>['c'=>"\0",'ac'=>"\0\0\2",'nt'=>1,'b'=>1],
		'i'=>['c'=>"\7",'ac'=>"\4",'fe'=>1],
		'iframe'=>['c'=>"\57",'nt'=>1,'e'=>1,'to'=>1],
		'img'=>['c'=>"\57",'c3'=>'@usemap','nt'=>1,'e'=>1,'v'=>1],
		'input'=>['c'=>"\17",'c3'=>'@type!="hidden"','c1'=>'@type!="hidden"','nt'=>1,'e'=>1,'v'=>1],
		'ins'=>['c'=>"\7",'ac'=>"\0",'t'=>1],
		'kbd'=>['c'=>"\7",'ac'=>"\4"],
		'keygen'=>['c'=>"\17",'nt'=>1,'e'=>1,'v'=>1],
		'label'=>['c'=>"\17\0\0\100",'ac'=>"\4",'dd'=>"\0\0\0\100"],
		'legend'=>['c'=>"\0\0\0\2",'ac'=>"\4",'b'=>1],
		'li'=>['c'=>"\0\0\0\0\20",'ac'=>"\1",'b'=>1,'cp'=>['li']],
		'link'=>['c'=>"\20",'nt'=>1,'e'=>1,'v'=>1,'b'=>1],
		'main'=>['c'=>"\3\20\0\200",'ac'=>"\1",'b'=>1,'cp'=>['p']],
		'map'=>['c'=>"\7",'ac'=>"\0",'t'=>1],
		'mark'=>['c'=>"\7",'ac'=>"\4"],
		'meta'=>['c'=>"\20",'nt'=>1,'e'=>1,'v'=>1,'b'=>1],
		'meter'=>['c'=>"\7\100\0\40",'ac'=>"\4",'dd'=>"\0\0\0\40"],
		'nav'=>['c'=>"\3\2",'ac'=>"\1",'dd'=>"\0\0\0\200",'b'=>1,'cp'=>['p']],
		'noscript'=>['c'=>"\25\0\100",'ac'=>"\0",'dd'=>"\0\0\100",'t'=>1],
		'object'=>['c'=>"\57",'c3'=>'@usemap','ac'=>"\0\0\0\20",'t'=>1],
		'ol'=>['c'=>"\3",'ac'=>"\0\40\0\0\20",'nt'=>1,'b'=>1,'cp'=>['p']],
		'optgroup'=>['c'=>"\0\200",'ac'=>"\0\40\0\1",'nt'=>1,'b'=>1,'cp'=>['optgroup','option']],
		'option'=>['c'=>"\0\200\0\1",'e'=>1,'e0'=>'@label and @value','to'=>1,'b'=>1,'cp'=>['option']],
		'output'=>['c'=>"\7",'ac'=>"\4"],
		'p'=>['c'=>"\3",'ac'=>"\4",'b'=>1,'cp'=>['p']],
		'param'=>['c'=>"\0\0\0\20",'nt'=>1,'e'=>1,'v'=>1,'b'=>1],
		'pre'=>['c'=>"\3",'ac'=>"\4",'pre'=>1,'b'=>1,'cp'=>['p']],
		'progress'=>['c'=>"\7\100\40",'ac'=>"\4",'dd'=>"\0\0\40"],
		'q'=>['c'=>"\7",'ac'=>"\4"],
		'rb'=>['c'=>"\0\4",'ac'=>"\4",'b'=>1,'cp'=>['rb','rp','rt','rtc']],
		'rp'=>['c'=>"\0\4",'ac'=>"\4",'b'=>1,'cp'=>['rb','rp','rtc']],
		'rt'=>['c'=>"\0\4\0\0\10",'ac'=>"\4",'b'=>1,'cp'=>['rb','rp','rt']],
		'rtc'=>['c'=>"\0\4",'ac'=>"\4\0\0\0\10",'b'=>1,'cp'=>['rb','rp','rt','rtc']],
		'ruby'=>['c'=>"\7",'ac'=>"\4\4"],
		's'=>['c'=>"\7",'ac'=>"\4",'fe'=>1],
		'samp'=>['c'=>"\7",'ac'=>"\4"],
		'script'=>['c'=>"\25\40",'e'=>1,'e0'=>'@src','to'=>1],
		'section'=>['c'=>"\3\2",'ac'=>"\1",'b'=>1,'cp'=>['p']],
		'select'=>['c'=>"\17",'ac'=>"\0\240",'nt'=>1],
		'small'=>['c'=>"\7",'ac'=>"\4",'fe'=>1],
		'source'=>['c'=>"\0\0\200",'nt'=>1,'e'=>1,'v'=>1,'b'=>1],
		'span'=>['c'=>"\7",'ac'=>"\4"],
		'strong'=>['c'=>"\7",'ac'=>"\4",'fe'=>1],
		'style'=>['c'=>"\20",'to'=>1,'b'=>1],
		'sub'=>['c'=>"\7",'ac'=>"\4"],
		'sup'=>['c'=>"\7",'ac'=>"\4"],
		'table'=>['c'=>"\3\0\0\10",'ac'=>"\200\40",'nt'=>1,'b'=>1,'cp'=>['p']],
		'tbody'=>['c'=>"\200",'ac'=>"\0\40\0\0\4",'nt'=>1,'b'=>1,'cp'=>['tbody','tfoot','thead']],
		'td'=>['c'=>"\0\1\10",'ac'=>"\1",'b'=>1,'cp'=>['td','th']],
		'template'=>['c'=>"\25\40\4",'ac'=>"\21"],
		'textarea'=>['c'=>"\17",'pre'=>1],
		'tfoot'=>['c'=>"\200",'ac'=>"\0\40\0\0\4",'nt'=>1,'b'=>1,'cp'=>['tbody','thead']],
		'th'=>['c'=>"\0\0\10",'ac'=>"\1",'dd'=>"\100\2\1",'b'=>1,'cp'=>['td','th']],
		'thead'=>['c'=>"\200",'ac'=>"\0\40\0\0\4",'nt'=>1,'b'=>1],
		'time'=>['c'=>"\7",'ac'=>"\4"],
		'title'=>['c'=>"\20",'to'=>1,'b'=>1],
		'tr'=>['c'=>"\200\0\0\0\4",'ac'=>"\0\40\10",'nt'=>1,'b'=>1,'cp'=>['tr']],
		'track'=>['c'=>"\0\0\0\4",'nt'=>1,'e'=>1,'v'=>1,'b'=>1],
		'u'=>['c'=>"\7",'ac'=>"\4",'fe'=>1],
		'ul'=>['c'=>"\3",'ac'=>"\0\40\0\0\20",'nt'=>1,'b'=>1,'cp'=>['p']],
		'var'=>['c'=>"\7",'ac'=>"\4"],
		'video'=>['c'=>"\57",'c3'=>'@controls','ac'=>"\0\0\200\4",'ac23'=>'not(@src)','ac26'=>'@src','t'=>1],
		'wbr'=>['c'=>"\5",'nt'=>1,'e'=>1,'v'=>1]
	];

	/**
	* Get the bitfield value for a given element name in a given context
	*
	* @param  string     $elName Name of the HTML element
	* @param  string     $k      Bitfield name: either 'c', 'ac' or 'dd'
	* @param  DOMElement $node   Context node (not necessarily the same as $elName)
	* @return string
	*/
	protected function getBitfield($elName, $k, DOMElement $node)
	{
		if (!isset(self::$htmlElements[$elName][$k]))
		{
			return "\0";
		}

		$bitfield = self::$htmlElements[$elName][$k];

		foreach (str_split($bitfield, 1) as $byteNumber => $char)
		{
			$byteValue = ord($char);

			for ($bitNumber = 0; $bitNumber < 8; ++$bitNumber)
			{
				$bitValue = 1 << $bitNumber;

				if (!($byteValue & $bitValue))
				{
					// The bit is not set
					continue;
				}

				$n = $byteNumber * 8 + $bitNumber;

				// Test for an XPath condition for that category
				if (isset(self::$htmlElements[$elName][$k . $n]))
				{
					$xpath = self::$htmlElements[$elName][$k . $n];

					// If the XPath condition is not fulfilled...
					if (!$this->evaluate($xpath, $node))
					{
						// ...turn off the corresponding bit
						$byteValue ^= $bitValue;

						// Update the original bitfield
						$bitfield[$byteNumber] = chr($byteValue);
					}
				}
			}
		}

		return $bitfield;
	}

	/**
	* Test whether given element has given property in context
	*
	* @param  string     $elName   Element name
	* @param  string     $propName Property name, see self::$htmlElements
	* @param  DOMElement $node     Context node
	* @return bool
	*/
	protected function hasProperty($elName, $propName, DOMElement $node)
	{
		if (!empty(self::$htmlElements[$elName][$propName]))
		{
			// Test the XPath condition
			if (!isset(self::$htmlElements[$elName][$propName . '0'])
			 || $this->evaluate(self::$htmlElements[$elName][$propName . '0'], $node))
			{
				return true;
			}
		}

		return false;
	}

	/**
	* Test whether two bitfields have any bits in common
	*
	* @param  string $bitfield1
	* @param  string $bitfield2
	* @return bool
	*/
	protected static function match($bitfield1, $bitfield2)
	{
		return (trim($bitfield1 & $bitfield2, "\0") !== '');
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;

use DOMAttr;
use DOMCharacterData;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMProcessingInstruction;
use DOMXPath;
use RuntimeException;
use s9e\TextFormatter\Configurator\Exceptions\InvalidXslException;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;

abstract class TemplateHelper
{
	/**
	* XSL namespace
	*/
	const XMLNS_XSL = 'http://www.w3.org/1999/XSL/Transform';

	/**
	* Load a template as an xsl:template node
	*
	* Will attempt to load it as XML first, then as HTML as a fallback. Either way, an xsl:template
	* node is returned
	*
	* @param  string      $template
	* @return DOMDocument
	*/
	public static function loadTemplate($template)
	{
		$dom = new DOMDocument;

		// First try as XML
		$xml = '<?xml version="1.0" encoding="utf-8" ?><xsl:template xmlns:xsl="' . self::XMLNS_XSL . '">' . $template . '</xsl:template>';

		$useErrors = libxml_use_internal_errors(true);
		$success   = $dom->loadXML($xml);
		libxml_use_internal_errors($useErrors);

		if ($success)
		{
			return $dom;
		}

		// Try fixing unescaped ampersands and replacing HTML entities
		$tmp = preg_replace('(&(?![A-Za-z0-9]+;|#\\d+;|#x[A-Fa-f0-9]+;))', '&amp;', $template);
		$tmp = preg_replace_callback(
			'(&(?!quot;|amp;|apos;|lt;|gt;)\\w+;)',
			function ($m)
			{
				return html_entity_decode($m[0], ENT_NOQUOTES, 'UTF-8');
			},
			$tmp
		);
		$xml = '<?xml version="1.0" encoding="utf-8" ?><xsl:template xmlns:xsl="' . self::XMLNS_XSL . '">' . $tmp . '</xsl:template>';

		$useErrors = libxml_use_internal_errors(true);
		$success   = $dom->loadXML($xml);
		libxml_use_internal_errors($useErrors);

		if ($success)
		{
			return $dom;
		}

		// If the template contains an XSL element, abort now. Otherwise, try reparsing it as HTML
		if (strpos($template, '<xsl:') !== false)
		{
			$error = libxml_get_last_error();
			throw new InvalidXslException($error->message);
		}

		// Fall back to loading it inside a div, as HTML
		$html = '<html><body><div>' . $template . '</div></body></html>';

		$useErrors = libxml_use_internal_errors(true);
		$dom->loadHTML($html);
		libxml_use_internal_errors($useErrors);

		// Now dump the thing as XML and reload it with the proper namespace declaration
		$xml = self::innerXML($dom->documentElement->firstChild->firstChild);

		return self::loadTemplate($xml);
	}

	/**
	* Serialize a loaded template back into a string
	*
	* NOTE: removes the root node created by loadTemplate()
	*
	* @param  DOMDocument $dom
	* @return string
	*/
	public static function saveTemplate(DOMDocument $dom)
	{
		return self::innerXML($dom->documentElement);
	}

	/**
	* Get the XML content of an element
	*
	* @param  DOMElement $element
	* @return string
	*/
	protected static function innerXML(DOMElement $element)
	{
		// Serialize the XML then remove the outer element
		$xml = $element->ownerDocument->saveXML($element);

		$pos = 1 + strpos($xml, '>');
		$len = strrpos($xml, '<') - $pos;

		// If the template is empty, return an empty string
		if ($len < 1)
		{
			return '';
		}

		$xml = substr($xml, $pos, $len);

		return $xml;
	}

	/**
	* Return a list of parameters in use in given XSL
	*
	* @param  string $xsl XSL source
	* @return array       Alphabetically sorted list of unique parameter names
	*/
	public static function getParametersFromXSL($xsl)
	{
		$paramNames = [];

		// Wrap the XSL in boilerplate code because it might not have a root element
		$xsl = '<xsl:stylesheet xmlns:xsl="' . self::XMLNS_XSL . '">'
		     . '<xsl:template>'
		     . $xsl
		     . '</xsl:template>'
		     . '</xsl:stylesheet>';

		$dom = new DOMDocument;
		$dom->loadXML($xsl);

		$xpath = new DOMXPath($dom);

		// Start by collecting XPath expressions in XSL elements
		$query = '//xsl:*/@match | //xsl:*/@select | //xsl:*/@test';
		foreach ($xpath->query($query) as $attribute)
		{
			foreach (XPathHelper::getVariables($attribute->value) as $varName)
			{
				// Test whether this is the name of a local variable
				$varQuery = 'ancestor-or-self::*/'
				          . 'preceding-sibling::xsl:variable[@name="' . $varName . '"]';

				if (!$xpath->query($varQuery, $attribute)->length)
				{
					$paramNames[] = $varName;
				}
			}
		}

		// Collecting XPath expressions in attribute value templates
		$query = '//*[namespace-uri() != "' . self::XMLNS_XSL . '"]'
		       . '/@*[contains(., "{")]';
		foreach ($xpath->query($query) as $attribute)
		{
			$tokens = AVTHelper::parse($attribute->value);

			foreach ($tokens as $token)
			{
				if ($token[0] !== 'expression')
				{
					continue;
				}

				foreach (XPathHelper::getVariables($token[1]) as $varName)
				{
					// Test whether this is the name of a local variable
					$varQuery = 'ancestor-or-self::*/'
					          . 'preceding-sibling::xsl:variable[@name="' . $varName . '"]';

					if (!$xpath->query($varQuery, $attribute)->length)
					{
						$paramNames[] = $varName;
					}
				}
			}
		}

		// Dedupe and sort names
		$paramNames = array_unique($paramNames);
		sort($paramNames);

		return $paramNames;
	}

	/**
	* Return all attributes (literal or generated) that match given regexp
	*
	* @param  DOMDocument $dom    Document
	* @param  string      $regexp Regexp
	* @return array               Array of DOMNode instances
	*/
	public static function getAttributesByRegexp(DOMDocument $dom, $regexp)
	{
		$xpath = new DOMXPath($dom);
		$nodes = [];

		// Get literal attributes
		foreach ($xpath->query('//@*') as $attribute)
		{
			if (preg_match($regexp, $attribute->name))
			{
				$nodes[] = $attribute;
			}
		}

		// Get generated attributes
		foreach ($xpath->query('//xsl:attribute') as $attribute)
		{
			if (preg_match($regexp, $attribute->getAttribute('name')))
			{
				$nodes[] = $attribute;
			}
		}

		// Get attributes created with <xsl:copy-of/>
		foreach ($xpath->query('//xsl:copy-of') as $node)
		{
			$expr = $node->getAttribute('select');

			if (preg_match('/^@(\\w+)$/', $expr, $m)
			 && preg_match($regexp, $m[1]))
			{
				$nodes[] = $node;
			}
		}

		return $nodes;
	}

	/**
	* Return all elements (literal or generated) that match given regexp
	*
	* @param  DOMDocument $dom    Document
	* @param  string      $regexp Regexp
	* @return array               Array of DOMNode instances
	*/
	public static function getElementsByRegexp(DOMDocument $dom, $regexp)
	{
		$xpath = new DOMXPath($dom);
		$nodes = [];

		// Get literal attributes
		foreach ($xpath->query('//*') as $element)
		{
			if (preg_match($regexp, $element->localName))
			{
				$nodes[] = $element;
			}
		}

		// Get generated elements
		foreach ($xpath->query('//xsl:element') as $element)
		{
			if (preg_match($regexp, $element->getAttribute('name')))
			{
				$nodes[] = $element;
			}
		}

		// Get elements created with <xsl:copy-of/>
		// NOTE: this method of creating elements is disallowed by default
		foreach ($xpath->query('//xsl:copy-of') as $node)
		{
			$expr = $node->getAttribute('select');

			if (preg_match('/^\\w+$/', $expr)
			 && preg_match($regexp, $expr))
			{
				$nodes[] = $node;
			}
		}

		return $nodes;
	}

	/**
	* Return all elements (literal or generated) that match given regexp
	*
	* Will return all <param/> descendants of <object/> and all attributes of <embed/> whose name
	* matches given regexp. This method will NOT catch <param/> elements whose 'name' attribute is
	* set via an <xsl:attribute/>
	*
	* @param  DOMDocument $dom    Document
	* @param  string      $regexp
	* @return array               Array of DOMNode instances
	*/
	public static function getObjectParamsByRegexp(DOMDocument $dom, $regexp)
	{
		$xpath = new DOMXPath($dom);
		$nodes = [];

		// Collect attributes from <embed/> elements
		foreach (self::getAttributesByRegexp($dom, $regexp) as $attribute)
		{
			if ($attribute->nodeType === XML_ATTRIBUTE_NODE)
			{
				if (strtolower($attribute->parentNode->localName) === 'embed')
				{
					$nodes[] = $attribute;
				}
			}
			elseif ($xpath->evaluate('ancestor::embed', $attribute))
			{
				// Assuming <xsl:attribute/> or <xsl:copy-of/>
				$nodes[] = $attribute;
			}
		}

		// Collect <param/> descendants of <object/> elements
		foreach ($dom->getElementsByTagName('object') as $object)
		{
			foreach ($object->getElementsByTagName('param') as $param)
			{
				if (preg_match($regexp, $param->getAttribute('name')))
				{
					$nodes[] = $param;
				}
			}
		}

		return $nodes;
	}

	/**
	* Return all DOMNodes whose content is CSS
	*
	* @param  DOMDocument $dom Document
	* @return array            Array of DOMNode instances
	*/
	public static function getCSSNodes(DOMDocument $dom)
	{
		$regexp = '/^style$/i';
		$nodes  = array_merge(
			self::getAttributesByRegexp($dom, $regexp),
			self::getElementsByRegexp($dom, '/^style$/i')
		);

		return $nodes;
	}

	/**
	* Return all DOMNodes whose content is JavaScript
	*
	* @param  DOMDocument $dom Document
	* @return array            Array of DOMNode instances
	*/
	public static function getJSNodes(DOMDocument $dom)
	{
		$regexp = '/^(?>data-s9e-livepreview-postprocess$|on)/i';
		$nodes  = array_merge(
			self::getAttributesByRegexp($dom, $regexp),
			self::getElementsByRegexp($dom, '/^script$/i')
		);

		return $nodes;
	}

	/**
	* Return all DOMNodes whose content is an URL
	*
	* NOTE: it will also return HTML4 nodes whose content is an URI
	*
	* @param  DOMDocument $dom Document
	* @return array            Array of DOMNode instances
	*/
	public static function getURLNodes(DOMDocument $dom)
	{
		$regexp = '/(?:^(?:background|c(?>ite|lassid|odebase)|data|href|i(?>con|tem(?>id|prop|type))|longdesc|manifest|p(?>luginspage|oster|rofile)|usemap|(?>form)?action)|src)$/i';
		$nodes  = self::getAttributesByRegexp($dom, $regexp);

		/**
		* @link http://helpx.adobe.com/flash/kb/object-tag-syntax-flash-professional.html
		* @link http://www.sitepoint.com/control-internet-explorer/
		*/
		foreach (self::getObjectParamsByRegexp($dom, '/^(?:dataurl|movie)$/i') as $param)
		{
			$node = $param->getAttributeNode('value');
			if ($node)
			{
				$nodes[] = $node;
			}
		}

		return $nodes;
	}

	/**
	* Replace parts of a template that match given regexp
	*
	* Treats attribute values as plain text. Replacements within XPath expression is unsupported.
	* The callback must return an array with two elements. The first must be either of 'expression',
	* 'literal' or 'passthrough', and the second element depends on the first.
	*
	*  - 'expression' indicates that the replacement must be treated as an XPath expression such as
	*    '@foo', which must be passed as the second element.
	*  - 'literal' indicates a literal (plain text) replacement, passed as its second element.
	*  - 'passthrough' indicates that the replacement should the tag's content. It works differently
	*    whether it is inside an attribute's value or a text node. Within an attribute's value, the
	*    replacement will be the text content of the tag. Within a text node, the replacement
	*    becomes an <xsl:apply-templates/> node.
	*
	* @param  string   $template Original template
	* @param  string   $regexp   Regexp for matching parts that need replacement
	* @param  callback $fn       Callback used to get the replacement
	* @return string             Processed template
	*/
	public static function replaceTokens($template, $regexp, $fn)
	{
		if ($template === '')
		{
			return $template;
		}

		$dom   = self::loadTemplate($template);
		$xpath = new DOMXPath($dom);

		// Replace tokens in attributes
		foreach ($xpath->query('//@*') as $attribute)
		{
			// Generate the new value
			$attrValue = preg_replace_callback(
				$regexp,
				function ($m) use ($fn, $attribute)
				{
					$replacement = $fn($m, $attribute);

					if ($replacement[0] === 'expression')
					{
						return '{' . $replacement[1] . '}';
					}
					elseif ($replacement[0] === 'passthrough')
					{
						return '{.}';
					}
					else
					{
						// Literal replacement
						return $replacement[1];
					}
				},
				$attribute->value
			);

			// Replace the attribute value
			$attribute->value = htmlspecialchars($attrValue, ENT_COMPAT, 'UTF-8');
		}

		// Replace tokens in text nodes
		foreach ($xpath->query('//text()') as $node)
		{
			preg_match_all(
				$regexp,
				$node->textContent,
				$matches,
				PREG_SET_ORDER | PREG_OFFSET_CAPTURE
			);

			if (empty($matches))
			{
				continue;
			}

			// Grab the node's parent so that we can rebuild the text with added variables right
			// before the node, using DOM's insertBefore(). Technically, it would make more sense
			// to create a document fragment, append nodes then replace the node with the fragment
			// but it leads to namespace redeclarations, which looks ugly
			$parentNode = $node->parentNode;

			$lastPos = 0;
			foreach ($matches as $m)
			{
				$pos = $m[0][1];

				// Catch-up to current position
				if ($pos > $lastPos)
				{
					$parentNode->insertBefore(
						$dom->createTextNode(
							substr($node->textContent, $lastPos, $pos - $lastPos)
						),
						$node
					);
				}
				$lastPos = $pos + strlen($m[0][0]);

				// Remove the offset data from the array, keep only the content of captures so that
				// $_m contains the same data that preg_match() or preg_replace() would return
				$_m = [];
				foreach ($m as $capture)
				{
					$_m[] = $capture[0];
				}

				// Get the replacement for this token
				$replacement = $fn($_m, $node);

				if ($replacement[0] === 'expression')
				{
					// Expressions are evaluated in a <xsl:value-of/> node
					$parentNode
						->insertBefore(
							$dom->createElementNS(self::XMLNS_XSL, 'xsl:value-of'),
							$node
						)
						->setAttribute('select', $replacement[1]);
				}
				elseif ($replacement[0] === 'passthrough')
				{
					// Passthrough token, replace with <xsl:apply-templates/>
					$parentNode->insertBefore(
						$dom->createElementNS(self::XMLNS_XSL, 'xsl:apply-templates'),
						$node
					);
				}
				else
				{
					// Literal replacement
					$parentNode->insertBefore($dom->createTextNode($replacement[1]), $node);
				}
			}

			// Append the rest of the text
			$text = substr($node->textContent, $lastPos);
			if ($text > '')
			{
				$parentNode->insertBefore($dom->createTextNode($text), $node);
			}

			// Now remove the old text node
			$parentNode->removeChild($node);
		}

		return self::saveTemplate($dom);
	}

	/**
	* Highlight the source of a node inside of a template
	*
	* @param  DOMNode $node    Node to highlight
	* @param  string  $prepend HTML to prepend
	* @param  string  $append  HTML to append
	* @return string           Template's source, as HTML
	*/
	public static function highlightNode(DOMNode $node, $prepend, $append)
	{
		// Add a unique token to the node
		$uniqid = uniqid('_');
		if ($node instanceof DOMAttr)
		{
			$node->value .= $uniqid;
		}
		elseif ($node instanceof DOMElement)
		{
			$node->setAttribute($uniqid, '');
		}
		elseif ($node instanceof DOMCharacterData
		     || $node instanceof DOMProcessingInstruction)
		{
			$node->data .= $uniqid;
		}

		$dom = $node->ownerDocument;
		$dom->formatOutput = true;

		$docXml = self::innerXML($dom->documentElement);
		$docXml = trim(str_replace("\n  ", "\n", $docXml));

		$nodeHtml = htmlspecialchars(trim($dom->saveXML($node)));
		$docHtml  = htmlspecialchars($docXml);

		// Enclose the node's representation in our hilighting HTML
		$html = str_replace($nodeHtml, $prepend . $nodeHtml . $append, $docHtml);

		// Remove the unique token from HTML and from the node
		if ($node instanceof DOMAttr)
		{
			$node->value = substr($node->value, 0, -strlen($uniqid));
			$html = str_replace($uniqid, '', $html);
		}
		elseif ($node instanceof DOMElement)
		{
			$node->removeAttribute($uniqid);
			$html = str_replace(' ' . $uniqid . '=&quot;&quot;', '', $html);
		}
		elseif ($node instanceof DOMCharacterData
		     || $node instanceof DOMProcessingInstruction)
		{
			$node->data .= $uniqid;
			$html = str_replace($uniqid, '', $html);
		}

		return $html;
	}

	/**
	* Get the regexp used to remove meta elements from the intermediate representation
	*
	* @param  array  $templates
	* @return string
	*/
	public static function getMetaElementsRegexp(array $templates)
	{
		$exprs = [];

		// Coalesce all templates and load them into DOM
		$xsl = '<xsl:template xmlns:xsl="http://www.w3.org/1999/XSL/Transform">' . implode('', $templates) . '</xsl:template>';
		$dom = new DOMDocument;
		$dom->loadXML($xsl);
		$xpath = new DOMXPath($dom);

		// Collect the values of all the "match", "select" and "test" attributes of XSL elements
		$query = '//xsl:*/@*[contains("matchselectest", name())]';
		foreach ($xpath->query($query) as $attribute)
		{
			$exprs[] = $attribute->value;
		}

		// Collect the XPath expressions used in all the attributes of non-XSL elements
		$query = '//*[namespace-uri() != "' . self::XMLNS_XSL . '"]/@*';
		foreach ($xpath->query($query) as $attribute)
		{
			foreach (AVTHelper::parse($attribute->value) as $token)
			{
				if ($token[0] === 'expression')
				{
					$exprs[] = $token[1];
				}
			}
		}

		// Names of the meta elements
		$tagNames = [
			'e' => true,
			'i' => true,
			's' => true
		];

		// In the highly unlikely event the meta elements are rendered, we remove them from the list
		foreach (array_keys($tagNames) as $tagName)
		{
			if (isset($templates[$tagName]) && $templates[$tagName] !== '')
			{
				unset($tagNames[$tagName]);
			}
		}

		// Create a regexp that matches the tag names used as element names, e.g. "s" in "//s" but
		// not in "@s" or "$s"
		$regexp = '(\\b(?<![$@])(' . implode('|', array_keys($tagNames)) . ')(?!-)\\b)';

		// Now look into all of the expressions that we've collected
		preg_match_all($regexp, implode("\n", $exprs), $m);

		foreach ($m[0] as $tagName)
		{
			unset($tagNames[$tagName]);
		}

		if (empty($tagNames))
		{
			// Always-false regexp
			return '((?!))';
		}

		return '(<' . RegexpBuilder::fromList(array_keys($tagNames)) . '>[^<]*</[^>]+>)';
	}

	/**
	* Replace simple templates (in an array, in-place) with a common template
	*
	* In some situations, renderers can take advantage of multiple tags having the same template. In
	* any configuration, there's almost always a number of "simple" tags that are rendered as an
	* HTML element of the same name with no HTML attributes. For instance, the system tag "p" used
	* for paragraphs, "B" tags used for "b" HTML elements, etc... This method replaces those
	* templates with a common template that uses a dynamic element name based on the tag's name,
	* either its nodeName or localName depending on whether the tag is namespaced, and normalized to
	* lowercase using XPath's translate() function
	*
	* @param  array<string> &$templates Associative array of [tagName => template]
	* @param  integer       $minCount
	* @return void
	*/
	public static function replaceHomogeneousTemplates(array &$templates, $minCount = 3)
	{
		$tagNames = [];

		// Prepare the XPath expression used for the element's name
		$expr = 'name()';

		// Identify "simple" tags, whose template is one element of the same name. Their template
		// can be replaced with a dynamic template shared by all the simple tags
		foreach ($templates as $tagName => $template)
		{
			// Generate the element name based on the tag's localName, lowercased
			$elName = strtolower(preg_replace('/^[^:]+:/', '', $tagName));

			if ($template === '<' . $elName . '><xsl:apply-templates/></' . $elName . '>')
			{
				$tagNames[] = $tagName;

				// Use local-name() if any of the tags are namespaced
				if (strpos($tagName, ':') !== false)
				{
					$expr = 'local-name()';
				}
			}
		}

		// We only bother replacing their template if there are at least $minCount simple tags.
		// Otherwise it only makes the stylesheet bigger
		if (count($tagNames) < $minCount)
		{
			return;
		}

		// Generate a list of uppercase characters from the tags' names
		$chars = preg_replace('/[^A-Z]+/', '', count_chars(implode('', $tagNames), 3));

		if (is_string($chars) && $chars !== '')
		{
			$expr = 'translate(' . $expr . ",'" . $chars . "','" . strtolower($chars) . "')";
		}

		// Prepare the common template
		$template = '<xsl:element name="{' . $expr . '}">'
		          . '<xsl:apply-templates/>'
		          . '</xsl:element>';

		// Replace the templates
		foreach ($tagNames as $tagName)
		{
			$templates[$tagName] = $template;
		}
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;

use RuntimeException;

abstract class XPathHelper
{
	/**
	* Export a literal as an XPath expression
	*
	* @param  string $str Literal, e.g. "foo"
	* @return string      XPath expression, e.g. "'foo'"
	*/
	public static function export($str)
	{
		// foo becomes 'foo'
		if (strpos($str, "'") === false)
		{
			return "'" . $str . "'";
		}

		// d'oh becomes "d'oh"
		if (strpos($str, '"') === false)
		{
			return '"' . $str . '"';
		}

		// This string contains both ' and ". XPath 1.0 doesn't have a mechanism to escape quotes,
		// so we have to get creative and use concat() to join chunks in single quotes and chunks
		// in double quotes
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

	/**
	* Return the list of variables used in a given XPath expression
	*
	* @param  string $expr XPath expression
	* @return array        Alphabetically sorted list of unique variable names
	*/
	public static function getVariables($expr)
	{
		// First, remove strings' contents to prevent false-positives
		$expr = preg_replace('/(["\']).*?\\1/s', '$1$1', $expr);

		// Capture all the variable names
		preg_match_all('/\\$(\\w+)/', $expr, $matches);

		// Dedupe and sort names
		$varNames = array_unique($matches[1]);
		sort($varNames);

		return $varNames;
	}

	/**
	* Determine whether given XPath expression definitely evaluates to a number
	*
	* @param  string $expr XPath expression
	* @return bool         Whether given XPath expression definitely evaluates to a number
	*/
	public static function isExpressionNumeric($expr)
	{
		// Trim the expression and remove parentheses that are not part of a function call. PCRE
		// does not support lookbehind assertions of variable length so we have to flip the string.
		// We exclude the XPath operator "div" (flipped into "vid") to avoid false positives
		$expr = trim($expr);
		$expr = strrev(preg_replace('(\\((?!\\s*(?!vid(?!\\w))\\w))', '', strrev($expr)));
		$expr = str_replace(')', '', $expr);
		if (preg_match('(^([$@][-\\w]++|-?\\d++)(?>\\s*(?>[-+*]|div)\\s*(?1))++$)', $expr))
		{
			return true;
		}

		return false;
	}

	/**
	* Remove extraneous space in a given XPath expression
	*
	* @param  string $expr Original XPath expression
	* @return string       Minified XPath expression
	*/
	public static function minify($expr)
	{
		$old     = $expr;
		$strings = [];

		// Trim the surrounding whitespace then temporarily remove literal strings
		$expr = preg_replace_callback(
			'/(?:"[^"]*"|\'[^\']*\')/',
			function ($m) use (&$strings)
			{
				$uniqid = '(' . sha1(uniqid()) . ')';
				$strings[$uniqid] = $m[0];

				return $uniqid;
			},
			trim($expr)
		);

		if (preg_match('/[\'"]/', $expr))
		{
			throw new RuntimeException("Cannot parse XPath expression '" . $old . "'");
		}

		// Normalize whitespace to a single space
		$expr = preg_replace('/\\s+/', ' ', $expr);

		// Remove the space between a non-word character and a word character
		$expr = preg_replace('/([-a-z_0-9]) ([^-a-z_0-9])/i', '$1$2', $expr);
		$expr = preg_replace('/([^-a-z_0-9]) ([-a-z_0-9])/i', '$1$2', $expr);

		// Remove the space between two non-word characters as long as they're not two -
		$expr = preg_replace('/(?!- -)([^-a-z_0-9]) ([^-a-z_0-9])/i', '$1$2', $expr);

		// Remove the space between a - and a word character, as long as there's a space before -
		$expr = preg_replace('/ - ([a-z_0-9])/i', ' -$1', $expr);

		// Restore the literals
		$expr = strtr($expr, $strings);

		return $expr;
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;

use DOMDocument;
use s9e\TextFormatter\Configurator\Helpers\TemplateForensics;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\TemplateNormalizer;

class Template
{
	/**
	* @var TemplateForensics Instance of TemplateForensics based on the content of this template
	*/
	protected $forensics;

	/**
	* @var bool Whether this template has been normalized
	*/
	protected $isNormalized = false;

	/**
	* @var string This template's content
	*/
	protected $template;

	/**
	* Constructor
	*
	* @param  string $template This template's content
	* @return void
	*/
	public function __construct($template)
	{
		$this->template = $template;
	}

	/**
	* Handle calls to undefined methods
	*
	* Forwards calls to this template's TemplateForensics instance
	*
	* @return mixed
	*/
	public function __call($methodName, $args)
	{
		return call_user_func_array([$this->getForensics(), $methodName], $args);
	}

	/**
	* Return this template's content
	*
	* @return string
	*/
	public function __toString()
	{
		return $this->template;
	}

	/**
	* Return the content of this template as a DOMDocument
	*
	* NOTE: the content is wrapped in an <xsl:template/> node
	*
	* @return DOMDocument
	*/
	public function asDOM()
	{
		$xml = '<xsl:template xmlns:xsl="http://www.w3.org/1999/XSL/Transform">'
		     . $this->__toString()
		     . '</xsl:template>';

		$dom = new TemplateDocument($this);
		$dom->loadXML($xml);

		return $dom;
	}

	/**
	* Return all the nodes in this template whose content type is CSS
	*
	* @return array
	*/
	public function getCSSNodes()
	{
		return TemplateHelper::getCSSNodes($this->asDOM());
	}

	/**
	* Return an instance of TemplateForensics based on this template's content
	*
	* @return TemplateForensics
	*/
	public function getForensics()
	{
		if (!isset($this->forensics))
		{
			$this->forensics = new TemplateForensics($this->__toString());
		}

		return $this->forensics;
	}

	/**
	* Return all the nodes in this template whose content type is JavaScript
	*
	* @return array
	*/
	public function getJSNodes()
	{
		return TemplateHelper::getJSNodes($this->asDOM());
	}

	/**
	* Return all the nodes in this template whose value is an URL
	*
	* @return array
	*/
	public function getURLNodes()
	{
		return TemplateHelper::getURLNodes($this->asDOM());
	}

	/**
	* Return a list of parameters in use in this template
	*
	* @return array Alphabetically sorted list of unique parameter names
	*/
	public function getParameters()
	{
		return TemplateHelper::getParametersFromXSL($this->__toString());
	}

	/**
	* Set and/or return whether this template has been normalized
	*
	* @param  bool $bool If present, the new value for this template's isNormalized flag
	* @return bool       Whether this template has been normalized
	*/
	public function isNormalized($bool = null)
	{
		if (isset($bool))
		{
			$this->isNormalized = $bool;
		}

		return $this->isNormalized;
	}

	/**
	* Normalize this template's content
	*
	* @param  TemplateNormalizer $templateNormalizer
	* @return void
	*/
	public function normalize(TemplateNormalizer $templateNormalizer)
	{
		$this->forensics    = null;
		$this->template     = $templateNormalizer->normalizeTemplate($this->template);
		$this->isNormalized = true;
	}

	/**
	* Replace parts of this template that match given regexp
	*
	* @param  string   $regexp Regexp for matching parts that need replacement
	* @param  callback $fn     Callback used to get the replacement
	* @return void
	*/
	public function replaceTokens($regexp, $fn)
	{
		$this->forensics    = null;
		$this->template     = TemplateHelper::replaceTokens($this->template, $regexp, $fn);
		$this->isNormalized = false;
	}

	/**
	* Replace this template's content
	*
	* @param  string $template New content
	* @return void
	*/
	public function setContent($template)
	{
		$this->forensics    = null;
		$this->template     = (string) $template;
		$this->isNormalized = false;
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;

use InvalidArgumentException;

class Variant
{
	/**
	* @var mixed Default value
	*/
	protected $defaultValue;

	/**
	* @var array Variants
	*/
	protected $variants = [];

	/**
	* Constructor
	*
	* @param  mixed $value    Default value
	* @param  array $variants Associative array of variants ([name => value])
	* @return void
	*/
	public function __construct($value = null, array $variants = [])
	{
		// If we're trying to create a variant of a variant, we just become a copy of it
		if ($value instanceof self)
		{
			$this->defaultValue = $value->defaultValue;
			$this->variants     = $value->variants;
		}
		else
		{
			$this->defaultValue = $value;
		}

		foreach ($variants as $k => $v)
		{
			$this->set($k, $v);
		}
	}

	/**
	* Return this variant's default value as a string
	*
	* Variants are primarily used for regexp-related configuration, so it makes sense to allow
	* variants to be used as strings
	*
	* @return string
	*/
	public function __toString()
	{
		return (string) $this->defaultValue;
	}

	/**
	* Get this value, either from preferred variant or the default value
	*
	* @param  string $variant Preferred variant
	* @return mixed           Value from preferred variant if available, default value otherwise
	*/
	public function get($variant = null)
	{
		if (isset($variant) && isset($this->variants[$variant]))
		{
			list($isDynamic, $value) = $this->variants[$variant];

			return ($isDynamic) ? $value() : $value;
		}

		return $this->defaultValue;
	}

	/**
	* Return whether a value exists for given variant
	*
	* @param  string $variant Variant name
	* @return bool            Whether given variant exists
	*/
	public function has($variant)
	{
		return isset($this->variants[$variant]);
	}

	/**
	* Set a variant for this value
	*
	* @param  string $variant Name of variant
	* @param  mixed  $value   Variant's value
	* @return void
	*/
	public function set($variant, $value)
	{
		$this->variants[$variant] = [false, $value];
	}

	/**
	* Set a dynamic variant for this value
	*
	* @param  string   $variant  Name of variant
	* @param  callback $callback Callback that returns this variant's value
	* @return void
	*/
	public function setDynamic($variant, $callback)
	{
		if (!is_callable($callback))
		{
			throw new InvalidArgumentException('Argument 1 passed to ' . __METHOD__ . ' must be a valid callback');
		}

		$this->variants[$variant] = [true, $callback];
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript;

/**
* Wrapper used to identify strings that should be treated as JavaScript source code
*/
class Code
{
	/**
	* @var string JavaScript source code
	*/
	public $code;

	/**
	* Constructor
	*
	* @param  string $code JavaScript source code
	* @return void
	*/
	public function __construct($code)
	{
		$this->code = $code;
	}

	/**
	* Return this source code
	*
	* @return string
	*/
	public function __toString()
	{
		return $this->code;
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript;

use InvalidArgumentException;

class FunctionProvider
{
	static public $cache = [
		'addslashes'=>'function(str)
{
	return str.replace(/["\'\\\\]/g, \'\\\\$&\').replace(/\\u0000/g, \'\\\\0\');
}',
		'dechex'=>'function(str)
{
	return parseInt(str).toString(16);
}',
		'intval'=>'function(str)
{
	return parseInt(str) || 0;
}',
		'ltrim'=>'function(str)
{
	return str.replace(/^[ \\n\\r\\t\\0\\x0B]+/g, \'\');
}',
		'mb_strtolower'=>'function(str)
{
	return str.toLowerCase();
}',
		'mb_strtoupper'=>'function(str)
{
	return str.toUpperCase();
}',
		'mt_rand'=>'function(min, max)
{
	return (min + Math.floor(Math.random() * (max + 1 - min)));
}',
		'rawurlencode'=>'function(str)
{
	return encodeURIComponent(str).replace(
		/[!\'()*]/g,
		/**
		* @param {!string} c
		*/
		function(c)
		{
			return \'%\' + c.charCodeAt(0).toString(16).toUpperCase();
		}
	);
}',
		'rtrim'=>'function(str)
{
	return str.replace(/[ \\n\\r\\t\\0\\x0B]+$/g, \'\');
}',
		'str_rot13'=>'function(str)
{
	return str.replace(
		/[a-z]/gi,
		function(c)
		{
			return String.fromCharCode(c.charCodeAt(0) + ((c.toLowerCase() < \'n\') ? 13 : -13));
		}
	);
}',
		'stripslashes'=>'function(str)
{
	// NOTE: this will not correctly transform \\0 into a NULL byte. I consider this a feature
	//       rather than a bug. There\'s no reason to use NULL bytes in a text.
	return str.replace(/\\\\([\\s\\S]?)/g, \'\\\\1\');
}',
		'strrev'=>'function(str)
{
	return str.split(\'\').reverse().join(\'\');
}',
		'strtolower'=>'function(str)
{
	return str.toLowerCase();
}',
		'strtotime'=>'function(str)
{
	return Date.parse(str) / 1000;
}',
		'strtoupper'=>'function(str)
{
	return str.toUpperCase();
}',
		'trim'=>'function(str)
{
	return str.replace(/^[ \\n\\r\\t\\0\\x0B]+/g, \'\').replace(/[ \\n\\r\\t\\0\\x0B]+$/g, \'\');
}',
		'ucfirst'=>'function(str)
{
	return str.charAt(0).toUpperCase() + str.substr(1);
}',
		'ucwords'=>'function(str)
{
	return str.replace(
		/(?:^|\\s)[a-z]/g,
		function(m)
		{
			return m.toUpperCase()
		}
	);
}',
		'urlencode'=>'function(str)
{
	return encodeURIComponent(str);
}'
	];

	/**
	* Return a function's source from the cache or the filesystem
	*
	* @param  string $funcName Function's name
	* @return string           Function's source
	*/
	public static function get($funcName)
	{
		if (isset(self::$cache[$funcName]))
		{
			return self::$cache[$funcName];
		}
		if (preg_match('(^[a-z_0-9]+$)D', $funcName))
		{
			$filepath = __DIR__ . '/Configurator/JavaScript/functions/' . $funcName . '.js';
			if (file_exists($filepath))
			{
				return file_get_contents($filepath);
			}
		}
		throw new InvalidArgumentException("Unknown function '" . $funcName . "'");
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;

interface RendererGenerator
{
	/**
	* Generate and return a renderer
	*
	* @param  Rendering                   $rendering Rendering configuration
	* @return \s9e\TextFormatter\Renderer            Instance of Renderer
	*/
	public function getRenderer(Rendering $rendering);
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\XSLT;

use s9e\TextFormatter\Configurator\TemplateNormalizer;

class Optimizer
{
	/**
	* @var TemplateNormalizer
	*/
	public $normalizer;

	/**
	* Constructor
	*
	* @return void
	*/
	public function __construct()
	{
		$this->normalizer = new TemplateNormalizer;
		$this->normalizer->clear();
		$this->normalizer->append('MergeIdenticalConditionalBranches');
		$this->normalizer->append('OptimizeNestedConditionals');
	}

	/**
	* Optimize a single template
	*
	* @param  string $template Original template
	* @return string           Optimized template
	*/
	public function optimizeTemplate($template)
	{
		return $this->normalizer->normalizeTemplate($template);
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RulesGenerators\Interfaces;

use s9e\TextFormatter\Configurator\Helpers\TemplateForensics;

interface BooleanRulesGenerator
{
	/**
	* Generate boolean rules that apply to given template forensics
	*
	* @param  TemplateForensics $src Source template forensics
	* @return array                  Array of boolean rules as [ruleName => bool]
	*/
	public function generateBooleanRules(TemplateForensics $src);
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RulesGenerators\Interfaces;

use s9e\TextFormatter\Configurator\Helpers\TemplateForensics;

interface TargetedRulesGenerator
{
	/**
	* Generate targeted rules that apply to given template forensics
	*
	* @param  TemplateForensics $src Source template forensics
	* @param  TemplateForensics $trg Target template forensics
	* @return array                  List of rules that apply from the source template to the target
	*/
	public function generateTargetedRules(TemplateForensics $src, TemplateForensics $trg);
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;

use DOMElement;
use s9e\TextFormatter\Configurator\Items\Tag;

/**
* @codeCoverageIgnore
*/
abstract class TemplateCheck
{
	/**
	* XSL namespace
	*/
	const XMLNS_XSL = 'http://www.w3.org/1999/XSL/Transform';

	/**
	* Check a template for infractions to this check and throw any relevant Exception
	*
	* @param  DOMElement $template <xsl:template/> node
	* @param  Tag     $tag      Tag this template belongs to
	* @return void
	*/
	abstract public function check(DOMElement $template, Tag $tag);
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;

use DOMElement;

/**
* @codeCoverageIgnore
*/
abstract class TemplateNormalization
{
	/**
	* XSL namespace
	*/
	const XMLNS_XSL = 'http://www.w3.org/1999/XSL/Transform';

	/**
	* @var bool Whether this normalization should be applied only once per template
	*/
	public $onlyOnce = false;

	/**
	* Apply this normalization rule to given template
	*
	* @param  DOMElement $template <xsl:template/> node
	* @return void
	*/
	abstract public function normalize(DOMElement $template);

	/**
	* Make an ASCII string lowercase
	*
	* @param  string $str Original string
	* @return string      Lowercased string
	*/
	public static function lowercase($str)
	{
		return strtr($str, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz');
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Traits;

/**
* Allows an object to act as a proxy for a NormalizedCollection stored in $this->collection
*
* @property \s9e\TextFormatter\Collections\NormalizedCollection $collection
*
* @method mixed   add(string $key, mixed $value)
* @method array   asConfig()
* @method bool    contains(mixed $value)
* @method void    delete(string $key)
* @method bool    exists(string $key)
* @method mixed   get(string $key)
* @method mixed   indexOf(mixed $value)
* @method string  normalizeKey(string $key)
* @method mixed   normalizeValue(mixed $value)
* @method string  onDuplicate(string $action)
* @method mixed   set(string $key, mixed $value)
*/
trait CollectionProxy
{
	/**
	* Forward all unknown method calls to $this->collection
	*
	* @param  string $methodName
	* @param  array  $args
	* @return mixed
	*/
	public function __call($methodName, $args)
	{
		return call_user_func_array([$this->collection, $methodName], $args);
	}

	//==========================================================================
	// ArrayAccess
	//==========================================================================

	/**
	* @param  string|integer $offset
	* @return bool
	*/
	public function offsetExists($offset)
	{
		return isset($this->collection[$offset]);
	}

	/**
	* @param  string|integer $offset
	* @return mixed
	*/
	public function offsetGet($offset)
	{
		return $this->collection[$offset];
	}

	/**
	* @param  string|integer $offset
	* @param  mixed          $value
	* @return void
	*/
	public function offsetSet($offset, $value)
	{
		$this->collection[$offset] = $value;
	}

	/**
	* @param  string|integer $offset
	* @return void
	*/
	public function offsetUnset($offset)
	{
		unset($this->collection[$offset]);
	}

	//==========================================================================
	// Countable
	//==========================================================================

	/**
	* @return integer
	*/
	public function count()
	{
		return count($this->collection);
	}

	//==========================================================================
	// Iterator
	//==========================================================================

	/**
	* @return mixed
	*/
	public function current()
	{
		return $this->collection->current();
	}

	/**
	* @return string|integer
	*/
	public function key()
	{
		return $this->collection->key();
	}

	/**
	* @return mixed
	*/
	public function next()
	{
		return $this->collection->next();
	}

	/**
	* @return void
	*/
	public function rewind()
	{
		$this->collection->rewind();
	}

	/**
	* @return boolean
	*/
	public function valid()
	{
		return $this->collection->valid();
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Traits;

use InvalidArgumentException;
use RuntimeException;
use Traversable;
use s9e\TextFormatter\Configurator\Collections\Collection;
use s9e\TextFormatter\Configurator\Collections\NormalizedCollection;

/**
* Provides magic __get, __set, __isset and __unset implementations
*/
trait Configurable
{
	/**
	* Magic getter
	*
	* Will return $this->foo if it exists, then $this->getFoo() or will throw an exception if
	* neither exists
	*
	* @param  string $propName
	* @return mixed
	*/
	public function __get($propName)
	{
		$methodName = 'get' . ucfirst($propName);

		// Look for a getter, e.g. getDefaultTemplate()
		if (method_exists($this, $methodName))
		{
			return $this->$methodName();
		}

		if (!property_exists($this, $propName))
		{
			throw new RuntimeException("Property '" . $propName . "' does not exist");
		}

		return $this->$propName;
	}

	/**
	* Magic setter
	*
	* Will call $this->setFoo($propValue) if it exists, otherwise it will set $this->foo.
	* If $this->foo is a NormalizedCollection, we do not replace it, instead we clear() it then
	* fill it back up. It will not overwrite an object with a different incompatible object (of a
	* different, non-extending class) and it will throw an exception if the PHP type cannot match
	* without incurring data loss.
	*
	* @param  string $propName
	* @param  mixed  $propValue
	* @return void
	*/
	public function __set($propName, $propValue)
	{
		$methodName = 'set' . ucfirst($propName);

		// Look for a setter, e.g. setDefaultChildRule()
		if (method_exists($this, $methodName))
		{
			$this->$methodName($propValue);

			return;
		}

		// If the property isn't already set, we just create/set it
		if (!isset($this->$propName))
		{
			$this->$propName = $propValue;

			return;
		}

		// If we're trying to replace a NormalizedCollection, instead we clear it then
		// iteratively set new values
		if ($this->$propName instanceof NormalizedCollection)
		{
			if (!is_array($propValue)
			 && !($propValue instanceof Traversable))
			{
				throw new InvalidArgumentException("Property '" . $propName . "' expects an array or a traversable object to be passed");
			}

			$this->$propName->clear();

			foreach ($propValue as $k => $v)
			{
				$this->$propName->set($k, $v);
			}

			return;
		}

		// If this property is an object, test whether they are compatible. Otherwise, test if PHP
		// types are compatible
		if (is_object($this->$propName))
		{
			if (!($propValue instanceof $this->$propName))
			{
				throw new InvalidArgumentException("Cannot replace property '" . $propName . "' of class '" . get_class($this->$propName) . "' with instance of '" . get_class($propValue) . "'");
			}
		}
		else
		{
			// Test whether the PHP types are compatible
			$oldType = gettype($this->$propName);
			$newType = gettype($propValue);

			// If the property is a boolean, we'll accept "true" and "false" as strings
			if ($oldType === 'boolean')
			{
				if ($propValue === 'false')
				{
					$newType   = 'boolean';
					$propValue = false;
				}
				elseif ($propValue === 'true')
				{
					$newType   = 'boolean';
					$propValue = true;
				}
			}

			if ($oldType !== $newType)
			{
				// Test whether the PHP type roundtrip is lossless
				$tmp = $propValue;
				settype($tmp, $oldType);
				settype($tmp, $newType);

				if ($tmp !== $propValue)
				{
					throw new InvalidArgumentException("Cannot replace property '" . $propName . "' of type " . $oldType . ' with value of type ' . $newType);
				}

				// Finally, set the new value to the correct type
				settype($propValue, $oldType);
			}
		}

		$this->$propName = $propValue;
	}

	/**
	* Test whether a property is set
	*
	* @param  string $propName
	* @return bool
	*/
	public function __isset($propName)
	{
		$methodName = 'isset' . ucfirst($propName);

		if (method_exists($this, $methodName))
		{
			return $this->$methodName();
		}

		return isset($this->$propName);
	}

	/**
	* Unset a property, if the class supports it
	*
	* @param  string $propName
	* @return void
	*/
	public function __unset($propName)
	{
		$methodName = 'unset' . ucfirst($propName);

		if (method_exists($this, $methodName))
		{
			$this->$methodName();

			return;
		}

		if (!isset($this->$propName))
		{
			return;
		}

		if ($this->$propName instanceof Collection)
		{
			$this->$propName->clear();

			return;
		}

		throw new RuntimeException("Property '" . $propName . "' cannot be unset");
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Traits;

trait TemplateSafeness
{
	/**
	* @var array Contexts in which this object is considered safe to be used
	*/
	protected $markedSafe = [];

	/**
	* Return whether this object is safe to be used in given context
	*
	* @param  string $context Either 'AsURL', 'InCSS' or 'InJS'
	* @return bool
	*/
	protected function isSafe($context)
	{
		// Test whether this attribute was marked as safe in given context
		return !empty($this->markedSafe[$context]);
	}

	/**
	* Return whether this object is safe to be used as a URL
	*
	* @return bool
	*/
	public function isSafeAsURL()
	{
		return $this->isSafe('AsURL');
	}

	/**
	* Return whether this object is safe to be used in CSS
	*
	* @return bool
	*/
	public function isSafeInCSS()
	{
		return $this->isSafe('InCSS');
	}

	/**
	* Return whether this object is safe to be used in JavaScript
	*
	* @return bool
	*/
	public function isSafeInJS()
	{
		return $this->isSafe('InJS');
	}

	/**
	* Return whether this object is safe to be used as a URL
	*
	* @return self
	*/
	public function markAsSafeAsURL()
	{
		$this->markedSafe['AsURL'] = true;

		return $this;
	}

	/**
	* Return whether this object is safe to be used in CSS
	*
	* @return self
	*/
	public function markAsSafeInCSS()
	{
		$this->markedSafe['InCSS'] = true;

		return $this;
	}

	/**
	* Return whether this object is safe to be used in JavaScript
	*
	* @return self
	*/
	public function markAsSafeInJS()
	{
		$this->markedSafe['InJS'] = true;

		return $this;
	}

	/**
	* Reset the "marked safe" statuses
	*
	* @return self
	*/
	public function resetSafeness()
	{
		$this->markedSafe = [];

		return $this;
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Validators;

use InvalidArgumentException;

/**
* Attribute name rules:
*  - must start with a letter or an underscore
*  - can only contain letters, numbers, underscores and dashes
*
* Unprefixed names are normalized to uppercase. Prefixed names are preserved as-is.
*/
abstract class AttributeName
{
	/**
	* Return whether a string is a valid attribute name
	*
	* @param  string $name
	* @return bool
	*/
	public static function isValid($name)
	{
		return (bool) preg_match('#^(?!xmlns$)[a-z_][-a-z_0-9]*$#Di', $name);
	}

	/**
	* Normalize a tag name
	*
	* @throws InvalidArgumentException if the original name is not valid
	*
	* @param  string $name Original name
	* @return string       Normalized name
	*/
	public static function normalize($name)
	{
		if (!static::isValid($name))
		{
			throw new InvalidArgumentException("Invalid attribute name '" . $name . "'");
		}

		return strtolower($name);
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Validators;

use InvalidArgumentException;

/**
* Tag name rules:
*  - must start with a letter or an underscore
*  - can only contain letters, numbers, dashes and underscores
*  - can be prefixed with one prefix following the same rules, separated with one colon
*  - the prefixes "xsl" and "s9e" are reserved
*
* Unprefixed names are normalized to uppercase. Prefixed names are preserved as-is.
*/
abstract class TagName
{
	/**
	* Return whether a string is a valid tag name
	*
	* @param  string $name
	* @return bool
	*/
	public static function isValid($name)
	{
		return (bool) preg_match('#^(?:(?!xmlns|xsl|s9e)[a-z_][a-z_0-9]*:)?[a-z_][-a-z_0-9]*$#Di', $name);
	}

	/**
	* Normalize a tag name
	*
	* @throws InvalidArgumentException if the original name is not valid
	*
	* @param  string $name Original name
	* @return string       Normalized name
	*/
	public static function normalize($name)
	{
		if (!static::isValid($name))
		{
			throw new InvalidArgumentException("Invalid tag name '" . $name . "'");
		}

		// Non-namespaced tags are uppercased
		if (strpos($name, ':') === false)
		{
			$name = strtoupper($name);
		}

		return $name;
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

use Countable;
use Iterator;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;

class Collection implements ConfigProvider, Countable, Iterator
{
	/**
	* @var array Items that this collection holds
	*/
	protected $items = [];

	/**
	* Empty this collection
	*/
	public function clear()
	{
		$this->items = [];
	}

	/**
	* @return mixed
	*/
	public function asConfig()
	{
		return ConfigHelper::toArray($this->items, true);
	}

	//==========================================================================
	// Countable stuff
	//==========================================================================

	/**
	* @return integer
	*/
	public function count()
	{
		return count($this->items);
	}

	//==========================================================================
	// Iterator stuff
	//==========================================================================

	/**
	* @return mixed
	*/
	public function current()
	{
		return current($this->items);
	}

	/**
	* @return integer|string
	*/
	public function key()
	{
		return key($this->items);
	}

	/**
	* @return mixed
	*/
	public function next()
	{
		return next($this->items);
	}

	/**
	* @return void
	*/
	public function rewind()
	{
		reset($this->items);
	}

	/**
	* @return bool
	*/
	public function valid()
	{
		return (key($this->items) !== null);
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;

use s9e\TextFormatter\Configurator\Collections\AttributeFilterChain;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Items\ProgrammableCallback;
use s9e\TextFormatter\Configurator\Traits\Configurable;
use s9e\TextFormatter\Configurator\Traits\TemplateSafeness;

/**
* @property mixed $defaultValue Default value used for this attribute
* @property AttributeFilterChain $filterChain This attribute's filter chain
* @property ProgrammableCallback $generator Generator used to generate a value for this attribute during parsing
* @property bool $required Whether this attribute is required for the tag to be valid
*/
class Attribute implements ConfigProvider
{
	use Configurable;
	use TemplateSafeness;

	/**
	* @var mixed Default value used for this attribute
	*/
	protected $defaultValue;

	/**
	* @var AttributeFilterChain This attribute's filter chain
	*/
	protected $filterChain;

	/**
	* @var ProgrammableCallback Generator used to generate a value for this attribute during parsing
	*/
	protected $generator;

	/**
	* @var bool Whether this attribute is required for the tag to be valid
	*/
	protected $required = true;

	/**
	* Constructor
	*
	* @param array $options This attribute's options
	*/
	public function __construct(array $options = null)
	{
		$this->filterChain = new AttributeFilterChain;

		if (isset($options))
		{
			foreach ($options as $optionName => $optionValue)
			{
				$this->__set($optionName, $optionValue);
			}
		}
	}

	/**
	* Return whether this attribute is safe to be used in given context
	*
	* @param  string $context Either 'AsURL', 'InCSS' or 'InJS'
	* @return bool
	*/
	protected function isSafe($context)
	{
		// Test this attribute's filters
		$methodName = 'isSafe' . $context;
		foreach ($this->filterChain as $filter)
		{
			if ($filter->$methodName())
			{
				// If any filter makes it safe, we consider it safe
				return true;
			}
		}

		return !empty($this->markedSafe[$context]);
	}

	/**
	* Set a generator for this attribute
	*
	* @param callable|ProgrammableCallback $callback
	*/
	public function setGenerator($callback)
	{
		if (!($callback instanceof ProgrammableCallback))
		{
			$callback = new ProgrammableCallback($callback);
		}

		$this->generator = $callback;
	}

	/**
	* {@inheritdoc}
	*/
	public function asConfig()
	{
		$vars = get_object_vars($this);
		unset($vars['markedSafe']);

		return ConfigHelper::toArray($vars);
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;

use InvalidArgumentException;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Items\Variant;
use s9e\TextFormatter\Configurator\JavaScript\Code;
use s9e\TextFormatter\Configurator\JavaScript\FunctionProvider;

class ProgrammableCallback implements ConfigProvider
{
	/**
	* @var callback Callback
	*/
	protected $callback;

	/**
	* @var Code JavaScript source code for this callback
	*/
	protected $js = null;

	/**
	* @var array List of params to be passed to the callback
	*/
	protected $params = [];

	/**
	* @var array Variables associated with this instance
	*/
	protected $vars = [];

	/**
	* @param callable $callback
	*/
	public function __construct($callback)
	{
		if (!is_callable($callback))
		{
			throw new InvalidArgumentException(__METHOD__ . '() expects a callback');
		}

		// Normalize ['foo', 'bar'] to 'foo::bar'
		if (is_array($callback) && is_string($callback[0]))
		{
			$callback = $callback[0] . '::' . $callback[1];
		}

		// Normalize '\\foo' to 'foo' and '\\foo::bar' to 'foo::bar'
		if (is_string($callback))
		{
			$callback = ltrim($callback, '\\');
		}

		$this->callback = $callback;
	}

	/**
	* Add a parameter by value
	*
	* @param  mixed $paramValue
	* @return self
	*/
	public function addParameterByValue($paramValue)
	{
		$this->params[] = $paramValue;

		return $this;
	}

	/**
	* Add a parameter by name
	*
	* The value will be dynamically generated by the caller
	*
	* @param  string $paramName
	* @return self
	*/
	public function addParameterByName($paramName)
	{
		$this->params[$paramName] = null;

		return $this;
	}

	/**
	* Get this object's callback
	*
	* @return callback
	*/
	public function getCallback()
	{
		return $this->callback;
	}

	/**
	* Get this callback's JavaScript
	*
	* @return Code Instance of Code
	*/
	public function getJS()
	{
		// If no JavaScript was set try the default FunctionProvider
		if (!isset($this->js) && is_string($this->callback))
		{
			try
			{
				return new Code(FunctionProvider::get($this->callback));
			}
			catch (InvalidArgumentException $e)
			{
				// Do nothing
			}
		}

		return $this->js;
	}

	/**
	* Get this object's variables
	*
	* @return array
	*/
	public function getVars()
	{
		return $this->vars;
	}

	/**
	* Remove all the parameters
	*
	* @return self
	*/
	public function resetParameters()
	{
		$this->params = [];

		return $this;
	}

	/**
	* Set this callback's JavaScript
	*
	* @param  Code|string $js JavaScript source code for this callback
	* @return self
	*/
	public function setJS($js)
	{
		if (!($js instanceof Code))
		{
			$js = new Code($js);
		}

		$this->js = $js;

		return $this;
	}

	/**
	* Set or overwrite one of this callback's variable
	*
	* @param  string $name  Variable name
	* @param  string $value Variable value
	* @return self
	*/
	public function setVar($name, $value)
	{
		$this->vars[$name] = $value;

		return $this;
	}

	/**
	* Set all of this callback's variables at once
	*
	* @param  array $vars Associative array of values
	* @return self
	*/
	public function setVars(array $vars)
	{
		$this->vars = $vars;

		return $this;
	}

	/**
	* {@inheritdoc}
	*/
	public function asConfig()
	{
		$config = ['callback' => $this->callback];

		foreach ($this->params as $k => $v)
		{
			if (is_numeric($k))
			{
				// By value
				$config['params'][] = $v;
			}
			elseif (isset($this->vars[$k]))
			{
				// By name, but the value is readily available in $this->vars
				$config['params'][] = $this->vars[$k];
			}
			else
			{
				// By name
				$config['params'][$k] = null;
			}
		}

		if (isset($config['params']))
		{
			$config['params'] = ConfigHelper::toArray($config['params'], true, true);
		}

		// Add the callback's JavaScript representation, if available
		$js = $this->getJS();
		if (isset($js))
		{
			$config['js'] = new Variant;
			$config['js']->set('JS', $js);
		}

		return $config;
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;

use InvalidArgumentException;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Items\Variant;
use s9e\TextFormatter\Configurator\JavaScript\RegexpConvertor;

class Regexp implements ConfigProvider
{
	/**
	* @var bool Whether this regexp should become a JavaScript RegExp object with global flag
	*/
	protected $isGlobal;

	/**
	* @var string PCRE regexp, with delimiters and modifiers, e.g. "/foo/i"
	*/
	protected $regexp;

	/**
	* Constructor
	*
	* @param  string $regexp PCRE regexp, with delimiters and modifiers, e.g. "/foo/i"
	* @return void
	*/
	public function __construct($regexp, $isGlobal = false)
	{
		if (@preg_match($regexp, '') === false)
		{
			throw new InvalidArgumentException('Invalid regular expression ' . var_export($regexp, true));
		}

		$this->regexp   = $regexp;
		$this->isGlobal = $isGlobal;
	}

	/**
	* Return this regexp as a string
	*
	* @return string
	*/
	public function __toString()
	{
		return $this->regexp;
	}

	/**
	* {@inheritdoc}
	*/
	public function asConfig()
	{
		$variant = new Variant($this->regexp);
		$variant->setDynamic(
			'JS',
			function ()
			{
				return $this->toJS();
			}
		);

		return $variant;
	}

	/**
	* Return this regexp as a JavaScript RegExp
	*
	* @return RegExp
	*/
	public function toJS()
	{
		$obj = RegexpConvertor::toJS($this->regexp);

		if ($this->isGlobal)
		{
			$obj->flags .= 'g';
		}

		return $obj;
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;

use InvalidArgumentException;
use s9e\TextFormatter\Configurator\Collections\AttributeCollection;
use s9e\TextFormatter\Configurator\Collections\AttributePreprocessorCollection;
use s9e\TextFormatter\Configurator\Collections\Ruleset;
use s9e\TextFormatter\Configurator\Collections\TagFilterChain;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Items\Template;
use s9e\TextFormatter\Configurator\Traits\Configurable;

/**
* @property AttributeCollection $attributes This tag's attributes
* @property AttributePreprocessorCollection $attributePreprocessors This tag's attribute parsers
* @property TagFilterChain $filterChain This tag's filter chain
* @property integer $nestingLimit Maximum nesting level for this tag
* @property Ruleset $rules Rules associated with this tag
* @property integer $tagLimit Maximum number of this tag per message
* @property Template $template Template associated with this tag
* @property-write string|Template $template Template associated with this tag
*/
class Tag implements ConfigProvider
{
	use Configurable;

	/**
	* @var AttributeCollection This tag's attributes
	*/
	protected $attributes;

	/**
	* @var AttributePreprocessorCollection This tag's attribute parsers
	*/
	protected $attributePreprocessors;

	/**
	* @var TagFilterChain This tag's filter chain
	*/
	protected $filterChain;

	/**
	* @var integer Maximum nesting level for this tag
	*/
	protected $nestingLimit = 10;

	/**
	* @var Ruleset Rules associated with this tag
	*/
	protected $rules;

	/**
	* @var integer Maximum number of this tag per message
	*/
	protected $tagLimit = 1000;

	/**
	* @var Template Template associated with this tag
	*/
	protected $template;

	/**
	* Constructor
	*
	* @param  array $options This tag's options
	* @return void
	*/
	public function __construct(array $options = null)
	{
		$this->attributes             = new AttributeCollection;
		$this->attributePreprocessors = new AttributePreprocessorCollection;
		$this->filterChain            = new TagFilterChain;
		$this->rules                  = new Ruleset;

		// Start the filterChain with the default processing
		$this->filterChain->append('s9e\\TextFormatter\\Parser::executeAttributePreprocessors')
		                  ->addParameterByName('tag')
		                  ->addParameterByName('tagConfig');

		$this->filterChain->append('s9e\\TextFormatter\\Parser::filterAttributes')
		                  ->addParameterByName('tag')
		                  ->addParameterByName('tagConfig')
		                  ->addParameterByName('registeredVars')
		                  ->addParameterByName('logger');

		if (isset($options))
		{
			// Sort the options by name so that attributes are set before the template, which is
			// necessary to evaluate whether the template is safe
			ksort($options);

			foreach ($options as $optionName => $optionValue)
			{
				$this->__set($optionName, $optionValue);
			}
		}
	}

	/**
	* {@inheritdoc}
	*/
	public function asConfig()
	{
		$vars = get_object_vars($this);

		// Remove properties that are not needed during parsing
		unset($vars['defaultChildRule']);
		unset($vars['defaultDescendantRule']);
		unset($vars['template']);

		// If there are no attribute preprocessors defined, we can remove the step from this tag's
		// filterChain
		if (!count($this->attributePreprocessors))
		{
			$callback = 's9e\\TextFormatter\\Parser::executeAttributePreprocessors';

			// We operate on a copy of the filterChain, without modifying the original
			$filterChain = clone $vars['filterChain'];

			// Process the chain in reverse order so that we don't skip indices
			$i = count($filterChain);
			while (--$i >= 0)
			{
				if ($filterChain[$i]->getCallback() === $callback)
				{
					unset($filterChain[$i]);
				}
			}

			$vars['filterChain'] = $filterChain;
		}

		return ConfigHelper::toArray($vars);
	}

	/**
	* Return this tag's template
	*
	* @return Template
	*/
	public function getTemplate()
	{
		return $this->template;
	}

	/**
	* Test whether this tag has a template
	*
	* @return bool
	*/
	public function issetTemplate()
	{
		return isset($this->template);
	}

	/**
	* Set this tag's attribute preprocessors
	*
	* @param  array|AttributePreprocessorCollection $attributePreprocessors 2D array of [attrName=>[regexp]], or an instance of AttributePreprocessorCollection
	* @return void
	*/
	public function setAttributePreprocessors($attributePreprocessors)
	{
		$this->attributePreprocessors->clear();
		$this->attributePreprocessors->merge($attributePreprocessors);
	}

	/**
	* Set this tag's nestingLimit
	*
	* @param  integer $limit
	* @return void
	*/
	public function setNestingLimit($limit)
	{
		$limit = (int) $limit;

		if ($limit < 1)
		{
			throw new InvalidArgumentException('nestingLimit must be a number greater than 0');
		}

		$this->nestingLimit = $limit;
	}

	/**
	* Set this tag's rules
	*
	* @param  array|Ruleset $rules 2D array of rule definitions, or instance of Ruleset
	* @return void
	*/
	public function setRules($rules)
	{
		$this->rules->clear();
		$this->rules->merge($rules);
	}

	/**
	* Set this tag's tagLimit
	*
	* @param  integer $limit
	* @return void
	*/
	public function setTagLimit($limit)
	{
		$limit = (int) $limit;

		if ($limit < 1)
		{
			throw new InvalidArgumentException('tagLimit must be a number greater than 0');
		}

		$this->tagLimit = $limit;
	}

	/**
	* Set the template associated with this tag
	*
	* @param  string|Template $template
	* @return void
	*/
	public function setTemplate($template)
	{
		if (!($template instanceof Template))
		{
			$template = new Template($template);
		}

		$this->template = $template;
	}

	/**
	* Unset this tag's template
	*
	* @return void
	*/
	public function unsetTemplate()
	{
		unset($this->template);
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators;

use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\RendererGenerator;
use s9e\TextFormatter\Configurator\RendererGenerators\XSLT\Optimizer;
use s9e\TextFormatter\Configurator\Rendering;
use s9e\TextFormatter\Renderers\XSLT as XSLTRenderer;

class XSLT implements RendererGenerator
{
	/**
	* @var Optimizer
	*/
	public $optimizer;

	/**
	* Constructor
	*
	* @return void
	*/
	public function __construct()
	{
		$this->optimizer = new Optimizer;
	}

	/**
	* {@inheritdoc}
	*/
	public function getRenderer(Rendering $rendering)
	{
		return new XSLTRenderer($this->getXSL($rendering));
	}

	/**
	* Generate an XSL stylesheet based on given rendering configuration
	*
	* @param  Rendering $rendering
	* @return string
	*/
	public function getXSL(Rendering $rendering)
	{
		$groupedTemplates = [];
		$prefixes         = [];
		$templates        = $rendering->getTemplates();

		// Replace simple templates if there are at least 3 of them
		TemplateHelper::replaceHomogeneousTemplates($templates, 3);

		// Group tags with identical templates together
		foreach ($templates as $tagName => $template)
		{
			$template = $this->optimizer->optimizeTemplate($template);
			$groupedTemplates[$template][] = $tagName;

			// Record the tag's prefix if applicable
			$pos = strpos($tagName, ':');
			if ($pos !== false)
			{
				$prefixes[substr($tagName, 0, $pos)] = 1;
			}
		}

		// Declare all the namespaces in use at the top
		$xsl = '<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"';

		// Append the namespace declarations to the stylesheet
		$prefixes = array_keys($prefixes);
		sort($prefixes);
		foreach ($prefixes as $prefix)
		{
			$xsl .= ' xmlns:' . $prefix . '="urn:s9e:TextFormatter:' . $prefix . '"';
		}

		/**
		* Exclude those prefixes to keep the HTML neat
		*
		* @link http://lenzconsulting.com/namespaces-in-xslt/#exclude-result-prefixes
		*/
		if (!empty($prefixes))
		{
			$xsl .= ' exclude-result-prefixes="' . implode(' ', $prefixes) . '"';
		}

		// Start the stylesheet with the boilerplate stuff
		$xsl .= '><xsl:output method="html" encoding="utf-8" indent="no"';
		$xsl .= '/>';

		// Add stylesheet parameters
		foreach ($rendering->getAllParameters() as $paramName => $paramValue)
		{
			$xsl .= '<xsl:param name="' . htmlspecialchars($paramName) . '"';

			if ($paramValue === '')
			{
				$xsl .= '/>';
			}
			else
			{
				$xsl .= '>' . htmlspecialchars($paramValue) . '</xsl:param>';
			}
		}

		// Add templates
		foreach ($groupedTemplates as $template => $tagNames)
		{
			// Open the template element
			$xsl .= '<xsl:template match="' . implode('|', $tagNames) . '"';

			// Make it a self-closing element if the template is empty
			if ($template === '')
			{
				$xsl .= '/>';
			}
			else
			{
				$xsl .= '>' . $template . '</xsl:template>';
			}
		}

		$xsl .= '</xsl:stylesheet>';

		return $xsl;
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;

use ReflectionClass;
use RuntimeException;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Collections\TemplateParameterCollection;
use s9e\TextFormatter\Configurator\RendererGenerator;
use s9e\TextFormatter\Configurator\Traits\Configurable;

/**
* @property RendererGenerator $engine
* @property TemplateParameterCollection $parameters Parameters used by the renderer
*/
class Rendering
{
	use Configurable;

	/**
	* @var Configurator
	*/
	protected $configurator;

	/**
	* @var RendererGenerator
	*/
	protected $engine;

	/**
	* @var TemplateParameterCollection Parameters used by the renderer
	*/
	protected $parameters;

	/**
	* Constructor
	*
	* @param  Configurator $configurator
	* @return void
	*/
	public function __construct(Configurator $configurator)
	{
		$this->configurator = $configurator;
		$this->parameters   = new TemplateParameterCollection;

		$this->setEngine('XSLT');
	}

	/**
	* Get all the parameters defined and/or used in all the templates
	*
	* @return array Associative array of parameters names and their default value
	*/
	public function getAllParameters()
	{
		// Collect parameters used in template
		$params = [];
		foreach ($this->configurator->tags as $tag)
		{
			if (isset($tag->template))
			{
				foreach ($tag->template->getParameters() as $paramName)
				{
					$params[$paramName] = '';
				}
			}
		}

		// Merge defined parameters and those collected from templates. Defined parameters take
		// precedence
		$params = iterator_to_array($this->parameters) + $params;

		// Sort parameters by name for consistency
		ksort($params);

		return $params;
	}

	/**
	* Return an instance of Renderer based on the current config
	*
	* @return Renderer
	*/
	public function getRenderer()
	{
		return $this->engine->getRenderer($this);
	}

	/**
	* Get the templates defined in all the targs
	*
	* @return array Associative array of template names and content
	*/
	public function getTemplates()
	{
		$templates = [
			'br' => '<br/>',
			'e'  => '',
			'i'  => '',
			'p'  => '<p><xsl:apply-templates/></p>',
			's'  => ''
		];

		foreach ($this->configurator->tags as $tagName => $tag)
		{
			if (isset($tag->template))
			{
				$templates[$tagName] = (string) $tag->template;
			}
		}

		ksort($templates);

		return $templates;
	}

	/**
	* Set the RendererGenerator instance used
	*
	* NOTE: extra parameters are passed to the RendererGenerator's constructor
	*
	* @param  string|RendererGenerator $engine Engine name or instance of RendererGenerator
	* @return RendererGenerator                Instance of RendererGenerator
	*/
	public function setEngine($engine)
	{
		if (!($engine instanceof RendererGenerator))
		{
			$className  = 's9e\\TextFormatter\\Configurator\\RendererGenerators\\' . $engine;
			$reflection = new ReflectionClass($className);

			$engine = $reflection->newInstanceArgs(array_slice(func_get_args(), 1));
		}

		$this->engine = $engine;

		return $engine;
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;

use ArrayAccess;
use DOMDocument;
use Iterator;
use s9e\TextFormatter\Configurator\Collections\RulesGeneratorList;
use s9e\TextFormatter\Configurator\Collections\TagCollection;
use s9e\TextFormatter\Configurator\Helpers\TemplateForensics;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\BooleanRulesGenerator;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\TargetedRulesGenerator;
use s9e\TextFormatter\Configurator\Traits\CollectionProxy;

/**
* @method mixed   add(mixed $value, null $void)
* @method mixed   append(mixed $value)
* @method array   asConfig()
* @method void    clear()
* @method bool    contains(mixed $value)
* @method integer count()
* @method mixed   current()
* @method void    delete(string $key)
* @method bool    exists(string $key)
* @method mixed   get(string $key)
* @method mixed   indexOf(mixed $value)
* @method mixed   insert(integer $offset, mixed $value)
* @method integer|string key()
* @method mixed   next()
* @method integer normalizeKey(mixed $key)
* @method BooleanRulesGenerator|TargetedRulesGenerator normalizeValue(string|BooleanRulesGenerator|TargetedRulesGenerator $generator)
* @method bool    offsetExists(string|integer $offset)
* @method mixed   offsetGet(string|integer $offset)
* @method void    offsetSet(mixed $offset, mixed $value)
* @method void    offsetUnset(string|integer $offset)
* @method string  onDuplicate(string|null $action)
* @method mixed   prepend(mixed $value)
* @method integer remove(mixed $value)
* @method void    rewind()
* @method mixed   set(string $key, mixed $value)
* @method bool    valid()
*/
class RulesGenerator implements ArrayAccess, Iterator
{
	use CollectionProxy;

	/**
	* @var RulesGeneratorList Collection of objects
	*/
	protected $collection;

	/**
	* Constructor
	*
	* Will load the default rule generators
	*
	* @return void
	*/
	public function __construct()
	{
		$this->collection = new RulesGeneratorList;
		$this->collection->append('AutoCloseIfVoid');
		$this->collection->append('AutoReopenFormattingElements');
		$this->collection->append('BlockElementsFosterFormattingElements');
		$this->collection->append('DisableAutoLineBreaksIfNewLinesArePreserved');
		$this->collection->append('EnforceContentModels');
		$this->collection->append('EnforceOptionalEndTags');
		$this->collection->append('IgnoreTagsInCode');
		$this->collection->append('IgnoreTextIfDisallowed');
		$this->collection->append('IgnoreWhitespaceAroundBlockElements');
		$this->collection->append('TrimFirstLineInCodeBlocks');
	}

	/**
	* Generate rules for given tag collection
	*
	* Possible options:
	*
	*  parentHTML: HTML leading to the start of the rendered text. Defaults to "<div>"
	*
	* @param  TagCollection $tags    Tags collection
	* @param  array         $options Array of option settings
	* @return array
	*/
	public function getRules(TagCollection $tags, array $options = [])
	{
		// Unless specified otherwise, we consider that the renderered text will be displayed as
		// the child of a <div> element
		$parentHTML = (isset($options['parentHTML'])) ? $options['parentHTML'] : '<div>';

		// Create a proxy for the parent markup so that we can determine which tags are allowed at
		// the root of the text (IOW, with no parent) or even disabled altogether
		$rootForensics = $this->generateRootForensics($parentHTML);

		// Study the tags
		$templateForensics = [];
		foreach ($tags as $tagName => $tag)
		{
			// Use the tag's template if applicable or XSLT's implicit default otherwise
			$template = (isset($tag->template)) ? $tag->template : '<xsl:apply-templates/>';
			$templateForensics[$tagName] = new TemplateForensics($template);
		}

		// Generate a full set of rules
		$rules = $this->generateRulesets($templateForensics, $rootForensics);

		// Remove root rules that wouldn't be applied anyway
		unset($rules['root']['autoClose']);
		unset($rules['root']['autoReopen']);
		unset($rules['root']['breakParagraph']);
		unset($rules['root']['closeAncestor']);
		unset($rules['root']['closeParent']);
		unset($rules['root']['fosterParent']);
		unset($rules['root']['ignoreSurroundingWhitespace']);
		unset($rules['root']['isTransparent']);
		unset($rules['root']['requireAncestor']);
		unset($rules['root']['requireParent']);

		return $rules;
	}

	/**
	* Generate a TemplateForensics instance for the root element
	*
	* @param  string            $html Root HTML, e.g. "<div>"
	* @return TemplateForensics
	*/
	protected function generateRootForensics($html)
	{
		$dom = new DOMDocument;
		$dom->loadHTML($html);

		// Get the document's <body> element
		$body = $dom->getElementsByTagName('body')->item(0);

		// Grab the deepest node
		$node = $body;
		while ($node->firstChild)
		{
			$node = $node->firstChild;
		}

		// Now append an <xsl:apply-templates/> node to make the markup look like a normal template
		$node->appendChild($dom->createElementNS(
			'http://www.w3.org/1999/XSL/Transform',
			'xsl:apply-templates'
		));

		// Finally create and return a new TemplateForensics instance
		return new TemplateForensics($dom->saveXML($body));
	}

	/**
	* Generate and return rules based on a set of TemplateForensics
	*
	* @param  array             $templateForensics Array of [tagName => TemplateForensics]
	* @param  TemplateForensics $rootForensics     TemplateForensics for the root of the text
	* @return array
	*/
	protected function generateRulesets(array $templateForensics, TemplateForensics $rootForensics)
	{
		$rules = [
			'root' => $this->generateRuleset($rootForensics, $templateForensics),
			'tags' => []
		];

		foreach ($templateForensics as $tagName => $src)
		{
			$rules['tags'][$tagName] = $this->generateRuleset($src, $templateForensics);
		}

		return $rules;
	}

	/**
	* Generate a set of rules for a single TemplateForensics instance
	*
	* @param  TemplateForensics $src     Source of the rules
	* @param  array             $targets Array of [tagName => TemplateForensics]
	* @return array
	*/
	protected function generateRuleset(TemplateForensics $src, array $targets)
	{
		$rules = [];

		foreach ($this->collection as $rulesGenerator)
		{
			if ($rulesGenerator instanceof BooleanRulesGenerator)
			{
				foreach ($rulesGenerator->generateBooleanRules($src) as $ruleName => $bool)
				{
					$rules[$ruleName] = $bool;
				}
			}

			if ($rulesGenerator instanceof TargetedRulesGenerator)
			{
				foreach ($targets as $tagName => $trg)
				{
					foreach ($rulesGenerator->generateTargetedRules($src, $trg) as $ruleName)
					{
						$rules[$ruleName][] = $tagName;
					}
				}
			}
		}

		return $rules;
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RulesGenerators;

use s9e\TextFormatter\Configurator\Helpers\TemplateForensics;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\BooleanRulesGenerator;

class AutoCloseIfVoid implements BooleanRulesGenerator
{
	/**
	* {@inheritdoc}
	*/
	public function generateBooleanRules(TemplateForensics $src)
	{
		return ($src->isVoid()) ? ['autoClose' => true] : [];
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RulesGenerators;

use s9e\TextFormatter\Configurator\Helpers\TemplateForensics;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\BooleanRulesGenerator;

class AutoReopenFormattingElements implements BooleanRulesGenerator
{
	/**
	* {@inheritdoc}
	*/
	public function generateBooleanRules(TemplateForensics $src)
	{
		return ($src->isFormattingElement()) ? ['autoReopen' => true] : [];
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RulesGenerators;

use s9e\TextFormatter\Configurator\Helpers\TemplateForensics;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\TargetedRulesGenerator;

class BlockElementsFosterFormattingElements implements TargetedRulesGenerator
{
	/**
	* {@inheritdoc}
	*/
	public function generateTargetedRules(TemplateForensics $src, TemplateForensics $trg)
	{
		return ($src->isBlock() && $trg->isFormattingElement()) ? ['fosterParent'] : [];
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RulesGenerators;

use s9e\TextFormatter\Configurator\Helpers\TemplateForensics;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\BooleanRulesGenerator;

class DisableAutoLineBreaksIfNewLinesArePreserved implements BooleanRulesGenerator
{
	/**
	* {@inheritdoc}
	*/
	public function generateBooleanRules(TemplateForensics $src)
	{
		return ($src->preservesNewLines()) ? ['disableAutoLineBreaks' => true] : [];
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RulesGenerators;

use s9e\TextFormatter\Configurator\Helpers\TemplateForensics;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\BooleanRulesGenerator;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\TargetedRulesGenerator;

class EnforceContentModels implements BooleanRulesGenerator, TargetedRulesGenerator
{
	/**
	* @var TemplateForensics
	*/
	protected $br;

	/**
	* Constructor
	*
	* Prepares the TemplateForensics for <br/>
	*
	* @return void
	*/
	public function __construct()
	{
		$this->br = new TemplateForensics('<br/>');
	}

	/**
	* {@inheritdoc}
	*/
	public function generateBooleanRules(TemplateForensics $src)
	{
		$rules = [];

		if ($src->isTransparent())
		{
			$rules['isTransparent'] = true;
		}

		if (!$src->allowsChild($this->br))
		{
			$rules['preventLineBreaks'] = true;
			$rules['suspendAutoLineBreaks'] = true;
		}

		if (!$src->allowsDescendant($this->br))
		{
			$rules['disableAutoLineBreaks'] = true;
			$rules['preventLineBreaks'] = true;
		}

		return $rules;
	}

	/**
	* {@inheritdoc}
	*/
	public function generateTargetedRules(TemplateForensics $src, TemplateForensics $trg)
	{
		$rules = [];

		if (!$src->allowsChild($trg))
		{
			$rules[] = 'denyChild';
		}

		if (!$src->allowsDescendant($trg))
		{
			$rules[] = 'denyDescendant';
		}

		return $rules;
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RulesGenerators;

use s9e\TextFormatter\Configurator\Helpers\TemplateForensics;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\TargetedRulesGenerator;

class EnforceOptionalEndTags implements TargetedRulesGenerator
{
	/**
	* {@inheritdoc}
	*/
	public function generateTargetedRules(TemplateForensics $src, TemplateForensics $trg)
	{
		return ($src->closesParent($trg)) ? ['closeParent'] : [];
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RulesGenerators;

use DOMXPath;
use s9e\TextFormatter\Configurator\Helpers\TemplateForensics;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\BooleanRulesGenerator;

class IgnoreTagsInCode implements BooleanRulesGenerator
{
	/**
	* {@inheritdoc}
	*/
	public function generateBooleanRules(TemplateForensics $src)
	{
		$xpath = new DOMXPath($src->getDOM());

		if ($xpath->evaluate('count(//code//xsl:apply-templates)'))
		{
			return ['ignoreTags' => true];
		}

		return [];
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RulesGenerators;

use s9e\TextFormatter\Configurator\Helpers\TemplateForensics;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\BooleanRulesGenerator;

class IgnoreTextIfDisallowed implements BooleanRulesGenerator
{
	/**
	* {@inheritdoc}
	*/
	public function generateBooleanRules(TemplateForensics $src)
	{
		return ($src->allowsText()) ? [] : ['ignoreText' => true];
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RulesGenerators;

use s9e\TextFormatter\Configurator\Helpers\TemplateForensics;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\BooleanRulesGenerator;

class IgnoreWhitespaceAroundBlockElements implements BooleanRulesGenerator
{
	/**
	* {@inheritdoc}
	*/
	public function generateBooleanRules(TemplateForensics $src)
	{
		return ($src->isBlock()) ? ['ignoreSurroundingWhitespace' => true] : [];
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RulesGenerators;

use DOMXPath;
use s9e\TextFormatter\Configurator\Helpers\TemplateForensics;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\BooleanRulesGenerator;

class TrimFirstLineInCodeBlocks implements BooleanRulesGenerator
{
	/**
	* {@inheritdoc}
	*/
	public function generateBooleanRules(TemplateForensics $src)
	{
		$rules = [];
		$xpath = new DOMXPath($src->getDOM());
		if ($xpath->evaluate('count(//pre//code//xsl:apply-templates)') > 0)
		{
			$rules['trimFirstLine'] = true;
		}

		return $rules;
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;

use ArrayAccess;
use Iterator;
use s9e\TextFormatter\Configurator\Collections\TemplateCheckList;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\Items\UnsafeTemplate;
use s9e\TextFormatter\Configurator\TemplateChecks\DisallowElementNS;
use s9e\TextFormatter\Configurator\TemplateChecks\DisallowXPathFunction;
use s9e\TextFormatter\Configurator\TemplateChecks\RestrictFlashScriptAccess;
use s9e\TextFormatter\Configurator\Traits\CollectionProxy;

/**
* @method mixed   add(mixed $value, null $void)
* @method mixed   append(mixed $value)
* @method array   asConfig()
* @method void    clear()
* @method bool    contains(mixed $value)
* @method integer count()
* @method mixed   current()
* @method void    delete(string $key)
* @method bool    exists(string $key)
* @method mixed   get(string $key)
* @method mixed   indexOf(mixed $value)
* @method mixed   insert(integer $offset, mixed $value)
* @method integer|string key()
* @method mixed   next()
* @method integer normalizeKey(mixed $key)
* @method TemplateCheck normalizeValue(mixed $check)
* @method bool    offsetExists(string|integer $offset)
* @method mixed   offsetGet(string|integer $offset)
* @method void    offsetSet(mixed $offset, mixed $value)
* @method void    offsetUnset(string|integer $offset)
* @method string  onDuplicate(string|null $action)
* @method mixed   prepend(mixed $value)
* @method integer remove(mixed $value)
* @method void    rewind()
* @method mixed   set(string $key, mixed $value)
* @method bool    valid()
*/
class TemplateChecker implements ArrayAccess, Iterator
{
	use CollectionProxy;

	/**
	* @var TemplateCheckList Collection of TemplateCheck instances
	*/
	protected $collection;

	/**
	* @var bool Whether checks are currently disabled
	*/
	protected $disabled = false;

	/**
	* Constructor
	*
	* Will load the default checks
	*
	* @return void
	*/
	public function __construct()
	{
		$this->collection = new TemplateCheckList;
		$this->collection->append('DisallowAttributeSets');
		$this->collection->append('DisallowCopy');
		$this->collection->append('DisallowDisableOutputEscaping');
		$this->collection->append('DisallowDynamicAttributeNames');
		$this->collection->append('DisallowDynamicElementNames');
		$this->collection->append('DisallowObjectParamsWithGeneratedName');
		$this->collection->append('DisallowPHPTags');
		$this->collection->append('DisallowUnsafeCopyOf');
		$this->collection->append('DisallowUnsafeDynamicCSS');
		$this->collection->append('DisallowUnsafeDynamicJS');
		$this->collection->append('DisallowUnsafeDynamicURL');
		$this->collection->append(new DisallowElementNS('http://icl.com/saxon', 'output'));
		$this->collection->append(new DisallowXPathFunction('document'));
		$this->collection->append(new RestrictFlashScriptAccess('sameDomain', true));
	}

	/**
	* Check a given tag's templates for disallowed content
	*
	* @param  Tag  $tag Tag whose templates will be checked
	* @return void
	*/
	public function checkTag(Tag $tag)
	{
		if (isset($tag->template) && !($tag->template instanceof UnsafeTemplate))
		{
			$template = (string) $tag->template;
			$this->checkTemplate($template, $tag);
		}
	}

	/**
	* Check a given template for disallowed content
	*
	* @param  string $template Template
	* @param  Tag    $tag      Tag this template belongs to
	* @return void
	*/
	public function checkTemplate($template, Tag $tag = null)
	{
		if ($this->disabled)
		{
			return;
		}

		if (!isset($tag))
		{
			$tag = new Tag;
		}

		// Load the template into a DOMDocument
		$dom = TemplateHelper::loadTemplate($template);

		foreach ($this->collection as $check)
		{
			$check->check($dom->documentElement, $tag);
		}
	}

	/**
	* Disable all checks
	*
	* @return void
	*/
	public function disable()
	{
		$this->disabled = true;
	}

	/**
	* Enable all checks
	*
	* @return void
	*/
	public function enable()
	{
		$this->disabled = false;
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;

use DOMAttr;
use DOMElement;
use DOMNode;
use DOMXPath;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;
use s9e\TextFormatter\Configurator\Items\Attribute;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;

abstract class AbstractDynamicContentCheck extends TemplateCheck
{
	/**
	* @var bool Whether to ignore unknown attributes
	*/
	protected $ignoreUnknownAttributes = false;

	/**
	* Get the nodes targeted by this check
	*
	* @param  DOMElement $template <xsl:template/> node
	* @return array             Array of DOMElement instances
	*/
	abstract protected function getNodes(DOMElement $template);

	/**
	* Return whether an attribute is considered safe
	*
	* @param  Attribute $attribute Attribute
	* @return bool
	*/
	abstract protected function isSafe(Attribute $attribute);

	/**
	* Look for improperly-filtered dynamic content
	*
	* @param  DOMElement $template <xsl:template/> node
	* @param  Tag        $tag      Tag this template belongs to
	* @return void
	*/
	public function check(DOMElement $template, Tag $tag)
	{
		foreach ($this->getNodes($template) as $node)
		{
			// Test this node's safety
			$this->checkNode($node, $tag);
		}
	}

	/**
	* Configure this template check to detect unknown attributes
	*
	* @return void
	*/
	public function detectUnknownAttributes()
	{
		$this->ignoreUnknownAttributes = false;
	}

	/**
	* Configure this template check to ignore unknown attributes
	*
	* @return void
	*/
	public function ignoreUnknownAttributes()
	{
		$this->ignoreUnknownAttributes = true;
	}

	/**
	* Test whether a tag attribute is safe
	*
	* @param  DOMNode $node     Context node
	* @param  Tag     $tag      Source tag
	* @param  string  $attrName Name of the attribute
	* @return void
	*/
	protected function checkAttribute(DOMNode $node, Tag $tag, $attrName)
	{
		// Test whether the attribute exists
		if (!isset($tag->attributes[$attrName]))
		{
			if ($this->ignoreUnknownAttributes)
			{
				return;
			}

			throw new UnsafeTemplateException("Cannot assess the safety of unknown attribute '" . $attrName . "'", $node);
		}

		// Test whether the attribute is safe to be used in this content type
		if (!$this->tagFiltersAttributes($tag) || !$this->isSafe($tag->attributes[$attrName]))
		{
			throw new UnsafeTemplateException("Attribute '" . $attrName . "' is not properly sanitized to be used in this context", $node);
		}
	}

	/**
	* Test whether an attribute node is safe
	*
	* @param  DOMAttr $attribute Attribute node
	* @param  Tag     $tag       Reference tag
	* @return void
	*/
	protected function checkAttributeNode(DOMAttr $attribute, Tag $tag)
	{
		// Parse the attribute value for XPath expressions and assess their safety
		foreach (AVTHelper::parse($attribute->value) as $token)
		{
			if ($token[0] === 'expression')
			{
				$this->checkExpression($attribute, $token[1], $tag);
			}
		}
	}

	/**
	* Test whether a node's context can be safely assessed
	*
	* @param  DOMNode $node Source node
	* @return void
	*/
	protected function checkContext(DOMNode $node)
	{
		// Test whether we know in what context this node is used. An <xsl:for-each/> ancestor would // change this node's context
		$xpath     = new DOMXPath($node->ownerDocument);
		$ancestors = $xpath->query('ancestor::xsl:for-each', $node);

		if ($ancestors->length)
		{
			throw new UnsafeTemplateException("Cannot assess context due to '" . $ancestors->item(0)->nodeName . "'", $node);
		}
	}

	/**
	* Test whether an <xsl:copy-of/> node is safe
	*
	* @param  DOMElement $node <xsl:copy-of/> node
	* @param  Tag        $tag  Reference tag
	* @return void
	*/
	protected function checkCopyOfNode(DOMElement $node, Tag $tag)
	{
		$this->checkSelectNode($node->getAttributeNode('select'), $tag);
	}

	/**
	* Test whether an element node is safe
	*
	* @param  DOMElement $element Element
	* @param  Tag        $tag     Reference tag
	* @return void
	*/
	protected function checkElementNode(DOMElement $element, Tag $tag)
	{
		$xpath = new DOMXPath($element->ownerDocument);

		// If current node is not an <xsl:attribute/> element, we exclude descendants
		// with an <xsl:attribute/> ancestor so that content such as:
		//   <script><xsl:attribute name="id"><xsl:value-of/></xsl:attribute></script>
		// would not trigger a false-positive due to the presence of an <xsl:value-of/>
		// element in a <script>
		$predicate = ($element->localName === 'attribute') ? '' : '[not(ancestor::xsl:attribute)]';

		// Test the select expression of <xsl:value-of/> nodes
		$query = './/xsl:value-of' . $predicate;
		foreach ($xpath->query($query, $element) as $valueOf)
		{
			$this->checkSelectNode($valueOf->getAttributeNode('select'), $tag);
		}

		// Reject all <xsl:apply-templates/> nodes
		$query = './/xsl:apply-templates' . $predicate;
		foreach ($xpath->query($query, $element) as $applyTemplates)
		{
			throw new UnsafeTemplateException('Cannot allow unfiltered data in this context', $applyTemplates);
		}
	}

	/**
	* Test the safety of an XPath expression
	*
	* @param  DOMNode $node Source node
	* @param  string  $expr XPath expression
	* @param  Tag     $tag  Source tag
	* @return void
	*/
	protected function checkExpression(DOMNode $node, $expr, Tag $tag)
	{
		$this->checkContext($node);

		// Consider stylesheet parameters safe but test local variables/params
		if (preg_match('/^\\$(\\w+)$/', $expr, $m))
		{
			$this->checkVariable($node, $tag, $m[1]);

			// Either this expression came from a variable that is considered safe, or it's a
			// stylesheet parameters, which are considered safe by default
			return;
		}

		// Test whether the expression is safe as per the concrete implementation
		if ($this->isExpressionSafe($expr))
		{
			return;
		}

		// Test whether the expression contains one single attribute
		if (preg_match('/^@(\\w+)$/', $expr, $m))
		{
			$this->checkAttribute($node, $tag, $m[1]);

			return;
		}

		throw new UnsafeTemplateException("Cannot assess the safety of expression '" . $expr . "'", $node);
	}

	/**
	* Test whether a node is safe
	*
	* @param  DOMNode $node Source node
	* @param  Tag     $tag  Reference tag
	* @return void
	*/
	protected function checkNode(DOMNode $node, Tag $tag)
	{
		if ($node instanceof DOMAttr)
		{
			$this->checkAttributeNode($node, $tag);
		}
		elseif ($node instanceof DOMElement)
		{
			if ($node->namespaceURI === self::XMLNS_XSL
			 && $node->localName    === 'copy-of')
			{
				$this->checkCopyOfNode($node, $tag);
			}
			else
			{
				$this->checkElementNode($node, $tag);
			}
		}
	}

	/**
	* Check whether a variable is safe in context
	*
	* @param  DOMNode $node  Context node
	* @param  Tag     $tag   Source tag
	* @param  string  $qname Name of the variable
	* @return void
	*/
	protected function checkVariable(DOMNode $node, $tag, $qname)
	{
		// Test whether this variable comes from a previous xsl:param or xsl:variable element
		$this->checkVariableDeclaration($node, $tag, 'xsl:param[@name="' . $qname . '"]');
		$this->checkVariableDeclaration($node, $tag, 'xsl:variable[@name="' . $qname . '"]');
	}

	/**
	* Check whether a variable declaration is safe in context
	*
	* @param  DOMNode $node  Context node
	* @param  Tag     $tag   Source tag
	* @param  string  $query XPath query
	* @return void
	*/
	protected function checkVariableDeclaration(DOMNode $node, $tag, $query)
	{
		$query = 'ancestor-or-self::*/preceding-sibling::' . $query . '[@select]';
		$xpath = new DOMXPath($node->ownerDocument);
		foreach ($xpath->query($query, $node) as $varNode)
		{
			// Intercept the UnsafeTemplateException and change the node to the one we're
			// really checking before rethrowing it
			try
			{
				$this->checkExpression($varNode, $varNode->getAttribute('select'), $tag);
			}
			catch (UnsafeTemplateException $e)
			{
				$e->setNode($node);

				throw $e;
			}
		}
	}

	/**
	* Test whether a select attribute of a node is safe
	*
	* @param  DOMAttr $select Select attribute node
	* @param  Tag     $tag    Reference tag
	* @return void
	*/
	protected function checkSelectNode(DOMAttr $select, Tag $tag)
	{
		$this->checkExpression($select, $select->value, $tag);
	}

	/**
	* Test whether given expression is safe in context
	*
	* @param  string $expr XPath expression
	* @return bool         Whether the expression is safe in context
	*/
	protected function isExpressionSafe($expr)
	{
		return false;
	}

	/**
	* Test whether given tag filters attribute values
	*
	* @param  Tag  $tag
	* @return bool
	*/
	protected function tagFiltersAttributes(Tag $tag)
	{
		return $tag->filterChain->containsCallback('s9e\\TextFormatter\\Parser::filterAttributes');
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;

use DOMElement;
use DOMNode;
use DOMXPath;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;

/**
* NOTE: when this check is enabled, DisallowObjectParamsWithGeneratedName should be enabled too.
*       Otherwise, <param/> elements with a dynamic 'name' attribute could be used to bypass this
*       restriction. For the same reason, DisallowCopy, DisallowDisableOutputEscaping,
*       DisallowDynamicAttributeNames, DisallowDynamicElementNames and DisallowUnsafeCopyOf should
*       all be enabled too
*/
abstract class AbstractFlashRestriction extends TemplateCheck
{
	/**
	* @var string Name of the default setting
	*/
	public $defaultSetting;

	/**
	* @var string Name of the highest setting allowed
	*/
	public $maxSetting;

	/**
	* @var bool Whether this restriction applies only to elements using any kind of dynamic markup:
	*           XSL elements or attribute value templates
	*/
	public $onlyIfDynamic;

	/**
	* @var string Name of the restricted setting
	*/
	protected $settingName;

	/**
	* @var array Valid settings
	*/
	protected $settings;

	/**
	* @var DOMElement <xsl:template/> node
	*/
	protected $template;

	/**
	* Constructor
	*
	* @param  string $maxSetting    Max setting allowed
	* @param  bool   $onlyIfDynamic Whether this restriction applies only to elements using any kind
	*                               of dynamic markup: XSL elements or attribute value templates
	* @return void
	*/
	public function __construct($maxSetting, $onlyIfDynamic = false)
	{
		$this->maxSetting    = $maxSetting;
		$this->onlyIfDynamic = $onlyIfDynamic;
	}

	/**
	* Test for the set Flash restriction
	*
	* @param  DOMElement $template <xsl:template/> node
	* @param  Tag        $tag      Tag this template belongs to
	* @return void
	*/
	public function check(DOMElement $template, Tag $tag)
	{
		$this->template = $template;
		$this->checkEmbeds();
		$this->checkObjects();
	}

	/**
	* Test given element's attributes
	*
	* @param  DOMElement $embed Context element
	* @return void
	*/
	protected function checkAttributes(DOMElement $embed)
	{
		$settingName = strtolower($this->settingName);
		$useDefault  = true;
		foreach ($embed->attributes as $attribute)
		{
			$attrName = strtolower($attribute->name);
			if ($attrName === $settingName)
			{
				$this->checkSetting($attribute, $attribute->value);
				$useDefault = false;
			}
		}
		if ($useDefault)
		{
			$this->checkSetting($embed, $this->defaultSetting);
		}
	}

	/**
	* Test whether given element has dynamic attributes that match the setting's name
	*
	* @param  DOMElement $embed Context element
	* @return void
	*/
	protected function checkDynamicAttributes(DOMElement $embed)
	{
		$settingName = strtolower($this->settingName);
		foreach ($embed->getElementsByTagNameNS(self::XMLNS_XSL, 'attribute') as $attribute)
		{
			$attrName = strtolower($attribute->getAttribute('name'));
			if ($attrName === $settingName)
			{
				throw new UnsafeTemplateException('Cannot assess the safety of dynamic attributes', $attribute);
			}
		}
	}

	/**
	* Test the presence of dynamic params in given object
	*
	* @param  DOMElement $object Context element
	* @return void
	*/
	protected function checkDynamicParams(DOMElement $object)
	{
		foreach ($this->getObjectParams($object) as $param)
		{
			foreach ($param->getElementsByTagNameNS(self::XMLNS_XSL, 'attribute') as $attribute)
			{
				// Test for a dynamic "value" attribute
				if (strtolower($attribute->getAttribute('name')) === 'value')
				{
					throw new UnsafeTemplateException('Cannot assess the safety of dynamic attributes', $attribute);
				}
			}
		}
	}

	/**
	* Check embed elements in given template
	*
	* @return void
	*/
	protected function checkEmbeds()
	{
		foreach ($this->getElements('embed') as $embed)
		{
			// Test <xsl:attribute/> descendants
			$this->checkDynamicAttributes($embed);

			// Test the element's attributes
			$this->checkAttributes($embed);
		}
	}

	/**
	* Check object elements in given template
	*
	* @return void
	*/
	protected function checkObjects()
	{
		foreach ($this->getElements('object') as $object)
		{
			// Make sure this object doesn't have dynamic params
			$this->checkDynamicParams($object);

			// Test the element's <param/> descendants
			$params = $this->getObjectParams($object);
			foreach ($params as $param)
			{
				$this->checkSetting($param, $param->getAttribute('value'));
			}
			if (empty($params))
			{
				$this->checkSetting($object, $this->defaultSetting);
			}
		}
	}

	/**
	* Test whether given setting is allowed
	*
	* @param  DOMNode $node    Target node
	* @param  string  $setting Setting
	* @return void
	*/
	protected function checkSetting(DOMNode $node, $setting)
	{
		if (!isset($this->settings[strtolower($setting)]))
		{
			// Test whether the value contains an odd number of {
			if (preg_match('/(?<!\\{)\\{(?:\\{\\{)*(?!\\{)/', $setting))
			{
				throw new UnsafeTemplateException('Cannot assess ' . $this->settingName . " setting '" . $setting . "'", $node);
			}

			throw new UnsafeTemplateException('Unknown ' . $this->settingName . " value '" . $setting . "'", $node);
		}

		$value    = $this->settings[strtolower($setting)];
		$maxValue = $this->settings[strtolower($this->maxSetting)];

		if ($value > $maxValue)
		{
			throw new UnsafeTemplateException($this->settingName . " setting '" . $setting . "' exceeds restricted value '" . $this->maxSetting . "'", $node);
		}
	}

	/**
	* Test whether given node contains dynamic content (XSL elements or attribute value template)
	*
	* @param  DOMElement $node Node
	* @return bool
	*/
	protected function isDynamic(DOMElement $node)
	{
		if ($node->getElementsByTagNameNS(self::XMLNS_XSL, '*')->length)
		{
			return true;
		}

		// Look for any attributes containing "{" in this element or its descendants
		$xpath = new DOMXPath($node->ownerDocument);
		$query = './/@*[contains(., "{")]';

		foreach ($xpath->query($query, $node) as $attribute)
		{
			// Test whether the value contains an odd number of {
			if (preg_match('/(?<!\\{)\\{(?:\\{\\{)*(?!\\{)/', $attribute->value))
			{
				return true;
			}
		}

		return false;
	}

	/**
	* Get all elements the restriction applies to
	*
	* @param  string       $tagName Element's name
	* @return DOMElement[]
	*/
	protected function getElements($tagName)
	{
		$nodes = [];
		foreach ($this->template->ownerDocument->getElementsByTagName($tagName) as $node)
		{
			if (!$this->onlyIfDynamic || $this->isDynamic($node))
			{
				$nodes[] = $node;
			}
		}

		return $nodes;
	}

	/**
	* Get all param elements attached to given object
	*
	* @param  DOMElement   $object Context element
	* @return DOMElement[]
	*/
	protected function getObjectParams(DOMElement $object)
	{
		$params      = [];
		$settingName = strtolower($this->settingName);
		foreach ($object->getElementsByTagName('param') as $param)
		{
			$paramName = strtolower($param->getAttribute('name'));
			if ($paramName === $settingName && $param->parentNode->isSameNode($object))
			{
				$params[] = $param;
			}
		}

		return $params;
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;

use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;

class DisallowAttributeSets extends TemplateCheck
{
	/**
	* Test whether the template contains an <xsl:attribute-set/>
	*
	* Templates are checked outside of their stylesheet, which means we don't have access to the
	* <xsl:attribute-set/> declarations and we can't easily test them. Attribute sets are fairly
	* uncommon and there's little incentive to use them in small stylesheets
	*
	* @param  DOMElement $template <xsl:template/> node
	* @param  Tag        $tag      Tag this template belongs to
	* @return void
	*/
	public function check(DOMElement $template, Tag $tag)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		$nodes = $xpath->query('//@use-attribute-sets');

		if ($nodes->length)
		{
			throw new UnsafeTemplateException('Cannot assess the safety of attribute sets', $nodes->item(0));
		}
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;

use DOMElement;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;

class DisallowCopy extends TemplateCheck
{
	/**
	* Check for <xsl:copy/> elements
	*
	* @param  DOMElement $template <xsl:template/> node
	* @param  Tag        $tag      Tag this template belongs to
	* @return void
	*/
	public function check(DOMElement $template, Tag $tag)
	{
		$nodes = $template->getElementsByTagNameNS(self::XMLNS_XSL, 'copy');
		$node  = $nodes->item(0);

		if ($node)
		{
			throw new UnsafeTemplateException("Cannot assess the safety of an '" . $node->nodeName . "' element", $node);
		}
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;

use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;

class DisallowDisableOutputEscaping extends TemplateCheck
{
	/**
	* Check a template for any tag using @disable-output-escaping
	*
	* @param  DOMElement $template <xsl:template/> node
	* @param  Tag        $tag      Tag this template belongs to
	* @return void
	*/
	public function check(DOMElement $template, Tag $tag)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		$node  = $xpath->query('//@disable-output-escaping')->item(0);

		if ($node)
		{
			throw new UnsafeTemplateException("The template contains a 'disable-output-escaping' attribute", $node);
		}
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;

use DOMElement;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;

class DisallowDynamicAttributeNames extends TemplateCheck
{
	/**
	* Test for the presence of an <xsl:attribute/> node using a dynamic name
	*
	* @param  DOMElement $template <xsl:template/> node
	* @param  Tag        $tag      Tag this template belongs to
	* @return void
	*/
	public function check(DOMElement $template, Tag $tag)
	{
		$nodes = $template->getElementsByTagNameNS(self::XMLNS_XSL, 'attribute');
		foreach ($nodes as $node)
		{
			if (strpos($node->getAttribute('name'), '{') !== false)
			{
				throw new UnsafeTemplateException('Dynamic <xsl:attribute/> names are disallowed', $node);
			}
		}
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;

use DOMElement;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;

class DisallowDynamicElementNames extends TemplateCheck
{
	/**
	* Test for the presence of an <xsl:element/> node using a dynamic name
	*
	* @param  DOMElement $template <xsl:template/> node
	* @param  Tag        $tag      Tag this template belongs to
	* @return void
	*/
	public function check(DOMElement $template, Tag $tag)
	{
		$nodes = $template->getElementsByTagNameNS(self::XMLNS_XSL, 'element');
		foreach ($nodes as $node)
		{
			if (strpos($node->getAttribute('name'), '{') !== false)
			{
				throw new UnsafeTemplateException('Dynamic <xsl:element/> names are disallowed', $node);
			}
		}
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;

use DOMElement;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;

class DisallowElementNS extends TemplateCheck
{
	/**
	* @var string Local name of the disallowed element
	*/
	public $elName;

	/**
	* @var string Namespace URI of the disallowed element
	*/
	public $namespaceURI;

	/**
	* Constructor
	*
	* @param  string $namespaceURI Namespace URI of the disallowed element
	* @param  string $elName       Local name of the disallowed element
	* @return void
	*/
	public function __construct($namespaceURI, $elName)
	{
		$this->namespaceURI  = $namespaceURI;
		$this->elName        = $elName;
	}

	/**
	* Test for the presence of an element of given name in given namespace
	*
	* @param  DOMElement $template <xsl:template/> node
	* @param  Tag        $tag      Tag this template belongs to
	* @return void
	*/
	public function check(DOMElement $template, Tag $tag)
	{
		$node = $template->getElementsByTagNameNS($this->namespaceURI, $this->elName)->item(0);

		if ($node)
		{
			throw new UnsafeTemplateException("Element '" . $node->nodeName . "' is disallowed", $node);
		}
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;

use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;

class DisallowObjectParamsWithGeneratedName extends TemplateCheck
{
	/**
	* Check for <param> elements with a generated "name" attribute
	*
	* This check will reject <param> elements whose "name" attribute is generated by an
	* <xsl:attribute/> element. This is a setup that has no practical use and should be eliminated
	* because it makes it much harder to check the param's name, and therefore infer the type of
	* content it expects
	*
	* @param  DOMElement $template <xsl:template/> node
	* @param  Tag        $tag      Tag this template belongs to
	* @return void
	*/
	public function check(DOMElement $template, Tag $tag)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		$query = '//object//param[contains(@name, "{") or .//xsl:attribute[translate(@name, "NAME", "name") = "name"]]';
		$nodes = $xpath->query($query);

		foreach ($nodes as $node)
		{
			throw new UnsafeTemplateException("A 'param' element with a suspect name has been found", $node);
		}
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;

use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;

class DisallowPHPTags extends TemplateCheck
{
	/**
	* Prevent PHP tags from appearing in the stylesheet or in renderings
	*
	* Targets <?php tags as well as <script language="php">. Cannot target short tags or ASP tags.
	* Assumes that element names and attribute names are normalized to lowercase by the template
	* normalizer. Does not cover script elements in the output, dynamic xsl:element names are
	* handled by DisallowDynamicElementNames.
	*
	* NOTE: PHP tags have no effect in templates or in renderings, they are removed on the remote
	*       chance of being used as a vector, for example if a template is saved in a publicly
	*       accessible file that the webserver is somehow configured to process as PHP, or if the
	*       output is saved in a file (e.g. for static archives) that is parsed by PHP
	*
	* @param  DOMElement $template <xsl:template/> node
	* @param  Tag        $tag      Tag this template belongs to
	* @return void
	*/
	public function check(DOMElement $template, Tag $tag)
	{
		$queries = [
			'//processing-instruction()["php" = translate(name(),"HP","hp")]'
				=> 'PHP tags are not allowed in the template',

			'//script["php" = translate(@language,"HP","hp")]'
				=> 'PHP tags are not allowed in the template',

			'//xsl:processing-instruction["php" = translate(@name,"HP","hp")]'
				=> 'PHP tags are not allowed in the output',

			'//xsl:processing-instruction[contains(@name, "{")]'
				=> 'Dynamic processing instructions are not allowed',
		];

		$xpath = new DOMXPath($template->ownerDocument);
		foreach ($queries as $query => $error)
		{
			$nodes = $xpath->query($query); 

			if ($nodes->length)
			{
				throw new UnsafeTemplateException($error, $nodes->item(0));
			}
		}
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;

use DOMElement;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;

class DisallowUnsafeCopyOf extends TemplateCheck
{
	/**
	* Check for unsafe <xsl:copy-of/> elements
	*
	* Any select expression that is not a single attribute is considered unsafe
	*
	* @param  DOMElement $template <xsl:template/> node
	* @param  Tag        $tag      Tag this template belongs to
	* @return void
	*/
	public function check(DOMElement $template, Tag $tag)
	{
		$nodes = $template->getElementsByTagNameNS(self::XMLNS_XSL, 'copy-of');

		foreach ($nodes as $node)
		{
			$expr = $node->getAttribute('select');

			if (!preg_match('#^@[-\\w]*$#D', $expr))
			{
				throw new UnsafeTemplateException("Cannot assess the safety of '" . $node->nodeName . "' select expression '" . $expr . "'", $node);
			}
		}
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;

use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;

class DisallowXPathFunction extends TemplateCheck
{
	/**
	* @var string Name of the disallowed function
	*/
	public $funcName;

	/**
	* Constructor
	*
	* @param  string $funcName Name of the disallowed function
	* @return void
	*/
	public function __construct($funcName)
	{
		$this->funcName = $funcName;
	}

	/**
	* Test for the presence of given XPath function
	*
	* @param  DOMElement $template <xsl:template/> node
	* @param  Tag        $tag      Tag this template belongs to
	* @return void
	*/
	public function check(DOMElement $template, Tag $tag)
	{
		// Regexp that matches the function call
		$regexp = '#(?!<\\pL)' . preg_quote($this->funcName, '#') . '\\s*\\(#iu';

		// Allow whitespace around colons (NOTE: colons are unnecessarily escaped by preg_quote())
		$regexp = str_replace('\\:', '\\s*:\\s*', $regexp);

		foreach ($this->getExpressions($template) as $expr => $node)
		{
			// Remove string literals from the expression
			$expr = preg_replace('#([\'"]).*?\\1#s', '', $expr);

			// Test whether the expression contains a document() call
			if (preg_match($regexp, $expr))
			{
				throw new UnsafeTemplateException('An XPath expression uses the ' . $this->funcName . '() function', $node);
			}
		}
	}

	/**
	* Get all the potential XPath expressions used in given template
	*
	* @param  DOMElement $template <xsl:template/> node
	* @return array                XPath expression as key, reference node as value
	*/
	protected function getExpressions(DOMElement $template)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		$exprs = [];

		foreach ($xpath->query('//@*') as $attribute)
		{
			if ($attribute->parentNode->namespaceURI === self::XMLNS_XSL)
			{
				// Attribute of an XSL element. May or may not use XPath, but it shouldn't produce
				// false-positives
				$expr = $attribute->value;
				$exprs[$expr] = $attribute;
			}
			else
			{
				// Attribute of an HTML (or otherwise) element -- Look for inline expressions
				foreach (AVTHelper::parse($attribute->value) as $token)
				{
					if ($token[0] === 'expression')
					{
						$exprs[$token[1]] = $attribute;
					}
				}
			}
		}

		return $exprs;
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMAttr;
use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;

class FixUnescapedCurlyBracesInHtmlAttributes extends TemplateNormalization
{
	/**
	* Fix unescaped curly braces in HTML attributes
	*
	* Will replace
	*     <hr onclick="if(1){alert(1)}">
	* with
	*     <hr onclick="if(1){{alert(1)}">
	*
	* @param  DOMElement $template <xsl:template/> node
	* @return void
	*/
	public function normalize(DOMElement $template)
	{
		$dom   = $template->ownerDocument;
		$xpath = new DOMXPath($dom);
		$query = '//@*[contains(., "{")]';
		foreach ($xpath->query($query) as $attribute)
		{
			$this->fixAttribute($attribute);
		}
	}

	/**
	* Fix unescaped braces in give attribute
	*
	* @param  DOMAttr $attribute
	* @return void
	*/
	protected function fixAttribute(DOMAttr $attribute)
	{
		$parentNode = $attribute->parentNode;

		// Skip XSL elements
		if ($parentNode->namespaceURI === self::XMLNS_XSL)
		{
			return;
		}

		$attribute->value = htmlspecialchars(
			preg_replace(
				'(\\b(?:do|else|(?:if|while)\\s*\\(.*?\\))\\s*\\{(?![{@]))',
				'$0{',
				$attribute->value
			),
			ENT_NOQUOTES,
			'UTF-8'
		);
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMElement;
use DOMException;
use DOMText;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;

class InlineAttributes extends TemplateNormalization
{
	/**
	* Inline the attribute declarations of a template
	*
	* Will replace
	*     <a><xsl:attribute name="href"><xsl:value-of select="@url"/></xsl:attribute>...</a>
	* with
	*     <a href="{@url}">...</a>
	*
	* @param  DOMElement $template <xsl:template/> node
	* @return void
	*/
	public function normalize(DOMElement $template)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		$query = '//*[namespace-uri() != "' . self::XMLNS_XSL . '"]/xsl:attribute';
		foreach ($xpath->query($query) as $attribute)
		{
			$this->inlineAttribute($attribute);
		}
	}

	/**
	* Inline the content of an xsl:attribute element
	*
	* @param  DOMElement $attribute xsl:attribute element
	* @return void
	*/
	protected function inlineAttribute(DOMElement $attribute)
	{
		$value = '';
		foreach ($attribute->childNodes as $node)
		{
			if ($node instanceof DOMText
			 || [$node->namespaceURI, $node->localName] === [self::XMLNS_XSL, 'text'])
			{
				$value .= preg_replace('([{}])', '$0$0', $node->textContent);
			}
			elseif ([$node->namespaceURI, $node->localName] === [self::XMLNS_XSL, 'value-of'])
			{
				$value .= '{' . $node->getAttribute('select') . '}';
			}
			else
			{
				// Can't inline this attribute
				return;
			}
		}
		$attribute->parentNode->setAttribute($attribute->getAttribute('name'), $value);
		$attribute->parentNode->removeChild($attribute);
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;

class InlineCDATA extends TemplateNormalization
{
	/**
	* Replace CDATA sections with text literals
	*
	* @param  DOMElement $template <xsl:template/> node
	* @return void
	*/
	public function normalize(DOMElement $template)
	{
		$dom   = $template->ownerDocument;
		$xpath = new DOMXPath($dom);
		foreach ($xpath->query('//text()') as $textNode)
		{
			if ($textNode->nodeType === XML_CDATA_SECTION_NODE)
			{
				$textNode->parentNode->replaceChild(
					$dom->createTextNode($textNode->textContent),
					$textNode
				);
			}
		}
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMElement;
use DOMException;
use s9e\TextFormatter\Configurator\TemplateNormalization;

class InlineElements extends TemplateNormalization
{
	/**
	* Inline the elements declarations of a template
	*
	* Will replace
	*     <xsl:element name="div"><xsl:apply-templates/></xsl:element>
	* with
	*     <div><xsl:apply-templates/></div>
	*
	* @param  DOMElement $template <xsl:template/> node
	* @return void
	*/
	public function normalize(DOMElement $template)
	{
		$dom = $template->ownerDocument;
		foreach ($template->getElementsByTagNameNS(self::XMLNS_XSL, 'element') as $element)
		{
			$elName = $element->getAttribute('name');

			try
			{
				// Create the new static element
				$newElement = ($element->hasAttribute('namespace'))
				            ? $dom->createElementNS($element->getAttribute('namespace'), $elName)
				            : $dom->createElement($elName);
			}
			catch (DOMException $e)
			{
				// Ignore this element and keep going if an exception got thrown
				continue;
			}

			// Replace the old <xsl:element/> with it. We do it now so that libxml doesn't have to
			// redeclare the XSL namespace
			$element->parentNode->replaceChild($newElement, $element);

			// One by one and in order, we move the nodes from the old element to the new one
			while ($element->firstChild)
			{
				$newElement->appendChild($element->removeChild($element->firstChild));
			}
		}
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMAttr;
use DOMElement;
use DOMNode;
use DOMXPath;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;
use s9e\TextFormatter\Configurator\Helpers\TemplateParser;
use s9e\TextFormatter\Configurator\TemplateNormalization;

class InlineInferredValues extends TemplateNormalization
{
	/**
	* Inline the text content of a node or the value of an attribute where it's known
	*
	* Will replace
	*     <xsl:if test="@foo='Foo'"><xsl:value-of select="@foo"/></xsl:if>
	* with
	*     <xsl:if test="@foo='Foo'">Foo</xsl:if>
	*
	* @param  DOMElement $template <xsl:template/> node
	* @return void
	*/
	public function normalize(DOMElement $template)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		$query = '//xsl:if | //xsl:when';
		foreach ($xpath->query($query) as $node)
		{
			// Test whether the map has exactly one key and one value
			$map = TemplateParser::parseEqualityExpr($node->getAttribute('test'));
			if ($map === false || count($map) !== 1 || count($map[key($map)]) !== 1)
			{
				continue;
			}

			$expr  = key($map);
			$value = end($map[$expr]);
			$this->inlineInferredValue($node, $expr, $value);
		}
	}

	/**
	* Replace the inferred value in given node and its descendants
	*
	* @param  DOMNode $node  Context node
	* @param  string  $expr  XPath expression
	* @param  string  $value Inferred value
	* @return void
	*/
	protected function inlineInferredValue(DOMNode $node, $expr, $value)
	{
		$xpath = new DOMXPath($node->ownerDocument);

		// Get xsl:value-of descendants that match the condition
		$query = './/xsl:value-of[@select="' . $expr . '"]';
		foreach ($xpath->query($query, $node) as $valueOf)
		{
			$this->replaceValueOf($valueOf, $value);
		}

		// Get all attributes from non-XSL elements that *could* match the condition
		$query = './/*[namespace-uri() != "' . self::XMLNS_XSL . '"]'
		       . '/@*[contains(., "{' . $expr . '}")]';
		foreach ($xpath->query($query, $node) as $attribute)
		{
			$this->replaceAttribute($attribute, $expr, $value);
		}
	}

	/**
	* Replace an expression with a literal value in given attribute
	*
	* @param  DOMAttr $attribute
	* @param  string  $expr
	* @param  string  $value
	* @return void
	*/
	protected function replaceAttribute(DOMAttr $attribute, $expr, $value)
	{
		AVTHelper::replace(
			$attribute,
			function ($token) use ($expr, $value)
			{
				// Test whether this expression is the one we're looking for
				if ($token[0] === 'expression' && $token[1] === $expr)
				{
					// Replace the expression with the value (as a literal)
					$token = ['literal', $value];
				}

				return $token;
			}
		);
	}

	/**
	* Replace an xsl:value-of element with a literal value
	*
	* @param  DOMElement $valueOf
	* @param  string     $value
	* @return void
	*/
	protected function replaceValueOf(DOMElement $valueOf, $value)
	{
		$valueOf->parentNode->replaceChild(
			$valueOf->ownerDocument->createTextNode($value),
			$valueOf
		);
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;

class InlineTextElements extends TemplateNormalization
{
	/**
	* Replace <xsl:text/> nodes with a Text node, except for nodes whose content is only whitespace
	*
	* @param  DOMElement $template <xsl:template/> node
	* @return void
	*/
	public function normalize(DOMElement $template)
	{
		$dom   = $template->ownerDocument;
		$xpath = new DOMXPath($dom);
		foreach ($xpath->query('//xsl:text') as $node)
		{
			// If this node's content is whitespace, ensure it's preceded or followed by a text node
			if (trim($node->textContent) === '')
			{
				if ($node->previousSibling && $node->previousSibling->nodeType === XML_TEXT_NODE)
				{
					// This node is preceded by a text node
				}
				elseif ($node->nextSibling && $node->nextSibling->nodeType === XML_TEXT_NODE)
				{
					// This node is followed by a text node
				}
				else
				{
					// This would become inter-element whitespace, therefore we can't inline
					continue;
				}
			}
			$node->parentNode->replaceChild(
				$dom->createTextNode($node->textContent),
				$node
			);
		}
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;

class InlineXPathLiterals extends TemplateNormalization
{
	/**
	* Replace xsl:value nodes that contain a literal with a Text node
	*
	* @param  DOMElement $template <xsl:template/> node
	* @return void
	*/
	public function normalize(DOMElement $template)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		foreach ($xpath->query('//xsl:value-of') as $valueOf)
		{
			$textContent = $this->getTextContent($valueOf->getAttribute('select'));

			if ($textContent !== false)
			{
				$this->replaceElement($valueOf, $textContent);
			}
		}

		$query = '//*[namespace-uri() != "' . self::XMLNS_XSL . '"]'
		       . '/@*[contains(., "{")]';
		foreach ($xpath->query($query) as $attribute)
		{
			AVTHelper::replace(
				$attribute,
				function ($token)
				{
					if ($token[0] === 'expression')
					{
						$textContent = $this->getTextContent($token[1]);
						if ($textContent !== false)
						{
							// Turn this token into a literal
							$token = ['literal', $textContent];
						}
					}

					return $token;
				}
			);
		}
	}

	/**
	* Return the textContent value of an XPath expression
	*
	* @param  string      $expr XPath expression
	* @return string|bool       Text value, or FALSE if not a literal
	*/
	protected function getTextContent($expr)
	{
		$expr = trim($expr);

		if (preg_match('(^(?:\'[^\']*\'|"[^"]*")$)', $expr))
		{
			return substr($expr, 1, -1);
		}

		if (preg_match('(^0*([0-9]+)$)', $expr, $m))
		{
			// NOTE: we specifically ignore leading zeros
			return $m[1];
		}

		return false;
	}

	/**
	* Replace an xsl:value-of element with a text node
	*
	* @param  DOMElement $valueOf
	* @param  string     $textContent
	* @return void
	*/
	protected function replaceElement(DOMElement $valueOf, $textContent)
	{
		$valueOf->parentNode->replaceChild(
			$valueOf->ownerDocument->createTextNode($textContent),
			$valueOf
		);
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMElement;
use DOMNode;
use s9e\TextFormatter\Configurator\Helpers\TemplateParser;
use s9e\TextFormatter\Configurator\TemplateNormalization;

class MergeIdenticalConditionalBranches extends TemplateNormalization
{
	/**
	* Merge xsl:when branches if they have identical content
	*
	* NOTE: may fail if branches have identical equality expressions, e.g. "@a=1" and "@a=1"
	*
	* @param  DOMElement $template <xsl:template/> node
	* @return void
	*/
	public function normalize(DOMElement $template)
	{
		foreach ($template->getElementsByTagNameNS(self::XMLNS_XSL, 'choose') as $choose)
		{
			self::mergeCompatibleBranches($choose);
			self::mergeConsecutiveBranches($choose);
		}
	}

	/**
	* Inspect the branches of an xsl:choose element and merge branches if their content is identical
	* and their order does not matter
	*
	* @param  DOMElement $choose xsl:choose element
	* @return void
	*/
	protected static function mergeCompatibleBranches(DOMElement $choose)
	{
		$node = $choose->firstChild;
		while ($node)
		{
			$nodes = self::collectCompatibleBranches($node);

			if (count($nodes) > 1)
			{
				$node = end($nodes)->nextSibling;

				// Try to merge branches if there's more than one of them
				self::mergeBranches($nodes);
			}
			else
			{
				$node = $node->nextSibling;
			}
		}
	}

	/**
	* Inspect the branches of an xsl:choose element and merge consecutive branches if their content
	* is identical
	*
	* @param  DOMElement $choose xsl:choose element
	* @return void
	*/
	protected static function mergeConsecutiveBranches(DOMElement $choose)
	{
		// Try to merge consecutive branches even if their test conditions are not compatible,
		// e.g. "@a=1" and "@b=2"
		$nodes = [];
		foreach ($choose->childNodes as $node)
		{
			if (self::isXslWhen($node))
			{
				$nodes[] = $node;
			}
		}

		$i = count($nodes);
		while (--$i > 0)
		{
			self::mergeBranches([$nodes[$i - 1], $nodes[$i]]);
		}
	}

	/**
	* Collect consecutive xsl:when elements that share the same kind of equality tests
	*
	* Will return xsl:when elements that test a constant part (e.g. a literal) against the same
	* variable part (e.g. the same attribute)
	*
	* @param  DOMNode      $node First node to inspect
	* @return DOMElement[]
	*/
	protected static function collectCompatibleBranches(DOMNode $node)
	{
		$nodes  = [];
		$key    = null;
		$values = [];

		while ($node && self::isXslWhen($node))
		{
			$branch = TemplateParser::parseEqualityExpr($node->getAttribute('test'));

			if ($branch === false || count($branch) !== 1)
			{
				// The expression is not entirely composed of equalities, or they have a different
				// variable part
				break;
			}

			if (isset($key) && key($branch) !== $key)
			{
				// Not the same variable as our branches
				break;
			}

			if (array_intersect($values, end($branch)))
			{
				// Duplicate values across branches, e.g. ".=1 or .=2" and ".=2 or .=3"
				break;
			}

			$key    = key($branch);
			$values = array_merge($values, end($branch));

			// Record this node then move on to the next sibling
			$nodes[] = $node;
			$node    = $node->nextSibling;
		}

		return $nodes;
	}

	/**
	* Merge identical xsl:when elements from a list
	*
	* @param  DOMElement[] $nodes
	* @return void
	*/
	protected static function mergeBranches(array $nodes)
	{
		$sortedNodes = [];
		foreach ($nodes as $node)
		{
			$outerXML = $node->ownerDocument->saveXML($node);
			$innerXML = preg_replace('([^>]+>(.*)<[^<]+)s', '$1', $outerXML);

			$sortedNodes[$innerXML][] = $node;
		}

		foreach ($sortedNodes as $identicalNodes)
		{
			if (count($identicalNodes) < 2)
			{
				continue;
			}

			$expr = [];
			foreach ($identicalNodes as $i => $node)
			{
				$expr[] = $node->getAttribute('test');

				if ($i > 0)
				{
					$node->parentNode->removeChild($node);
				}
			}

			$identicalNodes[0]->setAttribute('test', implode(' or ', $expr));
		}
	}

	/**
	* Test whether a node is an xsl:when element
	*
	* @param  DOMNode $node
	* @return boolean
	*/
	protected static function isXslWhen(DOMNode $node)
	{
		return ($node->namespaceURI === self::XMLNS_XSL && $node->localName === 'when');
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;
use s9e\TextFormatter\Configurator\Helpers\XPathHelper;
use s9e\TextFormatter\Configurator\TemplateNormalization;

class MinifyXPathExpressions extends TemplateNormalization
{
	/**
	* Remove extraneous space in XPath expressions used in XSL elements
	*
	* @param  DOMElement $template <xsl:template/> node
	* @return void
	*/
	public function normalize(DOMElement $template)
	{
		$xpath = new DOMXPath($template->ownerDocument);

		// Get all the "match", "select" and "test" attributes of XSL elements, whose value contains
		// a space
		$query = '//xsl:*/@*[contains(., " ")][contains("matchselectest", name())]';
		foreach ($xpath->query($query) as $attribute)
		{
			$attribute->parentNode->setAttribute(
				$attribute->nodeName,
				XPathHelper::minify($attribute->nodeValue)
			);
		}

		// Get all the attributes of non-XSL elements, whose value contains a space
		$query = '//*[namespace-uri() != "' . self::XMLNS_XSL . '"]'
		       . '/@*[contains(., " ")]';
		foreach ($xpath->query($query) as $attribute)
		{
			AVTHelper::replace(
				$attribute,
				function ($token)
				{
					if ($token[0] === 'expression')
					{
						$token[1] = XPathHelper::minify($token[1]);
					}

					return $token;
				}
			);
		}
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;

class NormalizeAttributeNames extends TemplateNormalization
{
	/**
	* Lowercase attribute names
	*
	* @param  DOMElement $template <xsl:template/> node
	* @return void
	*/
	public function normalize(DOMElement $template)
	{
		$xpath = new DOMXPath($template->ownerDocument);

		// Normalize elements' attributes
		foreach ($xpath->query('.//@*', $template) as $attribute)
		{
			$attrName = self::lowercase($attribute->localName);

			if ($attrName !== $attribute->localName)
			{
				$attribute->parentNode->setAttribute($attrName, $attribute->value);
				$attribute->parentNode->removeAttributeNode($attribute);
			}
		}

		// Normalize <xsl:attribute/> names
		foreach ($xpath->query('//xsl:attribute[not(contains(@name, "{"))]') as $attribute)
		{
			$attrName = self::lowercase($attribute->getAttribute('name'));

			$attribute->setAttribute('name', $attrName);
		}
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;

class NormalizeElementNames extends TemplateNormalization
{
	/**
	* Lowercase element names
	*
	* @param  DOMElement $template <xsl:template/> node
	* @return void
	*/
	public function normalize(DOMElement $template)
	{
		$dom   = $template->ownerDocument;
		$xpath = new DOMXPath($dom);

		// Normalize elements' names
		foreach ($xpath->query('//*[namespace-uri() != "' . self::XMLNS_XSL . '"]') as $element)
		{
			$elName = self::lowercase($element->localName);

			if ($elName === $element->localName)
			{
				continue;
			}

			// Create a new element with the correct name
			$newElement = (is_null($element->namespaceURI))
			            ? $dom->createElement($elName)
			            : $dom->createElementNS($element->namespaceURI, $elName);

			// Move every child to the new element
			while ($element->firstChild)
			{
				$newElement->appendChild($element->removeChild($element->firstChild));
			}

			// Copy attributes to the new node
			foreach ($element->attributes as $attribute)
			{
				$newElement->setAttributeNS(
					$attribute->namespaceURI,
					$attribute->nodeName,
					$attribute->value
				);
			}

			// Replace the old element with the new one
			$element->parentNode->replaceChild($newElement, $element);
		}

		// Normalize <xsl:element/> names
		foreach ($xpath->query('//xsl:element[not(contains(@name, "{"))]') as $element)
		{
			$elName = self::lowercase($element->getAttribute('name'));

			$element->setAttribute('name', $elName);
		}
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMAttr;
use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\TemplateNormalization;
use s9e\TextFormatter\Parser\BuiltInFilters;

class NormalizeUrls extends TemplateNormalization
{
	/**
	* Normalize URLs
	*
	* @link http://dev.w3.org/html5/spec/links.html#attr-hyperlink-href
	*
	* @param  DOMElement $template <xsl:template/> node
	* @return void
	*/
	public function normalize(DOMElement $template)
	{
		foreach (TemplateHelper::getURLNodes($template->ownerDocument) as $node)
		{
			if ($node instanceof DOMAttr)
			{
				$this->normalizeAttribute($node);
			}
			elseif ($node instanceof DOMElement)
			{
				$this->normalizeElement($node);
			}
		}
	}

	/**
	* Normalize the value of an attribute
	*
	* @param  DOMAttr $attribute
	* @return void
	*/
	protected function normalizeAttribute(DOMAttr $attribute)
	{
		// Trim the URL and parse it
		$tokens = AVTHelper::parse(trim($attribute->value));

		$attrValue = '';
		foreach ($tokens as $_f6b3b659)
		{
			list($type, $content) = $_f6b3b659;
			if ($type === 'literal')
			{
				$attrValue .= BuiltInFilters::sanitizeUrl($content);
			}
			else
			{
				$attrValue .= '{' . $content . '}';
			}
		}

		// Unescape brackets in the host part
		$attrValue = $this->unescapeBrackets($attrValue);

		// Update the attribute's value
		$attribute->value = htmlspecialchars($attrValue);
	}

	/**
	* Normalize value of the text nodes, descendants of an element
	*
	* @param  DOMElement $element
	* @return void
	*/
	protected function normalizeElement(DOMElement $element)
	{
		$xpath = new DOMXPath($element->ownerDocument);
		$query = './/text()[normalize-space() != ""]';
		foreach ($xpath->query($query, $element) as $i => $node)
		{
			$value = BuiltInFilters::sanitizeUrl($node->nodeValue);

			if (!$i)
			{
				$value = $this->unescapeBrackets(ltrim($value));
			}

			$node->nodeValue = $value;
		}
		if (isset($node))
		{
			$node->nodeValue = rtrim($node->nodeValue);
		}
	}

	/**
	* Unescape brackets in the host part of a URL if it looks like an IPv6 address
	*
	* @param  string $url
	* @return string
	*/
	protected function unescapeBrackets($url)
	{
		return preg_replace('#^(\\w+://)%5B([-\\w:._%]+)%5D#i', '$1[$2]', $url);
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;

class OptimizeConditionalAttributes extends TemplateNormalization
{
	/**
	* Optimize conditional attributes
	*
	* Will replace conditional attributes with a <xsl:copy-of/>, e.g.
	*	<xsl:if test="@foo">
	*		<xsl:attribute name="foo">
	*			<xsl:value-of select="@foo" />
	*		</xsl:attribute>
	*	</xsl:if>
	* into
	*	<xsl:copy-of select="@foo"/>
	*
	* @param  DOMElement $template <xsl:template/> node
	* @return void
	*/
	public function normalize(DOMElement $template)
	{
		$dom   = $template->ownerDocument;
		$xpath = new DOMXPath($dom);
		$query = '//xsl:if'
		       . "[starts-with(@test, '@')]"
		       . '[count(descendant::node()) = 2]'
		       . '[xsl:attribute[@name = substring(../@test, 2)][xsl:value-of[@select = ../../@test]]]';
		foreach ($xpath->query($query) as $if)
		{
			$copyOf = $dom->createElementNS(self::XMLNS_XSL, 'xsl:copy-of');
			$copyOf->setAttribute('select', $if->getAttribute('test'));

			$if->parentNode->replaceChild($copyOf, $if);
		}
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;

class OptimizeConditionalValueOf extends TemplateNormalization
{
	/**
	* Remove unnecessary <xsl:if> tests around <xsl:value-of>
	*
	* NOTE: should be performed before attributes are inlined for maximum effect
	*
	* @param  DOMElement $template <xsl:template/> node
	* @return void
	*/
	public function normalize(DOMElement $template)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		$query = '//xsl:if[count(descendant::node()) = 1]/xsl:value-of';
		foreach ($xpath->query($query) as $valueOf)
		{
			$if     = $valueOf->parentNode;
			$test   = $if->getAttribute('test');
			$select = $valueOf->getAttribute('select');

			// Ensure that the expressions match, and that they select one single attribute
			if ($select !== $test
			 || !preg_match('#^@[-\\w]+$#D', $select))
			{
				continue;
			}

			// Replace the <xsl:if/> node with the <xsl:value-of/> node
			$if->parentNode->replaceChild(
				$if->removeChild($valueOf),
				$if
			);
		}
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;

class OptimizeNestedConditionals extends TemplateNormalization
{
	/**
	* Optimize xsl:choose elements by integrating the content of another xsl:choose element located
	* in their xsl:otherwise part
	*
	* Will move child nodes from //xsl:choose/xsl:otherwise/xsl:choose to their great-grandparent as
	* long as the inner xsl:choose has no siblings. Good for XSLT stylesheets because it reduces the
	* number of nodes, not-so-good for the PHP renderer when it prevents from optimizing branch
	* tables by mixing the branch keys
	*
	* @param  DOMElement $template <xsl:template/> node
	* @return void
	*/
	public function normalize(DOMElement $template)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		$query = '//xsl:choose/xsl:otherwise[count(node()) = 1]/xsl:choose';
		foreach ($xpath->query($query) as $innerChoose)
		{
			$otherwise   = $innerChoose->parentNode;
			$outerChoose = $otherwise->parentNode;

			while ($innerChoose->firstChild)
			{
				$outerChoose->appendChild($innerChoose->removeChild($innerChoose->firstChild));
			}

			$outerChoose->removeChild($otherwise);
		}
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;

class PreserveSingleSpaces extends TemplateNormalization
{
	/**
	* Removes all inter-element whitespace except for single space characters
	*
	* @param  DOMElement $template <xsl:template/> node
	* @return void
	*/
	public function normalize(DOMElement $template)
	{
		$dom   = $template->ownerDocument;
		$xpath = new DOMXPath($dom);

		// Query all text nodes that are made of a single space and not inside of an xsl:text
		// element
		$query = '//text()[. = " "][not(parent::xsl:text)]';
		foreach ($xpath->query($query) as $textNode)
		{
			$textNode->parentNode->replaceChild(
				$dom->createElementNS(self::XMLNS_XSL, 'text', ' '),
				$textNode
			);
		}
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;

class RemoveComments extends TemplateNormalization
{
	/**
	* Remove all comments
	*
	* @param  DOMElement $template <xsl:template/> node
	* @return void
	*/
	public function normalize(DOMElement $template)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		foreach ($xpath->query('//comment()') as $comment)
		{
			$comment->parentNode->removeChild($comment);
		}
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;

class RemoveInterElementWhitespace extends TemplateNormalization
{
	/**
	* Removes all inter-element whitespace except for single space characters
	*
	* @param  DOMElement $template <xsl:template/> node
	* @return void
	*/
	public function normalize(DOMElement $template)
	{
		$xpath = new DOMXPath($template->ownerDocument);

		// Query all text nodes that are entirely made of whitespace but not made of a single space
		// and not inside of an xsl:text element
		$query = '//text()[normalize-space() = ""][. != " "][not(parent::xsl:text)]';
		foreach ($xpath->query($query) as $textNode)
		{
			$textNode->parentNode->removeChild($textNode);
		}
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license'); The MIT License
*/
namespace s9e\TextFormatter\Configurator;

use ArrayAccess;
use Iterator;
use s9e\TextFormatter\Configurator\Collections\TemplateNormalizationList;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\Traits\CollectionProxy;

/**
* @method mixed   add(mixed $value, null $void)
* @method mixed   append(mixed $value)
* @method array   asConfig()
* @method void    clear()
* @method bool    contains(mixed $value)
* @method integer count()
* @method mixed   current()
* @method void    delete(string $key)
* @method bool    exists(string $key)
* @method mixed   get(string $key)
* @method mixed   indexOf(mixed $value)
* @method mixed   insert(integer $offset, mixed $value)
* @method integer|string key()
* @method mixed   next()
* @method integer normalizeKey(mixed $key)
* @method TemplateNormalization normalizeValue(mixed $value)
* @method bool    offsetExists(string|integer $offset)
* @method mixed   offsetGet(string|integer $offset)
* @method void    offsetSet(mixed $offset, mixed $value)
* @method void    offsetUnset(string|integer $offset)
* @method string  onDuplicate(string|null $action)
* @method mixed   prepend(mixed $value)
* @method integer remove(mixed $value)
* @method void    rewind()
* @method mixed   set(string $key, mixed $value)
* @method bool    valid()
*/
class TemplateNormalizer implements ArrayAccess, Iterator
{
	use CollectionProxy;

	/**
	* @var TemplateNormalizationList Collection of TemplateNormalization instances
	*/
	protected $collection;

	/**
	* Constructor
	*
	* Will load the default normalization rules
	*
	* @return void
	*/
	public function __construct()
	{
		$this->collection = new TemplateNormalizationList;

		$this->collection->append('PreserveSingleSpaces');
		$this->collection->append('RemoveComments');
		$this->collection->append('RemoveInterElementWhitespace');
		$this->collection->append('FixUnescapedCurlyBracesInHtmlAttributes');
		$this->collection->append('InlineAttributes');
		$this->collection->append('InlineCDATA');
		$this->collection->append('InlineElements');
		$this->collection->append('InlineInferredValues');
		$this->collection->append('InlineTextElements');
		$this->collection->append('InlineXPathLiterals');
		$this->collection->append('MinifyXPathExpressions');
		$this->collection->append('NormalizeAttributeNames');
		$this->collection->append('NormalizeElementNames');
		$this->collection->append('NormalizeUrls');
		$this->collection->append('OptimizeConditionalAttributes');
		$this->collection->append('OptimizeConditionalValueOf');
	}

	/**
	* Normalize a tag's templates
	*
	* @param  Tag  $tag Tag whose templates will be normalized
	* @return void
	*/
	public function normalizeTag(Tag $tag)
	{
		if (isset($tag->template) && !$tag->template->isNormalized())
		{
			$tag->template->normalize($this);
		}
	}

	/**
	* Normalize a template
	*
	* @param  string $template Original template
	* @return string           Normalized template
	*/
	public function normalizeTemplate($template)
	{
		$dom = TemplateHelper::loadTemplate($template);

		// We'll keep track of what normalizations have been applied
		$applied = [];

		// Apply all the normalizations until no more change is made or we've reached the maximum
		// number of loops
		$loops = 5;
		do
		{
			$old = $template;

			foreach ($this->collection as $k => $normalization)
			{
				if (isset($applied[$k]) && !empty($normalization->onlyOnce))
				{
					continue;
				}

				$normalization->normalize($dom->documentElement);
				$applied[$k] = 1;
			}

			$template = TemplateHelper::saveTemplate($dom);
		}
		while (--$loops && $template !== $old);

		return $template;
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;

use RuntimeException;
use s9e\TextFormatter\Configurator\Collections\HostnameList;
use s9e\TextFormatter\Configurator\Collections\SchemeList;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;

class UrlConfig implements ConfigProvider
{
	/**
	* @var SchemeList List of allowed schemes
	*/
	protected $allowedSchemes;

	/**
	* @var HostnameList List of disallowed hosts
	*/
	protected $disallowedHosts;

	/**
	* @var HostnameList List of allowed hosts
	*/
	protected $restrictedHosts;

	/**
	* Constructor
	*
	* @return void
	*/
	public function __construct()
	{
		$this->disallowedHosts = new HostnameList;
		$this->restrictedHosts = new HostnameList;

		$this->allowedSchemes   = new SchemeList;
		$this->allowedSchemes[] = 'http';
		$this->allowedSchemes[] = 'https';
	}

	/**
	* {@inheritdoc}
	*/
	public function asConfig()
	{
		return ConfigHelper::toArray(get_object_vars($this));
	}

	/**
	* Allow a URL scheme
	*
	* @param string $scheme URL scheme, e.g. "file" or "ed2k"
	* @return void
	*/
	public function allowScheme($scheme)
	{
		if (strtolower($scheme) === 'javascript')
		{
			throw new RuntimeException('The JavaScript URL scheme cannot be allowed');
		}

		$this->allowedSchemes[] = $scheme;
	}

	/**
	* Disallow a hostname (or hostname mask) from being used in URLs
	*
	* @param  string $host            Hostname or hostmask
	* @param  bool   $matchSubdomains Whether to match subdomains of given host
	* @return void
	*/
	public function disallowHost($host, $matchSubdomains = true)
	{
		$this->disallowedHosts[] = $host;

		if ($matchSubdomains && substr($host, 0, 1) !== '*')
		{
			$this->disallowedHosts[] = '*.' . $host;
		}
	}

	/**
	* Remove a scheme from the list of allowed URL schemes
	*
	* @param  string $scheme URL scheme, e.g. "file" or "ed2k"
	* @return void
	*/
	public function disallowScheme($scheme)
	{
		$this->allowedSchemes->remove($scheme);
	}

	/**
	* Return the list of allowed URL schemes
	*
	* @return array
	*/
	public function getAllowedSchemes()
	{
		return iterator_to_array($this->allowedSchemes);
	}

	/**
	* Allow a hostname (or hostname mask) to being used in URLs while disallowing everything else
	*
	* Can be called multiple times to restricts URLs to a set of given hostnames
	*
	* @param  string $host            Hostname or hostmask
	* @param  bool   $matchSubdomains Whether to match subdomains of given host
	* @return void
	*/
	public function restrictHost($host, $matchSubdomains = true)
	{
		$this->restrictedHosts[] = $host;

		if ($matchSubdomains && substr($host, 0, 1) !== '*')
		{
			$this->restrictedHosts[] = '*.' . $host;
		}
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

use InvalidArgumentException;
use s9e\TextFormatter\Configurator\Items\AttributePreprocessor;
use s9e\TextFormatter\Configurator\Items\Variant;
use s9e\TextFormatter\Configurator\JavaScript\RegexpConvertor;
use s9e\TextFormatter\Configurator\Validators\AttributeName;

class AttributePreprocessorCollection extends Collection
{
	/**
	* Add an attribute preprocessor
	*
	* @param  string $attrName Original name
	* @param  string $regexp   Preprocessor's regexp
	* @return AttributePreprocessor
	*/
	public function add($attrName, $regexp)
	{
		$attrName = AttributeName::normalize($attrName);

		$k = serialize([$attrName, $regexp]);
		$this->items[$k] = new AttributePreprocessor($regexp);

		return $this->items[$k];
	}

	/**
	* @return string Name of the attribute the attribute processor uses as source
	*/
	public function key()
	{
		list($attrName) = unserialize(key($this->items));

		return $attrName;
	}

	/**
	* Merge a set of attribute preprocessors into this collection
	*
	* @param array|AttributePreprocessorCollection $attributePreprocessors Instance of AttributePreprocessorCollection or 2D array of [[attrName,regexp|AttributePreprocessor]]
	*/
	public function merge($attributePreprocessors)
	{
		$error = false;

		if ($attributePreprocessors instanceof AttributePreprocessorCollection)
		{
			foreach ($attributePreprocessors as $attrName => $attributePreprocessor)
			{
				$this->add($attrName, $attributePreprocessor->getRegexp());
			}
		}
		elseif (is_array($attributePreprocessors))
		{
			// This should be a list where each element is a [attrName,regexp] pair, or
			// [attrName,AttributePreprocessor]
			foreach ($attributePreprocessors as $values)
			{
				if (!is_array($values))
				{
					$error = true;
					break;
				}

				list($attrName, $value) = $values;

				if ($value instanceof AttributePreprocessor)
				{
					$value = $value->getRegexp();
				}

				$this->add($attrName, $value);
			}
		}
		else
		{
			$error = true;
		}

		if ($error)
		{
			throw new InvalidArgumentException('merge() expects an instance of AttributePreprocessorCollection or a 2D array where each element is a [attribute name, regexp] pair');
		}
	}

	/**
	* {@inheritdoc}
	*/
	public function asConfig()
	{
		$config = [];

		foreach ($this->items as $k => $ap)
		{
			list($attrName, $regexp) = unserialize($k);

			// Create a JavaScript regexp for the JS variant
			$jsRegexp = RegexpConvertor::toJS($regexp);

			$config[] = new Variant(
				[$attrName, $regexp],
				[
					'JS' => [$attrName, $jsRegexp, $jsRegexp->map]
				]
			);
		}

		return $config;
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

use ArrayAccess;
use InvalidArgumentException;
use RuntimeException;

class NormalizedCollection extends Collection implements ArrayAccess
{
	/**
	* @var string Action to take when add() is called with a key that already exists
	*/
	protected $onDuplicateAction = 'error';

	/**
	* Query and set the action to take when add() is called with a key that already exists
	*
	* @param  string|null $action If specified: either "error", "ignore" or "replace"
	* @return string              Old action
	*/
	public function onDuplicate($action = null)
	{
		// Save the old action so it can be returned
		$old = $this->onDuplicateAction;

		if (func_num_args() && $action !== 'error' && $action !== 'ignore' && $action !== 'replace')
		{
			throw new InvalidArgumentException("Invalid onDuplicate action '" . $action . "'. Expected: 'error', 'ignore' or 'replace'");
		}

		$this->onDuplicateAction = $action;

		return $old;
	}

	//==========================================================================
	// Overridable methods
	//==========================================================================

	/**
	* Return the exception that is thrown when creating an item using a key that already exists
	*
	* @param  string           $key Item's key
	* @return RuntimeException
	*/
	protected function getAlreadyExistsException($key)
	{
		return new RuntimeException("Item '" . $key . "' already exists");
	}

	/**
	* Return the exception that is thrown when accessing an item that does not exist
	*
	* @param  string           $key Item's key
	* @return RuntimeException
	*/
	protected function getNotExistException($key)
	{
		return new RuntimeException("Item '" . $key . "' does not exist");
	}

	/**
	* Normalize an item's key
	*
	* This method can be overridden to implement keys normalization or implement constraints
	*
	* @param  string $key Original key
	* @return string      Normalized key
	*/
	public function normalizeKey($key)
	{
		return $key;
	}

	/**
	* Normalize a value for storage
	*
	* This method can be overridden to implement value normalization
	*
	* @param  mixed $value Original value
	* @return mixed        Normalized value
	*/
	public function normalizeValue($value)
	{
		return $value;
	}

	//==========================================================================
	// Items access/manipulation
	//==========================================================================

	/**
	* Add an item to this collection
	*
	* NOTE: relies on exists() to check the key for invalid values and on set() to normalize it
	*
	* @param  string $key   Item's key
	* @param  mixed  $value Item's value
	* @return mixed         Normalized value
	*/
	public function add($key, $value = null)
	{
		// Test whether this key is already in use
		if ($this->exists($key))
		{
			// If the action is "ignore" we return the old value, if it's "error" we throw an
			// exception. Otherwise, we keep going and replace the value
			if ($this->onDuplicateAction === 'ignore')
			{
				return $this->get($key);
			}
			elseif ($this->onDuplicateAction === 'error')
			{
				throw $this->getAlreadyExistsException($key);
			}
		}

		return $this->set($key, $value);
	}

	/**
	* Test whether a given value is present in this collection
	*
	* @param  mixed $value Original value
	* @return bool         Whether the normalized value was found in this collection
	*/
	public function contains($value)
	{
		return in_array($this->normalizeValue($value), $this->items);
	}

	/**
	* Delete an item from this collection
	*
	* @param  string $key Item's key
	* @return void
	*/
	public function delete($key)
	{
		$key = $this->normalizeKey($key);

		unset($this->items[$key]);
	}

	/**
	* Test whether an item of given key exists
	*
	* @param  string $key Item's key
	* @return bool        Whether this key exists in this collection
	*/
	public function exists($key)
	{
		$key = $this->normalizeKey($key);

		return array_key_exists($key, $this->items);
	}

	/**
	* Return a value from this collection
	*
	* @param  string $key Item's key
	* @return mixed       Normalized value
	*/
	public function get($key)
	{
		if (!$this->exists($key))
		{
			throw $this->getNotExistException($key);
		}

		$key = $this->normalizeKey($key);

		return $this->items[$key];
	}

	/**
	* Find the index of a given value
	*
	* Will return the first key associated with the given value, or FALSE if the value is not found
	*
	* @param  mixed $value Original value
	* @return mixed        Index of the value, or FALSE if not found
	*/
	public function indexOf($value)
	{
		return array_search($this->normalizeValue($value), $this->items);
	}

	/**
	* Set and overwrite a value in this collection
	*
	* @param  string $key   Item's key
	* @param  mixed  $value Item's value
	* @return mixed         Normalized value
	*/
	public function set($key, $value)
	{
		$key = $this->normalizeKey($key);

		$this->items[$key] = $this->normalizeValue($value);

		return $this->items[$key];
	}

	//==========================================================================
	// ArrayAccess stuff
	//==========================================================================

	/**
	* @param  string|integer $offset
	* @return bool
	*/
	public function offsetExists($offset)
	{
		return $this->exists($offset);
	}

	/**
	* @param  string|integer $offset
	* @return mixed
	*/
	public function offsetGet($offset)
	{
		return $this->get($offset);
	}

	/**
	* @param  string|integer $offset
	* @param  mixed          $value
	* @return void
	*/
	public function offsetSet($offset, $value)
	{
		$this->set($offset, $value);
	}

	/**
	* @param  string|integer $offset
	* @return void
	*/
	public function offsetUnset($offset)
	{
		$this->delete($offset);
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

use ArrayAccess;
use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Items\Variant;
use s9e\TextFormatter\Configurator\JavaScript\Dictionary;
use s9e\TextFormatter\Configurator\Validators\TagName;
use s9e\TextFormatter\Parser;

/**
* @see docs/Rules.md
*/
class Ruleset extends Collection implements ArrayAccess, ConfigProvider
{
	/**
	* Constructor
	*
	* @return void
	*/
	public function __construct()
	{
		$this->defaultChildRule('allow');
		$this->defaultDescendantRule('allow');
	}

	//==========================================================================
	// ArrayAccess methods
	//==========================================================================

	/**
	* Test whether a rule category exists
	*
	* @param  string $k Rule name, e.g. "allowChild" or "isTransparent"
	*/
	public function offsetExists($k)
	{
		return isset($this->items[$k]);
	}

	/**
	* Return the content of a rule category
	*
	* @param  string $k Rule name, e.g. "allowChild" or "isTransparent"
	* @return mixed
	*/
	public function offsetGet($k)
	{
		return $this->items[$k];
	}

	/**
	* Not supported
	*/
	public function offsetSet($k, $v)
	{
		throw new RuntimeException('Not supported');
	}

	/**
	* Clear a subset of the rules
	*
	* @see clear()
	*
	* @param  string $k Rule name, e.g. "allowChild" or "isTransparent"
	*/
	public function offsetUnset($k)
	{
		return $this->remove($k);
	}

	//==========================================================================
	// Generic methods
	//==========================================================================

	/**
	* {@inheritdoc}
	*/
	public function asConfig()
	{
		$config = $this->items;

		// Remove rules that are not needed at parsing time. All of those are resolved when building
		// the allowed bitfields
		unset($config['allowChild']);
		unset($config['allowDescendant']);
		unset($config['defaultChildRule']);
		unset($config['defaultDescendantRule']);
		unset($config['denyChild']);
		unset($config['denyDescendant']);
		unset($config['requireParent']);

		// Pack boolean rules into a bitfield
		$bitValues = [
			'autoClose'                   => Parser::RULE_AUTO_CLOSE,
			'autoReopen'                  => Parser::RULE_AUTO_REOPEN,
			'breakParagraph'              => Parser::RULE_BREAK_PARAGRAPH,
			'createParagraphs'            => Parser::RULE_CREATE_PARAGRAPHS,
			'disableAutoLineBreaks'       => Parser::RULE_DISABLE_AUTO_BR,
			'enableAutoLineBreaks'        => Parser::RULE_ENABLE_AUTO_BR,
			'ignoreSurroundingWhitespace' => Parser::RULE_IGNORE_WHITESPACE,
			'ignoreTags'                  => Parser::RULE_IGNORE_TAGS,
			'ignoreText'                  => Parser::RULE_IGNORE_TEXT,
			'isTransparent'               => Parser::RULE_IS_TRANSPARENT,
			'preventLineBreaks'           => Parser::RULE_PREVENT_BR,
			'suspendAutoLineBreaks'       => Parser::RULE_SUSPEND_AUTO_BR,
			'trimFirstLine'               => Parser::RULE_TRIM_FIRST_LINE
		];

		$bitfield = 0;
		foreach ($bitValues as $ruleName => $bitValue)
		{
			if (!empty($config[$ruleName]))
			{
				$bitfield |= $bitValue;
			}

			unset($config[$ruleName]);
		}

		// In order to speed up lookups, we use the tag names as keys
		foreach (['closeAncestor', 'closeParent', 'fosterParent'] as $ruleName)
		{
			if (isset($config[$ruleName]))
			{
				$targets = array_fill_keys($config[$ruleName], 1);
				$config[$ruleName] = new Dictionary($targets);
			}
		}

		// Add the bitfield to the config
		$config['flags'] = $bitfield;

		return $config;
	}

	/**
	* Merge a set of rules into this collection
	*
	* @param array|Ruleset $rules     2D array of rule definitions, or instance of Ruleset
	* @param bool          $overwrite Whether to overwrite scalar rules (e.g. boolean rules)
	*/
	public function merge($rules, $overwrite = true)
	{
		if (!is_array($rules)
		 && !($rules instanceof self))
		{
			throw new InvalidArgumentException('merge() expects an array or an instance of Ruleset');
		}

		foreach ($rules as $action => $value)
		{
			if (is_array($value))
			{
				foreach ($value as $tagName)
				{
					$this->$action($tagName);
				}
			}
			elseif ($overwrite || !isset($this->items[$action]))
			{
				$this->$action($value);
			}
		}
	}

	/**
	* Remove a specific rule, or all the rules of a given type
	*
	* @param  string $type    Type of rules to clear
	* @param  string $tagName Name of the target tag, or none to remove all rules of given type
	* @return void
	*/
	public function remove($type, $tagName = null)
	{
		if (preg_match('(^default(?:Child|Descendant)Rule)', $type))
		{
			throw new RuntimeException('Cannot remove ' . $type);
		}

		if (isset($tagName))
		{
			$tagName = TagName::normalize($tagName);

			if (isset($this->items[$type]))
			{
				// Compute the difference between current list and our one tag name
				$this->items[$type] = array_diff(
					$this->items[$type],
					[$tagName]
				);

				if (empty($this->items[$type]))
				{
					// If the list is now empty, keep it neat and unset it
					unset($this->items[$type]);
				}
				else
				{
					// If the list still have names, keep it neat and rearrange keys
					$this->items[$type] = array_values($this->items[$type]);
				}
			}
		}
		else
		{
			unset($this->items[$type]);
		}
	}

	//==========================================================================
	// Rules
	//==========================================================================

	/**
	* Add a boolean rule
	*
	* @param  string $ruleName Name of the rule
	* @param  bool   $bool     Whether to enable or disable the rule
	* @return self
	*/
	protected function addBooleanRule($ruleName, $bool)
	{
		if (!is_bool($bool))
		{
			throw new InvalidArgumentException($ruleName . '() expects a boolean');
		}

		$this->items[$ruleName] = $bool;

		return $this;
	}

	/**
	* Add a targeted rule
	*
	* @param  string $ruleName Name of the rule
	* @param  string $tagName  Name of the target tag
	* @return self
	*/
	protected function addTargetedRule($ruleName, $tagName)
	{
		$this->items[$ruleName][] = TagName::normalize($tagName);

		return $this;
	}

	/**
	* Add an allowChild rule
	*
	* @param  string $tagName Name of the target tag
	* @return self
	*/
	public function allowChild($tagName)
	{
		return $this->addTargetedRule('allowChild', $tagName);
	}

	/**
	* Add an allowDescendant rule
	*
	* @param  string $tagName Name of the target tag
	* @return self
	*/
	public function allowDescendant($tagName)
	{
		return $this->addTargetedRule('allowDescendant', $tagName);
	}

	/**
	* Add an autoClose rule
	*
	* NOTE: this rule exists so that plugins don't have to specifically handle tags whose end tag
	*       may/must be omitted such as <hr> or [img]
	*
	* @param  bool $bool Whether or not the tag should automatically be closed if its start tag is not followed by an end tag
	* @return self
	*/
	public function autoClose($bool = true)
	{
		return $this->addBooleanRule('autoClose', $bool);
	}

	/**
	* Add an autoReopen rule
	*
	* @param  bool $bool Whether or not the tag should automatically be reopened if closed by an end tag of a different name
	* @return self
	*/
	public function autoReopen($bool = true)
	{
		return $this->addBooleanRule('autoReopen', $bool);
	}

	/**
	* Add a breakParagraph rule
	*
	* @param  bool $bool Whether or not this tag breaks current paragraph if applicable
	* @return self
	*/
	public function breakParagraph($bool = true)
	{
		return $this->addBooleanRule('breakParagraph', $bool);
	}

	/**
	* Add a closeAncestor rule
	*
	* @param  string $tagName Name of the target tag
	* @return self
	*/
	public function closeAncestor($tagName)
	{
		return $this->addTargetedRule('closeAncestor', $tagName);
	}

	/**
	* Add a closeParent rule
	*
	* @param  string $tagName Name of the target tag
	* @return self
	*/
	public function closeParent($tagName)
	{
		return $this->addTargetedRule('closeParent', $tagName);
	}

	/**
	* Add a createParagraphs rule
	*
	* @param  bool $bool Whether or not paragraphs should automatically be created to handle content
	* @return self
	*/
	public function createParagraphs($bool = true)
	{
		return $this->addBooleanRule('createParagraphs', $bool);
	}

	/**
	* Set the default child rule
	*
	* @param  string $rule Either "allow" or "deny"
	* @return self
	*/
	public function defaultChildRule($rule)
	{
		if ($rule !== 'allow' && $rule !== 'deny')
		{
			throw new InvalidArgumentException("defaultChildRule() only accepts 'allow' or 'deny'");
		}

		$this->items['defaultChildRule'] = $rule;

		return $this;
	}

	/**
	* Set the default descendant rule
	*
	* @param  string $rule Either "allow" or "deny"
	* @return self
	*/
	public function defaultDescendantRule($rule)
	{
		if ($rule !== 'allow' && $rule !== 'deny')
		{
			throw new InvalidArgumentException("defaultDescendantRule() only accepts 'allow' or 'deny'");
		}

		$this->items['defaultDescendantRule'] = $rule;

		return $this;
	}

	/**
	* Add a denyChild rule
	*
	* @param  string $tagName Name of the target tag
	* @return self
	*/
	public function denyChild($tagName)
	{
		return $this->addTargetedRule('denyChild', $tagName);
	}

	/**
	* Add a denyDescendant rule
	*
	* @param  string $tagName Name of the target tag
	* @return self
	*/
	public function denyDescendant($tagName)
	{
		return $this->addTargetedRule('denyDescendant', $tagName);
	}

	/**
	* Add a disableAutoLineBreaks rule
	*
	* @param  bool $bool Whether or not automatic line breaks should be disabled
	* @return self
	*/
	public function disableAutoLineBreaks($bool = true)
	{
		return $this->addBooleanRule('disableAutoLineBreaks', $bool);
	}

	/**
	* Add a enableAutoLineBreaks rule
	*
	* @param  bool $bool Whether or not automatic line breaks should be enabled
	* @return self
	*/
	public function enableAutoLineBreaks($bool = true)
	{
		return $this->addBooleanRule('enableAutoLineBreaks', $bool);
	}

	/**
	* Add a fosterParent rule
	*
	* @param  string $tagName Name of the target tag
	* @return self
	*/
	public function fosterParent($tagName)
	{
		return $this->addTargetedRule('fosterParent', $tagName);
	}

	/**
	* Ignore (some) whitespace around tags
	*
	* When true, some whitespace around this tag will be ignored (not transformed to line breaks.)
	* Up to 2 lines outside of a tag pair and 1 line inside of it:
	*     {2 lines}{START_TAG}{1 line}{CONTENT}{1 line}{END_TAG}{2 lines}
	*
	* @param  bool $bool Whether whitespace around this tag should be ignored
	* @return self
	*/
	public function ignoreSurroundingWhitespace($bool = true)
	{
		return $this->addBooleanRule('ignoreSurroundingWhitespace', $bool);
	}

	/**
	* Add an ignoreTags rule
	*
	* @param  bool $bool Whether to silently ignore all tags until current tag is closed
	* @return self
	*/
	public function ignoreTags($bool = true)
	{
		return $this->addBooleanRule('ignoreTags', $bool);
	}

	/**
	* Add an ignoreText rule
	*
	* @param  bool $bool Whether or not the tag should ignore text nodes
	* @return self
	*/
	public function ignoreText($bool = true)
	{
		return $this->addBooleanRule('ignoreText', $bool);
	}

	/**
	* Add a isTransparent rule
	*
	* @param  bool $bool Whether or not the tag should use the "transparent" content model
	* @return self
	*/
	public function isTransparent($bool = true)
	{
		return $this->addBooleanRule('isTransparent', $bool);
	}

	/**
	* Add a preventLineBreaks rule
	*
	* @param  bool $bool Whether or not manual line breaks should be ignored in this tag's context
	* @return self
	*/
	public function preventLineBreaks($bool = true)
	{
		return $this->addBooleanRule('preventLineBreaks', $bool);
	}

	/**
	* Add a requireParent rule
	*
	* @param  string $tagName Name of the target tag
	* @return self
	*/
	public function requireParent($tagName)
	{
		return $this->addTargetedRule('requireParent', $tagName);
	}

	/**
	* Add a requireAncestor rule
	*
	* @param  string $tagName Name of the target tag
	* @return self
	*/
	public function requireAncestor($tagName)
	{
		return $this->addTargetedRule('requireAncestor', $tagName);
	}

	/**
	* Add a suspendAutoLineBreaks rule
	*
	* @param  bool $bool Whether or not automatic line breaks should be temporarily suspended
	* @return self
	*/
	public function suspendAutoLineBreaks($bool = true)
	{
		return $this->addBooleanRule('suspendAutoLineBreaks', $bool);
	}

	/**
	* Add a trimFirstLine rule
	*
	* @param  bool $bool Whether the white space inside this tag should be trimmed 
	* @return self
	*/
	public function trimFirstLine($bool = true)
	{
		return $this->addBooleanRule('trimFirstLine', $bool);
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;

abstract class Filter extends ProgrammableCallback
{
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;

use DOMElement;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\Helpers\XPathHelper;
use s9e\TextFormatter\Configurator\Items\Attribute;

class DisallowUnsafeDynamicCSS extends AbstractDynamicContentCheck
{
	/**
	* {@inheritdoc}
	*/
	protected function getNodes(DOMElement $template)
	{
		return TemplateHelper::getCSSNodes($template->ownerDocument);
	}

	/**
	* {@inheritdoc}
	*/
	protected function isExpressionSafe($expr)
	{
		return XPathHelper::isExpressionNumeric($expr);
	}

	/**
	* {@inheritdoc}
	*/
	protected function isSafe(Attribute $attribute)
	{
		return $attribute->isSafeInCSS();
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;

use DOMElement;
use s9e\TextFormatter\Configurator\Helpers\XPathHelper;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\Items\Attribute;

class DisallowUnsafeDynamicJS extends AbstractDynamicContentCheck
{
	/**
	* {@inheritdoc}
	*/
	protected function getNodes(DOMElement $template)
	{
		return TemplateHelper::getJSNodes($template->ownerDocument);
	}

	/**
	* {@inheritdoc}
	*/
	protected function isExpressionSafe($expr)
	{
		return XPathHelper::isExpressionNumeric($expr);
	}

	/**
	* {@inheritdoc}
	*/
	protected function isSafe(Attribute $attribute)
	{
		return $attribute->isSafeInJS();
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;

use DOMAttr;
use DOMElement;
use DOMText;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\Items\Attribute;
use s9e\TextFormatter\Configurator\Items\Tag;

/**
* This primary use of this check is to ensure that dynamic content cannot be used to create
* javascript: links
*/
class DisallowUnsafeDynamicURL extends AbstractDynamicContentCheck
{
	/**
	* @var string Regexp used to exclude nodes that start with a hardcoded scheme part, a hardcoded
	*             local part, or a fragment
	*/
	protected $exceptionRegexp = '(^(?:(?!data|\\w*script)\\w+:|[^:]*/|#))i';

	/**
	* {@inheritdoc}
	*/
	protected function getNodes(DOMElement $template)
	{
		return TemplateHelper::getURLNodes($template->ownerDocument);
	}

	/**
	* {@inheritdoc}
	*/
	protected function isSafe(Attribute $attribute)
	{
		return $attribute->isSafeAsURL();
	}

	/**
	* {@inheritdoc}
	*/
	protected function checkAttributeNode(DOMAttr $attribute, Tag $tag)
	{
		// Ignore this attribute if its scheme is hardcoded or it starts with //
		if (preg_match($this->exceptionRegexp, $attribute->value))
		{
			return;
		}

		parent::checkAttributeNode($attribute, $tag);
	}

	/**
	* {@inheritdoc}
	*/
	protected function checkElementNode(DOMElement $element, Tag $tag)
	{
		// Ignore this element if its scheme is hardcoded or it starts with //
		if ($element->firstChild
		 && $element->firstChild instanceof DOMText
		 && preg_match($this->exceptionRegexp, $element->firstChild->textContent))
		{
			return;
		}

		parent::checkElementNode($element, $tag);
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;

class RestrictFlashScriptAccess extends AbstractFlashRestriction
{
	/**
	* @var string Default AllowScriptAccess setting
	* @link http://helpx.adobe.com/flash-player/kb/changes-allowscriptaccess-default-flash-player.html
	*/
	public $defaultSetting = 'sameDomain';

	/**
	* {@inheritdoc}
	*/
	protected $settingName = 'allowScriptAccess';

	/**
	* @var array Valid AllowScriptAccess values
	*/
	protected $settings = [
		'always'     => 3,
		'samedomain' => 2,
		'never'      => 1
	];
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

use RuntimeException;
use s9e\TextFormatter\Configurator\Items\Attribute;
use s9e\TextFormatter\Configurator\Validators\AttributeName;

class AttributeCollection extends NormalizedCollection
{
	/**
	* {@inheritdoc}
	*/
	protected function getAlreadyExistsException($key)
	{
		return new RuntimeException("Attribute '" . $key . "' already exists");
	}

	/**
	* {@inheritdoc}
	*/
	protected function getNotExistException($key)
	{
		return new RuntimeException("Attribute '" . $key . "' does not exist");
	}

	/**
	* Normalize a key as an attribute name
	*
	* @param  string $key
	* @return string
	*/
	public function normalizeKey($key)
	{
		return AttributeName::normalize($key);
	}

	/**
	* Normalize a value to an instance of Attribute
	*
	* @param  array|null|Attribute $value
	* @return Attribute
	*/
	public function normalizeValue($value)
	{
		return ($value instanceof Attribute)
		     ? $value
		     : new Attribute($value);
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

use InvalidArgumentException;
use s9e\TextFormatter\Configurator\Items\AttributeFilter;

class AttributeFilterCollection extends NormalizedCollection
{
	/**
	* Return a value from this collection
	*
	* @param  string $key
	* @return \s9e\TextFormatter\Configurator\Items\ProgrammableCallback
	*/
	public function get($key)
	{
		$key = $this->normalizeKey($key);

		if (!$this->exists($key))
		{
			if ($key[0] === '#')
			{
				$this->set($key, self::getDefaultFilter(substr($key, 1)));
			}
			else
			{
				$this->set($key, new AttributeFilter($key));
			}
		}

		// Get the filter from the collection
		$filter = parent::get($key);

		// Clone it to preserve the original instance
		$filter = clone $filter;

		return $filter;
	}

	/**
	* Get an instance of the default filter for given name
	*
	* @param  string          $filterName Filter name, e.g. "int" or "color"
	* @return AttributeFilter
	*/
	public static function getDefaultFilter($filterName)
	{
		$filterName = ucfirst(strtolower($filterName));
		$className  = 's9e\\TextFormatter\\Configurator\\Items\\AttributeFilters\\' . $filterName . 'Filter';

		if (!class_exists($className))
		{
			throw new InvalidArgumentException("Unknown attribute filter '" . $filterName . "'");
		}

		return new $className;
	}

	/**
	* Normalize the name of an attribute filter
	*
	* @param  string $key
	* @return string
	*/
	public function normalizeKey($key)
	{
		// Built-in/custom filter, normalized to lowercase
		if (preg_match('/^#[a-z_0-9]+$/Di', $key))
		{
			return strtolower($key);
		}

		// Valid callback
		if (is_string($key) && is_callable($key))
		{
			return $key;
		}

		throw new InvalidArgumentException("Invalid filter name '" . $key . "'");
	}

	/**
	* Normalize a value to an instance of AttributeFilter
	*
	* @param  callable|AttributeFilter $value
	* @return AttributeFilter
	*/
	public function normalizeValue($value)
	{
		if ($value instanceof AttributeFilter)
		{
			return $value;
		}

		if (is_callable($value))
		{
			return new AttributeFilter($value);
		}

		throw new InvalidArgumentException('Argument 1 passed to ' . __METHOD__ . ' must be a valid callback or an instance of s9e\\TextFormatter\\Configurator\\Items\\AttributeFilter');
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

use InvalidArgumentException;

class NormalizedList extends NormalizedCollection
{
	/**
	* Add (append) a value to this list
	*
	* Alias for append(). Overrides NormalizedCollection::add()
	*
	* @param  mixed $value Original value
	* @param  null  $void  Unused
	* @return mixed        Normalized value
	*/
	public function add($value, $void = null)
	{
		return $this->append($value);
	}

	/**
	* Append a value to this list
	*
	* @param  mixed $value Original value
	* @return mixed        Normalized value
	*/
	public function append($value)
	{
		$value = $this->normalizeValue($value);

		$this->items[] = $value;

		return $value;
	}

	/**
	* Delete a value from this list and remove gaps in keys
	*
	* NOTE: parent::offsetUnset() maps to $this->delete() so this method covers both usages
	*
	* @param  string $key
	* @return void
	*/
	public function delete($key)
	{
		parent::delete($key);

		// Reindex the array to eliminate any gaps
		$this->items = array_values($this->items);
	}

	/**
	* Insert a value at an arbitrary 0-based position
	*
	* @param  integer $offset
	* @param  mixed   $value
	* @return mixed           Normalized value
	*/
	public function insert($offset, $value)
	{
		$offset = $this->normalizeKey($offset);
		$value  = $this->normalizeValue($value);

		// Insert the value at given offset. We put the value into an array so that array_splice()
		// won't insert it as multiple elements if it happens to be an array
		array_splice($this->items, $offset, 0, [$value]);

		return $value;
	}

	/**
	* Ensure that the key is a valid offset, ranging from 0 to count($this->items)
	*
	* @param  mixed   $key
	* @return integer
	*/
	public function normalizeKey($key)
	{
		$normalizedKey = filter_var(
			$key,
			FILTER_VALIDATE_INT,
			[
				'options' => [
					'min_range' => 0,
					'max_range' => count($this->items)
				]
			]
		);

		if ($normalizedKey === false)
		{
			throw new InvalidArgumentException("Invalid offset '" . $key . "'");
		}

		return $normalizedKey;
	}

	/**
	* Custom offsetSet() implementation to allow assignment with a null offset to append to the
	* chain
	*
	* @param  mixed $offset
	* @param  mixed $value
	* @return void
	*/
	public function offsetSet($offset, $value)
	{
		if ($offset === null)
		{
			// $list[] = 'foo' maps to $list->append('foo')
			$this->append($value);
		}
		else
		{
			// Use the default implementation
			parent::offsetSet($offset, $value);
		}
	}

	/**
	* Prepend a value to this list
	*
	* @param  mixed $value
	* @return mixed        Normalized value
	*/
	public function prepend($value)
	{
		$value = $this->normalizeValue($value);

		array_unshift($this->items, $value);

		return $value;
	}

	/**
	* Remove all items matching given value
	*
	* @param  mixed   $value Original value
	* @return integer        Number of items removed
	*/
	public function remove($value)
	{
		$keys = array_keys($this->items, $this->normalizeValue($value));
		foreach ($keys as $k)
		{
			unset($this->items[$k]);
		}

		$this->items = array_values($this->items);

		return count($keys);
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Items\Variant;
use s9e\TextFormatter\Plugins\ConfiguratorBase;

class PluginCollection extends NormalizedCollection
{
	/**
	* @var Configurator
	*/
	protected $configurator;

	/**
	* Constructor
	*
	* @param Configurator $configurator
	*/
	public function __construct(Configurator $configurator)
	{
		$this->configurator = $configurator;
	}

	/**
	* Finalize all of this collection's plugins
	*
	* @return void
	*/
	public function finalize()
	{
		foreach ($this->items as $plugin)
		{
			$plugin->finalize();
		}
	}

	/**
	* Validate a plugin name
	*
	* @param  string $pluginName
	* @return string
	*/
	public function normalizeKey($pluginName)
	{
		if (!preg_match('#^[A-Z][A-Za-z_0-9]+$#D', $pluginName))
		{
			throw new InvalidArgumentException("Invalid plugin name '" . $pluginName . "'");
		}

		return $pluginName;
	}

	/**
	* Create a plugin instance/ensure it implements the correct interface
	*
	* @param  mixed Either a class name or an object that implements ConfiguratorBase
	* @return ConfiguratorBase
	*/
	public function normalizeValue($value)
	{
		if (is_string($value) && class_exists($value))
		{
			$value = new $value($this->configurator);
		}

		if ($value instanceof ConfiguratorBase)
		{
			return $value;
		}

		throw new InvalidArgumentException('PluginCollection::normalizeValue() expects a class name or an object that implements s9e\\TextFormatter\\Plugins\\ConfiguratorBase');
	}

	/**
	* Load a default plugin
	*
	* @param  string $pluginName    Name of the plugin
	* @param  array  $overrideProps Properties of the plugin will be overwritten with those
	* @return ConfiguratorBase
	*/
	public function load($pluginName, array $overrideProps = [])
	{
		// Validate the plugin name / class
		$pluginName = $this->normalizeKey($pluginName);
		$className  = 's9e\\TextFormatter\\Plugins\\' . $pluginName . '\\Configurator';

		if (!class_exists($className))
		{
			throw new RuntimeException("Class '" . $className . "' does not exist");
		}

		// Create the plugin
		$plugin = new $className($this->configurator, $overrideProps);

		// Save it
		$this->set($pluginName, $plugin);

		// Return it
		return $plugin;
	}

	/**
	* {@inheritdoc}
	*/
	public function asConfig()
	{
		$plugins = parent::asConfig();

		// Adjust plugins' default properties
		foreach ($plugins as $pluginName => &$pluginConfig)
		{
			$plugin = $this->get($pluginName);

			// Add base properties
			$pluginConfig += $plugin->getBaseProperties();

			// Remove quickMatch if it's false
			if ($pluginConfig['quickMatch'] === false)
			{
				unset($pluginConfig['quickMatch']);
			}

			// Remove regexpLimit if there's no regexp
			if (!isset($pluginConfig['regexp']))
			{
				unset($pluginConfig['regexpLimit']);
			}

			// Add the JavaScript parser (generated dynamically)
			if (!isset($pluginConfig['parser']))
			{
				$pluginConfig['parser'] = new Variant;
				$pluginConfig['parser']->setDynamic('JS', [$plugin, 'getJSParser']);
			}

			// Remove className if it's a default plugin using its default name. Its class name will
			// be generated by the parser automatically
			$className = 's9e\\TextFormatter\\Plugins\\' . $pluginName . '\\Parser';
			if ($pluginConfig['className'] === $className)
			{
				unset($pluginConfig['className']);
			}
		}
		unset($pluginConfig);

		return $plugins;
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

use RuntimeException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\Validators\TagName;

class TagCollection extends NormalizedCollection
{
	/**
	* {@inheritdoc}
	*/
	protected function getAlreadyExistsException($key)
	{
		return new RuntimeException("Tag '" . $key . "' already exists");
	}

	/**
	* {@inheritdoc}
	*/
	protected function getNotExistException($key)
	{
		return new RuntimeException("Tag '" . $key . "' does not exist");
	}

	/**
	* Normalize a tag name used as a key in this colelction
	*
	* @param  string $key Original name
	* @return string      Normalized name
	*/
	public function normalizeKey($key)
	{
		return TagName::normalize($key);
	}

	/**
	* Normalize a value to an instance of Tag
	*
	* @param  array|null|Tag $value
	* @return Tag
	*/
	public function normalizeValue($value)
	{
		return ($value instanceof Tag)
		     ? $value
		     : new Tag($value);
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

use s9e\TextFormatter\Configurator\Validators\TemplateParameterName;

class TemplateParameterCollection extends NormalizedCollection
{
	/**
	* Normalize a parameter name
	*
	* @param  string $key
	* @return string
	*/
	public function normalizeKey($key)
	{
		return TemplateParameterName::normalize($key);
	}

	/**
	* Normalize a parameter value
	*
	* @param  mixed  $value
	* @return string
	*/
	public function normalizeValue($value)
	{
		return (string) $value;
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;

use s9e\TextFormatter\Configurator\Traits\TemplateSafeness;

class AttributeFilter extends Filter
{
	use TemplateSafeness;

	/**
	* Constructor
	*
	* @param  callable $callback
	* @return void
	*/
	public function __construct($callback)
	{
		parent::__construct($callback);

		// Set the default signature
		$this->resetParameters();
		$this->addParameterByName('attrValue');
	}

	/**
	* Return whether this filter makes a value safe to be used in JavaScript
	*
	* @return bool
	*/
	public function isSafeInJS()
	{
		// List of callbacks that make a value safe to be used in a script, hardcoded for
		// convenience. Technically, there are numerous built-in PHP functions that would make an
		// arbitrary value safe in JS, but only a handful have the potential to be used as an
		// attribute filter
		$safeCallbacks = [
			'urlencode',
			'strtotime',
			'rawurlencode'
		];

		if (in_array($this->callback, $safeCallbacks, true))
		{
			return true;
		}

		return $this->isSafe('InJS');
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;

class TagFilter extends Filter
{
	/**
	* Constructor
	*
	* @param  callable $callback
	* @return void
	*/
	public function __construct($callback)
	{
		parent::__construct($callback);

		// Set the default signature
		$this->resetParameters();
		$this->addParameterByName('tag');
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

use InvalidArgumentException;
use s9e\TextFormatter\Configurator\Items\ProgrammableCallback;

abstract class FilterChain extends NormalizedList
{
	/**
	* Get the name of the filter class
	*
	* @return string
	*/
	abstract protected function getFilterClassName();

	/**
	* Test whether this filter chain contains given callback
	*
	* @param  callable $callback
	* @return bool
	*/
	public function containsCallback(callable $callback)
	{
		// Normalize the callback
		$pc = new ProgrammableCallback($callback);
		$callback = $pc->getCallback();
		foreach ($this->items as $filter)
		{
			if ($callback === $filter->getCallback())
			{
				return true;
			}
		}

		return false;
	}

	/**
	* Normalize a value into an TagFilter instance
	*
	* @param  mixed     $value Either a valid callback or an instance of TagFilter
	* @return TagFilter        Normalized filter
	*/
	public function normalizeValue($value)
	{
		$className  = $this->getFilterClassName();
		if ($value instanceof $className)
		{
			return $value;
		}

		if (!is_callable($value))
		{
			throw new InvalidArgumentException('Filter ' . var_export($value, true) . ' is neither callable nor an instance of ' . $className);
		}

		return new $className($value);
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
use s9e\TextFormatter\Configurator\Items\Regexp;

class HostnameList extends NormalizedList
{
	/**
	* Return this hostname list as a regexp's config
	*
	* @return \s9e\TextFormatter\Configurator\Items\Variant|null An instance of Variant that represents a regexp, or NULL if the collection is empty
	*/
	public function asConfig()
	{
		if (empty($this->items))
		{
			return null;
		}

		$regexp = new Regexp($this->getRegexp());

		return $regexp->asConfig();
	}

	/**
	* Return a regexp that matches the list of hostnames
	*
	* @return string
	*/
	public function getRegexp()
	{
		$hosts = [];
		foreach ($this->items as $host)
		{
			$hosts[] = $this->normalizeHostmask($host);
		}

		$regexp = RegexpBuilder::fromList(
			$hosts,
			[
				// Asterisks * are turned into a catch-all expression, while ^ and $ are preserved
				'specialChars' => [
					'*' => '.*',
					'^' => '^',
					'$' => '$'
				]
			]
		);

		return '/' . $regexp . '/DSis';
	}

	/**
	* Normalize a hostmask to a regular expression
	*
	* @param  string $host Hostname or hostmask
	* @return string
	*/
	protected function normalizeHostmask($host)
	{
		if (preg_match('#[\\x80-\xff]#', $host) && function_exists('idn_to_ascii'))
		{
			$host = idn_to_ascii($host);
		}

		if (substr($host, 0, 1) === '*')
		{
			// *.example.com => /\.example\.com$/
			$host = ltrim($host, '*');
		}
		else
		{
			// example.com => /^example\.com$/
			$host = '^' . $host;
		}

		if (substr($host, -1) === '*')
		{
			// example.* => /^example\./
			$host = rtrim($host, '*');
		}
		else
		{
			// example.com => /^example\.com$/
			$host .= '$';
		}

		return $host;
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

use InvalidArgumentException;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\BooleanRulesGenerator;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\TargetedRulesGenerator;

class RulesGeneratorList extends NormalizedList
{
	/**
	* Normalize the value to an object
	*
	* @param  string|BooleanRulesGenerator|TargetedRulesGenerator $generator Either a string, or an instance of a rules generator
	* @return BooleanRulesGenerator|TargetedRulesGenerator
	*/
	public function normalizeValue($generator)
	{
		if (is_string($generator))
		{
			$className = 's9e\\TextFormatter\\Configurator\\RulesGenerators\\' . $generator;

			if (class_exists($className))
			{
				$generator = new $className;
			}
		}

		if (!($generator instanceof BooleanRulesGenerator)
		 && !($generator instanceof TargetedRulesGenerator))
		{
			throw new InvalidArgumentException('Invalid rules generator ' . var_export($generator, true));
		}

		return $generator;
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

use InvalidArgumentException;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
use s9e\TextFormatter\Configurator\Items\Regexp;

class SchemeList extends NormalizedList
{
	/**
	* Return this scheme list as a regexp
	*
	* @return Regexp
	*/
	public function asConfig()
	{
		$regexp = new Regexp('/^' . RegexpBuilder::fromList($this->items) . '$/Di');

		return $regexp->asConfig();
	}

	/**
	* Validate and normalize a scheme name to lowercase, or throw an exception if invalid
	*
	* @link http://tools.ietf.org/html/rfc3986#section-3.1
	*
	* @param  string $scheme URL scheme, e.g. "file" or "ed2k"
	* @return string
	*/
	public function normalizeValue($scheme)
	{
		if (!preg_match('#^[a-z][a-z0-9+\\-.]*$#Di', $scheme))
		{
			throw new InvalidArgumentException("Invalid scheme name '" . $scheme . "'");
		}

		return strtolower($scheme);
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

use s9e\TextFormatter\Configurator\TemplateCheck;

class TemplateCheckList extends NormalizedList
{
	/**
	* Normalize the value to an instance of TemplateCheck
	*
	* @param  mixed         $check Either a string, or an instance of TemplateCheck
	* @return TemplateCheck        An instance of TemplateCheck
	*/
	public function normalizeValue($check)
	{
		if (!($check instanceof TemplateCheck))
		{
			$className = 's9e\\TextFormatter\\Configurator\\TemplateChecks\\' . $check;
			$check     = new $className;
		}

		return $check;
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

use s9e\TextFormatter\Configurator\TemplateNormalization;
use s9e\TextFormatter\Configurator\TemplateNormalizations\Custom;

class TemplateNormalizationList extends NormalizedList
{
	/**
	* Normalize the value to an instance of TemplateNormalization
	*
	* @param  mixed                 $value Either a string, or an instance of TemplateNormalization
	* @return TemplateNormalization        An instance of TemplateNormalization
	*/
	public function normalizeValue($value)
	{
		if ($value instanceof TemplateNormalization)
		{
			return $value;
		}

		if (is_callable($value))
		{
			return new Custom($value);
		}

		$className = 's9e\\TextFormatter\\Configurator\\TemplateNormalizations\\' . $value;

		return new $className;
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items\AttributeFilters;

use s9e\TextFormatter\Configurator\Items\AttributeFilter;

class UrlFilter extends AttributeFilter
{
	/**
	* Constructor
	*
	* @return void
	*/
	public function __construct()
	{
		parent::__construct('s9e\\TextFormatter\\Parser\\BuiltInFilters::filterUrl');

		$this->resetParameters();
		$this->addParameterByName('attrValue');
		$this->addParameterByName('urlConfig');
		$this->addParameterByName('logger');
		$this->setJS('BuiltInFilters.filterUrl');
	}

	/**
	* {@inheritdoc}
	*/
	public function isSafeInCSS()
	{
		return true;
	}

	/**
	* {@inheritdoc}
	*/
	public function isSafeInJS()
	{
		return true;
	}

	/**
	* {@inheritdoc}
	*/
	public function isSafeAsURL()
	{
		return true;
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

class AttributeFilterChain extends FilterChain
{
	/**
	* {@inheritdoc}
	*/
	public function getFilterClassName()
	{
		return 's9e\\TextFormatter\\Configurator\\Items\\AttributeFilter';
	}

	/**
	* Normalize a value into an AttributeFilter instance
	*
	* @param  mixed $value Either a valid callback or an instance of AttributeFilter
	* @return \s9e\TextFormatter\Configurator\Items\AttributeFilter Normalized filter
	*/
	public function normalizeValue($value)
	{
		if (is_string($value) && preg_match('(^#\\w+$)', $value))
		{
			$value = AttributeFilterCollection::getDefaultFilter(substr($value, 1));
		}

		return parent::normalizeValue($value);
	}
}

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

class TagFilterChain extends FilterChain
{
	/**
	* {@inheritdoc}
	*/
	public function getFilterClassName()
	{
		return 's9e\\TextFormatter\\Configurator\\Items\\TagFilter';
	}
}