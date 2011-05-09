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

$jsParser = $cb->getJSParser(array(
//	'compilation'     => 'ADVANCED_OPTIMIZATIONS',
//	'disableLogTypes' => array('debug', 'warning', 'error'),
	'compilation'     => 'none',
	'disableLogTypes' => array(),
	'removeDeadCode'  => true
));

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
	</style>
</head>
<body>
	<div>
		<form>
			<textarea cols="80" rows="15">This is a demo of the Javascript port of [url=https://github.com/s9e/Toolkit/tree/master/src/TextFormatter]s9e\TextFormatter[/url].

A few BBCodes have been added such as:

[list]
	[*][b]bold[/b],
	[*][i]italic[/i],
	[*][color=#f05]color[/color],
	[*]+ a few others
[/list]

The code required has been minified to a few kilobytes with [url=http://closure-compiler.appspot.com/home]Google Closure Compiler[/url] but the raw sources can be found at GitHub in the [url=https://github.com/s9e/Toolkit/tree/TextFormatter-JSParser/src/TextFormatter]TextFormatter-JSParser branch[/url].
</textarea>
			<br>
			<input type="checkbox" id="rendercheck" checked="checked"><label for="rendercheck"> Render</label>
			<input type="checkbox" id="logcheck"><label for="logcheck"> Show log</label>
		</form>
	</div>

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

			s = new XMLSerializer();

		rendercheck.onchange = refreshOutput;

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
			console.dir(rendercheck);
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
					msgs.push(
						'[' + type + '] ' + entry.msg.replace(
							/%([0-9]\$)?[sd]/g,
							function(m)
							{
								return entry.params[m[1] ? m[1] - 1 : 0];
							}
						)
					);
				});
			}

			logdiv.innerHTML = (msgs.length) ? msgs.join("\n") : 'No log';
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