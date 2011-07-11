<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2011 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins;

use InvalidArgumentException,
    s9e\TextFormatter\ConfigBuilder,
    s9e\TextFormatter\PluginConfig;

class HTMLEntitiesConfig extends PluginConfig
{
	/**
	* @var string Name of the tag used by this plugin
	*/
	protected $tagName = 'HE';

	/**
	* @var string Name of the attribute used by this plugin
	*/
	protected $attrName = 'char';

	/**
	* @var array  List of entities NOT to decode
	*/
	protected $disabled = array();

	public function setUp()
	{
		if (!$this->cb->tagExists($this->tagName))
		{
			$this->cb->addTag($this->tagName);
			$this->cb->addTagAttribute($this->tagName, $this->attrName, 'text');
			$this->cb->setTagTemplate($this->tagName, '<xsl:value-of select="@' . htmlspecialchars($this->attrName) . '"/>');
		}
	}

	/**
	* Add an emoticon
	*
	* @param string $entity HTML entity, e.g. "&amp;" or "&eacute;"
	*/
	public function disableEntity($entity)
	{
		if (!preg_match('/^&(?:[a-z]+|#[0-9]+|#x[0-9a-f]+);$/Di', $entity, $m))
		{
			throw new InvalidArgumentException("Invalid HTML entity '" . $entity . "'");
		}

		$this->disabled[$entity] = 1;
	}

	/**
	* @return array
	*/
	public function getConfig()
	{
		$config = array(
			'tagName'  => $this->tagName,
			'attrName' => $this->attrName,
			'regexp'   => '/&(?:[a-z]+|#[0-9]+|#x[0-9a-f]+);/i'
		);

		if ($this->disabled)
		{
			$config['disabled'] = $this->disabled;
		}

		return $config;
	}

	//==========================================================================
	// JS Parser stuff
	//==========================================================================

	public function getJSParser()
	{
		return file_get_contents(__DIR__ . '/HTMLEntitiesParser.js');
	}

	public function getJSConfigMeta()
	{
		return array(
			'preserveKeys' => array(
				array('disabled', true)
			)
		);
	}
}