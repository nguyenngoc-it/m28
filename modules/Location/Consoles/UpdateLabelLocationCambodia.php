<?php

namespace Modules\Location\Consoles;

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Illuminate\Console\Command;
use Modules\Location\Models\Location;
use Rap2hpoutre\FastExcel\FastExcel;

class UpdateLabelLocationCambodia extends Command
{
    protected $signature = 'location:update_label_location_cambodia';
    protected $description = 'import cambodia locations';

    protected $provinces = [];
    protected $districts = [];

    /**
     *
     * @param $label
     * @return Location
     */
    protected function getProvince($label)
    {
        if(!isset($this->provinces[$label])) {
            $this->provinces[$label] = Location::query()->where('label', $label)
                ->where('parent_code', Location::COUNTRY_CAMBODIA)
                ->where('type', Location::TYPE_PROVINCE)->first();
        }
        return $this->provinces[$label];
    }

    /**
     * @param $label
     * @param $parentCode
     * @return Location|null
     */
    protected function getDistrict($label, $parentCode)
    {
        return  Location::query()->where('label', $label)
            ->where('parent_code', $parentCode)
            ->where('type', Location::TYPE_DISTRICT)->first();
    }

    /**
     * @throws IOException
     * @throws UnsupportedTypeException
     * @throws ReaderNotOpenedException
     */
    public function handle()
    {
        $provinceUpdated = [];
        (new FastExcel)->import(storage_path('location_cambodia_update_label.xlsx'), function ($line) use (&$provinceUpdated) {

            $province = trim($line['province']);
            $district = trim($line['district']);

            /*
            $new = trim($line['new']);
            if($new == 'ok') {
                $districtCode = 'CAM' . rand(9999, 99999);
                $provinceLocation = Location::query()->where('label', $province)->where('type',Location::TYPE_PROVINCE)->first();
                if($provinceLocation instanceof Location) {
                    $district = Location::updateOrCreate([
                        'code' => $districtCode,
                        'type' => Location::TYPE_DISTRICT,
                        'parent_code' => $provinceLocation->code
                    ], ['label' => $district,]);
                }
            }*/

            $provinceEn = trim(str_replace('Province', '', $line['province_update']));
            $provinceEn = trim(str_replace('province', '', trim($provinceEn)));
            $provinceEn = trim(str_replace('municipality', '', trim($provinceEn)));
            $provinceEn = trim(str_replace('Municipality', '', trim($provinceEn)));

            $provinceLocation = $this->getProvince($province);
            if(
                !empty($line['province_update']) && !isset($provinceUpdated[$province])
            ) {
                if($provinceLocation) {
                    $provinceLocation->update(['label' => $provinceEn]);
                }

                $this->info('update province '.$line['province']. ' -> '.$provinceEn);
                $provinceUpdated[$province] = true;
            }

            if(
                $provinceLocation instanceof Location &&
                !empty($line['district_update'])
            ) {
                $districtEn = trim(str_replace('District', '', trim($line['district_update'])));
                $districtEn = trim(str_replace('district', '', $districtEn));
                $districtEn = trim(str_replace('municipality', '', $districtEn));
                $districtEn = trim(str_replace('Municipality', '', $districtEn));
                $districtEn = trim(str_replace('Distric', '', $districtEn));
                $districtEn = trim(str_replace('Distrisct', '', $districtEn));
                $districtEn = trim(str_replace('Disdtrict', '', $districtEn));

                $districtLocation = $this->getDistrict($district, $provinceLocation->code);

                if($districtLocation instanceof Location) {
                    $districtLocation->update(['label' => $districtEn]);
                }

                $this->info('update district '.$line['district']. ' -> '.$line['district_update']);
            }
        });
    }

}
