<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\AuditService;

class BackupController extends Controller
{
    /**
     * Display a listing of database backups.
     */
    public function index()
    {
        $activeRole = session('admin_role', auth()->user()->role);
        if ($activeRole !== 'super_admin') {
            abort(403, 'Only Super Admins can manage database backups.');
        }

        $backupDir = storage_path('app/backups');
        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0777, true);
        }

        // Clean up backups older than 30 days
        $this->cleanupOldBackups($backupDir);

        $backups = [];
        $files = glob($backupDir . '/*.zip');
        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'size' => round(filesize($file) / 1024 / 1024, 2), // MB
                'created_at' => date('Y-m-d H:i:s', filemtime($file)),
            ];
        }

        // Sort by created_at desc
        usort($backups, function ($a, $b) {
            return strcmp($b['created_at'], $a['created_at']);
        });

        return view('system.backups', compact('backups'));
    }

    /**
     * Create a new database backup.
     */
    public function create()
    {
        $activeRole = session('admin_role', auth()->user()->role);
        if ($activeRole !== 'super_admin') {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized action.'], 403);
        }

        try {
            $connection = config('database.default');
            $driver = config("database.connections.{$connection}.driver", $connection);
            $backupDir = storage_path('app/backups');
            if (!file_exists($backupDir)) {
                mkdir($backupDir, 0777, true);
            }

            $timestamp = date('Ymd_His');
            $filename = "backup_{$timestamp}.sql";
            $zipFilename = "backup_{$timestamp}.zip";
            $sqlPath = $backupDir . '/' . $filename;
            $zipPath = $backupDir . '/' . $zipFilename;

            if ($driver === 'sqlite') {
                $dbPath = config("database.connections.{$connection}.database");
                if ($dbPath === ':memory:') {
                    return response()->json(['status' => 'error', 'message' => 'Cannot back up in-memory database.'], 400);
                }
                copy($dbPath, $sqlPath);
            } else {
                // MySQL dump
                $host = config('database.connections.mysql.host');
                $username = config('database.connections.mysql.username');
                $password = config('database.connections.mysql.password');
                $database = config('database.connections.mysql.database');
                
                $cmd = sprintf(
                    'mysqldump --host=%s --user=%s --password=%s %s > %s',
                    escapeshellarg($host),
                    escapeshellarg($username),
                    escapeshellarg($password),
                    escapeshellarg($database),
                    escapeshellarg($sqlPath)
                );
                
                exec($cmd, $output, $returnVar);
                if ($returnVar !== 0) {
                    throw new \Exception("mysqldump failed with status code {$returnVar}. Output: " . implode("\n", $output));
                }
            }

            // Create ZIP archive
            $zip = new \ZipArchive();
            if ($zip->open($zipPath, \ZipArchive::CREATE) === TRUE) {
                $zip->addFile($sqlPath, $filename);
                $zip->close();
                @unlink($sqlPath);
            } else {
                throw new \Exception("Could not create zip archive.");
            }

            // Audit Log
            app(\App\Services\AuditService::class)->log(
                'create_database_backup',
                null,
                null,
                ['filename' => $zipFilename]
            );

            return response()->json([
                'status' => 'success',
                'message' => "Database backup {$zipFilename} created successfully."
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Backup creation failed: " . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download a backup file.
     */
    public function download($filename)
    {
        $activeRole = session('admin_role', auth()->user()->role);
        if ($activeRole !== 'super_admin') {
            abort(403, 'Unauthorized action.');
        }

        $filename = basename($filename);
        $filePath = storage_path('app/backups/' . $filename);

        if (!file_exists($filePath)) {
            abort(404, 'Backup file not found.');
        }

        app(\App\Services\AuditService::class)->log(
            'download_database_backup',
            null,
            null,
            ['filename' => $filename]
        );

        return response()->download($filePath);
    }

    /**
     * Restore database from a backup file.
     */
    public function restore($filename)
    {
        $activeRole = session('admin_role', auth()->user()->role);
        if ($activeRole !== 'super_admin') {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized action.'], 403);
        }

        try {
            $filename = basename($filename);
            $backupDir = storage_path('app/backups');
            $zipPath = $backupDir . '/' . $filename;

            if (!file_exists($zipPath)) {
                return response()->json(['status' => 'error', 'message' => 'Backup file not found.'], 404);
            }

            // Extract zip
            $zip = new \ZipArchive();
            $sqlFilename = str_replace('.zip', '.sql', $filename);
            $sqlPath = $backupDir . '/' . $sqlFilename;

            if ($zip->open($zipPath) === TRUE) {
                $zip->extractTo($backupDir, $sqlFilename);
                $zip->close();
            } else {
                throw new \Exception("Could not extract zip archive.");
            }

            if (!file_exists($sqlPath)) {
                throw new \Exception("SQL file not found in archive.");
            }

            $connection = config('database.default');
            $driver = config("database.connections.{$connection}.driver", $connection);
            if ($driver === 'sqlite') {
                $dbPath = config("database.connections.{$connection}.database");
                if ($dbPath === ':memory:') {
                    @unlink($sqlPath);
                    return response()->json(['status' => 'error', 'message' => 'Cannot restore in-memory database.'], 400);
                }
                
                copy($sqlPath, $dbPath);
                @unlink($sqlPath);
            } else {
                // MySQL restore
                $host = config('database.connections.mysql.host');
                $username = config('database.connections.mysql.username');
                $password = config('database.connections.mysql.password');
                $database = config('database.connections.mysql.database');

                $cmd = sprintf(
                    'mysql --host=%s --user=%s --password=%s %s < %s',
                    escapeshellarg($host),
                    escapeshellarg($username),
                    escapeshellarg($password),
                    escapeshellarg($database),
                    escapeshellarg($sqlPath)
                );

                exec($cmd, $output, $returnVar);
                @unlink($sqlPath);

                if ($returnVar !== 0) {
                    throw new \Exception("mysql restore failed with status code {$returnVar}. Output: " . implode("\n", $output));
                }
            }

            app(\App\Services\AuditService::class)->log(
                'restore_database_backup',
                null,
                null,
                ['filename' => $filename]
            );

            return response()->json([
                'status' => 'success',
                'message' => "Database restored successfully from backup {$filename}."
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Restore failed: " . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a backup file.
     */
    public function destroy($filename)
    {
        $activeRole = session('admin_role', auth()->user()->role);
        if ($activeRole !== 'super_admin') {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized action.'], 403);
        }

        $filename = basename($filename);
        $filePath = storage_path('app/backups/' . $filename);

        if (file_exists($filePath)) {
            @unlink($filePath);
            
            app(\App\Services\AuditService::class)->log(
                'delete_database_backup',
                null,
                null,
                ['filename' => $filename]
            );

            return response()->json(['status' => 'success', 'message' => 'Backup deleted successfully.']);
        }

        return response()->json(['status' => 'error', 'message' => 'Backup not found.'], 404);
    }

    /**
     * Cleanup backups older than 30 days.
     */
    protected function cleanupOldBackups($backupDir)
    {
        $files = glob($backupDir . '/*.zip');
        $cutoff = time() - (30 * 24 * 60 * 60); // 30 days
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                @unlink($file);
            }
        }
    }
}
