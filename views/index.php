<?php

use tonisormisson\version\Version;

/** @var Survey $survey */
/** @var AdminController $this */
/** @var string $thischaracterset */
/** @var array $aEncodings */
/** @var string $exportUrl */
/** @var ImportRelevance $import */

$this->pageTitle = "import";
?>


<div id='relevance-imex'>
    <div class="h3 pagetitle">Import/Export survey relevance logic</div>


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

    <?php echo CHtml::form(null, 'post',array('enctype'=>'multipart/form-data')); ?>
    <div class="row">
        <div class="col-md-12 col-lg-6">
            <div class="panel panel-default">

                <!-- Import relevances -->
                <div class="panel-heading">
                    <strong> <?php eT("Import")?></strong>
                </div>
                <div class="panel-body">
                    <div class="alert alert-warning">
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


        <div class="col-md-12 col-lg-6">
            <div class="panel panel-default">

                <!-- Export relevances -->
                <div class="panel-heading">
                    <strong> <?php eT("Export")?></strong>
                </div>
                <div class="panel-body">
                    <a role='button' class = "btn btn-warning" href='<?= $exportUrl; ?>'>Export</a>
                </div>
            </div>
        </div>

        <input type='hidden' name='sid' value='<?= $survey->primaryKey;?>' />
        <?php echo CHtml::endForm() ?>
    </div>
    <div class="row">
        <div class="pull-right">
            <span class="label label-default">Version: <?= (new Version(__DIR__. DIRECTORY_SEPARATOR .".." . DIRECTORY_SEPARATOR ))->tag;?></span>
        </div>
    </div>
</div>