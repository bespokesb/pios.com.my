<?php

namespace Database\Seeders;

use App\Models\MalaysiaLocation;
use App\Support\MalaysianLocations;
use Illuminate\Database\Seeder;

class MalaysiaLocationSeeder extends Seeder
{
    /**
     * Seed the malaysia_locations database table.
     */
    public function run(): void
    {
        $dataset = MalaysianLocations::defaultDataset();

        $rows = [];
        $now = now();

        foreach ($dataset as $state => $data) {
            $cities = $data['cities'] ?? [];
            $cityPostcodes = $data['city_postcodes'] ?? [];
            $allPostcodes = $data['postcodes'] ?? [];

            // Seed mapped city-postcodes
            $handledPostcodes = [];
            foreach ($cityPostcodes as $city => $postcodes) {
                foreach ($postcodes as $postcode) {
                    $key = "{$state}|{$city}|{$postcode}";
                    $rows[$key] = [
                        'state' => $state,
                        'city' => $city,
                        'postcode' => $postcode,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $handledPostcodes[$postcode] = true;
                }
            }

            // Postcodes not explicitly grouped in city_postcodes
            $defaultCity = $cities[0] ?? $state;
            foreach ($allPostcodes as $postcode) {
                if (! isset($handledPostcodes[$postcode])) {
                    $key = "{$state}|{$defaultCity}|{$postcode}";
                    $rows[$key] = [
                        'state' => $state,
                        'city' => $defaultCity,
                        'postcode' => $postcode,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            // Ensure every city has at least one entry even if not mapped to a postcode yet
            foreach ($cities as $city) {
                if (! isset($cityPostcodes[$city]) || empty($cityPostcodes[$city])) {
                    $dummyPostcode = $allPostcodes[0] ?? '00000';
                    $key = "{$state}|{$city}|{$dummyPostcode}";
                    if (! isset($rows[$key])) {
                        $rows[$key] = [
                            'state' => $state,
                            'city' => $city,
                            'postcode' => $dummyPostcode,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }
            }
        }

        foreach (array_chunk(array_values($rows), 100) as $chunk) {
            MalaysiaLocation::upsert(
                $chunk,
                ['state', 'city', 'postcode'],
                ['updated_at']
            );
        }
    }
}
