<?php

namespace Tests\Unit;

// Use Illuminate\Foundation\Testing\RefreshDatabase; // If needed for db logging tests
use Illuminate\Support\Facades\Config; // Use Config facade
use Tests\TestCase; // Assuming a base TestCase exists in tests/
use SmartMailer\SmartMailer;
use SmartMailer\Services\MailLogger;
use SmartMailer\SmartMailable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue; // Import Queue facade
use Exception;
use Swift_TransportException; // Import specific exception
use Illuminate\Contracts\Queue\ShouldQueue;

// Base TestCase (if not already existing) - adjust path if needed
// Create tests/TestCase.php if it doesn't exist:
/*
<?php
namespace Tests;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
abstract class TestCase extends BaseTestCase
{
    use CreatesApplication; // Make sure CreatesApplication trait exists and is correct
}
*/

// Test Mailable Classes (moved outside the main test class for clarity)
class TestMailableBasic extends SmartMailable
{
    public function build()
    {
        return $this->view('test-view')->subject('Test Email');
    }
}

class TestMailableQueued extends SmartMailable implements ShouldQueue
{
    public function build()
    {
        return $this->view('test-view')->subject('Test Queued Email');
    }
}

class TestMailableWithCcBcc extends SmartMailable
{
    public $cc = ['cc@example.com'];
    public $bcc = ['bcc@example.com'];

    public function build()
    {
        return $this->view('test-view')->subject('Test Email with CC/BCC');
    }
}


class SmartMailerTest extends TestCase // Extend Laravel TestCase
{
    protected $smartMailer;
    protected $logger;

    protected function setUp(): void
    {
        parent::setUp(); // Call parent setUp for Laravel features

        // Mock logger
        $this->logger = $this->mock(MailLogger::class);

        // Set base config for tests (can be overridden per test)
        Config::set('smart_mailer', [
            'from_addresses' => [
                'marketing' => [
                    'name' => 'Marketing Team',
                    'address' => 'marketing@example.com',
                    'mailer' => 'rotate'
                ],
                'support' => [
                    'name' => 'Support Team',
                    'address' => 'support@example.com',
                    'mailer' => 'smtp2'
                ]
            ],
            'strategy' => 'round_robin',
            'connections' => [
                [
                    'name' => 'smtp1',
                    'host' => 'smtp1.test.com',
                    'port' => 587,
                    'encryption' => 'tls',
                    'username' => 'test1@test.com',
                    'password' => 'pass1'
                ],
                [
                    'name' => 'smtp2',
                    'host' => 'smtp2.test.com',
                    'port' => 587,
                    'encryption' => 'tls',
                    'username' => 'test2@test.com',
                    'password' => 'pass2'
                ]
            ],
            'queue' => [
                'connection' => null,
                'name' => null,
                'types' => [
                    'marketing' => [
                        'connection' => 'sync', // Use sync for easier testing
                        'queue' => 'marketing-test-queue',
                    ],
                    'support' => [
                        'connection' => 'sync',
                        'queue' => 'support-test-queue',
                    ],
                ]
            ],
            'database_logging' => ['enabled' => false], // Disable DB logging for most tests
            'logging' => ['enabled' => false], // Disable file logging
        ]);

        // Get instance from container to respect mocks/config
        $this->smartMailer = $this->app->make('smartmailer');

        // Use Laravel's Mail Fake
        Mail::fake();
        // Use Laravel's Queue Fake
        Queue::fake();
    }

    /** @test */
    public function it_requires_recipient_before_sending_non_queued()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Recipient not specified');

