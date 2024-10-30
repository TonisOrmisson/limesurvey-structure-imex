<?php
use tonisormisson\ls\structureimex\StructureImEx;

/** @var StructureImEx $exportPlugin */
$version = new \SebastianBergmann\Version('1.0.0', $exportPlugin->dir().DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR);
?>
<div class="row">
    <div class="pull-right">
        <span class="badge bg-secondary">Version:<?= $version->getVersion();?></span>

    </div>
</div>

