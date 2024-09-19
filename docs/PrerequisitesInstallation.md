# Prerequisites and Installation *(Conflux Loader)*

## Prerequisites

### REDCap Versioning

Conflux Loader supports REDCap versions `12.0.4` and above (EM Framework
Version 9).

Like most External Modules, administrator-level access is needed to install
Conflux Loader on a REDCap instance.


### Instance Environment

Conflux Loader loads Conflux Loader modules from a filesystem accessible to the
REDCap instance, meaning it needs an appropriate place to read from with
appropriate filesystem-level permissions.

These permissions should generally mirror the permissions granted to REDCap
External Modules; on UNIX-like systems (Linux), these are directory and
file-level read permissions (`u+r,g+r`) for the webserver access group (usually
`www-data`).

System administrators should decide on a dedicated path where loadable Conflux
Loader modules reside *before* setting up Conflux Loader.

**It is recommended to make a `conflux_loader_modules` directory directly under
the REDCap www root** (example: `www/conflux_loader_modules`).

> [!WARNING]
> **NOT RECOMMENDED**: `modules/conflux_loader_modules` (EM folder)
> piggybacking. This can be useful when EMs reside in a mount and getting a
> new mount is a headache, but avoid doing this in production.

> [!CAUTION]
> **HIGHLY NOT RECOMMENDED**: `edocs/conflux_loader_modules` piggybacking. This
> is only for when when new mounts cause headaches, and a writeable mount is
> needed for developer self-service solutions (none of which exist yet). Do not
> do this in production.


**Make a note of the full path of this directory.** It will needed during
installation (example: `/var/www/html/conflux_loader_modules`, *no trailing slash!*).


## Installation

### Via the *REDCap Repository of External Modules*

*This will be updated when Conflux Loader is available via the REDCap Repo...*


### From Source (GitHub)

Conflux Loader is a standard REDCap External Module, and can be installed by
cloning this repository into a subdirectory of the REDCap instance's `modules`
folder:

```
git clone https://github.com/osu-com-rit/Conflux-Loader.git conflux_loader_v1.1.3
```

Ensure that the cloned directory has permissions in line with other installed
EMs (things like `www-data` group ownership).


### Enabling Conflux Loader

The Conflux Loader EM should now be enabled at the system-level via the External
Modules section of the Control Center.

It is important that production installations have the admin come pre-prepared
(see [Prerequisites](./PrerequsitesInstallation.md#Prerequisites)) with a path
to load Conflux Loader modules from (setting labeled ***"Limit Conflux Module
loading to directory"***). While it's unlikely that not specifying this path
presents any immediate risks or problems, it should not be forgotten.

> [!IMPORTANT]
> Optionally, if the REDCap instance already has Shazam installed, Conflux
> Loader has a system setting for specifying the Shazam EM directory prefix
> (typically `shazam` or `redcap_em_shazam`). This ensures that Shazam and
> Conflux Loader play extra nicely when a project has them both enabled.

It's important that the system administrator has a test case Conflux Loader
module, and a project, or user, who can verify that Conflux Loader modules are
loading correctly and as intended once Conflux Loader is enabled.

> [!WARNING]
> Issues at this stage usually relate to permissions and paths, so it's worth
> double checking these first.
>
> The path limit system setting might be specified (with a trailing slash) as
> `/var/www/html/edocs/conflux_loader_modules/`, but the project user specified
> `Example`. Conflux Loader's path joining results in the incorrect full path of
> `/var/www/html/edocs/conflux_loader_modules//Example` (note the double
> slash). Correcting this means removing trailing slash from the path limit
> system setting.


## Using Conflux Loader in Projects

Once installed, the Conflux Loader EM can then be enabled within projects.

Project-level configuration of Conflux Loader is simple: specify the directories
of the Conflux Loader modules that are intended for use with the REDCap project:

![Image of REDCap project specifying a Conflux Loader
module to load](./images/prerequisitesinstallation_projectconfig.png)

