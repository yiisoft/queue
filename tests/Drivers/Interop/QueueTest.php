<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Yii\Queue\Tests\Drivers\Interop;

use yii\helpers\Yii;
use Yiisoft\Yii\Queue\Drivers\Interop\Queue;
use Yiisoft\Yii\Queue\Tests\App\PriorityJob;
use Yiisoft\Yii\Queue\Tests\App\RetryJob;
use Yiisoft\Yii\Queue\Tests\Drivers\CliTestCase;

/**
 * AMQP Queue Test.
 *
 * @author Maksym Kotliar <kotlyar.maksim@gmail.com>
 */
class QueueTest extends CliTestCase
{
    public function testListen()
    {
        $this->startProcess('php yii queue/listen');
        $job = $this->createSimpleJob();
        $this->getQueue()->push($job);

        $this->assertSimpleJobDone($job);
    }

    public function testLater()
    {
        $this->startProcess('php yii queue/listen');
        $job = $this->createSimpleJob();
        $this->getQueue()->delay(2)->push($job);

        $this->assertSimpleJobLaterDone($job, 2);
    }

    public function testRetry()
    {
        $this->startProcess('php yii queue/listen');
        $job = new RetryJob(uniqid());
        $this->getQueue()->push($job);
        sleep(6);

        $this->assertFileExists($job->getFileName());
        $this->assertEquals('aa', file_get_contents($job->getFileName()));
    }

    public function testPriority()
    {
        $this->getQueue()->priority(3)->push(new PriorityJob(1));
        $this->getQueue()->priority(1)->push(new PriorityJob(5));
        $this->getQueue()->priority(2)->push(new PriorityJob(3));
        $this->getQueue()->priority(2)->push(new PriorityJob(4));
        $this->getQueue()->priority(3)->push(new PriorityJob(2));
        $this->startProcess('php yii queue/listen');
        sleep(3);

        $this->assertEquals('12345', file_get_contents(PriorityJob::getFileName()));
    }

    /**
     * @return Queue
     */
    protected function getQueue()
    {
        return $this->container->get('interopQueue');
    }

    protected function setUp(): void
    {
        if ('true' == getenv('EXCLUDE_AMQP_INTEROP')) {
            $this->markTestSkipped('Amqp tests are disabled for php 5.5');
        }

        parent::setUp();
    }
}
