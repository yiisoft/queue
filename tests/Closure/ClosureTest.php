<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Yii\Queue\Tests\closure;

use yii\helpers\Yii;
use Yiisoft\Yii\Queue\Closure\Behavior;
use Yiisoft\Yii\Queue\Drivers\Sync\Queue;
use Yiisoft\Yii\Queue\Serializers\JsonSerializer;
use Yiisoft\Yii\Queue\Tests\TestCase;

/**
 * Closure Test.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class ClosureTest extends TestCase
{
    public function testPush1()
    {
        $this->getQueue()->push(static function () {
            $fileName = Yii::getAlias('@runtime/job-1.lock');
            file_put_contents($fileName, '');
        });
        $this->getQueue()->run();
        $this->assertFileExists(Yii::getAlias('@runtime/job-1.lock'));
    }

    public function testPush2()
    {
        $fileName = Yii::getAlias('@runtime/job-2.lock');
        $this->getQueue()->push(static function () use ($fileName) {
            file_put_contents($fileName, '');
        });
        $this->getQueue()->run();
        $this->assertFileExists($fileName);
    }

    /**
     * @return Queue
     */
    protected function getQueue()
    {
        if (!$this->_queue) {
            $this->_queue = new Queue(new JsonSerializer());
            $this->_queue->handle = false;
            $this->_queue->attachBehavior('closure', Behavior::class);
        }

        return $this->_queue;
    }

    private $_queue;

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        foreach (glob(Yii::getAlias('@runtime/job-*.lock')) as $fileName) {
            unlink($fileName);
        }
        parent::tearDown();
    }
}
