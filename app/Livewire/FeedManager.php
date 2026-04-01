<?php

namespace App\Livewire;

use App\Models\IcsFeed;
use App\Models\IcsFeedRule;
use App\Services\IcsFeedSyncService;
use Livewire\Component;

class FeedManager extends Component
{
    public bool $showAddFeed = false;
    public string $newFeedName = '';
    public string $newFeedUrl = '';
    public string $newFeedColor = '#6B8F71';

    public bool $showRuleForm = false;
    public ?int $rulesFeedId = null;
    public string $ruleField = 'description';
    public string $ruleOperator = 'contains';
    public string $ruleValue = '';
    public string $ruleAction = 'mark_relevant';
    public string $ruleActionValue = '';

    public function addFeed(): void
    {
        $this->validate([
            'newFeedName' => 'required|string|max:255',
            'newFeedUrl' => 'required|url',
        ]);

        $feed = IcsFeed::create([
            'name' => $this->newFeedName,
            'url' => $this->newFeedUrl,
            'color' => $this->newFeedColor,
        ]);

        $service = app(IcsFeedSyncService::class);
        $service->syncFeed($feed);

        $this->reset(['newFeedName', 'newFeedUrl', 'showAddFeed']);
        $this->newFeedColor = '#6B8F71';
    }

    public function syncFeed(int $feedId): void
    {
        $feed = IcsFeed::findOrFail($feedId);
        $service = app(IcsFeedSyncService::class);
        $service->syncFeed($feed);
    }

    public function toggleFeed(int $feedId): void
    {
        $feed = IcsFeed::findOrFail($feedId);
        $feed->update(['is_enabled' => ! $feed->is_enabled]);
    }

    public function deleteFeed(int $feedId): void
    {
        IcsFeed::findOrFail($feedId)->delete();
    }

    public function showRules(int $feedId): void
    {
        $this->rulesFeedId = $feedId;
        $this->showRuleForm = true;
    }

    public function addRule(): void
    {
        $this->validate([
            'ruleField' => 'required|in:title,description,location',
            'ruleOperator' => 'required|in:contains,starts_with,matches_regex',
            'ruleValue' => 'required|string|max:255',
            'ruleAction' => 'required|in:mark_relevant,background,set_note',
        ]);

        $feed = IcsFeed::findOrFail($this->rulesFeedId);

        $feed->rules()->create([
            'field' => $this->ruleField,
            'operator' => $this->ruleOperator,
            'value' => $this->ruleValue,
            'action' => $this->ruleAction,
            'action_value' => $this->ruleActionValue ?: null,
            'position' => $feed->rules()->max('position') + 1,
        ]);

        // Re-apply rules
        $service = app(IcsFeedSyncService::class);
        $service->applyRules($feed);

        $this->reset(['ruleValue', 'ruleActionValue']);
    }

    public function deleteRule(int $ruleId): void
    {
        $rule = IcsFeedRule::findOrFail($ruleId);
        $feedId = $rule->ics_feed_id;
        $rule->delete();

        $service = app(IcsFeedSyncService::class);
        $service->applyRules(IcsFeed::find($feedId));
    }

    public function render()
    {
        $feeds = IcsFeed::withCount('events')->with('rules')->get();

        return view('livewire.feed-manager', [
            'feeds' => $feeds,
        ]);
    }
}
