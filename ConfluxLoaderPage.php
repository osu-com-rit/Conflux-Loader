<?php
function collect($key, $entries) {
    return array_map(function($o) use ($key) { return $o[$key]; }, $entries);
}

function annotateFieldWithInstrument($datadict, $fields) {
    return array_map(
        function ($field) use ($datadict) { return $field.' ('.$datadict[$field]['form_name'].')'; },
        $fields
    );
}

$datadict = \REDCap::getDataDictionary($module->getProjectId(), 'array');
// print_r($datadict);
$configs = $module->getLoaderConfigs();
$verboseMode = !empty($_GET['verbose']);
$abridgedConfigs = $module->getLoaderConfigs($verboseMode);
$enabledModules = collect('__loader_module', $configs);
?>

<style>
.conflux_config { font-size: 11px; display: none; }
label + .toggle:checked + .conflux_config { display: block; }
</style>

<hr />
<h3>
  <i class="fas fa-gear" style="text-align: center;" aria-hidden="true"></i>
  <b>&nbsp;Conflux Loader Dashboard</b>
</h3>
<hr />

<p>
  <b>Conflux Loader Prefix:</b> <code><?= $module->PREFIX ?></code> (<code><?= $module->VERSION ?></code>)
  <br />
  <b>Enabled Loader Modules:</b> <code><?= implode(', ', $enabledModules) ?></code>
</p>

<br />
<hr />
<h4><b>Active <code>loader_config.json</code> configurations <?= $verboseMode ? '(verbose)':''?></b></h4>
<hr />

<?
for($i = 0; $i < count($configs); $i++) {
    $config = $configs[$i];
    $fields = empty($config['fields']) ? [] : collect('field_name', $config['fields']);
    $annotatedFields = annotateFieldWithInstrument($datadict, $fields);
    $instruments = empty($config['instruments']) ? [] : collect('instrument_name', $config['instruments']);
    $pagePaths = empty($config['pages']) ? [] : collect('page_path', $config['pages']);
    $fileRepoInfo = $config['__file_repository'];
    $pid = $module->getProjectId();
    $fileRepoLink = "../index.php?pid=".$pid."&route=FileRepositoryController:index&folder_id=".$fileRepoInfo['folder_id'];
?>
  <p>
    <b>Module: </b><code><?= $config['__loader_module'] ?></code>
    <br />
    <b>Description:</b> <i>&quot;<?= $config['description'] ?>&quot;</i>
    <br />
<? if (!empty($fileRepoInfo)) { ?>
    <b>Folder (File Repository):</b> <a href="<?= $fileRepoLink ?>"><code><?= $config['__directory'] ?></code></a>
    <br />
 <? } else { ?>
    <b>Directory: </b><code><?= $config['__directory'] ?></code>
    <br />
<? }?>
<? if (!empty($fields)) { ?>
    Fields targeted: <code><?= implode(', ', $annotatedFields) ?></code>
    <br />
<? } ?>
<? if (!empty($instruments)) { ?>
    Instruments targeted: <code><?= implode(', ', $instruments) ?></code>
    <br />
<? } ?>
<? if (!empty($pagePaths)) { ?>
    Pages targeted: <code><?= implode(', ', $pagePaths) ?></code>
    <br />
<? } ?>
  </p>
  <label for="toggle_<?= $i ?>"><b><u>Show Config</b></u></label>
  <input class="toggle" type="checkbox" id="toggle_<?= $i ?>" />
  <pre class="conflux_config">
<?= json_encode($abridgedConfigs[$i], JSON_PRETTY_PRINT) ?>
  </pre>
  <hr />
<? } ?>
