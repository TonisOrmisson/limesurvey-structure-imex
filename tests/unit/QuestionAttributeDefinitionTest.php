<?php

namespace tonisormisson\ls\structureimex\Tests\Unit;

use PHPUnit\Framework\TestCase;
use tonisormisson\ls\structureimex\validation\QuestionAttributeDefinition;

/**
 * Test the QuestionAttributeDefinition class
 */
class QuestionAttributeDefinitionTest extends TestCase
{
    /**
     * Test that supported question types are returned
     */
    public function testGetSupportedQuestionTypes()
    {
        $supportedTypes = QuestionAttributeDefinition::getSupportedQuestionTypes();
        
        $this->assertIsArray($supportedTypes);
        $this->assertNotEmpty($supportedTypes);
        $this->assertContains('T', $supportedTypes, 'Should support Long free text');
        $this->assertContains('L', $supportedTypes, 'Should support List (Radio)');
        $this->assertContains('N', $supportedTypes, 'Should support Numerical');
    }

    /**
     * Test getting attributes for valid question types
     */
    public function testGetAttributesForQuestionType()
    {
        // Test Long free text (T)
        $tAttributes = QuestionAttributeDefinition::getAttributesForQuestionType('T');
        $this->assertIsArray($tAttributes);
        $this->assertNotEmpty($tAttributes);
        $this->assertArrayHasKey('hide_tip', $tAttributes);
        $this->assertArrayHasKey('hidden', $tAttributes);
        $this->assertArrayHasKey('cssclass', $tAttributes);
        
        // Test List Radio (L)
        $lAttributes = QuestionAttributeDefinition::getAttributesForQuestionType('L');
        $this->assertIsArray($lAttributes);
        $this->assertNotEmpty($lAttributes);
        $this->assertArrayHasKey('hide_tip', $lAttributes);
        $this->assertArrayHasKey('answer_order', $lAttributes);
        
        // Test Numerical (N)
        $nAttributes = QuestionAttributeDefinition::getAttributesForQuestionType('N');
        $this->assertIsArray($nAttributes);
        $this->assertNotEmpty($nAttributes);
        $this->assertArrayHasKey('hide_tip', $nAttributes);
        $this->assertArrayHasKey('min_answers', $nAttributes);
        $this->assertArrayHasKey('max_answers', $nAttributes);
    }

    /**
     * Test getting attributes for invalid question type
     */
    public function testGetAttributesForInvalidQuestionType()
    {
        $attributes = QuestionAttributeDefinition::getAttributesForQuestionType('INVALID');
        $this->assertIsArray($attributes);
        $this->assertEmpty($attributes);
    }

    /**
     * Test getting default values
     */
    public function testGetDefaultValue()
    {
        // Test known defaults
        $this->assertEquals('0', QuestionAttributeDefinition::getDefaultValue('T', 'hide_tip'));
        $this->assertEquals('0', QuestionAttributeDefinition::getDefaultValue('L', 'hidden'));
        $this->assertEquals('normal', QuestionAttributeDefinition::getDefaultValue('L', 'answer_order'));
        $this->assertEquals('', QuestionAttributeDefinition::getDefaultValue('N', 'min_answers'));
        
        // Test unknown attribute
        $this->assertNull(QuestionAttributeDefinition::getDefaultValue('T', 'unknown_attribute'));
        
        // Test unknown question type
        $this->assertNull(QuestionAttributeDefinition::getDefaultValue('INVALID', 'hide_tip'));
    }

    /**
     * Test checking if attribute is valid for question type
     */
    public function testIsValidAttribute()
    {
        // Test valid attributes
        $this->assertTrue(QuestionAttributeDefinition::isValidAttribute('T', 'hide_tip'));
        $this->assertTrue(QuestionAttributeDefinition::isValidAttribute('L', 'answer_order'));
        $this->assertTrue(QuestionAttributeDefinition::isValidAttribute('N', 'min_answers'));
        
        // Test invalid attributes
        $this->assertFalse(QuestionAttributeDefinition::isValidAttribute('T', 'unknown_attribute'));
        $this->assertFalse(QuestionAttributeDefinition::isValidAttribute('INVALID', 'hide_tip'));
        
        // Test attributes that exist for one type but not another
        $this->assertTrue(QuestionAttributeDefinition::isValidAttribute('L', 'answer_order'));
        $this->assertFalse(QuestionAttributeDefinition::isValidAttribute('T', 'answer_order'));
    }

