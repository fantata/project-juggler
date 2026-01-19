<?php

namespace App\Livewire;

use App\Enums\MoneyStatus;
use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Enums\RetainerFrequency;
use App\Models\Project;
use Livewire\Attributes\Validate;
use Livewire\Component;

class QuickAdd extends Component
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required')]
    public string $type = 'client';

    #[Validate('required')]
    public string $status = 'active';

    #[Validate('required')]
    public string $money_status = 'none';

    #[Validate('nullable|numeric|min:0')]
    public ?string $money_value = null;

    #[Validate('nullable|date')]
    public ?string $deadline = null;

    #[Validate('nullable|string|max:255')]
    public ?string $next_action = null;

    public bool $is_retainer = false;

    public ?string $retainer_frequency = null;

    #[Validate('nullable|numeric|min:0')]
    public ?string $retainer_amount = null;

    public function save(): void
    {
        $this->validate();

        Project::create([
            'name' => $this->name,
            'type' => $this->type,
            'status' => $this->status,
            'money_status' => $this->money_status,
            'money_value' => $this->money_value ?: null,
            'deadline' => $this->deadline ?: null,
            'next_action' => $this->next_action ?: null,
            'is_retainer' => $this->is_retainer,
            'retainer_frequency' => $this->retainer_frequency ?: null,
            'retainer_amount' => $this->retainer_amount ?: null,
            'last_touched_at' => now(),
        ]);

        $this->reset();
        $this->dispatch('project-created');
        $this->dispatch('close-modal');
    }

    public function render()
    {
        return view('livewire.quick-add', [
            'types' => ProjectType::cases(),
            'statuses' => ProjectStatus::cases(),
            'moneyStatuses' => MoneyStatus::cases(),
            'retainerFrequencies' => RetainerFrequency::cases(),
        ]);
    }
}
