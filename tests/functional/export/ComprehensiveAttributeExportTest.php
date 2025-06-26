<?php

namespace tonisormisson\ls\structureimex\Tests\Functional;

use Question;
use QuestionAttribute;
use tonisormisson\ls\structureimex\validation\QuestionAttributeLanguageManager;

/**
 * Comprehensive test for ALL question types and ALL their attributes
 * Tests both global and language-specific attribute export functionality
 */
class ComprehensiveAttributeExportTest extends DatabaseTestCase
{
    protected $testSurveyId;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Import the proper multi-language survey
        $this->testSurveyId = $this->importSurveyFromFile($this->getMultiLanguageSurveyPath());
    }
    
    /**
     * Data provider: All question types with their specific attributes to test export
     */
    public function questionTypeAttributeProvider()
    {
        return [
            // === LIST RADIO (L) ===
            // Global attributes
            [\Question::QT_L_LIST, 'hidden', '0', '1'],
            [\Question::QT_L_LIST, 'hide_tip', '0', '1'],
            [\Question::QT_L_LIST, 'answer_order', 'normal', 'random'],
            [\Question::QT_L_LIST, 'assessment_value', '0', '1'],
            [\Question::QT_L_LIST, 'scale_export', '0', '1'],
            // Language-specific attributes
            [\Question::QT_L_LIST, 'em_validation_q_tip', '', 'List validation tip'],
            [\Question::QT_L_LIST, 'other_replace_text', '', 'Other option text'],
            
            // === LONG FREE TEXT (T) ===
            // Global attributes
            [\Question::QT_T_LONG_FREE_TEXT, 'hidden', '0', '1'],
            [\Question::QT_T_LONG_FREE_TEXT, 'hide_tip', '0', '1'],
            [\Question::QT_T_LONG_FREE_TEXT, 'maximum_chars', '', '500'],
            [\Question::QT_T_LONG_FREE_TEXT, 'text_input_width', '', '200'],
            [\Question::QT_T_LONG_FREE_TEXT, 'display_rows', '5', '10'],
            // Language-specific attributes
            [\Question::QT_T_LONG_FREE_TEXT, 'em_validation_q_tip', '', 'Long text validation tip'],
            
            // === NUMERICAL (N) ===
            // Global attributes
            [\Question::QT_N_NUMERICAL, 'hidden', '0', '1'],
            [\Question::QT_N_NUMERICAL, 'hide_tip', '0', '1'],
            [\Question::QT_N_NUMERICAL, 'num_value_int_only', '0', '1'],
            [\Question::QT_N_NUMERICAL, 'min_num_value_n', '', '1'],
            [\Question::QT_N_NUMERICAL, 'max_num_value_n', '', '100'],
            // Language-specific attributes
            [\Question::QT_N_NUMERICAL, 'em_validation_q_tip', '', 'Number validation tip'],
            
            // === MULTIPLE CHOICE (M) ===
            // Global attributes
            [\Question::QT_M_MULTIPLE_CHOICE, 'hidden', '0', '1'],
            [\Question::QT_M_MULTIPLE_CHOICE, 'hide_tip', '0', '1'],
            [\Question::QT_M_MULTIPLE_CHOICE, 'min_answers', '', '2'],
            [\Question::QT_M_MULTIPLE_CHOICE, 'max_answers', '', '5'],
            [\Question::QT_M_MULTIPLE_CHOICE, 'answer_order', 'normal', 'random'],
            // Language-specific attributes
            [\Question::QT_M_MULTIPLE_CHOICE, 'em_validation_q_tip', '', 'Multiple choice validation tip'],
            [\Question::QT_M_MULTIPLE_CHOICE, 'other_replace_text', '', 'Other choice text'],
            
            // === SHORT FREE TEXT (S) ===
            // Global attributes
            [\Question::QT_S_SHORT_FREE_TEXT, 'hidden', '0', '1'],
            [\Question::QT_S_SHORT_FREE_TEXT, 'hide_tip', '0', '1'],
            [\Question::QT_S_SHORT_FREE_TEXT, 'text_input_width', '', '100'],
            [\Question::QT_S_SHORT_FREE_TEXT, 'maximum_chars', '', '50'],
            // Language-specific attributes
            [\Question::QT_S_SHORT_FREE_TEXT, 'em_validation_q_tip', '', 'Short text validation tip'],
            
            // === LIST DROPDOWN (!) ===
            // Global attributes
            [\Question::QT_EXCLAMATION_LIST_DROPDOWN, 'hidden', '0', '1'],
            [\Question::QT_EXCLAMATION_LIST_DROPDOWN, 'hide_tip', '0', '1'],
            [\Question::QT_EXCLAMATION_LIST_DROPDOWN, 'answer_order', 'normal', 'random'],
            // Language-specific attributes
            [\Question::QT_EXCLAMATION_LIST_DROPDOWN, 'em_validation_q_tip', '', 'Dropdown validation tip'],
            [\Question::QT_EXCLAMATION_LIST_DROPDOWN, 'other_replace_text', '', 'Dropdown other text'],
            
            // === ARRAY (F) ===
            // Global attributes
            [\Question::QT_F_ARRAY, 'hidden', '0', '1'],
            [\Question::QT_F_ARRAY, 'hide_tip', '0', '1'],
            [\Question::QT_F_ARRAY, 'random_order', '0', '1'],
            [\Question::QT_F_ARRAY, 'array_filter_style', '0', '1'],
            // Language-specific attributes
            [\Question::QT_F_ARRAY, 'em_validation_q_tip', '', 'Array validation tip'],
            
            // === MULTIPLE SHORT TEXT (Q) ===
            // Global attributes
            [\Question::QT_Q_MULTIPLE_SHORT_TEXT, 'hidden', '0', '1'],
            [\Question::QT_Q_MULTIPLE_SHORT_TEXT, 'hide_tip', '0', '1'],
            [\Question::QT_Q_MULTIPLE_SHORT_TEXT, 'text_input_columns', '', '6'],
            // Language-specific attributes
            [\Question::QT_Q_MULTIPLE_SHORT_TEXT, 'em_validation_q_tip', '', 'Multi short text validation tip'],
            
            // === MULTIPLE NUMERICAL (K) ===
            // Global attributes
            [\Question::QT_K_MULTIPLE_NUMERICAL, 'hidden', '0', '1'],
            [\Question::QT_K_MULTIPLE_NUMERICAL, 'hide_tip', '0', '1'],
            [\Question::QT_K_MULTIPLE_NUMERICAL, 'num_value_int_only', '0', '1'],
            // Language-specific attributes
            [\Question::QT_K_MULTIPLE_NUMERICAL, 'em_validation_q_tip', '', 'Multi numerical validation tip'],
            
            // === TEXT DISPLAY (X) ===
            // Global attributes
            [\Question::QT_X_TEXT_DISPLAY, 'hidden', '0', '1'],
            [\Question::QT_X_TEXT_DISPLAY, 'hide_tip', '0', '1'],
            [\Question::QT_X_TEXT_DISPLAY, 'cssclass', '', 'display-class'],
            // Language-specific attributes
            [\Question::QT_X_TEXT_DISPLAY, 'em_validation_q_tip', '', 'Text display validation tip'],
            
            // === YES/NO (Y) ===
            // Global attributes
            [\Question::QT_Y_YES_NO_RADIO, 'hidden', '0', '1'],
            [\Question::QT_Y_YES_NO_RADIO, 'hide_tip', '0', '1'],
            // Language-specific attributes
            [\Question::QT_Y_YES_NO_RADIO, 'em_validation_q_tip', '', 'Yes/No validation tip'],
            
            // === GENDER (G) ===
            // Global attributes
            [\Question::QT_G_GENDER, 'hidden', '0', '1'],
            [\Question::QT_G_GENDER, 'hide_tip', '0', '1'],
            // Language-specific attributes
            [\Question::QT_G_GENDER, 'em_validation_q_tip', '', 'Gender validation tip'],
            
            // === ARRAY DUAL SCALE (1) ===
            // Global attributes
            [\Question::QT_1_ARRAY_DUAL, 'hidden', '0', '1'],
            [\Question::QT_1_ARRAY_DUAL, 'hide_tip', '0', '1'],
            [\Question::QT_1_ARRAY_DUAL, 'random_order', '0', '1'],
            // Language-specific attributes
            [\Question::QT_1_ARRAY_DUAL, 'em_validation_q_tip', '', 'Array dual validation tip'],
            
            // === 5 POINT CHOICE (5) ===
            // Global attributes
            [\Question::QT_5_POINT_CHOICE, 'hidden', '0', '1'],
            [\Question::QT_5_POINT_CHOICE, 'hide_tip', '0', '1'],
            // Language-specific attributes
            [\Question::QT_5_POINT_CHOICE, 'em_validation_q_tip', '', '5 point validation tip'],
            
            // === DATE (D) ===
            // Global attributes
            [\Question::QT_D_DATE, 'hidden', '0', '1'],
            [\Question::QT_D_DATE, 'hide_tip', '0', '1'],
            [\Question::QT_D_DATE, 'date_format', '', 'dd/mm/yyyy'],
            // Language-specific attributes
            [\Question::QT_D_DATE, 'em_validation_q_tip', '', 'Date validation tip'],
            
            // === ARRAY 5 POINT (A) ===
            // Global attributes
            [\Question::QT_A_ARRAY_5_POINT, 'hidden', '0', '1'],
            [\Question::QT_A_ARRAY_5_POINT, 'hide_tip', '0', '1'],
            [\Question::QT_A_ARRAY_5_POINT, 'random_order', '0', '1'],
            // Language-specific attributes
            [\Question::QT_A_ARRAY_5_POINT, 'em_validation_q_tip', '', '5 point array validation tip'],
            
            // === ARRAY 10 CHOICE (B) ===
            // Global attributes
            [\Question::QT_B_ARRAY_10_CHOICE_QUESTIONS, 'hidden', '0', '1'],
            [\Question::QT_B_ARRAY_10_CHOICE_QUESTIONS, 'hide_tip', '0', '1'],
            [\Question::QT_B_ARRAY_10_CHOICE_QUESTIONS, 'random_order', '0', '1'],
            // Language-specific attributes
            [\Question::QT_B_ARRAY_10_CHOICE_QUESTIONS, 'em_validation_q_tip', '', '10 choice array validation tip'],
            
            // === ARRAY YES/UNCERTAIN/NO (C) ===
            // Global attributes
            [\Question::QT_C_ARRAY_YES_UNCERTAIN_NO, 'hidden', '0', '1'],
            [\Question::QT_C_ARRAY_YES_UNCERTAIN_NO, 'hide_tip', '0', '1'],
            [\Question::QT_C_ARRAY_YES_UNCERTAIN_NO, 'random_order', '0', '1'],
            // Language-specific attributes
            [\Question::QT_C_ARRAY_YES_UNCERTAIN_NO, 'em_validation_q_tip', '', 'Yes/Uncertain/No array validation tip'],
            
            // === ARRAY INC/SAME/DEC (E) ===
            // Global attributes
            [\Question::QT_E_ARRAY_INC_SAME_DEC, 'hidden', '0', '1'],
            [\Question::QT_E_ARRAY_INC_SAME_DEC, 'hide_tip', '0', '1'],
            [\Question::QT_E_ARRAY_INC_SAME_DEC, 'random_order', '0', '1'],
            // Language-specific attributes
            [\Question::QT_E_ARRAY_INC_SAME_DEC, 'em_validation_q_tip', '', 'Inc/Same/Dec array validation tip'],
            
            // === ARRAY COLUMN (H) ===
            // Global attributes
            [\Question::QT_H_ARRAY_COLUMN, 'hidden', '0', '1'],
            [\Question::QT_H_ARRAY_COLUMN, 'hide_tip', '0', '1'],
            [\Question::QT_H_ARRAY_COLUMN, 'random_order', '0', '1'],
            // Language-specific attributes
            [\Question::QT_H_ARRAY_COLUMN, 'em_validation_q_tip', '', 'Array column validation tip'],
            
            // === LANGUAGE (I) ===
            // Global attributes
            [\Question::QT_I_LANGUAGE, 'hidden', '0', '1'],
            [\Question::QT_I_LANGUAGE, 'hide_tip', '0', '1'],
            // Language-specific attributes
            [\Question::QT_I_LANGUAGE, 'em_validation_q_tip', '', 'Language validation tip'],
            
            // === LIST WITH COMMENT (O) ===
            // Global attributes
            [\Question::QT_O_LIST_WITH_COMMENT, 'hidden', '0', '1'],
            [\Question::QT_O_LIST_WITH_COMMENT, 'hide_tip', '0', '1'],
            [\Question::QT_O_LIST_WITH_COMMENT, 'answer_order', 'normal', 'random'],
            // Language-specific attributes
            [\Question::QT_O_LIST_WITH_COMMENT, 'em_validation_q_tip', '', 'List with comment validation tip'],
            [\Question::QT_O_LIST_WITH_COMMENT, 'other_replace_text', '', 'Comment field text'],
            
            // === MULTIPLE CHOICE WITH COMMENTS (P) ===
            // Global attributes
            [\Question::QT_P_MULTIPLE_CHOICE_WITH_COMMENTS, 'hidden', '0', '1'],
            [\Question::QT_P_MULTIPLE_CHOICE_WITH_COMMENTS, 'hide_tip', '0', '1'],
            [\Question::QT_P_MULTIPLE_CHOICE_WITH_COMMENTS, 'min_answers', '', '2'],
            [\Question::QT_P_MULTIPLE_CHOICE_WITH_COMMENTS, 'max_answers', '', '5'],
            // Language-specific attributes
            [\Question::QT_P_MULTIPLE_CHOICE_WITH_COMMENTS, 'em_validation_q_tip', '', 'Multiple choice with comments validation tip'],
            
            // === RANKING (R) ===
            // Global attributes
            [\Question::QT_R_RANKING, 'hidden', '0', '1'],
            [\Question::QT_R_RANKING, 'hide_tip', '0', '1'],
            [\Question::QT_R_RANKING, 'min_answers', '', '2'],
            [\Question::QT_R_RANKING, 'max_answers', '', '5'],
            // Language-specific attributes
            [\Question::QT_R_RANKING, 'em_validation_q_tip', '', 'Ranking validation tip'],
            
            // === HUGE FREE TEXT (U) ===
            // Global attributes
            [\Question::QT_U_HUGE_FREE_TEXT, 'hidden', '0', '1'],
            [\Question::QT_U_HUGE_FREE_TEXT, 'hide_tip', '0', '1'],
            [\Question::QT_U_HUGE_FREE_TEXT, 'maximum_chars', '', '1000'],
            [\Question::QT_U_HUGE_FREE_TEXT, 'display_rows', '10', '20'],
            // Language-specific attributes
            [\Question::QT_U_HUGE_FREE_TEXT, 'em_validation_q_tip', '', 'Huge text validation tip'],
            
            // === FILE UPLOAD (|) ===
            // Global attributes
            [\Question::QT_VERTICAL_FILE_UPLOAD, 'hidden', '0', '1'],
            [\Question::QT_VERTICAL_FILE_UPLOAD, 'hide_tip', '0', '1'],
            [\Question::QT_VERTICAL_FILE_UPLOAD, 'max_filesize', '', '2048'],
            [\Question::QT_VERTICAL_FILE_UPLOAD, 'allowed_filetypes', '', 'pdf,doc,docx'],
            // Language-specific attributes
            [\Question::QT_VERTICAL_FILE_UPLOAD, 'em_validation_q_tip', '', 'File upload validation tip'],
            
            // === EQUATION (*) ===
            // Global attributes
            [\Question::QT_ASTERISK_EQUATION, 'hidden', '0', '1'],
            [\Question::QT_ASTERISK_EQUATION, 'hide_tip', '0', '1'],
            // Language-specific attributes
            [\Question::QT_ASTERISK_EQUATION, 'em_validation_q_tip', '', 'Equation validation tip'],
            
            // === ARRAY NUMBERS (:) ===
            // Global attributes
            [\Question::QT_COLON_ARRAY_NUMBERS, 'hidden', '0', '1'],
            [\Question::QT_COLON_ARRAY_NUMBERS, 'hide_tip', '0', '1'],
            [\Question::QT_COLON_ARRAY_NUMBERS, 'random_order', '0', '1'],
            // Language-specific attributes
            [\Question::QT_COLON_ARRAY_NUMBERS, 'em_validation_q_tip', '', 'Array numbers validation tip'],
            
            // === ARRAY TEXT (;) ===
            // Global attributes
            [\Question::QT_SEMICOLON_ARRAY_TEXT, 'hidden', '0', '1'],
            [\Question::QT_SEMICOLON_ARRAY_TEXT, 'hide_tip', '0', '1'],
            [\Question::QT_SEMICOLON_ARRAY_TEXT, 'random_order', '0', '1'],
            // Language-specific attributes
            [\Question::QT_SEMICOLON_ARRAY_TEXT, 'em_validation_q_tip', '', 'Array text validation tip'],
        ];
    }
    
    /**
     * @dataProvider questionTypeAttributeProvider
     */
    public function testQuestionTypeAttributeExport($questionType, $attributeName, $defaultValue, $changedValue)
    {
        // Create a question group
        $group = new \QuestionGroup();
        $group->sid = $this->testSurveyId;
        $group->group_name = 'Test Group';
        $group->group_order = 1;
        $group->save();
        
        // Create a question of the specified type (alphanumeric only)
        // Convert special characters to letters for valid question codes
        $typeChar = str_replace(['*', ':', ';', '|', '!'], ['A', 'B', 'C', 'D', 'E'], $questionType);
        $questionCode = 'Q' . $typeChar . substr(md5($attributeName), 0, 6);
        $questionId = $this->createTestQuestion($this->testSurveyId, $group->gid, $questionCode, $questionType, 'Test Question for ' . $questionType);
        
        // Set the attribute based on whether it's global or language-specific
        if (QuestionAttributeLanguageManager::isGlobal($attributeName)) {
            // Global attribute
            $this->setGlobalAttribute($questionId, $attributeName, $changedValue);
        } else {
            // Language-specific attribute - set for both languages
            $this->setLanguageSpecificAttribute($questionId, $attributeName, 'en', $changedValue . ' (English)');
            $this->setLanguageSpecificAttribute($questionId, $attributeName, 'et', $changedValue . ' (Estonian)');
        }
        
        // Export questions
        $survey = \Survey::model()->findByPk($this->testSurveyId);

        // Use reflection to set the path property before export happens in constructor
        $exportClass = new \ReflectionClass('\tonisormisson\ls\structureimex\export\ExportQuestions');
        $export = $exportClass->newInstanceWithoutConstructor();
        $pathProperty = $exportClass->getProperty('path');
        $pathProperty->setAccessible(true);
        $pathProperty->setValue($export, \Yii::app()->runtimePath . '/');
        
        // Now call the constructor to trigger the export with correct path
        $constructor = $exportClass->getConstructor();
        $constructor->invoke($export, $survey);
        
        // Ensure export writer is properly closed
        if ($export->writer) {
            $export->writer->close();
        }
        
        // Get the filename and read the exported file
        $tempFile = $export->getFullFileName();
        $this->assertFileExists($tempFile, "Export file should exist");
        
        $reader = new \OpenSpout\Reader\XLSX\Reader();
        $reader->open($tempFile);
        $sheet = $reader->getSheetIterator()->current();
        
        // Find our question row
        $questionRow = null;
        foreach ($sheet->getRowIterator() as $row) {
            $cells = $row->getCells();
            if (count($cells) >= 3) {
                $type = $cells[0]->getValue();
                $code = $cells[2]->getValue();
                
                if ($type === 'Q' && $code === $questionCode) {
                    $questionRow = array_map(function($cell) { return $cell->getValue(); }, $cells);
                    break;
                }
            }
        }
        
        $reader->close();
        unlink($tempFile);
        
        // Verify we found the question
        $this->assertNotNull($questionRow, "Question $questionCode should be found in export");
        
        // Check attribute export based on type
        if (QuestionAttributeLanguageManager::isGlobal($attributeName)) {
            // Global attribute should be in column 12 (global options) for 2-language survey
            // Structure: type, subtype, code, value-en, help-en, script-en, value-et, help-et, script-et, relevance, mandatory, theme, options
            $globalOptions = isset($questionRow[12]) ? $questionRow[12] : '';
            $this->assertNotEmpty($globalOptions, "Global options should not be empty for global attribute $attributeName");
            
            $optionsArray = json_decode($globalOptions, true);
            $this->assertIsArray($optionsArray, "Global options should be valid JSON");
            $this->assertArrayHasKey($attributeName, $optionsArray, "Global attribute $attributeName should be exported");
            $this->assertEquals($changedValue, $optionsArray[$attributeName], "Global attribute value should match");
        } else {
            // Language-specific attribute should be in columns 13 and 14 for 2-language survey
            $enOptions = isset($questionRow[13]) ? $questionRow[13] : '';
            $etOptions = isset($questionRow[14]) ? $questionRow[14] : '';
            
            // Check English options
            $this->assertNotEmpty($enOptions, "English options should not be empty for language-specific attribute $attributeName");
            $enArray = json_decode($enOptions, true);
            $this->assertIsArray($enArray, "English options should be valid JSON");
            $this->assertArrayHasKey($attributeName, $enArray, "Language-specific attribute $attributeName should be in English options");
            $this->assertEquals($changedValue . ' (English)', $enArray[$attributeName], "English attribute value should match");
            
            // Check Estonian options
            $this->assertNotEmpty($etOptions, "Estonian options should not be empty for language-specific attribute $attributeName");
            $etArray = json_decode($etOptions, true);
            $this->assertIsArray($etArray, "Estonian options should be valid JSON");
            $this->assertArrayHasKey($attributeName, $etArray, "Language-specific attribute $attributeName should be in Estonian options");
            $this->assertEquals($changedValue . ' (Estonian)', $etArray[$attributeName], "Estonian attribute value should match");
        }
    }
    
    private function setLanguageSpecificAttribute($questionId, $attributeName, $language, $value)
    {
        // Delete existing attribute
        QuestionAttribute::model()->deleteAll([
            'condition' => 'qid = :qid AND attribute = :attr AND language = :lang',
            'params' => [':qid' => $questionId, ':attr' => $attributeName, ':lang' => $language]
        ]);
        
        // Create new attribute
        $attribute = new QuestionAttribute();
        $attribute->qid = $questionId;
        $attribute->attribute = $attributeName;
        $attribute->value = $value;
        $attribute->language = $language;
        
        $result = $attribute->save();
        if (!$result) {
            throw new \Exception("Failed to set language-specific attribute $attributeName ($language): " . print_r($attribute->getErrors(), true));
        }
    }
    
    private function setGlobalAttribute($questionId, $attributeName, $value)
    {
        // Delete existing attribute
        QuestionAttribute::model()->deleteAll([
            'condition' => 'qid = :qid AND attribute = :attr AND (language = \'\' OR language IS NULL)',
            'params' => [':qid' => $questionId, ':attr' => $attributeName]
        ]);
        
        // Create new attribute
        $attribute = new QuestionAttribute();
        $attribute->qid = $questionId;
        $attribute->attribute = $attributeName;
        $attribute->value = $value;
        $attribute->language = ''; // Global attributes use empty string
        
        $result = $attribute->save();
        if (!$result) {
            throw new \Exception("Failed to set global attribute $attributeName: " . print_r($attribute->getErrors(), true));
        }
    }
}
