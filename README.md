
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

## Dependencies

Conflux Loader has a hard dependency on the
[Shazam](https://github.com/susom/redcap-em-shazam) External Module.

**Shazam must be installed and enabled before Conflux Loader can be successfully
configured.**

Conflux Loader's system-level configuration requires the installer to supply the
prefix of the installed Shazam EM (typically `"shazam"` or
`"redcap_em_shazam"`).

## Example

A valid Conflux Loader module is a directory looking something like:

```
examplemodule/
├── common.js
├── heading.css
├── heading.html
├── survey_hook.js
├── instrument_hook.js
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
      "javascript": "instrument_hook.js"
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

## Acknowledgments

Conflux Loader was built by the Research IT group at The Ohio State Wexner
Medical Center.

Conflux loader was heavily inspired by the "Shazam" and "Javascript Injector"
REDCap External Modules, and aims to highly compatible with them in the
short-to-medium term.

## Legal

# @@@ <<< LEGAL TODO >>> @@@
