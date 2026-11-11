@extends('layouts.app')

@section('title', 'System Backups & Recovery')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 pb-2 border-bottom" style="border-color: var(--border-grey) !important;">
    <div>
        <div class="text-secondary-label">DATA INTEGRITY &amp; DISASTER RECOVERY</div>
        <h1 class="h2 mb-0">System Backup &amp; Recovery Console</h1>
    </div>
</div>

<!-- Storage Metrics and Trigger Actions -->
<div class="row g-4 mb-4">
    <div class="col-12 col-lg-4">
        <div class="sci-stat-box h-100">
            <div class="sci-stat-label">CENTRAL IMAGE REPOSITORY SIZE</div>
            <div class="sci-stat-value">{{ $formattedStorage }}</div>
            <div class="sci-stat-sub">{{ number_format($totalFiles) }} total private files stored on server</div>

            <div class="mt-4 pt-3 border-top" style="border-color: var(--border-grey) !important;">
                <div class="text-secondary-label mb-2">TRIGGER NEW BACKUP</div>
                <div class="d-flex flex-column gap-2">
                    <form method="POST" action="{{ route('admin.backups.create') }}">
                        @csrf
                        <input type="hidden" name="backup_type" value="full">
                        <button type="submit" class="btn btn-sci-primary w-100" onclick="this.innerHTML='Compiling Full Backup Archive...'; this.disabled=true; this.form.submit();">
                            COMPILE FULL SYSTEM BACKUP (SQL + MEDIA)
                        </button>
                    </form>

                    <div class="d-flex gap-2">
                        <form method="POST" action="{{ route('admin.backups.create') }}" class="w-50">
                            @csrf
                            <input type="hidden" name="backup_type" value="database">
                            <button type="submit" class="btn btn-sci-secondary w-100" style="font-size: 11px;">
                                DB ONLY (SQL)
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.backups.create') }}" class="w-50">
                            @csrf
                            <input type="hidden" name="backup_type" value="images">
                            <button type="submit" class="btn btn-sci-secondary w-100" style="font-size: 11px;">
                                IMAGES ONLY (ZIP)
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recovery Documentation -->
    <div class="col-12 col-lg-8">
        <div class="sci-card h-100 mb-0">
            <div class="sci-card-header">
                <span class="sci-card-title">DISASTER RECOVERY &amp; SYNCHRONIZATION SPECIFICATIONS</span>
            </div>
            <div class="sci-card-body" style="font-size: 13px;">
                <div class="mb-3">
                    <strong>1. Centralized Server Architecture:</strong><br>
                    All uploaded embryological data resides on the central server. The Admin PC can synchronize and store offline copies by downloading the full backup archive below.
                </div>
                <div class="mb-3">
                    <strong>2. Database &amp; Media Atomic Integrity:</strong><br>
                    Each full backup archive contains the complete database SQL dump under <code>database/</code> along with the identical matching directory structure of original images, non-destructive vector JSONs, and flattened previews under <code>embryos/</code>.
                </div>
                <div class="mb-0">
                    <strong>3. Server Restoration Procedure:</strong><br>
                    To restore from an archive:
                    <ol class="mb-0 ps-3 mt-1">
                        <li>Import <code>database/mysql_dump.sql</code> into MySQL 8.</li>
                        <li>Extract <code>embryos/</code> to <code>storage/app/private/embryos/</code>.</li>
                        <li>Verify checksums and run <code>php artisan cache:clear</code>.</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Backup Archives Table -->
<div class="sci-card">
    <div class="sci-card-header">
        <span class="sci-card-title">COMPILED BACKUP ARCHIVES</span>
    </div>
    <div class="table-responsive">
        <table class="table-sci">
            <thead>
                <tr>
                    <th>ARCHIVE FILENAME</th>
                    <th>BACKUP TYPE</th>
                    <th>ARCHIVE SIZE</th>
                    <th>STATUS</th>
                    <th>COMPILATION TIMESTAMP (UTC)</th>
                    <th>ACTION</th>
                </tr>
            </thead>
            <tbody>
                @forelse($backupLogs as $backup)
                    <tr>
                        <td class="mono fw-bold">{{ $backup->filename }}</td>
                        <td>
                            <span class="badge-sci badge-sci-neutral">{{ strtoupper($backup->backup_type) }}</span>
                        </td>
                        <td class="mono">{{ $backup->formatted_file_size }}</td>
                        <td>
                            @if($backup->status === 'completed')
                                <span class="badge-sci badge-sci-success">COMPLETED</span>
                            @else
                                <span class="badge-sci badge-sci-danger">FAILED</span>
                            @endif
                        </td>
                        <td class="mono" style="font-size: 12px;">{{ $backup->created_at?->format('Y-m-d H:i:s') }}</td>
                        <td>
                            @if($backup->status === 'completed')
                                <a href="{{ route('admin.backups.download', $backup->id) }}" class="btn btn-sm btn-sci-primary">
                                    DOWNLOAD TO PC
                                </a>
                            @else
                                <span class="text-danger" style="font-size: 11px;">{{ $backup->error_message }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            No backup archives generated yet. Trigger a backup above.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($backupLogs->hasPages())
        <div class="p-3 border-top" style="border-color: var(--border-grey) !important;">
            {{ $backupLogs->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>
@endsection

