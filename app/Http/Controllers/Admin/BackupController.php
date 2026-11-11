<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BackupLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class BackupController extends Controller
{
    public function index()
    {
        $backupLogs = BackupLog::latest('id')->paginate(15);

        // Storage statistics
        $privatePath = storage_path('app/private/embryos');
        $totalStorageBytes = 0;
        $totalFiles = 0;

        if (file_exists($privatePath)) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($privatePath, \FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $totalStorageBytes += $file->getSize();
                    $totalFiles++;
                }
            }
        }

        $formattedStorage = $this->formatBytes($totalStorageBytes);

        return view('admin.backups.index', compact('backupLogs', 'formattedStorage', 'totalFiles'));
    }

    public function createBackup(Request $request)
    {
        $request->validate([
            'backup_type' => ['required', 'in:full,database,images'],
        ]);

        $backupType = $request->backup_type;
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "EmbryoCase_Backup_{$backupType}_{$timestamp}.zip";

        $backupsDir = storage_path('app/backups');
        if (!file_exists($backupsDir)) {
            @mkdir($backupsDir, 0777, true);
        }

        $zipPath = "{$backupsDir}/{$filename}";

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            BackupLog::create([
                'user_id' => auth()->id(),
                'filename' => $filename,
                'file_size' => 0,
                'backup_type' => $backupType,
                'status' => 'failed',
                'error_message' => 'Could not create ZIP archive on server filesystem.',
                'created_at' => now(),
            ]);

            return back()->with('error', 'Backup initiation failed.');
        }

        // 1. Include Database
        if ($backupType === 'database' || $backupType === 'full') {
            $dbConnection = config('database.default');
            if ($dbConnection === 'sqlite') {
                $sqlitePath = database_path('database.sqlite');
                if (file_exists($sqlitePath)) {
                    $zip->addFile($sqlitePath, 'database/database.sqlite');
                }
            } else {
                // MySQL Export
                $sqlDump = "-- EmbryoCase MySQL Backup\n-- Generated at: " . now()->toIso8601String() . "\n\n";
                $tables = DB::select('SHOW TABLES');
                $key = "Tables_in_" . config('database.connections.mysql.database');
                foreach ($tables as $t) {
                    $tableName = $t->$key ?? array_values((array)$t)[0];
                    $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`");
                    $sqlDump .= "-- Table structure for `{$tableName}`\n";
                    $sqlDump .= $createTable[0]->{'Create Table'} . ";\n\n";

                    $rows = DB::table($tableName)->get();
                    if ($rows->count() > 0) {
                        $sqlDump .= "-- Data for `{$tableName}`\n";
                        foreach ($rows as $row) {
                            $rowArray = (array)$row;
                            $columns = implode("`, `", array_keys($rowArray));
                            $values = implode(", ", array_map(function ($val) {
                                return is_null($val) ? 'NULL' : "'" . addslashes($val) . "'";
                            }, array_values($rowArray)));
                            $sqlDump .= "INSERT INTO `{$tableName}` (`{$columns}`) VALUES ({$values});\n";
                        }
                        $sqlDump .= "\n";
                    }
                }
                $zip->addFromString('database/mysql_dump.sql', $sqlDump);
            }
        }

        // 2. Include Private Image Repository
        if ($backupType === 'images' || $backupType === 'full') {
            $embryosPath = storage_path('app/private/embryos');
            if (file_exists($embryosPath)) {
                $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($embryosPath, \FilesystemIterator::SKIP_DOTS));
                foreach ($files as $file) {
                    if ($file->isFile()) {
                        $filePath = $file->getRealPath();
                        $relativePath = 'embryos/' . substr($filePath, strlen($embryosPath) + 1);
                        $relativePath = str_replace('\\', '/', $relativePath);
                        $zip->addFile($filePath, $relativePath);
                    }
                }
            }
        }

        // 3. Manifest
        $manifest = [
            'application' => 'EmbryoCase',
            'version' => '1.0.0',
            'backup_type' => $backupType,
            'generated_at' => now()->toIso8601String(),
            'generated_by' => auth()->user()?->email,
            'system_info' => [
                'php_version' => PHP_VERSION,
                'db_driver' => config('database.default'),
            ]
        ];
        $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));

        $zip->close();

        $fileSize = file_exists($zipPath) ? filesize($zipPath) : 0;

        $log = BackupLog::create([
            'user_id' => auth()->id(),
            'filename' => $filename,
            'file_size' => $fileSize,
            'backup_type' => $backupType,
            'status' => 'completed',
            'created_at' => now(),
        ]);

        ActivityLog::record(auth()->id(), 'backup_created', null, [
            'backup_id' => $log->id,
            'filename' => $filename,
            'backup_type' => $backupType,
            'file_size' => $fileSize,
        ]);

        return back()->with('status', "Backup archive '{$filename}' successfully compiled and stored.");
    }

    public function download($id)
    {
        $log = BackupLog::findOrFail($id);
        $filePath = storage_path("app/backups/{$log->filename}");

        if (!file_exists($filePath)) {
            return back()->with('error', 'Backup archive file no longer exists on disk.');
        }

        ActivityLog::record(auth()->id(), 'backup_downloaded', null, [
            'backup_id' => $log->id,
            'filename' => $log->filename,
        ]);

        return response()->download($filePath, $log->filename);
    }

    protected function formatBytes($bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}
