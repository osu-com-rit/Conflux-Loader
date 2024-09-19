
# Conflux Loader

![GitHub Release](https://img.shields.io/github/v/release/osu-com-rit/Conflux-Loader) ![GitHub License](https://img.shields.io/github/license/osu-com-rit/Conflux-Loader) [![DOI](https://zenodo.org/badge/821544441.svg)](https://zenodo.org/doi/10.5281/zenodo.12746418)

**Conflux Loader** is a REDCap External Module (EM) that takes a file-centric
approach to injecting user-defined HTML, CSS, and JavaScript source code into
the REDCap frontend.

Developed in response to challenges faced in larger codebases with existing code
injection external modules like
[Shazam](https://github.com/susom/redcap-em-shazam) and [REDCap JavaScript
Injector](https://github.com/grezniczek/redcap_javascript_injector), Conflux
Loader unlocks standard web development toolkits for use in REDCap frontend
development. Conflux Loader "modules" are easily version controlled, and
seamlessly integrate with the modern web development ecosystem and its libraries
and frameworks.

Having been designed as a migration target for complex Shazam-and-JSI
development efforts, Conflux Loader naturally retains a high degree of
compatibility with existing source code injection EMs.

## Documentation

> [!IMPORTANT]
> Please open a [GitHub
> Issue](https://github.com/osu-com-rit/Conflux-Loader/issues) if you feel like
> we've missed a useful piece of information or documentation. All suggestions are welcome!

#### **Administrators**

Do you plan to deploy and maintain a Conflux Loader installation for
your users and developers? This documentation section is for you.

* [Prerequisites and Installation](./docs/PrerequisitesInstallation.md)

* [Reference: Conflux Loader EM Configuration](./docs/InstanceConfiguration.md) (System and Project-level)

* [Locking Conflux Down: Security Concerns](./docs/Security.md)


#### **Developers**

Do you wish to use Conflux Loader in your development workflow? This
documentation section is for you.

* [Quickstart: your first Conflux Loader Module](./docs/Quickstart.md)

* [Reference: The `loader_config.json` Format](./docs/LoaderConfigJsonFormat.md)

* [How Conflux Loader Works](./docs/HowConfluxLoaderWorks.md)

<!-- * **[TODO]** [Migrating from Shazam and JavaScript Injector](./docs/MigrationGuide.md) -->

<!-- * **[TODO]** [Options for deploying Conflux Loader Modules](./docs/DeployingModules.md) -->

## Developer Examples

> [!TIP]
> Other examples may be found in our
> [Conflux-Loader-Examples](https://github.com/osu-com-rit/Conflux-Loader-Examples)
> repository!

Here are some example uses of Conflux Loader:

* [Information
  Browser](https://github.com/osu-com-rit/Conflux-Loader-Examples/tree/main/information_browser) -
  a textual content browser embeddable in a REDCap survey (React SPA)

* [Word
  Cloud](https://github.com/osu-com-rit/Conflux-Loader-Examples/tree/main/word_cloud) -
  a simple interactive field-like element (React, React Three Fiber)

* [3D Ping
  Pong](https://github.com/osu-com-rit/Conflux-Loader-Examples/tree/main/3d_ping_pong) -
  a 3D example embedded and integrated with a REDCap survey (React, React Three
  Fiber, 3D Models)



## Suggestions and Contributions

Please open a new [GitHub
Issue](https://github.com/osu-com-rit/Conflux-Loader/issues) for any
suggestions, questions, or concerns surrounding the Conflux Loader
software. Conflux Loader is still in its infancy and it is certain that later
versions will look and play a bit differently to what we have now.

Please also feel free to submit a [Pull Request
(PR)](https://github.com/osu-com-rit/Conflux-Loader/pulls) if you wish to fix or
extend Conflux Loader. Please ensure changes or additions are documented in the
PR.

For significant change proposals (think: those needing a bump in [Semantic
Versioning major/minor version](https://semver.org/)) it is highly recommended
that a PR is opened first to discuss the changes.


## Acknowledgments and Attributions

*See the `NOTICE` file for more information.*

Conflux Loader's initial design and development was conducted by the Research
Information Technology department of The Ohio State University College of
Medicine.

Conflux Loader was heavily inspired by the "Shazam" (Stanford School of
Medicine) and "Javascript Injector" (Günther Rezniczek) REDCap External
Modules. Conflux Loader's distribution includes a copy of a file sourced from
the Shazam REDCap External Module (see the *"Shazam: Inclusion and License"*
section of this README for more information).

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

## Shazam: Inclusion and License

This repository includes a copy of a file from the [Shazam REDCap External
Module](https://github.com/susom/redcap-em-shazam/), released under the
open-source MIT License with notice *"Copyright (c) 2017 Stanford School of
Medicine"*.

For more information see `shazam/README.md` and `shazam/LICENSE`.
