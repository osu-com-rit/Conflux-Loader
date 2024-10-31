# Quickstart: your first Conflux Loader Module *(Conflux Loader)*

## What we'll end up with!

After this quickstart you should have an instrument with a field that displays
like the following:

![Image of REDCap survey using Conflux Loader module functionality after
 following this quickstart](./images/quickstart_final.png)

## Setup

First, ensure Conflux Loader is running and functional (see [Prerequisites &
Installation](./PrerequisitesInstallation.md) for more information!).

Then, create a REDCap project and, on the 'base' instrument, create a
***Descriptive Text*** field called `heading`. We will wire our code injections
up with this field:

![Image of REDCap instrument with a heading descriptive text instrument
 created](./images/quickstart_instrument.png)

Then, enable Conflux Loader on the project that you have just created, and
enable our Conflux Loader Module (`my_first_loader_module`) in the Conflux
Loader EM project settings:

![Image of Conflux Loader EM project setting enabled on a REDCap
 project](./images/quickstart_enable.png)


## What does a Conflux Loader Module look like?

A valid Conflux Loader module is a directory consisting of a
`loader_config.json` configuration file, followed by source code, structured in
whichever way you prefer.

A simple Conflux Loader module -- let's call it `my_first_loader_module` -- is a
directory of the same name, containing source code files like:

```
my_first_loader_module/
├── heading.js
├── heading.css
├── heading.html
├── ...
└── loader_config.json
```

## The `loader_config.json` configuration file

The `loader_config.json` configuration file is the *'secret sauce'* of a Conflux
Loader module, and contains all of the configuration necessary to wire up pieces
of source code to the relevant place on a REDCap data entry or survey page. For
those familiar with the Shazam External Module, this should all look quite
familiar.

The following short config uses an object entry of the `"fields"` JSON array to
describe injections into a REDCap ***Descriptive Text*** field named `heading`.

```json
{
  "description": "My First Conflux Loader Module",
  "version": "v0.0.1",

  "fields": [
    {
      "field_name": "heading",
      "html": "heading.html",
      "css": "heading.css",
      "javascript": "heading.js"
    }
  ]
}
```

Specifically, the descriptive text field's HTML body is replaced with the
content of the HTML content in `heading.html`. Similarly, CSS (`heading.css`)
and JavaScript (`heading.js`) are injected to alter the styling and behavior of
the page, though there is no mechanism that stops the CSS and JS 'leaking' to
affect the behavior and appearances of other fields and elements.

> [!TIP]
> It is *highly suggested* that CSS and JS code files are limited in scope to
> only altering the appearance and behavior of the specific fields that they
> are injected for.
>
> A good way to go about this is defining your own CSS classes and using
> selectors to drive the logic and styling. See the HTML/CSS/JS source
> code below for an example of how you can do this.


> [!IMPORTANT]
> Conflux Loader currently only injects field HTML/CSS/JS into REDCap fields of
> the ***Descriptive Field*** type. This is likely to be changed eventually.


### `heading.html` source code

```html
<h1 class="my-custom-header">Hello World</h1>
```

### `heading.css` source code

```css
.my-custom-header { color: #f0f; }
```

### `heading.js` source code

> [!NOTE]
> This snippet of code uses Shazam's framework for code injection. Conflux Loader
> loads Shazam internally for strong guarantees of backwards compatibility.

```js
Shazam.beforeDisplayCallback = function() {
  $('.my-custom-header').css({ "transform": "rotate(10deg)", "marginTop": "100px" });
};
```
