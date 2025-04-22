<?php

namespace SmartMailer\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SmartMailer\Models\MailLog;
use SmartMailer\SmartMailer;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class MailLogController extends Controller
{
    protected $smartMailer;

    public function __construct(SmartMailer $smartMailer)
    {
        $this->smartMailer = $smartMailer;
    }

    /**
     * Display the mail log dashboard
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = MailLog::with('errors');

        // Apply filters
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            if ($request->status === 'failed') {
                $query->where('status', 'failed');
            } elseif ($request->status === 'sent') {
                $query->where('status', 'sent');
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                  ->orWhere('from_email', 'like', "%{$search}%")
                  ->orWhere('to_email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('smtp_server')) {
            $query->where('connection_name', $request->smtp_server);
        }

        $logs = $query->latest()->paginate(20)->withQueryString();
        $smtpStatus = $this->getSmtpStatus();
        $stats = $this->getEmailStats();
        $types = MailLog::distinct()->pluck('type');
        $smtpServers = MailLog::distinct()->pluck('connection_name');
        $smtpStats = $this->getDetailedSmtpStats();

        return view('smartmailer::dashboard', compact(
            'logs',
            'smtpStatus',
            'stats',
            'types',
            'smtpServers',
            'smtpStats'
        ));
    }

    /**
     * Retry sending a failed email
     *
     * @param MailLog $log
     * @return \Illuminate\Http\RedirectResponse
     */
    public function retry(MailLog $log)
    {
        try {
            if (!$log->mailable_data) {
                throw new \Exception('No mailable data found for this log.');
            }

            $mailable = unserialize($log->mailable_data);

            try {
                $this->smartMailer
                    ->to($log->to_email)
                    ->type($log->type)
                    ->send($mailable);

                $log->update(['status' => 'queued']);

                return redirect()
                    ->back()
                    ->with('success', 'Email has been queued for retry.');
            } catch (\Exception $e) {
                $log->errors()->create([
                    'error_message' => $e->getMessage(),
                    'error_code' => $e->getCode(),
                    'stack_trace' => $e->getTraceAsString(),
                    'attempted_at' => now()
                ]);

                throw $e;
            }
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to retry email: ' . $e->getMessage());
        }
    }

    /**
     * Bulk retry multiple failed emails
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function bulkRetry(Request $request)
    {
        $logIds = $request->input('log_ids', []);
        $successCount = 0;
        $failureCount = 0;

        foreach ($logIds as $logId) {
            try {
                $log = MailLog::findOrFail($logId);

                if (!$log->mailable_data) {
                    throw new \Exception('No mailable data found for log #' . $logId);
                }

                $mailable = unserialize($log->mailable_data);

                $this->smartMailer
                    ->to($log->to_email)
                    ->type($log->type)
                    ->send($mailable);

                $log->update(['status' => 'queued']);
                $successCount++;
            } catch (\Exception $e) {
                // Log the error
                if (isset($log)) {
                    $log->create([
                        'error_message' => $e->getMessage(),
                        'error_code' => $e->getCode(),
                        'stack_trace' => $e->getTraceAsString(),
                        'attempted_at' => now()
                    ]);
                }
                $failureCount++;
            }
        }

        return redirect()
            ->back()
            ->with('success', "{$successCount} emails queued for retry. {$failureCount} failed.");
    }

    /**
     * Get SMTP server status
     *
     * @return array
     */
    protected function getSmtpStatus()
    {
        $config = config('smart_mailer.connections');
        $status = [];

        foreach ($config as $connection) {
            $cacheKey = 'smtp_status_' . $connection['name'];
            $lastError = Cache::get($cacheKey);

            $status[] = [
                'name' => $connection['name'],
                'host' => $connection['host'],
                'last_error' => $lastError,
                'status' => $lastError ? 'error' : 'operational',
                'last_used' => Cache::get('smtp_last_used_' . $connection['name'])
            ];
        }

        return $status;
    }

    /**
     * Display the details of a specific mail log
     *
     * @param MailLog $log
     * @return \Illuminate\View\View
     */
    public function show(MailLog $log)
    {
        return view('smartmailer::dashboard.show', compact('log'));
    }

    /**
     * Get mail statistics
     *
     * @return array
     */
    protected function getEmailStats()
    {
        $today = now()->startOfDay();
        $thisWeek = now()->startOfWeek();
        $thisMonth = now()->startOfMonth();

        return [
            'today' => [
                'sent' => MailLog::whereDate('created_at', $today)->where('status', 'sent')->count(),
                'failed' => MailLog::whereDate('created_at', $today)->where('status', 'failed')->count()
            ],
            'this_week' => [
                'sent' => MailLog::where('created_at', '>=', $thisWeek)->where('status', 'sent')->count(),
                'failed' => MailLog::where('created_at', '>=', $thisWeek)->where('status', 'failed')->count()
            ],
            'this_month' => [
                'sent' => MailLog::where('created_at', '>=', $thisMonth)->where('status', 'sent')->count(),
                'failed' => MailLog::where('created_at', '>=', $thisMonth)->where('status', 'failed')->count()
            ]
        ];
    }

    /**
     * Get detailed SMTP server statistics
     *
     * @return array
     */
    protected function getDetailedSmtpStats()
    {
        $stats = [];
        $connections = config('smart_mailer.connections');

        foreach ($connections as $connection) {
            $name = $connection['name'];
            $today = Carbon::today();

            $stats[$name] = [
                'total_sent' => MailLog::where('connection_name', $name)
                    ->where('status', 'sent')
                    ->count(),
                'total_failed' => MailLog::where('connection_name', $name)
                    ->where('status', 'failed')
                    ->count(),
                'sent_today' => MailLog::where('connection_name', $name)
                    ->where('status', 'sent')
                    ->whereDate('created_at', $today)
                    ->count(),
                'avg_response_time' => MailLog::where('connection_name', $name)
                    ->whereNotNull('sent_at')
                    ->whereNotNull('queued_at')
                    ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, queued_at, sent_at)) as avg_time')
                    ->value('avg_time')
            ];
        }

        return $stats;
    }
}
