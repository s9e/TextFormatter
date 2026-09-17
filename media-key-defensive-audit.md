# Defensive Audit — s9e Media-Site Keys (9 Providers)

**Scope:** Imgur, Google Sheets, Google Drive, SlideShare, JSFiddle, Internet Archive, CodePen, GitHub Gist, Falstad/CircuitJS.
**Sources:** `s9e/xenforo-ext-media-sites` (new "media key" architecture) + `s9e/TextFormatter` (old `MediaEmbed` plugin, for contrast).
**Threat model:** attacker/user-controlled raw post content (BBCode + pasted URLs) **before** XenForo parsing, media matching, and rendering.

---

## 1. Executive Summary

The single most important fact: **the media key is user-controllable and is interpolated into XenForo templates with NO HTML escaping** (`{$var}` is raw). There are two distinct input paths that produce a key:

| Path | How the key is formed | Constraint on the key |
| --- | --- | --- |
| **Bare URL** (e.g. `https://codepen.io/user/pen/abc123`) | `Parser::match()` runs per-site regexes → `serializeVars()` | Constrained by the site's match regex (e.g. `user=[-\\\\w]+`, `id=\\\\w+`) |
| **`[MEDIA=provider]key[/MEDIA]` BBCode** | The user types the key directly; `convertMediaTag()` wraps it as `[URL media="provider:key"]` | **Any character except `"` and `[`** — the key is NOT run through `Parser::match()` |

The `[MEDIA]` path is the high-value one: it lets the user encode **arbitrary `k=v;k2=v2` pairs** (urldecoded at render time) that are then spliced raw into the template. Because `parseVars()` urldecodes each value, the user can inject a literal `"` via `%22`, a `&` via `%26`, a `<` via `%3C`, etc. — characters that the bare-URL regexes would have stripped.

**Net effect:** from a single `[MEDIA=...]` tag the user can (a) break out of the iframe's `src`/`style`/other double-quoted attributes, (b) inject new attributes (`onmouseover`, `src`, `style`, `data-*`, …), (c) override the renderer's fixed mechanics (appearance, UI, simulation, loading, external URLs), and (d) reach the `replaceIframes` JSON sink, which re-emits those attributes via `setAttribute` on the client.

The "CircuitJS" sink the user asked about is the **Falstad** provider: its `src` is `https://www.falstad.com/circuit/circuitjs.html?running=false&cct=…` (or `ctz=…`), and the `cct`/`ctz` value is user-controlled, so the user can (i) choose which circuit is loaded (simulation behavior) and (ii) inject extra query params (e.g. `&running=true`) to override the renderer's fixed `running=false`.

---

## 2. Architecture & Data Flow

```
Raw post content (attacker-controlled)
  │
  ├─ bare URL ─────────────► Parser::match($url, …)
  │                            │  per-site regexes (static::$sites[siteId][0])
  │                            │  scrape configs  (static::$sites[siteId][2])
  │                            │  filters         (static::$sites[siteId][3])
  │                            │  adjustVars{SiteId}()
  │                            └► serializeVars($vars, $siteId)  ──► KEY
  │
  └─ [MEDIA=prov]key[/MEDIA] ► convertMediaTag()  ──► [URL media="prov:key"]
                                  (key = [^"\[]++ , NOT matched)      KEY
  │
  ▼
Html::renderTagUrl()  →  splits "prov:key"  →  renderTagMedia([key], prov, …)
  │
  ▼
Renderer::render($mediaKey, $site, $siteId)
  │  parseVars($mediaKey):
  │     if key =~ ^(\w+=[^;]*)(?:;(?1))*$  →  explode ';' → k=v (both urldecode)
  │     else                                →  $vars['id'] = $mediaKey
  │  + defaultValues (internetarchive: width=640,height=360)
  │  + adjustVars{SiteId}()
  │  + filters (internetarchive: filterUint width/height)
  ▼
templater->renderTemplate('public:_media_site_embed_{siteId}', $vars)
  │  {$var}  = RAW (no escaping)
  │  {{ expr }} = RAW expression
  ▼
renderTagMedia() wraps in <span data-s9e-mediaembed="…">
  │
  ▼
Helper::replaceIframes()  (post-processing)
  │  regex-parses the iframe's  attr="value"  pairs
  │  re-serializes into  data-s9e-mediaembed-iframe='[JSON]'
  ▼
Client JS:  JSON.parse(attr)  →  iframe.setAttribute(name, value)  for each pair
```

