<h2>Custom BBCode syntax</h2>

`s9e\TextFormatter\Plugins\BBCodes\Configurator\BBCodeMonkey` is an helper class designed to allow end users to easily express a BBCode definition, both its usage and its template. Its syntax is based and expands on phpBB's own custom BBCode interface and it aims to be compatible with the many custom BBCodes available online while being able to express most BBCode constructs.

### BBCode usage

First, we need to express the typical BBCode usage. It takes the form of a mock BBCode, such as

    [b]{TEXT}[/b]

Here, we see that the BBCode is named B and it contains some text. The pair of brackets with text in between is called a *token*. Tokens are used as placeholders for actual data, e.g. {URL}, {NUMBER}, etc... They takes the form of a name in uppercase, which is either:
 * the name of an attribute filter optionally followed by a number, e.g. {TEXT1}
 * the name of a [template parameter](../../Templating/Template_parameters.md), e.g. {L_WROTE} or {USERNAME}

The closing tag is optional if the BBCode isn't supposed to have one, for example:

    [hr]

### Attributes

BBCodes can have any number of (named) attributes. The following example describes a BBCode with two attributes: one named "href" of type "url" and the other named "title" of type "simpletext.

    [a href={URL} title={SIMPLETEXT}]{TEXT}[/a]

The first attribute becomes the BBCode's `defaultAttribute`, and if its name is the same as the BBCode, it can be omitted altogether, e.g.

    [url={URL}]{TEXT}[/url]

Here, we have a BBCode named "URL" with an attribute named "url" which is its `defaultAttribute`.

By default, all attributes are `required`. To mark an attribute as optional:

    [b title={TEXT1;optional}]{TEXT2}[/b]

Here we have an optional attribute "title" or type "text". Internally, attribute types are added to the attribute's `filterChain` as a built-in filter, except for the type "text" which has no filter associated. For example, {URL} will add the filter "#url" to its attribute's `filterChain`. Some filters accept some parameters:

    [size={RANGE=7,24}]{TEXT}[/size]
    [suit={CHOICE=spades,hearts,diamonds,clubs}]
    [film={REGEXP=/^(?:Star Wars [123456]|Rambo [1-4])$/i}]

*Note*: internally, the "CHOICE" type is transformed into the corresponding regexp. Also, it's case-insensitive by default and can be made case-sensitive using the "caseSensitive" option:

    [suit={CHOICE=Spades,Hearts,Diamonds,Clubs;caseSensitive}]

In addition to the token's type, any number of filters can be added to the `filterChain`, either before ("preFilter") or after ("postFilter"), e.g.

    [time={NUMBER;preFilter=strtotime}]
    [title={TEXT;postFilter=strtolower,ucwords}]

Only the filters listed in BBCodeMonkey::$allowedFilters can be used, for obvious security reasons. `[foo={TEXT;preFilter=eval}]` will throw an exception.

Other attribute options are supported, see `s9e\TextFormatter\Configurator\Items\Attribute`:

    [font face={SIMPLETEXT;defaultValue=Arial}]

In addition to normal attribute options, another option "useContent" can be used. An attribute declared with the `useContent` option will use the BBCode's content as its value if it's not explicitly given one. For instance, consider this BBCode:

    [url={URL;useContent}]{TEXT}[/url]

This BBCode can be used as `[url]http://localhost[/url]` and will be interpreted as `[url=http://localhost]http://localhost[/url]`. And of course, it doesn't prevent it from being used as `[url=http://localhost]My website![/url]`.

### Attribute preprocessors

Attribute preprocessors are a mechanism to parse the content of attributes before validation to extract the values of other attributes. They take the form of a {PARSE} token containing a regexp, or several regexps separated with commas. Any [named subpattern](http://docs.php.net/manual/en/regexp.reference.subpatterns.php) will create an attribute of the same name. For example, let's consider a BBCode that displays a user's first and last name:

    [name={PARSE=/(?<first>\w+) (?<last>\w+)/}]

Functionally, this is the same as:

    [name={PARSE=/(?<first>\w+) (?<last>\w+)/} first={REGEXP=/^\w+$/} last={REGEXP=/^\w+$/}]

Practically, what will happen during parsing is that

    [name="John Smith"]

...will be interpreted as:

    [name first="John" last="Smith"]

Attribute preprocessors use the same syntax as attributes, but they don't necessarily create an attribute of the same name. In the example above, the value for "name" was not kept because no "name" attribute was defined. If you want to use "name" both as an attribute and as an attribute preprocessor, you need to define both as follows:

    [name={TEXT} name={PARSE=/(?<first>\w+) (?<last>\w+)/}]

Now the same example would be interpreted as:

    [name name="John Smith" first="John" last="Smith"]

Values extracted by attribute preprocessors do not overwrite explicit values, and values are only extracted if the attribute preprocessor's regexp matches the attribute's value. The following shows how the above BBCode would be interpreted during parsing: (first line is how it's used, followed by how it's interpreted)

    [name="John Smith"]
    [name first="John" last="Smith"]

    [name="John"]
    [name]

    [name="John Smith" first="Johnny"]
    [name first="Johnny" last="Smith"]

Any number of attribute preprocessors can be defined, either by using multiple {PARSE} tokens or by assigning multiple regexps (separated with commas) to the same {PARSE} token (both methods produce the same result.) Attribute preprocessors are applied in the same order they are defined, but currently the behaviour of multiple preprocessors trying to set the same attributes is undefined until an actual, practical case where it matters is found. Here's how we can define an improved BBCode that allows the user's name to be given as "John Smith" or as "Smith, John". First using multiple {PARSE} tokens:

    [name={PARSE=/(?<first>\w+) (?<last>\w+)/} name={PARSE=/(?<last>\w+), (?<first>\w+)/}]

...or using one {PARSE} token with two regexps separated with a comma:

    [name={PARSE=/(?<first>\w+) (?<last>\w+)/,/(?<last>\w+), (?<first>\w+)/}]

Here's how user input will be interpreted: (user input on top, how it's interpreted below)

    [name="John Smith"]
    [name first="John" last="Smith"]

    [name="Smith, John"]
    [name last="Smith" first="John"]

