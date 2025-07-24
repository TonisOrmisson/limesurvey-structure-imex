<?php
/**
 * Compare extracted XML attributes against current plugin implementation
 * Identify ALL missing attributes for 100% coverage
 */

require_once __DIR__ . '/src/validation/QuestionAttributeDefinition.php';

use tonisormisson\ls\structureimex\validation\QuestionAttributeDefinition;

// Load extracted XML data
$xmlData = json_decode(file_get_contents(__DIR__ . '/all_core_attributes.json'), true);
$xmlAttributes = $xmlData['attributes'];

// Load usage data from live installation
$usageData = json_decode(file_get_contents(__DIR__ . '/tasks/attributes-by-type.json'), true);
$usageByAttribute = [];

// Parse usage data to get usage counts per attribute
foreach ($usageData as $entry) {
    if (isset($entry['data'])) {
        foreach ($entry['data'] as $usage) {
            if (isset($usage['attribute'])) {
                $attr = $usage['attribute'];
                if (!isset($usageByAttribute[$attr])) {
                    $usageByAttribute[$attr] = [
                        'total_usage' => 0,
                        'question_types' => [],
                        'surveys_using' => 0
                    ];
                }
                $usageByAttribute[$attr]['total_usage'] += (int)$usage['usage_count'];
                $usageByAttribute[$attr]['question_types'][] = $usage['question_type'];
                $usageByAttribute[$attr]['surveys_using'] = max(
                    $usageByAttribute[$attr]['surveys_using'], 
                    (int)$usage['surveys_using']
                );
            }
        }
    }
}

// Get all question types from our current implementation
$currentTypes = QuestionAttributeDefinition::getSupportedQuestionTypes();
$currentAttributes = [];

// Extract all attributes from our current implementation
foreach ($currentTypes as $type) {
    $attrs = QuestionAttributeDefinition::getAttributesForQuestionType($type);
    foreach ($attrs as $attrName => $def) {
        if (!isset($currentAttributes[$attrName])) {
            $currentAttributes[$attrName] = ['question_types' => []];
        }
        $currentAttributes[$attrName]['question_types'][] = $type;
    }
}

// Comparison analysis
$missingAttributes = [];
$implementedAttributes = [];
$extraAttributes = [];

foreach ($xmlAttributes as $attrName => $xmlData) {
    if (isset($currentAttributes[$attrName])) {
        $implementedAttributes[$attrName] = [
            'xml_data' => $xmlData,
            'usage' => $usageByAttribute[$attrName] ?? null,
            'current_types' => $currentAttributes[$attrName]['question_types']
        ];
    } else {
        $missingAttributes[$attrName] = [
            'xml_data' => $xmlData,
            'usage' => $usageByAttribute[$attrName] ?? null
        ];
    }
}

// Check for attributes in our implementation that don't exist in XML
foreach ($currentAttributes as $attrName => $data) {
    if (!isset($xmlAttributes[$attrName])) {
        $extraAttributes[$attrName] = $data;
    }
}

// Sort by usage frequency (most used first)
uasort($missingAttributes, function($a, $b) {
    $usageA = $a['usage']['total_usage'] ?? 0;
    $usageB = $b['usage']['total_usage'] ?? 0;
    return $usageB - $usageA;
});

// Output comprehensive analysis
echo str_repeat("=", 100) . "\n";
echo "COMPREHENSIVE ATTRIBUTE COVERAGE ANALYSIS\n";
echo str_repeat("=", 100) . "\n\n";

echo "📊 SUMMARY STATISTICS:\n";
echo sprintf("   XML Attributes (LimeSurvey Core): %d\n", count($xmlAttributes));
echo sprintf("   Current Implementation:          %d\n", count($currentAttributes));
echo sprintf("   ✅ Implemented:                 %d (%.1f%%)\n", 
    count($implementedAttributes), 
    (count($implementedAttributes) / count($xmlAttributes)) * 100);
echo sprintf("   ❌ Missing:                     %d (%.1f%%)\n", 
    count($missingAttributes), 
    (count($missingAttributes) / count($xmlAttributes)) * 100);
echo sprintf("   ⚠️  Extra (not in XML):         %d\n", count($extraAttributes));

echo "\n" . str_repeat("=", 100) . "\n";
echo "❌ MISSING ATTRIBUTES (" . count($missingAttributes) . " total) - BY USAGE FREQUENCY\n";
echo str_repeat("=", 100) . "\n";

$priorityHigh = 0;
$priorityMedium = 0;
$priorityLow = 0;

foreach ($missingAttributes as $attrName => $data) {
    $usage = $data['usage'];
    $xmlInfo = $data['xml_data'];
    
    $usageCount = $usage ? $usage['total_usage'] : 0;
    $surveysUsing = $usage ? $usage['surveys_using'] : 0;
    $questionTypes = implode(', ', $xmlInfo['question_types']);
    $default = $xmlInfo['definition']['default'] ?? '';
    $inputType = $xmlInfo['definition']['inputtype'] ?? '';
    $category = $xmlInfo['definition']['category'] ?? '';
    
    // Priority classification
    $priority = 'LOW';
    if ($usageCount > 1000) {
        $priority = 'HIGH';
        $priorityHigh++;
    } elseif ($usageCount > 100) {
        $priority = 'MEDIUM'; 
        $priorityMedium++;
    } else {
        $priorityLow++;
    }
    
    printf("%-30s | Usage: %5d | Surveys: %3d | Priority: %-6s | Types: %s\n",
        $attrName, $usageCount, $surveysUsing, $priority, $questionTypes);
    printf("%30s | Default: %-10s | Input: %-15s | Category: %s\n", 
        '', $default, $inputType, $category);
    echo str_repeat("-", 100) . "\n";
}

