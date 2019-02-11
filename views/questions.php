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
    <div class="h3 pagetitle">Import/Export survey relevance logic</div>
    <?= $exportPlugin->renderPartial('_menu', ['navUrls' => $navUrls]) ;?>

</div>