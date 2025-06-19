<?php
use tonisormisson\ls\structureimex\StructureImEx;

/** @var AdminController $this */
/** @var array $navUrls */
/** @var StructureImEx $exportPlugin */

?>
<!-- Pill Navigation -->
<div class="d-flex gap-2 mb-4 p-3 bg-light rounded">
    <div class="btn-group" role="group" aria-label="StructureImEx Navigation">
        <a href="<?= $navUrls[StructureImEx::ACTION_QUESTIONS];?>" 
           class="btn btn-outline-primary d-flex align-items-center gap-2 <?=($exportPlugin->type == StructureImEx::ACTION_QUESTIONS ? "active" : null)?>">
            <i class="ri-questionnaire-line" aria-hidden="true"></i>
            <span>Questions & Groups</span>
        </a>
        <a href="<?= $navUrls[StructureImEx::ACTION_RELEVANCES];?>" 
           class="btn btn-outline-success d-flex align-items-center gap-2 <?=($exportPlugin->type == StructureImEx::ACTION_RELEVANCES ? "active" : null)?>">
            <i class="ri-git-branch-line" aria-hidden="true"></i>
            <span>Logic & Conditions</span>
        </a>
    </div>
    
    <!-- Current Mode Indicator -->
    <div class="ms-auto d-flex align-items-center">
        <small class="text-muted">Current mode:</small>
        <span class="ms-2 badge <?=($exportPlugin->type == StructureImEx::ACTION_QUESTIONS ? "bg-primary" : "bg-success")?>">
            <?php if($exportPlugin->type == StructureImEx::ACTION_QUESTIONS): ?>
                <i class="ri-questionnaire-line me-1"></i>Questions & Groups
            <?php else: ?>
                <i class="ri-git-branch-line me-1"></i>Logic & Conditions
            <?php endif; ?>
        </span>
    </div>
</div>
