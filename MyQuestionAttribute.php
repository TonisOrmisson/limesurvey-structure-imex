<?php

class MyQuestionAttribute extends CModel
{
    const ATTRIBUTE_HIDE_TIP = "hide_tip";
    const ATTRIBUTE_EXCLUDE = "exclude_all_others";
    const ATTRIBUTE_HIDDEN = "hidden";
    const ATTRIBUTE_TEXT_INPUT_WIDTH = "text_input_width";
    const ATTRIBUTE_ANSWER_WIDTH = "answer_width";
    const ATTRIBUTE_MAX_ANSWERS = "max_answers";
    const ATTRIBUTE_RANDOM_ORDER = "random_order";

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
    /** @var integer */
    public $random_order;

    public function rules()
    {
        return [
            [static::ATTRIBUTE_HIDDEN, 'filterIntegers'],
            [static::ATTRIBUTE_HIDE_TIP, 'filterIntegers'],
            [static::ATTRIBUTE_TEXT_INPUT_WIDTH, 'filterIntegers'],
            [static::ATTRIBUTE_MAX_ANSWERS, 'filterIntegers'],
            [static::ATTRIBUTE_RANDOM_ORDER, 'filterIntegers'],

            [static::ATTRIBUTE_HIDE_TIP, 'numerical', 'integerOnly'=>true,  'max'=>1, 'allowEmpty' => true],
            [static::ATTRIBUTE_HIDDEN, 'numerical', 'integerOnly'=>true,  'max'=>1, 'allowEmpty' => true],
            [static::ATTRIBUTE_TEXT_INPUT_WIDTH, 'numerical', 'integerOnly'=>true,  'max'=>12, 'allowEmpty' => true],
            [static::ATTRIBUTE_MAX_ANSWERS, 'numerical', 'integerOnly'=>true,  'max'=>1000, 'allowEmpty' => true],
            [static::ATTRIBUTE_RANDOM_ORDER, 'numerical', 'integerOnly'=>true,  'max'=>1, 'allowEmpty' => true],
        ];
    }

    /**
     * Custom filter method to allow empty values
     * @param $attributeName
     * @return bool
     */
    public function  filterIntegers($attributeName)
    {
        // allow empty
        if(is_null($this->{$attributeName})) {
            return true;
        }
        $this->{$attributeName} = intval($this->{$attributeName});
        return true;
    }


    public function attributeLabels() {
        return [
            static::ATTRIBUTE_HIDE_TIP => "Hide tip",
            //static::ATTRIBUTE_EXCLUDE => "Exclusive option",
            static::ATTRIBUTE_HIDDEN => "Always hidden",
            static::ATTRIBUTE_TEXT_INPUT_WIDTH => "Text input width",
            static::ATTRIBUTE_ANSWER_WIDTH => "Answer width",
            static::ATTRIBUTE_MAX_ANSWERS => "Maximum answers",
            static::ATTRIBUTE_RANDOM_ORDER => "Random order",

        ];
    }

    public function allowedValues() : array
    {
        return [
            static::ATTRIBUTE_HIDE_TIP => "integer 0-1",
            //static::ATTRIBUTE_EXCLUDE => "eg '1;2;3'",
            static::ATTRIBUTE_HIDDEN => "integer 0-1",
            static::ATTRIBUTE_TEXT_INPUT_WIDTH => "integer 1-12",
            static::ATTRIBUTE_ANSWER_WIDTH => "integer 1-12",
            static::ATTRIBUTE_MAX_ANSWERS => "integer 1-1000",
            static::ATTRIBUTE_RANDOM_ORDER => "integer 0-1",

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
            //static::ATTRIBUTE_EXCLUDE,
            static::ATTRIBUTE_HIDDEN,
            static::ATTRIBUTE_TEXT_INPUT_WIDTH,
            static::ATTRIBUTE_ANSWER_WIDTH,
            static::ATTRIBUTE_MAX_ANSWERS,
            static::ATTRIBUTE_RANDOM_ORDER,
        ];
    }
}
