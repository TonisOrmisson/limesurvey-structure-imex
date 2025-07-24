<?php
/**
 * Extract ALL question attributes from ALL LimeSurvey core XML files
 * This creates the definitive source of truth for 100% attribute coverage
 */

// Path to LimeSurvey question type XML files
$xmlPath = '/home/tonis/PhpstormProjects/LimeSurvey/application/views/survey/questions/answer';

// Find all config.xml files
$xmlFiles = glob($xmlPath . '/*/config.xml');

$allAttributes = [];
$questionTypeMapping = [];

foreach ($xmlFiles as $xmlFile) {
    // Extract question type from path
    $pathParts = explode('/', $xmlFile);
    $questionTypeName = $pathParts[count($pathParts) - 2];
    
    // Load XML
    $xml = simplexml_load_file($xmlFile);
    if (!$xml) {
        echo "ERROR: Could not load $xmlFile\n";
        continue;
    }
    
    // Get question type code from XML
    $questionType = (string)$xml->metadata->questionType;
    $questionTypeMapping[$questionType] = $questionTypeName;
    
    echo "Processing question type: $questionType ($questionTypeName)\n";
    
    // Extract general attributes
    if (isset($xml->generalattributes->attribute)) {
        foreach ($xml->generalattributes->attribute as $attr) {
            $attrName = (string)$attr;
            if (!isset($allAttributes[$attrName])) {
                $allAttributes[$attrName] = [
                    'name' => $attrName,
                    'type' => 'general',
                    'question_types' => [],
                    'definition' => [
                        'default' => '',
                        'inputtype' => 'general',
                        'category' => 'General'
                    ]
                ];
            }
            $allAttributes[$attrName]['question_types'][] = $questionType;
        }
    }
    
    // Extract advanced attributes
    if (isset($xml->attributes->attribute)) {
        foreach ($xml->attributes->attribute as $attr) {
            $attrName = (string)$attr->name;
            if (empty($attrName)) continue;
            
            if (!isset($allAttributes[$attrName])) {
                $allAttributes[$attrName] = [
                    'name' => $attrName,
                    'type' => 'advanced',
                    'question_types' => [],
                    'definition' => []
                ];
            }
            
            // Add question type to this attribute
            $allAttributes[$attrName]['question_types'][] = $questionType;
            
            // Extract attribute definition
            $definition = [
                'name' => $attrName,
                'default' => (string)$attr->default,
                'inputtype' => (string)$attr->inputtype,
                'category' => (string)$attr->category,
                'help' => (string)$attr->help,
                'caption' => (string)$attr->caption,
                'sortorder' => (string)$attr->sortorder,
                'expression' => (string)$attr->expression,
                'i18n' => (string)$attr->i18n,
                'readonly' => (string)$attr->readonly,
                'readonly_when_active' => (string)$attr->readonly_when_active,
                'xssfilter' => (string)$attr->xssfilter
            ];
            
            // Extract options if present
            if (isset($attr->options->option)) {
                $options = [];
                foreach ($attr->options->option as $option) {
                    $value = (string)$option->value;
                    $text = (string)$option->text;
                    $options[$value] = $text;
                }
                $definition['options'] = $options;
            }
            
            // Store the most complete definition we find
            if (empty($allAttributes[$attrName]['definition']) || 
                count($definition) > count($allAttributes[$attrName]['definition'])) {
                $allAttributes[$attrName]['definition'] = $definition;
            }
        }
    }
}

// Remove duplicates and sort
foreach ($allAttributes as $name => $data) {
    $allAttributes[$name]['question_types'] = array_unique($data['question_types']);
    sort($allAttributes[$name]['question_types']);
}

// Sort attributes alphabetically
ksort($allAttributes);

// Output results
echo "\n" . str_repeat("=", 80) . "\n";
echo "COMPREHENSIVE ATTRIBUTE ANALYSIS - " . count($allAttributes) . " TOTAL ATTRIBUTES\n";
echo str_repeat("=", 80) . "\n\n";

// Group by first letter for easier reading
$byLetter = [];
foreach ($allAttributes as $name => $data) {
    $firstLetter = strtoupper($name[0]);
    $byLetter[$firstLetter][] = $data;
}

foreach ($byLetter as $letter => $attributes) {
    echo "=== $letter Attributes (" . count($attributes) . " total) ===\n";
    foreach ($attributes as $attr) {
        $name = $attr['name'];
        $types = implode(', ', $attr['question_types']);
        $default = $attr['definition']['default'] ?? '';
        $inputtype = $attr['definition']['inputtype'] ?? '';
        $category = $attr['definition']['category'] ?? '';
        
        echo sprintf("  %-30s | Types: %-20s | Default: %-10s | Input: %-15s | Category: %s\n", 
            $name, $types, $default, $inputtype, $category);
    }
    echo "\n";
}

// Statistics
echo "\n" . str_repeat("=", 80) . "\n";
echo "STATISTICS\n";
echo str_repeat("=", 80) . "\n";
echo "Total attributes found: " . count($allAttributes) . "\n";
echo "Question types processed: " . count($questionTypeMapping) . "\n";

echo "\nQuestion type mapping:\n";
foreach ($questionTypeMapping as $code => $name) {
    echo "  $code => $name\n";
}

// Generate JSON output for further analysis
$outputData = [
    'attributes' => $allAttributes,
    'question_types' => $questionTypeMapping,
    'statistics' => [
        'total_attributes' => count($allAttributes),
        'total_question_types' => count($questionTypeMapping),
        'extraction_date' => date('Y-m-d H:i:s')
    ]
];

file_put_contents('/home/tonis/PhpstormProjects/LimeSurvey/upload/plugins/StructureImEx/all_core_attributes.json', 
    json_encode($outputData, JSON_PRETTY_PRINT));

echo "\nComplete attribute data saved to: all_core_attributes.json\n";
echo "Run this file with: php extract_all_attributes.php\n";