    /**
     * Test getting attribute names for question type
     */
    public function testGetAttributeNames()
    {
        $tAttributeNames = QuestionAttributeDefinition::getAttributeNames('T');
        $this->assertIsArray($tAttributeNames);
        $this->assertContains('hide_tip', $tAttributeNames);
        $this->assertContains('hidden', $tAttributeNames);
        $this->assertContains('cssclass', $tAttributeNames);
        
        $lAttributeNames = QuestionAttributeDefinition::getAttributeNames('L');
        $this->assertIsArray($lAttributeNames);
        $this->assertContains('hide_tip', $lAttributeNames);
        $this->assertContains('answer_order', $lAttributeNames);
        
        // Test invalid question type
        $invalidAttributeNames = QuestionAttributeDefinition::getAttributeNames('INVALID');
        $this->assertIsArray($invalidAttributeNames);
        $this->assertEmpty($invalidAttributeNames);
    }

    /**
     * Test checking if value is non-default
     */
    public function testIsNonDefaultValue()
    {
        // Test default values (should return false - don't export)
        $this->assertFalse(QuestionAttributeDefinition::isNonDefaultValue('T', 'hide_tip', '0'));
        $this->assertFalse(QuestionAttributeDefinition::isNonDefaultValue('L', 'answer_order', 'normal'));
        $this->assertFalse(QuestionAttributeDefinition::isNonDefaultValue('N', 'min_answers', ''));
        
        // Test non-default values (should return true - do export)
        $this->assertTrue(QuestionAttributeDefinition::isNonDefaultValue('T', 'hide_tip', '1'));
        $this->assertTrue(QuestionAttributeDefinition::isNonDefaultValue('L', 'answer_order', 'random'));
        $this->assertTrue(QuestionAttributeDefinition::isNonDefaultValue('N', 'min_answers', '5'));
        
        // Test empty string vs null handling
        $this->assertFalse(QuestionAttributeDefinition::isNonDefaultValue('T', 'cssclass', ''));
        $this->assertFalse(QuestionAttributeDefinition::isNonDefaultValue('T', 'cssclass', null));
        $this->assertTrue(QuestionAttributeDefinition::isNonDefaultValue('T', 'cssclass', 'custom-class'));
        
        // Test unknown attribute (should return false - don't export)
        $this->assertFalse(QuestionAttributeDefinition::isNonDefaultValue('T', 'unknown_attribute', 'value'));
    }

    /**
     * Test attribute value validation
     */
    public function testValidateAttributeValue()
    {
        // Test switch attributes
        $this->assertTrue(QuestionAttributeDefinition::validateAttributeValue('T', 'hide_tip', '0'));
        $this->assertTrue(QuestionAttributeDefinition::validateAttributeValue('T', 'hide_tip', '1'));
        $this->assertFalse(QuestionAttributeDefinition::validateAttributeValue('T', 'hide_tip', '2'));
        $this->assertFalse(QuestionAttributeDefinition::validateAttributeValue('T', 'hide_tip', 'yes'));
        
        // Test integer attributes
        $this->assertTrue(QuestionAttributeDefinition::validateAttributeValue('N', 'min_answers', ''));
        $this->assertTrue(QuestionAttributeDefinition::validateAttributeValue('N', 'min_answers', '5'));
        $this->assertTrue(QuestionAttributeDefinition::validateAttributeValue('N', 'min_answers', '0'));
        $this->assertFalse(QuestionAttributeDefinition::validateAttributeValue('N', 'min_answers', '5.5'));
        $this->assertFalse(QuestionAttributeDefinition::validateAttributeValue('N', 'min_answers', 'text'));
        
        // Test singleselect attributes
        $this->assertTrue(QuestionAttributeDefinition::validateAttributeValue('L', 'answer_order', 'normal'));
        $this->assertTrue(QuestionAttributeDefinition::validateAttributeValue('L', 'answer_order', 'random'));
        $this->assertTrue(QuestionAttributeDefinition::validateAttributeValue('L', 'answer_order', 'alphabetical'));
        $this->assertFalse(QuestionAttributeDefinition::validateAttributeValue('L', 'answer_order', 'invalid'));
        
        // Test text attributes (should always be valid)
        $this->assertTrue(QuestionAttributeDefinition::validateAttributeValue('T', 'cssclass', 'any-text'));
        $this->assertTrue(QuestionAttributeDefinition::validateAttributeValue('T', 'cssclass', ''));
        $this->assertTrue(QuestionAttributeDefinition::validateAttributeValue('T', 'cssclass', 'with spaces and symbols!@#'));
        
        // Test unknown attribute (should return false)
        $this->assertFalse(QuestionAttributeDefinition::validateAttributeValue('T', 'unknown_attribute', 'value'));
    }

