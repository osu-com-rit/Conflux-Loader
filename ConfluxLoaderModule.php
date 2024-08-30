<?php

namespace OSUCOMRIT\ConfluxLoaderModule;

use \REDCap as REDCap;
use \Stanford\Shazam as Shazam;
use \ExternalModules\ExternalModules as ExternalModules;


class ConfluxLoaderModule extends \ExternalModules\AbstractExternalModule {

    public function __construct() {
        parent::__construct();

        $pid = $this->getProjectId();
        $systemPathPrefix = $this->getSystemSetting('path-prefix');
        error_log("Conflux Loader initialized for pid=$pid"
                  . ($systemPathPrefix ? " using prefix $systemPathPrefix" : ''));

    }

    function getShazamInstanceForProject($projectId) {
        // Shazam prefix specified in system settings, but the version we can
        // figure out for ourselves.
        $shazamPrefix = $this->getSystemSetting('shazam-module-name');
        $shazamVersion = null;

        $enabledModules = ExternalModules::getEnabledModules($projectId);
        foreach ($enabledModules as $modulePrefix => $moduleVersion) {
            if ($modulePrefix === $shazamPrefix) {
                $shazamVersion = $moduleVersion;
                break;
            }
        }

        if (!$shazamVersion) {
            return null;
        }

        $fwInstance = ExternalModules::getFrameworkInstance($shazamPrefix, $shazamVersion);
        return $fwInstance ?? $fwInstance->getModuleInstance();
    }

    function getInjectorScriptForProject($projectId) {

        // Found an enabled Shazam? Serve its shazam.js file.
        // Didn't find Shazam? Serve Conflux Loader's builtin shazam.js.

        $shazam = $this->getShazamInstanceForProject($projectId);
        if ($shazam) {
            return $shazam->getUrl("js/shazam.js");
        } else {
            return $this->getUrl("shazam/shazam.js");
        }
    }

    function getLoaderDirectories() {

        // Conflux Loader can have a system-level prefix specified by an admin,
        // intended to allow the admin to limit code loading to subdirectories
        // of the system prefix.

        $systemPathPrefix = $this->getSystemSetting('path-prefix');

        $sanitizedDirectoryPaths = array();

        $pathEntries = $this->getSubSettings('loader-target-directories');
        foreach ($pathEntries as $entry) {
            $path = $entry['path'];

            // Filter out empty paths. Usually happens when someone clicks '+'
            // in subsettings '+' but doesn't fill out the extra entry.
            if (empty($path)) {
                continue;
            }

            $sanitizedPath = preg_replace('/\.+/', '.', $path);

            if ($systemPathPrefix) {
                // rebase the sanitized path on to the system-level path prefix
                array_push($sanitizedDirectoryPaths, $systemPathPrefix . '/' . $sanitizedPath);
            } else {
                // NOTE: absence of system path prefix is typically a Dev
                // config, but I still think it's fine to prevent '.' and '..'
                // in paths, and always require absolute paths.
                array_push($sanitizedDirectoryPaths, $sanitizedPath);
            }
        }

        return $sanitizedDirectoryPaths;
    }

    function getLoaderConfigs() {

        $loaderConfigs = array();

        $loaderDirectories = $this->getLoaderDirectories();
        foreach ($loaderDirectories as $loaderDirectory) {
            $loaderConfigPath = $loaderDirectory . '/loader_config.json';
            $loaderConfig = json_decode(file_get_contents($loaderConfigPath), true);

            if ($loaderConfig === null && json_last_error() !== JSON_ERROR_NONE) {
                echo '<div style="background-color: #870326; padding-left: 20px;">' . '<hr />'
                    . '<b>Conflux Loader JSON decoding error in ' . $loaderConfigPath .':</b><br />'
                    . '<pre>' . json_last_error_msg() . '</pre>'
                    . 'Please review the loader_config.json file and correct the error.'
                    . '<hr /></div><br />' . "\n";
                // The error above is loud enough that I think it's safe to
                // march on in attempting to load other modules...
                continue;
            }

            // Tagging the directory in the config entry essentially lets us
            // 'merge' loader_config.json entries for easier consumption by the
            // injection routines.
            $loaderConfig['__directory'] = $loaderDirectory;

            array_push($loaderConfigs, $loaderConfig);
        }

        return $loaderConfigs;
    }

