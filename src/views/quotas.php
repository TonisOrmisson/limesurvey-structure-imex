<?php

/** @var Survey $survey */
/** @var AdminController $this */
/** @var string $exportUrl */
/** @var ImportQuotas|null $import */
/** @var array $navUrls */
/** @var StructureImEx $exportPlugin */

?>


<div id='quota-imex'>
    <div class="page-header"><span class="h1">Import/Export survey quotas & limits</span></div>

    <?= $exportPlugin->renderPartial('_menu', ['navUrls' => $navUrls, 'exportPlugin' => $exportPlugin]) ;?>
    <div class="tab-content">

        <!-- Persistent Warnings -->
        <?php if($exportPlugin->getWarningManager()->hasActiveWarnings()): ?>
        <div class="row">
            <div class="col-md-12">
                <?= $exportPlugin->getWarningManager()->renderWarnings(); ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if($import instanceof ImportQuotas):?>
            <div class="row">
                <div class="col-md-12">
                    <?php if(!empty($import->getErrors())):?>
                        <div id="quota-import-results" class="alert alert-danger">
                            <div class="h4">Errors while importing the quota file!</div>
                            <?php foreach($import->getErrors() as $field => $errors): ?>
                                <?php foreach((array)$errors as $error): ?>
                                    <div class="h4 text-danger"><?= $error; ?></div>
                                <?php endforeach;?>
                            <?php endforeach;?>
                        </div>
                    <?php else:?>

                    <div id="quota-import-results" class="alert alert-success">
                        <div class="h4">Successfully updated <?= $import->successfulModelsCount?> quota models.</div>
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
            <!-- Export quotas -->
            <div class="col-md-12 col-lg-6">
                <div class="card">
                    <div class="card-body">
                        <div class="card-title">
                            <strong> <?php eT("Export")?></strong>
                        </div>
                        <div class="alert alert-info">
                            <div class="h3">Download</div>
                            <p>
                                Download the existing quotas and quota conditions for editing in your preferred spreadsheet editor. 
                                Includes quota limits, actions, and multi-language messages.
                            </p>
                        </div>
                        <div class="card-body">
                            <a role='button' class = "btn btn-warning pull-right" href='<?= $exportUrl; ?>'>Export Quotas</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Import quotas -->
            <div class="col-md-12 col-lg-6">
                <div class="card card-warning mt-3">
                    <div class="card-body">
                        <div class="card-title">
                            <strong> <?php eT("Import")?></strong>
                        </div>
                        <div class="alert alert-warning">
                            <div class="h3">NB! Quotas will be overwritten!</div>
                            <p>
                                Note that importing quotas via the import file will overwrite all quotas described in the file. 
                                Existing quotas with the same name will be updated, and new quotas will be created.
                            </p>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <div class="col-md-6">
                                    <?php echo CHtml::fileField('the_file','',['required'=>'required','accept'=>".xlsx, .xls, .ods"]); ?>
                                </div>
                                <div class="col-md-6">
                                    <input type='submit' class = "btn btn-warning pull-right" value='<?php eT("Import"); ?>' />
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

<!-- Include warning manager JavaScript -->
<?= $exportPlugin->getWarningManager()->getJavaScript(); ?>

</div>