#!/usr/bin/php
<?php

include __DIR__ . '/../src/s9e/TextFormatter/autoloader.php';

$configurator = new s9e\TextFormatter\Configurator;

$configurator->BBCodes->addFromRepository('B');
$configurator->BBCodes->addFromRepository('I');
$configurator->BBCodes->addFromRepository('U');
$configurator->BBCodes->addFromRepository('S');
$configurator->BBCodes->addFromRepository('URL');
$configurator->BBCodes->addFromRepository('QUOTE');
$configurator->BBCodes->addFromRepository('LIST');
$configurator->BBCodes->addFromRepository('*');
$configurator->BBCodes->addFromRepository('C');
$configurator->BBCodes->addFromRepository('COLOR');
$configurator->BBCodes->addFromRepository('FLOAT');
$configurator->BBCodes->addFromRepository('YOUTUBE', 'default', array(
	'width'  => 240,
	'height' => 180
));

$configurator->Censor->add('apple', 'banana');
$configurator->Emoticons->add(':)', '<img src="s.png" alt=":)"/>');
$configurator->Generic->add(
	'/#(?<tag>[a-z0-9]+)/i',
	'<a href="https://twitter.com/#!/search/%23{@tag}"><xsl:apply-templates/></a>'
);
$configurator->HTMLElements->allowElement('a');
$configurator->HTMLElements->allowElement('b');
$configurator->HTMLElements->allowAttribute('a', 'href');
$configurator->HTMLElements->allowAttribute('a', 'title');

$configurator->plugins->load('Autolink');
$configurator->plugins->load('HTMLEntities');
$configurator->plugins->load('FancyPants');

$configurator->addHTML5Rules();

$js = 'var xsl=' . json_encode($configurator->stylesheet->get()) . ";\n";
$js .= $configurator->javascript->getParser();
$js .= file_get_contents(__DIR__ . '/../src/s9e/TextFormatter/live.js');

ob_start();
?><!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
	<title>s9e\TextFormatter &bull; Demo</title>
	<style type="text/css">
		#preview
		{
			font-family: sans;
			padding: 5px;
			background-color: #eee;
			border: dashed 1px #8af;
			border-radius: 5px;
		}

		code
		{
			padding: 2px;
			background-color: #fff;
			border-radius: 3px;
			border: solid 1px #ddd;
		}

		object
		{
			transition: width .5s, height .5s;
			-transition: width .5s, height .5s;
			-o-transition: width .5s, height .5s;
			-moz-transition: width .5s, height .5s;
			-webkit-transition: width .5s, height .5s;
		}
	</style>

</head>
<body>
	<div style="float:left;width:80%;max-width:800px">
		<form>
			<textarea style="width:99%" rows="15">[float=right][youtube width=240 height=180]http://www.youtube.com/watch?v=QH2-TGUlwu4[/youtube][/float]

This is a demo of the JavaScript port of [url=https://github.com/s9e/TextFormatter/tree/master/src/ title="s9e\TextFormatter at GitHub.com"]s9e\TextFormatter[/url].

The following plugins have been enabled:

[list]
  [*][b]Autolink[/b] --- loose URLs such as http://github.com are automatically turned into links
  [*][b]BBCodes[/b]
  [list=circle]
    [*][b]bold[/b], [i]italic[/i], [u]underline[/u], [s]strikethrough[/s],
    [*][color=#f05]co[/color][color=#2f2]lo[/color][color=#02f]r,[/color]
    [*][C][URL][/C], [C:123][C][/C:123], [C][YOUTUBE][/C], [C][FLOAT][/C], and [C][LIST][/C]
  [/list][/*]
  [*][b]Censor[/b] --- the word "apple" is censored and automatically replaced with "banana"
  [*][b]Emoticons[/b] --- one emoticon :) has been added
  [*][b]FancyPants[/b] --- some typography is enhanced, e.g. (c) (tm) and "quotes"
  [*][b]Generic[/b] --- the Generic plugin provides a way to perform generic regexp-based replacements that are HTML-safe. Here, text that matches [C]/#(?<tag>[a-z0-9]+)/i[/C] is replaced with the template [C]<a href="https://twitter.com/#!/search/%23{@tag}"><xsl:apply-templates/></a>[/C] -- For example: #PHP, #fml
  [*][b]HTMLElements[/b] --- [C]<a>[/C] and [C]<b>[/C] tags are allowed, with two whitelisted attributes for [C]<a>[/C]: [C]href[/C] and [C]title[/C]. Example: <a href="https://github.com" title="GitHub - Social Coding"><b>GitHub</b></a>
  [*][b]HTMLEntities[/b] --- HTML entities such as &amp;hearts; are decoded
[/list]

The parser/renderer used on this page page has been generated via [url=https://github.com/s9e/TextFormatter/blob/master/scripts/generateLiveDemo.php]this script[/url].</textarea>
		</form>
	</div>

	<div style="float:left;">
		<form><?php

			$list = array();

			foreach ($configurator->plugins as $pluginName => $plugin)
			{
				$list[$pluginName] = '<input type="checkbox" id="' . $pluginName . '" checked="checked" onchange="toggle(this)"><label for="' . $pluginName . '">&nbsp;'. $pluginName . '</label>';
			}

			ksort($list);
			echo implode('<br>', $list);

		?></form>
	</div>

	<div style="clear:both"></div>

	<div id="preview"></div>

	<script type="text/javascript"><?php echo $js; ?>

		var text,
			textareaEl = document.getElementsByTagName('textarea')[0],
			previewEl = document.getElementById('preview');

		window.setInterval(function()
		{
			if (textareaEl.value === text)
			{
				return;
			}

			text = textareaEl.value;
			preview(text, previewEl);
		}, 20);

		function toggle(el)
		{
			((el.checked) ? enablePlugin : disablePlugin)(el.id);
			text = '';
		}
	</script>
</body>
</html><?php

file_put_contents(__DIR__ . '/../../s9e.github.io/TextFormatter/demo.html', ob_get_clean());

echo "Done.\n";