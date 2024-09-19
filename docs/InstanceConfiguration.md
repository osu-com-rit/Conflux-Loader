# Reference: Conflux Loader EM Configuration *(Conflux Loader)*

## System-level configuration

### `path-prefix` **(IMPORTANT)** *[default: none]*

> Limit Conflux Module loading to directory

This is primarily intended for production systems:

> *Setting this value forces Conflux Loader modules to be loaded from
> subdirectories of this directory. Unspecified, a user can load code from any
> location in the instance's filesystem. Setting this value will alter the way
> that configured project-level paths are interpreted by the EM.*

**It is *very* important on production systems that this setting is configured
appropriately.**

Please follow the [Prerequisites and
Installation](./PrerequisitesInstallation.md) guide to ensure that you have a
valid value for this setting (determined by the path that you wish to limit
Conflux Loader Modules being loaded from), and that you understand why this
setting is important.

Incorrect configuration of this setting may allow arbitrary content injection
into user-facing pages. Conflux Loader does have file-extension-based mechanisms
in place to make accidental non-HTML/CSS/JS injections difficult, but there is
still a risk.

### `allow-login-injection` **(IMPORTANT)** *[default: off]*

> Allow Conflux Loader modules to inject code into the REDCap login page?

Disabled by default. Not recommended. Enabling this setting permits Conflux
Loader modules to modify the appearance and behavior of the REDCap login page.

It is not recommended to set this unless you have a very clear need for it.

### `allow-system-injection` **(IMPORTANT)** *[default: off]*

> Allow Conflux Loader modules to inject code into the REDCap system pages?

Disabled by default. Not recommended. Enabling this setting permits Conflux
Loader modules to modify the appearance and behavior of the REDCap system pages,
such as the control center.

Just like login injection, it is not recommended to set this unless you have a
very clear need for it.

### `shazam-module-name` *(optional)* *[default: none]*

> Prefix of the Shazam External Module

Used to configure reuse of the Shazam external module's injection facilities, in
case a REDCap instance has both Shazam and Conflux Loader installed. This
setting is *optional*, but, when appropriate, it is recommended.

## Project-level configuration

### `loader-target-directories`

> List of Conflux Loader modules to load

Entries here correspond to Conflux Loader Modules that should be loaded for this
particular project:

> Entries specified here should correspond to directories that are valid Conflux
> Loader modules. Entries will respect any configured system-level settings that
> limit where Conflux Loader modules are loaded from.
