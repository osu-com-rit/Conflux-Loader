# Reference: The `loader_config.json` Format *(Conflux Loader)*

Conflux Loader currently supports, listed in order of least to most general:
*field*, *instrument*, and *page* level injections.

These different "types" of injection are denoted by entries in
`loader_config.json` (under `"fields"`, `"instruments"`, and `"pages"`). These
entries follow a fairly uniform structure of declaring a 'name' of something to
target for injection (`field_name`, `instrument_name`, `page_path`), and then
specifying source code files to inject into the target.

Specific types of entry, like a `"pages"` entry, will generally have some
'type-specific' keys too; in the case of `"pages"`, the entry may also include a
regex for matching URLs. These type-specific features are documented under the
the appropriate headings in this reference.


## **Field Injections** (`"fields"`)

Field injections are useful for altering the behavior and styling of a
particular REDCap instrument's field, as would be visualized as in either the
data entry or survey pages.

Injections only work for REDCap fields of the ***Descriptive Text*** field type
-- it is planned that other field types will eventually be supported, but this
is an inherited mechanism from the Shazam external module, and it works well
enough for now.

An example entry for a `fields` injection:

```json
"fields": [
  {
    "field_name": "heading",
    "html": "heading.html",
    "css": "heading.css",
    "javascript": "heading.js"
  }
]
```

The following entries are supported configuration elements in entries of `fields`.

### `field_name`

The name of the REDCap field to target for code injection.

### `html` / `css` / `javascript`

The HTML / CSS / JavaScript file(s) to inject for this field.

### `use_jsmo` and `use_jsmo.bind_as`

Inject REDCap's JavaScript Module Object (JSMO) into the page:

```json
"use_jsmo": true
```

Optionally, specify `use_jsmo.bind_as` to automatically name the JSMO injected
into the page (REDCap does not give a JavaScript name to JSMO by default, so
this bypasses some boilerplate):

```json
"use_jsmo": { "bind_as": "JSMO" }
```

### `disable_for_survey` and `disable_for_data_entry`

`disable_for_survey` prevents the injection on survey pages.

`disable_for_data_entry` prevents the injection on the DataEntry page (AKA form
mode/view).

## **Instrument Injections** (`"instruments"`)

Instrument injections occur at the instrument level, and are useful for
modifying the entire page of an instrument, such as in data entry and surveys.

Instrument-level injections occur at the top of the HTML page (via the
`redcap_survey_page_top` and `redcap_data_entry_form_top` EM hooks), and are
best used for code that affects multiple fields in an instrument, or for
functionality and styling that should be 'universal' to the instrument.

An example entry for an `instruments` injection:

```json
"instruments": [
  {
    "instrument_name": "form_1",
    "css": "form1styling.css"
  }
]
```

The following entries are supported configuration elements in entries of `instruments`.

### `instrument_name`

The name of the REDCap instrument to target for code injection.

### `html` / `css` / `javascript`

The HTML / CSS / JavaScript file(s) to inject at the top of this instrument.

### `use_jsmo` and `use_jsmo.bind_as`

Inject REDCap's JavaScript Module Object (JSMO) into the page. Operates the same
as for `fields`.

### `disable_for_survey` and `disable_for_data_entry`

See `fields` description of `disable_for_survey` and `disable_for_data_entry`.

## **Page Injections** (`"pages"`)

Page injections occur -- you guessed it -- on the page level. These are more
generally applicable than instrument level injections, as they can theoretically
target *any* page of a REDCap instance.

Most commonly these will be used to target dashboards, external module pages,
and the actual project page presentation.

> [!CAUTION]
> Injections to system-level and login pages are locked behind a few
> system-level settings (`"allow-login-injection"` and
> `"allow-system-injection"`). These are disabled by default and you shouldn't
> generally need these. More commentary on this can be found in the system
> administrator documents: [Reference: Conflux Loader EM Configuration](./InstanceConfiguration.md).

An example entry for a `pages` injection:

```json
"pages": [
  {
    "page_path": "ProjectDashController:view",
    "css": "all_dashboard_styles.css",
    "javascript": "dashboard_common.js"
  }
]
```

The following entries are supported configuration elements in entries of `instruments`.

### `page_path`

The REDCap page path that you wish to inject against.

These generally correspond to the exact page in the URL, like `DataEntry` or
`Design/online_designer.php`. Sometimes REDCap treats logical 'subpath's like
the true path, such as for Dashboards, where you will use
`ProjectDashController:view` as the path.

### `html` / `css` / `javascript`

The HTML / CSS / JavaScript file(s) to inject at the top of this page.

### `use_jsmo` and `use_jsmo.bind_as`

Inject REDCap's JavaScript Module Object (JSMO) into the page. Operates the same
as for `fields`.

### `disable_for_survey` and `disable_for_data_entry`

See `fields` description of `disable_for_survey` and `disable_for_data_entry`.

For `pages` this is functionally the same as a negative `path_match_regex` match
on `surveys/index.php` and `DataEntry/index.php`.

### `path_match_regex`

`path_match_regex` is a powerful tool that lets you use a regular expression
(regex) to match page paths. This can be useful for matching multiple paths in
the same entry, or for targeting more specific 'subpages' of a more general
page, where 'subpage' is determined by some URL parameter.

Perhaps the most useful trick here is to target specific dashboards for
injection. Here we have a dashboard with the `dash_id` of `4`, and we can write
a regex to target it specifically for injection with the following `pages`
config entry:

```json
{
  "page_path": "ProjectDashController:view",
  "path_match_regex": "/[?&]dash_id=4(?:&|$)/",
  "css": "my_dashboard_style.css"
}
```

> [!NOTE]
> It is likely that we'll eventually implement regex matchers for
> instrument and field names too. Let us know if you need this feature!
