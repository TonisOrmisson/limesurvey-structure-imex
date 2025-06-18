<?php

namespace tonisormisson\ls\structureimex\Tests\Unit;

use PHPUnit\Framework\TestCase;
use tonisormisson\ls\structureimex\validation\QuestionAttributeDefinition;

/**
 * Test the ExportQuestions integration with QuestionAttributeDefinition
 */
class ExportQuestionsDefinitionTest extends TestCase
{
    /**
     * Test that QuestionAttributeDefinition correctly identifies valid attributes
     */
    public function testValidAttributeIdentification()
    {
        // Test valid attributes for different question types
        $this->assertTrue(QuestionAttributeDefinition::isValidAttribute('T', 'hide_tip'));
        $this->assertTrue(QuestionAttributeDefinition::isValidAttribute('L', 'answer_order'));
        $this->assertTrue(QuestionAttributeDefinition::isValidAttribute('N', 'min_num_value_n'));
        
        // Test invalid attributes
        $this->assertFalse(QuestionAttributeDefinition::isValidAttribute('T', 'answer_order'));
        $this->assertFalse(QuestionAttributeDefinition::isValidAttribute('L', 'min_num_value_n'));
        $this->assertFalse(QuestionAttributeDefinition::isValidAttribute('N', 'answer_order'));
        
        // Test unknown question type
        $this->assertFalse(QuestionAttributeDefinition::isValidAttribute('UNKNOWN', 'hide_tip'));
    }

    /**
     * Test that default value detection works correctly
     */
    public function testDefaultValueDetection()
    {
        // Test default values should NOT be exported
        $this->assertFalse(QuestionAttributeDefinition::isNonDefaultValue('T', 'hide_tip', '0'));
        $this->assertFalse(QuestionAttributeDefinition::isNonDefaultValue('L', 'answer_order', 'normal'));
        $this->assertFalse(QuestionAttributeDefinition::isNonDefaultValue('N', 'min_num_value_n', ''));
        
        // Test non-default values SHOULD be exported
        $this->assertTrue(QuestionAttributeDefinition::isNonDefaultValue('T', 'hide_tip', '1'));
        $this->assertTrue(QuestionAttributeDefinition::isNonDefaultValue('L', 'answer_order', 'random'));
        $this->assertTrue(QuestionAttributeDefinition::isNonDefaultValue('N', 'min_num_value_n', '5'));
    }

    /**
     * Test the export logic scenario: only defined, non-default attributes should be exported
     */
    public function testExportLogicScenario()
    {
        // Simulate export logic for question type T with various attributes
        $simulatedQuestionAttributes = [
            // Valid attribute with default value - should NOT export
            ['name' => 'hide_tip', 'value' => '0'],
            // Valid attribute with non-default value - SHOULD export  
            ['name' => 'hide_tip', 'value' => '1'],
            // Valid attribute with default empty value - should NOT export
            ['name' => 'cssclass', 'value' => ''],
            // Valid attribute with non-default value - SHOULD export
            ['name' => 'cssclass', 'value' => 'custom-class'],
            // Invalid attribute for this type - should NOT export
            ['name' => 'answer_order', 'value' => 'random'],
            // Unknown attribute - should NOT export
            ['name' => 'unknown_attr', 'value' => 'some_value']
        ];
        
        $questionType = 'T';
        $exportedAttributes = [];
        
        // Simulate the export logic from ExportQuestions
        foreach ($simulatedQuestionAttributes as $attr) {
            $attributeName = $attr['name'];
            $attributeValue = $attr['value'];
            
            // Skip question_template (handled elsewhere)
            if ($attributeName === 'question_template') {
                continue;
            }
            
            // Only export attributes defined for this question type
            if (!QuestionAttributeDefinition::isValidAttribute($questionType, $attributeName)) {
                continue;
            }
            
            // Only export attributes with non-default values
            if (!QuestionAttributeDefinition::isNonDefaultValue($questionType, $attributeName, $attributeValue)) {
                continue;
            }
            
            $exportedAttributes[$attributeName] = $attributeValue;
        }
        
        // Verify results
        $this->assertArrayHasKey('hide_tip', $exportedAttributes);
        $this->assertEquals('1', $exportedAttributes['hide_tip']);
        
        $this->assertArrayHasKey('cssclass', $exportedAttributes);
        $this->assertEquals('custom-class', $exportedAttributes['cssclass']);
        
        // These should NOT be exported
        $this->assertArrayNotHasKey('answer_order', $exportedAttributes);
        $this->assertArrayNotHasKey('unknown_attr', $exportedAttributes);
        
        // Should have exactly 2 exported attributes
        $this->assertCount(2, $exportedAttributes);
    }

    /**
     * Test export logic for different question types
     */
    public function testExportLogicForDifferentQuestionTypes()
    {
        // Test L type (List Radio) specific attributes
        $this->assertTrue(QuestionAttributeDefinition::isValidAttribute('L', 'answer_order'));
        $this->assertTrue(QuestionAttributeDefinition::isNonDefaultValue('L', 'answer_order', 'random'));
        $this->assertFalse(QuestionAttributeDefinition::isNonDefaultValue('L', 'answer_order', 'normal'));
        
        // Test N type (Numerical) specific attributes  
        $this->assertTrue(QuestionAttributeDefinition::isValidAttribute('N', 'min_num_value_n'));
        $this->assertTrue(QuestionAttributeDefinition::isValidAttribute('N', 'max_num_value_n'));
        $this->assertTrue(QuestionAttributeDefinition::isNonDefaultValue('N', 'min_num_value_n', '1'));
        $this->assertFalse(QuestionAttributeDefinition::isNonDefaultValue('N', 'min_num_value_n', ''));
        
        // Test cross-contamination doesn't occur
        $this->assertFalse(QuestionAttributeDefinition::isValidAttribute('T', 'answer_order'));
        $this->assertFalse(QuestionAttributeDefinition::isValidAttribute('T', 'min_num_value_n'));
        $this->assertFalse(QuestionAttributeDefinition::isValidAttribute('L', 'min_num_value_n'));
    }
}