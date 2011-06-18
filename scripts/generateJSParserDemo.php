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
$cb->BBCodes->addPredefinedBBCode('YOUTUBE');
$cb->BBCodes->addPredefinedBBCode('FLOAT');

$cb->BBCodes->addBBCode('CODE', array(
	'template' => '<code><xsl:apply-templates/></code>',
	'defaultDescendantRule' => 'deny'
));

// Force YouTube vids to autoplay
$cb->setTagAttributeOption('YOUTUBE', 'content', 'replaceWith', '$1&amp;autoplay=1');

$cb->Emoticons->addEmoticon(':)', '<img alt=":)" src="https://github.com/images/icons/public.png"/>');

$cb->Censor->addWord('apple', 'banana');

$cb->loadPlugin('Autolink');
$cb->loadPlugin('HTMLEntities')->disableEntity('&lt;');
$cb->loadPlugin('WittyPants');

$cb->addRulesFromHTML5Specs();

$jsParser = $cb->getJSParser(array(
	'compilation'     => (empty($_SERVER['argv'][1])) ? 'none' : 'ADVANCED_OPTIMIZATIONS',
	'disableLogTypes' => (empty($_SERVER['argv'][2])) ? array() : array('debug', 'warning', 'error'),
	'removeDeadCode'  => (isset($_SERVER['argv'][3])) ? (bool) $_SERVER['argv'][3] : true
));

$closureCompilerNote = (empty($_SERVER['argv'][1])) ? '' : ' It has been minified to ' . round(strlen($jsParser) / 1024, 1) . 'KB (' . round(strlen(gzencode($jsParser, 9)) / 1024, 1) . 'KB gzipped) with [url=http://closure-compiler.appspot.com/home]Google Closure Compiler[/url].';

file_put_contents('/tmp/foo.js', $jsParser);

