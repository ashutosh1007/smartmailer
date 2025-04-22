@extends('vendor.smartmailer.layouts.app')

@section('content')
	<div class="container-fluid px-4">
		<div class="d-flex justify-content-between align-items-center mb-4">
			<h1 class="h3 mb-0 text-gray-800">
				<i class="bi bi-envelope-paper me-2"></i>Email Monitoring
			</h1>
			<div class="d-flex align-items-center">
				<span class="text-muted me-3">
					<i class="bi bi-clock"></i> Last Updated: {{ now()->format('H:i:s') }}
				</span>
				<button class="btn btn-primary btn-sm" onclick="window.location.reload()">
					<i class="bi bi-arrow-clockwise"></i> Refresh
				</button>
			</div>
		</div>

		<!-- Stats Cards Row -->
		<div class="row g-4 mb-4">
			<!-- Total Sent -->
			<div class="col-xl-3 col-md-6">
				<div class="card stats-card border-start border-4 border-primary">
					<div class="card-body">
						<div class="d-flex justify-content-between align-items-center">
							<div>
								<h6 class="text-muted mb-2">Total Sent</h6>
								<h2 class="mb-0">{{ number_format($stats['total_sent']) }}</h2>
							</div>
							<div class="stats-icon stats-primary">
								<i class="bi bi-envelope-check-fill"></i>
							</div>
						</div>
						<div class="progress mt-3" style="height: 4px;">
							<div class="progress-bar bg-primary" style="width: 75%"></div>
						</div>
					</div>
				</div>
			</div>

			<!-- Failed Emails -->
			<div class="col-xl-3 col-md-6">
				<div class="card stats-card border-start border-4 border-danger">
					<div class="card-body">
						<div class="d-flex justify-content-between align-items-center">
							<div>
								<h6 class="text-muted mb-2">Failed</h6>
								<h2 class="mb-0">{{ number_format($stats['total_failed']) }}</h2>
							</div>
							<div class="stats-icon stats-danger">
								<i class="bi bi-envelope-x-fill"></i>
							</div>
						</div>
						<div class="progress mt-3" style="height: 4px;">
							<div class="progress-bar bg-danger" style="width: 25%"></div>
						</div>
					</div>
				</div>
			</div>

			<!-- Today's Stats -->
			<div class="col-xl-3 col-md-6">
				<div class="card stats-card border-start border-4 border-success">
					<div class="card-body">
						<div class="d-flex justify-content-between align-items-center">
							<div>
								<h6 class="text-muted mb-2">Today's Emails</h6>
								<h2 class="mb-0">{{ number_format($stats['sent_today']) }}</h2>
							</div>
							<div class="stats-icon stats-success">
								<i class="bi bi-calendar-check-fill"></i>
							</div>
						</div>
						<div class="progress mt-3" style="height: 4px;">
							<div class="progress-bar bg-success" style="width: 65%"></div>
						</div>
					</div>
				</div>
			</div>

			<!-- Weekly Stats -->
			<div class="col-xl-3 col-md-6">
				<div class="card stats-card border-start border-4 border-info">
					<div class="card-body">
						<div class="d-flex justify-content-between align-items-center">
							<div>
								<h6 class="text-muted mb-2">This Week</h6>
								<h2 class="mb-0">{{ number_format($stats['sent_this_week']) }}</h2>
							</div>
							<div class="stats-icon stats-info">
								<i class="bi bi-graph-up-arrow"></i>
							</div>
						</div>
						<div class="progress mt-3" style="height: 4px;">
							<div class="progress-bar bg-info" style="width: 85%"></div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Filters Card -->
		<div class="card mb-4 filter-card">
			<div class="card-header bg-light">
				<h5 class="mb-0">
					<i class="bi bi-funnel me-2"></i>Advanced Filters
				</h5>
			</div>
			<div class="card-body">
				<form action="{{ route('smartmailer.dashboard') }}" method="GET" class="row g-3">
					<div class="col-md-2">
						<label class="form-label">Type</label>
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
					<div class="col-md-2 d-flex align-items-end">
						<div class="d-grid gap-2 w-100">
							<button type="submit" class="btn btn-primary">
								<i class="bi bi-search me-1"></i> Apply
							</button>
							<a href="{{ route('smartmailer.dashboard') }}" class="btn btn-outline-secondary">
								<i class="bi bi-x-circle me-1"></i> Reset
							</a>
						</div>
					</div>
				</form>
			</div>
		</div>

		<!-- Logs Table Card -->
		<div class="card">
			<div class="card-header bg-light d-flex justify-content-between align-items-center">
				<h5 class="mb-0">
					<i class="bi bi-table me-2"></i>Email Logs
				</h5>
				<div class="btn-group">
					<button class="btn btn-sm btn-outline-primary">
						<i class="bi bi-download me-1"></i>Export
					</button>
					<button class="btn btn-sm btn-outline-primary" onclick="window.location.reload()">
						<i class="bi bi-arrow-clockwise me-1"></i>Refresh
					</button>
				</div>
			</div>
			<div class="card-body p-0">
				<div class="table-responsive">
					<table class="table table-hover mb-0">
						<thead>
							<tr>
								<th>Date/Time</th>
								<th>Type</th>
								<th>From</th>
								<th>To</th>
								<th>Subject</th>
								<th>SMTP</th>
								<th>Status</th>
								<th>Actions</th>
							</tr>
						</thead>
						<tbody>
							@forelse($logs as $log)
								<tr>
									<td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
									<td>
										<span class="badge bg-primary">{{ ucfirst($log->type) }}</span>
									</td>
									<td>{{ $log->from_email }}</td>
									<td>
										{{ implode(', ', array_slice($log->to_email, 0, 2)) }}
										@if (count($log->to_email) > 2)
											<span class="badge bg-secondary">+{{ count($log->to_email) - 2 }}</span>
										@endif
									</td>
									<td>{{ Str::limit($log->subject, 40) }}</td>
									<td>{{ $log->smtp_host }}</td>
									<td>
										<span
											class="badge bg-{{ $log->status === 'sent' ? 'success' : ($log->status === 'queued' ? 'warning' : 'danger') }}">
											{{ ucfirst($log->status) }}
										</span>
									</td>
									<td>
										<div class="btn-group">
											<a href="{{ route('smartmailer.show', $log) }}" class="btn btn-sm btn-outline-primary"
												data-bs-toggle="tooltip" title="View Details">
												<i class="bi bi-eye"></i>
											</a>
											@if ($log->status === 'failed')
												<button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="tooltip"
													title="Retry Sending">
													<i class="bi bi-arrow-clockwise"></i>
												</button>
											@endif
										</div>
									</td>
								</tr>
							@empty
								<tr>
									<td colspan="8" class="text-center py-4">
										<div class="text-muted">
											<i class="bi bi-inbox h1 d-block mb-2"></i>
											No email logs found
										</div>
									</td>
								</tr>
							@endforelse
						</tbody>
					</table>
				</div>
			</div>
			@if ($logs->hasPages())
				<div class="card-footer bg-light">
					{{ $logs->links() }}
				</div>
			@endif
		</div>
	</div>

	@push('scripts')
		<script>
			// Initialize tooltips
			var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
			var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
				return new bootstrap.Tooltip(tooltipTriggerEl)
			});

			// Auto refresh every 30 seconds
			setTimeout(function() {
				window.location.reload();
			}, 30000);
		</script>
	@endpush
