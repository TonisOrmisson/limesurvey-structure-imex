<?php

namespace tonisormisson\ls\structureimex\import;

use Quota;
use QuotaMember;
use QuotaLanguageSetting;
use Question;
use tonisormisson\ls\structureimex\exceptions\ImexException;

/**
 * Class ImportQuotas
 * 
 * Imports survey quotas from Excel/CSV format including:
 * - Core quota information (name, limit, active, etc.)
 * - Quota members (question/answer conditions)
 * - Multi-language     quota settings
 * 
 * Import format follows hierarchical structure similar to QuestionGroup → Questions:
 * - Q rows: Quota information with language-specific content
 * - QM rows: QuotaMember information (question code + answer code)
 */
class ImportQuotas extends ImportFromFile
{
    protected string $type = 'quotas';

    protected function beforeProcess(): void
    {
        $this->validateImportData();
    }

    private $currentQuotaName = '';

    protected function importModel(array $attributes): void
    {
        $type = $attributes['type'] ?? '';
        
        try {
            if ($type === 'Q') {
                $this->currentQuotaName = $attributes['name'] ?? '';
                $this->importQuota($attributes);
            } elseif ($type === 'QM') {
                $this->importQuotaMember($attributes);
            } else {
                $this->addError('type', "Invalid row type: {$type}");
            }
        } catch (ImexException $e) {
            $this->addError('import', $e->getMessage());
            $this->failedModelsCount++;
        }
    }

    private function importQuota(array $attributes): void
    {
        $quotaName = $attributes['name'] ?? '';
        if (empty($quotaName)) {
            throw new ImexException('Quota name is required');
        }

        $quota = $this->findOrCreateQuota($quotaName);
        
        $quota->name = $quotaName;
        $quota->qlimit = (int)($attributes['value'] ?? 0);
        $quota->active = (int)($attributes['active'] ?? 1);
        $quota->autoload_url = (int)($attributes['autoload_url'] ?? 0);
        $quota->sid = $this->survey->sid;

        if (!$quota->save()) {
            $errors = [];
            foreach ($quota->getErrors() as $field => $fieldErrors) {
                foreach ((array)$fieldErrors as $error) {
                    $errors[] = "$field: $error";
                }
            }
            throw new ImexException('Failed to save quota: ' . implode(', ', $errors));
        }

        $this->importQuotaLanguageSettings($quota, $attributes);
        $this->successfulModelsCount++;
    }

    private function importQuotaMember(array $attributes): void
    {
        $quotaName = $this->currentQuotaName;
        $questionCode = $attributes['name'] ?? '';
        $answerCode = $attributes['value'] ?? '';

        if (empty($quotaName) || empty($questionCode) || empty($answerCode)) {
            throw new ImexException('Missing required quota member data');
        }

        $quota = $this->findQuotaByName($quotaName);
        if (!$quota) {
            throw new ImexException("Quota not found: {$quotaName}");
        }

        $question = $this->findQuestionByCode($questionCode);
        if (!$question) {
            throw new ImexException("Question not found: {$questionCode}");
        }

        $member = $this->findOrCreateQuotaMember($quota, $question, $answerCode);
        
        $member->sid = $this->survey->sid;
        $member->quota_id = $quota->id;
        $member->qid = $question->qid;
        $member->code = $answerCode;

        if (!$member->save()) {
            $errors = [];
            foreach ($member->getErrors() as $field => $fieldErrors) {
                foreach ((array)$fieldErrors as $error) {
                    $errors[] = "$field: $error";
                }
            }
            throw new ImexException('Failed to save quota member: ' . implode(', ', $errors));
        }

        $this->successfulModelsCount++;
    }

    private function findOrCreateQuota(string $name): Quota
    {
        $criteria = new \CDbCriteria();
        $criteria->condition = 'sid = :sid AND name = :name';
        $criteria->params = [':sid' => $this->survey->sid, ':name' => $name];

        /** @var Quota|null $quota */
        $quota = Quota::model()->find($criteria);
        
        if (!$quota) {
            return new Quota();
        }

        return $quota;
    }

    private function findQuotaByName(string $name): ?Quota
    {
        $criteria = new \CDbCriteria();
        $criteria->condition = 'sid = :sid AND name = :name';
        $criteria->params = [':sid' => $this->survey->sid, ':name' => $name];

        /** @var Quota|null */
        return Quota::model()->find($criteria);
    }

    private function findQuestionByCode(string $code): ?Question
    {
        $criteria = new \CDbCriteria();
        $criteria->condition = 'sid = :sid AND title = :title';
        $criteria->params = [':sid' => $this->survey->sid, ':title' => $code];

        /** @var Question|null */
        return Question::model()->find($criteria);
    }

    private function findOrCreateQuotaMember(Quota $quota, Question $question, string $code): QuotaMember
    {
        // Find existing quota member for this question in this quota (only one allowed per question)
        $criteria = new \CDbCriteria();
        $criteria->condition = 'quota_id = :quota_id AND qid = :qid';
        $criteria->params = [
            ':quota_id' => $quota->id,
            ':qid' => $question->qid
        ];

        /** @var QuotaMember|null $member */
        $member = QuotaMember::model()->find($criteria);
        
        if (!$member) {
            $member = new QuotaMember();
        }

        return $member;
    }


