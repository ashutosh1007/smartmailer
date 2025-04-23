@extends('smartmailer::layouts.app')

@section('content')
	<div class="container-fluid px-4">
		<div class="d-flex justify-content-between align-items-center mb-4">
			<h1 class="h3 mb-0 text-gray-800">
				<i class="bi bi-envelope-paper me-2"></i>Email Details
			</h1>
			<a href="{{ route('smartmailer.dashboard') }}" class="btn btn-outline-primary">
				<i class="bi bi-arrow-left me-1"></i>Back to Dashboard
			</a>
		</div>

		<div class="row">
			<!-- Main Email Information -->
			<div class="col-lg-8">
				<div class="card mb-4">
					<div class="card-header bg-light d-flex justify-content-between align-items-center">
						<h5 class="mb-0">
							<i class="bi bi-envelope me-2"></i>Email Information
						</h5>
						<span
							class="badge bg-{{ $log->status === 'sent' ? 'success' : ($log->status === 'queued' ? 'warning' : 'danger') }}">
							{{ ucfirst($log->status) }}
						</span>
					</div>
					<div class="card-body">
						<div class="row mb-3">
							<div class="col-md-3 text-muted">Subject:</div>
							<div class="col-md-9 fw-bold">{{ $log->subject }}</div>
						</div>
						<div class="row mb-3">
							<div class="col-md-3 text-muted">From:</div>
							<div class="col-md-9">
								<div class="d-flex align-items-center">
									<i class="bi bi-person-circle me-2"></i>
									{{ $log->from_name }} &lt;{{ $log->from_email }}&gt;
								</div>
							</div>
						</div>
						<div class="row mb-3">
							<div class="col-md-3 text-muted">To:</div>
							<div class="col-md-9">
								@foreach ($log->to_email as $email)
									<div class="badge bg-light text-dark mb-1 me-1 p-2">
										<i class="bi bi-envelope me-1"></i>{{ $email }}
									</div>
								@endforeach
							</div>
						</div>
						@if ($log->cc)
							<div class="row mb-3">
								<div class="col-md-3 text-muted">CC:</div>
								<div class="col-md-9">
									@foreach ($log->cc as $email)
										<div class="badge bg-light text-dark mb-1 me-1 p-2">
											<i class="bi bi-envelope me-1"></i>{{ $email }}
										</div>
									@endforeach
								</div>
							</div>
						@endif
						@if ($log->bcc)
							<div class="row mb-3">
								<div class="col-md-3 text-muted">BCC:</div>
								<div class="col-md-9">
									@foreach ($log->bcc as $email)
										<div class="badge bg-light text-dark mb-1 me-1 p-2">
											<i class="bi bi-envelope me-1"></i>{{ $email }}
										</div>
									@endforeach
								</div>
							</div>
						@endif
						<div class="row mb-3">
							<div class="col-md-3 text-muted">Type:</div>
							<div class="col-md-9">
								<span class="badge bg-primary">{{ ucfirst($log->type) }}</span>
							</div>
						</div>
					</div>
				</div>

				@if ($log->error_message)
					<div class="card mb-4 border-danger">
						<div class="card-header bg-danger text-white">
							<h5 class="mb-0">
								<i class="bi bi-exclamation-triangle me-2"></i>Error Details
							</h5>
						</div>
						<div class="card-body">
							<div class="alert alert-danger mb-0">
								{{ $log->error_message }}
							</div>
						</div>
					</div>
				@endif
			</div>

			<!-- Sidebar Information -->
			<div class="col-lg-4">
				<!-- SMTP Details -->
				<div class="card mb-4">
					<div class="card-header bg-light">
						<h5 class="mb-0">
							<i class="bi bi-server me-2"></i>SMTP Details
						</h5>
					</div>
					<div class="card-body">
						<div class="mb-3">
							<div class="text-muted mb-1">Host:</div>
							<div class="fw-bold">{{ $log->smtp_host }}</div>
						</div>
						<div class="mb-3">
							<div class="text-muted mb-1">Username:</div>
							<div class="fw-bold">{{ $log->smtp_username }}</div>
						</div>
						<div class="mb-0">
							<div class="text-muted mb-1">Message ID:</div>
							<div class="fw-bold">{{ $log->message_id }}</div>
						</div>
					</div>
				</div>

				<!-- Timing Information -->
				<div class="card mb-4">
					<div class="card-header bg-light">
						<h5 class="mb-0">
							<i class="bi bi-clock-history me-2"></i>Timing
						</h5>
					</div>
					<div class="card-body">
						<div class="mb-3">
							<div class="text-muted mb-1">Queued At:</div>
							<div class="fw-bold">
								@if ($log->queued_at)
									{{ $log->queued_at->format('Y-m-d H:i:s') }}
									<small class="text-muted">({{ $log->queued_at->diffForHumans() }})</small>
								@else
									N/A
								@endif
							</div>
						</div>
						<div class="mb-3">
							<div class="text-muted mb-1">Sent At:</div>
							<div class="fw-bold">
								@if ($log->sent_at)
									{{ $log->sent_at->format('Y-m-d H:i:s') }}
									<small class="text-muted">({{ $log->sent_at->diffForHumans() }})</small>
								@else
									N/A
								@endif
							</div>
						</div>
						@if ($log->sent_at && $log->queued_at)
							<div class="mb-0">
								<div class="text-muted mb-1">Processing Time:</div>
								<div class="fw-bold">{{ $log->sent_at->diffInSeconds($log->queued_at) }} seconds</div>
							</div>
						@endif
					</div>
				</div>

				@if ($log->metadata)
					<!-- Metadata -->
					<div class="card">
						<div class="card-header bg-light">
							<h5 class="mb-0">
								<i class="bi bi-code-square me-2"></i>Metadata
							</h5>
						</div>
						<div class="card-body">
							<pre class="mb-0"><code>{{ json_encode($log->metadata, JSON_PRETTY_PRINT) }}</code></pre>
						</div>
					</div>
				@endif
			</div>
		</div>
	</div>
@endsection

@push('styles')
	<style>
		pre {
			background: #f8f9fa;
			padding: 1rem;
			border-radius: 0.5rem;
			max-height: 400px;
			overflow-y: auto;
		}

		.badge {
			font-size: 0.875rem;
		}
	</style>
@endpush
