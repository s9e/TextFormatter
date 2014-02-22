Built-in filters
================

<dl>
<dt>#alnum</dt>
<dd>Alphanumeric value. Matches <code>/^[0-9A-Za-z]+$/</code>.</dd>

<dt>#color</dt>
<dd>Any string that looks like a CSS color. Matches hex values such as <code>#123</code> or <code>#123456</code>, color names such as <code>red</code> (or any string composed of letters from a to z) and CSS rgb() values such as <code>rgb(12, 34, 56)</code>.</dd>

<dt>#email</dt>
<dd>A well-formed email address. Uses ext/filter's FILTER_VALIDATE_EMAIL filter. The JavaScript version is much more lenient.</dd>

<dt>#hashmap</dt>
<dd>Requires a <code>map</code> option that is an associative array. At runtime, the attribute's value is replaced by the map's value of the same name. If no match is found, the original value is returned unless the `strict` option is set, in which case the attribute is invalidated. The map is case-sensitive. Also see <code>#map</code>.</dd>

<dt>#identifier</dt>
<dd>A string of letters, numbers, dashes and underscores. Matches <code>/^[-0-9A-Za-z_]+$/</code>.</dd>

<dt>#ip</dt>
<dd>A valid IPv4 or IPv6 address. Uses ext/filter's FILTER_VALIDATE_IP filter.</dd>

<dt>#ipport</dt>
<dd>A valid IPv4 or IPv6 address with a port such as <code>127.0.0.1:80</code> or <code>[ff02::1]:80</code>. Uses ext/filter's FILTER_VALIDATE_IP filter for the IP part.</dd>

<dt>#ipv4</dt>
<dd>A valid IPv4 address. Uses ext/filter's FILTER_VALIDATE_IP filter with the FILTER_FLAG_IPV4 flag.</dd>

<dt>#ipv6</dt>
<dd>A valid IPv6 address. Uses ext/filter's FILTER_VALIDATE_IP filter with the FILTER_FLAG_IPV6 flag.</dd>

<dt>#int</dt>
<dd>An integer value. Uses ext/filter's FILTER_VALIDATE_INT filter. Returns an integer, not a string.</dd>

<dt>#map</dt>
<dd>Requires a <code>map</code> option that is an associative array with regexps as keys. The map is processed in order, and the first regexp that matches replaces the value with its corresponding element. If no match is found, the original value is returned unless the `strict` option is set, in which case the attribute is invalidated.</dd>

<dt>#number</dt>
<dd>A string made of digits. Matches <code>/^[0-9]+$/</code>. Note that unlike #int, "0123" is a valid number. Returns a string.</dd>

<dt>#range</dt>
<dd>An integer value, adjusted for given range. Requires a <code>min</code> option and a <code>max</code> option. Uses ext/filter's FILTER_VALIDATE_INT filter. Values outside of range are adjusted to closest valid value. Returns an integer.</dd>

<dt>#regexp</dt>
<dd>A string that matches given regexp. Requires a <code>regexp</code> option.</dd>

<dt>#simpletext</dt>
<dd>A string that matches <code>/^[- +,.0-9A-Za-z_]+$/</code>.</dd>

<dt>#uint</dt>
<dd>An unsigned integer value. Same as #int, but rejects values less than 0.</dd>

<dt>#url</dt>
<dd>A valid URL. Uses ext/filter's FILTER_VALIDATE_URL. Trims surrounding whitespace to facilitate compliance with <a href="http://www.w3.org/html/wg/drafts/html/master/infrastructure.html#valid-url-potentially-surrounded-by-spaces">HTML specs</a>. Uses the settings from UrlConfig. Allows scheme-less URLs by default. Punycodes IDNs if <code>idn_to_ascii()</code> is available. URL-encodes non-ASCII characters. Also URL-encodes the four following characters <code>"'()</code> to make the URL safe to use in CSS. Validates scheme against allowed schemes. Validates host against disallowed hosts. Does not support local URLs.</dd>

</dl>