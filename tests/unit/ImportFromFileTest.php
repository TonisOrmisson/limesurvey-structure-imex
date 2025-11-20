<?php

namespace tonisormisson\ls\structureimex\Tests\Unit;

use tonisormisson\ls\structureimex\import\ImportFromFile;

class ImportFromFileTest extends BaseExportTest
{
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
}
