<?php
use tonisormisson\version\Version;

?>
<div class="row">
    <div class="text-end pull-right">
        <span class="label label-default">Version: <?= "-" /*(new Version(__DIR__. DIRECTORY_SEPARATOR .".." . DIRECTORY_SEPARATOR ))->tag;*/ ?></span>
    </div>
</div>

