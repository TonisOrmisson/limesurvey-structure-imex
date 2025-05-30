<?php

use tonisormisson\ls\structureimex\import\ImportRelevance;

/** @var Survey $survey */
/** @var AdminController $this */
/** @var string $exportUrl */
/** @var ImportRelevance $import */
/** @var array $navUrls */
/** @var PluginBase $exportPlugin */

?>


<div id='relevance-imex'>
    <div class="page-header"><span class="h1">Import/Export survey relevance logic</span></div>

    <?= $exportPlugin->renderPartial('_menu', ['navUrls' => $navUrls, 'exportPlugin' => $exportPlugin]) ;?>
    <div class="tab-content">

        <?php if($import instanceof ImportRelevance):?>
            <div class="row">
                <div class="col-md-12">
                    <?php if(!empty($import->getErrors())):?>
                        <div id="relevance-import-results" class="alert alert-danger">
                            <div class="h4">Errors while importing the logic file!</div>
                            <?php foreach($import->getErrors('currentModel') as $error): ?>
                                <div class="h4 text-danger"><?= $error; ?></div>
                            <?php endforeach;?>
                        </div>
                    <?php else:?>

                    <div id="relevance-import-results" class="alert alert-success">
                        <div class="h4">Successfully updated <?= $import->successfulModelsCount?> models logic.</div>
                        <?php if ($import->failedModelsCount > 0): ?>
                            <div class="h4 text-danger">Failed to find <?= $import->failedModelsCount?> models.</div>
                        <?php endif;?>
                    </div>
                    <?php endif;?>
                </div>
            </div>
        <?php endif;?>

        <div class="row">
            <?= CHtml::form(null, 'post',['enctype'=>'multipart/form-data']); ?>
            <!-- Export relevances -->
            <div class="col-md-12 col-lg-6">
                <div class="card">
                    <div class="card-body">
                        <div class="card-title">
                            <strong> <?php eT("Export")?></strong>
                        </div>
                        <div class="alert alert-info">
                            <div class="h3">Download</div>
                            <p>
                                Download the existing logic or base structure of the groups and questions for editing in your preferred spreadsheet editor.
                            </p>
                        </div>
                        <div class="card-body">
                            <a role='button' class = "btn btn-success pull-right" href='<?= $exportUrl; ?>'>Export</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Import relevances -->
            <div class="col-md-12 col-lg-6">
                <div class="card card-danger mt-3">
                    <div class="card-body">
                        <div class="card-title">
                            <strong> <?php eT("Import")?></strong>
                        </div>
                        <div class="alert alert-danger">
                            <div class="h3">NB! Conditions will be removed!</div>
                            <p>
                                Note that by importing the relevances via the import file, will overwrite all relevances described in the file and will also remove all current question conditions (if defined).
                            </p>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <div class="col-md-6">
                                    <?php echo CHtml::fileField('the_file','',['required'=>'required','accept'=>".xlsx, .xls, .ods"]); ?>
                                </div>
                                <div class="col-md-6">
                                    <input type='submit' class = "btn btn-success pull-right" value='<?php eT("Import"); ?>' />
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>


            <input type='hidden' name='sid' value='<?= $survey->primaryKey;?>' />
            <?php echo CHtml::endForm() ?>
        </div>
    </div>

    <?= $exportPlugin->renderPartial('_footer', ['exportPlugin' => $exportPlugin]);?>

</div>
