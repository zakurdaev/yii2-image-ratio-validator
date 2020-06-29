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
     * @var float image aspect ratio for comparison.
     */
    public $ratio;

    /**
     * @var float minimum image aspect ratio for comparison.
     */
    public $ratioFrom;

    /**
     * @var float maximum image aspect ratio for comparison.
     */
    public $ratioTo;

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

        if (!is_null($this->ratio)) {
            $currentRatio = round($width / $height, $this->precision($this->ratio));
            $ratio = round($this->ratio, $this->precision($this->ratio));
            if ($currentRatio != $ratio) {
                return [$this->wrongRatio, ['file' => $image->name, 'ratio' => $currentRatio]];
            }
        } elseif (!is_null($this->ratioFrom) && !is_null($this->ratioTo)) {
            $precisionTo = $this->precision($this->ratioTo);
            $precisionFrom = $this->precision($this->ratioFrom);
            $precision = $precisionTo > $precisionFrom ? $precisionTo : $precisionFrom;
            $currentRatio = round($width / $height, $precision);
            $ratioTo = round($this->ratioTo, $precision);
            $ratioFrom = round($this->ratioFrom, $precision);
            if ($currentRatio < $ratioTo || $currentRatio > $ratioFrom) {
                return [$this->wrongRatio, ['file' => $image->name, 'ratio' => $currentRatio]];
            }
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
}
