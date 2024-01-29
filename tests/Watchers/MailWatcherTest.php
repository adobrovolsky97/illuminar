<?php

namespace Adobrovolsky97\Illuminar\Tests\Watchers;

use Adobrovolsky97\Illuminar\Factories\StorageDriverFactory;
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

        $data = StorageDriverFactory::getDriverForConfig()->getData();

        $this->assertNotEmpty($data);
        $this->assertEquals(MailWatcher::getName(), $data[0]['type']);
        $this->assertNull($data[0]['mailable_class']);
    }

    /**
     * @return void
     */
    public function testWatcherRegistersEntryWithMailableClass(): void
    {
        illuminar()->trackMails();

        Mail::to(['to@illuminar.com'])->send(new TestMail(['key' => 'value']));

        $data = StorageDriverFactory::getDriverForConfig()->getData();
        $this->assertCount(1, $data);

        $this->assertEquals(MailWatcher::getName(), $data[0]['type']);
        $this->assertEquals(TestMail::class, $data[0]['mailable_class']);
    }
}
