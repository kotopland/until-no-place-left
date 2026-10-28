<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Models\Npc;
use App\Models\User;
use App\Services\PersonAvatarService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class PersonAvatar extends Component
{
    public int $pixelSize;

    public string $flag;

    public string $genderIcon;

    public string $ageGroupLabel;

    public string $svg;

    /**
     * Create a new component instance with constructor property promotion.
     */
    public function __construct(
        public Npc|User|null $person = null,
        public string $gender = 'male',
        public int $age = 30,
        public string $nationality = 'Japanese',
        public ?string $role = null,
        public ?string $status = null,
        public string $size = 'md',
        public bool $showBadges = true,
        public bool $showStatusGlow = true,
        public bool $showAgePill = false,
    ) {
        if ($this->person instanceof Npc) {
            $this->gender = $this->person->gender ?: 'male';
            $this->age = $this->person->age ?: 30;
            $this->nationality = $this->person->nationality ?: 'Japanese';
            $this->role = $this->person->role_archetype->value;
            $this->status = $this->person->status->value;
        } elseif ($this->person instanceof User) {
            $this->gender = $this->person->gender ?: 'male';
            $this->age = $this->person->age ?: 30;
            $this->nationality = $this->person->nationality ?: 'Japanese';
            $this->role = $this->person->colaborer_archetype ?? 'missionary';
            $this->status = 'green';
        }

        $avatarService = app(PersonAvatarService::class);
        $this->flag = $avatarService->flagEmoji($this->nationality);
        $this->genderIcon = $avatarService->genderIcon($this->gender);
        $this->ageGroupLabel = $avatarService->ageGroupLabel($this->age);

        $this->pixelSize = match ($this->size) {
            'xs' => 28,
            'sm' => 38,
            'md' => 48,
            'lg' => 64,
            'xl' => 80,
            '2xl' => 96,
            default => 48,
        };

        $seed = $this->person->name ?? ($this->nationality.$this->gender.$this->age);
        $this->svg = $avatarService->generateSvg(
            $this->gender,
            $this->age,
            $this->nationality,
            $this->role,
            $this->status,
            $this->pixelSize,
            false,
            $seed
        );
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.person-avatar');
    }
}
