<?php

namespace App\Console\Commands;

use App\Jobs\CreateWaitlistUserAccountJob;
use App\Models\Waitlist;
use Illuminate\Console\Command;

class ProcessWaitlistCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'waitlist:process-accounts 
                            {--dry-run : Show what would be processed without actually creating accounts}
                            {--limit=50 : Maximum number of entries to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create user accounts for waitlist entries that have not been converted yet
    
    Examples:
    - php artisan waitlist:process-accounts --dry-run (see what would be processed)
    - php artisan waitlist:process-accounts --limit=10 (process max 10 entries)
    - php artisan waitlist:process-accounts (process all pending entries, default limit 50)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');

        $this->info('🔄 Processing waitlist entries...');

        // Get waitlist entries that haven't been converted yet
        $waitlistEntries = Waitlist::whereNull('converted_at')
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();

        if ($waitlistEntries->isEmpty()) {
            $this->info('✅ No waitlist entries to process.');

            return Command::SUCCESS;
        }

        $this->info("📋 Found {$waitlistEntries->count()} entries to process".($dryRun ? ' (DRY RUN)' : ''));

        $successCount = 0;
        $errorCount = 0;

        foreach ($waitlistEntries as $waitlist) {
            $this->line("📧 Processing: {$waitlist->email}");

            if ($dryRun) {
                $this->line("   → Would create account for: {$waitlist->email}");
                $successCount++;

                continue;
            }

            try {
                // Dispatch the job immediately (no delay for command)
                CreateWaitlistUserAccountJob::dispatchSync($waitlist);

                $this->line('   ✅ Account created successfully');
                $successCount++;

            } catch (\Exception $e) {
                $this->line("   ❌ Error: {$e->getMessage()}");
                $errorCount++;
            }
        }

        $this->newLine();

        if ($dryRun) {
            $this->info('🔍 DRY RUN COMPLETE');
            $this->info("📊 Would process: {$successCount} entries");
        } else {
            $this->info('✨ PROCESSING COMPLETE');
            $this->info("📊 Success: {$successCount} | Errors: {$errorCount}");
        }

        return Command::SUCCESS;
    }
}