    function inject($configEntries, $loaderDirectory, $comparator = null,
                    $type = 'javascript', $tag = 'script', $extensionRegex = '/\.(js)$/') {
        if (!$comparator) {
            $comparator = function($entry) { return true; };
        }

        // Keep track of already embedded JS/CSS to prevent double loading. This
        // would otherwise happen when two instrument configs rely on the same
        // CSS/JS file (e.g. a "common.js" script).
        //
        // NOTE: the same script will be loaded if it's specified across
        // page/instr/field. this is mostly to prevent the common use case of
        // multiple fields using the same script on the same page.

        $loadedFiles = array();

        foreach ($configEntries as $entry) {
            // REDCap's JSMO is super useful for SPAs, so Conflux provides an
            // easy option for loading it.
            //
            // TODO: impl different JSMO loading 'modes' ("raw", "bind") to
            // magic away the `window.JSMO` binding boilerplate
            if ($entry['use_jsmo']) {
                $this->initializeJavascriptModuleObject();
            }

            if (isset($entry[$type])
                && !empty($entry[$type])
                && preg_match($extensionRegex, $entry[$type])
                && !isset($loadedFiles[$entry[$type]])
                && $comparator($entry)) {

                $INLINE = file_get_contents($loaderDirectory . '/' . $entry[$type]);
                echo "<$tag>" . $INLINE . "</$tag>\n";
                $loadedFiles[$entry[$type]] = true;
            }
        }
    }

    function injectForPage($pagePath) {

        // Page-level matcher does two things to match a page:
        // 1. Match current PAGE exactly with "page_path" entry
        // 2. Match current REQUEST_URI with "path_match_regex" entry (optional)
        //    (this is useful for matching a dashboard with `dash_id=X`)

        $matcher = function($entry) use ($pagePath) {
            if (!isset($entry['page_path'])) {
                return false;
            }

            if ($entry['page_path'] !== $pagePath) {
                return false;
            }

            if (isset($entry['path_match_regex'])) {
                if (!preg_match($entry['path_match_regex'], $_SERVER["REQUEST_URI"])) {
                    return false;
                }
            }

            return true;
        };

        $loaderConfigs = $this->getLoaderConfigs();
        foreach ($loaderConfigs as $loaderConfig) {

            // Inject scripts into pages when a path matches
            $this->inject(
                $loaderConfig['pages'],
                $loaderConfig['__directory'],
                $matcher,
            );

            // Inject HTML for pages
            $this->inject(
                $loaderConfig['pages'],
                $loaderConfig['__directory'],
                $matcher,
                'html',
                'section',
                '/\.(html)$/'
            );

            // Inject CSS for pages
            $this->inject(
                $loaderConfig['pages'],
                $loaderConfig['__directory'],
                $matcher,
                'css',
                'style',
                '/\.(css)$/'
            );
        }
    }

    function injectForInstrument($pagePath) {
        // Instruments only exist on survey and data entry pages
        if ($pagePath !== "surveys/index.php"
            && $pagePath !== "DataEntry/index.php") {
            return;
        }

        $proj = new \Project();
        $instrument = isset($proj->forms[$_GET["page"]]) ? $_GET["page"] : null;

        $matcher = function($entry) use ($instrument) {
            return $entry['instrument_name'] === $instrument;
        };

        $loaderConfigs = $this->getLoaderConfigs();
        foreach ($loaderConfigs as $loaderConfig) {

            // Inject scripts when the instrument matches the current page
            $this->inject(
                $loaderConfig['instruments'],
                $loaderConfig['__directory'],
                $matcher
            );

            // Inject HTML for these same instruments
            $this->inject(
                $loaderConfig['instruments'],
                $loaderConfig['__directory'],
                $matcher,
                'html',
                'section',
                '/\.(html)$/'
            );

            // Inject CSS for these same instruments
            $this->inject(
                $loaderConfig['instruments'],
                $loaderConfig['__directory'],
                $matcher,
                'css',
                'style',
                '/\.(css)$/'
            );
        }
    }

