#!/usr/bin/php
<?php

include __DIR__ . '/../src/autoloader.php';

$configurator = new s9e\TextFormatter\Configurator;

$configurator->disallowHost('*.example.com');
$configurator->setDefaultScheme('https');

$configurator->BBCodes->addPredefinedBBCode('B');
$configurator->BBCodes->addPredefinedBBCode('I');
$configurator->BBCodes->addPredefinedBBCode('U');
$configurator->BBCodes->addPredefinedBBCode('S');
$configurator->BBCodes->addPredefinedBBCode('URL');
$configurator->BBCodes->addPredefinedBBCode('LIST');
$configurator->BBCodes->addPredefinedBBCode('COLOR');
$configurator->BBCodes->addPredefinedBBCode('YOUTUBE');
$configurator->BBCodes->addPredefinedBBCode('FLOAT');

$configurator->BBCodes->addBBCode('CODE', array(
	'template' => '<code><xsl:apply-templates/></code>',
	'defaultDescendantRule' => 'deny'
));

$configurator->Emoticons->addEmoticon(':)', '<img alt=":)" src="https://github.com/images/icons/public.png"/>');
// Limit the number of emoticons to 7
$configurator->setTagOption('E', 'tagLimit', 7);

$configurator->Censor->addWord('apple', 'banana');

$configurator->Generic->addReplacement(
	'/#(?<tag>[a-z0-9]+)/i',
	'<a href="https://twitter.com/#!/search/%23{@tag}"><xsl:apply-templates/></a>'
);

$configurator->HTMLElements->allowElement('a');
$configurator->HTMLElements->allowElement('b');
$configurator->HTMLElements->allowAttribute('a', 'href');
$configurator->HTMLElements->allowAttribute('a', 'title');

$configurator->loadPlugin('Autolink');
$configurator->loadPlugin('Escaper');
$configurator->loadPlugin('HTMLEntities')->disableEntity('&lt;');
$configurator->loadPlugin('Linebreaker');
$configurator->loadPlugin('WittyPants');

$configurator->addRulesFromHTML5Specs();

$jsParser = $configurator->getJSParser(array(
	'compilationLevel'          => 'ADVANCED_OPTIMIZATIONS',
	'enableLivePreviewFastPath' => true,
	'setOptimizationHints'      => true
));

