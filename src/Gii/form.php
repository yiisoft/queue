<?php
/**
 * @var \Yiisoft\View\View $this
 * @var \Yiisoft\Widget\ActiveForm $form
 * @var \Yiisoft\Yii\Queue\Gii\Generator $generator
 */
?>
<?= $form->field($generator, 'jobClass')->textInput(['autofocus' => true]) ?>
<?= $form->field($generator, 'properties') ?>
<?= $form->field($generator, 'retryable')->checkbox() ?>
<?= $form->field($generator, 'ns') ?>
<?= $form->field($generator, 'baseClass') ?>
