<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Emoji;

use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
use s9e\TextFormatter\Configurator\Items\Regexp;
use s9e\TextFormatter\Configurator\Items\Variant;
use s9e\TextFormatter\Plugins\ConfiguratorBase;

class Configurator extends ConfiguratorBase
{
	/**
	* @var string Name of the attribute used by this plugin
	*/
	protected $attrName = 'seq';

	/**
	* @var array Associative array of alias => emoji
	*/
	protected $aliases = [];

	/**
	* @var bool Whether to force the image size in the img tag
	*/
	protected $forceImageSize = true;

	/**
	* @var string Emoji set to use
	*/
	protected $imageSet = 'twemoji';

	/**
	* @var integer Target size for the emoji images
	*/
	protected $imageSize = 16;

	/**
	* @var string Preferred image type
	*/
	protected $imageType = 'png';

	/**
	* @var string Name of the tag used by this plugin
	*/
	protected $tagName = 'EMOJI';

	/**
	* Plugin's setup
	*
	* Will create the tag used by this plugin
	*/
	protected function setUp()
	{
		if (isset($this->configurator->tags[$this->tagName]))
		{
			return;
		}

		$tag = $this->configurator->tags->add($this->tagName);
		$tag->attributes->add($this->attrName)->filterChain->append(
			$this->configurator->attributeFilters['#identifier']
		);
		$this->resetTemplate();
	}

	/**
	* Add an emoji alias
	*
	* @param  string $alias
	* @param  string $emoji
	* @return void
	*/
	public function addAlias($alias, $emoji)
	{
		$this->aliases[$alias] = $emoji;
	}

	/**
	* Force the size of the image to be set in the img element
	*
	* @return void
	*/
	public function forceImageSize()
	{
		$this->forceImageSize = true;
		$this->resetTemplate();
	}

	/**
	* Remove an emoji alias
	*
	* @param  string $alias
	* @return void
	*/
	public function removeAlias($alias)
	{
		unset($this->aliases[$alias]);
	}

	/**
	* Omit the size of the image in the img element
	*
	* @return void
	*/
	public function omitImageSize()
	{
		$this->forceImageSize = false;
		$this->resetTemplate();
	}

	/**
	* Get all emoji aliases
	*
	* @return array
	*/
	public function getAliases()
	{
		return $this->aliases;
	}

	/**
	* Set the size of the images used for emoji
	*
	* @param  integer $size Preferred size
	* @return void
	*/
	public function setImageSize($size)
	{
		$this->imageSize = (int) $size;
		$this->resetTemplate();
	}

	/**
	* Use the EmojiOne image set
	*
	* @return void
	*/
	public function useEmojiOne()
	{
		$this->imageSet = 'emojione';
		$this->resetTemplate();
	}

	/**
	* Use PNG images if available
	*
	* @return void
	*/
	public function usePNG()
	{
		$this->imageType = 'png';
		$this->resetTemplate();
	}

	/**
	* Use SVG images if available
	*
	* @return void
	*/
	public function useSVG()
	{
		$this->imageType = 'svg';
		$this->resetTemplate();
	}

	/**
	* Use the Twemoji image set
	*
	* @return void
	*/
	public function useTwemoji()
	{
		$this->imageSet = 'twemoji';
		$this->resetTemplate();
	}

	/**
	* {@inheritdoc}
	*/
	public function asConfig()
	{
		$config = [
			'attrName' => $this->attrName,
			'tagName'  => $this->tagName
		];

		if (!empty($this->aliases))
		{
			$aliases = array_keys($this->aliases);
			$regexp  = '/' . RegexpBuilder::fromList($aliases) . '/';

			$config['aliases']       = $this->aliases;
			$config['aliasesRegexp'] = new Regexp($regexp, true);

			$quickMatch = ConfigHelper::generateQuickMatchFromList($aliases);
			if ($quickMatch !== false)
			{
				$config['aliasesQuickMatch'] = $quickMatch;
			}
		}

		return $config;
	}

	/**
	* {@inheritdoc}
	*/
	public function getJSHints()
	{
		$quickMatch = ConfigHelper::generateQuickMatchFromList(array_keys($this->aliases));

		return [
			'EMOJI_HAS_ALIASES'          => !empty($this->aliases),
			'EMOJI_HAS_ALIAS_QUICKMATCH' => ($quickMatch !== false)
		];
	}

	/**
	* Get the content of the src attribute used to display EmojiOne's images
	*
	* @return string
	*/
	protected function getEmojiOneSrc()
	{
		$src  = '//cdn.jsdelivr.net/emojione/assets/' . $this->imageType . '/';
		$src .= "<xsl:if test=\"contains(@seq, '-20e3') or @seq = 'a9' or @seq = 'ae'\">00</xsl:if>";
		$src .= "<xsl:value-of select=\"translate(@seq, 'abcdef', 'ABCDEF')\"/>";
		$src .= '.' . $this->imageType;

		return $src;
	}

	/**
	* Get the first available size that satisfies our size requirement
	*
	* @param  integer[] $sizes Available sizes
	* @return integer
	*/
	protected function getTargetSize(array $sizes)
	{
		$k = 0;
		foreach ($sizes as $k => $size)
		{
			if ($size >= $this->imageSize)
			{
				break;
			}
		}

		return $sizes[$k];
	}

	/**
	* Get this tag's template
	*
	* @return string
	*/
	protected function getTemplate()
	{
		$template = '<img alt="{.}" class="emoji" draggable="false"';
		if ($this->forceImageSize)
		{
			$template .= ' width="' . $this->imageSize . '" height="' . $this->imageSize . '"';
		}
		$template .= '><xsl:attribute name="src">';
		$template .= ($this->imageSet === 'emojione') ? $this->getEmojiOneSrc() :  $this->getTwemojiSrc();
		$template .= '</xsl:attribute></img>';

		return $template;
	}

	/**
	* Get the content of the src attribute used to display Twemoji's images
	*
	* @return string
	*/
	protected function getTwemojiSrc()
	{
		$src = '//twemoji.maxcdn.com/';
		if ($this->imageType === 'svg')
		{
			$src .= 'svg';
		}
		else
		{
			$size = $this->getTargetSize([16, 36, 72]);
			$src .= $size . 'x' . $size;
		}
		$src .= '/<xsl:value-of select="@seq"/>.' . $this->imageType;

		return $src;
	}

	/**
	* Reset the template used by this plugin's tag
	*
	* @return void
	*/
	protected function resetTemplate()
	{
		$this->getTag()->template = $this->getTemplate();
	}
}