ob_start();
?><!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
	<title>s9e\TextFormatter &bull; Demo</title>
	<style type="text/css">
		div
		{
			margin-bottom: 10px;
		}

		#logdiv
		{
			max-height: 120px;
			overflow: auto;
		}

		#logdiv,
		#preview
		{
			font-family: sans;
			padding: 5px;
			background-color: #eee;
			border: dashed 1px #8af;
			border-radius: 5px;
		}

		#preview > pre
		{
			white-space: pre-line;
		}

		label
		{
			cursor: pointer;
		}

		code
		{
			padding: 2px;
			background-color: #fff;
			border-radius: 3px;
			border: solid 1px #ddd;
		}

		pre
		{
			margin: 0;
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

	<!--[if lt IE 8]>
	<style type="text/css">
	#preview > pre
	{
		white-space: pre;
		word-wrap:   break-word;
	}
	</style>
	<![endif]-->

</head>
<body>
	<div style="float:left;width:80%;max-width:800px">
		<form>
			<textarea style="width:99%" rows="15">[float=right][youtube width=240 height=180]http://www.youtube.com/watch?v=QH2-TGUlwu4[/youtube][/float]

This is a demo of the Javascript port of [url=https://github.com/s9e/TextFormatter/tree/master/src/ title="s9e\TextFormatter at GitHub.com"]s9e\\TextFormatter[/url].

The following plugins have been enabled:

[list]
  [*][b]Autolink[/b] --- loose URLs such as http://github.com are automatically turned into links

  [*][b]BBCodes[/b]
  [list=circle]
    [*][b]bold[/b], [i]italic[/i], [u]underline[/u], [s]strikethrough[/s],
    [*][color=#f05]co[/color][color=#2f2]lo[/color][color=#02f]r,[/color]
    [*][CODE][URL][/CODE], [CODE:123][CODE][/CODE:123], [CODE][YOUTUBE][/CODE], [CODE][FLOAT][/CODE], and [CODE][LIST][/CODE]
  [/list][/*]

  [*][b]Censor[/b] --- the word "apple" is censored and automatically replaced with "banana"
  [*][b]Emoticons[/b] --- one emoticon :) has been added. The maximum number of emoticons has been arbitrarily set to 7
  [*][b]Escaper[/b] --- a backslash can be used to escape one character, e.g. \:)
  [*][b]Generic[/b] --- the Generic plugin provides a way to perform generic regexp-based replacements that are HTML-safe. Here, text that matches [CODE]/#(?<tag>[a-z0-9]+)/i[/CODE] is replaced with the template [CODE]<a href="https://twitter.com/#!/search/%23{@tag}"><xsl:apply-templates/></a>[/CODE] -- For example: #PHP, #fml
  [*][b]HTMLEntities[/b] --- HTML entities such as &amp;hearts; are decoded
  [*][b]Linebreaker[/b] --- Linefeeds are converted to &lt;br&gt;
  [*][b]HTMLElements[/b] --- [CODE]<a>[/CODE] and [CODE]<b>[/CODE] tags are allowed, with two whitelisted attributes for [CODE]<a>[/CODE]: [CODE]href[/CODE] and [CODE]title[/CODE]. Example: <a href="https://github.com" title="GitHub - Social Coding"><b>GitHub</b></a>
  [*][b]WittyPants[/b] --- some typography is enhanced, e.g. (c) (tm) and "quotes"
[/list]

Additionally, in order to demonstrate some other features:

[list=square]
  [*][b]Configurator::disallowHost()[/b] --- links to [url=http://example.com]example.com[/url] are disabled. This applies to [b]Autolink[/b] and [b]HTMLElements[/b] as well: <a href="http://example.com">example.com</a>
  [*][b]Configurator::setDefaultScheme('https')[/b] --- schemeless URLs are allowed and they are treated as if their scheme was 'https'
  [*][b]HTMLEntitiesConfig::disableEntity()[/b] --- the HTML entity &amp;lt; is arbitrarily disabled
  [*]a YouTube video, at the right, keeps playing as you're editing the text [i](including its own tag!)[/i] to demonstrate the partial-update algorithm used to refresh the live preview
[/list]

You can take a look at the log, hover the messages with the mouse and click them to get to the part of the text that generated them.

The parser/renderer used on this page page has been generated via [url=https://github.com/s9e/TextFormatter/blob/master/scripts/generateJSParserDemo.php]this script[/url]. It has been minified to <?php echo round(strlen($jsParser) / 1024, 1) . 'KB (' . round(strlen(gzencode($jsParser, 9)) / 1024, 1) . 'KB gzipped)'; ?> with [url=http://closure-compiler.appspot.com/home]Google Closure Compiler[/url]. The raw sources can be found [url=https://github.com/s9e/TextFormatter/blob/master/src/TextFormatter.js]at GitHub[/url].</textarea>
			<br>
			<select id="output">
				<option value="preview" selected>Live Preview</option>
				<option value="html">Show HTML</option>
				<option value="xml">Show XML</option>
			</select>
			<input type="checkbox" id="logcheck"><label for="logcheck"> Show log</label>
		</form>
	</div>

	<div style="float:left;">
		<form><?php

			$plugins = $configurator->getLoadedPlugins();
			ksort($plugins);

			foreach ($plugins as $pluginName => $plugin)
			{
				echo '<input type="checkbox" id="', $pluginName, '" checked="checked" onchange="toggle(this)"><label for="', $pluginName, '">&nbsp;', $pluginName, '</label><br>';
			}

		?></form>
	</div>

	<div style="clear:both"></div>

	<div id="logdiv" style="display:none"></div>

	<div id="preview"></div>

	<script type="text/javascript"><?php echo $jsParser; ?>

		var text,

			textarea = document.getElementsByTagName('textarea')[0],
			preview = document.getElementById('preview'),
			pre = false,

			outputSelect = document.getElementById('output'),

			logcheck = document.getElementById('logcheck'),
			logdiv = document.getElementById('logdiv'),
			disableHighlight = false;

		outputSelect.onchange = function()
		{
			if (outputSelect.value === 'preview')
			{
				pre = false;
			}

			refreshOutput();
		}

		textarea.onmouseout = function()
		{
			disableHighlight = false;
		}

		logcheck.onchange = function()
		{
			if (logcheck.checked)
			{
				logdiv.style.display = '';
				refreshLog();
			}
			else
			{
				logdiv.style.display = 'none';
			}
		}

		function refreshOutput()
		{
			if (outputSelect.value === 'preview')
			{
				s9e.TextFormatter.preview(text, preview);
			}
			else
			{
				var xml = s9e.TextFormatter.parse(text),
					content = (outputSelect.value === 'html') ? s9e.TextFormatter.render(xml) : xml;

				if (!pre)
				{
					preview.innerHTML = '<pre></pre>';
					pre = preview.firstChild;
				}

				if ('textContent' in pre)
				{
					pre.textContent = content;
				}
				else
				{
					pre.innerText = content;
				}
			}
		}

		function refreshLog()
		{
			var log = s9e.TextFormatter.getLog(),
				msgs = [];

			['error', 'warning', 'debug'].forEach(function(type)
			{
				if (!log[type])
				{
					return;
				}

				log[type].forEach(function(entry)
				{
					var msg = '[' + type + '] [' + entry.pluginName + '] ' + entry.msg.replace(
							/%(?:([0-9])\$)?[sd]/g,
							function(str, p1)
							{
								return entry.params[(p1 ? p1 - 1 : 0)];
							}
						);

					if (entry.pos !== undefined)
					{
						if (!entry.len)
						{
							entry.len = 0;
						}

						msg = '<a style="cursor:pointer" onmouseover="highlight(' + entry.pos + ',' + entry.len + ')" onclick="select(' + entry.pos + ',' + entry.len + ')">' + msg + '</a>';
					}

					msgs.push(msg);
				});
			});

			logdiv.innerHTML = (msgs.length) ? msgs.join('<br/>') : 'No log';
		}

		function highlight(pos, len)
		{
			if (disableHighlight)
			{
				return;
			}

			if (textarea.setSelectionRange)
			{
				textarea.setSelectionRange(pos, pos + len);
			}
			else
			{
				var range = textarea.createTextRange();
				range.collapse(true);
				range.moveEnd('character', pos + len);
				range.moveStart('character', pos);
				range.select();
			}
		}

		function select(pos, len)
		{
			disableHighlight = false;
			textarea.focus();
			highlight(pos, len);
			disableHighlight = true;
		}

		function toggle(el)
		{
			s9e.TextFormatter[(el.checked) ? 'enablePlugin' : 'disablePlugin'](el.id);
			text = '';
		}

		window.setInterval(function()
		{
			if (textarea.value === text)
			{
				return;
			}

			text = textarea.value;
			refreshOutput();

			if (logcheck.checked)
			{
				refreshLog();
			}
		}, 20);
	</script>
</body>
</html><?php

file_put_contents(__DIR__ . '/../../s9e.github.com/TextFormatter/demo.html', ob_get_clean());

echo "Done.\n";