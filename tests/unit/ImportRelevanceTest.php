<?php

namespace tonisormisson\ls\structureimex\Tests\Unit;

use tonisormisson\ls\structureimex\import\ImportRelevance;

class ImportRelevanceTest extends BaseExportTest
{
    public function testProcessRejectsRowsWithGroupAndCode(): void
    {
        $import = new ImportRelevance($this->mockSurvey, $this->warningManager);
        $import->readerData = [[
            'group' => 'Demographics',
            'code' => 'age',
            'parent' => '',
            'relevance' => 'age.NAOK > 18',
        ]];

        $this->assertFalse($import->process());
        $this->assertStringContainsString(
            'Invalid relevance import format',
            implode(' ', $import->getErrors('currentModel'))
        );
    }
}
