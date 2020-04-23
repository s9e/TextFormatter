This plugin implements task lists, a form of markup compatible with GitHub/GitLab Flavored Markdown and other dialects.

This plugin requires a `LI` tag to function properly. If there is no `LI` tag defined when the plugin is initialized, the Litedown plugin is automatically loaded.

Each task is assigned a pseudo-random alphanumeric ID generated at parsing time.


### Syntax

A task is represented by a `[x]` or `[ ]` marker immediately following a list item.

```md
Markdown style:

- [x] Checked
- [ ] Unchecked

BBCode style:

[list]
[*][x] Checked
[*][ ] Unchecked
[/list]
```


### References

 - <https://docs.gitlab.com/ee/user/markdown.html#task-lists>
 - <https://github.com/mity/md4c/wiki/Markdown-Syntax:-Task-Lists>
 - <https://github.github.com/gfm/#task-list-items-extension->


## Examples

Note that in all of the reference outputs, random IDs have been replaced with a `...` placeholder for convenience.

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->Litedown;
$configurator->TaskLists;

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = "- [x] checked\n"
      . "- [X] Checked\n"
      . "- [ ] unchecked"; 
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<ul><li data-task-id="..." data-task-state="complete"><input data-task-id="..." type="checkbox" checked disabled> checked</li>
<li data-task-id="..." data-task-state="complete"><input data-task-id="..." type="checkbox" checked disabled> Checked</li>
<li data-task-id="..." data-task-state="incomplete"><input data-task-id="..." type="checkbox" disabled> unchecked</li></ul>
```


### Allow tasks to be toggled

Setting the `TASKLISTS_EDITABLE` parameter to a non-empty value will make tasks editable.

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->Litedown;
$configurator->TaskLists;

extract($configurator->finalize());

$text = "- [x] checked\n"
      . "- [ ] unchecked";
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html, "\n\n";

// Render it again but make the tasks editable
$renderer->setParameter('TASKLISTS_EDITABLE', '1');

echo $renderer->render($xml);
```
```html
<ul><li data-task-id="..." data-task-state="complete"><input data-task-id="..." type="checkbox" checked disabled> checked</li>
<li data-task-id="..." data-task-state="incomplete"><input data-task-id="..." type="checkbox" disabled> unchecked</li></ul>

<ul><li data-task-id="..." data-task-state="complete"><input data-task-id="..." type="checkbox" checked> checked</li>
<li data-task-id="..." data-task-state="incomplete"><input data-task-id="..." type="checkbox"> unchecked</li></ul>
```


### Using the API

The [Helper](https://s9e.github.io/TextFormatter/api/s9e/TextFormatter/Plugins/TaskLists/Helper.html) class provides an API to extract stats from, and change a task's state in the XML representation of a parsed text.

```php
use s9e\TextFormatter\Plugins\TaskLists\Helper;
use s9e\TextFormatter\Unparser;

$configurator = new s9e\TextFormatter\Configurator;
$configurator->Litedown;
$configurator->TaskLists;

extract($configurator->finalize());

$text = "- [ ] First\n"
      . "- [ ] Second";
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

// Capture the first task's ID from the HTML
preg_match('(data-task-id="(\\w+))', $html, $match);
$id = $match[1];

// Show the original text and stats
echo "Before:\n", Unparser::unparse($xml), "\n\n", json_encode(Helper::getStats($xml)), "\n\n";

// Change the state of a given task with markTaskComplete() or markTaskIncomplete()
$xml = Helper::markTaskComplete($xml, $id);

// Show the updated text and stats
echo "After:\n", Unparser::unparse($xml), "\n\n", json_encode(Helper::getStats($xml));
```
```
Before:
- [ ] First
- [ ] Second

{"complete":0,"incomplete":2}

After:
- [x] First
- [ ] Second

{"complete":1,"incomplete":1}
```


### Styling task lists

Task lists can be styled via the `data-task-*` attributes. A common pattern is to hide the list item in unordered lists and cross-out completed tasks.

```css
ul > li[data-task-id]
{
	list-style-type: none;
}
li[data-task-state="complete"]
{
	text-decoration: line-through rgba(0, 0, 0, .6);
}
```
