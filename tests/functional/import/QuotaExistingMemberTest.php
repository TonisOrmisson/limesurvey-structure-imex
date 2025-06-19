<?php

namespace tonisormisson\ls\structureimex\tests\functional\import;

use Quota;
use QuotaMember;
use Question;
use tonisormisson\ls\structureimex\import\ImportQuotas;
use tonisormisson\ls\structureimex\Tests\Functional\DatabaseTestCase;

/**
 * Test quota member validation against existing database records
 */
class QuotaExistingMemberTest extends DatabaseTestCase
{
    protected $survey;
    protected $testQuestion;
    protected $existingQuota;

    public function setUp(): void
    {
        parent::setUp();
        
        // Import test survey
        $this->testSurveyId = $this->importSurveyFromFile($this->getBlankSurveyPath());
        $this->survey = \Survey::model()->findByPk($this->testSurveyId);
        
        // Ensure survey is inactive for import
        if ($this->survey && $this->survey->active === 'Y') {
            $this->survey->active = 'N';
            $this->survey->save();
        }
        
        // Create test question
        $groupData = $this->createTestSurveyWithQuestions();
        $this->testQuestion = Question::model()->findByPk($groupData['questions'][0]);
        
        // Create existing quota with existing quota member
        $this->createExistingQuotaWithMember();
    }

    /**
     * Create an existing quota with a quota member in the database
     */
    private function createExistingQuotaWithMember(): void
    {
        // Create quota
        $this->existingQuota = new Quota();
        $this->existingQuota->sid = $this->survey->sid;
        $this->existingQuota->name = 'ExistingQuota';
        $this->existingQuota->qlimit = 50;
        $this->existingQuota->active = 1;
        $this->existingQuota->autoload_url = 0;
        $this->existingQuota->save();
        
        // Create existing quota member for the test question
        $question = Question::model()->find('sid = :sid', [':sid' => $this->testSurveyId]);
        $existingMember = new QuotaMember();
        $existingMember->sid = $this->survey->sid;
        $existingMember->quota_id = $this->existingQuota->id;
        $existingMember->qid = $question->qid;
        $existingMember->code = 'EXISTING';
        $existingMember->save();
        
        $this->assertNotNull($this->existingQuota->id, 'Existing quota should be created');
        $this->assertNotNull($existingMember->id, 'Existing quota member should be created');
    }

    /**
     * Test that importing a quota member for a question that already has a member in the same quota succeeds (updates)
     * This should now pass with the update logic
     */
    public function testImportDuplicateQuestionMemberUpdates()
    {
        // Get the actual question title that exists in the survey
        $question = Question::model()->find('sid = :sid', [':sid' => $this->testSurveyId]);
        $questionTitle = $question->title;
        
        // Try to import another quota member for the same question in the same quota
        $quotaData = [
            ['type' => 'Q', 'name' => 'ExistingQuota', 'value' => '100', 'active' => '1'], // Update existing quota
            ['type' => 'QM', 'name' => $questionTitle, 'value' => 'NEWVAL', 'active' => ''], // Same question, different value
        ];
        
        $result = $this->importQuotas($quotaData);
        
        // This should succeed because existing quota member should be updated
        $this->assertTrue($result, 'Import should succeed when updating existing quota member for same question in same quota');
        
        // Verify the quota member was updated to new value
        $criteria = new \CDbCriteria();
        $criteria->condition = 'quota_id = :quota_id AND qid = :qid';
        $criteria->params = [':quota_id' => $this->existingQuota->id, ':qid' => $question->qid];
        
        $quotaMember = QuotaMember::model()->find($criteria);
        $this->assertNotNull($quotaMember, 'QuotaMember should exist');
        $this->assertEquals('NEWVAL', $quotaMember->code, 'QuotaMember should be updated to new value');
        
        // Verify only one quota member exists (no duplicates)
        $allMembers = QuotaMember::model()->findAll($criteria);
        $this->assertCount(1, $allMembers, 'Should have exactly 1 quota member after update');
    }


