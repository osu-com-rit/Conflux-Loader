# How Conflux Loader Works *(Conflux Loader)*

All things considered, Conflux Loader is a fairly simple REDCap External Module,
and behaves very similar to the
[Shazam](https://github.com/susom/redcap-em-shazam) and [REDCap JavaScript
Injector](https://github.com/grezniczek/redcap_javascript_injector) External
Modules. As mentioned in the [README](../README.md), Conflux Loader was designed
as a migration target for the Shazam/JSI codebases that grew to the point of
needing version control and dependency management.

The [Quickstart's example config
file](./Quickstart.md#the-loader_configjson-configuration-file) should look
familiar to anyone acquainted with REDCap script injection, as it is essentially
a broader, textual representation of how Shazam and JSI wire things up, but as a
single configuration file.

## Injection Specifics

### CSS and Javascript

CSS and Javascript are loaded in a manner consistent with Shazam and JSI. CSS
and Javascript files are inlined at the top of the loaded page, as would be
expected when using the `redcap_survey_page_top`, `redcap_data_entry_form_top`,
and `redcap_every_page_top` External Module API hooks.

This means that injected CSS and JavaScript is ***not scoped***. Field CSS/JS
can freely affect and bleed into other fields, and developers are encouraged to
be mindful in manually handling this shared scope ([see Quickstart CSS class
example](./Quickstart.md#headinghtml-source-code)).

While there's no magic here yet, in the future we may be able to take advantage
of Shadow DOMs to limit the scope of injected CSS and JS, but that remains to be
seen.

> [!NOTE]
> There is no current way to load JS and CSS files without inlining them, as
> safely serving files from a REDCap instance's filesystem is somewhat
> out-of-scope for this module.

### HTML

For HTML files, Conflux Loader loads a copy of Shazam's `shazam.js` code
injector (using some magic to find this path at runtime), and feeds HTML
information in a manner compliant with how the Shazam EM would.

By reusing Shazam's injector we're able to make guarantees on Conflux's
compatibility with Shazam, which is a major bonus when we're designing Conflux
Loader as a migration target for post-Shazam projects (see [Migrating from
Shazam and JavaScript Injector](./MigrationGuide.md)).

Also, Shazam's injector has a lot of *"lessons learned"* encoded into how it
transforms HTML before injection. Shazam does a lot of the heavy lifting to
match injected HTML's styling, and to make sure that the injected HTML plays
nicely with the various contexts that it may appear in. It would be unwise for
use to ignore those lessons in developing our own injector!