    /**
     * Test that all attribute definitions have required fields
     */
    public function testAttributeDefinitionStructure()
    {
        $supportedTypes = QuestionAttributeDefinition::getSupportedQuestionTypes();
        
        foreach ($supportedTypes as $questionType) {
            $attributes = QuestionAttributeDefinition::getAttributesForQuestionType($questionType);
            
            foreach ($attributes as $attributeName => $definition) {
                $this->assertIsArray($definition, "Attribute '{$attributeName}' for type '{$questionType}' must be an array");
                $this->assertArrayHasKey('default', $definition, "Attribute '{$attributeName}' for type '{$questionType}' must have 'default' key");
                $this->assertArrayHasKey('type', $definition, "Attribute '{$attributeName}' for type '{$questionType}' must have 'type' key");
                $this->assertArrayHasKey('category', $definition, "Attribute '{$attributeName}' for type '{$questionType}' must have 'category' key");
                
                // Validate that 'default' is a string
                $this->assertIsString($definition['default'], "Default value for '{$attributeName}' in type '{$questionType}' must be a string");
                
                // Validate that 'type' is a known type
                $validTypes = ['switch', 'integer', 'singleselect', 'text', 'textarea', 'columns'];
                $this->assertContains($definition['type'], $validTypes, "Type '{$definition['type']}' for attribute '{$attributeName}' in type '{$questionType}' must be valid");
                
                // If type is switch or singleselect, must have options
                if (in_array($definition['type'], ['switch', 'singleselect'])) {
                    $this->assertArrayHasKey('options', $definition, "Attribute '{$attributeName}' of type '{$definition['type']}' must have 'options'");
                    $this->assertIsArray($definition['options'], "Options for '{$attributeName}' must be an array");
                    $this->assertNotEmpty($definition['options'], "Options for '{$attributeName}' cannot be empty");
                }
            }
        }
    }

    /**
     * Test that common attributes exist across question types
     */
    public function testCommonAttributesExistAcrossTypes()
    {
        $supportedTypes = QuestionAttributeDefinition::getSupportedQuestionTypes();
        
        // These attributes should exist for all question types
        $commonAttributes = ['hide_tip', 'hidden', 'cssclass', 'random_group', 'em_validation_q', 'em_validation_q_tip'];
        
        foreach ($supportedTypes as $questionType) {
            $attributeNames = QuestionAttributeDefinition::getAttributeNames($questionType);
            
            foreach ($commonAttributes as $commonAttribute) {
                $this->assertContains($commonAttribute, $attributeNames, 
                    "Common attribute '{$commonAttribute}' should exist for question type '{$questionType}'");
            }
        }
    }

    /**
     * Test that hide_tip has consistent default across all types
     */
    public function testHideTipConsistentDefault()
    {
        $supportedTypes = QuestionAttributeDefinition::getSupportedQuestionTypes();
        
        foreach ($supportedTypes as $questionType) {
            $hideTipDefault = QuestionAttributeDefinition::getDefaultValue($questionType, 'hide_tip');
            $this->assertEquals('0', $hideTipDefault, 
                "hide_tip should have default '0' for all question types, got '{$hideTipDefault}' for type '{$questionType}'");
        }
    }
}