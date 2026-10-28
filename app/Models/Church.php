<?php

namespace App\Models;

use App\Enums\ChurchElement;
use App\Enums\MinistryActionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $game_session_id
 * @property int $ward_id
 * @property string $name
 * @property int|null $parent_church_id
 * @property int|null $leader_npc_id
 * @property int|null $leader_user_id
 * @property int $generation
 * @property array<string> $elements_json
 * @property bool $is_healthy_church
 * @property int $health_score
 * @property int $attenders_count
 * @property int $believers_count
 * @property int $baptized_count
 * @property bool $is_dissolved
 * @property int|null $dissolved_game_month
 * @property string|null $dissolution_reason
 * @property string|null $started_by_name
 * @property int|null $last_attrition_month
 * @property string|null $last_attrition_event
 * @property float $lat
 * @property float $lng
 * @property int $founded_game_month
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Table('churches')]
#[Fillable([
    'game_session_id',
    'ward_id',
    'name',
    'parent_church_id',
    'leader_npc_id',
    'leader_user_id',
    'generation',
    'elements_json',
    'is_healthy_church',
    'health_score',
    'attenders_count',
    'believers_count',
    'baptized_count',
    'is_dissolved',
    'dissolved_game_month',
    'dissolution_reason',
    'started_by_name',
    'last_attrition_month',
    'last_attrition_event',
    'last_coached_game_month',
    'last_coached_game_week',
    'last_coached_game_day',
    'lat',
    'lng',
    'founded_game_month',
])]
class Church extends Model
{
    use HasFactory;

    /**
     * PHP 8.4 Property Hook: Generational badge label (G1, G2, G3, G4+)
     */
    public string $generationBadge {
        get => "G{$this->generation}";
    }

    /**
     * PHP 8.4 Property Hook: Whether church is currently active and gathering
     */
    public bool $isActive {
        get => ! $this->is_dissolved;
    }

    /**
     * PHP 8.4 Property Hook: Human-friendly status label
     */
    public string $statusLabel {
        get => $this->is_dissolved
            ? 'Dissolved Group'
            : ($this->is_healthy_church ? 'Healthy Acts 2 Church' : 'Developing Bible Study');
    }

    /**
     * PHP 8.4 Property Hook: Number of practiced Acts 2 elements
     */
    public int $practicedElementsCount {
        get => count($this->elements_json ?? []);
    }

    /**
     * PHP 8.4 Property Hook: Whether this church is autonomous (led by a local Timothy/Elder NPC)
     */
    public bool $isAutonomous {
        get => $this->leader_npc_id !== null && $this->leader_user_id === null;
    }

    /**
     * PHP 8.4 Property Hook: Effective leader display name
     */
    public string $leaderDisplayName {
        get => $this->leaderNpc?->displayName
            ?? $this->leaderUser?->name
            ?? $this->started_by_name
            ?? 'Local Servant Leader';
    }

