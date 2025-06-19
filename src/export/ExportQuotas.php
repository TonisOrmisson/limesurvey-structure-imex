<?php

namespace tonisormisson\ls\structureimex\export;

use Quota;
use QuotaMember;
use QuotaLanguageSetting;
use Survey;
use tonisormisson\ls\structureimex\exceptions\ImexException;

/**
 * Class ExportQuotas
 * 
 * Exports survey quotas to Excel/CSV format including:
 * - Core quota information (name, limit, active, etc.)
 * - Quota members (question/answer conditions)
 * - Multi-language quota settings
 * 
 * Export format follows hierarchical structure similar to QuestionGroup → Questions:
 * - Q rows: Quota information with language-specific content
 * - QM rows: QuotaMember information (question code + answer code)
 */
class ExportQuotas extends AbstractExport
{
    protected $sheetName = 'quotas';

    /**
     * Get data for export
     * @return array Export data in hierarchical format
     * @throws ImexException
     */
    protected function getData(): array
    {
        $survey = $this->getSurvey();
        $quotas = $this->getQuotasFromSurvey($survey);
        
        if (empty($quotas)) {
            return [];
        }

        $exportData = [];
        foreach ($quotas as $quota) {
            // Add quota row (Q)
            $exportData[] = $this->buildQuotaRow($quota, $survey);
            
            // Add quota member rows (QM)
            $members = $this->getQuotaMembers($quota);
            foreach ($members as $member) {
                $exportData[] = $this->buildQuotaMemberRow($member, $survey);
            }
        }

        return $exportData;
    }

    /**
     * Get survey from parent class
     * @return Survey
     */
    private function getSurvey(): Survey 
    {
        return $this->survey;
    }

    /**
     * Get all quotas for the survey
     * @param Survey $survey
     * @return Quota[]
     */
    private function getQuotasFromSurvey(Survey $survey): array
    {
        $criteria = new \CDbCriteria();
        $criteria->condition = 'sid = :sid';
        $criteria->params = [':sid' => $survey->sid];
        $criteria->order = 'name ASC';

        /** @var Quota[] $quotas */
        $quotas = Quota::model()->findAll($criteria);
        return $quotas;
    }

    /**
     * Build quota row (type = "Q")
     * @param Quota $quota
     * @param Survey $survey
     * @return array Quota row data
     */
    private function buildQuotaRow(Quota $quota, Survey $survey): array
    {
        $row = [];
        $surveyLanguages = $this->getSurveyLanguages($survey);
        $languageSettings = $this->getQuotaLanguageSettings($quota);

        // Fixed columns
        $row['type'] = 'Q';
        $row['name'] = $quota->name;
        $row['value'] = $quota->qlimit;
        $row['active'] = $quota->active;
        $row['autoload_url'] = $quota->autoload_url;

        // Language-specific columns
        foreach ($surveyLanguages as $language) {
            $setting = $languageSettings[$language] ?? null;
            $row['message-' . $language] = $setting ? $setting->quotals_message : '';
            $row['url-' . $language] = $setting ? $setting->quotals_url : '';
            $row['url_description-' . $language] = $setting ? $setting->quotals_urldescrip : '';
        }

        return $row;
    }

    /**
     * Build quota member row (type = "QM")
     * @param QuotaMember $member
     * @param Survey $survey
     * @return array QuotaMember row data
     */
    private function buildQuotaMemberRow(QuotaMember $member, Survey $survey): array
    {
        $row = [];
        $surveyLanguages = $this->getSurveyLanguages($survey);
        $memberInfo = $this->getMemberInfo($member);

        // Fixed columns
        $row['type'] = 'QM';
        $row['name'] = $memberInfo['question_code'];
        $row['value'] = $member->code;
        $row['active'] = '';
        $row['autoload_url'] = '';

        // Language-specific columns (all empty for quota members)
        foreach ($surveyLanguages as $language) {
            $row['message-' . $language] = '';
            $row['url-' . $language] = '';
            $row['url_description-' . $language] = '';
        }

        return $row;
    }

    /**
     * Get quota members for a quota
     * @param Quota $quota
     * @return QuotaMember[]
     */
    private function getQuotaMembers(Quota $quota): array
    {
        $criteria = new \CDbCriteria();
        $criteria->condition = 'quota_id = :quota_id';
        $criteria->params = [':quota_id' => $quota->id];
        $criteria->order = 'qid ASC, code ASC';

        return QuotaMember::model()->findAll($criteria);
    }

    /**
     * Get quota language settings for a quota
     * @param Quota $quota
     * @return array Indexed by language code
     */
    private function getQuotaLanguageSettings(Quota $quota): array
    {
        $criteria = new \CDbCriteria();
        $criteria->condition = 'quotals_quota_id = :quota_id';
        $criteria->params = [':quota_id' => $quota->id];

        $settings = QuotaLanguageSetting::model()->findAll($criteria);
        
        $indexed = [];
        foreach ($settings as $setting) {
            $indexed[$setting->quotals_language] = $setting;
        }

        return $indexed;
    }

    /**
     * Get all languages for the survey
     * @param Survey $survey
     * @return array Array of language codes
     */
    private function getSurveyLanguages(Survey $survey): array
    {
        $languages = [$survey->language]; // Base language first
        
        if (!empty($survey->additional_languages)) {
            $additionalLangs = explode(' ', trim($survey->additional_languages));
            $languages = array_merge($languages, $additionalLangs);
        }

        return array_unique($languages);
    }


    /**
     * Get member information including question details
     * @param QuotaMember $member
     * @return array Member info with question_code
     */
    private function getMemberInfo(QuotaMember $member): array
    {
        // Get question to find question code (title)
        $question = \Question::model()->findByPk($member->qid);
        
        if (!$question) {
            return [
                'question_code' => 'UNKNOWN_Q' . $member->qid
            ];
        }

        return [
            'question_code' => $question->title
        ];
    }

    /**
     * Get export headers
     * @return array Column headers for export
     */
    protected function getHeaders(): array
    {
        $survey = $this->getSurvey();
        $languages = $this->getSurveyLanguages($survey);

        $headers = [
            'type',
            'name',
            'value', 
            'active',
            'autoload_url'
        ];

        // Language-specific columns
        foreach ($languages as $language) {
            $headers[] = 'message-' . $language;
            $headers[] = 'url-' . $language;
            $headers[] = 'url_description-' . $language;
        }

        return $headers;
    }

    /**
     * Write data to export file
     * Required by AbstractExport
     */
    protected function writeData(): void
    {
        $data = $this->getData();
        $headers = $this->getHeaders();

        foreach ($data as $rowData) {
            $values = [];
            foreach ($headers as $header) {
                $values[] = $rowData[$header] ?? '';
            }
            $dataRow = \OpenSpout\Common\Entity\Row::fromValues($values);
            $this->writer->addRow($dataRow);
        }
    }

    /**
     * Load headers for export
     * Required by AbstractExport
     */
    protected function loadHeader(): void
    {
        $this->header = $this->getHeaders();
    }
}
