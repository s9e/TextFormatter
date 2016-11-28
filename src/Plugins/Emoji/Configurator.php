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
	protected $imageSet = 'emojione';

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
	* @var string[] List of Twemoji sequences that do not match EmojiOne's
	*/
	protected $twemojiAliases = [
		'00a9'                    => 'a9',
		'00ae'                    => 'ae',
		'0023-20e3'               => '23-20e3',
		'002a-20e3'               => '2a-20e3',
		'0030-20e3'               => '30-20e3',
		'0031-20e3'               => '31-20e3',
		'0032-20e3'               => '32-20e3',
		'0033-20e3'               => '33-20e3',
		'0034-20e3'               => '34-20e3',
		'0035-20e3'               => '35-20e3',
		'0036-20e3'               => '36-20e3',
		'0037-20e3'               => '37-20e3',
		'0038-20e3'               => '38-20e3',
		'0039-20e3'               => '39-20e3',
		'1f3f3-1f308'             => '1f3f3-fe0f-200d-1f308',
		'1f3f4-2620'              => '1f3f4-200d-2620-fe0f',
		'1f441-1f5e8'             => '1f441-200d-1f5e8',
		'1f468-1f468-1f466-1f466' => '1f468-200d-1f468-200d-1f466-200d-1f466',
		'1f468-1f468-1f466'       => '1f468-200d-1f468-200d-1f466',
		'1f468-1f468-1f467-1f466' => '1f468-200d-1f468-200d-1f467-200d-1f466',
		'1f468-1f468-1f467-1f467' => '1f468-200d-1f468-200d-1f467-200d-1f467',
		'1f468-1f468-1f467'       => '1f468-200d-1f468-200d-1f467',
		'1f468-1f469-1f466-1f466' => '1f468-200d-1f469-200d-1f466-200d-1f466',
		'1f468-1f469-1f466'       => '1f468-200d-1f469-200d-1f466',
		'1f468-1f469-1f467-1f466' => '1f468-200d-1f469-200d-1f467-200d-1f466',
		'1f468-1f469-1f467-1f467' => '1f468-200d-1f469-200d-1f467-200d-1f467',
		'1f468-1f469-1f467'       => '1f468-200d-1f469-200d-1f467',
		'1f468-2764-1f468'        => '1f468-200d-2764-fe0f-200d-1f468',
		'1f468-2764-1f48b-1f468'  => '1f468-200d-2764-fe0f-200d-1f48b-200d-1f468',
		'1f469-1f469-1f466-1f466' => '1f469-200d-1f469-200d-1f466-200d-1f466',
		'1f469-1f469-1f466'       => '1f469-200d-1f469-200d-1f466',
		'1f469-1f469-1f467-1f466' => '1f469-200d-1f469-200d-1f467-200d-1f466',
		'1f469-1f469-1f467-1f467' => '1f469-200d-1f469-200d-1f467-200d-1f467',
		'1f469-1f469-1f467'       => '1f469-200d-1f469-200d-1f467',
		'1f469-2764-1f468'        => '1f469-200d-2764-fe0f-200d-1f468',
		'1f469-2764-1f469'        => '1f469-200d-2764-fe0f-200d-1f469',
		'1f469-2764-1f48b-1f468'  => '1f469-200d-2764-fe0f-200d-1f48b-200d-1f468',
		'1f469-2764-1f48b-1f469'  => '1f469-200d-2764-fe0f-200d-1f48b-200d-1f469'
	];

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
		$src .= '<xsl:value-of select="@seq"/>';
		$src .= '.' . $this->imageType;

		return $src;
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
		$src  = '//twemoji.maxcdn.com/2/';
		$src .= ($this->imageType === 'svg') ? 'svg' : '72x72';
		$src .= '/<xsl:choose>';
		foreach ($this->twemojiAliases as $seq => $filename)
		{
			$src .= '<xsl:when test="@seq=\'' . $seq . '\'">' . $filename . '</xsl:when>';
		}
		$src .= '<xsl:otherwise><xsl:value-of select="@seq"/></xsl:otherwise></xsl:choose>';
		$src .= '.' . $this->imageType;

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