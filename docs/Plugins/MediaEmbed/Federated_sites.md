<h2>Federated sites</h2>


### Bluesky

The default Bluesky media site can be customized with different hosts. This can be done using the `BlueskyHelper` class. In the example below, we configure the Bluesky media site to support both the main `bsky.app` domain, as well as our own hypothetical instance hosted on `bluesky.local`.

```php
$configurator = new s9e\TextFormatter\Configurator;

// Use the Bluesky helper to set 'bsky.app' and 'bluesky.local' as supported instances
$mastodonHelper = $configurator->MediaEmbed->getSiteHelper('bluesky');
$mastodonHelper->setHosts(['bsky.app', 'bluesky.local']);

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 'https://bsky.app/profile/bsky.app/post/3kkrqzuydho2v';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<iframe data-s9e-mediaembed="bluesky" allowfullscreen="" loading="lazy" onload="let c=new MessageChannel;c.port1.onmessage=e=&gt;this.style.height=e.data+'px';this.contentWindow.postMessage('s9e:init','*',[c.port2])" scrolling="no" src="https://s9e.github.io/iframe/2/bluesky.min.html#at://did:plc:z72i7hdynmk6r22z27h6tvur/app.bsky.feed.post/3kkrqzuydho2v#embed.bsky.app" style="border:0;height:600px;max-width:600px;width:100%"></iframe>
```


### Mastodon

The default Mastodon media site can be customized with additional hosts. This can be done using the `MastodonHelper` class. In the example below, we add support for toots published by the `infosec.exchange` instance.

```php
$configurator = new s9e\TextFormatter\Configurator;

// Use the Mastodon helper to add 'infosec.exchange' as a supported instance
$mastodonHelper = $configurator->MediaEmbed->getSiteHelper('mastodon');
$mastodonHelper->addHost('infosec.exchange');

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 'https://infosec.exchange/@SwiftOnSecurity/109579438603578302';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<iframe data-s9e-mediaembed="mastodon" allowfullscreen="" loading="lazy" onload="let c=new MessageChannel;c.port1.onmessage=e=&gt;this.style.height=e.data+'px';this.contentWindow.postMessage('s9e:init','*',[c.port2])" scrolling="no" style="border:0;height:300px;max-width:550px;width:100%" src="https://s9e.github.io/iframe/2/mastodon.min.html#SwiftOnSecurity@infosec.exchange/109579438603578302"></iframe>
```


### XenForo 2.3+

While not technically a federated platform, XenForo 2.3+ allows embedding content from one forum into another. In the following example, we use the `XenForoHelper` class to allow embedding content from `xenforo.com`.


```php
$configurator = new s9e\TextFormatter\Configurator;

// Use the XenForo helper to add 'xenforo.com' as an authorized source
$xenforoHelper = $configurator->MediaEmbed->getSiteHelper('xenforo');
$xenforoHelper->addHost('xenforo.com');

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 'https://xenforo.com/community/threads/embed-your-content-anywhere.217381/';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<iframe data-s9e-mediaembed="xenforo" allowfullscreen="" loading="lazy" onload="let c=new MessageChannel;c.port1.onmessage=e=&gt;this.style.height=e.data+'px';this.contentWindow.postMessage('s9e:init','*',[c.port2])" scrolling="no" style="border:0;height:300px;width:100%" src="https://s9e.github.io/iframe/2/xenforo.min.html#https://xenforo.com/community/threads/217381"></iframe>
```