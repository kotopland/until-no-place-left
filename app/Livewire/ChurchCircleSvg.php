<?php

namespace App\Livewire;

use App\Enums\ChurchElement;
use App\Models\Church;
use Livewire\Component;

class ChurchCircleSvg extends Component
{
    public ?Church $church = null;

    public ?int $churchId = null;

    public function mount(?int $churchId = null, ?Church $church = null): void
    {
        if ($church) {
            $this->church = $church;
            $this->churchId = $church->id;
        } elseif ($churchId) {
            $this->churchId = $churchId;
            $this->church = Church::with(['ward', 'leaderNpc', 'leaderUser', 'parentChurch'])->find($churchId);
        }
    }

    public function render()
    {
        $practiced = $this->church?->elements_json ?? [];

        // All 11 Acts 2 Church Circle elements with angles around the 360 circle
        $elements = [
            ['enum' => ChurchElement::RepentanceBelief, 'angle' => -90],
            ['enum' => ChurchElement::Baptism,          'angle' => -57],
            ['enum' => ChurchElement::HolySpirit,       'angle' => -24],
            ['enum' => ChurchElement::Word,             'angle' => 8],
            ['enum' => ChurchElement::Communion,        'angle' => 41],
            ['enum' => ChurchElement::Prayer,           'angle' => 74],
            ['enum' => ChurchElement::Fellowship,       'angle' => 106],
            ['enum' => ChurchElement::GivingMercy,      'angle' => 139],
            ['enum' => ChurchElement::Worship,          'angle' => 172],
            ['enum' => ChurchElement::LocalLeaders,     'angle' => 205],
            ['enum' => ChurchElement::MakingDisciples,  'angle' => 237],
        ];

        $radius = 110;
        $center = 150;

        $items = array_map(function ($item) use ($practiced, $radius, $center) {
            $rad = deg2rad($item['angle']);
            $x = $center + $radius * cos($rad);
            $y = $center + $radius * sin($rad);
            $isPracticed = in_array($item['enum']->value, $practiced, true);

            return [
                'enum' => $item['enum'],
                'x' => round($x, 1),
                'y' => round($y, 1),
                'isPracticed' => $isPracticed,
            ];
        }, $elements);

        return view('livewire.church-circle-svg', [
            'items' => $items,
            'isHealthy' => $this->church?->is_healthy_church ?? false,
            'generation' => $this->church?->generation ?? 1,
            'healthScore' => $this->church?->health_score ?? 0,
        ]);
    }
}
