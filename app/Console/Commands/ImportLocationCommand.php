<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Location\Models\Location;
use Rap2hpoutre\FastExcel\FastExcel;

class ImportLocationCommand extends Command
{
    protected $signature = 'import-location';

    protected $description = 'import location';

    public function handle()
    {

        $filePath = storage_path('app/location-Indonesia.csv');
        $idKey = '55449';
        $typeKey = 'province';
        $labelKey = 'Kalimantan Selatan';
        $parent = '54888';


        /*
               $filePath = storage_path('app/location-Philippines.csv');
               $idKey = '2538';
               $typeKey = 'province';
               $labelKey = 'MISAMIS-OCCIDENTAL';
               $parent = '2484';

               /*
               $filePath = storage_path('app/location-Thailand.csv');
               $idKey = '49181';
               $typeKey = 'province';
               $labelKey = 'Bangkok';
               $parent = '49179';*/

        Location::create([
            'code' => $idKey,
            'type' => trim(strtoupper($typeKey)),
            'label' => $labelKey,
            'parent_code' => $parent,
            'active' => true,
        ]);


        /*
        $filePath = storage_path('app/location-Vietnam.csv');
        $idKey = 'id';
        $typeKey = 'type';
        $labelKey = 'name';
        $parent = 'parent';
        */

        (new FastExcel())->import($filePath, function ($row) use ($idKey, $typeKey, $labelKey, $parent) {
            $id = $row["$idKey"];
            $type = $row["$typeKey"];
            $label = $row["$labelKey"];
            $parent_code = $row["$parent"];

            if(!empty($id)) {
                $type = trim(strtoupper($type));
                if($type == 'CITY') {
                    $type = Location::TYPE_DISTRICT;
                } else if($type == Location::TYPE_DISTRICT) {
                    $type = Location::TYPE_WARD;
                }

                Location::create([
                    'code' => trim($id),
                    'type' => $type,
                    'label' => trim($label),
                    'parent_code' => trim($parent_code),
                    'active' => true,
                ]);
            }
        });

        $res = null;

        print_r([$res]);
    }
}