### Key deserialization (`Renderer::parseVars`)
```php
if (preg_match('(^(\w+=[^;]*)(?:;(?1))*$)', $mediaKey)) {
    foreach (explode(';', $mediaKey) as $pair) {
        list($k, $v) = explode('=', $pair);
        $vars[urldecode($k)] = urldecode($v);   // ← urldecode = the injection vector
    }
} else {
    $vars['id'] = $mediaKey;
}
```

### Key serialization (`Parser::serializeVars`)
```php
ksort($vars);
if ($keys === ['id'] && preg_match('(^[-./:\w]+$)D', $vars['id'])) return $vars['id'];
// else: urlencode(k)=urlencode(v);…  with  '..' → '%2E%2E'
```

### The `replaceIframe` JSON sink (`Helper::replaceIframe`)
```php
preg_match_all('(([-\w]++)="([^"]*+))', $original, $m);   // captures attr="value"
$attributes = array_combine($m[1], $m[2]);                 // duplicate attr → LAST wins
…
$values[] = $attrName; $values[] = $attrValue;             // for every non-c2l attr
$html .= " data-s9e-mediaembed-iframe='"
      .  str_replace("'", '&#39;', htmlspecialchars(json_encode($values), ENT_NOQUOTES, …))
      .  "'";
```
Client side: `for(;++b<f.length;) c.setAttribute(f[b], f[++b]);` — every captured attribute (including any injected `onmouseover`, second `src`, second `style`) is applied to the live iframe.

> **Consequence:** the `replaceIframes` step does NOT neutralize attribute breakout — it *preserves* it. The regex `attr="[^"]*"` is exactly what turns a `"`-breakout into a new, cleanly-captured attribute.

---

## 3. The Media Key as User-Controlled Input

### 3.1 The `[MEDIA]` path (primary vector)
`convertMediaTag()`:
```php
if (preg_match('(^\\[MEDIA=(\\w++)\\]([^"\\[]++)\\[/MEDIA\\])i', $markup, $m)) {
    $markup = '[URL media="' . $m[1] . ':' . $m[2] . '"]' . $url . '[/URL]';
}
```
- `$m[2]` (the key) = `[^"\[]++` → **any character except `"` and `[`**.
- The key is **not** validated against the site's match regex.
- At render, `parseVars()` urldecodes each `v`, so `%22`→`"`, `%26`→`&`, `%3C`→`<`, `%3E`→`>`, `%3D`→`=`, etc.

**Minimal attribute-breakout primitive** (works on any provider whose template puts a var inside a double-quoted attribute):
```
[MEDIA=codepen]user=x%22onmouseover%3D%22alert(1)//;id=abc123[/MEDIA]
```
→ `user = x"onmouseover="alert(1)//`
→ template emits:
```html
<iframe src="https://codepen.io/x"onmouseover="alert(1)//embed/abc123?height=400&amp;default-tab=html,result" …>
```
→ `replaceIframe` captures `src="https://codepen.io/x"` and `onmouseover="alert(1)//embed/abc123?…"` → client sets both via `setAttribute` → hover fires `alert(1)`.

### 3.2 The bare-URL path (secondary, constrained)
`Parser::match()` runs the site's regexes, so the captured vars are constrained (e.g. `user=[-\w]+`). The user can still influence *which* URL is matched (subdomain, path shape) but cannot inject `"`/`&` into the captured vars. The key is then `serializeVars()`-ed. This path is much weaker than `[MEDIA]` but still lets the user choose the resource that gets loaded (e.g. which Imgur album, which archive.org item, which CircuitJS circuit).

### 3.3 Why `"` is the magic character
All nine templates quote their attributes with `"`. A `"` inside a var value terminates the attribute. The only way to get a `"` into a var is via the `[MEDIA]` path + `%22` (urldecode). The bare-URL regexes never capture `"`.

---

## 4. Per-Provider Audit

