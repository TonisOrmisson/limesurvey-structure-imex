<?php

/** @var AdminController $this */
/** @var array $navUrls */
/** @var StructureImEx $exportPlugin */
?>

<?php if ($exportPlugin->LSVersionCompare("6", "<")): ?>
    <ul class="nav nav-tabs">
        <li class="<?=($exportPlugin->type == StructureImEx::ACTION_QUESTIONS ? "active" : null)?>"><a href="<?= $navUrls[StructureImEx::ACTION_QUESTIONS];?>">Questions</a></li>
        <li class="<?=($exportPlugin->type == StructureImEx::ACTION_RELEVANCES ? "active" : null)?>"><a href="<?= $navUrls[StructureImEx::ACTION_RELEVANCES];?>">Logic</a></li>
    </ul>
<?php else: ?>
    <ul class="nav nav-tabs mt-4">
        <li class="nav-item"><a class="nav-link <?=($exportPlugin->type == StructureImEx::ACTION_QUESTIONS ? "active" : null)?>" href="<?= $navUrls[StructureImEx::ACTION_QUESTIONS];?>">Questions</a></li>
        <li class="nav-item"><a class="nav-link <?=($exportPlugin->type == StructureImEx::ACTION_RELEVANCES ? "active" : null)?>" href="<?= $navUrls[StructureImEx::ACTION_RELEVANCES];?>">Logic</a></li>
    </ul>
<?php endif; ?>