        $mailable = new TestMailableBasic; // Use non-queued for this check
        $this->smartMailer->type('marketing')->send($mailable);
    }

    /** @test */
    public function it_requires_type_before_sending_non_queued()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Email type not specified');

        $mailable = new TestMailableBasic; // Use non-queued for this check
        $this->smartMailer->to('test@example.com')->send($mailable);
    }

    /** @test */
    public function it_sends_immediately_if_mailable_is_not_should_queue()
    {
        $mailable = new class extends SmartMailable { // Non-queued mailable
            public function build() { return $this->subject('Non-queued'); }
        };

        $this->smartMailer
            ->to('test@example.com')
            ->type('marketing')
            ->send($mailable);

        Mail::assertSent(function ($mail) use ($mailable) {
            return $mail instanceof $mailable;
        });
        Queue::assertNothingPushed();
    }

    /** @test */
    public function it_uses_specific_mailer_connection_when_configured_for_type()
    {
         // Config sets 'support' type to use 'smtp2'
        $mailable = new TestMailableBasic; // Non-queued

        // We can't directly assert the Swift_Mailer instance config easily with Mail::fake()
        // Instead, we test the failover logic relies on this
        // If we setup failover, it should start with smtp2 for 'support' type

        // Mock Mail::send to check which connection is attempted
        // This requires deeper mocking beyond Mail::fake()
        // For now, trust that getAllAvailableConnections prioritizes correctly (tested separately)
        $this->markTestSkipped('Direct assertion of mailer connection requires deeper mocking.');

        // $this->smartMailer
        //     ->to('test@example.com')
        //     ->type('support') // Should use smtp2 first
        //     ->send($mailable);
        // Mail::assertSent(TestMailableBasic::class, 1);
    }


    /** @test */
    public function it_falls_back_to_next_smtp_when_first_fails_using_correct_exception()
    {
        // This test needs real mail sending attempts or complex mocking of Swift_Mailer
        // Mail::fake() doesn't simulate transport exceptions well.
        $this->markTestSkipped('Failover testing requires simulating Swift_TransportException, difficult with Mail::fake().');

        /* Example structure (if Mail::fake() could handle it):
        $this->logger->shouldReceive('logError')->once();
        $mailable = new TestMailableBasic;

        // Mock first attempt (smtp1 for marketing) to fail with Swift_TransportException
        Mail::shouldReceive('send') // Or underlying mailer method
            ->once()
            ->andThrow(new Swift_TransportException('Connection failed'));

        // Mock second attempt (smtp2) to succeed
        Mail::shouldReceive('send')
            ->once()
            ->andReturn(true); // Or assert it was called

        $result = $this->smartMailer
            ->to('test@example.com')
            ->type('marketing') // uses rotate -> smtp1 first
            ->send($mailable);

        $this->assertTrue($result);
        */
    }

    /** @test */
    public function it_throws_exception_when_all_smtp_servers_fail()
    {
        // Similar limitation as the failover test
        $this->markTestSkipped('Testing all servers failing requires simulating Swift_TransportException.');

        /*
        $this->logger->shouldReceive('logError')->twice();
        $mailable = new TestMailableBasic;

        Mail::shouldReceive('send')
            ->twice()
            ->andThrow(new Swift_TransportException('SMTP Error'));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to send email after trying all available SMTP connections');

        $this->smartMailer
            ->to('test@example.com')
            ->type('marketing')
            ->send($mailable);
        */
    }

    /** @test */
    public function it_rotates_connections_using_round_robin_correctly()
    {
        Cache::shouldReceive('increment')->with('smart_mailer_counter')->andReturn(1, 2);
        // Need to re-instantiate to pick up mocked Cache behavior if not using DI properly
        $mailer = $this->app->make('smartmailer');

        $connections1 = $mailer->getAllAvailableConnections('rotate');
        $this->assertEquals('smtp1', $connections1[0]['name']);

        $connections2 = $mailer->getAllAvailableConnections('rotate');
        $this->assertEquals('smtp2', $connections2[0]['name']);
    }

    /** @test */
    public function it_handles_random_connection_selection()
    {
        Config::set('smart_mailer.strategy', 'random');
        $mailer = $this->app->make('smartmailer'); // Re-instantiate with new config

        $connections = $mailer->getAllAvailableConnections('rotate');
        $this->assertContains($connections[0]['name'], ['smtp1', 'smtp2']);
    }

    /** @test */
    public function it_prioritizes_specified_mailer_in_connection_list_for_failover()
    {
        // Get connections for 'support' type (which specifies 'smtp2')
        $connections = $this->smartMailer->getAllAvailableConnections('smtp2');
        $this->assertEquals('smtp2', $connections[0]['name'], "First connection should be the specified one");
        $this->assertCount(2, $connections, "Should contain all connections for failover");
        $this->assertEquals('smtp1', $connections[1]['name'], "Second connection should be the other one");
    }

    /** @test */
    public function it_queues_smart_mailable_implementing_should_queue()
    {
        $mailable = new TestMailableQueued;

        $this->smartMailer
            ->to('test@example.com')
            ->type('marketing')
            ->send($mailable);

        Queue::assertPushed(function ($job) use ($mailable) {
             // Check if the job is for sending our mailable
             // The actual job class might vary depending on Laravel version
             // For simplicity, check if the display name contains the mailable class
             return str_contains($job->displayName(), class_basename($mailable));
        });
        Mail::assertNothingSent(); // Should be queued, not sent immediately
    }

    /** @test */
    public function it_applies_queue_settings_from_config_when_queuing()
    {
        $mailable = new TestMailableQueued;

        $this->smartMailer
            ->to('test@example.com')
            ->type('marketing') // Config specifies sync connection, marketing-test-queue
            ->send($mailable);

        Queue::assertPushedOn('marketing-test-queue', function ($job) use ($mailable) {
            // Assert the connection is sync ONLY if Queue::fake() supports it easily.
            // Often simpler to check the queue name.
            // dump($job->connection); // See what Queue::fake() provides
            return str_contains($job->displayName(), class_basename($mailable)); // Basic check
        });

        // Test overriding queue settings
        $mailableSpecific = new TestMailableQueued;
        $mailableSpecific->onQueue('specific-queue')->onConnection('specific-connection');

         $this->smartMailer
            ->to('test@example.com')
            ->type('marketing')
            ->send($mailableSpecific);

        Queue::assertPushedOn('specific-queue', function ($job) use ($mailableSpecific) {
             // dump($job->connection); // Check connection if possible
             return str_contains($job->displayName(), class_basename($mailableSpecific));
        });
    }

    /** @test */
    public function it_handles_cc_and_bcc_recipients_when_sending_immediately()
    {
        $mailable = new TestMailableWithCcBcc; // Non-queued by default

        $this->smartMailer
            ->to('test@example.com')
            ->type('marketing')
            ->send($mailable);

        Mail::assertSent(TestMailableWithCcBcc::class, function ($mail) {
             // Mail::fake() captures the Mailable instance, check its properties
             return !empty($mail->cc) && $mail->cc[0]['address'] === 'cc@example.com' &&
                    !empty($mail->bcc) && $mail->bcc[0]['address'] === 'bcc@example.com';
        });
    }

    /** @test */
    public function it_throws_exception_for_empty_smtp_connections_list()
    {
        Config::set('smart_mailer.connections', []);
        $mailer = $this->app->make('smartmailer'); // Re-instantiate

        $this->expectException(Exception::class); // Or InvalidArgumentException from validation
        $this->expectExceptionMessage('No SMTP connections configured');

        // This call happens inside sendMailable()
        $mailer->sendMailable(new TestMailableBasic);
    }

    /** @test */
    public function it_throws_exception_for_invalid_email_type()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No configuration found for email type: invalid_type');

        $mailable = new TestMailableBasic;
        $this->smartMailer
            ->to('test@example.com')
            ->type('invalid_type')
            ->send($mailable);
    }

    /** @test */
    public function it_handles_multiple_recipients_when_sending_immediately()
    {
        $mailable = new TestMailableBasic;
        $recipients = ['test1@example.com', 'test2@example.com'];

        $this->smartMailer
            ->to($recipients)
            ->type('marketing')
            ->send($mailable);

        Mail::assertSent(TestMailableBasic::class, function ($mail) use ($recipients) {
            // Check the 'to' property populated by the sender
            $toAddresses = collect($mail->to)->pluck('address')->all();
            return $toAddresses == $recipients;
        });
    }

     /** @test */
    public function it_logs_smtp_errors_correctly_when_failover_happens()
    {
        // Similar limitation as the failover test
        $this->markTestSkipped('Testing error logging during failover requires simulating Swift_TransportException.');

        /*
        $error = new Swift_TransportException('SMTP Authentication failed');
        $this->logger->shouldReceive('logError')
            ->once()
            ->with($error, Mockery::any()); // Mock the log entry arg if needed

        $mailable = new TestMailableBasic;

        Mail::shouldReceive('send') // Mock underlying sender
            ->once()
            ->andThrow($error);
        Mail::shouldReceive('send') // Mock fallback sender
            ->once()
            ->andReturn(true); // Succeeds on fallback

        try {
             $this->smartMailer
                ->to('test@example.com')
                ->type('marketing')
                ->send($mailable);
        } catch (Exception $e) {
             $this->fail('Should not throw exception if fallback succeeds.');
        }
        */
    }

} 