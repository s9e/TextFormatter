Built-in filters
================

<dl>
<dt>#alnum</dt>
<dd>Alphanumeric value. Matches <code>/^[0-9A-Za-z]+$/</code></dd>

<dt>#color</dt>
<dd>Any string that looks like a CSS color. Matches <code>/^(?:#[0-9a-f]{3,6}|[a-z]+)$/i</code></dd>

<dt>#email</dt>
<dd>A well-formed email address. Uses ext/filter's FILTER_VALIDATE_EMAIL filter. The Javascript version is much more lenient.</dd>

<dt>#identifier</dt>
<dd>A string of letters, numbers, dashes and underscores. Matches <code>/^[-0-9A-Za-z_]+$/</code></dd>