For each provider: **match regex** (bare-URL path), **key shape**, **template sink**, **user-controllable vars**, and **how to override the renderer's mechanics**.

> Legend — renderer "mechanics" the user can override:
> **A**ppearance (style/size) · **U**I (event handlers, data-\*) · **S**imulation (what runs/loads) · **L**oading/import (src, resource) · **E**xternal URL (where the request goes)

### 4.1 CodePen
- **Match:** `!codepen\.io/(?<user>[-\w]+)/(?:details|embed|full|live|pen)/(?<id>\w+)!`
- **Vars:** `user` (`[-\w]+`), `id` (`\w+`). Key = `id=…;user=…`.
- **Template sink:**
  ```html
  <iframe src="https://codepen.io/{$user}/embed/{$id}?height=400&amp;default-tab=html,result"
          style="height:400px;width:100%">
  ```
- **User-controllable (via `[MEDIA]`):** `user`, `id` (and any invented `k=v`).
- **Overrides:**
    - **E/L/S:** inject a second `src` → iframe loads attacker URL. `user=x%22src%3D%22https%3A%2F%2Fattacker.example%2Fp%22onload%3D%22…//`
    - **A:** inject `style` → resize/reposition. `user=x%22style%3D%22width:100%25%3Bheight:10px%22…`
    - **U:** inject `onmouseover`/`onload`/`data-*` → JS on hover/load.
    - **Fixed mechanics bypassed:** `height=400`, `default-tab=html,result`, `style="height:400px;width:100%"` are all overridable.

### 4.2 Falstad / CircuitJS  ← the "CircuitJS sink"
- **Match:** `!falstad\.com/circuit/circuitjs\.html\?c(?:ct=(?<cct>[^&]+)|tz=(?<ctz>[-+=\w]+))!`
- **Vars:** `cct` (`[^&]+`) **or** `ctz` (`[-+=\w]+`). Key = `cct=…` or `ctz=…`.
- **Template sink:**
  ```html
  <iframe style="height:500px;max-height:90vh;width:100%"
          src="https://www.falstad.com/circuit/circuitjs.html?running=false&amp;c{{ $cct ? 'ct=' . $cct : ('tz=' . $ctz) }}">
  ```
- **User-controllable (via `[MEDIA]`):** `cct`, `ctz` (and any invented `k=v`).
- **Overrides:**
    - **S (simulation):** `cct` selects the circuit that CircuitJS loads. `[MEDIA=falstad]cct=<circuit-id>[/MEDIA]` → `…?running=false&cct=<circuit-id>`.
    - **S (override fixed `running=false`):** because `cct` is urldecoded, inject `&`: `cct=abc%26running=true` → `…?running=false&cct=abc&running=true` → last `running` wins → **circuit auto-runs**.
    - **S (other sim params):** inject any CircuitJS query param (`t`, `s`, `z`, …) the same way.
    - **E/L:** inject a second `src` → load a different (attacker) page in the iframe.
    - **A:** inject `style` → override `height:500px;max-height:90vh`.
    - **U:** inject `onload`/`onmouseover` → JS.
    - **Fixed mechanics bypassed:** `running=false`, `height:500px;max-height:90vh;width:100%`, and the `cct`/`ctz` choice are all user-overridable.

### 4.3 GitHub Gist
- **Match:** `@gist\.github\.com/(?<id>(?:[-\w]+/)?[\da-f]+(?:/[\da-f]+)?\b(?!/archive))@`
- **Vars:** `id` (optional `user/` + hex + optional `/hex`). Key = bare `id` (matches `^[-./:\w]+$`).
- **Template sink:**
  ```html
  <iframe onload="let c=new MessageChannel;…"
          src="https://s9e.github.io/iframe/2/gist.min.html#{$id}"
          style="height:180px;width:100%" data-s9e-mediaembed-api="2">
  ```
