<?php

namespace tonisormisson\ls\structureimex\import;

use CModel;
use CUploadedFile;
use LSYii_Application;
use OpenSpout\Common\Entity\Cell\FormulaCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\ReaderInterface;
use OpenSpout\Reader\SheetInterface;
use Survey;
use tonisormisson\ls\structureimex\AppTrait;
use tonisormisson\ls\structureimex\exceptions\ImexException;
use tonisormisson\ls\structureimex\PersistentWarningManager;
use tonisormisson\ls\structureimex\StructureImEx;
use Yii;



/**
 * An abstract class for various file imports
 */
abstract class ImportFromFile extends CModel
{
    /** @var CUploadedFile $file imported file */
    public CUploadedFile $file;

    public string $fileName;

    private ReaderInterface $reader;

    protected ?SheetInterface $sheet = null;

    public array $readerData = [];

    /** @var ?string $customTempDir Custom temp directory for testing, bypasses Yii app dependency */
    private ?string $customTempDir = null;

    protected string $type = "";

    protected string $relevanceAttribute = "";

    protected string $language = "";

    protected bool $hasSurveyColumn = true;

    /** @var string $questionCodeColumn column in import file where question code is located */
    protected string $questionCodeColumn = 'code';

    /** @var int $successfulModelsCount Total number of successfully processed records */
    public int $successfulModelsCount = 0;

    /** @var int $failedModelsCount Total number of records failed the processing */
    public int $failedModelsCount = 0;

    public array $rowAttributes = [];

    protected StructureImEx $plugin;

    use AppTrait;


    function __construct(
        protected Survey $survey,
        protected PersistentWarningManager $warningManager,
        protected bool $importUnknownAttributes = false
    )
    {
        $this->language = $this->survey->language ?? 'en';
        /** @var int|string|null $surveyId */
        $surveyId = $this->survey->sid;
        if ($surveyId === null) {
            throw new ImexException("survey sid is null");
        }
    }

    /**
     * Set custom temp directory for testing (bypasses Yii app dependency)
     */
    public function setCustomTempDir(string $tempDir): void
    {
        $this->customTempDir = $tempDir;
    }

    /**
     * @return bool
     */
    public function loadFile(CUploadedFile $file)
    {
        $this->file = $file;
        $this->validate(['file']);
        if ($this->hasErrors()) {
            return false;
        }
        $sPath = $this->customTempDir ?? $this->app()->getConfig('tempdir');
        $sFileName = $sPath . '/' . $this->file->name;
        $this->fileName = $sFileName;

        // delete if anything with same name in runtime
        if (is_file($sFileName)) {
            unlink($sFileName);
        }

        if (!@$this->file->saveAs($sFileName)) {
            $this->addError('file', gT('Error saving file'));
            return false;
        }
        return $this->prepare();

    }

    public function process()
    {
        $this->beforeProcess();

        try {
            if (!empty($this->errors)) {
                return false;
            }

            if (empty($this->readerData)) {
                $this->addError('data', gT('No data to import!'));
            } else {
                foreach ($this->readerData as $key => $row) {
                    $this->importModel($row);
                    if (!empty($this->getErrors())) {
                        return false;
                    }
                }
            }

            return empty($this->errors) ? null : false;
        } finally {
            $this->removeTemporaryFile();
        }

    }

    /**
     * Delete the uploaded temporary file if it still exists.
     */
    private function removeTemporaryFile(): void
    {
        if (!empty($this->fileName) && is_file($this->fileName)) {
            @unlink($this->fileName);
        }
    }


    abstract protected function beforeProcess(): void;

    public function prepare(): bool
    {
        $extension = pathinfo($this->fileName, PATHINFO_EXTENSION);
        $this->reader = match ($extension) {
            'xlsx' => new \OpenSpout\Reader\XLSX\Reader(),
            'xls' => new \OpenSpout\Reader\XLSX\Reader(),
            'ods' => new \OpenSpout\Reader\ODS\Reader(),
            'csv' => new \OpenSpout\Reader\CSV\Reader(),
            default => throw new ImexException("invalid extension '$extension'"),
        };

        if ($extension === 'xlsx') {
            $this->validateFormulaCellsHaveCachedValues();
        }

        $this->reader->open($this->fileName);
        $this->setReaderData();
        $this->prepareReaderData();
        return true;
    }

