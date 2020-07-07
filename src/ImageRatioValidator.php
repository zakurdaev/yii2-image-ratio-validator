<?php

namespace zakurdaev\imageratio;

use Yii;
use yii\helpers\ArrayHelper;
use yii\validators\FileValidator;
use yii\web\UploadedFile;

/**
 * Class ImageRatioValidator
 *
 * @author Andrey Zakurdaev <andrey@zakurdaev.pro>
 * @link https://github.com/zakurdaev/yii2-image-ratio-validator
 * @license https://github.com/zakurdaev/yii2-image-ratio-validator/blob/master/LICENSE.md
 */
class ImageRatioValidator extends FileValidator
{
    /**
     * @var string the error message used when the uploaded file is not an image.
     * You may use the following tokens in the message:
     *
     * - {attribute}: the attribute name
     * - {file}: the uploaded file name
     */
    public $notImage;

    /**
     * @var string the error message used when the uploaded file has a bad aspect ratio
     * You may use the following tokens in the message:
     *
     * - {attribute}: the attribute name
     * - {file}: the uploaded file name
     * - {ratio}: Current aspect ratio
     */
    public $wrongRatio;

    /**
     * @var array list of image aspect ratios for comparison.
     *
     * Usage:
     * 16/9
     * [16/9, 3/2]
     * [['to' => 16/9, 'from' => 3/2]]
     */
    public $ratios;

    /**
     * @var string translation message file category name for i18n.
     *
     * @see [[\yii\i18n\I18N]]
     */
    protected $messageCategory = 'image-ratio-validator';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        $this->initI18N();

        if (is_null($this->notImage)) {
            $this->notImage = Yii::t($this->messageCategory, 'The file "{file}" is not an image.');
        }

        if (is_null($this->wrongRatio)) {
            $this->wrongRatio = Yii::t($this->messageCategory, 'Image "{file}" has an incorrect aspect ratio {ratio}.');
        }
    }

    /**
     * Yii i18n messages configuration for generating translations
     */
    public function initI18N()
    {
        $config = [
            'class' => 'yii\i18n\PhpMessageSource',
            'basePath' => __DIR__ . "/messages",
            'forceTranslation' => true,
        ];
        $globalConfig = ArrayHelper::getValue(Yii::$app->i18n->translations, "{$this->messageCategory}*", []);
        if (!empty($globalConfig)) {
            $config = array_merge($config, is_array($globalConfig) ? $globalConfig : (array)$globalConfig);
        }
        Yii::$app->i18n->translations["{$this->messageCategory}*"] = $config;
    }

    /**
     * {@inheritdoc}
     */
    protected function validateValue($value)
    {
        $result = parent::validateValue($value);

        return empty($result) ? $this->validateImage($value) : $result;
    }

    /**
     * Validates an image file.
     * @param UploadedFile $image uploaded file passed to check against a set of rules
     * @return array|null the error message and the parameters to be inserted into the error message.
     * Null should be returned if the data is valid.
     */
    protected function validateImage($image)
    {
        if (false === ($imageInfo = getimagesize($image->tempName))) {
            return [$this->notImage, ['file' => $image->name]];
        }

        list($width, $height) = $imageInfo;

        if ($width == 0 || $height == 0) {
            return [$this->notImage, ['file' => $image->name]];
        }

        $ratios = $this->ratios;
        if (!is_array($ratios) || !isset($ratios[0])) {
            $ratios = [$ratios];
        }

        $actualRatio = $width / $height;
        $returnError = true;
        foreach ($ratios as $ratio) {
            if (is_array($ratio) || is_object($ratio)) {
                $ratioFrom = ArrayHelper::getValue($ratio, 'from');
                $ratioTo = ArrayHelper::getValue($ratio, 'to');

                if (!is_numeric($ratioFrom) || !is_float($ratioFrom) || !is_numeric($ratioTo) || !is_float($ratioTo)) {
                    return ['Incorect validation attribute `ratios`', []];
                }

                if ($this->validateRatioBetween($actualRatio, $ratioFrom, $ratioTo)) {
                    $returnError = false;
                }
            } else {
                if (!is_numeric($ratio) || !is_float($ratio)) {
                    return ['Incorect validation attribute `ratios`', []];
                }

                if ($this->validateRatioEquality($actualRatio, $ratio)) {
                    $returnError = false;
                }
            }
        }

        if ($returnError) {
            return [$this->wrongRatio, ['file' => $image->name, 'ratio' => round($actualRatio, 2)]];
        }

        return null;
    }

    /**
     * Return decimal precision
     *
     * @param $number
     * @return int
     */
    protected function precision($number)
    {
        $explodeDigits = explode('.', (string)$number);
        return strlen((string)ArrayHelper::getValue($explodeDigits, 0, ''));
    }

    protected function validateRatioEquality($actualRatio, $checkedRatio) {
        $precision = $this->precision( $checkedRatio);
        $actualRatio = round($actualRatio, $precision);
        $checkedRatio = round($checkedRatio, $precision);

        return $actualRatio == $checkedRatio;
    }

    protected function validateRatioBetween($actualRatio, $ratioFrom, $ratioTo) {
        $precisionFrom = $this->precision($ratioFrom);
        $precisionTo = $this->precision($ratioTo);
        $precision = $precisionTo > $precisionFrom ? $precisionTo : $precisionFrom;

        $actualRatio = round($actualRatio, $precision);
        $ratioFrom = round($ratioFrom, $precision);
        $ratioTo = round($ratioTo, $precision);

        return ($actualRatio > $ratioFrom && $actualRatio < $ratioTo);
    }
}