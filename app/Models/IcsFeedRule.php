<?php

namespace App\Models;

use App\Enums\FeedRuleAction;
use App\Enums\FeedRuleField;
use App\Enums\FeedRuleOperator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IcsFeedRule extends Model
{
    protected $fillable = [
        'ics_feed_id',
        'field',
        'operator',
        'value',
        'action',
        'action_value',
        'is_enabled',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'field' => FeedRuleField::class,
            'operator' => FeedRuleOperator::class,
            'action' => FeedRuleAction::class,
            'is_enabled' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function feed(): BelongsTo
    {
        return $this->belongsTo(IcsFeed::class, 'ics_feed_id');
    }

    public function matches(IcsFeedEvent $event): bool
    {
        $fieldValue = match ($this->field) {
            FeedRuleField::Title => $event->title ?? '',
            FeedRuleField::Description => $event->description ?? '',
            FeedRuleField::Location => $event->location ?? '',
        };

        return $this->operator->test($fieldValue, $this->value);
    }

    public function apply(IcsFeedEvent $event): void
    {
        match ($this->action) {
            FeedRuleAction::MarkRelevant => $event->is_relevant = true,
            FeedRuleAction::Background => $event->is_backgrounded = true,
            FeedRuleAction::SetNote => $event->relevance_note = $this->action_value,
        };
    }
}