    private function validateFormulaCellsHaveCachedValues(): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($this->fileName) !== true) {
            throw new ImexException('Unable to inspect XLSX formulas before import.');
        }

        $previousLibxmlSetting = libxml_use_internal_errors(true);

        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $fileName = $zip->getNameIndex($i);
                if (!is_string($fileName) || !preg_match('#^xl/worksheets/.*\.xml$#', $fileName)) {
                    continue;
                }

                $xml = $zip->getFromName($fileName);
                if (!is_string($xml)) {
                    continue;
                }

                $document = new \DOMDocument();
                if (!$document->loadXML($xml)) {
                    throw new ImexException("Unable to inspect worksheet '$fileName' before import.");
                }

                $xpath = new \DOMXPath($document);
                $formulaCellsWithoutCachedValues = $xpath->query('//*[local-name()="c"][*[local-name()="f"] and not(*[local-name()="v"])]');
                if ($formulaCellsWithoutCachedValues === false) {
                    throw new ImexException("Unable to inspect formulas in worksheet '$fileName'.");
                }

                foreach ($formulaCellsWithoutCachedValues as $cell) {
                    $cellReference = $cell instanceof \DOMElement ? $cell->getAttribute('r') : '';
                    $cellReference = $cellReference !== '' ? $cellReference : 'unknown';
                    throw new ImexException("Formula cell $cellReference has no cached value. Open and save the spreadsheet in Excel or LibreOffice before importing.");
                }
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousLibxmlSetting);
            $zip->close();
        }
    }

    protected function prepareReaderData(): void
    {

        if (!empty($this->readerData)) {
            $this->readerData = self::indexByRow($this->readerData);
            foreach ($this->readerData as $key => $row) {
                $this->readerData[$key] = $row;
            }
        }
    }


    /**
     * read current worksheet row by row and set row data as readerData
     */
    private function setReaderData(): void
    {
        $this->readerData = [];
        $this->setWorksheet();
        $rowIndex = 0;
        foreach ($this->sheet->getRowIterator() as $row) {
            /** @var Row $row */
            $rowData = [];
            $cells = $row->getCells();
            $cellIndex = 0;
            foreach ($cells as $cell) {
                $cellValue = $cell instanceof FormulaCell ? $cell->getComputedValue() : $cell->getValue();
                if ($cell instanceof FormulaCell && $cellValue === null) {
                    throw new ImexException('Formula cell has no cached value. Open and save the spreadsheet in Excel or LibreOffice before importing.');
                }
                $rowData[] = $cellValue;
                $cellIndex++;
            }
            // skip empty rows
            if (empty($rowData[0]) && empty($rowData[1]) && empty($rowData[2])) {
                continue;
            }
            $this->readerData[] = $rowData;
            $rowIndex++;
        }
    }

    abstract protected function importModel(array $attributes): void;

    /**
     * @inheritdoc
     */
    public function attributeNames()
    {
        return [
            'file' => gT('Import file'),
            'processedModelsCount' => gT('Total records processed'),
            'successfulModelsCount' => gT('Successful records'),
            'failedModelsCount' => gT('Failed records'),
        ];
    }


    /**
     * Converts an non-indexed MULTIDIMENSIONAL array (such as data matrix from spreadsheet)
     * into an indexed array based on the $i-th element in the array. By default its the
     * first [0] element (header row). The indexing element will be excluded from output
     * array
     */
    public static function indexByRow(array $array, int $i = 0): array
    {
        if (empty($array)) {
            throw new ImexException('Empty array provided to ' . __CLASS__ . '::' . __FUNCTION__);
        }

        if (!isset($array[$i]) || !is_array($array[$i])) {
            throw new ImexException("Index $i not found in dataset for " . __CLASS__ . '::' . __FUNCTION__);
        }

        $keys = $array[$i];

        $newArray = [];
        foreach ($array as $key => $row) {
            // don't add the indexing element into output
            if ($key != $i) {
                $newRow = [];
                $j = 0;
                foreach ($row as $cell) {
                    // Only map if we have a corresponding key for this position
                    if (isset($keys[$j])) {
                        $newRow[$keys[$j]] = $cell;
                    }
                    $j++;
                }
                $newArray[] = $newRow;
            }
        }

        return $newArray;
    }

    /**
     * Changes the Excel reader active worksheet
     */
    protected function setWorksheet(?string $sheetName = null): bool
    {
        /** @var SheetInterface $sheet */
        foreach ($this->reader->getSheetIterator() as $sheet) {
            if (empty($sheetName)) {
                $this->sheet = $sheet;
                return true;
            }

            if (strtolower($sheet->getName()) === $sheetName) {
                $this->sheet = $sheet;
                return true;
            }
        }
        $this->sheet = null;
        return false;
    }
}
