<?php

namespace App\Console\Commands;

use App\Services\NagoyaGeoJsonService;
use Illuminate\Console\Command;

class ImportNagoyaBordersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nagoya:import-borders {--source= : Custom source GeoJSON file path} {--force : Overwrite existing storage border file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import authentic Nagoya 16-ward GIS boundaries and map ward names from MLIT GeoJSON';

    /**
     * Execute the console command.
     */
    public function handle(NagoyaGeoJsonService $service): int
    {
        $this->info('Starting Nagoya Ward GIS boundary import...');

        $destination = storage_path('app/nagoya-border.json');
        $source = $this->option('source') ?: '/Users/ko/code/development/praystation/public/geojson/nagoya-border.json';

        if (! file_exists($source)) {
            $this->error("Source file not found at: {$source}");

            return self::FAILURE;
        }

        $result = $service->importFromSource($source, $destination);

        if (! $result || empty($result['features'])) {
            $this->error('Failed to import features from source GeoJSON.');

            return self::FAILURE;
        }

        $count = count($result['features']);
        $this->info("Successfully imported {$count} ward boundaries into: {$destination}");

        foreach ($result['features'] as $feature) {
            $name = $feature['properties']['name'] ?? 'Unknown';
            $nameJa = $feature['properties']['name_ja'] ?? 'Unknown';
            $slug = $feature['properties']['slug'] ?? 'Unknown';
            $this->line("  ✓ {$name} ({$nameJa}) [{$slug}]");
        }

        return self::SUCCESS;
    }
}
