<?php

namespace Modules\Location\Consoles;

use Gobiz\Support\Helper;
use Illuminate\Console\Command;
use Modules\Location\Models\Location;
use Modules\Location\Models\LocationSearch;

class ImportLocationSearch extends Command
{
    protected $signature = 'location:import_location_searchs {countryCode}';
    protected $description = 'import keyword for searching location';

    public function handle()
    {
        $countryCode    = $this->argument('countryCode');
        $provincesQuery = Location::query()->where('type', Location::TYPE_PROVINCE)->where('parent_code', $countryCode);

        $provincesQuery->chunkById(5, function ($provinces) use ($countryCode) {
            /** @var Location $province */
            foreach ($provinces as $province) {
                $this->importKeywordProvince($province, $countryCode);
                $this->info('imported for province ' . $province->label);
                $province->childrens->each(function (Location $district) use ($countryCode) {
                    $this->importKeywordDistrict($district, $countryCode);
                    $this->info('imported for district ' . $district->label);
                    $district->childrens->each(function (Location $ward) use ($countryCode) {
                        $this->importKeywordWard($ward, $countryCode);
                        $this->info('imported for ward ' . $ward->label);
                    });
                });
            }
        }, 'id');
    }

    /**
     * @param Location $province
     * @param string $countryCode
     */
    protected function importKeywordProvince(Location $province, string $countryCode)
    {
        if ($countryCode == Location::COUNTRY_VIETNAM) {
            $baseKeyword = Helper::convert_vi_to_en(strtolower($province->label));
            $this->addKeywords($province, $baseKeyword, ['tinh', 'thanh pho']);
            return;
        }
        $baseKeyword = Helper::clean(strtolower($province->label));
        $this->addKeywords($province, $baseKeyword);
    }

    /**
     * @param Location $district
     * @param string $countryCode
     */
    protected function importKeywordDistrict(Location $district, string $countryCode)
    {
        if ($countryCode == Location::COUNTRY_VIETNAM) {
            $baseKeyword = Helper::convert_vi_to_en(strtolower($district->label));
            $this->addKeywords($district, $baseKeyword, ['quan', 'huyen']);
            return;
        }
        $baseKeyword = Helper::clean(strtolower($district->label));
        $this->addKeywords($district, $baseKeyword);
    }

    /**
     * @param Location $ward
     * @param string $countryCode
     */
    protected function importKeywordWard(Location $ward, string $countryCode)
    {
        if ($countryCode == Location::COUNTRY_VIETNAM) {
            $baseKeyword = Helper::convert_vi_to_en(strtolower($ward->label));
            $this->addKeywords($ward, $baseKeyword, ['xa', 'phuong']);
            return;
        }
        $baseKeyword = Helper::clean(strtolower($ward->label));
        $this->addKeywords($ward, $baseKeyword);
    }

    /**
     * @param Location $location
     * @param string $baseKeyword
     * @param array $prefixes
     */
    protected function addKeywords(Location $location, string $baseKeyword, array $prefixes = [])
    {
        $keywords = [str_replace(' ', '', $baseKeyword)];
        foreach ($prefixes as $prefix) {
            $keyword1   = trim(str_replace($prefix, '', $baseKeyword));
            $keywords[] = str_replace(' ', '', $keyword1);
        }
        foreach ($keywords as $keyword) {
            LocationSearch::updateOrCreate(
                [
                    'location_id' => $location->id,
                    'keyword' => $keyword
                ],
                [
                    'type' => $location->type,
                    'parent_code' => $location->parent_code
                ]
            );
        }
    }
}
