<?php

namespace App\Livewire;

use App\Enums\MoneyStatus;
use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Models\Project;
use Livewire\Attributes\Validate;
use Livewire\Component;

class ProjectDetail extends Component
{
    public Project $project;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required')]
    public string $type = '';

    #[Validate('required')]
    public string $status = '';

    #[Validate('required')]
    public string $money_status = '';

    #[Validate('nullable|numeric|min:0')]
    public ?string $money_value = null;

    #[Validate('nullable|date')]
    public ?string $deadline = null;

    #[Validate('nullable|string|max:255')]
    public ?string $next_action = null;

    #[Validate('nullable|string')]
    public ?string $notes = null;

    #[Validate('required|string|min:1')]
    public string $newLogEntry = '';

    public function mount(Project $project): void
    {
        $this->project = $project;
        $this->name = $project->name;
        $this->type = $project->type->value;
        $this->status = $project->status->value;
        $this->money_status = $project->money_status->value;
        $this->money_value = $project->money_value;
        $this->deadline = $project->deadline?->format('Y-m-d');
        $this->next_action = $project->next_action;
        $this->notes = $project->notes;
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'type' => 'required',
            'status' => 'required',
            'money_status' => 'required',
            'money_value' => 'nullable|numeric|min:0',
            'deadline' => 'nullable|date',
            'next_action' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $this->project->update([
            'name' => $this->name,
            'type' => $this->type,
            'status' => $this->status,
            'money_status' => $this->money_status,
            'money_value' => $this->money_value ?: null,
            'deadline' => $this->deadline ?: null,
            'next_action' => $this->next_action ?: null,
            'notes' => $this->notes ?: null,
            'last_touched_at' => now(),
        ]);

        session()->flash('message', 'Project updated.');
    }

    public function addLog(): void
    {
        $this->validate(['newLogEntry' => 'required|string|min:1']);

        $this->project->logs()->create([
            'entry' => $this->newLogEntry,
        ]);

        $this->project->update(['last_touched_at' => now()]);
        $this->project->refresh();

        $this->newLogEntry = '';
        session()->flash('log-message', 'Log entry added.');
    }

    public function delete(): void
    {
        $this->project->delete();
        $this->redirect(route('dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.project-detail', [
            'types' => ProjectType::cases(),
            'statuses' => ProjectStatus::cases(),
            'moneyStatuses' => MoneyStatus::cases(),
            'logs' => $this->project->logs()->orderByDesc('created_at')->get(),
        ]);
    }
}
