<?php

/** @var Survey $survey */
/** @var AdminController $this */
/** @var array $navUrls */
/** @var StructureImEx $exportPlugin */

?>

<div id='structure-imex-landing'>
    <div class="page-header">
        <span class="h1">StructureImEx - Survey Import/Export Tool</span>
        <p class="lead">Choose what you want to work with:</p>
    </div>

    <!-- Persistent Warnings -->
    <?php if($exportPlugin->getWarningManager()->hasActiveWarnings()): ?>
    <div class="row">
        <div class="col-md-12">
            <?= $exportPlugin->getWarningManager()->renderWarnings(); ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="row justify-content-center mt-5">
        
        <!-- Questions & Groups Card -->
        <div class="col-md-5 mb-4">
            <div class="card h-100 border-primary">
                <div class="card-header bg-primary text-white text-center">
                    <i class="ri-questionnaire-line fa-3x mb-2"></i>
                    <h3 class="card-title mb-0">Questions & Groups</h3>
                </div>
                <div class="card-body d-flex flex-column">
                    <p class="card-text flex-grow-1">
                        Import and export your survey's question structure, groups, and question attributes. 
                        This includes question types, settings, and organization.
                    </p>
                    <div class="alert alert-warning">
                        <small><strong>Note:</strong> This will modify your survey structure. Cannot be used on active surveys.</small>
                    </div>
                    <a href="<?= $navUrls[StructureImEx::ACTION_QUESTIONS];?>" 
                       class="btn btn-primary btn-lg mt-auto">
                        <i class="ri-questionnaire-line me-2"></i>
                        Work with Questions
                    </a>
                </div>
            </div>
        </div>

        <!-- Logic & Conditions Card -->
        <div class="col-md-5 mb-4">
            <div class="card h-100 border-success">
                <div class="card-header bg-success text-white text-center">
                    <i class="ri-git-branch-line fa-3x mb-2"></i>
                    <h3 class="card-title mb-0">Logic & Conditions</h3>
                </div>
                <div class="card-body d-flex flex-column">
                    <p class="card-text flex-grow-1">
                        Import and export relevance equations and conditional logic. 
                        Control when questions are shown based on previous answers.
                    </p>
                    <div class="alert alert-info">
                        <small><strong>Note:</strong> This will modify question conditions and relevance logic.</small>
                    </div>
                    <a href="<?= $navUrls[StructureImEx::ACTION_RELEVANCES];?>" 
                       class="btn btn-success btn-lg mt-auto">
                        <i class="ri-git-branch-line me-2"></i>
                        Work with Logic
                    </a>
                </div>
            </div>
        </div>

    </div>

    <div class="row justify-content-center mt-4">
        <div class="col-md-8">
            <div class="alert alert-info text-center">
                <h5><i class="ri-information-line me-2"></i>What's the difference?</h5>
                <div class="row">
                    <div class="col-md-6">
                        <strong>Questions & Groups:</strong> The structure and content of your survey - what questions exist, their types, and settings.
                    </div>
                    <div class="col-md-6">
                        <strong>Logic & Conditions:</strong> The behavior and flow - when questions are shown based on answers to other questions.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?= $exportPlugin->renderPartial('_footer', ['exportPlugin' => $exportPlugin]);?>

</div>

<!-- Include warning manager JavaScript -->
<?= $exportPlugin->getWarningManager()->getJavaScript(); ?>
