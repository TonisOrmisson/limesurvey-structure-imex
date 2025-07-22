<?php

namespace tonisormisson\ls\structureimex\Tests\Unit;

use PHPUnit\Framework\TestCase;
use tonisormisson\ls\structureimex\validation\QuestionAttributeLanguageManager;

/**
 * Unit tests for QuestionAttributeLanguageManager business logic
 * Tests the classification of question attributes as global vs language-specific
 */
class QuestionAttributeLanguageManagerTest extends TestCase
{
    public function testIsLanguageSpecificForKnownLanguageSpecificAttributes()
    {
        // Test known language-specific attributes
        $this->assertTrue(QuestionAttributeLanguageManager::isLanguageSpecific('prefix'));
        $this->assertTrue(QuestionAttributeLanguageManager::isLanguageSpecific('suffix'));
        $this->assertTrue(QuestionAttributeLanguageManager::isLanguageSpecific('other_replace_text'));
        $this->assertTrue(QuestionAttributeLanguageManager::isLanguageSpecific('em_validation_q_tip'));
        $this->assertTrue(QuestionAttributeLanguageManager::isLanguageSpecific('validation_message'));
        $this->assertTrue(QuestionAttributeLanguageManager::isLanguageSpecific('time_limit_message'));
        $this->assertTrue(QuestionAttributeLanguageManager::isLanguageSpecific('slider_min_text'));
        $this->assertTrue(QuestionAttributeLanguageManager::isLanguageSpecific('slider_max_text'));
    }

    public function testIsLanguageSpecificForKnownGlobalAttributes()
    {
        // Test known global attributes should return false
        $this->assertFalse(QuestionAttributeLanguageManager::isLanguageSpecific('hide_tip'));
        $this->assertFalse(QuestionAttributeLanguageManager::isLanguageSpecific('hidden'));
        $this->assertFalse(QuestionAttributeLanguageManager::isLanguageSpecific('cssclass'));
        $this->assertFalse(QuestionAttributeLanguageManager::isLanguageSpecific('mandatory'));
        $this->assertFalse(QuestionAttributeLanguageManager::isLanguageSpecific('numbers_only'));
        $this->assertFalse(QuestionAttributeLanguageManager::isLanguageSpecific('min_answers'));
        $this->assertFalse(QuestionAttributeLanguageManager::isLanguageSpecific('max_answers'));
        $this->assertFalse(QuestionAttributeLanguageManager::isLanguageSpecific('slider_min'));
        $this->assertFalse(QuestionAttributeLanguageManager::isLanguageSpecific('slider_max'));
    }

    public function testIsLanguageSpecificForUnknownAttributes()
    {
        // Unknown attributes should default to global (false) for safety
        $this->assertFalse(QuestionAttributeLanguageManager::isLanguageSpecific('unknown_attribute'));
        $this->assertFalse(QuestionAttributeLanguageManager::isLanguageSpecific('custom_plugin_attr'));
        $this->assertFalse(QuestionAttributeLanguageManager::isLanguageSpecific('theme_specific_option'));
    }

    public function testIsGlobalIsInverseOfIsLanguageSpecific()
    {
        // Test that isGlobal is exactly the inverse of isLanguageSpecific
        $testAttributes = [
            'prefix', // language-specific
            'suffix', // language-specific  
            'hide_tip', // global
            'mandatory', // global
            'unknown_attr', // unknown (defaults to global)
        ];

        foreach ($testAttributes as $attribute) {
            $isLanguageSpecific = QuestionAttributeLanguageManager::isLanguageSpecific($attribute);
            $isGlobal = QuestionAttributeLanguageManager::isGlobal($attribute);
            
            $this->assertEquals(!$isLanguageSpecific, $isGlobal, 
                "isGlobal should be the inverse of isLanguageSpecific for attribute: $attribute");
        }
    }

