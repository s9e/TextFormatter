#!/usr/bin/php
<?php

include __DIR__ . '/../src/autoloader.php';

$generator = new s9e\TextFormatter\Generator;

$generator->BBCodes->addPredefinedBBCode('B');
$generator->BBCodes->addPredefinedBBCode('I');
$generator->BBCodes->addPredefinedBBCode('U');
$generator->BBCodes->addPredefinedBBCode('S');
$generator->BBCodes->addPredefinedBBCode('URL');
$generator->BBCodes->addPredefinedBBCode('COLOR');

$generator->BBCodes->addBBCode('LIST', array(
	'trimBefore'   => true,
	'trimAfter'    => true,
	'ltrimContent' => true,
	'rtrimContent' => true,

	'tagName'  => 'UL',
	'template' => '<ul><xsl:apply-templates/></ul>'
));

$generator->BBCodes->addBBCode('*', array(
	'trimBefore'   => true,
	'trimAfter'    => true,
	'ltrimContent' => true,
	'rtrimContent' => true,

	'tagName'  => 'LI',
	'template' => '<li><xsl:apply-templates/></li>'
));

$generator->BBCodes->addBBCode('CODE', array(
	'template' => '<code><xsl:apply-templates/></code>',
	'defaultDescendantRule' => 'deny'
));

$generator->Emoticons->addEmoticon(':)', '<img alt=":)" src="https://github.com/images/icons/public.png"/>');

$generator->loadPlugin('Autolink');

$generator->addRulesFromHTML5Specs();

$jsParser = $generator->getJSParser(array(
	'compilationLevel'     => 'ADVANCED_OPTIMIZATIONS',
	'setOptimizationHints' => true,
	'unsafeMinification'   => true,
	'xslNamespacePrefix'   => 'x',
	'enableIE'             => false,
	'disableLogTypes'      => array(
		'debug',
		'warning',
		'error'
	),
	'disableAPI' => array(
		'parse',
		'render',
		'getLog',
		'enablePlugin',
		'disablePlugin'
	),
	'enableLivePreviewFastPath' => false
));
file_put_contents('/tmp/z.js', $jsParser);
ob_start();
?><!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
	<title>s9e\TextFormatter &bull; Demo Lite</title>
	<style type="text/css">
		#preview
		{
			margin-top: 10px;
			font-family: sans;
			padding: 5px;
			background-color: #eee;
			border: dashed 1px #8af;
			border-radius: 5px;
			white-space: pre-line;
		}

		code
		{
			padding: 2px;
			background-color: #fff;
			border-radius: 3px;
			border: solid 1px #ddd;
		}
	</style>
</head>
<body>
	<div style="width:80%;max-width:800px">
		<form>
			<textarea style="width:99%" rows="15">This is a special version of [url=https://github.com/s9e/TextFormatter/tree/master/src/ title="s9e\TextFormatter at GitHub.com"]s9e\TextFormatter[/url]'s Live Preview feature, optimized purely for size.

Only some of the plugins have been enabled and aggressive optimizations have been turned on. Logging has been disabled, as well as compatibility with Internet Explorer.

The parser/renderer used on this page page has been generated via [url=https://github.com/s9e/TextFormatter/blob/master/scripts/generateJSParserDemoLite.php]this script[/url]. The source has been minified to <?php echo round(strlen($jsParser) / 1024, 1); ?>KB (<?php echo round(strlen(gzencode($jsParser, 9)) / 1024, 1); ?>KB gzipped) with [url=http://closure-compiler.appspot.com/home]Google Closure Compiler[/url].

The included plugins are:

[list]
  [*][b]Autolink[/b] — loose URLs such as http://github.com are automatically turned into links

  [*][b]BBCodes[/b]
  [list=circle]
    [*][b]bold[/b], [i]italic[/i], [u]underline[/u], [s]strikethrough[/s],
    [*][color=#f05]co[/color][color=#2f2]lo[/color][color=#02f]r,[/color]
    [*][code][URL][/code], [code:123][CODE][/code:123] and [code][LIST][/code]
  [/list][/*]
  [*][b]Emoticons[/b] — one emoticon :) has been added
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

file_put_contents(__DIR__ . '/../../s9e.github.com/TextFormatter/demoLite.html', ob_get_clean());

echo "Done.\n";