ob_start();
?><!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
	<title>s9e\Toolkit\TextFormatter &bull; Demo</title>
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
			white-space: pre-wrap;
			padding: 5px;
			background-color: #eee;
			border: dashed 1px #8af;
			border-radius: 5px;
		}

		label
		{
			cursor: pointer;
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
	<div style="float:left;width:80%;max-width:800px">
		<form>
			<textarea style="width:99%" rows="15">[float=right][youtube width=240 height=180]http://www.youtube.com/watch?v=QH2-TGUlwu4[/youtube][/float]

This is a demo of the Javascript port of [url=https://github.com/s9e/Toolkit/tree/master/src/TextFormatter title="s9e\Toolkit\TextFormatter at GitHub.com"]s9e\Toolkit\TextFormatter[/url].

The following plugins have been enabled:

[list]
  [*][b]Autolink[/b] --- loose URLs such as http://github.com are automatically turned into links

  [*][b]BBCodes[/b]
  [list=square]
    [*][b]bold[/b], [i]italic[/i], [u]underline[/u], [s]strikethrough[/s],
    [*][color=#f05]co[/color][color=#2f2]lo[/color][color=#02f]r,[/color]
    [*][CODE][URL][/CODE], [CODE:123][CODE][/CODE:123], [CODE][YOUTUBE][/CODE], [CODE][FLOAT][/CODE], and [CODE][LIST][/CODE]
  [/list][/*]

  [*][b]Censor[/b] --- the word "apple" is censored and automatically replaced with "banana"
  [*][b]Emoticons[/b] --- one emoticon :) has been added
  [*][b]HTMLEntities[/b] --- HTML entities such as &amp;hearts; are decoded
  [*][b]WittyPants[/b] --- some typography is enhanced, e.g. (c) (tm) and "quotes"
[/list]

Additionally, in order to demonstrate some other features:

[list]
  [*]ConfigBuilder::disallowHost() --- links to [url=http://example.com]example.com[/url] are disabled
  [*]HTMLEntitiesConfig::disableEntity() --- the HTML entity &amp;lt; is arbitrarily disabled
  [*]a YouTube video, at the right, keeps playing as you're editing the text [i](including its own tag!)[/i] to demonstrate the partial-update algorithm used to refresh the preview
[/list]

You can take a look at the log, hover the messages with the mouse and click them to get to the part of the text that generated them.

The parser/renderer used on this page page has been generated via [url=https://github.com/s9e/Toolkit/blob/master/scripts/generateJSParserDemo.php]this script[/url].<?php echo $closureCompilerNote; ?> The raw sources can be found [url=https://github.com/s9e/Toolkit/blob/master/src/TextFormatter/TextFormatter.js]at GitHub[/url].
</textarea>
			<br>
			<input type="checkbox" id="rendercheck" checked="checked"><label for="rendercheck"> Render</label>
			<input type="checkbox" id="logcheck"><label for="logcheck"> Show log</label>
		</form>
	</div>

	<div style="float:left;">
		<form>
			<input type="checkbox" id="Autolink" checked="checked" onchange="toggle(this)"><label for="Autolink">&nbsp;Autolink</label><br>
			<input type="checkbox" id="BBCodes" checked="checked" onchange="toggle(this)"><label for="BBCodes">&nbsp;BBCodes</label><br>
			<input type="checkbox" id="Censor" checked="checked" onchange="toggle(this)"><label for="Censor">&nbsp;Censor</label><br>
			<input type="checkbox" id="Emoticons" checked="checked" onchange="toggle(this)"><label for="Emoticons">&nbsp;Emoticons</label><br>
			<input type="checkbox" id="HTMLEntities" checked="checked" onchange="toggle(this)"><label for="HTMLEntities">&nbsp;HTMLEntities</label><br>
			<input type="checkbox" id="WittyPants" checked="checked" onchange="toggle(this)"><label for="WittyPants">&nbsp;WittyPants</label>
		</form>
	</div>

	<div style="clear:both"></div>

	<div id="logdiv" style="display:none"></div>

	<div id="preview"></div>

	<script type="text/javascript"><?php echo $jsParser; ?>

		var text,
			xml,

			textarea = document.getElementsByTagName('textarea')[0],
			preview = document.getElementById('preview'),

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
				var newPreview = document.createElement('pre');

				newPreview.appendChild(
					s9e.TextFormatter.render(xml)
				);

				refreshElement(preview, newPreview);
			}
			else
			{
				preview.textContent = s.serializeToString(xml)
			}
		}

		function processNodes(oldNode, newNode)
		{
			if (oldNode.localName !== newNode.localName
			 || oldNode.nodeType  !== newNode.nodeType)
			{
				return 0;
			}

			if (oldNode.isEqualNode(newNode))
			{
				return 1;
			}

			if (oldNode.nodeType === Node.TEXT_NODE)
			{
				oldNode.textContent = newNode.textContent;
				return 1;
			}

			syncAttributes(oldNode, newNode);
			refreshElement(oldNode, newNode);

			return 1;
		}

		function refreshElement(oldEl, newEl)
		{
			var oldCnt = oldEl.childNodes.length,
				newCnt = newEl.childNodes.length,
				i = 0;

			while (i < oldCnt && i < newCnt)
			{
				var oldNode = oldEl.childNodes[i],
					newNode = newEl.childNodes[i];

				if (!processNodes(oldNode, newNode))
				{
					break;
				}

				++i;
			}

			var left = i,
				right = 0,
				maxRight = Math.min(oldCnt - left, newCnt - left);

			while (right < maxRight)
			{
				var oldNode = oldEl.childNodes[oldCnt - (right + 1)],
					newNode = newEl.childNodes[newCnt - (right + 1)];

				if (processNodes(oldNode, newNode))
				{
					++right;
				}
				else
				{
					break;
				}
			}

			/**
			* Clone the new nodes from newEl
			*/
			var frag = document.createDocumentFragment();
			i = left;
			while (i < (newCnt - right))
			{
				frag.appendChild(newEl.childNodes[i].cloneNode(true));
				++i;
			}

			/**
			* Remove the dirty nodes from oldEl
			*/
			i = oldCnt - right;
			while (--i >= left)
			{
				oldEl.removeChild(oldEl.childNodes[i]);
			}

			if (left === oldCnt)
			{
				oldEl.appendChild(frag);
			}
			else
			{
				oldEl.insertBefore(frag, oldEl.childNodes[left]);
			}
		}

		function syncAttributes(oldEl, newEl)
		{
			var oldCnt = oldEl.attributes.length,
				newCnt = newEl.attributes.length,
				i = oldCnt;

			while (--i >= 0)
			{
				var oldAttr = oldEl.attributes[i];

				if (!newEl.hasAttributeNS(oldAttr.namespaceURI, oldAttr.name))
				{
					oldEl.removeAttributeNS(oldAttr.namespaceURI, oldAttr.name);
				}
			}

			i = newCnt;
			while (--i >= 0)
			{
				var newAttr = newEl.attributes[i];

				if (newAttr.value !== oldEl.getAttributeNS(newAttr.namespaceURI, newAttr.name))
				{
					oldEl.setAttributeNS(newAttr.namespaceURI, newAttr.name, newAttr.value);
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

			logdiv.innerHTML = (msgs.length) ? msgs.join("\n") : 'No log';
		}

		function select(pos, len)
		{
			autoHighlight = false;
			textarea.focus();
			textarea.setSelectionRange(pos, pos + len);
		}

		function highlight(pos, len)
		{
			if (autoHighlight)
			{
				textarea.setSelectionRange(pos, pos + len);
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
		}, 20);
	</script>
</body>
</html><?php

file_put_contents(__DIR__ . '/../docs/JSParserDemo.html', ob_get_clean());

echo "Done.\n";