Gii Code generator
==================

You can use the Gii code generator to create a job template.

Configuration
-------------

To use the Gii job generator you have to configure it like the following:

```php
if (!YII_ENV_TEST) {
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'Yiisoft\Yii\Gii\Module',
        'generators' => [
            'job' => [
                'class' => \Yiisoft\Yii\Queue\Gii\Generator::class,
            ],
        ],
    ];
}

```

After doing so you'll find the generator in the Gii menu.

![default](https://user-images.githubusercontent.com/1656851/29426628-e9a3e5ae-838f-11e7-859f-6f3cb8649f02.png)
