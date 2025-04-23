@extends('smartmailer::layouts.app')

@section('content')
	<div class="container-fluid px-4">
		<h1 class="mt-4">Email Monitoring Dashboard</h1>

		@if (session('success'))
			<div class="alert alert-success alert-dismissible fade show" role="alert">
				{{ session('success') }}
				<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
			</div>
		@endif

		@if (session('error'))
			<div class="alert alert-danger alert-dismissible fade show" role="alert">
				{{ session('error') }}
				<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
			</div>
		@endif

		<!-- Statistics Cards -->
		<div class="row mt-4">
			<div class="col-xl-3 col-md-6">
				<div class="card bg-primary text-white mb-4">
					<div class="card-body">
						<h4>Today's Emails</h4>
						<div class="d-flex justify-content-between">
							<div>
								<h2>{{ $stats['today']['sent'] }}</h2>
								<small>Sent</small>
							</div>
							<div>
								<h2>{{ $stats['today']['failed'] }}</h2>
								<small>Failed</small>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="col-xl-3 col-md-6">
				<div class="card bg-success text-white mb-4">
					<div class="card-body">
						<h4>This Week</h4>
						<div class="d-flex justify-content-between">
							<div>
								<h2>{{ $stats['this_week']['sent'] }}</h2>
								<small>Sent</small>
							</div>
							<div>
								<h2>{{ $stats['this_week']['failed'] }}</h2>
								<small>Failed</small>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="col-xl-3 col-md-6">
				<div class="card bg-info text-white mb-4">
					<div class="card-body">
						<h4>This Month</h4>
						<div class="d-flex justify-content-between">
							<div>
								<h2>{{ $stats['this_month']['sent'] }}</h2>
								<small>Sent</small>
							</div>
							<div>
								<h2>{{ $stats['this_month']['failed'] }}</h2>
								<small>Failed</small>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Advanced Filters -->
		<div class="card mt-4">
			<div class="card-header">
				<h3 class="card-title">
					<i class="bi bi-funnel"></i> Advanced Filters
				</h3>
			</div>
			<div class="card-body">
				<form action="{{ url()->current() }}" method="GET" class="row g-3">
					<div class="col-md-2">
						<label class="form-label">Email Type</label>
						<select name="type" class="form-select">
							<option value="">All Types</option>
							@foreach ($types as $type)
								<option value="{{ $type }}" {{ request('type') == $type ? 'selected' : '' }}>
									{{ ucfirst($type) }}
								</option>
							@endforeach
						</select>
					</div>
					<div class="col-md-2">
						<label class="form-label">Status</label>
						<select name="status" class="form-select">
							<option value="">All Status</option>
							<option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Sent</option>
							<option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
						</select>
					</div>
					<div class="col-md-2">
						<label class="form-label">SMTP Server</label>
						<select name="smtp_server" class="form-select">
							<option value="">All Servers</option>
							@foreach ($smtpServers as $server)
								<option value="{{ $server }}" {{ request('smtp_server') == $server ? 'selected' : '' }}>
									{{ $server }}
								</option>
							@endforeach
						</select>
					</div>
					<div class="col-md-2">
						<label class="form-label">From Date</label>
						<input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
					</div>
					<div class="col-md-2">
						<label class="form-label">To Date</label>
						<input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
					</div>
					<div class="col-md-2">
						<label class="form-label">Search</label>
						<input type="text" name="search" class="form-control" placeholder="Subject, Email..."
							value="{{ request('search') }}">
					</div>
					<div class="col-12">
						<button type="submit" class="btn btn-primary">
							<i class="bi bi-search"></i> Apply Filters
						</button>
						<a href="{{ url()->current() }}" class="btn btn-secondary">
							<i class="bi bi-x-circle"></i> Clear Filters
						</a>
					</div>
				</form>
			</div>
		</div>

		<!-- SMTP Server Status with Detailed Stats -->
		<div class="row mt-4">
			<div class="col-12">
				<div class="card">
					<div class="card-header d-flex justify-content-between align-items-center">
						<h3 class="card-title">SMTP Server Status</h3>
						<button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#smtpDetailsModal">
							<i class="bi bi-graph-up"></i> View Detailed Stats
						</button>
					</div>
					<div class="card-body">
						<div class="table-responsive">
							<table class="table table-bordered">
								<thead>
									<tr>
										<th>Server Name</th>
										<th>Host</th>
										<th>Status</th>
										<th>Last Error</th>
										<th>Last Used</th>
									</tr>
								</thead>
								<tbody>
									@foreach ($smtpStatus as $server)
										<tr>
											<td>{{ $server['name'] }}</td>
											<td>{{ $server['host'] }}</td>
											<td>
												<span class="badge bg-{{ $server['status'] === 'operational' ? 'success' : 'danger' }}">
													{{ ucfirst($server['status']) }}
												</span>
											</td>
											<td>{{ $server['last_error'] ?? 'None' }}</td>
											<td>{{ $server['last_used'] ? \Carbon\Carbon::parse($server['last_used'])->diffForHumans() : 'Never' }}</td>
										</tr>
									@endforeach
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Recent Email Logs with Retry -->
		<div class="row mt-4">
			<div class="col-12">
				<div class="card">
					<div class="card-header d-flex justify-content-between align-items-center">
						<h3 class="card-title">Recent Email Logs</h3>
						<div>
							<button type="button" class="btn btn-warning bulk-retry" disabled>
								<i class="bi bi-arrow-repeat"></i> Retry Selected
							</button>
						</div>
					</div>
					<div class="card-body">
						<form id="bulkRetryForm" action="{{ route('smartmailer.bulk-retry') }}" method="POST">
							@csrf
							<div class="table-responsive">
								<table class="table table-striped">
									<thead>
										<tr>
											<th>
												<input type="checkbox" class="select-all">
											</th>
											<th>Time</th>
											<th>Type</th>
											<th>From</th>
											<th>To</th>
											<th>Subject</th>
											<th>Status</th>
											<th>SMTP Server</th>
											<th>Actions</th>
										</tr>
									</thead>
									<tbody>
										@foreach ($logs as $log)
											<tr>
												<td>
													@if (!$log->errors->isEmpty())
														<input type="checkbox" name="log_ids[]" value="{{ $log->id }}" class="log-checkbox">
													@endif
												</td>
												<td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
												<td>{{ $log->type }}</td>
												<td>{{ $log->from_email }}</td>
												<td>{{ $log->formatted_to_email }}</td>
												<td>{{ $log->subject }}</td>
												<td>
													<span class="badge bg-{{ $log->errors->isEmpty() ? 'success' : 'danger' }}">
														{{ $log->errors->isEmpty() ? 'Sent' : 'Failed' }}
													</span>
												</td>
												<td>{{ $log->connection_name }}</td>
												<td>
													@if (!$log->errors->isEmpty())
														<form action="{{ route('smartmailer.retry', $log) }}" method="POST" class="d-inline">
															@csrf
															<button type="submit" class="btn btn-sm btn-warning">
																<i class="bi bi-arrow-repeat"></i> Retry
															</button>
														</form>
													@endif
													<a href="{{ route('smartmailer.show', $log) }}" class="btn btn-sm btn-info">
														<i class="bi bi-eye"></i> View
													</a>
												</td>
											</tr>
											@if (!$log->errors->isEmpty())
												<tr>
													<td colspan="9" class="bg-light">
														<strong>Error:</strong> {{ $log->errors->first()->message }}
													</td>
												</tr>
											@endif
										@endforeach
									</tbody>
								</table>
							</div>
						</form>
						<div class="mt-4">
							{{ $logs->links() }}
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- SMTP Details Modal -->
	<div class="modal fade" id="smtpDetailsModal" tabindex="-1">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">SMTP Server Statistics</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<div class="modal-body">
					<div class="table-responsive">
						<table class="table">
							<thead>
								<tr>
									<th>Server</th>
									<th>Total Sent</th>
									<th>Total Failed</th>
									<th>Sent Today</th>
									<th>Avg Response Time</th>
								</tr>
							</thead>
							<tbody>
								@foreach ($smtpStats as $server => $stat)
									<tr>
										<td>{{ $server }}</td>
										<td>{{ number_format($stat['total_sent']) }}</td>
										<td>{{ number_format($stat['total_failed']) }}</td>
										<td>{{ number_format($stat['sent_today']) }}</td>
										<td>{{ number_format($stat['avg_response_time'], 2) }}s</td>
									</tr>
								@endforeach
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>

	@push('scripts')
		<script>
			// Checkbox handling for bulk retry
			document.addEventListener('DOMContentLoaded', function() {
				const selectAll = document.querySelector('.select-all');
				const logCheckboxes = document.querySelectorAll('.log-checkbox');
				const bulkRetryButton = document.querySelector('.bulk-retry');
				const bulkRetryForm = document.getElementById('bulkRetryForm');

				function updateBulkRetryButton() {
					const checkedBoxes = document.querySelectorAll('.log-checkbox:checked');
					bulkRetryButton.disabled = checkedBoxes.length === 0;
				}

				selectAll?.addEventListener('change', function() {
					logCheckboxes.forEach(checkbox => {
						checkbox.checked = selectAll.checked;
					});
					updateBulkRetryButton();
				});

				logCheckboxes.forEach(checkbox => {
					checkbox.addEventListener('change', updateBulkRetryButton);
				});

				bulkRetryButton?.addEventListener('click', function() {
					if (confirm('Are you sure you want to retry sending these emails?')) {
						bulkRetryForm.submit();
					}
				});
			});

			// Auto-refresh dashboard every 30 seconds
			setInterval(function() {
				window.location.reload();
			}, 30000);
		</script>
	@endpush
@endsection
