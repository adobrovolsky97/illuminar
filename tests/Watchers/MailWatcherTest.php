<?php

namespace Adobrovolsky97\Illuminar\Tests\Watchers;

use Adobrovolsky97\Illuminar\DataCollector;
use Adobrovolsky97\Illuminar\Tests\Stubs\TestMail;
use Adobrovolsky97\Illuminar\Tests\TestCase;
use Adobrovolsky97\Illuminar\Watchers\MailWatcher;
use Illuminate\Support\Facades\Mail;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class MailWatcherTest
 */
class MailWatcherTest extends TestCase
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app->get('config')->set('mail.driver', 'array');
    }

    /**
     * @return void
     */
    public function testWatcherRegistersEntryWithNoMailableClass(): void
    {
        illuminar()->trackMails();

        Mail::raw('Illuminar is awesome!', function ($message) {
            $message->from('from@illuminar.com')
                ->to('to@illuminar.com')
                ->cc(['cc1@illuminar.com', 'cc2@illuminar.com'])
                ->bcc('bcc@illuminar.com')
                ->subject('Test email!');
        });

        illuminar()->stopTrackingMails();

        Mail::raw('Illuminar is awesome!', function ($message) {
            $message->from('from@illuminar.com')
                ->to('to@illuminar.com')
                ->cc(['cc1@illuminar.com', 'cc2@illuminar.com'])
                ->bcc('bcc@illuminar.com')
                ->subject('Test email!');
        });

        $batch = DataCollector::getBatch();
        $this->assertCount(1, $batch);

        $entry = reset($batch);
        $this->assertEquals(MailWatcher::getName(), $entry['type']);
        $this->assertNull($entry['mailable_class']);
    }

    /**
     * @return void
     */
    public function testWatcherRegistersEntryWithMailableClass(): void
    {
        illuminar()->trackMails();

        Mail::to(['to@illuminar.com'])->send(new TestMail(['key' => 'value']));

        $batch = DataCollector::getBatch();
        $this->assertCount(1, $batch);

        $entry = reset($batch);
        $this->assertEquals(MailWatcher::getName(), $entry['type']);
        $this->assertEquals(TestMail::class, $entry['mailable_class']);
    }
}