    /**
     * Test that importing a quota member for a different question in the same quota succeeds
     */
    public function testImportDifferentQuestionInSameQuotaSucceeds()
    {
        // Get existing questions and create a second one if needed
        $questions = Question::model()->findAll('sid = :sid', [':sid' => $this->testSurveyId]);
        $firstQuestion = $questions[0];
        
        // Create a second question for this test
        $secondQuestionId = $this->createTestQuestion(
            $this->testSurveyId, 
            $firstQuestion->gid, 
            'Q002', 
            'L', 
            'Second Question'
        );
        $secondQuestion = Question::model()->findByPk($secondQuestionId);
        
        $quotaData = [
            ['type' => 'Q', 'name' => 'ExistingQuota', 'value' => '100', 'active' => '1'],
            ['type' => 'QM', 'name' => $secondQuestion->title, 'value' => 'NEWVAL', 'active' => ''], // Different question
        ];
        
        $result = $this->importQuotas($quotaData);
        
        $this->assertTrue($result, 'Import should succeed when adding quota member for different question');
        
        // Verify both quota members exist (original + new)
        $criteria = new \CDbCriteria();
        $criteria->condition = 'quota_id = :quota_id';
        $criteria->params = [':quota_id' => $this->existingQuota->id];
        
        $quotaMembers = QuotaMember::model()->findAll($criteria);
        $this->assertCount(2, $quotaMembers, 'Should have 2 quota members: existing + new');
    }

    /**
     * Test that importing a quota member for the same question in a different quota succeeds
     */
    public function testImportSameQuestionInDifferentQuotaSucceeds()
    {
        // Get the actual question title that exists in the survey
        $question = Question::model()->find('sid = :sid', [':sid' => $this->testSurveyId]);
        $questionTitle = $question->title;
        
        $quotaData = [
            ['type' => 'Q', 'name' => 'NewQuota', 'value' => '75', 'active' => '1'], // Different quota
            ['type' => 'QM', 'name' => $questionTitle, 'value' => 'NEWVAL', 'active' => ''], // Same question
        ];
        
        $result = $this->importQuotas($quotaData);
        
        $this->assertTrue($result, 'Import should succeed when adding same question to different quota');
        
        // Verify quota members exist in both quotas
        $criteria = new \CDbCriteria();
        $criteria->condition = 'qid = :qid AND sid = :sid';
        $criteria->params = [':qid' => $question->qid, ':sid' => $this->testSurveyId];
        
        $quotaMembers = QuotaMember::model()->findAll($criteria);
        $this->assertCount(2, $quotaMembers, 'Should have 2 quota members for same question in different quotas');
    }

    /**
     * Test that updating existing quota member (same question, same quota, same value) succeeds
     */
    public function testUpdateExistingQuotaMemberSucceeds()
    {
        // Get the actual question title that exists in the survey
        $question = Question::model()->find('sid = :sid', [':sid' => $this->testSurveyId]);
        $questionTitle = $question->title;
        
        $quotaData = [
            ['type' => 'Q', 'name' => 'ExistingQuota', 'value' => '100', 'active' => '1'],
            ['type' => 'QM', 'name' => $questionTitle, 'value' => 'EXISTING', 'active' => ''], // Same value as existing
        ];
        
        $result = $this->importQuotas($quotaData);
        
        $this->assertTrue($result, 'Import should succeed when updating existing quota member with same value');
        
        // Verify only one quota member exists (no duplicates)
        $criteria = new \CDbCriteria();
        $criteria->condition = 'quota_id = :quota_id AND qid = :qid';
        $criteria->params = [':quota_id' => $this->existingQuota->id, ':qid' => $question->qid];
        
        $quotaMembers = QuotaMember::model()->findAll($criteria);
        $this->assertCount(1, $quotaMembers, 'Should have exactly 1 quota member after update');
        $this->assertEquals('EXISTING', $quotaMembers[0]->code, 'Quota member should keep existing value');
    }

    /**
     * Helper method to import quota data and return success status
     */
    private function importQuotas(array $quotaData): bool
    {
        $fileName = $this->createExcelFileFromData($quotaData);
        $plugin = $this->createRealPlugin($this->testSurveyId);
        $import = new ImportQuotas($plugin);
        
        $import->fileName = $fileName;
        
        if (!$import->prepare()) {
            return false;
        }
        
        $import->process();
        $errors = $import->getErrors();
        
        // Clean up temp file
        if (file_exists($fileName)) {
            unlink($fileName);
        }
        
        return empty($errors);
    }

    /**
     * Create Excel file from data array
     */
    private function createExcelFileFromData(array $data): string
    {
        $fileName = sys_get_temp_dir() . '/quota_test_' . uniqid() . '.xlsx';
        
        $writer = new \OpenSpout\Writer\XLSX\Writer();
        $writer->openToFile($fileName);
        
        // Get headers from first row
        if (!empty($data)) {
            $headers = array_keys($data[0]);
            $headerRow = \OpenSpout\Common\Entity\Row::fromValues($headers);
            $writer->addRow($headerRow);
            
            foreach ($data as $rowData) {
                $values = [];
                foreach ($headers as $header) {
                    $values[] = $rowData[$header] ?? '';
                }
                $dataRow = \OpenSpout\Common\Entity\Row::fromValues($values);
                $writer->addRow($dataRow);
            }
        }
        
        $writer->close();
        return $fileName;
    }

}