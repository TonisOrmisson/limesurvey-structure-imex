<?php
/**
 * Compare extracted XML attributes against current plugin implementation
 * WITHOUT loading the QuestionAttributeDefinition class to avoid dependencies
 */

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

// Extract attributes from current QuestionAttributeDefinition.php file
$definitionFile = file_get_contents(__DIR__ . '/src/validation/QuestionAttributeDefinition.php');
$currentAttributes = [];

// Extract universal attributes
if (preg_match('/private static \$universalAttributes = \[(.*?)\];/s', $definitionFile, $matches)) {
    preg_match_all("/'([^']+)'\s*=>/", $matches[1], $attrMatches);
    foreach ($attrMatches[1] as $attr) {
        $currentAttributes[$attr] = ['type' => 'universal'];
    }
}

// Extract question type specific attributes from the definitions array
if (preg_match('/private static \$definitions = \[(.*?)\];/s', $definitionFile, $matches)) {
    // Extract each question type section
    preg_match_all("/\\\\Question::QT_[^=]+ => \[(.*?)\]/s", $matches[1], $qtMatches);
    foreach ($qtMatches[1] as $qtContent) {
        preg_match_all("/'([^']+)'\s*=>/", $qtContent, $attrMatches);
        foreach ($attrMatches[1] as $attr) {
            if (!isset($currentAttributes[$attr])) {
                $currentAttributes[$attr] = ['type' => 'specific'];
            }
        }
    }
}

// Comparison analysis
$missingAttributes = [];
$implementedAttributes = [];