    function injectForFields($projectId, $isSurvey = false) {

        // Find and load Shazam EM instance, and derive the shazam.js
        // URL. Reusing Shazam's script should defend us from double loading, in
        // the case that a page has both Shazam and Loader elements.

        $SHAZAM_JS_URL = $this->getInjectorScriptForProject($projectId);
        echo "<script src=\"$SHAZAM_JS_URL\"></script>\n";

        $shazamParams = array();

        $loaderConfigs = $this->getLoaderConfigs();
        foreach ($loaderConfigs as $loaderConfig) {

            $fieldEntries = $loaderConfig['fields'];
            $loaderDirectory = $loaderConfig['__directory'];

            // Build Shazam's params from field config entries
            foreach ($fieldEntries as $fieldEntry) {
                $shazamParamsEntry = array('field_name' => $fieldEntry['field_name']);

                if (isset($fieldEntry['html'])
                    && !empty($fieldEntry['html'])
                    && preg_match('/\.(html)$/', $fieldEntry['html'])) {

                    $shazamParamsEntry['html'] = file_get_contents($loaderDirectory . '/' . $fieldEntry['html']);
                }
                array_push($shazamParams, $shazamParamsEntry);
            }

            // Inject scripts for fields
            $this->inject(
                $fieldEntries,
                $loaderDirectory
            );

            // Inject CSS for fields
            $this->inject(
                $fieldEntries,
                $loaderDirectory,
                null, // no matcher
                'css',
                'style',
                '/\.(css)$/'
            );
        }

        // Misc
        $consoleLog = true;
        $shazam = $this->getShazamInstanceForProject($projectId);
        $displayIcons = $shazam ? $shazam->getProjectSetting("shazam-display-icons") : false;

        // Inject JavaScript in a Shazam-compatible way:
?>
        <script>
            if (typeof Shazam === "undefined") {
                const msg = "\nConflux Loader error: Shazam JS object was not found."
                          + "\n\nPlease notify the REDCap project administrators.\n\n";
                console.error(msg);
                alert(msg);
            } else {
                $(document).ready(function () {
                    Shazam.params       = <?php print json_encode($shazamParams); ?>;
                    Shazam.isDev        = <?php echo $consoleLog ? 1 : 0; ?>;
                    Shazam.displayIcons = <?php print json_encode($displayIcons); ?>;
                    Shazam.isSurvey     = <?php print json_encode($isSurvey); ?>;
                    setTimeout(function(){ Shazam.Transform(); }, 1);
                });
            }
        </script>
        <style>
            #form {opacity: 0;}
            .shazam-vanished { z-index: -9999; }
        </style>
<?php

    }

    function redcap_survey_page_top($projectId, $record, $instrument) {
        $this->injectForFields($projectId, true);
    }

    function redcap_data_entry_form_top($projectId, $record, $instrument) {
        $this->injectForFields($projectId);
    }

    function redcap_every_page_top($projectId) {
        // NOTE: this Shazam check occurs in survey and data entry pages!
        $shazam = $this->getShazamInstanceForProject($projectId);
        if ($shazam) {
?>
            <script>console.info("Conflux Loader: using Shazam's shazam.js");</script>
<?php
        } else {
?>
            <script>console.info("Conflux Loader: using inbuilt shazam.js");</script>
<?php
        }

        $system_injection_allowed = $this->getSystemSetting('allow-system-injection');
        $login_injection_allowed  = $this->getSystemSetting('allow-login-injection');

        if (!$projectId && !$system_injection_allowed) {
            // System pages - disallow hooks here unless admin enables.
            return;
        }

        $pagePath = defined("PAGE") ? PAGE : null;
        if (!$pagePath && !$login_injection_allowed) {
            // Login page - disallow hooks here unless admin enables.
            return;
        }

        $this->injectForPage($pagePath);
        $this->injectForInstrument($pagePath);
    }
}

?>
