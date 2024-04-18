<?php

/** @var AdminController $this */
/** @var array $navUrls */
/** @var StructureImEx $exportPlugin */

// Questions imex not working 4+ versions
?>
<ul class="nav nav-tabs">
    <li class="nav-item">
        <a href="<?= $navUrls[StructureImEx::ACTION_RELEVANCES];?>" class="nav-link <?=($exportPlugin->type == StructureImEx::ACTION_RELEVANCES ? "active" : null)?>">Logic</a>
    </li>
    <?php if(!$exportPlugin->isV4plusVersion()): ?>
        <li class="nav-item">
            <a href="<?= $navUrls[StructureImEx::ACTION_QUESTIONS];?>" class="nav-link <?=($exportPlugin->type == StructureImEx::ACTION_QUESTIONS ? "active" : null)?>">Questions</a>
        </li>
    <?php endif;?>
</ul>