- **User-controllable (via `[MEDIA]`):** `id` (and any invented `k=v`).
- **Overrides:**
    - **L/S:** `id` is the fragment after `#` in the s9e gist proxy. The proxy (`gist.min.html`) fetches the gist by that id. User chooses which gist is fetched/rendered.
    - **E:** inject a second `src` → bypass the s9e proxy entirely, load attacker URL.
    - **A:** inject `style` → override `height:180px`.
    - **U:** `data-s9e-mediaembed-api="2"` causes `replaceIframe` to **drop `onload`** and use the `postMessage` miniplayer path instead — so the template's `onload` is not the live handler, but an injected `onmouseover`/second `onload` still survives.
    - **Fixed mechanics bypassed:** the s9e proxy URL, `height:180px`, and the `onload` MessageChannel bootstrap.

### 4.4 Google Drive
- **Match:** `!drive\.google\.com/.*?(?:file/d/|id=)(?<id>[-\w]+)!`
- **Vars:** `id` (`[-\w]+`). Key = bare `id`.
- **Template sink:**
  ```html
  <span style="padding-bottom:75%">
    <iframe src="//drive.google.com/file/d/{$id}/preview">
  ```
- **User-controllable (via `[MEDIA]`):** `id` (and any invented `k=v`).
- **Overrides:**
    - **L/S/E:** `id` selects the Drive file that is previewed. User chooses the file (any file id they know).
    - **E:** inject a second `src` → load attacker URL.
    - **A:** the outer `<span style="padding-bottom:75%">` is fixed, but an injected `style` on the iframe (or a breakout that adds a `style` to the span) can change the aspect ratio/size.
    - **U:** inject `onload`/`onmouseover` → JS.
    - **Fixed mechanics bypassed:** the `//drive.google.com/file/d/{id}/preview` pattern and the 75% padding box.

### 4.5 Google Sheets
- **Match (multiple):**
    - `@docs.google.com/spreadsheet(?:/ccc?key=|(?:[^e]/)+)(?<id>(?:e/)?[-\w]+)@`
    - `@oid=(?<oid>\d+)@`
    - `@#gid=(?<gid>\d+)@`
    - `@/pub(?<type>chart)@`
- **Vars:** `id` (`(e/)?[-\w]+`), `oid` (digits), `gid` (digits), `type` (`chart`). Key = `gid=…;id=…;oid=…;type=…` (subset).
- **Template sink:**
  ```html
  <xf:if is="$type=='chart'">
    … <iframe src="https://docs.google.com/spreadsheets/d/{$id}/pubchart?oid={$oid}&amp;format=interactive">
  <xf:else/>
    … <iframe src="https://docs.google.com/spreadsheets/d/{$id}/p{{ $id|substr(0,2)=='e/' ? 'ubhtml?widget=true&amp;headers=false' : 'review' }}#gid={$gid}">
  ```
- **User-controllable (via `[MEDIA]`):** `id`, `oid`, `gid`, `type` (and any invented `k=v`).
- **Overrides:**
    - **S/L:** `type=chart` switches the renderer between the `pubchart` (interactive chart) and `pubhtml`/`review` (sheet) branches — **the user picks the rendering mode** by setting `type`.
    - **S:** `gid` selects the sheet/tab; `oid` selects the chart object.
    - **E:** inject a second `src` → load attacker URL.
    - **A:** inject `style` → resize.
    - **U:** inject `onload`/`onmouseover` → JS.
    - **Fixed mechanics bypassed:** the `pubchart` vs `pubhtml`/`review` branch, the `format=interactive`, `widget=true&headers=false`, and `#gid=` are all user-influenced.

### 4.6 Imgur
- **Match (multiple):**
    - `@imgur.com/(?<id>(?:a|gallery)/\w+)@`
    - `@i.imgur.com/(?<id>\w{5,7})[lms]?.@`
    - `@imgur.com/(?!upload\b)(?<id>\w+)(?![\w./])@`
    - scrape: `data-id="(?<id>[\w/]+)"` from `https://api.imgur.com/oembed.xml?url=/{@path}`
- **adjustVarsImgur:** if `strlen(id)<6` → `id = 'a/'.id`; `str_replace('gallery/','a/',id)`.
- **Vars:** `id` (and `type` is referenced by the template but not set by match — see below). Key = bare `id` (e.g. `a/abc123`).
- **Template sink:**
  ```html
  <iframe onload="…" style="height:400px;width:542px" data-s9e-mediaembed-api="2"
          src="https://s9e.github.io/iframe/2/imgur.min.html#{{ $type=='album' ? 'a/' : '' }}{$id}">
  ```
