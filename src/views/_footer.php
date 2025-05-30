<?php
use tonisormisson\ls\structureimex\StructureImEx;

/** @var StructureImEx $exportPlugin */
try {
    $pluginDir = dirname(dirname(__DIR__)); // Go up two levels from the views directory
    $version = new \SebastianBergmann\Version('1.0.0', $pluginDir);
} catch (Exception $e) {
    // Fallback if there's an issue with the version
    $version = new class {
        public function getVersion() {
            return '1.0.0';
        }
    };
}
?>
<div class="row">
    <div class="pull-right">
        <span class="badge bg-secondary">Version:<?= $version->getVersion();?></span>

    </div>
</div>

