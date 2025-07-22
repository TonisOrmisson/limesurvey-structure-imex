<?php

namespace tonisormisson\ls\structureimex\Tests\Unit;

use tonisormisson\ls\structureimex\validation\MyQuestionAttribute;

/**
 * Unit tests for MyQuestionAttribute business logic
 * Tests validation rules, constants, and utility methods
 */
class MyQuestionAttributeTest extends BaseExportTest
{
    private MyQuestionAttribute $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new MyQuestionAttribute();
    }

    public function testConstants()
    {
        $this->assertEquals("hide_tip", MyQuestionAttribute::ATTRIBUTE_HIDE_TIP);
        $this->assertEquals("exclude_all_others", MyQuestionAttribute::ATTRIBUTE_EXCLUDE);
        $this->assertEquals("hidden", MyQuestionAttribute::ATTRIBUTE_HIDDEN);
        $this->assertEquals("text_input_width", MyQuestionAttribute::ATTRIBUTE_TEXT_INPUT_WIDTH);
        $this->assertEquals("answer_width", MyQuestionAttribute::ATTRIBUTE_ANSWER_WIDTH);
        $this->assertEquals("min_answers", MyQuestionAttribute::ATTRIBUTE_MIN_ANSWERS);
        $this->assertEquals("max_answers", MyQuestionAttribute::ATTRIBUTE_MAX_ANSWERS);
        $this->assertEquals("random_order", MyQuestionAttribute::ATTRIBUTE_RANDOM_ORDER);
        $this->assertEquals("min_num_value_n", MyQuestionAttribute::ATTRIBUTE_MIN_NUMERIC_VALUE);
        $this->assertEquals("max_num_value_n", MyQuestionAttribute::ATTRIBUTE_MAX_NUMERIC_VALUE);
        $this->assertEquals("num_value_int_only", MyQuestionAttribute::ATTRIBUTE_INTEGER_ONLY);
        $this->assertEquals("array_filter", MyQuestionAttribute::ATTRIBUTE_ARRAY_FILTER);
        $this->assertEquals("prefix", MyQuestionAttribute::ATTRIBUTE_PREFIX);
        $this->assertEquals("input_size", MyQuestionAttribute::ATTRIBUTE_INPUT_SIZE);
        $this->assertEquals("cssclass", MyQuestionAttribute::ATTRIBUTE_CSSCLASS);
        $this->assertEquals("em_validation_q", MyQuestionAttribute::ATTRIBUTE_EM_VALIDATION_Q);
        $this->assertEquals("em_validation_q_tip", MyQuestionAttribute::ATTRIBUTE_EM_VALIDATION_Q_TIP);
        $this->assertEquals("em_validation_sq", MyQuestionAttribute::ATTRIBUTE_EM_VALIDATION_SQ);
        $this->assertEquals("em_validation_sq_tip", MyQuestionAttribute::ATTRIBUTE_EM_VALIDATION_SQ_TIP);
        $this->assertEquals("maximum_chars", MyQuestionAttribute::ATTRIBUTE_MAXIMUM_CHARS);
    }

    public function testAttributeNames()
    {
        $attributeNames = $this->model->attributeNames();
        
        $this->assertIsArray($attributeNames);
        $this->assertContains(MyQuestionAttribute::ATTRIBUTE_HIDE_TIP, $attributeNames);
        $this->assertContains(MyQuestionAttribute::ATTRIBUTE_HIDDEN, $attributeNames);
        $this->assertContains(MyQuestionAttribute::ATTRIBUTE_MIN_ANSWERS, $attributeNames);
        $this->assertContains(MyQuestionAttribute::ATTRIBUTE_MAX_ANSWERS, $attributeNames);
        $this->assertContains(MyQuestionAttribute::ATTRIBUTE_CSSCLASS, $attributeNames);
        $this->assertContains(MyQuestionAttribute::ATTRIBUTE_MAXIMUM_CHARS, $attributeNames);
        
        $this->assertEquals(20, count($attributeNames));
    }

    public function testAttributeLabels()
    {
        $labels = $this->model->attributeLabels();
        
        $this->assertIsArray($labels);
        $this->assertEquals("Hide tip", $labels[MyQuestionAttribute::ATTRIBUTE_HIDE_TIP]);
        $this->assertEquals("Always hidden", $labels[MyQuestionAttribute::ATTRIBUTE_HIDDEN]);
        $this->assertEquals("Minimum answers", $labels[MyQuestionAttribute::ATTRIBUTE_MIN_ANSWERS]);
        $this->assertEquals("Maximum answers", $labels[MyQuestionAttribute::ATTRIBUTE_MAX_ANSWERS]);
        $this->assertEquals("CSS Class", $labels[MyQuestionAttribute::ATTRIBUTE_CSSCLASS]);
        $this->assertEquals("Maximum Characters", $labels[MyQuestionAttribute::ATTRIBUTE_MAXIMUM_CHARS]);
        $this->assertEquals("Text input width", $labels[MyQuestionAttribute::ATTRIBUTE_TEXT_INPUT_WIDTH]);
        $this->assertEquals("Random Order", $labels[MyQuestionAttribute::ATTRIBUTE_RANDOM_ORDER]);
        
        $this->assertArrayHasKey(MyQuestionAttribute::ATTRIBUTE_EXCLUDE, $labels);
        $this->assertArrayHasKey(MyQuestionAttribute::ATTRIBUTE_EM_VALIDATION_Q, $labels);
    }

    public function testAllowedValues()
    {
        $allowedValues = $this->model->allowedValues();
        
        $this->assertIsArray($allowedValues);
        $this->assertEquals("integer 0-1", $allowedValues[MyQuestionAttribute::ATTRIBUTE_HIDE_TIP]);
        $this->assertEquals("integer 0-1", $allowedValues[MyQuestionAttribute::ATTRIBUTE_HIDDEN]);
        $this->assertEquals("integer 1-12", $allowedValues[MyQuestionAttribute::ATTRIBUTE_TEXT_INPUT_WIDTH]);
        $this->assertEquals("integer 1-1000", $allowedValues[MyQuestionAttribute::ATTRIBUTE_MIN_ANSWERS]);
        $this->assertEquals("integer 1-1000", $allowedValues[MyQuestionAttribute::ATTRIBUTE_MAX_ANSWERS]);
        $this->assertEquals("string max 1024 chars", $allowedValues[MyQuestionAttribute::ATTRIBUTE_CSSCLASS]);
        $this->assertEquals("integer 1-1024", $allowedValues[MyQuestionAttribute::ATTRIBUTE_MAXIMUM_CHARS]);
        $this->assertEquals("string eg '1;2;3'", $allowedValues[MyQuestionAttribute::ATTRIBUTE_EXCLUDE]);
    }

    public function testFilterIntegersWithNullValue()
    {
        $this->model->hide_tip = null;
        $result = $this->model->filterIntegers('hide_tip');
        
        $this->assertTrue($result);
        $this->assertNull($this->model->hide_tip);
    }

    public function testFilterIntegersWithStringNumbers()
    {
        $this->model->hide_tip = "1";
        $result = $this->model->filterIntegers('hide_tip');
        
        $this->assertTrue($result);
        $this->assertSame(1, $this->model->hide_tip);
        
        $this->model->max_answers = "100";
        $result = $this->model->filterIntegers('max_answers');
        
        $this->assertTrue($result);
        $this->assertSame(100, $this->model->max_answers);
    }

    public function testFilterIntegersWithFloat()
    {
        $this->model->min_answers = 5.7;
        $result = $this->model->filterIntegers('min_answers');
        
        $this->assertTrue($result);
        $this->assertSame(5, $this->model->min_answers);
        
        $this->model->input_size = "12.9";
        $result = $this->model->filterIntegers('input_size');
        
        $this->assertTrue($result);
        $this->assertSame(12, $this->model->input_size);
    }

    public function testFilterIntegersWithZero()
    {
        $this->model->hide_tip = "0";
        $result = $this->model->filterIntegers('hide_tip');
        
        $this->assertTrue($result);
        $this->assertSame(0, $this->model->hide_tip);
        
        $this->model->random_order = 0;
        $result = $this->model->filterIntegers('random_order');
        
        $this->assertTrue($result);
        $this->assertSame(0, $this->model->random_order);
    }

    public function testFilterIntegersWithNegativeNumbers()
    {
        $this->model->min_num_value_n = "-10";
        $result = $this->model->filterIntegers('min_num_value_n');
        
        $this->assertTrue($result);
        $this->assertSame(-10, $this->model->min_num_value_n);
    }

    public function testFilterIntegersWithInvalidString()
    {
        $this->model->hide_tip = "abc";
        $result = $this->model->filterIntegers('hide_tip');
        
        $this->assertTrue($result);
        $this->assertSame(0, $this->model->hide_tip);
        
        $this->model->max_answers = "not_a_number";
        $result = $this->model->filterIntegers('max_answers');
        
        $this->assertTrue($result);
        $this->assertSame(0, $this->model->max_answers);
    }

    public function testRulesStructure()
    {
        $rules = $this->model->rules();
        
        $this->assertIsArray($rules);
        $this->assertNotEmpty($rules);
        
        $hasFilterIntegersRule = false;
        $hasNumericalRule = false;
        $hasLengthRule = false;
        
        foreach ($rules as $rule) {
            $this->assertIsArray($rule);
            $this->assertGreaterThanOrEqual(2, count($rule));
            
            if (isset($rule[1]) && $rule[1] === 'filterIntegers') {
                $hasFilterIntegersRule = true;
            }
            if (isset($rule[1]) && $rule[1] === 'numerical') {
                $hasNumericalRule = true;
            }
            if (isset($rule[1]) && $rule[1] === 'length') {
                $hasLengthRule = true;
            }
        }
        
        $this->assertTrue($hasFilterIntegersRule);
        $this->assertTrue($hasNumericalRule);
        $this->assertTrue($hasLengthRule);
    }

    public function testIntegerAttributesHaveFilterRule()
    {
        $rules = $this->model->rules();
        $integerAttributes = [
            MyQuestionAttribute::ATTRIBUTE_HIDDEN,
            MyQuestionAttribute::ATTRIBUTE_HIDE_TIP,
            MyQuestionAttribute::ATTRIBUTE_TEXT_INPUT_WIDTH,
            MyQuestionAttribute::ATTRIBUTE_MIN_ANSWERS,
            MyQuestionAttribute::ATTRIBUTE_MAX_ANSWERS,
            MyQuestionAttribute::ATTRIBUTE_RANDOM_ORDER,
            MyQuestionAttribute::ATTRIBUTE_MIN_NUMERIC_VALUE,
            MyQuestionAttribute::ATTRIBUTE_MAX_NUMERIC_VALUE,
            MyQuestionAttribute::ATTRIBUTE_INTEGER_ONLY,
            MyQuestionAttribute::ATTRIBUTE_INPUT_SIZE
        ];
        
        $filterAttributes = [];
        foreach ($rules as $rule) {
            if (isset($rule[1]) && $rule[1] === 'filterIntegers') {
                $filterAttributes[] = $rule[0];
            }
        }
        
        foreach ($integerAttributes as $attr) {
            $this->assertContains($attr, $filterAttributes, "Integer attribute '$attr' should have filterIntegers rule");
        }
    }

    public function testStringAttributesHaveLengthRule()
    {
        $rules = $this->model->rules();
        $stringAttributes = [
            MyQuestionAttribute::ATTRIBUTE_ARRAY_FILTER,
            MyQuestionAttribute::ATTRIBUTE_PREFIX,
            MyQuestionAttribute::ATTRIBUTE_EXCLUDE,
            MyQuestionAttribute::ATTRIBUTE_CSSCLASS,
            MyQuestionAttribute::ATTRIBUTE_EM_VALIDATION_Q,
            MyQuestionAttribute::ATTRIBUTE_EM_VALIDATION_Q_TIP,
            MyQuestionAttribute::ATTRIBUTE_EM_VALIDATION_SQ,
            MyQuestionAttribute::ATTRIBUTE_EM_VALIDATION_SQ_TIP
        ];
        
        $lengthAttributes = [];
        foreach ($rules as $rule) {
            if (isset($rule[1]) && $rule[1] === 'length') {
                $lengthAttributes[] = $rule[0];
            }
        }
        
        foreach ($stringAttributes as $attr) {
            $this->assertContains($attr, $lengthAttributes, "String attribute '$attr' should have length rule");
        }
    }

    public function testMostAttributesHaveLabels()
    {
        $attributeNames = $this->model->attributeNames();
        $labels = $this->model->attributeLabels();
        
        $missingLabels = [];
        foreach ($attributeNames as $attributeName) {
            if (!array_key_exists($attributeName, $labels)) {
                $missingLabels[] = $attributeName;
            } elseif (empty($labels[$attributeName])) {
                $this->fail("Label for '$attributeName' should not be empty");
            }
        }
        
        $this->assertLessThanOrEqual(2, count($missingLabels), 
            "Most attributes should have labels. Missing labels for: " . implode(', ', $missingLabels));
    }

    public function testMostAttributesHaveAllowedValues()
    {
        $attributeNames = $this->model->attributeNames();
        $allowedValues = $this->model->allowedValues();
        
        $missingAllowedValues = [];
        foreach ($attributeNames as $attributeName) {
            if (!array_key_exists($attributeName, $allowedValues)) {
                $missingAllowedValues[] = $attributeName;
            } elseif (empty($allowedValues[$attributeName])) {
                $this->fail("Allowed values for '$attributeName' should not be empty");
            }
        }
        
        $this->assertLessThanOrEqual(2, count($missingAllowedValues), 
            "Most attributes should have allowed values documentation. Missing for: " . implode(', ', $missingAllowedValues));
    }

    public function testModelPropertiesExist()
    {
        $attributeNames = $this->model->attributeNames();
        
        foreach ($attributeNames as $attributeName) {
            $this->assertTrue(property_exists($this->model, $attributeName), "Property '$attributeName' should exist on the model");
        }
    }

    public function testModelInheritance()
    {
        $this->assertInstanceOf(\CModel::class, $this->model);
    }
}