<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2010-2011 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\TextFormatter\Plugins;

use Exception,
    InvalidArgumentException,
    s9e\Toolkit\TextFormatter\ConfigBuilder,
    s9e\Toolkit\TextFormatter\PluginConfig;

class GenericConfig extends PluginConfig
{
	/**
	* @var array Associative array of regexps. The keys are the corresponding tag names
	*/
	protected $regexp = array();

	/**
	* Add a generic replacement
	*
	* @param  string $regexp
	* @param  string $template
	* @return string           The name of the tag
	*/
	public function addReplacement($regexp, $template)
	{
		$valid = false;

		try
		{
			$valid = @preg_match_all($regexp, '', $m);
		}
		catch (Exception $e)
		{
		}

		if ($valid === false)
		{
			throw new InvalidArgumentException('Invalid regexp');
		}

		/**
		* Generate a tag name based on the regexp
		*/
		$tagName = 'g' . dechex(crc32($regexp));

		/**
		* Create the tag
		*/
		$this->cb->addTag($tagName);

		/**
		* Capture the attribute names
		*
		* Theorically, it could capture invalid stuff like \\(?P<foo' but the chance of having
		* a real-world valid regexp that uses such a construct is nil
		*/
		preg_match_all("#\\(\\?(?:P?\\<|')([a-z_0-9]+)[\\>']#", $regexp, $m);

		foreach ($m[1] as $attrName)
		{
			$this->cb->addTagAttribute($tagName, $attrName, 'text');
		}

		/**
		* Set the template
		*/
		$this->cb->setTagTemplate($tagName, $template);

		/**
		* Finally, record the replacement
		*/
		$this->regexp[$tagName] = $regexp;

		return $tagName;
	}

	public function getConfig()
	{
		if (empty($this->regexp))
		{
			return false;
		}

		return array('regexp' => $this->regexp);
	}
}