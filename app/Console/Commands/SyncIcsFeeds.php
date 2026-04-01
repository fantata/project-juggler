<?php

namespace App\Console\Commands;

use App\Models\IcsFeed;
use App\Services\IcsFeedSyncService;
use Illuminate\Console\Command;

class SyncIcsFeeds extends Command
{
    protected $signature = 'feeds:sync {--feed= : Sync a specific feed by ID} {--force : Sync even if not due}';

    protected $description = 'Sync external ICS calendar feeds';

    public function handle(IcsFeedSyncService $service): int
    {
        $query = IcsFeed::query();

        if ($feedId = $this->option('feed')) {
            $query->where('id', $feedId);
        } else {
            $query->where('is_enabled', true);
        }

        $feeds = $query->get();

        if ($feeds->isEmpty()) {
            $this->info('No feeds to sync.');
            return 0;
        }

        foreach ($feeds as $feed) {
            if (! $this->option('force') && ! $feed->needsSync()) {
                $this->line("Skipping {$feed->name} (not due yet)");
                continue;
            }

            $this->line("Syncing {$feed->name}...");
            $stats = $service->syncFeed($feed);
            $this->info("  Created: {$stats['created']}, Updated: {$stats['updated']}, Deleted: {$stats['deleted']}");

            $feed->refresh();
            if ($feed->last_sync_status === 'error') {
                $this->error("  Error: {$feed->last_sync_error}");
            }
        }

        return 0;
    }
}
