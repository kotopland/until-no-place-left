<?php

namespace App\Livewire;

use App\Models\MonthlyReport;
use App\Models\User;
use Livewire\Component;

class MonthInReviewModal extends Component
{
    public bool $isOpen = false;

    public ?MonthlyReport $report = null;

    protected $listeners = [
        'open-month-review' => 'openModal',
        'report-generated' => 'checkLatestReport',
    ];

    public function mount(): void
    {
        $this->checkLatestReport();
    }

    public function checkLatestReport(): void
    {
        $user = auth()->user() ?? User::first();
        if (! $user) {
            return;
        }

        $latest = MonthlyReport::where('user_id', $user->id)
            ->latest('game_month')
            ->first();

        if ($latest && $latest->isUnread) {
            $this->report = $latest;
            $this->isOpen = true;
        }
    }

    public function openModal(?int $reportId = null): void
    {
        if ($reportId) {
            $this->report = MonthlyReport::find($reportId);
        } else {
            $user = auth()->user() ?? User::first();
            $this->report = MonthlyReport::where('user_id', $user?->id)->latest('game_month')->first();
        }

        $this->isOpen = true;
    }

    public function closeModal(): void
    {
        if ($this->report && $this->report->isUnread) {
            $this->report->update(['read_at' => now()]);
        }

        $this->isOpen = false;
    }

    public function render()
    {
        return view('livewire.month-in-review-modal');
    }
}
