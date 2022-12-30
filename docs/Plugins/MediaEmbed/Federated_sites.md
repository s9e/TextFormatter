<h2>Federated sites</h2>

### Mastodon

The default Mastodon media site can be customized with additional hosts. This can be done by modifying the original configuration in `$configurator->MediaEmbed->defaultSites['mastodon']`, or by assigning `mastodon` to the desired hostname in `$configurator->registeredVars['MediaEmbed.hosts']`. In the example below, we declare that the host `infosec.exchange` should be mapped to the `mastodon` site.

```php
$configurator = new s9e\TextFormatter\Configurator;

// Add Mastodon, then create an entry that maps the hostname 'infosec.exchange'
// to the 'mastodon' site
$configurator->MediaEmbed->add('mastodon');
$configurator->registeredVars['MediaEmbed.hosts']['infosec.exchange'] = 'mastodon';

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
