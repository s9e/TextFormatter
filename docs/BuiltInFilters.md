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

<dt>#ip</dt>
<dd>A valid IPv4 or IPv6 address. Uses ext/filter's FILTER_VALIDATE_IP filter.</dd>

<dt>#ipport</dt>
<dd>A valid IPv4 or IPv6 address with a port such as <code>127.0.0.1:80</code> or <code>[ff02::1]:80</code>. Uses ext/filter's FILTER_VALIDATE_IP filter for the IP part.</dd>

<dt>#ipv4</dt>
<dd>A valid IPv4 address. Uses ext/filter's FILTER_VALIDATE_IP filter with the FILTER_FLAG_IPV4 flag.</dd>

<dt>#ipv6</dt>
<dd>A valid IPv6 address. Uses ext/filter's FILTER_VALIDATE_IP filter with the FILTER_FLAG_IPV6 flag.</dd>

<dt>#map</dt>
<dd>Requires a 'map' option that is an associative array with regexps as keys. The map is processed in order, and the first regexp that matches replaces the value with its corresponding element. If no match is found, the original value is returned.</dd>

<dt>#int</dt>
<dd>An integer value. Uses ext/filter's FILTER_VALIDATE_INT filter. Returns an integer, not a string.</dd>

<dt>#number</dt>
<dd>A string made of digits. Matches <code>/^[0-9]+$/</code>. Note that unlike #int, "0123" is a valid number. Returns a string.</dd>

<dt>#range</dt>
<dd>An integer value, adjusted for given range. Requires a 'min' option and a 'max' option. Uses ext/filter's FILTER_VALIDATE_INT filter. Values outside of range are adjusted to closest valid value. Returns an integer.</dd>

<dt>#regexp</dt>
<dd>A string that matches given regexp. Requires a 'regexp' option.</dd>

<dt>#simpletext</dt>
<dd>A string that matches <code>/^[- +,.0-9A-Za-z_]+$/</code>.</dd>

<dt>#uint</dt>
<dd>An unsigned integer value. Same as #int, but rejects value less than 0.</dd>

<dt>#url</dt>
<dd>A valid URL. Uses ext/filter's FILTER_VALIDATE_URL. Trims surrounding whitespace to facilitate compliance with <a href="http://www.w3.org/html/wg/drafts/html/master/infrastructure.html#valid-url-potentially-surrounded-by-spaces">HTML specs</a>. Uses the settings from UrlConfig. Allows scheme-less URLs by default. Punycodes IDNs if <code>idn_to_ascii()</code> is available. URL-encodes non-ASCII characters. Also URL-encodes the four following characters <code>"'()</code> to make the URL safe to use in CSS. Validates scheme against allowed schemes. Validates against disallowedHosts. Optionally resolves redirectors. Does not support local URLs.</dd>

</dl>