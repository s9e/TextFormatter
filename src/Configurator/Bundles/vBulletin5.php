<?php declare(strict_types=1);

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Bundles;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Bundle;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Bundles\vBulletin5\Helper;

class vBulletin5 extends Bundle
{
	/**
	* {@inheritdoc}
	*/
	public function configure(Configurator $configurator)
	{
		$configurator->rootRules->enableAutoLineBreaks();

		// https://forum.vbulletin.com/help#bbcode_reference/bbcode_basic
		$configurator->BBCodes->addFromRepository('FONT');
		$configurator->BBCodes->addFromRepository('B');
		$configurator->BBCodes->addFromRepository('I');
		$configurator->BBCodes->addFromRepository('U');
		$configurator->BBCodes->addFromRepository('COLOR');
		$configurator->BBCodes->addFromRepository('SIZE', 'default', ['min' => 8, 'max' => 72]);
		$configurator->BBCodes->addFromRepository('LEFT');
		$configurator->BBCodes->addFromRepository('CENTER');
		$configurator->BBCodes->addFromRepository('RIGHT');
		$configurator->BBCodes->addCustom(
			'[INDENT]{TEXT}[/INDENT]',
			'<blockquote>{TEXT}</blockquote>'
		);
		$configurator->BBCodes->addFromRepository('NOPARSE');
		
		$configurator->BBCodes->addCustom(
			'[USER user={TEXT;useContent}]{TEXT}[/USER]',
			'@<a><xsl:value-of select="@user"/></a>'
		);
		$configurator->Preg->match('/@"(?<user>[^"]+)"/',   'USER');
		$configurator->Preg->match('/@(?!")(?<user>\\S+)/', 'USER');

		// https://forum.vbulletin.com/help#bbcode_reference/bbcode_links
		$configurator->BBCodes->addFromRepository('URL');
		$configurator->BBCodes->addFromRepository('EMAIL');
		$configurator->BBCodes->addCustom(
			'[NODE node_id={UINT;useContent}]{TEXT}[/NODE]',
			'<a href="{$NODE_URL}{@node_id}">{TEXT}</a>'
		);

		// https://forum.vbulletin.com/help#bbcode_reference/bbcode_quotes
		$configurator->BBCodes->addFromRepository('QUOTE');
		$this->configureQuoteTag($configurator->tags['QUOTE']);

		// https://forum.vbulletin.com/help#bbcode_reference/bbcode_media
		$configurator->BBCodes->addFromRepository('IMG');
		$this->configureVideoTag($configurator);
		$configurator->BBCodes->addCustom(
			'[ATTACH filename={TEXT;useContent}]',
			'<span><xsl:value-of select="@filename"/></span>'
		);

		// https://forum.vbulletin.com/help#bbcode_reference/bbcode_smilies
		$emoticons = [
			':)'         => "\u{1F642}",
			':('         => "\u{2639}",
			';)'         => "\u{1F609}",
			':D'         => "\u{1F600}",
			':mad:'      => "\u{1F621}",
			':cool:'     => "\u{1F60E}",
			':p'         => "\u{1F61B}",
			':o'         => "\u{1F633}",
			':rolleyes:' => "\u{1F644}",
			':eek:'      => "\u{1F632}",
			':confused:' => "\u{1F615}"
		];
		foreach ($emoticons as $code => $alias)
		{
			$configurator->Emoji->aliases[$code] = $alias;
		}

		// https://forum.vbulletin.com/help#bbcode_reference/bbcode_lists
		$configurator->BBCodes->addFromRepository('LIST');
		$configurator->BBCodes->addFromRepository('*');

		// https://forum.vbulletin.com/help#bbcode_reference/bbcode_tables
		$configurator->BBCodes->addFromRepository('TABLE');
		$configurator->BBCodes->addFromRepository('TR');
		$configurator->BBCodes->addFromRepository('TD');

		// https://forum.vbulletin.com/help#bbcode_reference/bbcode_code
		$configurator->BBCodes->addFromRepository('CODE');
		$configurator->BBCodes->add('HTML')->tagName = 'CODE';
		$configurator->BBCodes->add('PHP')->tagName  = 'CODE';
		$configurator->tags['CODE']->filterChain
			->prepend(Helper::class . '::filterCodeTag($tag, $tagText)');

		$configurator->Autoemail;
		$configurator->Autolink;
	}

	protected function configureQuoteTag(Tag $tag): void
	{
		$attribute = $tag->attributes->add('node_id');
		$attribute->filterChain->append('#uint');
		$attribute->required = false;

		// Split "John Doe;1" into @author and @node_id values
		$tag->attributePreprocessors->add('author', '/^(?<author>.*);(?<node_id>\\d+)$/');

		// Set blockquote's cite to {$NODE_URL}{@node_id}
		$dom  = $tag->template->asDOM();
		$cite = $dom->firstOf('//blockquote')
		            ->prependXslIf('@node_id')
		            ->appendXslAttribute('cite');
		$cite->appendXslValueOf('$NODE_URL');
		$cite->appendXslValueOf('@node_id');

		$dom->saveChanges();
	}

	protected function configureVideoTag(Configurator $configurator): void
	{
		(new MediaPack)->configure($configurator);

		$bbcode = $configurator->BBCodes->add('VIDEO');
		$bbcode->contentAttributes[] = 'url';
		$bbcode->tagName             = 'MEDIA';
	}
}