    public function testFilterGlobalAttributes()
    {
        $attributes = [
            'prefix' => 'test prefix', // language-specific
            'suffix' => 'test suffix', // language-specific
            'hide_tip' => '1', // global
            'mandatory' => 'Y', // global
            'em_validation_q_tip' => 'validation tip', // language-specific
            'numbers_only' => '1', // global
            'unknown_attr' => 'value', // unknown (defaults to global)
        ];

        $globalAttributes = QuestionAttributeLanguageManager::filterGlobalAttributes('T', $attributes);

        $expectedGlobal = [
            'hide_tip' => '1',
            'mandatory' => 'Y', 
            'numbers_only' => '1',
            'unknown_attr' => 'value',
        ];

        $this->assertEquals($expectedGlobal, $globalAttributes);
    }

    public function testFilterLanguageSpecificAttributes()
    {
        $attributes = [
            'prefix' => 'test prefix', // language-specific
            'suffix' => 'test suffix', // language-specific
            'hide_tip' => '1', // global
            'mandatory' => 'Y', // global
            'em_validation_q_tip' => 'validation tip', // language-specific
            'numbers_only' => '1', // global
            'unknown_attr' => 'value', // unknown (defaults to global)
        ];

        $languageAttributes = QuestionAttributeLanguageManager::filterLanguageSpecificAttributes('T', $attributes);

        $expectedLanguageSpecific = [
            'prefix' => 'test prefix',
            'suffix' => 'test suffix',
            'em_validation_q_tip' => 'validation tip',
        ];

        $this->assertEquals($expectedLanguageSpecific, $languageAttributes);
    }

    public function testSeparateAttributes()
    {
        $attributes = [
            'prefix' => 'test prefix', // language-specific
            'suffix' => 'test suffix', // language-specific
            'hide_tip' => '1', // global
            'mandatory' => 'Y', // global
            'validation_message' => 'Please enter a valid value', // language-specific
            'min_answers' => '2', // global
            'custom_unknown' => 'some value', // unknown (defaults to global)
        ];

        $separated = QuestionAttributeLanguageManager::separateAttributes($attributes);

        $expectedGlobal = [
            'hide_tip' => '1',
            'mandatory' => 'Y',
            'min_answers' => '2',
            'custom_unknown' => 'some value',
        ];

        $expectedLanguageSpecific = [
            'prefix' => 'test prefix',
            'suffix' => 'test suffix',
            'validation_message' => 'Please enter a valid value',
        ];

        $this->assertArrayHasKey('global', $separated);
        $this->assertArrayHasKey('language_specific', $separated);
        $this->assertEquals($expectedGlobal, $separated['global']);
        $this->assertEquals($expectedLanguageSpecific, $separated['language_specific']);
    }

    public function testSeparateAttributesWithEmptyInput()
    {
        $separated = QuestionAttributeLanguageManager::separateAttributes([]);

        $this->assertEquals([], $separated['global']);
        $this->assertEquals([], $separated['language_specific']);
    }

    public function testGetGlobalAttributeNames()
    {
        $globalNames = QuestionAttributeLanguageManager::getGlobalAttributeNames();

        $this->assertIsArray($globalNames);
        $this->assertNotEmpty($globalNames);
        
        // Test some known global attributes are in the list
        $this->assertContains('hide_tip', $globalNames);
        $this->assertContains('mandatory', $globalNames);
        $this->assertContains('cssclass', $globalNames);
        $this->assertContains('numbers_only', $globalNames);
        $this->assertContains('min_answers', $globalNames);
        $this->assertContains('max_answers', $globalNames);
    }

    public function testGetLanguageSpecificAttributeNames()
    {
        $languageNames = QuestionAttributeLanguageManager::getLanguageSpecificAttributeNames();

        $this->assertIsArray($languageNames);
        $this->assertNotEmpty($languageNames);
        
        // Test some known language-specific attributes are in the list
        $this->assertContains('prefix', $languageNames);
        $this->assertContains('suffix', $languageNames);
        $this->assertContains('other_replace_text', $languageNames);
        $this->assertContains('em_validation_q_tip', $languageNames);
        $this->assertContains('validation_message', $languageNames);
        $this->assertContains('time_limit_message', $languageNames);
    }

