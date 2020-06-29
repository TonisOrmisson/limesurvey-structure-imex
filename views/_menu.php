<?php

/** @var AdminController $this */
/** @var array $navUrls */
/** @var StructureImEx $exportPlugin */
?>


<ul class="nav nav-tabs">
    <li class="<?=($exportPlugin->type == StructureImEx::ACTION_QUESTIONS ? "active" : null)?>"><a href="<?= $navUrls[StructureImEx::ACTION_QUESTIONS];?>">Questions</a></li>
    <li class="<?=($exportPlugin->type == StructureImEx::ACTION_RELEVANCES ? "active" : null)?>"><a href="<?= $navUrls[StructureImEx::ACTION_RELEVANCES];?>">Logic</a></li>
</ul>