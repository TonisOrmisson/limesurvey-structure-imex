<?php

namespace tonisormisson\ls\structureimex\Tests\Unit;

use tonisormisson\ls\structureimex\exceptions\ImexException;
use tonisormisson\ls\structureimex\import\ImportFromFile;

class ImportFromFileTest extends BaseExportTest
{
    public function testPrepareUsesComputedValuesForFormulaCells(): void
    {
        $import = new class($this->mockSurvey, $this->warningManager) extends ImportFromFile {
            protected function beforeProcess(): void
            {
            }

            protected function importModel(array $attributes): void
            {
            }
        };

        $tempFile = tempnam(sys_get_temp_dir(), 'imex_formula_') . '.xlsx';
        $this->createFormulaExcelFile($tempFile);
        $import->fileName = $tempFile;

        $this->assertTrue($import->prepare());
        $this->assertSame('Formula Group', $import->readerData[0]['title-en']);

        @unlink($tempFile);
    }

    public function testPrepareFailsWhenFormulaCellHasNoCachedValue(): void
    {
        $import = new class($this->mockSurvey, $this->warningManager) extends ImportFromFile {
            protected function beforeProcess(): void
            {
            }

            protected function importModel(array $attributes): void
            {
            }
        };

        $tempFile = tempnam(sys_get_temp_dir(), 'imex_formula_missing_cache_') . '.xlsx';
        $this->createFormulaExcelFile($tempFile, false);
        $import->fileName = $tempFile;

        $this->expectException(ImexException::class);
        $this->expectExceptionMessage('Formula cell C2 has no cached value');

        try {
            $import->prepare();
        } finally {
            @unlink($tempFile);
        }
    }

    public function testProcessRemovesTemporaryFileOnSuccess(): void
    {
        $import = new class($this->mockSurvey, $this->warningManager) extends ImportFromFile {
            protected function beforeProcess(): void
            {
            }

            protected function importModel(array $attributes): void
            {
                $this->successfulModelsCount++;
            }
        };

        $tempFile = tempnam(sys_get_temp_dir(), 'imex_success_');
        file_put_contents($tempFile, 'test');
        $import->fileName = $tempFile;
        $import->readerData = [['row']];

        $this->assertNull($import->process());
        $this->assertFileDoesNotExist($tempFile);
    }

    public function testProcessRemovesTemporaryFileOnFailure(): void
    {
        $import = new class($this->mockSurvey, $this->warningManager) extends ImportFromFile {
            public bool $failOnImport = false;

            protected function beforeProcess(): void
            {
            }

            protected function importModel(array $attributes): void
            {
                if ($this->failOnImport) {
                    $this->addError('stub', 'forced failure');
                    return;
                }

                $this->successfulModelsCount++;
            }
        };

        $import->failOnImport = true;

        $tempFile = tempnam(sys_get_temp_dir(), 'imex_failure_');
        file_put_contents($tempFile, 'test');
        $import->fileName = $tempFile;
        $import->readerData = [['row']];

        $this->assertFalse($import->process());
        $this->assertFileDoesNotExist($tempFile);
    }

    private function createFormulaExcelFile(string $filePath, bool $includeCachedValue = true): void
    {
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($filePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE));
        $cachedValueXml = $includeCachedValue ? '<v>Formula Group</v>' : '';

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets><sheet name="Sheet1" sheetId="1" r:id="rId1"/></sheets>
</workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
</Relationships>');
        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0" encoding="UTF-8"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <sheetData>
    <row r="1">
      <c r="A1" t="inlineStr"><is><t>type</t></is></c>
      <c r="B1" t="inlineStr"><is><t>code</t></is></c>
      <c r="C1" t="inlineStr"><is><t>title-en</t></is></c>
    </row>
    <row r="2">
      <c r="A2" t="inlineStr"><is><t>G</t></is></c>
      <c r="B2" t="inlineStr"><is><t>group1</t></is></c>
      <c r="C2" t="str"><f>CONCAT(&quot;Formula&quot;,&quot; Group&quot;)</f>' . $cachedValueXml . '</c>
    </row>
  </sheetData>
</worksheet>');

        $this->assertTrue($zip->close());
    }
}
