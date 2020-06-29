# ImageRatioValidator for Yii 2

`Image Ratio Validator` allows you to add image validation by its aspect ratio. 
## Install

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```bash
$ php composer.phar require --prefer-dist zakurdaev/yii2-image-ratio-validator "*"
```

or add

```json
"zakurdaev/yii2-image-ratio-validator": "*"
```

to the `require` section of your `composer.json` file.


## Usage

Once the extension is installed, simply use it in your code:

### Strict validate

```php
    public function rules()
    {
        return [
            [['image'], zakurdaev\imageratio\ImageRatioValidator::class, 'ratio' => 1600/1200],
        ];
    }
```

### Range validate

```php
    public function rules()
    {
        return [
            [['image'], zakurdaev\imageratio\ImageRatioValidator::class, 'ratioTo' => 1400/1200, 'ratioFrom' => 1600/1200],
        ];
    }
```

## License
The BSD License (BSD). Please see [License File](LICENSE.md) for more information.