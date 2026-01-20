<?php
use tonisormisson\ls\structureimex\StructureImEx;

/** @var StructureImEx $exportPlugin */
    $pluginDir = dirname(dirname(__DIR__)); // Go up two levels from the views directory
    // LS core installs way old version hers so lets use our vendor version here
    require $pluginDir.'/vendor/sebastian/version/src/Version.php';
    $version = new \SebastianBergmann\Version('1.0.0', $pluginDir);
?>
<div class="row">
    <div class="pull-right">
        <span class="badge bg-secondary">Version:<?= $version->asString()?></span>

    </div>
</div>

