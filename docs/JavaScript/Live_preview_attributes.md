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
