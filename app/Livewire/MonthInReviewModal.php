<?php

namespace App\Livewire;

use App\Models\MonthlyReport;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class MonthInReviewModal extends Component
{
    public bool $isOpen = false;

    public ?MonthlyReport $report = null;

    /** @var array<string, string> */
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

    public function dispatchPrayerLetter(string $focus): void
    {
        if (! in_array($focus, ['pioneering', 'family_shield', 'timothy_multiplier'], true)) {
            return;
        }

        $user = auth()->user() ?? User::first();
        if (! $user) {
            return;
        }

        if ($this->report) {
            $this->report->update([
                'prayer_letter_dispatched_at' => now(),
                'selected_prayer_focus' => $focus,
            ]);
            $this->report->refresh();
        }

        $nextMonth = $this->report ? ($this->report->game_month + 1) : ($user->current_game_month + 1);

        $user->update([
            'last_prayer_letter_game_month' => $this->report?->game_month ?? $user->current_game_month,
            'consecutive_admin_neglect_months' => 0,
            'active_prayer_focus' => $focus,
            'prayer_shield_expires_at_month' => $nextMonth,
        ]);

        $focusName = match ($focus) {
            'pioneering' => 'Pioneer Sowing (+15% Field 1/2)',
            'family_shield' => 'Family Altar Shield (+15% Baptism)',
            'timothy_multiplier' => 'Timothy Multiplication (+15% Leadership)',
            default => 'General Prayer Backing',
        };

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => "✉️ Supporter Prayer Letter dispatched with {$focusName} focus! Overseas Prayer Shield active through Month {$nextMonth}.",
        ]);
        $this->dispatch('prayer-shield-activated');
    }

    public function render(): View
    {
        return view('livewire.month-in-review-modal');
    }
}
