# The Shazam External Module's Frontend Injector: `shazam.js`

---

Included `shazam.js` release version: `v1.3.13`

`shazam.js` last retrieved and included on: `2024-08-31`

Earliest known compatible `shazam.js` release version: `v1.3.5`

---

This directory contains a copy of the [Shazam REDCap External
Module](https://github.com/susom/redcap-em-shazam/tree/master)'s `js/shazam.js`
file, and is supplied with the Conflux Loader REDCap External Module.

Shazam is **Copyright (c) 2017 Stanford School of Medicine** and was released by
Stanford School of Medicine under the terms of the open-source MIT License (see
the `LICENSE` file). **The LICENSE file in this directory applies exclusively to
the code constituting `shazam.js` in this directory.** The copy of `shazam.js`
has been retrieved with no modifications from the upstream Stanford Shazam EM
distribution.

Shazam is hosted on GitHub and can be found at: https://github.com/susom/redcap-em-shazam/tree/master

## FAQ

### I'm already using Shazam, will this cause any problems?

**TL;DR: no, this will not cause problems.**

Conflux Loader will dynamically identify and reference an existing Shazam
installation's `shazam.js` if Conflux Loader and Shazam are both enabled on the
same REDCap project, *and* Conflux Loader is configured with knowledge of what
Shazam's installation folder is called (usually `"shazam"` or
`"redcap_em_shazam"`, this is a system-level REDCap EM setting).

Conflux Loader will always prefer to use a found Shazam installation's
`js/shazam.js` over its own. This ensures that the `shazam.js` script is only
loaded once, and that the REDCap admin has the final decision on which Shazam
version to use.

Shazam versions older than `v1.3.5` are not supported due to (minor) injector
incompatibilities. They might work, but you're on your own.

### Why does Conflux Loader make use of `shazam.js`?

* Compatibility with Shazam when injecting frontend scripts. This makes for an
easy transition of Shazam codebases to Conflux Loader.

* `shazam.js`'s injection routines are a product of years of development and
experience with the REDCap frontend's behaviors and quirks.