    public function testNoOverlapBetweenGlobalAndLanguageSpecificAttributes()
    {
        $globalNames = QuestionAttributeLanguageManager::getGlobalAttributeNames();
        $languageNames = QuestionAttributeLanguageManager::getLanguageSpecificAttributeNames();

        // Check that no attribute appears in both lists
        $overlap = array_intersect($globalNames, $languageNames);
        
        $this->assertEmpty($overlap, 
            'No attributes should appear in both global and language-specific lists. Found overlapping attributes: ' . 
            implode(', ', $overlap));
    }

    public function testBusinessLogicConsistency()
    {
        // Test that all attributes in getGlobalAttributeNames() are correctly classified as global
        $globalNames = QuestionAttributeLanguageManager::getGlobalAttributeNames();
        
        foreach ($globalNames as $attributeName) {
            $this->assertTrue(QuestionAttributeLanguageManager::isGlobal($attributeName), 
                "Attribute '$attributeName' is in global list but isGlobal() returns false");
            $this->assertFalse(QuestionAttributeLanguageManager::isLanguageSpecific($attributeName), 
                "Attribute '$attributeName' is in global list but isLanguageSpecific() returns true");
        }

        // Test that all attributes in getLanguageSpecificAttributeNames() are correctly classified as language-specific
        $languageNames = QuestionAttributeLanguageManager::getLanguageSpecificAttributeNames();
        
        foreach ($languageNames as $attributeName) {
            $this->assertTrue(QuestionAttributeLanguageManager::isLanguageSpecific($attributeName), 
                "Attribute '$attributeName' is in language-specific list but isLanguageSpecific() returns false");
            $this->assertFalse(QuestionAttributeLanguageManager::isGlobal($attributeName), 
                "Attribute '$attributeName' is in language-specific list but isGlobal() returns true");
        }
    }

    public function testRealWorldAttributeClassification()
    {
        // Test classification of common real-world attributes used in LimeSurvey surveys
        
        // Display-related global attributes
        $this->assertTrue(QuestionAttributeLanguageManager::isGlobal('hide_tip'));
        $this->assertTrue(QuestionAttributeLanguageManager::isGlobal('display_columns'));
        $this->assertTrue(QuestionAttributeLanguageManager::isGlobal('text_input_width'));
        
        // Logic-related global attributes
        $this->assertTrue(QuestionAttributeLanguageManager::isGlobal('mandatory'));
        $this->assertTrue(QuestionAttributeLanguageManager::isGlobal('other'));
        $this->assertTrue(QuestionAttributeLanguageManager::isGlobal('random_order'));
        
        // Validation-related global attributes
        $this->assertTrue(QuestionAttributeLanguageManager::isGlobal('numbers_only'));
        $this->assertTrue(QuestionAttributeLanguageManager::isGlobal('min_answers'));
        $this->assertTrue(QuestionAttributeLanguageManager::isGlobal('max_answers'));
        
        // User-facing text (language-specific attributes)
        $this->assertTrue(QuestionAttributeLanguageManager::isLanguageSpecific('prefix'));
        $this->assertTrue(QuestionAttributeLanguageManager::isLanguageSpecific('suffix'));
        $this->assertTrue(QuestionAttributeLanguageManager::isLanguageSpecific('other_replace_text'));
        
        // Validation messages (language-specific)
        $this->assertTrue(QuestionAttributeLanguageManager::isLanguageSpecific('em_validation_q_tip'));
        $this->assertTrue(QuestionAttributeLanguageManager::isLanguageSpecific('validation_message'));
        
        // Timer messages (language-specific)
        $this->assertTrue(QuestionAttributeLanguageManager::isLanguageSpecific('time_limit_message'));
        $this->assertTrue(QuestionAttributeLanguageManager::isLanguageSpecific('time_limit_warning_message'));
    }
}