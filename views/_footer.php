<?php
$sourcePath  =__DIR__. DIRECTORY_SEPARATOR .".." . DIRECTORY_SEPARATOR;
$version = new \SebastianBergmann\Version('1.0.0', $sourcePath);
?>
<div class="row">
    <div class="pull-right">
        <span class="badge bg-secondary">Version:<?= $version->getVersion();?></span>

    </div>
</div>