While an attribute preprocessor won't overwrite other attribute values, it can overwrite its own value. It can be used to clean up an attribute value before processing. Consider the following BBCode:

    [id={PARSE=/^#?(?'id'\d+)$/} id={NUMBER}]

Here's how user input will be interpreted: (user input on top, how it's interpreted below)

    [id=123]
    [id id=123]

    [id=#123]
    [id id=123]

### Composite attributes

Composite attributes are simply an alternative way to declare attribute preprocessors and offer better compatibility with phpBB's custom BBCodes. Whenever an attribute is defined by more than one single all-encompassing token, it's a composite attribute and is converted into an attribute preprocessor. For example:

    [flash={NUMBER1},{NUMBER2}]

This will be interpreted as:

    [flash={PARSE=/^(?<flash0>\d+),(?<flash1>\d+)$/}]

An attribute name is automatically created for {NUMBER1} and {NUMBER2} unless they are explicitly defined. For example:

    [flash={NUMBER1},{NUMBER2} width={NUMBER1} height={NUMBER2}]

...is functionally the same as: *(note the matching names in the subpatterns)*

    [flash={PARSE=/^(?<width>\d+),(?<height>\d+)$/} width={NUMBER1} height={NUMBER2}]

### BBCode options

BBCode options can be specified in the opening tag like an attribute, using their name preceded with a `$`. Boolean values can be expressed as `true` and `false` (in lowercase.) For example:

    [B $forceLookahead=true]{TEXT}[/B]
    [* $tagName=LI]{TEXT}[/*]

### Tag rules

[Tag rules](../../Rules/Tag_rules.md) can be specified in the opening tag like an attribute, using their name preceded with a `#`. Boolean values can be expressed as `true` and `false` (in lowercase.) Multiple values can be separated with a comma. For example:

    [B #autoReopen=true]{TEXT}[/B]
    [U #denyChild=B,I]{TEXT}[/U]

### Templates

Templates can consist of either a chunk of XSL (whatever is acceptable within an `<xsl:template/>` tag) or a chunk of HTML (which will be converted to XHTML, then XSL.) Tokens (such as <code>{URL}</code> or <code>{TEXT}</code>) and attribute names (such as <code>{@url}</code> or <code>{@author}</code>) can be used in text nodes or in attribute values. For example, consider the following BBCode usage:

    [link destination={URL;useContent}]{TEXT}[/link]

Its template could look like this:

    <a href="{URL}">{TEXT}</a>

Internally, the {URL} token will be replaced with the XPath expression `{@destination}` which represents the value of the attribute "destination":

    <a href="{@destination}"><xsl:apply-templates/></a>

Here, the {TEXT} token is replaced with the XSL element `<xsl:apply-templates/>` which will render the content of this BBCode, including the descendants' markup. This only applies to tokens that represent unfiltered content (by default, {TEXT} and {ANYTHING}) and only if the token is the sole content of a BBCode. Otherwise, any filtered attribute will be output as-is, with no markup. For example, the following BBCode:

    [foo]{SIMPLETEXT}[/foo]

...will be interpreted as:

    [foo content={SIMPLETEXT;useContent}]

Because {SIMPLETEXT} is a filtered type, it is assigned an attribute, arbitrarily named "content". And because this BBCode filters its content, if its template is:

    <div>{SIMPLETEXT}</div>

...it will be rendered as:

    <div><xsl:value-of select="{@content}"/></div>

### Token usage in templates

Note that only unique tokens can be used in templates. For instance, consider the following, valid BBCode usage:

    [box color={COLOR} width={NUMBER} height={NUMBER}]{TEXT}[/box]

It is valid to use the following template: *(note how tokens and XPath expressions are interchangeable)*

    <div style="color: {COLOR}; width: {@width}px; height: {@height}px">{TEXT}</div>

However, the following is **invalid** because the token {NUMBER} is ambiguous:

    <div style="width: {NUMBER}px; height: {NUMBER}px">{TEXT}</div>

This can be fixed by assigning different IDs to the tokens:

    [box color={COLOR} width={NUMBER1} height={NUMBER2}]{TEXT}[/box]

    <div style="color: {COLOR}; width: {NUMBER1}px; height: {NUMBER2}px">{TEXT}</div>
