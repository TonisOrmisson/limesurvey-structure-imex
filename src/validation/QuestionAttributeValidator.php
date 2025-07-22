<?php

namespace tonisormisson\ls\structureimex\validation;

use CModel;
use LimeSurvey\Models\Services\QuestionAttributeFetcher;
use Question;
use Survey;
use tonisormisson\ls\structureimex\AppTrait;

/**
 * Validates LimeSurvey 4+ question attributes by querying the core system
 * for allowed attributes per question type
 * 
 * @author Tõnis Ormisson <tonis@andmemasin.eu>
 */
class QuestionAttributeValidator extends CModel
{
    use AppTrait;

    private Survey $survey;

    /** @var array Cache for question type attribute definitions */
    private static array $attributeDefinitionsCache = [];

    /** @var array Validation errors */
    private array $validationErrors = [];

    /**
     * @param Survey $survey
     */
    public function __construct($survey = null)
    {
        $this->survey = $survey;
    }

    /**
     * Get allowed attributes for a specific question type
     * Uses LimeSurvey's core QuestionAttributeFetcher system
     *
     * @param string $questionType The question type (e.g., 'T', 'L', 'M', etc.)
     * @return array Array of allowed attribute definitions
     */
    public function getAllowedAttributesForQuestionType($questionType)
    {
        // Use cache to avoid repeated queries
        if (isset(self::$attributeDefinitionsCache[$questionType])) {
            return self::$attributeDefinitionsCache[$questionType];
        }

        // Create a dummy question to use with the fetcher
        $dummyQuestion = new Question();
        $dummyQuestion->type = $questionType;
        $dummyQuestion->sid = $this->survey->sid;

        // Use LimeSurvey's core attribute fetcher
        $fetcher = new QuestionAttributeFetcher();
        $fetcher->setQuestion($dummyQuestion);
        $fetcher->setQuestionType($questionType);

        $attributeDefinitions = $fetcher->fetch();

        // Cache the result
        self::$attributeDefinitionsCache[$questionType] = $attributeDefinitions;

        return $attributeDefinitions;

    }

    /**
     * Validate if an attribute is allowed for a specific question type
     *
     * @param string $questionType The question type
     * @param string $attributeName The attribute name to validate
     * @return bool True if the attribute is allowed, false otherwise
     */
    public function isAttributeAllowedForQuestionType($questionType, $attributeName)
    {
        $allowedAttributes = $this->getAllowedAttributesForQuestionType($questionType);
        return array_key_exists($attributeName, $allowedAttributes);
    }

    /**
     * Validate attribute value according to its definition
     *
     * @param string $questionType The question type
     * @param string $attributeName The attribute name
     * @param mixed $value The value to validate
     * @return bool True if valid, false otherwise
     */
    public function validateAttributeValue($questionType, $attributeName, $value)
    {
        $allowedAttributes = $this->getAllowedAttributesForQuestionType($questionType);

        if (!array_key_exists($attributeName, $allowedAttributes)) {
            $this->addValidationError($attributeName, "Attribute '{$attributeName}' is not allowed for question type '{$questionType}'");
            return false;
        }

        $attributeDefinition = $allowedAttributes[$attributeName];
        return $this->validateAttributeValueWithDefinition($attributeName, $value, $attributeDefinition);
    }

    /**
     * Validate attribute value with a provided definition (useful for testing)
     *
     * @param string $attributeName The attribute name
     * @param mixed $value The value to validate
     * @param array $attributeDefinition The attribute definition
     * @return bool True if valid, false otherwise
     */
    public function validateAttributeValueWithDefinition($attributeName, $value, $attributeDefinition)
    {
        $isValid = true;

        // Validate based on input type
        switch ($attributeDefinition['inputtype']) {
            case 'integer':
                if (!is_numeric($value) || (int)$value != $value) {
                    $this->addValidationError($attributeName, "Attribute '{$attributeName}' must be an integer");
                    $isValid = false;
                }
                // Check min/max constraints
                if (isset($attributeDefinition['min']) && $value < $attributeDefinition['min']) {
                    $this->addValidationError($attributeName, "Attribute '{$attributeName}' must be at least {$attributeDefinition['min']}");
                    $isValid = false;
                }
                if (isset($attributeDefinition['max']) && $value > $attributeDefinition['max']) {
                    $this->addValidationError($attributeName, "Attribute '{$attributeName}' must be at most {$attributeDefinition['max']}");
                    $isValid = false;
                }
                break;

            case 'decimal':
                if (!is_numeric($value)) {
                    $this->addValidationError($attributeName, "Attribute '{$attributeName}' must be a number");
                    $isValid = false;
                }
                break;

            case 'switch':
            case 'boolean':
                if (!in_array($value, ['0', '1', 0, 1, true, false, 'Y', 'N'], true)) {
                    $this->addValidationError($attributeName, "Attribute '{$attributeName}' must be a boolean value (0/1, Y/N, true/false)");
                    $isValid = false;
                }
                break;

            case 'select':
            case 'buttongroup':
                if (isset($attributeDefinition['options']) && is_array($attributeDefinition['options']) && !empty($attributeDefinition['options'])) {
                    $validOptions = array_keys($attributeDefinition['options']);
                    if (!in_array($value, $validOptions)) {
                        $this->addValidationError($attributeName, "Attribute '{$attributeName}' must be one of: " . implode(', ', $validOptions));
                        $isValid = false;
                    }
                }
                break;

            case 'text':
            case 'textarea':
            default:
                // Basic text validation - could be extended
                if (is_array($value) || is_object($value)) {
                    $this->addValidationError($attributeName, "Attribute '{$attributeName}' must be a string value");
                    $isValid = false;
                }
                break;
        }

        return $isValid;
    }

