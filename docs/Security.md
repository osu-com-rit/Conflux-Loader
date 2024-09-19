# Locking Conflux Down: Security Concerns *(Conflux Loader)*

Conflux Loader is, like [Shazam](https://github.com/susom/redcap-em-shazam) and
[REDCap JavaScript
Injector](https://github.com/grezniczek/redcap_javascript_injector), a REDCap
External Module that concerns itself with injecting source code that alters the
behavior and appearance of REDCap's frontend. Conflux Loader allows scopes of
injection that can be as narrow as a single field on a survey, or as wide as to
affect *every single page* on the REDCap instance; and, this is almost-entirely
dictated by how the developer has written the Conflux Loader Module's
configuration.

Like many highly flexible tools, Conflux Loader doesn't impose many rules on how
developers may use the tool. This is in part because such flexibility is needed
for interfacing with developer tooling. However, this flexibility comes with the
cost of trusting those using the tool, especially on production instances.

Some guardrails have been implemented to make it hard for Conflux Loader to be
used in an accidentally disastrous way:

* **System-level controls for limiting where injected source code lives.**  
*(see [Reference: Conflux Loader EM Configuration](./InstanceConfiguration.md#path-prefix-important-default-none))*
* **System-level, off-by-default login and system page injection controls.**  
*(see [Reference: Conflux Loader EM Configuration](./InstanceConfiguration.md#allow-login-injection-important-default-off))*
* **Inlined source code files limited to file extensions:** `*.html|*.css|*.js`
* **No opinions on code *sourcing.***  
Conflux Loader does not offer any features for moving code on to a REDCap instance!

However, none of this will protect you from truly poor use of Conflux Loader,
which can leave projects inaccessibly broken for researchers and admins, causing
a lot of workflow disruption (at the very least!).

So, the first rule of system administration of a REDCap instance using Conflux
Loader is **make sure you trust your developers**. There are two separate tasks,
*development* and *deployment*, that each have their own considerations.

## Trusting Developers to Develop

Developers should generally be trusted in their primary function, and that is to
develop software that can be safely deployed. As a system administrator in
charge of deploying developed software, you should push for development teams to
employ good practices when it comes to software testing and quality assurance.

If, for some reason, your developers fail to pass this trust hurdle, then you
may want to consider allowing them to use the
[Shazam](https://github.com/susom/redcap-em-shazam) REDCap External Module as an
alternative to Conflux Loader.

Shazam is less flexible, but still very powerful for customization, and is
strongly focused on field-level logic and styling. Poor use of Shazam can still
break survey presentation, but it can't break any administrative
pages. Furthermore, the total lack of filesystem-based code loading in Shazam
means that there is effectively zero chance that Shazam could be directed to
inject something it shouldn't be injecting.

> [!CAUTION] 
> The [REDCap JavaScript
> Injector](https://github.com/grezniczek/redcap_javascript_injector) is another
> option for non-filesystem based code injection, but JSI's flexibility is on
> par with Conflux Loader in terms of possibility for widespread / cross-project
> / admin / project breakage. If you don't trust them using Conflux Loader, you
> probably shouldn't be trusting them with JSI either!


## Trusting Developers to Deploy 

This is where the real system admin questions should appear. Developers can be
trusted to develop code...

...but should they be allowed to deploy to a remote REDCap instance?

...a remote *testing* REDCap instance? 

...a remote ***production*** REDCap instance?

It is recommended that system administrators define their own processes for
deploying and updating Conflux Loader Modules, and communicate with developers
to (co)design processes to minimize the chance of production breakage from
deployment failures.

As of now, for most system admins, manual Conflux Loader Module deployment will
look similar to the manual installation path for REDCap External Modules. We
recommend, for starters, a basic process that looks something like:

1. Developer releases a new, *blessed* (tested) version of a Conflux Loader
   module (*"the module"*).
2. Developer notifies system administrator of availability of a new release of
   the module.
3. Developer provides a ZIP/TAR archive of the new module release to the system administrator.
4. System administrator adds the new version of the module to the instance's filesystem.
5. Developer switches the project from using the old module release to using the
   new module release.
6. Developer validates the new module's functionality and notifies the system
   administrator of the validation result.
7. System administrator removes the old version of the module (eventually).

> [!NOTE]
> Eventually, the Conflux *'project'* will provide additional 'Sourcer' External
> Modules that operate as a self-service for developers to deploy code to remote
> REDCap instances, usually by uploading a ZIP or pointing something at a GitHub
> repository. Rest assured, these will be entirely optional and entirely
> separate to Conflux Loader.


## Trusting *Yourself* to Deploy

Sure, this isn't really a developer trust issue, but it can be useful to have an
understanding of the `loader_config.json` Conflux Loader Module configuration
format (see [Reference: Conflux Loader EM
Configuration](./InstanceConfiguration.md)). It also helps to know how Conflux
Loader works (see [How Conflux Loader Works](./HowConfluxLoaderWorks.md)).