echo "\n📈 MISSING ATTRIBUTES BY PRIORITY:\n";
echo sprintf("   🔴 HIGH Priority (>1000 usage):   %d attributes\n", $priorityHigh);
echo sprintf("   🟡 MEDIUM Priority (100-1000):    %d attributes\n", $priorityMedium);
echo sprintf("   🟢 LOW Priority (<100):           %d attributes\n", $priorityLow);

echo "\n" . str_repeat("=", 100) . "\n";
echo "✅ IMPLEMENTED ATTRIBUTES (" . count($implementedAttributes) . " total)\n";
echo str_repeat("=", 100) . "\n";

foreach ($implementedAttributes as $attrName => $data) {
    $usage = $data['usage'];
    $usageCount = $usage ? $usage['total_usage'] : 0;
    $surveysUsing = $usage ? $usage['surveys_using'] : 0;
    printf("%-30s | Usage: %5d | Surveys: %3d | ✅ IMPLEMENTED\n",
        $attrName, $usageCount, $surveysUsing);
}

if (count($extraAttributes) > 0) {
    echo "\n" . str_repeat("=", 100) . "\n";
    echo "⚠️  EXTRA ATTRIBUTES (not in XML core - " . count($extraAttributes) . " total)\n";
    echo str_repeat("=", 100) . "\n";
    
    foreach ($extraAttributes as $attrName => $data) {
        $usage = $usageByAttribute[$attrName] ?? null;
        $usageCount = $usage ? $usage['total_usage'] : 0;
        $surveysUsing = $usage ? $usage['surveys_using'] : 0;
        printf("%-30s | Usage: %5d | Surveys: %3d | Types: %s\n",
            $attrName, $usageCount, $surveysUsing, 
            implode(', ', $data['question_types']));
    }
}

// Generate detailed implementation plan
echo "\n" . str_repeat("=", 100) . "\n";
echo "🎯 IMPLEMENTATION PLAN TO ACHIEVE 100% COVERAGE\n";
echo str_repeat("=", 100) . "\n";

echo "\nPhase 1: HIGH Priority Attributes ($priorityHigh attributes)\n";
echo str_repeat("-", 50) . "\n";
$phaseCount = 0;
foreach ($missingAttributes as $attrName => $data) {
    $usageCount = ($data['usage']['total_usage'] ?? 0);
    if ($usageCount > 1000) {
        $phaseCount++;
        $questionTypes = implode(', ', $data['xml_data']['question_types']);
        $default = $data['xml_data']['definition']['default'] ?? '';
        $inputType = $data['xml_data']['definition']['inputtype'] ?? '';
        echo sprintf("%2d. %-25s | Default: %-10s | Types: %s\n", 
            $phaseCount, $attrName, $default, $questionTypes);
    }
}

echo "\nPhase 2: MEDIUM Priority Attributes ($priorityMedium attributes)\n";
echo str_repeat("-", 50) . "\n";
$phaseCount = 0;
foreach ($missingAttributes as $attrName => $data) {
    $usageCount = ($data['usage']['total_usage'] ?? 0);
    if ($usageCount > 100 && $usageCount <= 1000) {
        $phaseCount++;
        $questionTypes = implode(', ', $data['xml_data']['question_types']);
        $default = $data['xml_data']['definition']['default'] ?? '';
        echo sprintf("%2d. %-25s | Default: %-10s | Types: %s\n", 
            $phaseCount, $attrName, $default, $questionTypes);
    }
}

echo "\nPhase 3: LOW Priority Attributes ($priorityLow attributes)\n";
echo str_repeat("-", 50) . "\n";
echo "   [Complete list available in detailed analysis above]\n";

echo "\n🎯 NEXT STEPS:\n";
echo "1. Implement Phase 1 (HIGH) attributes first - biggest impact on real usage\n";
echo "2. Add comprehensive unit tests for new attributes\n";
echo "3. Implement Phase 2 (MEDIUM) attributes\n";
echo "4. Complete Phase 3 (LOW) attributes for 100% coverage\n";
echo "5. Run full test suite to verify all implementations work correctly\n";

// Save detailed analysis to file
$analysisData = [
    'summary' => [
        'xml_attributes' => count($xmlAttributes),
        'current_implementation' => count($currentAttributes),
        'implemented' => count($implementedAttributes),
        'missing' => count($missingAttributes),
        'extra' => count($extraAttributes),
        'coverage_percentage' => (count($implementedAttributes) / count($xmlAttributes)) * 100
    ],
    'missing_attributes' => $missingAttributes,
    'implemented_attributes' => $implementedAttributes,
    'extra_attributes' => $extraAttributes,
    'priority_breakdown' => [
        'high' => $priorityHigh,
        'medium' => $priorityMedium,
        'low' => $priorityLow
    ]
];

file_put_contents(__DIR__ . '/attribute_coverage_analysis.json', json_encode($analysisData, JSON_PRETTY_PRINT));
echo "\n💾 Detailed analysis saved to: attribute_coverage_analysis.json\n";