    /**
     * Validate multiple attributes for a question
     *
     * @param string $questionType The question type
     * @param array $attributes Array of attribute name => value pairs
     * @return bool True if all attributes are valid, false otherwise
     */
    public function validateQuestionAttributes($questionType, $attributes)
    {
        $this->clearValidationErrors();
        $allValid = true;

        foreach ($attributes as $attributeName => $value) {
            if (!$this->validateAttributeValue($questionType, $attributeName, $value)) {
                $allValid = false;
            }
        }

        return $allValid;
    }

    /**
     * Get all available question types from LimeSurvey
     *
     * @return array Array of question types
     */
    public function getAvailableQuestionTypes()
    {
        // Use LimeSurvey's core method to get question types
        $questionTypes = \QuestionTheme::model()->findAll();
        $types = [];

        foreach ($questionTypes as $theme) {
            $types[] = $theme->question_type;
        }

        return array_unique($types);
    }

    /**
     * Validate unknown attributes based on plugin settings
     *
     * @param string $questionType The question type
     * @param array $attributes Array of attribute name => value pairs
     * @param bool $allowUnknown Whether to allow unknown attributes
     * @return array Array of unknown attribute names
     */
    public function getUnknownAttributes($questionType, $attributes, $allowUnknown = false)
    {
        $allowedAttributes = $this->getAllowedAttributesForQuestionType($questionType);
        $unknownAttributes = [];

        foreach (array_keys($attributes) as $attributeName) {
            if (!array_key_exists($attributeName, $allowedAttributes)) {
                $unknownAttributes[] = $attributeName;
                
                if (!$allowUnknown) {
                    $this->addValidationError($attributeName, "Unknown attribute '{$attributeName}' for question type '{$questionType}'");
                }
            }
        }

        return $unknownAttributes;
    }

    /**
     * Add a validation error
     *
     * @param string $attribute The attribute name
     * @param string $message The error message
     */
    private function addValidationError($attribute, $message)
    {
        if (!isset($this->validationErrors[$attribute])) {
            $this->validationErrors[$attribute] = [];
        }
        $this->validationErrors[$attribute][] = $message;
    }

    /**
     * Clear all validation errors
     */
    public function clearValidationErrors()
    {
        $this->validationErrors = [];
    }

    /**
     * Get all validation errors
     *
     * @return array Array of validation errors
     */
    public function getValidationErrors()
    {
        return $this->validationErrors;
    }

    /**
     * Get validation errors for a specific attribute
     *
     * @param string $attribute The attribute name
     * @return array Array of error messages for the attribute
     */
    public function getValidationErrorsForAttribute($attribute)
    {
        return isset($this->validationErrors[$attribute]) ? $this->validationErrors[$attribute] : [];
    }

    /**
     * Check if there are any validation errors
     *
     * @return bool True if there are validation errors, false otherwise
     */
    public function hasValidationErrors()
    {
        return !empty($this->validationErrors);
    }

    /**
     * Get attribute definition for a specific question type and attribute
     *
     * @param string $questionType The question type
     * @param string $attributeName The attribute name
     * @return array|null The attribute definition or null if not found
     */
    public function getAttributeDefinition($questionType, $attributeName)
    {
        $allowedAttributes = $this->getAllowedAttributesForQuestionType($questionType);
        return isset($allowedAttributes[$attributeName]) ? $allowedAttributes[$attributeName] : null;
    }

    /**
     * Set the survey context for validation
     *
     * @param Survey $survey
     */
    public function setSurvey($survey)
    {
        $this->survey = $survey;
    }

    /**
     * Get the default value for a specific attribute of a question type
     *
     * @param string $questionType The question type
     * @param string $attributeName The attribute name
     * @return mixed The default value, or null if attribute not found
     */
    public function getAttributeDefaultValue($questionType, $attributeName)
    {
        $definition = $this->getAttributeDefinition($questionType, $attributeName);
        
        if ($definition === null) {
            return null;
        }
        
        return $definition['default'] ?? '';
    }

    /**
     * Required by CModel
     */
    public function attributeNames()
    {
        return ['questionType', 'attributeName', 'value'];
    }
}
