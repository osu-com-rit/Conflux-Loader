
# Conflux Loader

**Conflux Loader** is a REDCap External Module that loads user-defined
Javascript, CSS, and HTML code, in a manner mostly compatible with the
[Shazam](https://github.com/susom/redcap-em-shazam) and [REDCap JavaScript
Injector](https://github.com/grezniczek/redcap_javascript_injector) REDCap
External Modules.

The key feature of Conflux Loader is that it loads from the local filesystem, in
contrast to how Shazam and JSI load from project settings and configuration.

By loading from the local filesystem, Conflux Loader makes it easy to use
typical development tooling for Shazam-style development, as most developer
tooling expects code to exist in a directory.

We have found this module to be particularly useful when a Shazam/JSI codebase
grows beyond a scope that the Shazam/JSI EMs can comfortably handle.

## Example

A valid Conflux Loader module is a directory looking something like:

```
examplemodule/
├── common.js
├── heading.css
├── heading.html
├── survey_hook.js
├── instrument_hook.js
├── instrument_style.css
└── loader_config.json   (REQUIRED)
```

Note the JSON configuration file `loader_config.json`, which we have an example of:

```json
{
  "description": "An Example Conflux Loader Module",

  "fields": [
    {
      "field_name": "heading",
      "html": "heading.html",
      "css": "heading.css",
      "javascript": "heading.js"
    }
  ],

  "instruments": [
    {
      "instrument_name": "form_1",
      "javascript": "instrument_hook.js",
      "css": "instrument_style.css"
    }
  ],

  "pages": [
    {
      "page_path": "surveys/index.php",
      "javascript": "survey_hook.js"
    }
  ]
}
```

## Dependencies

Conflux Loader has a hard dependency on the
[Shazam](https://github.com/susom/redcap-em-shazam) External Module.

**Shazam must be installed and enabled before Conflux Loader can be successfully
configured.**

Conflux Loader's system-level configuration requires the installer to supply the
prefix of the installed Shazam EM (typically `"shazam"` or
`"redcap_em_shazam"`).

## Installation

Conflux Loader is a standard REDCap External Module. It can be installed by
cloning this repository into a subdirectory of your REDCap instance's
`modules` folder:

```
git clone <this@repo> conflux_loader_v0.1
```

From there, you will need to enable the EM on a system level and set the Shazam
prefix to point to your Shazam EM (see 'Dependencies').

The Conflux Loader EM can then be enabled on a per-project basis. Project-level
configuration of Conflux Loader is simple: point it at the directory of the
Conflux Loader module corresponding with your project.

## Mechanism and Compatibility

All things considered, Conflux Loader is a fairly simple REDCap External Module,
and behaves very similar to Shazam and JSI. The example config file above should
look familiar to anyone acquainted with REDCap script injection.

### CSS and Javascript

CSS and Javascript are loaded in a manner consistent with Shazam and JSI. CSS
and Javascript files are inlined at the top of the loaded page, as would be
expected when using the `redcap_survey_page_top`, `redcap_data_entry_form_top`,
and `redcap_every_page_top` External Module API hooks.

**Note:** there is no current way to load JS and CSS files without inlining
them, as safely serving files from a REDCap instance's filesystem is somewhat
out-of-scope for this module.

### HTML

For HTML files, Conflux Loader loads an existing Shazam instances `js/shazam.js`
file (using some magic to find this path at runtime), and feeds HTML information
in a manner compliant with how Shazam would.

Please note that HTML injection is only usable for fields. There is no current
HTML injection capability for pages or instruments.

## Acknowledgment and Attribution

*See the `NOTICE` file for more information.*

Conflux Loader's initial design and development was conducted by the Research
Information Technology department of The Ohio State University College of
Medicine.

Conflux Loader was heavily inspired by the "Shazam" (Stanford School of
Medicine) and "Javascript Injector" (Günther Rezniczek) REDCap External Modules.

The development of Conflux Loader was supported, in part, by The Ohio State
University's Center for Clinical and Translational Science. The content is
solely the responsibility of the authors and does not necessarily represent the
official views of the university, or the Center for Clinical and Translational
Science.

This project was supported, in part, by the National Center for Advancing
Translational Sciences of the National Institutes of Health under Grant Number
UM1TR004548. The content is solely the responsibility of the authors and does
not necessarily represent the official views of the National Institutes of
Health.

## License

*See the `LICENSE` file for more information.*

This software is licensed with the OSI-approved "Apache License 2.0" open-source
software license.

