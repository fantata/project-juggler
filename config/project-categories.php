<?php

return [
    'consultancy' => [
        'label' => 'Client Project',
        'issue_label' => 'Issue',
        'task_label' => 'Task',
        'show_fields' => ['money_status', 'money_value', 'waiting_on_client', 'is_retainer', 'github_repo'],
        'meta_fields' => [],
        'icon' => 'briefcase',
    ],
    'podcast' => [
        'label' => 'Podcast',
        'issue_label' => 'Episode',
        'task_label' => 'Segment',
        'show_fields' => [],
        'meta_fields' => [
            'guest_name' => ['type' => 'text', 'label' => 'Guest'],
            'guest_email' => ['type' => 'email', 'label' => 'Guest Email'],
            'guest_bio' => ['type' => 'textarea', 'label' => 'Guest Bio'],
            'episode_number' => ['type' => 'integer', 'label' => 'Episode #'],
            'recording_date' => ['type' => 'datetime', 'label' => 'Recording Date'],
            'publish_date' => ['type' => 'date', 'label' => 'Publish Date'],
            'status' => ['type' => 'select', 'label' => 'Status', 'options' => ['pitched', 'confirmed', 'recorded', 'edited', 'published']],
        ],
        'icon' => 'microphone',
        'auto_calendar' => true,
    ],
    'creative' => [
        'label' => 'Creative Project',
        'issue_label' => 'Section',
        'task_label' => 'Task',
        'show_fields' => ['deadline'],
        'meta_fields' => [
            'format' => ['type' => 'select', 'label' => 'Format', 'options' => ['book', 'game', 'show', 'course', 'other']],
            'word_count_target' => ['type' => 'integer', 'label' => 'Word Count Target'],
            'word_count_current' => ['type' => 'integer', 'label' => 'Current Words'],
        ],
        'icon' => 'sparkles',
    ],
    'event' => [
        'label' => 'Event',
        'issue_label' => 'Task Area',
        'task_label' => 'Task',
        'show_fields' => ['deadline', 'money_status', 'money_value'],
        'meta_fields' => [
            'event_date' => ['type' => 'datetime', 'label' => 'Event Date'],
            'venue' => ['type' => 'text', 'label' => 'Venue'],
            'capacity' => ['type' => 'integer', 'label' => 'Capacity'],
            'ticket_price' => ['type' => 'decimal', 'label' => 'Ticket Price'],
            'tickets_sold' => ['type' => 'integer', 'label' => 'Tickets Sold'],
        ],
        'icon' => 'calendar',
        'auto_calendar' => true,
    ],
    'generic' => [
        'label' => 'Project',
        'issue_label' => 'Item',
        'task_label' => 'Task',
        'show_fields' => ['deadline'],
        'meta_fields' => [],
        'icon' => 'folder',
    ],
];
