<?php

namespace Modules\Location\Consoles;

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Gobiz\Support\Helper;
use Illuminate\Console\Command;
use Modules\Location\Models\Location;
use Rap2hpoutre\FastExcel\FastExcel;

class ImportLocationCambodia extends Command
{
    protected $signature = 'location:import_location_cambodia';
    protected $description = 'import cambodia locations';

    /**
     * @throws IOException
     * @throws UnsupportedTypeException
     * @throws ReaderNotOpenedException
     */
    public function handle()
    {
        $codes = [];
        (new FastExcel)->import(storage_path('location_cambodia.xlsx'), function ($line) use (&$codes) {
            if (empty($codes['province'][Helper::clean($line['province'])])) {
                $codes['province'][Helper::clean($line['province'])] = rand(111, 999);
                while (count($codes['province']) > count(array_unique($codes['province']))) {
                    unset($codes['province'][Helper::clean($line['province'])]);
                    $codes['province'][Helper::clean($line['province'])] = rand(111, 999);
                }
            }
            if (empty($codes['district'][Helper::clean($line['province']) . Helper::clean($line['district'])])) {
                $codes['district'][Helper::clean($line['province']) . Helper::clean($line['district'])] = $codes['province'][Helper::clean($line['province'])] . rand(111, 999);
                while (count($codes['district']) > count(array_unique($codes['district']))) {
                    unset($codes['district'][Helper::clean($line['province']) . Helper::clean($line['district'])]);
                    $codes['district'][Helper::clean($line['province']) . Helper::clean($line['district'])] = $codes['province'][Helper::clean($line['province'])] . rand(111, 999);
                }
            }

            $provinceCode = 'CAM' . $codes['province'][Helper::clean($line['province'])];
            $districtCode = 'CAM' . $codes['district'][Helper::clean($line['province']) . Helper::clean($line['district'])];
            $province     = Location::updateOrCreate([
                'code' => $provinceCode,
                'type' => Location::TYPE_PROVINCE,
                'parent_code' => Location::COUNTRY_CAMBODIA
            ], ['label' => $line['province']]);
            $this->info('inserted province ' . $province->label);
            $district = Location::updateOrCreate([
                'code' => $districtCode,
                'type' => Location::TYPE_DISTRICT,
                'parent_code' => $provinceCode
            ], ['label' => $line['district'],]);
            $this->info('inserted district ' . $district->label);
        });
    }
}
