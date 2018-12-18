<?php
/** @var Survey $survey */
/** @var AdminController $this */
/** @var string $thischaracterset */
/** @var array $aEncodings */
/** @var string $exportUrl */


$this->pageTitle = "import";
?>


<div id='relevance-imex'>
    <div class="h3 pagetitle">Import/Export survey relevance logic</div>
    <?php echo CHtml::form(null, 'post',array('enctype'=>'multipart/form-data')); ?>
    <div class="row">
        <div class="col-md-12 col-lg-6">
            <div class="panel panel-default">

                <!-- Import relevances -->
                <div class="panel-heading">
                    <strong> <?php eT("Import")?></strong>
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <div class="col-sm-3">
                            <?php echo CHtml::fileField('the_file','',array('required'=>'required','accept'=>'text/csv')); ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <!-- "Character set of the file -->
                            <div class="form-group">
                                <label class="col-sm-2 control-label" for='csvcharset'><?php eT("Character set of the file:"); ?></label>
                                <div class="col-sm-5">
                                    <?php
                                    echo CHtml::dropDownList('csvcharset', $thischaracterset, $aEncodings, array('size' => '1', 'class'=>'form-control'));
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <input type='submit' class = "btn btn-success" value='<?php eT("Import"); ?>' />

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
</div>