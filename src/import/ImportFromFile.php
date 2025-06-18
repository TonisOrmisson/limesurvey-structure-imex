<?php

namespace tonisormisson\ls\structureimex\import;

use CModel;
use CUploadedFile;
use LSYii_Application;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\ReaderInterface;
use OpenSpout\Reader\SheetInterface;
use Survey;
use tonisormisson\ls\structureimex\AppTrait;
use tonisormisson\ls\structureimex\exceptions\ImexException;
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

    protected Survey $survey;

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

    //FIXME validate filetypes (eg rules)

    const TYPE_GROUP = 1;
    const TYPE_QUESTION = 2;
    const TYPE_SUBQUESTION = 3;

    use AppTrait;


    function __construct(StructureImEx $plugin)
    {
        $this->plugin = $plugin;
        $this->survey = $plugin->getSurvey();
        $this->language = $this->survey->language ?? 'en';
        if ($this->survey->sid === null) {
            throw new ImexException("survey sid is null");
        }
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
        $sPath = $this->app()->getConfig('tempdir');
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

        unlink($this->fileName);
        return null;

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

        $this->reader->open($this->fileName);
        $this->setReaderData();
        $this->prepareReaderData();
        return true;
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
            if ($row instanceof Row) {
                $rowData = [];
                $cells = $row->getCells();
                $cellIndex = 0;
                foreach ($cells as $cell) {
                    $cellValue = $cell->getValue();
                    $rowData[] = $cellValue;
                    $cellIndex++;
                }
                // skip empty rows
                if (empty($rowData[0]) && empty($rowData[1]) && empty($rowData[2])) {
                    continue;
                }
                $this->readerData[] = $rowData;
            }
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
        $keys = $array[$i];
        if (is_array($array) && !empty($array)) {
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
        throw new ImexException(gettype($array) . ' used as array in ' . __CLASS__ . '::' . __FUNCTION__);
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
