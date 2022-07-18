<?php

use tonisormisson\version\Version;

/** @var Survey $survey */
/** @var AdminController $this */
/** @var string $exportUrl */
/** @var ImportRelevance $import */
/** @var array $navUrls */
/** @var PluginBase $exportPlugin */

$this->pageTitle = "import";
?>


<div id='relevance-imex'>
    <div class="page-header"><span class="h1">Import/Export Questions</span> </div>

    <?= $exportPlugin->renderPartial('_menu', ['navUrls' => $navUrls, 'exportPlugin' => $exportPlugin]) ;?>
    <div class="tab-content">
        <?php if($survey->getIsActive()):?>
        <div class="row">
            <div class="alert alert-warning">
                <div class="h3">This is an activated survey! You can not import & change the structure of this survey!</div>
                <div>You can still import & export the <a href="<?= $navUrls[StructureImEx::ACTION_RELEVANCES];?>">relevances</a> (logic) of this survey</div>
            </div>
        </div>
        <?php endif;?>

        <div class="row">
            <?= CHtml::form(null, 'post',['enctype'=>'multipart/form-data']); ?>
            <!-- Export -->
            <div class="col-md-12 col-lg-6">
                <div class="panel panel-success">
                    <div class="panel-heading">
                        <?php eT("Export")?>
                    </div>
                    <div class="alert">
                        <div class="h3">Download</div>
                        <p>
                            Download the existing groups and questions for editing in your preferred spreadsheet editor.
                        </p>
                    </div>
                    <div class="panel-body">
                        <a role='button' class = "btn btn-success pull-right" href='<?= $exportUrl; ?>'>Export</a>
                    </div>
                </div>
            </div>

            <!-- Import relevances -->
            <div class="col-md-12 col-lg-6">
                <div class="panel panel-danger">
                    <div class="panel-heading">
                        <strong> <?php eT("Import")?></strong>
                    </div>

                    <div class="panel-body">
                        <div class="alert">
                            <div class="h3">NB! Existing questions will be overwritten</div>
                            <p>
                                Importing will remove all existing groups & questions and replace them with the existing ones.
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
    <?= $exportPlugin->renderPartial('_footer', []);?>

</div>

</div>
