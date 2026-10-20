<?php

namespace App\Support;

use App\Models\MalaysiaLocation;
use Illuminate\Support\Facades\Schema;

class MalaysianLocations
{
    /**
     * Primary hierarchical mapping of Malaysian States, Cities, and Postcodes.
     *
     * @return array<string, array{cities: list<string>, postcodes: list<string>, city_postcodes?: array<string, list<string>>}>
     */
    public static function all(): array
    {
        // If database table exists and has rows, merge / return database set
        if (self::hasDatabaseSet()) {
            $dbSet = self::fromDatabase();
            if (! empty($dbSet)) {
                return $dbSet;
            }
        }

        return self::defaultDataset();
    }

    /**
     * Check if the database table exists and has records.
     */
    public static function hasDatabaseSet(): bool
    {
        try {
            return Schema::hasTable('malaysia_locations') && MalaysiaLocation::query()->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Load locations structured from the database table.
     *
     * @return array<string, array{cities: list<string>, postcodes: list<string>, city_postcodes?: array<string, list<string>>}>
     */
    public static function fromDatabase(): array
    {
        $rows = MalaysiaLocation::query()->orderBy('state')->orderBy('city')->get();
        if ($rows->isEmpty()) {
            return [];
        }

        $result = [];
        foreach ($rows as $row) {
            $st = $row->state;
            $ct = $row->city;
            $pc = $row->postcode;

            if (! isset($result[$st])) {
                $result[$st] = [
                    'cities' => [],
                    'postcodes' => [],
                    'city_postcodes' => [],
                ];
            }

            if (! in_array($ct, $result[$st]['cities'], true)) {
                $result[$st]['cities'][] = $ct;
            }

            if (! in_array($pc, $result[$st]['postcodes'], true)) {
                $result[$st]['postcodes'][] = $pc;
            }

            if (! isset($result[$st]['city_postcodes'][$ct])) {
                $result[$st]['city_postcodes'][$ct] = [];
            }
            if (! in_array($pc, $result[$st]['city_postcodes'][$ct], true)) {
                $result[$st]['city_postcodes'][$ct][] = $pc;
            }
        }

        return $result;
    }

    /**
     * List of all 16 Malaysian States & Federal Territories.
     *
     * @return list<string>
     */
    public static function states(): array
    {
        return array_keys(self::all());
    }

    /**
     * Cities in a given state (or across all states if null).
     *
     * @return list<string>
     */
    public static function cities(?string $state = null): array
    {
        $all = self::all();

        if ($state !== null && isset($all[$state])) {
            return $all[$state]['cities'];
        }

        $cities = [];
        foreach ($all as $stData) {
            foreach ($stData['cities'] as $c) {
                if (! in_array($c, $cities, true)) {
                    $cities[] = $c;
                }
            }
        }

        sort($cities);

        return $cities;
    }

    /**
     * Postcodes in a state or specific city.
     *
     * @return list<string>
     */
    public static function postcodes(?string $state = null, ?string $city = null): array
    {
        $all = self::all();

        if ($state !== null && isset($all[$state])) {
            if ($city !== null && isset($all[$state]['city_postcodes'][$city])) {
                return $all[$state]['city_postcodes'][$city];
            }

            return $all[$state]['postcodes'];
        }

        $postcodes = [];
        foreach ($all as $stData) {
            foreach ($stData['postcodes'] as $p) {
                if (! in_array($p, $postcodes, true)) {
                    $postcodes[] = $p;
                }
            }
        }

        sort($postcodes);

        return $postcodes;
    }

    /**
     * Lookup State and City by Postcode.
     *
     * @return array{state: string, city: string}|null
     */
    public static function lookupByPostcode(string $postcode): ?array
    {
        $postcode = trim($postcode);
        if ($postcode === '') {
            return null;
        }

        $all = self::all();

        // 1. Direct search in city_postcodes mapping
        foreach ($all as $state => $data) {
            if (isset($data['city_postcodes'])) {
                foreach ($data['city_postcodes'] as $city => $pcs) {
                    if (in_array($postcode, $pcs, true)) {
                        return ['state' => $state, 'city' => $city];
                    }
                }
            }
            if (in_array($postcode, $data['postcodes'], true)) {
                return ['state' => $state, 'city' => $data['cities'][0] ?? ''];
            }
        }

        // 2. Prefix range mapping for 5-digit Malaysian postcodes
        $digits = preg_replace('/\D/', '', $postcode);
        if (strlen($digits) === 5) {
            $num = (int) $digits;
            $prefixState = self::stateByPostalNumber($num);
            if ($prefixState && isset($all[$prefixState])) {
                return [
                    'state' => $prefixState,
                    'city' => $all[$prefixState]['cities'][0] ?? '',
                ];
            }
        }

        return null;
    }

    /**
     * Determine State by standard Malaysian 5-digit postal number.
     */
    public static function stateByPostalNumber(int $num): ?string
    {
        return match (true) {
            $num >= 1000 && $num <= 2999 => 'Perlis',
            $num >= 5000 && $num <= 9999 => 'Kedah',
            $num >= 10000 && $num <= 14999 => 'Pulau Pinang',
            $num >= 15000 && $num <= 18999 => 'Kelantan',
            $num >= 20000 && $num <= 24999 => 'Terengganu',
            $num >= 39000 && $num <= 39299 => 'Pahang',
            $num >= 49000 && $num <= 49099 => 'Pahang',
            $num >= 69000 && $num <= 69099 => 'Pahang',
            $num >= 25000 && $num <= 28999 => 'Pahang',
            $num >= 30000 && $num <= 36999 => 'Perak',
            $num >= 50000 && $num <= 60999 => 'Wilayah Persekutuan Kuala Lumpur',
            $num >= 62000 && $num <= 62999 => 'Wilayah Persekutuan Putrajaya',
            $num >= 63000 && $num <= 64999 => 'Selangor',
            $num >= 68000 && $num <= 68199 => 'Selangor',
            $num >= 40000 && $num <= 48999 => 'Selangor',
            $num >= 70000 && $num <= 73999 => 'Negeri Sembilan',
            $num >= 75000 && $num <= 78999 => 'Melaka',
            $num >= 80000 && $num <= 86999 => 'Johor',
            $num >= 87000 && $num <= 87099 => 'Wilayah Persekutuan Labuan',
            $num >= 88000 && $num <= 91999 => 'Sabah',
            $num >= 93000 && $num <= 98999 => 'Sarawak',
            default => null,
        };
    }

    /**
     * Default canonical Malaysian location dataset.
     *
     * @return array<string, array{cities: list<string>, postcodes: list<string>, city_postcodes: array<string, list<string>>}>
     */
    public static function defaultDataset(): array
    {
        return [
            'Johor' => [
                'cities' => [
                    'Johor Bahru', 'Iskandar Puteri', 'Pasir Gudang', 'Batu Pahat', 'Kluang',
                    'Kulai', 'Muar', 'Kota Tinggi', 'Segamat', 'Pontian', 'Tangkak',
                    'Mersing', 'Skudai', 'Senai', 'Ulu Tiram', 'Yong Peng',
                ],
                'postcodes' => [
                    '79000', '79100', '80000', '80100', '80200', '80300', '80400', '81000',
                    '81100', '81200', '81300', '81400', '81500', '81700', '81800', '81900',
                    '82000', '83000', '83700', '84000', '84900', '85000', '86000', '86800',
                ],
                'city_postcodes' => [
                    'Johor Bahru' => ['80000', '80100', '80200', '80300', '80400', '81100', '81200'],
                    'Iskandar Puteri' => ['79000', '79100'],
                    'Skudai' => ['81300'],
                    'Senai' => ['81400'],
                    'Kulai' => ['81000'],
                    'Pasir Gudang' => ['81700'],
                    'Ulu Tiram' => ['81800'],
                    'Kota Tinggi' => ['81900'],
                    'Pontian' => ['82000'],
                    'Batu Pahat' => ['83000'],
                    'Yong Peng' => ['83700'],
                    'Muar' => ['84000'],
                    'Tangkak' => ['84900'],
                    'Segamat' => ['85000'],
                    'Kluang' => ['86000'],
                    'Mersing' => ['86800'],
                ],
            ],
            'Kedah' => [
                'cities' => [
                    'Alor Setar', 'Sungai Petani', 'Kulim', 'Langkawi', 'Jitra',
                    'Baling', 'Kubang Pasu', 'Pendang', 'Yan', 'Sik', 'Pokok Sena', 'Kuala Kedah',
                ],
                'postcodes' => [
                    '05000', '05100', '05200', '05300', '06000', '06600', '07000',
                    '08000', '08200', '08300', '08400', '08500', '08600', '09000', '09100',
                ],
                'city_postcodes' => [
                    'Alor Setar' => ['05000', '05100', '05200', '05300'],
                    'Jitra' => ['06000'],
                    'Kuala Kedah' => ['06600'],
                    'Langkawi' => ['07000'],
                    'Sungai Petani' => ['08000'],
                    'Baling' => ['09100'],
                    'Kulim' => ['09000'],
                ],
            ],
            'Kelantan' => [
                'cities' => [
                    'Kota Bharu', 'Pasir Mas', 'Tumpat', 'Bachok', 'Tanah Merah',
                    'Pasir Puteh', 'Kuala Krai', 'Machang', 'Gua Musang', 'Jeli', 'Rantau Panjang',
                ],
                'postcodes' => [
                    '15000', '15100', '16010', '16100', '16200', '16300', '16800',
                    '17000', '17200', '17500', '18000', '18300', '18500',
                ],
                'city_postcodes' => [
                    'Kota Bharu' => ['15000', '15100', '16100'],
                    'Tumpat' => ['16200'],
                    'Bachok' => ['16300'],
                    'Pasir Puteh' => ['16800'],
                    'Pasir Mas' => ['17000'],
                    'Rantau Panjang' => ['17200'],
                    'Tanah Merah' => ['17500'],
                    'Kuala Krai' => ['18000'],
                    'Gua Musang' => ['18300'],
                    'Jeli' => ['18500'],
                ],
            ],
            'Melaka' => [
                'cities' => [
                    'Melaka', 'Ayer Keroh', 'Alor Gajah', 'Jasin', 'Batu Berendam',
                    'Masjid Tanah', 'Merlimau', 'Klebang',
                ],
                'postcodes' => [
                    '75000', '75100', '75200', '75300', '75350', '75450', '77000', '77300', '78000', '78300',
                ],
                'city_postcodes' => [
                    'Melaka' => ['75000', '75100', '75200'],
                    'Batu Berendam' => ['75350'],
                    'Ayer Keroh' => ['75450'],
                    'Jasin' => ['77000'],
                    'Merlimau' => ['77300'],
                    'Alor Gajah' => ['78000'],
                    'Masjid Tanah' => ['78300'],
                ],
            ],
            'Negeri Sembilan' => [
                'cities' => [
                    'Seremban', 'Nilai', 'Port Dickson', 'Senawang', 'Bahau',
                    'Tampin', 'Kuala Pilah', 'Rembau', 'Jempol',
                ],
                'postcodes' => [
                    '70000', '70100', '70200', '70300', '70450', '71000', '71450', '71800',
                    '72000', '73000', '73100',
                ],
                'city_postcodes' => [
                    'Seremban' => ['70000', '70100', '70200', '70300'],
                    'Senawang' => ['70450'],
                    'Port Dickson' => ['71000'],
                    'Rembau' => ['71450'],
                    'Nilai' => ['71800'],
                    'Kuala Pilah' => ['72000'],
                    'Tampin' => ['73000'],
                    'Bahau' => ['73100'],
                ],
            ],
            'Pahang' => [
                'cities' => [
                    'Kuantan', 'Temerloh', 'Bentong', 'Cameron Highlands', 'Raub',
                    'Jerantut', 'Pekan', 'Maran', 'Rompin', 'Bera', 'Genting Highlands', 'Kuala Lipis',
                ],
                'postcodes' => [
                    '25000', '25100', '25200', '26000', '26600', '27000', '27200',
                    '27600', '28000', '28400', '28700', '39000', '39100', '49000', '69000',
                ],
                'city_postcodes' => [
                    'Kuantan' => ['25000', '25100', '25200', '26000'],
                    'Pekan' => ['26600'],
                    'Jerantut' => ['27000'],
                    'Kuala Lipis' => ['27200'],
                    'Raub' => ['27600'],
                    'Temerloh' => ['28000'],
                    'Mentakab' => ['28400'],
                    'Bentong' => ['28700'],
                    'Cameron Highlands' => ['39000', '39100'],
                    'Fraser\'s Hill' => ['49000'],
                    'Genting Highlands' => ['69000'],
                ],
            ],
            'Perak' => [
                'cities' => [
                    'Ipoh', 'Taiping', 'Teluk Intan', 'Sitiawan', 'Manjung',
                    'Lumut', 'Kuala Kangsar', 'Batu Gajah', 'Kampar', 'Tapah',
                    'Tanjung Malim', 'Parit Buntar', 'Bagan Serai',
                ],
                'postcodes' => [
                    '30000', '30100', '30200', '31000', '31100', '31400', '31900', '32000',
                    '32200', '33000', '34000', '34200', '35000', '35900', '36000',
                ],
                'city_postcodes' => [
                    'Ipoh' => ['30000', '30100', '30200', '31400'],
                    'Batu Gajah' => ['31000'],
                    'Kampar' => ['31900'],
                    'Sitiawan' => ['32000'],
                    'Lumut' => ['32200'],
                    'Kuala Kangsar' => ['33000'],
                    'Taiping' => ['34000'],
                    'Parit Buntar' => ['34200'],
                    'Tapah' => ['35000'],
                    'Tanjung Malim' => ['35900'],
                    'Teluk Intan' => ['36000'],
                ],
            ],
            'Perlis' => [
                'cities' => [
                    'Kangar', 'Arau', 'Padang Besar', 'Kuala Perlis', 'Simpang Ampat',
                ],
                'postcodes' => [
                    '01000', '02000', '02100', '02600',
                ],
                'city_postcodes' => [
                    'Kangar' => ['01000'],
                    'Kuala Perlis' => ['02000'],
                    'Padang Besar' => ['02100'],
                    'Arau' => ['02600'],
                ],
            ],
            'Pulau Pinang' => [
                'cities' => [
                    'George Town', 'Bayan Lepas', 'Butterworth', 'Bukit Mertajam', 'Seberang Perai',
                    'Balik Pulau', 'Gelugor', 'Air Itam', 'Tanjung Bungah', 'Kepala Batas', 'Nibong Tebal',
                ],
                'postcodes' => [
                    '10000', '10100', '10200', '10470', '11000', '11200', '11400', '11700',
                    '11900', '12000', '13000', '13200', '14000', '14300',
                ],
                'city_postcodes' => [
                    'George Town' => ['10000', '10100', '10200', '10470'],
                    'Balik Pulau' => ['11000'],
                    'Tanjung Bungah' => ['11200'],
                    'Air Itam' => ['11400'],
                    'Gelugor' => ['11700'],
                    'Bayan Lepas' => ['11900'],
                    'Butterworth' => ['12000', '13000'],
                    'Kepala Batas' => ['13200'],
                    'Bukit Mertajam' => ['14000'],
                    'Nibong Tebal' => ['14300'],
                ],
            ],
            'Sabah' => [
                'cities' => [
                    'Kota Kinabalu', 'Sandakan', 'Tawau', 'Lahad Datu', 'Keningau',
                    'Penampang', 'Putatan', 'Semporna', 'Kudat', 'Tuaran', 'Papar', 'Ranau', 'Beaufort',
                ],
                'postcodes' => [
                    '88000', '88100', '88200', '88300', '88450', '89000', '89150', '89200',
                    '89500', '89600', '90000', '91000', '91100', '91300',
                ],
                'city_postcodes' => [
                    'Kota Kinabalu' => ['88000', '88100', '88200', '88300', '88450'],
                    'Keningau' => ['89000'],
                    'Kudat' => ['89150'],
                    'Tuaran' => ['89200'],
                    'Penampang' => ['89500'],
                    'Papar' => ['89600'],
                    'Sandakan' => ['90000'],
                    'Tawau' => ['91000'],
                    'Lahad Datu' => ['91100'],
                    'Semporna' => ['91300'],
                ],
            ],
            'Sarawak' => [
                'cities' => [
                    'Kuching', 'Miri', 'Sibu', 'Bintulu', 'Samarahan',
                    'Sri Aman', 'Sarikei', 'Limbang', 'Kapit', 'Mukah', 'Lawas', 'Betong', 'Bau',
                ],
                'postcodes' => [
                    '93000', '93050', '93100', '93200', '93300', '94000', '94300', '95000',
                    '96000', '96100', '97000', '98000', '98700',
                ],
                'city_postcodes' => [
                    'Kuching' => ['93000', '93050', '93100', '93200', '93300'],
                    'Bau' => ['94000'],
                    'Samarahan' => ['94300'],
                    'Sri Aman' => ['95000'],
                    'Sibu' => ['96000'],
                    'Sarikei' => ['96100'],
                    'Bintulu' => ['97000'],
                    'Miri' => ['98000'],
                    'Limbang' => ['98700'],
                ],
            ],
            'Selangor' => [
                'cities' => [
                    'Shah Alam', 'Petaling Jaya', 'Subang Jaya', 'Klang', 'Ampang',
                    'Kajang', 'Rawang', 'Cyberjaya', 'Puchong', 'Sepang',
                    'Seri Kembangan', 'Banting', 'Kuala Selangor', 'Semenyih',
                    'Gombak', 'Bangi', 'Selayang', 'Damansara',
                ],
                'postcodes' => [
                    '40000', '40100', '40150', '40200', '40300', '40400', '41000', '41200',
                    '42000', '42700', '43000', '43200', '43300', '43650', '43800', '45000',
                    '46000', '47000', '47100', '47300', '47400', '47500', '47600', '47800',
                    '47810', '48000', '63000', '64000', '68000', '68100',
                ],
                'city_postcodes' => [
                    'Shah Alam' => ['40000', '40100', '40150', '40200', '40300', '40400'],
                    'Klang' => ['41000', '41200', '42000'],
                    'Banting' => ['42700'],
                    'Kajang' => ['43000', '43200'],
                    'Seri Kembangan' => ['43300'],
                    'Bangi' => ['43650'],
                    'Semenyih' => ['43800'],
                    'Kuala Selangor' => ['45000'],
                    'Petaling Jaya' => ['46000', '47300', '47400', '47800', '47810'],
                    'Puchong' => ['47100'],
                    'Subang Jaya' => ['47500', '47600'],
                    'Rawang' => ['48000'],
                    'Cyberjaya' => ['63000'],
                    'Sepang' => ['64000'],
                    'Ampang' => ['68000'],
                    'Batu Caves' => ['68100'],
                ],
            ],
            'Terengganu' => [
                'cities' => [
                    'Kuala Terengganu', 'Kemaman', 'Dungun', 'Besut', 'Marang',
                    'Hulu Terengganu', 'Setiu', 'Kerteh', 'Chukai',
                ],
                'postcodes' => [
                    '20000', '21000', '22000', '23000', '24000', '24100', '24300',
                ],
                'city_postcodes' => [
                    'Kuala Terengganu' => ['20000', '21000'],
                    'Besut' => ['22000'],
                    'Dungun' => ['23000'],
                    'Chukai' => ['24000'],
                    'Kemaman' => ['24100'],
                    'Kerteh' => ['24300'],
                ],
            ],
            'Wilayah Persekutuan Kuala Lumpur' => [
                'cities' => [
                    'Kuala Lumpur', 'Cheras', 'Kepong', 'Setapak', 'Wangsa Maju',
                    'Bukit Bintang', 'Bangsar', 'Sentul', 'Segambut', 'Mont Kiara',
                    'Ampang', 'Brickfields', 'Titiwangsa',
                ],
                'postcodes' => [
                    '50000', '50050', '50100', '50200', '50250', '50300', '50350', '50400',
                    '50450', '50480', '51000', '51100', '51200', '52000', '52100', '53000',
                    '53100', '53200', '53300', '54000', '54200', '55000', '55100', '56000',
                    '56100', '57000', '58000', '59000', '59100', '59200', '60000',
                ],
                'city_postcodes' => [
                    'Kuala Lumpur' => ['50000', '50050', '50100', '50200', '50250', '50300', '50350', '50400', '50450'],
                    'Mont Kiara' => ['50480'],
                    'Sentul' => ['51000', '51100'],
                    'Segambut' => ['51200'],
                    'Kepong' => ['52000', '52100'],
                    'Setapak' => ['53000', '53100'],
                    'Wangsa Maju' => ['53300'],
                    'Titiwangsa' => ['54200'],
                    'Bukit Bintang' => ['55100'],
                    'Cheras' => ['56000', '56100'],
                    'Bangsar' => ['59000', '59100'],
                ],
            ],
            'Wilayah Persekutuan Labuan' => [
                'cities' => [
                    'Victoria', 'Labuan',
                ],
                'postcodes' => [
                    '87000', '87008', '87010', '87020',
                ],
                'city_postcodes' => [
                    'Labuan' => ['87000', '87008', '87010', '87020'],
                    'Victoria' => ['87000'],
                ],
            ],
            'Wilayah Persekutuan Putrajaya' => [
                'cities' => [
                    'Putrajaya',
                ],
                'postcodes' => [
                    '62000', '62100', '62200', '62300', '62502', '62675',
                ],
                'city_postcodes' => [
                    'Putrajaya' => ['62000', '62100', '62200', '62300', '62502', '62675'],
                ],
            ],
        ];
    }
}
