<h2>Federated sites</h2>

### Mastodon

The default Mastodon media site can be customized with additional hosts. This can be done using the `MastodonHelper` class. In the example below, we declare that the host `infosec.exchange` should be mapped to the `mastodon` site.

```php
$configurator = new s9e\TextFormatter\Configurator;

// Add the Mastodon media site
$configurator->MediaEmbed->add('mastodon');

// Use MastodonHelper to add 'infosec.exchange' as a supported instance
$mastodonHelper = new s9e\TextFormatter\Plugins\MediaEmbed\Configurator\MastodonHelper($configurator);
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
