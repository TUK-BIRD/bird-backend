<?php

namespace App\Console\Commands;

use App\Models\Room;
use App\Services\LocationEstimateService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class GenerateLocationEstimates extends Command
{
    protected $signature = 'location-estimates:generate
        {--room-id= : Generate estimates for one room}
        {--space-id= : Generate estimates for rooms in one space}
        {--window-minutes= : Scan event window size}
        {--minimum-anchor-matches= : Minimum anchors required per device}
        {--until= : Window end timestamp}
        {--dry-run : Run estimator without saving rows}';

    protected $description = 'Generate BLE location estimates and store them as database snapshots.';

    public function handle(LocationEstimateService $service): int
    {
        $windowMinutes = (int) ($this->option('window-minutes') ?: config('services.location_estimator.window_minutes', 5));
        $minimumAnchorMatches = (int) ($this->option('minimum-anchor-matches') ?: config('services.location_estimator.minimum_anchor_matches', 2));

        if ($windowMinutes < 1 || $windowMinutes > 30) {
            $this->error('window-minutes must be between 1 and 30.');

            return self::FAILURE;
        }

        if ($minimumAnchorMatches < 2 || $minimumAnchorMatches > 20) {
            $this->error('minimum-anchor-matches must be between 2 and 20.');

            return self::FAILURE;
        }

        $until = $this->option('until')
            ? CarbonImmutable::parse((string) $this->option('until'))
            : CarbonImmutable::now();
        $since = $until->subMinutes($windowMinutes);
        $persist = ! (bool) $this->option('dry-run');

        $rooms = Room::query()
            ->when($this->option('room-id'), fn ($query, $roomId) => $query->where('id', (int) $roomId))
            ->when($this->option('space-id'), fn ($query, $spaceId) => $query->where('space_id', (int) $spaceId))
            ->whereHas('bleAnchors', fn ($query) => $query->whereNotNull('installed_at'))
            ->orderBy('id')
            ->get();

        if ($rooms->isEmpty()) {
            $this->info('No rooms with installed anchors found.');

            return self::SUCCESS;
        }

        $totalDevices = 0;

        foreach ($rooms as $room) {
            $result = $service->estimateForRoom(
                $room,
                $since,
                $until,
                $minimumAnchorMatches,
                $persist,
                $until
            );

            $deviceCount = $result['devices']->count();
            $totalDevices += $deviceCount;

            $this->line(sprintf(
                'Room %d: %d estimates%s',
                $room->id,
                $deviceCount,
                $persist ? ' saved' : ' generated'
            ));
        }

        $this->info(sprintf(
            'Generated %d location estimates for %d rooms (%s to %s).',
            $totalDevices,
            $rooms->count(),
            $since->toIso8601String(),
            $until->toIso8601String()
        ));

        return self::SUCCESS;
    }
}
