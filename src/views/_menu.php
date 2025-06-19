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
           class="btn btn-primary d-flex align-items-center gap-2 <?=($exportPlugin->type == StructureImEx::ACTION_QUESTIONS ? "active" : null)?>">
            <i class="ri-questionnaire-line" aria-hidden="true"></i>
            <span>Questions & Groups</span>
        </a>
        <a href="<?= $navUrls[StructureImEx::ACTION_RELEVANCES];?>" 
           class="btn btn-primary d-flex align-items-center gap-2 <?=($exportPlugin->type == StructureImEx::ACTION_RELEVANCES ? "active" : null)?>">
            <i class="ri-git-branch-line" aria-hidden="true"></i>
            <span>Logic & Conditions</span>
        </a>
        <a href="<?= $navUrls[StructureImEx::ACTION_QUOTAS];?>" 
           class="btn btn-primary d-flex align-items-center gap-2 <?=($exportPlugin->type == StructureImEx::ACTION_QUOTAS ? "active" : null)?>">
            <i class="ri-bar-chart-grouped-line" aria-hidden="true"></i>
            <span>Quotas & Limits</span>
        </a>
    </div>
    
    <!-- Current Mode Indicator -->
    <div class="ms-auto d-flex align-items-center">
        <small class="text-muted">Current mode:</small>
        <span class="ms-2 badge <?php 
            if($exportPlugin->type == StructureImEx::ACTION_QUESTIONS) echo "bg-primary";
            elseif($exportPlugin->type == StructureImEx::ACTION_RELEVANCES) echo "bg-success";
            else echo "bg-warning";
        ?>">
            <?php if($exportPlugin->type == StructureImEx::ACTION_QUESTIONS): ?>
                <i class="ri-questionnaire-line me-1"></i>Questions & Groups
            <?php elseif($exportPlugin->type == StructureImEx::ACTION_RELEVANCES): ?>
                <i class="ri-git-branch-line me-1"></i>Logic & Conditions
            <?php else: ?>
                <i class="ri-bar-chart-grouped-line me-1"></i>Quotas & Limits
            <?php endif; ?>
        </span>
    </div>
</div>
