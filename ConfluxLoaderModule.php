<?php

namespace OSUCOMRIT\ConfluxLoaderModule;

use \REDCap as REDCap;
use \ExternalModules\ExternalModules as ExternalModules;


class ConfluxLoaderModule extends \ExternalModules\AbstractExternalModule {

    // TODO: eventually configify FILE_REPOSITORY_PREFIX
    const FILE_REPOSITORY_PREFIX = 'conflux_loader_modules';

    function validInjectionTargets() {
        return array('fields', 'instruments', 'pages');
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
            $isFileRepoPath = $entry['is-filerepo-path'];

            // Filter out empty paths. Usually happens when someone clicks '+'
            // in subsettings '+' but doesn't fill out the extra entry.
            if (empty($path)) {
                continue;
            }

            $sanitizedPath = preg_replace('/\.+/', '.', $path);

            if ($isFileRepoPath) {
                // File Repository paths are their own thing
                array_push($sanitizedDirectoryPaths, '$ProjectFileRepository' . '/' . self::FILE_REPOSITORY_PREFIX . '/' . $sanitizedPath);
            } else if ($systemPathPrefix) {
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

    function getFileRepoDocsByPath($docName = null) {
        // TODO: eventually configify $escapedMaxCteRecursionDepth on
        // per-project basis.
        //
        // Note that the mysqli prepared statements don't like SET_VAR...
        //
        // $escapedMaxCteRecursionDepth = '10';
        $pid = $this->getProjectId();

        // SQL query resolves File Repo filepaths for all files under the
        // `conflux_loader_modules` root folder
        $sql = "
            with recursive
            parent_folder_cte as
            (
                select name path, folder_id
                from redcap_docs_folders
                where name = ?
                    and project_id = ?
                    and parent_folder_id is null

                union all

                select concat(parent.path, '/', folder.name) path, folder.folder_id
                from redcap_docs_folders folder
                    inner join parent_folder_cte parent on folder.parent_folder_id = parent.folder_id
                where project_id = ?
            )
            select /*+ SET_VAR(cte_max_recursion_depth = 10) */
                concat(path, '/', doc_name) as file_path, doc_id, folder_id
            from parent_folder_cte
                inner join redcap_docs_folders_files using (folder_id)
                left join redcap_docs_to_edocs using (docs_id)
                inner join redcap_edocs_metadata using (doc_id)
            where delete_date is null
        ";
        $query = $this->createQuery();
        $query->add($sql, [self::FILE_REPOSITORY_PREFIX, $pid, $pid]);
        if ($docName !== null) {
            $query->add("and doc_name = ?", [$docName]);
        }
        $query->add(";");
        $result = $query->execute();

        $docIdByRepoPath = array();
        while($row = $result->fetch_assoc()){
            $docIdByRepoPath['$ProjectFileRepository/' . $row['file_path']] = $row;
        }
        return $docIdByRepoPath;
    }

    function isFileRepositoryPath($path) {
        return strncmp($path, '$ProjectFileRepository/', strlen('$ProjectFileRepository/')) === 0;
    }

    function getLoaderConfigs($includeMetadata = true) {
        $loaderConfigs = array();

        $loaderDirectories = $this->getLoaderDirectories();
        $docsByPath = $this->getFileRepoDocsByPath();

        foreach ($loaderDirectories as $loaderDirectory) {
            $basename = basename($loaderDirectory);
            if ($this->isFileRepositoryPath($loaderDirectory)) {
                // Loader Config paths in the File Repo are read via the
                // API. These paths all have a particular prefix:
                //     $ProjectFileRepository/
                //
                $configDoc = $docsByPath[
                    '$ProjectFileRepository' . '/' . self::FILE_REPOSITORY_PREFIX . '/'
                        . $basename . '/' . 'loader_config.json'
                ];
                $configDocId = $configDoc['doc_id'];

                list ($mimeType, $docName, $fileContents) = REDCap::getFile($configDocId);
                if ($mimeType !== 'application/json') {
                    continue;
                }

                $loaderConfig = json_decode($fileContents, true);
                if ($includeMetadata) {
                    $loaderConfig['__file_repository'] = $configDoc;
                }

            } else {
                // Non-File Repo loading is simple: load from disk.
                $loaderConfigPath = $loaderDirectory . '/loader_config.json';
                $loaderConfig = json_decode(file_get_contents($loaderConfigPath), true);
            }

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

            if ($includeMetadata) {
                // Tagging the directory (as '__directory') in the configs and their
                // entries lets us 'merge' loader_config.json entries for easier
                // consumption by the injection routines. Good for debugging too!
                $loaderConfig['__loader_module'] = $basename;
                $loaderConfig['__directory'] = $loaderDirectory;
                foreach($this->validInjectionTargets() as $injTarg) {
                    if (isset($loaderConfig[$injTarg])) {
                        foreach($loaderConfig[$injTarg] as &$injTargEntry) {
                            $injTargEntry['__loader_module'] = $basename;
                            $injTargEntry['__directory'] = $loaderDirectory;
                        }
                    }
                }
            }

            array_push($loaderConfigs, $loaderConfig);
        }

        return $loaderConfigs;
    }

    function tryInjectJSMO($configEntries, $loadedFiles) {

        // REDCap's JSMO is super useful for SPAs, so Conflux provides an
        // easy option for loading and binding it.
        //
        // LOAD+BIND: "use_jsmo": { "bind_as": "JSMO" }
        // LOAD: "use_jsmo": true,
        //
        // NOTE: a JSMO dummy file ('__jsmo') is included in $loadedFiles to
        // protect against multiple JSMO loads per page (extra binds still work)

        foreach ($configEntries as $configEntry) {
            if (!isset($configEntry['use_jsmo'])) {
                continue;
            }

            $useJsmo = $configEntry['use_jsmo'];
            $jsmoName = $this->framework->getJavascriptModuleObjectName();

            if (!isset($loadedFiles['__jsmo'])) {
                echo "<script>console.info('Conflux Loader: JSMO loaded');</script>";
                $this->initializeJavascriptModuleObject();
                $loadedFiles['__jsmo'] = true;
            }

            if (isset($useJsmo['bind_as'])) {
                $jsmoBind = $useJsmo['bind_as'];
                echo "<script>console.info('Conflux Loader: new JSMO bind: ${jsmoBind}');</script>";
?>
                <script>const <?= $jsmoBind ?> = <?= $jsmoName ?>;</script>
<?php
            }
        }
    }

    function entryHasSetBool($entry, $key) {
        return (bool)(!empty($entry[$key]) && $entry[$key]);
    }

    function disabledForSurvey($configEntry) {
        return $this->entryHasSetBool($configEntry, 'disable_for_survey');
    }

    function disabledForDataEntry($configEntry) {
        return $this->entryHasSetBool($configEntry, 'disable_for_data_entry');
    }

    function inject($configEntries, $loadedFiles, $comparator = null,
                    $type = 'javascript', $tag = 'script', $extensionRegex = '/\.(js)$/') {
        if (!$comparator) {
            $comparator = function($entry) { return true; };
        }

        // $loadedFiles keeps track of already embedded JS/CSS to prevent double
        // loading. This would otherwise happen when two instrument configs rely
        // on the same CSS/JS file (e.g. a "common.js" script).
        //
        // NOTE: the same script will be loaded if it's specified across
        // page/instr/field. this is mostly to prevent the common use case of
        // multiple fields using the same script on the same page.

        $docsByPath = $this->getFileRepoDocsByPath();

        foreach ($configEntries as $entry) {
            if (isset($entry[$type])
                && !empty($entry[$type])
                && preg_match($extensionRegex, $entry[$type])
                && !isset($loadedFiles[$entry[$type]])
                && $comparator($entry)) {

                $INLINE = '';
                if ($this->isFileRepositoryPath($entry['__directory'])) {
                    // Read from File Repository
                    $fileRepoDoc = $docsByPath[$entry['__directory'] . '/' . $entry[$type]];
                    list ($mimeType, $docName, $fileContents) = REDCap::getFile($fileRepoDoc['doc_id']);
                    $INLINE = $fileContents;
                } else {
                    // Read from disk
                    $loaderDirectory = $entry['__directory'];
                    $INLINE = file_get_contents($loaderDirectory . '/' . $entry[$type]);
                }

                echo "<$tag>" . $INLINE . "</$tag>\n";
                $loadedFiles[$entry[$type]] = true;
            }
        }
    }

    function injectForPage($pagePath, $isSurvey, $isDataEntry) {

        // Page-level matcher does three things to match a page:
        // 1. Check if this injection is disabled on survey/DataEntry
        // 2. Match current PAGE exactly with "page_path" entry
        // 3. Match current REQUEST_URI with "path_match_regex" entry (optional)
        //    (this is useful for matching a dashboard with `dash_id=X`)

        $matcher = function($entry) use ($pagePath, $isSurvey, $isDataEntry) {
            if ($isSurvey && $this->disabledForSurvey($entry) ||
                $isDataEntry && $this->disabledForDataEntry($entry)) {
                return false;
            }

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

        $loadedFiles = array();

        $loaderConfigs = $this->getLoaderConfigs();
        foreach ($loaderConfigs as $loaderConfig) {

            $this->tryInjectJSMO(
                $loaderConfig['pages'],
                $loadedFiles
            );

            // Inject scripts into pages when a path matches
            $this->inject(
                $loaderConfig['pages'],
                $loadedFiles,
                $matcher,
            );

            // Inject HTML for pages
            $this->inject(
                $loaderConfig['pages'],
                $loadedFiles,
                $matcher,
                'html',
                'section',
                '/\.(html|htm)$/'
            );

            // Inject CSS for pages
            $this->inject(
                $loaderConfig['pages'],
                $loadedFiles,
                $matcher,
                'css',
                'style',
                '/\.(css)$/'
            );
        }
    }

    function injectForInstrument($instrument, $isSurvey) {
        $matcher = function($entry) use ($instrument, $isSurvey) {
            // Instruments only appear on survey xor data entry pages.
            $isDataEntry = !$isSurvey;

            // Injection disabled for instrument on survey ior data entry mode.
            if ($isSurvey && $this->disabledForSurvey($entry) ||
                $isDataEntry && $this->disabledForDataEntry($entry)) {
                return false;
            }

            return $entry['instrument_name'] === $instrument;
        };

        $loadedFiles = array();

        $loaderConfigs = $this->getLoaderConfigs();
        foreach ($loaderConfigs as $loaderConfig) {

            $this->tryInjectJSMO(
                $loaderConfig['instruments'],
                $loadedFiles
            );

            // Inject scripts when the instrument matches the current page
            $this->inject(
                $loaderConfig['instruments'],
                $loadedFiles,
                $matcher
            );

            // Inject HTML for these same instruments
            $this->inject(
                $loaderConfig['instruments'],
                $loadedFiles,
                $matcher,
                'html',
                'section',
                '/\.(html|htm)$/'
            );

            // Inject CSS for these same instruments
            $this->inject(
                $loaderConfig['instruments'],
                $loadedFiles,
                $matcher,
                'css',
                'style',
                '/\.(css)$/'
            );
        }
    }

    function injectForFields($projectId, $instrument, $isSurvey) {
        // Fields only appear on survey xor data entry pages.
        $isDataEntry = !$isSurvey;

        // Lookup table: field is in this instrument?
        // TODO: Feature idea: regex field name matching
        $instrumentHasField = array();
        foreach (REDCap::getFieldNames(array($instrument)) as $field) {
            $instrumentHasField[$field] = true;
        }

        // Find and load Shazam's injector.
        $SHAZAM_JS_URL = $this->getInjectorScriptForProject($projectId);
        echo "<script src=\"$SHAZAM_JS_URL\"></script>\n";

        // Build our pile of Shazam injection params, and inject any
        // field-associated JavaScript and CSS.
        $shazamParams = array();
        $loadedFiles = array();

        // Go through all fields of all configs, accumulating relevant fields
        // (i.e. occurs in the instrument) to be injected by CFL (for JS and
        // CSS) and Shazam (for HTML)
        $relevantFieldEntries = array();
        $loaderConfigs = $this->getLoaderConfigs();

        $docsByPath = $this->getFileRepoDocsByPath();

        foreach ($loaderConfigs as $loaderConfig) {

            // Build Shazam's params from field config entries
            $configFieldEntries = $loaderConfig['fields'];
            foreach ($configFieldEntries as $configFieldEntry) {
                $fieldName = $configFieldEntry['field_name'];

                // Field isn't relevant to this instrument, ignore.
                if (!$instrumentHasField[$fieldName]) {
                    continue;
                }

                // Injection disabled for field on survey ior data entry mode, ignore.
                if ($isSurvey && $this->disabledForSurvey($configFieldEntry) ||
                    $isDataEntry && $this->disabledForDataEntry($configFieldEntry)) {
                    continue;
                }

                $shazamParamsEntry = array('field_name' => $fieldName);

                if (isset($configFieldEntry['html'])
                    && !empty($configFieldEntry['html'])
                    && preg_match('/\.(html|htm)$/', $configFieldEntry['html'])) {

                    if ($this->isFileRepositoryPath($configFieldEntry['__directory'])) {
                        $fileRepoDoc = $docsByPath[$configFieldEntry['__directory'] . '/' . $configFieldEntry['html']];
                        list ($mimeType, $docName, $fileContents) = REDCap::getFile($fileRepoDoc['doc_id']);
                        $shazamParamsEntry['html'] = $fileContents;
                    } else {
                        $shazamParamsEntry['html'] = file_get_contents(
                            $configFieldEntry['__directory'] . '/' . $configFieldEntry['html']
                        );
                    }
                }

                array_push($relevantFieldEntries, $configFieldEntry);
                array_push($shazamParams, $shazamParamsEntry);
            }
        }

        // Inject JSMO if configured
        $this->tryInjectJSMO(
            $relevantFieldEntries,
            $loadedFiles
        );

        // Inject scripts for fields
        $this->inject(
            $relevantFieldEntries,
            $loadedFiles
        );

        // Inject CSS for fields
        $this->inject(
            $relevantFieldEntries,
            $loadedFiles,
            null, // no matcher
            'css',
            'style',
            '/\.(css)$/'
        );

        // Inject HTML via the Shazam injector:
        $consoleLog = true;
        $shazam = $this->getShazamInstanceForProject($projectId);
        $displayIcons = $shazam ? $shazam->getProjectSetting("shazam-display-icons") : false;
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
        $isSurvey = true;
        $this->injectForInstrument($instrument, $isSurvey);
        $this->injectForFields($projectId, $instrument, $isSurvey);
    }

    function redcap_data_entry_form_top($projectId, $record, $instrument) {
        $this->injectForInstrument($instrument, false);
        $this->injectForFields($projectId, $instrument, false);
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

        $isSurvey = $pagePath === "surveys/index.php";
        $isDataEntry = $pagePath === "DataEntry/index.php";

        $this->injectForPage($pagePath, $isSurvey, $isDataEntry);
    }
}

?>