    private function importQuotaLanguageSettings(Quota $quota, array $attributes): void
    {
        $surveyLanguages = $this->getSurveyLanguages();

        foreach ($surveyLanguages as $language) {
            $message = $attributes['message-' . $language] ?? '';
            $url = $attributes['url-' . $language] ?? '';
            $urlDescription = $attributes['url_description-' . $language] ?? '';

            // Check if any language-specific columns exist in import data
            $hasLanguageData = array_key_exists('message-' . $language, $attributes) ||
                             array_key_exists('url-' . $language, $attributes) ||
                             array_key_exists('url_description-' . $language, $attributes);

            // Skip processing if no language-specific columns exist in import
            if (!$hasLanguageData) {
                continue;
            }

            // Validate URL requirement when autoload is enabled
            if ($quota->autoload_url == 1 && trim($url) === '') {
                throw new ImexException("URL cannot be empty when autoload_url is enabled for quota '{$quota->name}'");
            }

            $setting = $this->findOrCreateLanguageSetting($quota, $language);
            
            $setting->quotals_quota_id = $quota->id;
            $setting->quotals_language = $language;
            $setting->quotals_message = trim($message) ?: '';
            $setting->quotals_url = trim($url) ?: '';
            $setting->quotals_urldescrip = trim($urlDescription) ?: '';

            // Skip validation and save directly to avoid LimeSurvey's incorrect validation rules
            $setting->save(false);
        }
    }

    private function findOrCreateLanguageSetting(Quota $quota, string $language): QuotaLanguageSetting
    {
        $criteria = new \CDbCriteria();
        $criteria->condition = 'quotals_quota_id = :quota_id AND quotals_language = :language';
        $criteria->params = [':quota_id' => $quota->id, ':language' => $language];

        /** @var QuotaLanguageSetting|null $setting */
        $setting = QuotaLanguageSetting::model()->find($criteria);
        
        if (!$setting) {
            $setting = new QuotaLanguageSetting();
        }

        return $setting;
    }

    private function getSurveyLanguages(): array
    {
        $languages = [$this->survey->language];
        
        if (!empty($this->survey->additional_languages)) {
            $additionalLangs = explode(' ', trim($this->survey->additional_languages));
            $languages = array_merge($languages, $additionalLangs);
        }

        return array_unique($languages);
    }


    private function validateImportData(): void
    {
        if (empty($this->readerData)) {
            $this->addError('data', 'No data to import');
            return;
        }

        $requiredColumns = ['type', 'name', 'value'];
        $firstRow = $this->readerData[0] ?? [];
        
        foreach ($requiredColumns as $column) {
            if (!array_key_exists($column, $firstRow)) {
                $this->addError('format', "Missing required column: {$column}");
            }
        }

        // Validate that all referenced questions exist
        $this->validateQuestionReferences();
    }

    private function validateQuestionReferences(): void
    {
        $missingQuestions = [];
        $duplicateQuestions = [];
        $currentQuotaName = '';
        $quotaQuestions = []; // Track questions per quota (only one member per question allowed)

        foreach ($this->readerData as $row) {
            $type = $row['type'] ?? '';
            
            if ($type === 'Q') {
                $currentQuotaName = $row['name'] ?? '';
                $quotaQuestions[$currentQuotaName] = []; // Reset for new quota
            } elseif ($type === 'QM') {
                $questionCode = $row['name'] ?? '';
                $answerCode = $row['value'] ?? '';
                
                if (empty($questionCode)) {
                    $this->addError('validation', "Quota member in quota '$currentQuotaName' has empty question code");
                    continue;
                }
                
                if (empty($answerCode)) {
                    $this->addError('validation', "Quota member in quota '$currentQuotaName' has empty answer code");
                    continue;
                }

                // Check for duplicate question within same quota in import file
                // LimeSurvey quota members use AND logic, so only one member per question per quota is allowed
                if (in_array($questionCode, $quotaQuestions[$currentQuotaName] ?? [])) {
                    $duplicateQuestions[] = "Question '$questionCode' appears multiple times in quota '$currentQuotaName'";
                } else {
                    $quotaQuestions[$currentQuotaName][] = $questionCode;
                }

                // Check if question exists
                $question = $this->findQuestionByCode($questionCode);
                if (!$question) {
                    $missingQuestions[] = $questionCode;
                    continue;
                }

                // Note: If quota member already exists in database for this question in this quota,
                // it will be updated by the findOrCreateQuotaMember method during import
            }
        }

        // Report duplicate question errors
        if (!empty($duplicateQuestions)) {
            foreach ($duplicateQuestions as $error) {
                $this->addError('validation', $error);
            }
        }

        // Report missing question errors
        if (!empty($missingQuestions)) {
            $uniqueMissing = array_unique($missingQuestions);
            $this->addError('validation', 'Referenced questions not found in survey: ' . implode(', ', $uniqueMissing));
        }
    }
}
