-- Query to analyze real-world usage of question attributes by question type
-- Shows which attributes are actually used with non-empty values, grouped by question type
-- Ordered by usage frequency to identify most important attributes

SELECT 
    q.type as question_type,
    qa.attribute,
    COUNT(*) as usage_count,
    COUNT(DISTINCT q.sid) as surveys_using,
    -- Sample of actual values (first 5, comma separated)
    GROUP_CONCAT(
        DISTINCT CASE 
            WHEN qa.value != '' AND qa.value IS NOT NULL 
            THEN CONCAT('"', LEFT(qa.value, 50), '"')
            ELSE NULL 
        END 
        ORDER BY qa.value 
        LIMIT 5
    ) as sample_values
FROM 
    lime_questions q
INNER JOIN 
    lime_question_attributes qa ON q.qid = qa.qid
WHERE 
    qa.value != '' 
    AND qa.value IS NOT NULL
    AND qa.value != '0'  -- Exclude default '0' values for switches
GROUP BY 
    q.type, qa.attribute
HAVING 
    usage_count >= 5  -- Only show attributes used at least 5 times
ORDER BY 
    q.type ASC, 
    usage_count DESC;

-- Additional query: Get total question counts by type for perspective
SELECT 
    'TOTALS BY TYPE' as info,
    type as question_type,
    COUNT(*) as total_questions
FROM lime_questions 
GROUP BY type 
ORDER BY type;

-- Additional query: Most frequently used attributes across all types
SELECT 
    'TOP ATTRIBUTES OVERALL' as info,
    qa.attribute,
    COUNT(*) as total_usage,
    COUNT(DISTINCT q.type) as question_types_using,
    COUNT(DISTINCT q.sid) as surveys_using
FROM 
    lime_questions q
INNER JOIN 
    lime_question_attributes qa ON q.qid = qa.qid
WHERE 
    qa.value != '' 
    AND qa.value IS NOT NULL
    AND qa.value != '0'
GROUP BY 
    qa.attribute
HAVING 
    total_usage >= 20
ORDER BY 
    total_usage DESC
LIMIT 30;

-- Query to find "exclusive" related attributes for M type specifically
SELECT 
    'M TYPE EXCLUSIVE SEARCH' as info,
    qa.attribute,
    COUNT(*) as usage_count,
    GROUP_CONCAT(DISTINCT qa.value ORDER BY qa.value LIMIT 10) as values_used
FROM 
    lime_questions q
INNER JOIN 
    lime_question_attributes qa ON q.qid = qa.qid
WHERE 
    q.type = 'M'
    AND (qa.attribute LIKE '%exclusive%' 
         OR qa.attribute LIKE '%other%'
         OR qa.value LIKE '%exclusive%')
GROUP BY 
    qa.attribute
ORDER BY 
    usage_count DESC;