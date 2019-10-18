### data-s9e-livepreview-hash

The value of a `data-s9e-livepreview-hash` attribute is automatically replaced during rendering with a hash that corresponds to the `outerHTML` value of its parent element. Elements with matching hashes are considered identical during DOM diffing, regardless of their content. This can be used to quickly skip deep trees or content that is modified post-rendering.

The hashing algorithm is unspecified and may change between minor releases.


### data-s9e-livepreview-ignore-attrs

Contains a space-separated list of attributes whose content should not be replaced or removed in the live preview. Can be used to preserve the state of an interactive element such as `detail` during preview.

```html
<details open="" data-s9e-livepreview-ignore-attrs="open">
This will not automatically reopen if closed during preview.
</details>
```


### data-s9e-livepreview-onrender

The content of a `data-s9e-livepreview-onrender` attribute is executed after the new DOM has been generated and before it's inserted into the target element. In this context, `this` will refer to the attribute's element.

```html
<span data-s9e-livepreview-onrender="this.style.color='red'">This will be red.</span>
```


### data-s9e-livepreview-onupdate

The content of a `data-s9e-livepreview-onupdate` attribute is executed during DOM diffing, whenever an element or its subtree is created or modified. In this context, `this` will refer to the attribute's element.

This event can be used to add post-processing to an element, such as syntax highlighting in code blocks or processing some markup such as LaTeX on the client side. If the code executed during this event is resource-intensive, it is recommended to use the `data-s9e-livepreview-hash` to reduce the number of updates. In the example below, the event triggers on the first element after each keystroke because its content changes continuously, but it only triggers once on the second element because its content is hashed before the event is triggered and only that hash is used for comparison.

```html
<div data-s9e-livepreview-onupdate="this.innerHTML=Math.random()"></div>
<div data-s9e-livepreview-onupdate="this.innerHTML=Math.random()" data-s9e-livepreview-hash=""></div>
```
