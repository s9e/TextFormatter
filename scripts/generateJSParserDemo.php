#!/usr/bin/php
<?php

include __DIR__ . '/../src/TextFormatter/ConfigBuilder.php';

$cb = new \s9e\Toolkit\TextFormatter\ConfigBuilder;

$cb->disallowHost('*.example.com');

$cb->BBCodes->addPredefinedBBCode('B');
$cb->BBCodes->addPredefinedBBCode('I');
$cb->BBCodes->addPredefinedBBCode('U');
$cb->BBCodes->addPredefinedBBCode('S');
$cb->BBCodes->addPredefinedBBCode('URL');
$cb->BBCodes->addPredefinedBBCode('LIST');
$cb->BBCodes->addPredefinedBBCode('COLOR');
$cb->BBCodes->addPredefinedBBCode('NOPARSE');
//$cb->BBCodes->addPredefinedBBCode('FLASH');

$cb->Emoticons->addEmoticon(':)', '<img alt=":)" src="https://github.com/images/icons/public.png"/>');

$cb->Censor->addWord('apple', 'banana');

$jsParser = $cb->getJSParser(array(
//	'compilation'     => 'ADVANCED_OPTIMIZATIONS',
//	'disableLogTypes' => array('debug', 'warning', 'error'),
	'compilation'     => 'none',
	'disableLogTypes' => array(),
	'removeDeadCode'  => true
));

file_put_contents('/tmp/foo.js', $jsParser);

ob_start();
?><!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
	<title></title>
	<style type="text/css">
		div
		{
			margin-bottom: 10px;
		}

		#logdiv
		{
			border: dashed 1px #8af;
		}

		#logdiv,
		#preview pre
		{
			font-family: sans;
			white-space: pre-wrap;
		}

		label
		{
			cursor: pointer;
		}
	</style>
</head>
<body>
	<div style="float:left">
		<form>
			<textarea cols="80" rows="15">This is a demo of the Javascript port of [url=https://github.com/s9e/Toolkit/tree/master/src/TextFormatter]s9e\TextFormatter[/url].

A few BBCodes have been added such as:

[list]
	[*][b]bold[/b], [i]italic[/i], [u]underline[/u], [s]strikethrough[/s],
	[*][color=#f05]co[/color][color=#2f2]lo[/color][color=#02f]r,[/color]
	[*][NOPARSE][URL][/NOPARSE], [NOPARSE:123][NOPARSE][/NOPARSE:123], and [NOPARSE][LIST][/NOPARSE]
[/list]

Additionally, one emoticon :) has been added, the word "apple" is censored and automatically replaced with "banana" and links to [url]http://example.com[/url] are disabled.

Take a look at the log, hover the messages with the mouse and click them to get to the part of the text that generated them.

This page has been generated via [url=https://github.com/s9e/Toolkit/blob/master/scripts/generateJSParserDemo.php]a script[/url] and can be minified to a few kilobytes with [url=http://closure-compiler.appspot.com/home]Google Closure Compiler[/url]. The raw sources can be found [url=https://github.com/s9e/Toolkit/blob/master/src/TextFormatter/TextFormatter.js]at GitHub[/url].
</textarea>
			<br>
			<input type="checkbox" id="rendercheck" checked="checked"><label for="rendercheck"> Render</label>
			<input type="checkbox" id="logcheck"><label for="logcheck"> Show log</label>
		</form>
	</div>

	<div style="float:left">
		<form>
			<input type="checkbox" id="BBCodes" checked="checked" onchange="toggle(this)"><label for="BBCodes"> BBCodes</label><br>
			<input type="checkbox" id="Censor" checked="checked" onchange="toggle(this)"><label for="Censor"> Censor</label><br>
			<input type="checkbox" id="Emoticons" checked="checked" onchange="toggle(this)"><label for="Emoticons"> Emoticons</label>
		</form>
	</div>

	<div style="clear:both"></div>

	<div id="logdiv" style="display:none"></div>

	<div id="preview"><pre></pre></div>

	<script type="text/javascript"><?php echo $jsParser ?>

		var text,
			xml,

			textarea = document.getElementsByTagName('textarea')[0],
			pre = document.getElementsByTagName('pre')[0],

			rendercheck = document.getElementById('rendercheck'),

			logcheck = document.getElementById('logcheck'),
			logdiv = document.getElementById('logdiv'),
			autoHighlight = true,

			s = new XMLSerializer();

		rendercheck.onchange = refreshOutput;

		textarea.onmouseout = function()
		{
			autoHighlight = true;
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
			if (rendercheck.checked)
			{
				var newPRE = document.createElement('pre');

				newPRE.appendChild(
					s9e.TextFormatter.render(xml)
				);

				pre.parentNode.replaceChild(newPRE, pre);
				pre = newPRE;
			}
			else
			{
				pre.textContent = s.serializeToString(xml)
			}
		}

		function refreshLog()
		{
			var type,
				log = s9e.TextFormatter.getLog(),
				msgs = [];

			for (type in log)
			{
				log[type].forEach(function(entry)
				{
					var msg = '[' + type + '] ' + entry.msg.replace(
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

						msg = '<a style="cursor:pointer" onmouseover="highlight(' + entry.pos + ',' + (entry.pos + entry.len) + ')" onclick="select(' + entry.pos + ',' + (entry.pos + entry.len) + ')">' + msg + '</a>';
					}

					msgs.push(msg);
				});
			}

			logdiv.innerHTML = (msgs.length) ? msgs.join("\n") : 'No log';
		}

		function select(lpos, rpos)
		{
			autoHighlight = false;
			textarea.focus();
			textarea.setSelectionRange(lpos, rpos);
		}

		function highlight(lpos, rpos)
		{
			if (autoHighlight)
			{
				textarea.setSelectionRange(lpos, rpos);
			}
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
			xml = s9e.TextFormatter.parse(text);

			refreshOutput();

			if (logcheck.checked)
			{
				refreshLog();
			}
		}, 50);
	</script>
</body>
</html><?php

file_put_contents(__DIR__ . '/../docs/JSParserDemo.html', ob_get_clean());

echo "Done.\n";