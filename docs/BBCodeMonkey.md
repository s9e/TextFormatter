BBCodeMonkey
============

s9e\TextFormatter\Plugins\BBCodes\BBCodeMonkey is an helper class designed to allow end users to easily express a BBCode definition, both its usage and its template. Its syntax is based and expands on phpBB's own custom BBCode interface and it aims to be compatible with the many custom BBCodes available online while being able to express most BBCode constructs.

BBCode usage
------------
First, we need to express the typical BBCode usage. It takes the form of a mock BBCode, such as

    [b]{TEXT}[/b]

Here, we see that the BBCode is named B and it contains some text. The pair of brackets with stuff in between is called a *token*. It takes the form of a name (a type) in uppercase, optionally followed by a number, e.g. {TEXT1} to keep them unique. Tokens are used as placeholders for actual data, e.g. {URL}, {NUMBER}, etc...

BBCodes can have (named) attributes. The following example describes an attribute named "href" of type "url".

    [a href={URL}]{TEXT}[/a]

The first attribute becomes the BBCode's `defaultAttribute`, and if its name is the same as the BBCode, it can be omitted altogether, e.G.

    [url={URL}]{TEXT}[/url]

Here, we have a BBCode named "URL" with an attribute named "url" which is its `defaultAttribute`.

By default, all attributes are `required`. To mark an attribute as optional:

    [b title={TEXT1;optional}]{TEXT2}[/b]

Other attribute options are supported, see s9e\TextFormatter\ConfigBuilder\Items\Attribute:

    [font face={SIMPLETEXT;defaultValue=Arial}]

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