- **User-controllable (via `[MEDIA]`):** `id`, `type` (and any invented `k=v`).
- **Overrides:**
    - **L/S:** `id` selects the Imgur image/album fetched by the s9e proxy. `type=album` toggles the `a/` prefix (album vs single image) — **user picks single vs album**.
    - **E:** inject a second `src` → bypass the s9e proxy, load attacker URL.
    - **A:** inject `style` → override `height:400px;width:542px`.
    - **U:** `data-s9e-mediaembed-api="2"` drops the template `onload` (postMessage path), but injected `onmouseover`/second `onload` survive.
    - **Fixed mechanics bypassed:** the s9e proxy URL, the `a/` album prefix, `height:400px;width:542px`, and the `onload` bootstrap.

### 4.7 Internet Archive
- **Match:** none directly (empty `[0]`). **Scrape only:**
    - `id` from `meta property="twitter:player" content="https://archive.org/embed/(?<id>[^/"]+)"`
    - `width` from `og:video:width`, `height` from `og:video:height`
    - when the URL matches `!archive\.org/(?:details|embed)/!`
- **Filters:** `height`→`filterUint`, `id`→`htmlspecialchars_decode`, `width`→`filterUint`.
- **Defaults:** `width=640`, `height=360`.
- **Vars:** `id` (`[^/"]+` — can contain `?`, `&`, `=`), `width`, `height`. Key = `id=…;height=…;width=…` (or bare `id` if it matches `^[-./:\w]+$`).
- **Template sink:**
  ```html
  <xf:if is="$id and contains($id,'playlist=1')">
    … <iframe src="https://archive.org/embed/{$id}&amp;list_height=150" style="height:170px">
  <xf:else/>
    <span style="width:{$width}px">
      <span style="{{ $width>0 ? 'padding-bottom:' . (100*$height/$width) . '%' : '' }}">
        <iframe src="https://archive.org/embed/{$id}">
  ```
