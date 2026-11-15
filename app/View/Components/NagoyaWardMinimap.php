<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Models\Ward;
use App\Services\NagoyaMiniMapService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class NagoyaWardMinimap extends Component
{
    public string $svg;

    /**
     * Create a new component instance.
     *
     * @param  array<int, array{lat: float, lng: float, label?: string, type?: string, color?: string}>  $markers
     * @param  array<int, int>  $highlightWardIds
     */
    public function __construct(
        public ?Ward $ward = null,
        public array $markers = [],
        public array $highlightWardIds = [],
        public string $accentColor = '#10b981',
        public int $width = 340,
        public int $height = 200,
        public ?string $subtitle = null,
    ) {
        $this->svg = app(NagoyaMiniMapService::class)->renderSvg(
            focusWard: $this->ward,
            markers: $this->markers,
            highlightWardIds: $this->highlightWardIds,
            accentColor: $this->accentColor,
            width: $this->width,
            height: $this->height,
            subtitle: $this->subtitle,
        );
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.nagoya-ward-minimap');
    }
}
