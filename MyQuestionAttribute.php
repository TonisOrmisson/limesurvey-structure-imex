<?php

class MyQuestionAttribute extends CModel
{
    const ATTRIBUTE_HIDE_TIP = "hide_tip";
    const ATTRIBUTE_EXCLUDE = "exclude_all_others";
    const ATTRIBUTE_HIDDEN = "hidden";
    const ATTRIBUTE_TEXT_INPUT_WIDTH = "text_input_width";
    const ATTRIBUTE_ANSWER_WIDTH = "answer_width";
    const ATTRIBUTE_MAX_ANSWERS = "max_answers";

    /** @var integer */
    public $hide_tip;
    /** @var string */
    public $exclude_all_others; // eg "1;2;3"
    /** @var int */
    public $hidden;
    /** @var integer */
    public $text_input_width;
    /** @var int */
    public $answer_width;
    /** @var integer */
    public $max_answers;

    public function rules()
    {
        return [
            [static::ATTRIBUTE_HIDDEN, 'filter', 'filter'=>'intval'],
            [static::ATTRIBUTE_HIDE_TIP, 'numerical', 'integerOnly'=>true,  'max'=>1],
            [static::ATTRIBUTE_HIDDEN, 'numerical', 'integerOnly'=>true,  'max'=>1],
            [static::ATTRIBUTE_TEXT_INPUT_WIDTH, 'numerical', 'integerOnly'=>true,  'max'=>12],
            [static::ATTRIBUTE_MAX_ANSWERS, 'numerical', 'integerOnly'=>true,  'max'=>1000],
        ];
    }


    public function attributeLabels() {
        return [
            static::ATTRIBUTE_HIDE_TIP => "Hide tip",
            static::ATTRIBUTE_EXCLUDE => "Exclusive option",
            static::ATTRIBUTE_HIDDEN => "Always hidden",
            static::ATTRIBUTE_TEXT_INPUT_WIDTH => "Text input width",
            static::ATTRIBUTE_ANSWER_WIDTH => "Answer width",
            static::ATTRIBUTE_MAX_ANSWERS => "Maximum answers",

        ];
    }

    public function allowedValues() : array
    {
        return [
            static::ATTRIBUTE_HIDE_TIP => "integer 0-1",
            static::ATTRIBUTE_EXCLUDE => "eg '1;2;3'",
            static::ATTRIBUTE_HIDDEN => "integer 0-1",
            static::ATTRIBUTE_TEXT_INPUT_WIDTH => "integer 1-12",
            static::ATTRIBUTE_ANSWER_WIDTH => "integer 1-12",
            static::ATTRIBUTE_MAX_ANSWERS => "integer 1-1000",

        ];
    }

    /**
     * Returns the list of attribute names of the model.
     * @return array list of attribute names.
     */
    public function attributeNames()
    {
        return  [
            static::ATTRIBUTE_HIDE_TIP,
            static::ATTRIBUTE_EXCLUDE,
            static::ATTRIBUTE_HIDDEN,
            static::ATTRIBUTE_TEXT_INPUT_WIDTH,
            static::ATTRIBUTE_ANSWER_WIDTH,
            static::ATTRIBUTE_MAX_ANSWERS,
        ];
    }
}
