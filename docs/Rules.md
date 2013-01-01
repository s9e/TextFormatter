Rules
=====

See `s9e\TextFormatter\Configurator\Collections\Ruleset`.
Rules are set on a per-tag basis, for example:

```
$configurator = new Configurator;

$tag = $configurator->tags->add('B');
$tag->rules->autoReopen();
$tag->rules->defaultChildRule('allow');
$tag->rules->denyChild('X');
```

<dl>

<dt>allowChild</dt>
<dd><i>Example:</i> <code>$tag->rules->allowChild('X');</code><br/>
Allows tag X to be used as a child of given tag.</dd>

</dl>