foreach ($xmlAttributes as $attrName => $xmlData) {
    if (isset($currentAttributes[$attrName])) {
        $implementedAttributes[$attrName] = [
            'xml_data' => $xmlData,
            'usage' => $usageByAttribute[$attrName] ?? null
        ];
    } else {
        $missingAttributes[$attrName] = [
            'xml_data' => $xmlData,
            'usage' => $usageByAttribute[$attrName] ?? null
        ];
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
echo "COMPREHENSIVE ATTRIBUTE COVERAGE ANALYSIS FOR 100% COVERAGE\n";
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

echo "\n" . str_repeat("=", 100) . "\n";
echo "❌ MISSING ATTRIBUTES (" . count($missingAttributes) . " total) - BY USAGE FREQUENCY\n";
echo str_repeat("=", 100) . "\n";

$priorityHigh = [];
$priorityMedium = [];
$priorityLow = [];

foreach ($missingAttributes as $attrName => $data) {
    $usage = $data['usage'];
    $xmlInfo = $data['xml_data'];
    
    $usageCount = $usage ? $usage['total_usage'] : 0;
    $surveysUsing = $usage ? $usage['surveys_using'] : 0;
    $questionTypes = implode(', ', $xmlInfo['question_types']);
    $default = $xmlInfo['definition']['default'] ?? '';
    $inputType = $xmlInfo['definition']['inputtype'] ?? '';
    $category = $xmlInfo['definition']['category'] ?? '';
    
    $attrInfo = [
        'name' => $attrName,
        'usage_count' => $usageCount,
        'surveys_using' => $surveysUsing,
        'question_types' => $questionTypes,
        'default' => $default,
        'input_type' => $inputType,
        'category' => $category,
        'xml_data' => $xmlInfo
    ];
    
    // Priority classification based on usage
    if ($usageCount > 1000) {
        $priorityHigh[] = $attrInfo;
    } elseif ($usageCount > 100) {
        $priorityMedium[] = $attrInfo;
    } else {
        $priorityLow[] = $attrInfo;
    }
    
    $priority = ($usageCount > 1000) ? 'HIGH' : (($usageCount > 100) ? 'MEDIUM' : 'LOW');
    
    printf("%-30s | Usage: %5d | Surveys: %3d | Priority: %-6s | Types: %s\n",
        $attrName, $usageCount, $surveysUsing, $priority, $questionTypes);
    printf("%30s | Default: %-10s | Input: %-15s | Category: %s\n", 
        '', $default, $inputType, $category);
    echo str_repeat("-", 100) . "\n";
}

echo "\n📈 MISSING ATTRIBUTES BY PRIORITY:\n";
echo sprintf("   🔴 HIGH Priority (>1000 usage):   %d attributes\n", count($priorityHigh));
echo sprintf("   🟡 MEDIUM Priority (100-1000):    %d attributes\n", count($priorityMedium));
echo sprintf("   🟢 LOW Priority (<100):           %d attributes\n", count($priorityLow));

// Generate implementation code for HIGH priority attributes
echo "\n" . str_repeat("=", 100) . "\n";
echo "🎯 IMPLEMENTATION PLAN - CODE GENERATION FOR HIGH PRIORITY ATTRIBUTES\n";
echo str_repeat("=", 100) . "\n";

if (!empty($priorityHigh)) {
    echo "\n// HIGH PRIORITY ATTRIBUTES TO ADD TO UNIVERSAL OR TYPE-SPECIFIC DEFINITIONS:\n\n";
    
    foreach ($priorityHigh as $attr) {
        $name = $attr['name'];
        $default = $attr['default'];
        $inputType = $attr['input_type'];
        $category = $attr['category'];
        $questionTypes = $attr['xml_data']['question_types'];
        
        // Determine if this should be universal (used by many types) or specific
        if (count($questionTypes) >= 10) {
            echo "// UNIVERSAL ATTRIBUTE (used by " . count($questionTypes) . " question types)\n";
            echo "'$name' => [\n";
            echo "    'default' => '$default',\n";
            echo "    'type' => '$inputType',\n";
            if (!empty($attr['xml_data']['definition']['options'])) {
                $options = array_keys($attr['xml_data']['definition']['options']);
                echo "    'options' => ['" . implode("', '", $options) . "'],\n";
            }
            echo "    'category' => '$category'\n";
            echo "],\n\n";
        } else {
            echo "// TYPE-SPECIFIC ATTRIBUTE: $name (used by: " . implode(', ', $questionTypes) . ")\n";
            echo "'$name' => [\n";
            echo "    'default' => '$default',\n";
            echo "    'type' => '$inputType',\n";
            if (!empty($attr['xml_data']['definition']['options'])) {
                $options = array_keys($attr['xml_data']['definition']['options']);
                echo "    'options' => ['" . implode("', '", $options) . "'],\n";
            }
            echo "    'category' => '$category'\n";
            echo "],\n";
        }
    }
}

// Generate summary report
echo "\n" . str_repeat("=", 100) . "\n";
echo "📋 COMPLETE IMPLEMENTATION SUMMARY FOR 100% COVERAGE\n";
echo str_repeat("=", 100) . "\n";

echo "\n🎯 SYSTEMATIC IMPLEMENTATION PLAN:\n\n";

echo "Phase 1: HIGH Priority (" . count($priorityHigh) . " attributes) - IMMEDIATE IMPACT\n";
echo str_repeat("-", 60) . "\n";
foreach ($priorityHigh as $i => $attr) {
    echo sprintf("%2d. %-25s | Usage: %5d | Types: %s\n", 
        $i+1, $attr['name'], $attr['usage_count'], $attr['question_types']);
}

echo "\nPhase 2: MEDIUM Priority (" . count($priorityMedium) . " attributes) - SIGNIFICANT IMPACT\n";
echo str_repeat("-", 60) . "\n";
foreach (array_slice($priorityMedium, 0, 10) as $i => $attr) {
    echo sprintf("%2d. %-25s | Usage: %5d | Types: %s\n", 
        $i+1, $attr['name'], $attr['usage_count'], $attr['question_types']);
}
if (count($priorityMedium) > 10) {
    echo "... and " . (count($priorityMedium) - 10) . " more medium priority attributes\n";
}

echo "\nPhase 3: LOW Priority (" . count($priorityLow) . " attributes) - COMPLETE COVERAGE\n";
echo str_repeat("-", 60) . "\n";
echo "Complete remaining attributes for 100% coverage\n";

echo "\n🎯 SUCCESS METRICS:\n";
echo sprintf("   Current Coverage: %.1f%% (%d/%d)\n", 
    (count($implementedAttributes) / count($xmlAttributes)) * 100,
    count($implementedAttributes), count($xmlAttributes));
echo sprintf("   After HIGH Phase: %.1f%% (%d/%d)\n", 
    ((count($implementedAttributes) + count($priorityHigh)) / count($xmlAttributes)) * 100,
    count($implementedAttributes) + count($priorityHigh), count($xmlAttributes));
echo sprintf("   After MEDIUM Phase: %.1f%% (%d/%d)\n", 
    ((count($implementedAttributes) + count($priorityHigh) + count($priorityMedium)) / count($xmlAttributes)) * 100,
    count($implementedAttributes) + count($priorityHigh) + count($priorityMedium), count($xmlAttributes));
echo sprintf("   After LOW Phase: 100.0%% (%d/%d) - COMPLETE COVERAGE! 🎉\n", 
    count($xmlAttributes), count($xmlAttributes));

// Save analysis data
$analysisData = [
    'summary' => [
        'xml_attributes_total' => count($xmlAttributes),
        'current_implementation' => count($currentAttributes),
        'implemented_count' => count($implementedAttributes),
        'missing_count' => count($missingAttributes),
        'current_coverage_percentage' => (count($implementedAttributes) / count($xmlAttributes)) * 100
    ],
    'priority_breakdown' => [
        'high_priority' => $priorityHigh,
        'medium_priority' => $priorityMedium,
        'low_priority' => $priorityLow
    ],
    'implementation_phases' => [
        'phase_1_high' => count($priorityHigh),
        'phase_2_medium' => count($priorityMedium), 
        'phase_3_low' => count($priorityLow)
    ]
];

file_put_contents(__DIR__ . '/complete_coverage_analysis.json', json_encode($analysisData, JSON_PRETTY_PRINT));
echo "\n💾 Complete analysis saved to: complete_coverage_analysis.json\n";
echo "\n🚀 Ready to implement " . count($missingAttributes) . " missing attributes for 100% coverage!\n";