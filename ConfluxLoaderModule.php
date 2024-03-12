<?php

namespace OSUWMC\ConfluxLoaderModule;

use \REDCap as REDCap;
use \Stanford\Shazam as Shazam;
use \ExternalModules\ExternalModules as ExternalModules;


class ConfluxLoaderModule extends \ExternalModules\AbstractExternalModule {

    function getShazamInstanceForProject($projectId) {
        // Shazam prefix specified in system settings, but the version we can
        // figure out for ourselves.
        $shazamPrefix = $this->getSystemSetting("shazam-module-name");
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

    function getLoaderDirectory() {
        // current EM dir is redcap/modules/<EM>, so the root is two levels up
        $REDCAP_ROOT = dirname(dirname(__DIR__));
        $loaderDirectory = $REDCAP_ROOT . '/' . $this->getProjectSetting("loader-target-directory");
        return $loaderDirectory;
    }

    function getLoaderConfig($key = null) {
        $loaderDirectory = $this->getLoaderDirectory();
        $loaderConfigPath = $loaderDirectory . '/loader_config.json';
        // TODO: warn the user of a broken JSON file
        $loaderConfig = json_decode(file_get_contents($loaderConfigPath), true);
        return $key ? $loaderConfig[$key] : $loaderConfig;
    }

    function inject($configEntries, $comparator = null,
                    $type = 'javascript', $tag = 'script', $extensionRegex = '/\.(js)$/') {
        if (!$comparator) {
            $comparator = function($entry) { return true; };
        }

        $loaderDirectory = $this->getLoaderDirectory();
        $loaderConfig = $this->getLoaderConfig();

        // Keep track of already embedded JS/CSS to prevent double loading. This
        // would otherwise happen when two instrument configs rely on the same
        // CSS/JS file (e.g. a "common.js" script).
        //
        // NOTE: the same script will be loaded if it's specified across
        // page/instr/field. this is mostly to prevent the common use case of
        // multiple fields using the same script on the same page.

        $loadedFiles = array();

        foreach ($configEntries as $entry) {
            // JSMO is a thing that JSI documents -- I think it provides a
            // dynamic content hook based on language selection (and other
            // reload triggers). Not sure if we use it, but it's available.
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
        // Inject scripts into pages when a path matches
        $this->inject(
            $this->getLoaderConfig('pages'),
            function($entry) use ($pagePath) {
                return $entry['page_path'] === $pagePath;
            },
        );
    }

    function injectForInstrument($pagePath) {
        // Instruments only exist on survey and data entry pages
        if ($pagePath !== "surveys/index.php"
            && $pagePath !== "DataEntry/index.php") {
            return;
        }

        $proj = new \Project();
        $instrument = isset($proj->forms[$_GET["page"]]) ? $_GET["page"] : null;

        // Inject scripts when the instrument matches the current page
        $this->inject(
            $this->getLoaderConfig('instruments'),
            function($entry) use ($instrument) {
                return $entry['instrument_name'] === $instrument;
            }
        );
    }

    function injectFromFolder($projectId, $isSurvey = false) {

        // Find and load Shazam EM instance, and derive the shazam.js
        // URL. Reusing Shazam's script should defend us from double loading, in
        // the case that a page has both Shazam and Loader elements.

        $shazam = $this->getShazamInstanceForProject($projectId);
        $SHAZAM_JS_URL = $shazam->getUrl("js/shazam.js");
        echo "<script src=\"$SHAZAM_JS_URL\"></script>\n";

        $loaderDirectory = $this->getLoaderDirectory();
        $fieldEntries = $this->getLoaderConfig('fields');

        $shazamParams = array();
        foreach ($fieldEntries as $fieldEntry) {
            $shazamParamsEntry = array('field_name' => $fieldEntry['field_name']);

            if (isset($fieldEntry['html'])
                && !empty($fieldEntry['html'])
                && preg_match('/\.(html)$/', $fieldEntry['html'])) {

                $shazamParamsEntry['html'] = file_get_contents($loaderDirectory . '/' . $fieldEntry['html']);
            }
            $shazamParams[] = $shazamParamsEntry;
        }

        $this->inject(
            $this->getLoaderConfig('fields')
        );

        $this->inject(
            $this->getLoaderConfig('fields'),
            type: 'css',
            tag: 'style',
            extensionRegex: '/\.(css)$/'
        );

        // Misc
        $consoleLog = true;

        // Inject JavaScript in a Shazam-compatible way:
?>
        <script>
            if (typeof Shazam === "undefined") {
                const msg = "\nConflux Loader error: Shazam JS object was not found."
                          + "\n\nPlease notify a REDCap administrator.\n\n";
                console.error(msg);
                alert(msg);
            } else {
                $(document).ready(function () {
                    Shazam.params       = <?php print json_encode($shazamParams); ?>;
                    Shazam.isDev        = <?php echo $consoleLog ? 1 : 0; ?>;
                    Shazam.displayIcons = <?php print json_encode($shazam->getProjectSetting("shazam-display-icons")); ?>;
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
        $this->injectFromFolder($projectId, true);
    }

    function redcap_data_entry_form_top($projectId, $record, $instrument) {
        $this->injectFromFolder($projectId);
    }

    function redcap_every_page_top($projectId) {
        $shazam = $this->getShazamInstanceForProject($projectId);
        if (!$shazam) {
?>
            <script>
                const msg = "\nConflux Loader error: Shazam prefix was not configured correctly."
                    + "\n\nPlease notify a REDCap administrator.\n\n";
                console.error(msg);
                alert(msg);
            </script>
<?php
            return;
        }

        if (!$projectId) {
            // System pages - should we allow hooks here?
            return;
        }

        $pagePath = defined("PAGE") ? PAGE : null;
        if (!$pagePath) {
            // Login screen - should we allow hook here?
            return;
        }

        $this->injectForPage($pagePath);
        $this->injectForInstrument($pagePath);
    }
}

?>