- **User-controllable (via `[MEDIA]`):** `id`, `width`, `height` (and any invented `k=v`).
- **Overrides:**
    - **S/L:** `id` selects the archive.org item. Because `id` can contain `?`/`&` (it's `[^/"]+` and only `htmlspecialchars_decode`-d, not regex-constrained on the `[MEDIA]` path), the user can embed **query params in the item id** — e.g. `id=details/xyz?playlist=1` flips the renderer into the **playlist branch** (`list_height=150`, `height:170px`).
    - **A:** `width`/`height` directly drive `style="width:{$width}px"` and the `padding-bottom` aspect ratio — **user sets the exact pixel size** (subject to `filterUint` ≥ 0).
    - **E:** inject a second `src` → load attacker URL.
    - **U:** inject `onload`/`onmouseover` → JS.
    - **Fixed mechanics bypassed:** the `playlist=1` branch, `list_height=150`, `height:170px`, `width:{$width}px`, and the computed `padding-bottom` aspect ratio.
    - **Note:** `filterUint` clamps `width`/`height` to unsigned ints, so the user can't inject CSS via those two — but `id` is only `htmlspecialchars_decode`-d, so it remains a rich injection surface.

### 4.8 JSFiddle
- **Match:** `@jsfiddle.net/(?:(?<user>\w+)/)?(?!\d+\b|embedded\b|show\b)(?<id>\w+)\b(?:/(?<revision>\d+)\b)?@`
- **Vars:** `user` (optional `\w+`), `id` (`\w+`, not digit-led, not `embedded`/`show`), `revision` (optional digits). Key = `id=…;revision=…;user=…` (subset).
- **Template sink:**
  ```html
  <iframe style="height:400px;width:100%"
          src="//jsfiddle.net/{$id}/{$revision}/embedded/{{ …theme logic… }}">
  ```
- **User-controllable (via `[MEDIA]`):** `id`, `revision`, `user` (and any invented `k=v`).
- **Overrides:**
    - **L/S:** `id`/`revision` select the fiddle and its revision that is embedded. User chooses the fiddle.
    - **E:** inject a second `src` → load attacker URL.
    - **A:** inject `style` → override `height:400px;width:100%`.
    - **U:** inject `onload`/`onmouseover` → JS.
    - **Fixed mechanics bypassed:** the `//jsfiddle.net/{id}/{revision}/embedded/` pattern, the theme logic, and `height:400px;width:100%`.
    - **Example from the prompt:** `[MEDIA=jsfiddle]abcd/3[/MEDIA]` → key `abcd/3` → `parseVars` → `$vars['id']='abcd/3'`, `revision` unset → `src="//jsfiddle.net/abcd/3//embedded/…"`. (Note the double-slash: the `[MEDIA]` path does NOT split `id`/`revision` the way the bare-URL match would.)

### 4.9 SlideShare
- **Match:** `!slideshare.net/slideshow/embed_code/key/(?<key>\w+)$!`  (required attr: `key`)
- **Scrape:** `key` from `embed_code/key/(?<key>\w+)`, `id` from `data-slideshow-id="(?<id>\d+)"`.
- **Vars:** `key` (`\w+`), `id` (digits). Key = `key=…` (or `id=…;key=…`).
- **Template sink:**
  ```
  999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
  999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999<span style="width:597px">
    <span style="padding-bottom:81.407035%">
      <iframe src="https://www.slideshare.net/slideshow/embed_code/key/{$key}">
  ```
- **User-controllable (via `[MEDIA]`):** `key`, `id` (and any invented `k=v`).
- **Overrides:**
    - **L/S:** `key` selects the SlideShare deck that is embedded. User chooses the deck.
    - **E:** inject a second `src` → load attacker URL.
    - **A:** the fixed `width:597px` / `padding-bottom:81.407035%` box can be overridden by an injected `style`.
    - **U:** inject `onload`/`onmouseover` → JS.
    - **Fixed mechanics bypassed:** the `embed_code/key/{key}` pattern and the 597px/81.4% box.

---

## 5. Common Patterns & Attack Vectors

All nine providers share the same structural weakness, so the same primitives apply everywhere:

1. **Attribute breakout via `%22`.** Any var placed inside a `"…"`-quoted attribute can be terminated with `%22` (→ `"`). This is the master key.
2. **Attribute injection.** After breaking out, append `attr%3D%22value%22` (→ `attr="value"`) to add new attributes: `onmouseover`, `onload`, `src`, `style`, `data-*`, `formaction`, etc.
3. **Duplicate-attribute override.** `replaceIframe` uses `array_combine`, so a **second** `src`/`style` wins over the renderer's fixed one → direct override of appearance, loading, and external URL.
4. **Query-param injection (simulation).** In Falstad (and anywhere a var lands in a URL query string), `%26` (→ `&`) lets the user append params that override fixed ones (`running=false` → `running=true`).
5. **Branch selection (logic override).** In Google Sheets (`type=chart`) and Internet Archive (`id` containing `playlist=1`) and Imgur (`type=album`), a user-controlled var selects which template branch / which resource mode is used.
6. **The `replaceIframes` JSON sink preserves breakouts.** It re-emits every captured attribute via `setAttribute`, so injected event handlers and duplicate `src`/`style` survive to the live DOM.
7. **`data-s9e-mediaembed-api="2"` (Gist, Imgur)** drops the template `onload` in favor of a `postMessage` miniplayer — but does NOT stop injected `onmouseover`/second `onload`.

### Overriding the renderer's fixed "mechanics" — summary table

| Mechanic (renderer-fixed) | Provider(s) | How user overrides it |
| --- | --- | --- |
| `src` (external URL / what loads) | all 9 | inject second `src` via `%22` breakout |
| `style` (appearance: size/aspect) | all 9 | inject `style` (duplicate wins) |
| `running=false` (CircuitJS sim) | Falstad | `cct=…%26running=true` |
| circuit id (CircuitJS sim) | Falstad | `cct=<id>` / `ctz=<id>` |
| `height=400` / `default-tab` | CodePen | inject `style` / second `src` |
| `pubchart` vs `pubhtml`/`review` | Google Sheets | set `type=chart` |
| `#gid=` / `oid=` (sheet/chart) | Google Sheets | set `gid`, `oid` |
| `a/` album prefix | Imgur | set `type=album` |
| `playlist=1` branch, `list_height` | Internet Archive | `id=…?playlist=1` |
| `width`/`height` px + aspect | Internet Archive | set `width`, `height` (filterUint) |
| `{id}/{revision}` fiddle | JSFiddle | set `id`, `revision` |
| `embed_code/key/{key}` | SlideShare | set `key` |
| `file/d/{id}/preview` | Google Drive | set `id` |
| `gist.min.html#{id}` proxy | Gist | set `id` |
| `imgur.min.html#{id}` proxy | Imgur | set `id` |

---

## 6. Surprising Findings

1. **The `[MEDIA]` key bypasses `Parser::match()` entirely.** The bare-URL path is heavily regex-constrained; the `[MEDIA]` path is almost unbounded (`[^"\[]++`). Most of the attack surface lives in the path that looks most "sanitized."
2. **`urldecode` in `parseVars` is the injection amplifier.** `%22`/`%26`/`%3C` become live `"`, `&`, `<`. The serialization side even goes out of its way to encode `..` as `%2E%2E` to survive XenForo — proving the key round-trips through encoding, and the decode side is where it bites.
3. **`replaceIframes` is a *preserver*, not a *sanitizer*.** Its `attr="[^"]*"` regex is precisely what converts a `"`-breakout into a clean new attribute, then re-applies it via `setAttribute`. Intuitively it looks like a defense (JSON-encoding the iframe), but it faithfully reproduces the breakout.
4. **Duplicate-attribute semantics give the user the win.** `array_combine` keeps the *last* duplicate, so a user-injected second `src`/`style` silently overrides the renderer's fixed value — no escaping needed, just ordering.
5. **CircuitJS "simulation" is user-steerable end-to-end.** The Falstad `cct`/`ctz` value is the circuit *and* a query-string injection point: the user picks the circuit **and** can flip `running=false`→`true` and append any other sim param.
6. **Logic-branch selection via data, not markup.** Google Sheets `type=chart`, Internet Archive `playlist=1`, Imgur `type=album` — the user chooses *which code path / resource mode* runs by setting a data var, not by injecting HTML.
7. **`htmlspecialchars_decode` on Internet Archive `id` is a double-edged sword.** It's meant to recover a real id from a scraped meta tag, but on the `[MEDIA]` path it means an attacker-supplied `id` can carry encoded entities that decode into live characters.
8. **The old TextFormatter `MediaEmbed` plugin is structurally safer.** It interpolates `{@var}` into *s9e TextFormatter templates* (which escape by default) and runs vars through an explicit `allowedFilters` allowlist (`htmlspecialchars_decode`, `stripslashes`, `str_replace`, `urldecode`). The new XenForo add-on's `{$var}` is raw — the regression is architectural.
9. **`[IMG]` is a parallel, independent sink.** `[IMG]https://…[/IMG]` is core XenForo (not a media site) and sets `<img src>` from user input — a separate attribute-injection surface that the media-key audit does not cover but shares the same root cause (user string → unescaped attribute).

---

## 7. Recommendations (defensive)

**Highest leverage (fix the root cause):**
1. **Escape vars in templates.** Use `{$var|e}` / `{$var|escape}` (or `htmlspecialchars`) for every var that lands in an attribute or text node. This single change neutralizes vectors 1–3 and 6.
2. **Validate the `[MEDIA]` key against the site's match regex** (i.e. run it through the same `Parser::match`-style validation as bare URLs), or restrict the key charset to `[-./:\w]` and reject `k=v;…` forms unless the provider explicitly supports them.
3. **Don't `urldecode` untrusted values before interpolation** — or decode *after* escaping, and re-escape.

**Defense in depth:**
4. **In `replaceIframe`, whitelist attributes** (only `src`, `style`, `data-s9e-mediaembed*`, `loading`, …) instead of re-emitting every captured `attr="value"` pair. Drop or escape event-handler attributes (`on*`).
5. **De-duplicate attributes by first-wins** (or reject duplicates) so a user-injected second `src`/`style` can't override the renderer's.
6. **Per-provider var allowlists + type filters.** E.g. Falstad `cct`/`ctz` → `^[-+=\w]+$` (no `&`); Internet Archive `id` → strip query string or re-validate; Google Sheets `type` → `in_array(['chart'])`.
7. **CSP** for the page (`frame-src` allowlisting the nine provider hosts + the s9e proxy) so an injected second `src` to an attacker host is blocked even if the breakout succeeds.
8. **`sandbox` the iframes** (`sandbox="allow-scripts allow-same-origin"` as appropriate) to limit what a loaded/attacker page can do.
9. **Cover `[IMG]`** with the same attribute-escaping discipline.

**Verification:**
10. Add regression tests that feed `[MEDIA=prov]x%22onmouseover%3D%22alert(1)//…[/MEDIA]` (and the `cct=…%26running=true` Falstad case) through the full pipeline and assert no `on*`/duplicate-`src`/`style` survives into the final `data-s9e-mediaembed-iframe` JSON.

---

## Appendix A — Exact per-provider match regexes (from `Parser::$sites`)

| Provider | Match regex(es) |
| --- | --- |
| codepen | `!codepen\.io/(?<user>[-\w]+)/(?:details\|embed\|full\|live\|pen)/(?<id>\w+)!` |
| falstad | `!falstad\.com/circuit/circuitjs\.html\?c(?:ct=(?<cct>[^&]+)\|tz=(?<ctz>[-+=\w]+))!` |
| gist | `@gist\.github\.com/(?<id>(?:[-\w]+/)?[\da-f]+(?:/[\da-f]+)?\b(?!/archive))@` |
| googledrive | `!drive\.google\.com/.*?(?:file/d/\|id=)(?<id>[-\w]+)!` |
| googlesheets | `@docs\.google\.com/spreadsheet(?:/ccc\?key=\|(?:[^e]/)+)(?<id>(?:e/)?[-\w]+)@` · `@oid=(?<oid>\\\\d+)@` · `@#gid=(?<gid>\\\\d+)@` · `@/pub(?<type>chart)@` |
| imgur | `@imgur\.com/(?<id>(?:a\|gallery)/\w+)@` · `@i\\\\.imgur\\\\.com/(?<id>\\\\w{5,7})[lms]?\\\\.@` · `@imgur\\\\.com/(?!upload\\\\b)(?<id>\\\\w+)(?![\\\\w./])@` · scrape `data-id` |
| internetarchive | scrape only: `twitter:player` → `id`, `og:video:width/height` → `width/height`; match `!archive\\\\.org/(?:details\|embed)/!` |
| jsfiddle | `@jsfiddle.net/(?:(?<user>\w+)/)?(?!\d+\b\|embedded\b\|show\b)(?<id>\w+)\b(?:/(?<revision>\d+)\b)?@` |
| slideshare | `!slideshare.net/slideshow/embed_code/key/(?<key>\w+)$!` · scrape `key`, `data-slideshow-id` |

## Appendix B — Key round-trip examples

| User input | Key | `parseVars` result | Template effect |
| --- | --- | --- | --- |
| `[MEDIA=codepen]user=x%22onmouseover%3D%22alert(1)//;id=abc123[/MEDIA]` | `user=x%22onmouseover%3D%22alert(1)//;id=abc123` | `user=x"onmouseover="alert(1)//`, `id=abc123` | `src="…/x"onmouseover="alert(1)//embed/abc123?…"` → hover JS |
| `[MEDIA=falstad]cct=abc%26running=true[/MEDIA]` | `cct=abc%26running=true` | `cct=abc&running=true` | `src="…?running=false&cct=abc&running=true"` → auto-run |
| `[MEDIA=googlesheets]type=chart;id=abc;oid=123[/MEDIA]` | `type=chart;id=abc;oid=123` | `type=chart`, `id=abc`, `oid=123` | takes `pubchart` branch |
| `[MEDIA=internetarchive]id=details/xyz?playlist=1[/MEDIA]` | `id=details/xyz?playlist=1` | `id=details/xyz?playlist=1` | takes playlist branch (`list_height=150`) |
| `[MEDIA=jsfiddle]abcd/3[/MEDIA]` | `abcd/3` | `id=abcd/3` (no `revision`) | `src="//jsfiddle.net/abcd/3//embedded/…"` |