    /**
     * PHP 8.4 Property Hook: Map marker color based on generation
     */
    public string $generationColor {
        get => $this->is_dissolved ? '#64748b' : match ($this->generation) {
            1 => '#3b82f6', // blue-500 (G1 Player-planted)
            2 => '#8b5cf6', // purple-500 (G2)
            3 => '#ec4899', // pink-500 (G3)
            default => '#eab308', // gold/yellow-500 (G4+ Movement stream!)
        };
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'generation' => 'integer',
            'elements_json' => 'array',
            'is_healthy_church' => 'boolean',
            'health_score' => 'integer',
            'attenders_count' => 'integer',
            'believers_count' => 'integer',
            'baptized_count' => 'integer',
            'is_dissolved' => 'boolean',
            'dissolved_game_month' => 'integer',
            'last_attrition_month' => 'integer',
            'last_coached_game_month' => 'integer',
            'last_coached_game_week' => 'integer',
            'last_coached_game_day' => 'integer',
            'lat' => 'float',
            'lng' => 'float',
            'founded_game_month' => 'integer',
        ];
    }

    /**
     * Associated Game Session
     *
     * @return BelongsTo<GameSession, $this>
     */
    public function gameSession(): BelongsTo
    {
        return $this->belongsTo(GameSession::class);
    }

    /**
     * Ward where this church meets
     *
     * @return BelongsTo<Ward, $this>
     */
    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }

    /**
     * Mother church in the generational stream
     *
     * @return BelongsTo<Church, $this>
     */
    public function parentChurch(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_church_id');
    }

    /**
     * Daughter churches planted by this church
     *
     * @return HasMany<Church, $this>
     */
    public function daughterChurches(): HasMany
    {
        return $this->hasMany(self::class, 'parent_church_id');
    }

    /**
     * Local Timothy/Elder NPC leading this church
     *
     * @return BelongsTo<Npc, $this>
     */
    public function leaderNpc(): BelongsTo
    {
        return $this->belongsTo(Npc::class, 'leader_npc_id');
    }

    /**
     * Player directly leading this church (if G1)
     *
     * @return BelongsTo<User, $this>
     */
    public function leaderUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_user_id');
    }

    /**
     * Checks if this church practices a specific Acts 2 Church Circle element.
     * Leverages PHP 8.4 native array_any().
     */
    public function practicesElement(ChurchElement $element): bool
    {
        $practiced = $this->elements_json ?? [];

        return array_any($practiced, fn ($item) => $item === $element->value);
    }

    /**
     * Evaluates if all essential Church Circle elements are practiced for full autonomous maturity.
     * Leverages PHP 8.4 native array_all().
     */
    public function hasAllCoreElements(): bool
    {
        $coreElements = [
            ChurchElement::RepentanceBelief,
            ChurchElement::Baptism,
            ChurchElement::Word,
            ChurchElement::Prayer,
            ChurchElement::Fellowship,
            ChurchElement::Communion,
            ChurchElement::GivingMercy,
            ChurchElement::LocalLeaders,
            ChurchElement::MakingDisciples,
        ];

        return array_all(
            $coreElements,
            fn (ChurchElement $elem) => $this->practicesElement($elem)
        );
    }

    /**
     * Resident disciples/believers attending this church
     *
     * @return HasMany<Npc, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(Npc::class, 'church_id');
    }

    /**
     * Applies attrition (relocation, sickness, or interpersonal conflict) to this church.
     * Decreases attendance, believers count, and health score.
     * Dissolves the church if health score falls too low (<= 15) or attenders drops <= 1.
     */
    public function applyAttrition(
        string $reason,
        int $lostAttenders,
        int $lostBelievers,
        int $healthLoss,
        int $gameMonth
    ): bool {
        if ($this->is_dissolved) {
            return false;
        }

        $newAttenders = max(0, $this->attenders_count - $lostAttenders);
        $newBelievers = max(0, min($newAttenders, $this->believers_count - $lostBelievers));
        $newBaptized = max(0, min($newBelievers, $this->baptized_count));
        $newHealth = max(0, $this->health_score - $healthLoss);
        $isHealthy = $newHealth >= 50 && $this->hasAllCoreElements();

        $eventSummary = "Month {$gameMonth}: {$reason} (-{$lostAttenders} attenders, -{$healthLoss}% health)";

        // Dissolve church if attendance drops to critical minimum or health collapsed
        if ($newHealth <= 15 || $newAttenders <= 1) {
            $this->dissolve($reason, $gameMonth);

            return true;
        }

        $this->update([
            'attenders_count' => $newAttenders,
            'believers_count' => $newBelievers,
            'baptized_count' => $newBaptized,
            'health_score' => $newHealth,
            'is_healthy_church' => $isHealthy,
            'last_attrition_month' => $gameMonth,
            'last_attrition_event' => $eventSummary,
        ]);

        return false;
    }

    /**
     * Dissolves this church gathering.
     */
    public function dissolve(string $reason, int $gameMonth): void
    {
        $this->update([
            'is_dissolved' => true,
            'dissolved_game_month' => $gameMonth,
            'dissolution_reason' => $reason,
            'health_score' => 0,
            'is_healthy_church' => false,
            'last_attrition_month' => $gameMonth,
            'last_attrition_event' => "Month {$gameMonth}: Dissolved — {$reason}",
        ]);

        // Unlink resident NPC members
        Npc::where('church_id', $this->id)->update(['church_id' => null]);
    }

    /**
     * Merges this Bible study group / gathering into another active group or church,
     * transferring core members and marking this group as dissolved due to consolidation/merging.
     */
    public function mergeInto(self $targetChurch, int $gameMonth, ?string $reason = null): void
    {
        if ($this->is_dissolved || $this->id === $targetChurch->id) {
            return;
        }

        $transferredAttenders = max(1, (int) ceil($this->attenders_count * 0.6));
        $transferredBelievers = max(1, (int) ceil($this->believers_count * 0.6));
        $transferredBaptized = min($transferredBelievers, (int) ceil($this->baptized_count * 0.6));

        $mergeReason = $reason ?? "Bible study group merged into {$targetChurch->name} to consolidate fellowship and discipleship";

        $targetChurch->update([
            'attenders_count' => $targetChurch->attenders_count + $transferredAttenders,
            'believers_count' => $targetChurch->believers_count + $transferredBelievers,
            'baptized_count' => $targetChurch->baptized_count + $transferredBaptized,
            'health_score' => min(95, $targetChurch->health_score + 4),
        ]);

        // Re-link resident NPC members to the merged target church/group
        Npc::where('church_id', $this->id)->update(['church_id' => $targetChurch->id]);

        $this->update([
            'is_dissolved' => true,
            'dissolved_game_month' => $gameMonth,
            'dissolution_reason' => $mergeReason,
            'health_score' => 0,
            'attenders_count' => 0,
            'believers_count' => 0,
            'baptized_count' => 0,
            'is_healthy_church' => false,
            'last_attrition_month' => $gameMonth,
            'last_attrition_event' => "Month {$gameMonth}: Merged — {$mergeReason}",
        ]);
    }

    /**
     * Number of game months this group has been gathering since its founding.
     */
    public function monthsAsBibleStudy(int $currentGameMonth): int
    {
        return max(0, $currentGameMonth - ($this->founded_game_month ?: 1));
    }

    /**
     * Determines whether this developing Bible study group has been coached and its leader trained
     * to transition into an autonomous Acts 2 simple church after 6–12 months.
     */
    public function isCoachedAndTrainedForChurchTransition(int $currentGameMonth): bool
    {
        if ($this->is_dissolved || $this->is_healthy_church) {
            return false;
        }

        if ($this->monthsAsBibleStudy($currentGameMonth) < 6) {
            return false;
        }

        $isRecentlyCoached = $this->last_coached_game_month !== null
            ? ($currentGameMonth - $this->last_coached_game_month) <= 4
            : ($this->health_score >= 68);

        $leaderNpc = $this->leaderNpc;
        $isLeaderTrained = $leaderNpc
            ? ($leaderNpc->trained_411 && $leaderNpc->leadership_tier->level() >= 2)
            : ($this->leader_user_id !== null);

        return $isRecentlyCoached && $isLeaderTrained;
    }

    /**
     * Computes the probability (0–100%) that a developing Bible study group transitions
     * into a full 9-element Healthy Acts 2 Church at the given game month.
     * Groups must gather as a Bible study for at least 6–12 months; if coached and trained,
     * the probability of becoming a church is high (75%–95%).
     */
    public function churchTransitionProbability(int $currentGameMonth): int
    {
        if ($this->is_dissolved || $this->is_healthy_church) {
            return 0;
        }

        $monthsActive = $this->monthsAsBibleStudy($currentGameMonth);
        if ($monthsActive < 6) {
            return 0;
        }

        if ($this->isCoachedAndTrainedForChurchTransition($currentGameMonth)) {
            $maturityBonus = min(15, ($monthsActive - 6) * 3);
            $healthBonus = $this->health_score >= 72 ? 5 : 0;

            return min(95, 75 + $maturityBonus + $healthBonus);
        }

        return $monthsActive >= 12 && $this->health_score >= 70 ? 15 : 5;
    }

    /**
     * Transitions a developing Bible study group into a Healthy Acts 2 Church practicing all core elements.
     */
    public function transitionToHealthyChurch(int $gameMonth): void
    {
        $allElements = array_map(fn (ChurchElement $e) => $e->value, ChurchElement::cases());
        $newName = str_replace(
            ['Discovery Bible Study', 'Bible Study', 'Discovery Study'],
            'Acts 2 Circle',
            $this->name
        );

        $this->update([
            'name' => $newName,
            'elements_json' => $allElements,
            'is_healthy_church' => true,
            'health_score' => min(95, max(75, $this->health_score + 10)),
            'baptized_count' => max(2, $this->baptized_count),
            'last_coached_game_month' => $gameMonth,
        ]);
    }

    /**
     * Revitalizes a struggling or dissolved church through pastoral coaching / restart.
     */
    public function revitalize(int $gameMonth, ?User $revitalizedBy = null): void
    {
        $practiced = $this->elements_json ?? [];
        if (empty($practiced)) {
            $practiced = [
                ChurchElement::RepentanceBelief->value,
                ChurchElement::Word->value,
                ChurchElement::Prayer->value,
                ChurchElement::Fellowship->value,
            ];
        }

        $this->update([
            'is_dissolved' => false,
            'dissolved_game_month' => null,
            'dissolution_reason' => null,
            'health_score' => 50,
            'is_healthy_church' => false,
            'attenders_count' => max(4, $this->attenders_count + 3),
            'believers_count' => max(2, $this->believers_count + 2),
            'baptized_count' => max(1, $this->baptized_count + 1),
            'elements_json' => $practiced,
            'last_attrition_event' => "Month {$gameMonth}: Revitalized through apostolic coaching!",
        ]);
    }

    /**
     * Evaluates whether this church is eligible for Church Circles coaching on the given game date/week.
     * Coaching is limited to once per week (1 coaching session per 7-day game week).
     *
     * @return array{allowed: bool, reason: string|null, days_remaining: int}
     */
    public function coachingEligibility(int $currentMonth, int $currentWeek, ?int $currentDay = null): array
    {
        $targetWeek = $currentDay !== null && $currentDay > 4
            ? min(4, max(1, (int) ceil($currentDay / 7)))
            : min(4, max(1, $currentWeek));

        // 1. Check if there is already an action pending for this church/leader in the target week
        $pendingActions = QueuedMonthlyAction::where('action_type', MinistryActionType::ChurchCircles)
            ->where('status', 'pending')
            ->where('game_month', $currentMonth)
            ->where(function ($q) {
                if ($this->leader_npc_id) {
                    $q->where('target_npc_id', $this->leader_npc_id);
                } else {
                    $q->where('ward_id', $this->ward_id);
                }
            })
            ->get();

        foreach ($pendingActions as $action) {
            $actionWeek = $action->game_week ?: ($action->game_day > 4 ? min(4, (int) ceil($action->game_day / 7)) : $action->game_day);
            $dayConflict = $currentDay !== null && $action->game_day && abs($currentDay - $action->game_day) < 7;
            if ($actionWeek === $targetWeek || $dayConflict) {
                return [
                    'allowed' => false,
                    'reason' => "Church Circles coaching is already scheduled for {$this->name} in Week {$actionWeek} / Month {$action->game_month}. Church coaching requires extensive 8 AP preparation and is limited to once per week.",
                    'days_remaining' => 1,
                ];
            }
        }

        // 2. Check recorded last coached date on church model
        if ($this->last_coached_game_month === $currentMonth) {
            $lastWeek = $this->last_coached_game_week ?: ($this->last_coached_game_day > 4 ? min(4, (int) ceil($this->last_coached_game_day / 7)) : $this->last_coached_game_day);
            $dayConflict = $currentDay !== null && $this->last_coached_game_day && abs($currentDay - $this->last_coached_game_day) < 7;
            if ($lastWeek === $targetWeek || $dayConflict) {
                return [
                    'allowed' => false,
                    'reason' => "{$this->name} was already coached in Week {$lastWeek} / Month {$this->last_coached_game_month}. Church Circles coaching is limited to once per week.",
                    'days_remaining' => 1,
                ];
            }
        }

        // 3. Check historical processed actions in QueuedMonthlyActions in the same week
        $recentProcessed = QueuedMonthlyAction::where('action_type', MinistryActionType::ChurchCircles)
            ->where('status', 'processed')
            ->where('game_month', $currentMonth)
            ->where(function ($q) {
                if ($this->leader_npc_id) {
                    $q->where('target_npc_id', $this->leader_npc_id);
                } else {
                    $q->where('ward_id', $this->ward_id);
                }
            })
            ->get();

        foreach ($recentProcessed as $pastAction) {
            $pastWeek = $pastAction->game_week ?: ($pastAction->game_day > 4 ? min(4, (int) ceil($pastAction->game_day / 7)) : $pastAction->game_day);
            $dayConflict = $currentDay !== null && $pastAction->game_day && abs($currentDay - $pastAction->game_day) < 7;
            if ($pastWeek === $targetWeek || $dayConflict) {
                return [
                    'allowed' => false,
                    'reason' => "{$this->name} was coached in Week {$pastWeek} / Month {$pastAction->game_month}. Church Circles coaching is limited to once per week.",
                    'days_remaining' => 1,
                ];
            }
        }

        return [
            'allowed' => true,
            'reason' => null,
            'days_remaining' => 0,
        ];
    }
}
