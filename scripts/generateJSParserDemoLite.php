#!/usr/bin/php
<?php

include __DIR__ . '/../src/TextFormatter/ConfigBuilder.php';

$cb = new \s9e\Toolkit\TextFormatter\ConfigBuilder;

$cb->BBCodes->addPredefinedBBCode('B');
$cb->BBCodes->addPredefinedBBCode('I');
$cb->BBCodes->addPredefinedBBCode('U');
$cb->BBCodes->addPredefinedBBCode('S');
$cb->BBCodes->addPredefinedBBCode('URL');
$cb->BBCodes->addPredefinedBBCode('LIST');
$cb->BBCodes->addPredefinedBBCode('COLOR');

$cb->BBCodes->addBBCode('CODE', array(
	'template' => '<code><xsl:apply-templates/></code>',
	'defaultDescendantRule' => 'deny'
));

$cb->Emoticons->addEmoticon(':)', '<img alt=":)" src="https://github.com/images/icons/public.png"/>');

$cb->loadPlugin('Autolink');
$cb->loadPlugin('Linebreaker');

$cb->addRulesFromHTML5Specs();

$jsParser = $cb->getJSParser(array(
	'compilation'     => 'ADVANCED_OPTIMIZATIONS',
	'disableLogTypes' => array('debug', 'warning', 'error'),
	'enableIEWorkarounds' => false
));

ob_start();
?><!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
	<title>s9e\Toolkit\TextFormatter &bull; Demo Lite</title>
	<style type="text/css">
		#preview
		{
			margin-top: 10px;
			font-family: sans;
			padding: 5px;
			background-color: #eee;
			border: dashed 1px #8af;
			border-radius: 5px;
		}

		code
		{
			display: inline;
			padding: 3px;
			background-color: #fff;
			border-radius: 3px;
		}
	</style>
</head>
<body>
	<div style="width:80%;max-width:800px">
		<form>
			<textarea style="width:99%" rows="15">This is a light version of the TextFormatter parser/renderer, optimized for size.
			
Here, logging has been disabled as well as Internet Explorer compatibility. The source has been minified to <?php echo round(strlen($jsParser) / 1024, 1); ?>KB (<?php echo round(strlen(gzencode($jsParser, 9)) / 1024, 1); ?>KB gzipped) with [url=http://closure-compiler.appspot.com/home]Google Closure Compiler[/url].

The following plugins have been enabled:

[list]
  [*][b]Autolink[/b] — loose URLs such as http://github.com are automatically turned into links

  [*][b]BBCodes[/b]
  [list=circle]
    [*][b]bold[/b], [i]italic[/i], [u]underline[/u], [s]strikethrough[/s],
    [*][color=#f05]co[/color][color=#2f2]lo[/color][color=#02f]r,[/color]
    [*][code][URL][/code], [code:123][CODE][/code:123] and [code][LIST][/code]
  [/list][/*]
  [*][b]Emoticons[/b] — one emoticon :) has been added
  [*][b]Linebreaker[/b] — Linefeeds are converted to &lt;br&gt;
[/list]</textarea>
		</form>
	</div>

	<div id="preview"></div>

	<script type="text/javascript"><?php echo $jsParser; ?>

		var text,
			textarea = document.getElementsByTagName('textarea')[0],
			preview = document.getElementById('preview');

		window.setInterval(function()
		{
			if (textarea.value === text)
			{
				return;
			}

			text = textarea.value;
			s9e.TextFormatter.preview(text, preview);
		}, 20);
	</script>
</body>
</html><?php

file_put_contents(__DIR__ . '/../docs/JSParserDemoLite.html', ob_get_clean());

echo "Done.\n";