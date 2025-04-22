<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use SmartMailer\SmartMailer;
use SmartMailer\Services\MailLogger;
use SmartMailer\SmartMailable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Exception;
use Swift_SmtpTransport;
use Swift_Mailer;

class SmartMailerTest extends TestCase
{
    protected $smartMailer;
    protected $logger;
    protected $testConfig;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->logger = $this->createMock(MailLogger::class);
        
        // Setup test configuration
        $this->testConfig = [
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
            ]
        ];

        config(['smart_mailer' => $this->testConfig]);
        
        $this->smartMailer = new SmartMailer($this->logger);
    }

    /** @test */
    public function it_requires_recipient_before_sending()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Recipient not specified');
        
        $mailable = new TestMailable;
        $this->smartMailer->type('marketing')->send($mailable);
    }

    /** @test */
    public function it_requires_type_before_sending()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Email type not specified');
        
        $mailable = new TestMailable;
        $this->smartMailer->to('test@example.com')->send($mailable);
    }

    /** @test */
    public function it_uses_specific_mailer_when_configured()
    {
        Mail::fake();
        
        $mailable = new TestMailable;
        $result = $this->smartMailer
            ->to('test@example.com')
            ->type('support')
            ->send($mailable);
        
        $this->assertTrue($result);
        Mail::assertSent(TestMailable::class, 1);
    }

    /** @test */
    public function it_falls_back_to_next_smtp_when_first_fails()
    {
        $this->logger->expects($this->once())
            ->method('logError');

        $mailable = new TestMailable;
        
        // Mock first SMTP to fail
        Mail::shouldReceive('send')
            ->once()
            ->andThrow(new Exception('SMTP Error'));
        
        // Mock second SMTP to succeed
        Mail::shouldReceive('send')
            ->once()
            ->andReturn(true);

        $result = $this->smartMailer
            ->to('test@example.com')
            ->type('marketing')
            ->send($mailable);
        
        $this->assertTrue($result);
    }

    /** @test */
    public function it_throws_exception_when_all_smtp_servers_fail()
    {
        $this->logger->expects($this->exactly(2))
            ->method('logError');

        $mailable = new TestMailable;
        
        Mail::shouldReceive('send')
            ->times(2)
            ->andThrow(new Exception('SMTP Error'));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to send email after trying all available SMTP connections');

        $this->smartMailer
            ->to('test@example.com')
            ->type('marketing')
            ->send($mailable);
    }

    /** @test */
    public function it_rotates_connections_using_round_robin()
    {
        Cache::shouldReceive('increment')
            ->once()
            ->andReturn(1);

        $connections = $this->smartMailer->getAllAvailableConnections('rotate');
        
        $this->assertEquals('smtp1', $connections[0]['name']);
        
        Cache::shouldReceive('increment')
            ->once()
            ->andReturn(2);

        $connections = $this->smartMailer->getAllAvailableConnections('rotate');
        
        $this->assertEquals('smtp2', $connections[0]['name']);
    }

    /** @test */
    public function it_handles_random_connection_selection()
    {
        config(['smart_mailer.strategy' => 'random']);
        
        $connections = $this->smartMailer->getAllAvailableConnections('rotate');
        
        $this->assertContains($connections[0]['name'], ['smtp1', 'smtp2']);
    }

    /** @test */
    public function it_prioritizes_specified_mailer_in_connection_list()
    {
        $connections = $this->smartMailer->getAllAvailableConnections('smtp2');
        
        $this->assertEquals('smtp2', $connections[0]['name']);
    }

    /** @test */
    public function it_queues_smart_mailable()
    {
        $mailable = new TestQueuedMailable;
        
        Mail::shouldReceive('queue')
            ->once()
            ->with($mailable)
            ->andReturn(true);

        $result = $this->smartMailer
            ->to('test@example.com')
            ->type('marketing')
            ->send($mailable);
        
        $this->assertTrue($result);
    }

    /** @test */
    public function it_handles_cc_and_bcc_recipients()
    {
        Mail::fake();
        
        $mailable = new TestMailableWithCcBcc;
        $result = $this->smartMailer
            ->to('test@example.com')
            ->type('marketing')
            ->send($mailable);
        
        Mail::assertSent(TestMailableWithCcBcc::class, function ($mail) {
            return $mail->cc === ['cc@example.com'] &&
                   $mail->bcc === ['bcc@example.com'];
        });
    }

    /** @test */
    public function it_handles_empty_smtp_connections_list()
    {
        config(['smart_mailer.connections' => []]);
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No SMTP connections configured');
        
        $this->smartMailer->getAllAvailableConnections('rotate');
    }

    /** @test */
    public function it_handles_invalid_email_type()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No configuration found for email type');
        
        $mailable = new TestMailable;
        $this->smartMailer
            ->to('test@example.com')
            ->type('invalid_type')
            ->send($mailable);
    }

    /** @test */
    public function it_retries_failed_connection_after_timeout()
    {
        $mailable = new TestMailable;
        
        // First attempt fails with timeout
        Mail::shouldReceive('send')
            ->once()
            ->andThrow(new Exception('Connection timed out'));
        
        // Second attempt succeeds
        Mail::shouldReceive('send')
            ->once()
            ->andReturn(true);

        $result = $this->smartMailer
            ->to('test@example.com')
            ->type('marketing')
            ->send($mailable);
        
        $this->assertTrue($result);
    }

    /** @test */
    public function it_handles_multiple_recipients()
    {
        Mail::fake();
        
        $mailable = new TestMailable;
        $result = $this->smartMailer
            ->to(['test1@example.com', 'test2@example.com'])
            ->type('marketing')
            ->send($mailable);
        
        Mail::assertSent(TestMailable::class, function ($mail) {
            return count($mail->to) === 2;
        });
    }

    /** @test */
    public function it_respects_queue_settings_per_email_type()
    {
        $this->testConfig['queue']['types']['marketing'] = [
            'connection' => 'redis',
            'queue' => 'marketing-emails',
            'timeout' => 120
        ];
        
        config(['smart_mailer' => $this->testConfig]);
        
        $mailable = new TestQueuedMailable;
        
        Mail::shouldReceive('queue')
            ->once()
            ->withArgs(function ($mail) {
                return $mail->queue === 'marketing-emails' &&
                       $mail->timeout === 120;
            })
            ->andReturn(true);

        $this->smartMailer
            ->to('test@example.com')
            ->type('marketing')
            ->send($mailable);
    }

    /** @test */
    public function it_logs_smtp_errors_correctly()
    {
        $error = new Exception('SMTP Authentication failed');
        
        $this->logger->expects($this->once())
            ->method('logError')
            ->with($error);

        Mail::shouldReceive('send')
            ->once()
            ->andThrow($error);

        try {
            $this->smartMailer
                ->to('test@example.com')
                ->type('marketing')
                ->send(new TestMailable);
        } catch (Exception $e) {
            $this->assertStringContainsString('SMTP Authentication failed', $e->getMessage());
        }
    }
}

class TestMailable extends SmartMailable
{
    public function build()
    {
        return $this->view('test-view')
                    ->subject('Test Email');
    }
}

class TestQueuedMailable extends SmartMailable implements \Illuminate\Contracts\Queue\ShouldQueue
{
    public function build()
    {
        return $this->view('test-view')
                    ->subject('Test Queued Email');
    }
}

class TestMailableWithCcBcc extends SmartMailable
{
    public $cc = ['cc@example.com'];
    public $bcc = ['bcc@example.com'];

    public function build()
    {
        return $this->view('test-view')
                    ->subject('Test Email with CC/BCC');
    }
} 