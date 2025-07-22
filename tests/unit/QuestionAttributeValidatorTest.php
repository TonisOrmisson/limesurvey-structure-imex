<?php

namespace tonisormisson\ls\structureimex\Tests\Unit;

use tonisormisson\ls\structureimex\validation\QuestionAttributeValidator;

/**
 * Unit tests for QuestionAttributeValidator business logic
 * Tests the validation logic for different input types without database dependencies
 */
class QuestionAttributeValidatorTest extends BaseExportTest
{
    private QuestionAttributeValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create actual validator with the mock survey from BaseExportTest
        $this->validator = new QuestionAttributeValidator($this->mockSurvey);
    }

    public function testIntegerValidation()
    {
        $attributeDefinition = ['inputtype' => 'integer'];
        
        // Test valid integers
        $this->assertTrue($this->validateAttributeValueWithMockedDefinition('test_attr', '123', $attributeDefinition));
        $this->assertTrue($this->validateAttributeValueWithMockedDefinition('test_attr', 456, $attributeDefinition));
        $this->assertTrue($this->validateAttributeValueWithMockedDefinition('test_attr', '0', $attributeDefinition));
        $this->assertTrue($this->validateAttributeValueWithMockedDefinition('test_attr', '-10', $attributeDefinition));
        
        // Test invalid integers
        $this->assertFalse($this->validateAttributeValueWithMockedDefinition('test_attr', '12.5', $attributeDefinition));
        $this->assertFalse($this->validateAttributeValueWithMockedDefinition('test_attr', 'abc', $attributeDefinition));
        $this->assertFalse($this->validateAttributeValueWithMockedDefinition('test_attr', '12abc', $attributeDefinition));
        $this->assertFalse($this->validateAttributeValueWithMockedDefinition('test_attr', '', $attributeDefinition));
    }

    public function testIntegerValidationWithMinMax()
    {
        $attributeDefinition = [
            'inputtype' => 'integer',
            'min' => 1,
            'max' => 10
        ];
        
        // Test valid range
        $this->assertTrue($this->validateAttributeValueWithMockedDefinition('test_attr', '5', $attributeDefinition));
        $this->assertTrue($this->validateAttributeValueWithMockedDefinition('test_attr', '1', $attributeDefinition));
        $this->assertTrue($this->validateAttributeValueWithMockedDefinition('test_attr', '10', $attributeDefinition));
        
        // Test out of range
        $this->assertFalse($this->validateAttributeValueWithMockedDefinition('test_attr', '0', $attributeDefinition));
        $this->assertFalse($this->validateAttributeValueWithMockedDefinition('test_attr', '11', $attributeDefinition));
        $this->assertFalse($this->validateAttributeValueWithMockedDefinition('test_attr', '-5', $attributeDefinition));
        
        // Verify error messages using the actual validator instance
        $this->validator->clearValidationErrors();
        $this->validator->validateAttributeValueWithDefinition('test_attr', '0', $attributeDefinition);
        $errors = $this->validator->getValidationErrors();
        $this->assertArrayHasKey('test_attr', $errors);
        $this->assertContains("Attribute 'test_attr' must be at least 1", $errors['test_attr']);
    }

    public function testDecimalValidation()
    {
        $attributeDefinition = ['inputtype' => 'decimal'];
        
        // Test valid decimals
        $this->assertTrue($this->validateAttributeValueWithMockedDefinition('test_attr', '123.45', $attributeDefinition));
        $this->assertTrue($this->validateAttributeValueWithMockedDefinition('test_attr', '0.5', $attributeDefinition));
        $this->assertTrue($this->validateAttributeValueWithMockedDefinition('test_attr', '100', $attributeDefinition));
        $this->assertTrue($this->validateAttributeValueWithMockedDefinition('test_attr', '-10.5', $attributeDefinition));
        
        // Test invalid decimals
        $this->assertFalse($this->validateAttributeValueWithMockedDefinition('test_attr', 'abc', $attributeDefinition));
        $this->assertFalse($this->validateAttributeValueWithMockedDefinition('test_attr', '12.5abc', $attributeDefinition));
        $this->assertFalse($this->validateAttributeValueWithMockedDefinition('test_attr', '', $attributeDefinition));
    }

    public function testBooleanValidation()
    {
        $attributeDefinition = ['inputtype' => 'boolean'];
        
        // Test valid boolean values
        $this->assertTrue($this->validateAttributeValueWithMockedDefinition('test_attr', '0', $attributeDefinition));
        $this->assertTrue($this->validateAttributeValueWithMockedDefinition('test_attr', '1', $attributeDefinition));
        $this->assertTrue($this->validateAttributeValueWithMockedDefinition('test_attr', 0, $attributeDefinition));
        $this->assertTrue($this->validateAttributeValueWithMockedDefinition('test_attr', 1, $attributeDefinition));
        $this->assertTrue($this->validateAttributeValueWithMockedDefinition('test_attr', true, $attributeDefinition));
        $this->assertTrue($this->validateAttributeValueWithMockedDefinition('test_attr', false, $attributeDefinition));
        $this->assertTrue($this->validateAttributeValueWithMockedDefinition('test_attr', 'Y', $attributeDefinition));
        $this->assertTrue($this->validateAttributeValueWithMockedDefinition('test_attr', 'N', $attributeDefinition));
        
        // Test invalid boolean values
        $this->assertFalse($this->validateAttributeValueWithMockedDefinition('test_attr', '2', $attributeDefinition));
        $this->assertFalse($this->validateAttributeValueWithMockedDefinition('test_attr', 'yes', $attributeDefinition));
        $this->assertFalse($this->validateAttributeValueWithMockedDefinition('test_attr', 'no', $attributeDefinition));
        $this->assertFalse($this->validateAttributeValueWithMockedDefinition('test_attr', 'true', $attributeDefinition));
        $this->assertFalse($this->validateAttributeValueWithMockedDefinition('test_attr', 'false', $attributeDefinition));
    }

    public function testSwitchValidation()
    {
        $attributeDefinition = ['inputtype' => 'switch'];
        
        // Switch should have same validation as boolean
        $this->assertTrue($this->validateAttributeValueWithMockedDefinition('test_attr', '0', $attributeDefinition));
        $this->assertTrue($this->validateAttributeValueWithMockedDefinition('test_attr', '1', $attributeDefinition));
        $this->assertTrue($this->validateAttributeValueWithMockedDefinition('test_attr', 'Y', $attributeDefinition));
        $this->assertTrue($this->validateAttributeValueWithMockedDefinition('test_attr', 'N', $attributeDefinition));
        
        $this->assertFalse($this->validateAttributeValueWithMockedDefinition('test_attr', '2', $attributeDefinition));
        $this->assertFalse($this->validateAttributeValueWithMockedDefinition('test_attr', 'maybe', $attributeDefinition));
    }

    public function testSelectValidation()
    {
        $attributeDefinition = [
            'inputtype' => 'select',
            'options' => [
                'option1' => 'Label 1',
                'option2' => 'Label 2', 
                'option3' => 'Label 3'
            ]
        ];
        
        // Test valid options
        $this->assertTrue($this->validateAttributeValueWithMockedDefinition('test_attr', 'option1', $attributeDefinition));
        $this->assertTrue($this->validateAttributeValueWithMockedDefinition('test_attr', 'option2', $attributeDefinition));
        $this->assertTrue($this->validateAttributeValueWithMockedDefinition('test_attr', 'option3', $attributeDefinition));
        
        // Test invalid options
        $this->assertFalse($this->validateAttributeValueWithMockedDefinition('test_attr', 'option4', $attributeDefinition));
        $this->assertFalse($this->validateAttributeValueWithMockedDefinition('test_attr', 'invalid', $attributeDefinition));
        $this->assertFalse($this->validateAttributeValueWithMockedDefinition('test_attr', '', $attributeDefinition));
        
        // Verify error message includes valid options using the actual validator instance
        $this->validator->clearValidationErrors();
        $this->validator->validateAttributeValueWithDefinition('test_attr', 'invalid', $attributeDefinition);
        $errors = $this->validator->getValidationErrors();
        $this->assertArrayHasKey('test_attr', $errors);
        $this->assertStringContainsString('option1, option2, option3', $errors['test_attr'][0]);
    }

    public function testButtongroupValidation()
    {
        $attributeDefinition = [
            'inputtype' => 'buttongroup',
            'options' => [
                'btn1' => 'Button 1',
                'btn2' => 'Button 2'
            ]
        ];
        
        // Buttongroup should have same validation as select
        $this->assertTrue($this->validateAttributeValueWithMockedDefinition('test_attr', 'btn1', $attributeDefinition));
        $this->assertTrue($this->validateAttributeValueWithMockedDefinition('test_attr', 'btn2', $attributeDefinition));
        
        $this->assertFalse($this->validateAttributeValueWithMockedDefinition('test_attr', 'btn3', $attributeDefinition));
        $this->assertFalse($this->validateAttributeValueWithMockedDefinition('test_attr', 'invalid', $attributeDefinition));
    }

    public function testTextValidation()
    {
        $attributeDefinition = ['inputtype' => 'text'];
        
        // Test valid text values
        $this->assertTrue($this->validateAttributeValueWithMockedDefinition('test_attr', 'Hello world', $attributeDefinition));
        $this->assertTrue($this->validateAttributeValueWithMockedDefinition('test_attr', '123', $attributeDefinition));
        $this->assertTrue($this->validateAttributeValueWithMockedDefinition('test_attr', '', $attributeDefinition));
        $this->assertTrue($this->validateAttributeValueWithMockedDefinition('test_attr', 'Special chars: @#$%', $attributeDefinition));
        
        // Test invalid text values (arrays and objects)
        $this->assertFalse($this->validateAttributeValueWithMockedDefinition('test_attr', ['array'], $attributeDefinition));
        $this->assertFalse($this->validateAttributeValueWithMockedDefinition('test_attr', (object)['prop' => 'value'], $attributeDefinition));
    }

    public function testTextareaValidation()
    {
        $attributeDefinition = ['inputtype' => 'textarea'];
        
        // Test valid textarea values (should behave like text)
        $this->assertTrue($this->validateAttributeValueWithMockedDefinition('test_attr', 'Multi\nline\ntext', $attributeDefinition));
        $this->assertTrue($this->validateAttributeValueWithMockedDefinition('test_attr', 'Long text content with spaces and special characters!', $attributeDefinition));
        $this->assertTrue($this->validateAttributeValueWithMockedDefinition('test_attr', '', $attributeDefinition));
        $this->assertTrue($this->validateAttributeValueWithMockedDefinition('test_attr', '12345', $attributeDefinition));
        
        // Test invalid textarea values (arrays and objects)
        $this->assertFalse($this->validateAttributeValueWithMockedDefinition('test_attr', ['array', 'values'], $attributeDefinition));
        $this->assertFalse($this->validateAttributeValueWithMockedDefinition('test_attr', (object)['key' => 'value'], $attributeDefinition));
        
        // Verify error message using the actual validator instance
        $this->validator->clearValidationErrors();
        $this->validator->validateAttributeValueWithDefinition('test_attr', ['invalid'], $attributeDefinition);
        $errors = $this->validator->getValidationErrors();
        $this->assertArrayHasKey('test_attr', $errors);
        $this->assertContains("Attribute 'test_attr' must be a string value", $errors['test_attr']);
    }

    public function testDefaultValidation()
    {
        $attributeDefinition = ['inputtype' => 'unknown_type'];
        
        // Unknown types should fall back to text/textarea validation
        $this->assertTrue($this->validateAttributeValueWithMockedDefinition('test_attr', 'text value', $attributeDefinition));
        $this->assertFalse($this->validateAttributeValueWithMockedDefinition('test_attr', ['array'], $attributeDefinition));
    }

    public function testErrorManagement()
    {
        $intDefinition = ['inputtype' => 'integer'];
        
        $this->validator->clearValidationErrors();
        $this->assertFalse($this->validator->hasValidationErrors());
        $this->assertEquals([], $this->validator->getValidationErrors());
        
        $this->validator->validateAttributeValueWithDefinition('attr1', 'invalid', $intDefinition);
        $this->validator->validateAttributeValueWithDefinition('attr2', 'also_invalid', $intDefinition);
        
        $this->assertTrue($this->validator->hasValidationErrors());
        $errors = $this->validator->getValidationErrors();
        $this->assertArrayHasKey('attr1', $errors);
        $this->assertArrayHasKey('attr2', $errors);
        
        $attr1Errors = $this->validator->getValidationErrorsForAttribute('attr1');
        $this->assertNotEmpty($attr1Errors);
        $this->assertContains("Attribute 'attr1' must be an integer", $attr1Errors);
        
        $nonExistentErrors = $this->validator->getValidationErrorsForAttribute('nonexistent');
        $this->assertEquals([], $nonExistentErrors);
        
        $this->validator->clearValidationErrors();
        $this->assertFalse($this->validator->hasValidationErrors());
        $this->assertEquals([], $this->validator->getValidationErrors());
    }

    public function testMultipleValidationErrors()
    {
        $attributeDefinition = [
            'inputtype' => 'integer',
            'min' => 5,
            'max' => 10
        ];
        
        $this->validator->clearValidationErrors();
        $this->validator->validateAttributeValueWithDefinition('test_attr', 'abc', $attributeDefinition);
        
        $errors = $this->validator->getValidationErrorsForAttribute('test_attr');
        $this->assertGreaterThanOrEqual(1, count($errors));
        $this->assertContains("Attribute 'test_attr' must be an integer", $errors);
    }

    public function testSelectValidationWithoutOptions()
    {
        $attributeDefinition = ['inputtype' => 'select'];
        
        $this->assertTrue($this->validateAttributeValueWithMockedDefinition('test_attr', 'any_value', $attributeDefinition));
        
        $attributeDefinition['options'] = [];
        $this->assertTrue($this->validateAttributeValueWithMockedDefinition('test_attr', 'any_value', $attributeDefinition));
    }

    public function testValidateQuestionAttributes()
    {
        $mockValidator = $this->getMockBuilder(QuestionAttributeValidator::class)
            ->setConstructorArgs([$this->mockSurvey])
            ->onlyMethods(['getAllowedAttributesForQuestionType'])
            ->getMock();

        $attributeDefinitions = [
            'min_answers' => ['inputtype' => 'integer'],
            'max_answers' => ['inputtype' => 'integer'],
            'random_order' => ['inputtype' => 'boolean']
        ];

        $mockValidator->method('getAllowedAttributesForQuestionType')
            ->willReturn($attributeDefinitions);

        $attributes = [
            'min_answers' => '1',
            'max_answers' => '10',
            'random_order' => '1'
        ];

        $this->assertTrue($mockValidator->validateQuestionAttributes('T', $attributes));

        $invalidAttributes = [
            'min_answers' => 'invalid',
            'max_answers' => '5',
            'random_order' => '1'
        ];

        $this->assertFalse($mockValidator->validateQuestionAttributes('T', $invalidAttributes));
        $this->assertTrue($mockValidator->hasValidationErrors());
    }

    public function testIsAttributeAllowedForQuestionType()
    {
        $mockValidator = $this->getMockBuilder(QuestionAttributeValidator::class)
            ->setConstructorArgs([$this->mockSurvey])
            ->onlyMethods(['getAllowedAttributesForQuestionType'])
            ->getMock();

        $attributeDefinitions = [
            'min_answers' => ['inputtype' => 'integer'],
            'max_answers' => ['inputtype' => 'integer']
        ];

        $mockValidator->method('getAllowedAttributesForQuestionType')
            ->willReturn($attributeDefinitions);

        $this->assertTrue($mockValidator->isAttributeAllowedForQuestionType('T', 'min_answers'));
        $this->assertTrue($mockValidator->isAttributeAllowedForQuestionType('T', 'max_answers'));
        $this->assertFalse($mockValidator->isAttributeAllowedForQuestionType('T', 'unknown_attribute'));
    }

    public function testGetUnknownAttributes()
    {
        $mockValidator = $this->getMockBuilder(QuestionAttributeValidator::class)
            ->setConstructorArgs([$this->mockSurvey])
            ->onlyMethods(['getAllowedAttributesForQuestionType'])
            ->getMock();

        $attributeDefinitions = [
            'min_answers' => ['inputtype' => 'integer'],
            'max_answers' => ['inputtype' => 'integer']
        ];

        $mockValidator->method('getAllowedAttributesForQuestionType')
            ->willReturn($attributeDefinitions);

        $attributes = [
            'min_answers' => '1',
            'unknown_attr1' => 'value1',
            'unknown_attr2' => 'value2'
        ];

        $unknownAttributes = $mockValidator->getUnknownAttributes('T', $attributes, true);
        $this->assertEquals(['unknown_attr1', 'unknown_attr2'], $unknownAttributes);
        $this->assertFalse($mockValidator->hasValidationErrors());

        $mockValidator->clearValidationErrors();
        $unknownAttributes = $mockValidator->getUnknownAttributes('T', $attributes, false);
        $this->assertEquals(['unknown_attr1', 'unknown_attr2'], $unknownAttributes);
        $this->assertTrue($mockValidator->hasValidationErrors());
        $errors = $mockValidator->getValidationErrors();
        $this->assertArrayHasKey('unknown_attr1', $errors);
        $this->assertArrayHasKey('unknown_attr2', $errors);
    }

    public function testGetAttributeDefinition()
    {
        $mockValidator = $this->getMockBuilder(QuestionAttributeValidator::class)
            ->setConstructorArgs([$this->mockSurvey])
            ->onlyMethods(['getAllowedAttributesForQuestionType'])
            ->getMock();

        $attributeDefinitions = [
            'min_answers' => ['inputtype' => 'integer', 'min' => 0, 'max' => 100],
            'random_order' => ['inputtype' => 'boolean']
        ];

        $mockValidator->method('getAllowedAttributesForQuestionType')
            ->willReturn($attributeDefinitions);

        $definition = $mockValidator->getAttributeDefinition('T', 'min_answers');
        $this->assertEquals(['inputtype' => 'integer', 'min' => 0, 'max' => 100], $definition);

        $definition = $mockValidator->getAttributeDefinition('T', 'random_order');
        $this->assertEquals(['inputtype' => 'boolean'], $definition);

        $definition = $mockValidator->getAttributeDefinition('T', 'unknown_attribute');
        $this->assertNull($definition);
    }

    public function testGetAttributeDefaultValue()
    {
        $mockValidator = $this->getMockBuilder(QuestionAttributeValidator::class)
            ->setConstructorArgs([$this->mockSurvey])
            ->onlyMethods(['getAllowedAttributesForQuestionType'])
            ->getMock();

        $attributeDefinitions = [
            'with_default' => ['inputtype' => 'integer', 'default' => '5'],
            'without_default' => ['inputtype' => 'boolean'],
            'empty_default' => ['inputtype' => 'text', 'default' => '']
        ];

        $mockValidator->method('getAllowedAttributesForQuestionType')
            ->willReturn($attributeDefinitions);

        $this->assertEquals('5', $mockValidator->getAttributeDefaultValue('T', 'with_default'));
        $this->assertEquals('', $mockValidator->getAttributeDefaultValue('T', 'without_default'));
        $this->assertEquals('', $mockValidator->getAttributeDefaultValue('T', 'empty_default'));
        $this->assertNull($mockValidator->getAttributeDefaultValue('T', 'unknown_attribute'));
    }

    public function testSetSurvey()
    {
        $newSurvey = $this->createMock(\Survey::class);
        $newSurvey->method('getAllLanguages')->willReturn(['en', 'fr']);
        $newSurvey->method('getPrimaryKey')->willReturn(789);

        $this->validator->setSurvey($newSurvey);

        $this->assertEquals($newSurvey, $this->getPrivateProperty($this->validator, 'survey'));
    }

    public function testAttributeNames()
    {
        $expectedAttributes = ['questionType', 'attributeName', 'value'];
        $this->assertEquals($expectedAttributes, $this->validator->attributeNames());
    }

    public function testEdgeCasesWithEmptyAndNullValues()
    {
        $intDefinition = ['inputtype' => 'integer'];
        
        $this->assertFalse($this->validator->validateAttributeValueWithDefinition('test_attr', '', $intDefinition));
        $this->assertFalse($this->validator->validateAttributeValueWithDefinition('test_attr', null, $intDefinition));
        
        $textDefinition = ['inputtype' => 'text'];
        $this->assertTrue($this->validator->validateAttributeValueWithDefinition('test_attr', '', $textDefinition));
        $this->assertTrue($this->validator->validateAttributeValueWithDefinition('test_attr', null, $textDefinition));
        
        $selectDefinition = ['inputtype' => 'select', 'options' => ['opt1' => 'Option 1']];
        $this->assertFalse($this->validator->validateAttributeValueWithDefinition('test_attr', '', $selectDefinition));
        $this->assertFalse($this->validator->validateAttributeValueWithDefinition('test_attr', null, $selectDefinition));
    }

    public function testNumericStringValidation()
    {
        $intDefinition = ['inputtype' => 'integer'];
        
        $this->assertTrue($this->validator->validateAttributeValueWithDefinition('test_attr', '123', $intDefinition));
        $this->assertTrue($this->validator->validateAttributeValueWithDefinition('test_attr', '-456', $intDefinition));
        $this->assertTrue($this->validator->validateAttributeValueWithDefinition('test_attr', '0', $intDefinition));
        
        $this->assertFalse($this->validator->validateAttributeValueWithDefinition('test_attr', '12.34', $intDefinition));
        $this->assertTrue($this->validator->validateAttributeValueWithDefinition('test_attr', '1.0', $intDefinition));
        
        $decimalDefinition = ['inputtype' => 'decimal'];
        $this->assertTrue($this->validator->validateAttributeValueWithDefinition('test_attr', '12.34', $decimalDefinition));
        $this->assertTrue($this->validator->validateAttributeValueWithDefinition('test_attr', '123', $decimalDefinition));
        $this->assertTrue($this->validator->validateAttributeValueWithDefinition('test_attr', '-12.34', $decimalDefinition));
    }

    public function testValidationWithInvalidAttributes()
    {
        $mockValidator = $this->getMockBuilder(QuestionAttributeValidator::class)
            ->setConstructorArgs([$this->mockSurvey])
            ->onlyMethods(['getAllowedAttributesForQuestionType'])
            ->getMock();

        $attributeDefinitions = [
            'min_answers' => ['inputtype' => 'integer'],
            'random_order' => ['inputtype' => 'boolean']
        ];

        $mockValidator->method('getAllowedAttributesForQuestionType')
            ->willReturn($attributeDefinitions);

        $mockValidator->clearValidationErrors();
        $result = $mockValidator->validateAttributeValue('T', 'invalid_attribute', 'some_value');
        
        $this->assertFalse($result);
        $this->assertTrue($mockValidator->hasValidationErrors());
        $errors = $mockValidator->getValidationErrors();
        $this->assertArrayHasKey('invalid_attribute', $errors);
        $this->assertContains("Attribute 'invalid_attribute' is not allowed for question type 'T'", $errors['invalid_attribute']);
    }

    private function getPrivateProperty($object, $propertyName)
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        return $property->getValue($object);
    }

    private function validateAttributeValueWithMockedDefinition(string $attributeName, $value, array $attributeDefinition): bool
    {
        return $this->validator->validateAttributeValueWithDefinition($attributeName, $value, $attributeDefinition);
    }
}