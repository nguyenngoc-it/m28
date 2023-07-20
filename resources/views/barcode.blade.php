<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <style>
            @page {
                margin-top: 30px;
                margin-bottom: 5px;
                margin-left: 0px;
                margin-right: 12mm;
                size: {{$data['page_size']}}mm 22mm portrait;
                position:relative;
            }
            *{ font-family: DejaVu Sans !important;}
            .row .colbar {
                display: block;
                float: left;
                padding: 0 0 0 5%;
                margin-right: 10px;
                margin-left: 10px;
            }
            .row>.colbar:last-child {
                margin-left: 10px;
            }
            .row>.colbar:first-child {
                {{$data['column'] == 3 ? 'margin-left: -20px;' : ''}}
            }
            .row .colbar .label {
                display: block;
                width: 100%;
                height: 15px;
                line-height: 18px;
                padding: 0px 0px 10px 0px;
                margin-bottom: 2px;
                text-align: center;
                word-wrap: break-word;
                overflow: hidden;
            }
            .row>.colbar>.label:first-child {
                padding: 0px 0px 10px 0px;
                margin-bottom: 10px;
            }
            .row .colbar .image {
                display: block;
                width: 100%;
            }
            .row .colbar .image img {
                height: 46px;
            }
            .row{ clear: both }
            .col-lg-1 {width:8%;  float:left;}
            .col-lg-2 {width:16%; float:left;}
            .col-lg-3 {width:25%; float:left;}
            .col-lg-4 {width:30%; float:left;}
            .col-lg-5 {width:42%; float:left;}
            .col-lg-6 {width:50%; float:left;}
            .col-lg-7 {width:58%; float:left;}
            .col-lg-8 {width:66%; float:left;}
            .col-lg-9 {width:75%; float:left;}
            .col-lg-10{width:83%; float:left;}
            .col-lg-11{width:92%; float:left;}
            .col-lg-12{width:100%; float:left;}
        </style>
    </head>
    <body>
        <div class="page" style="text-align:center;">
            @foreach ($data['data_skus_filtered']->chunk($data['column']) as $chunk)
                <div class="row">
                @foreach ($chunk as $sku)
                    @php
                        $code          = data_get($sku, 'code', null);
                        $name          = data_get($sku, 'name', null);
                        $sku_id        = data_get($sku, 'sku_id', null);
                        $label         = $code;
                        $labelFontSize = 7;
                    @endphp
                    @if ($sku_id)
                        @php
                            $label         = $sku_id . ' / ' . $code;
                            $code          = $sku_id;
                            $labelFontSize = 5;
                        @endphp
                    @endif
                    <div class="colbar" style="width: {{$data['width']}}%">
                        <div class="label" style="font-size: 7pt">{{\Illuminate\Support\Str::limit($name, 50, '...')}}</div>
                        <div class="image"><img style="margin: auto; width: 100%" src="data:image/png;base64,{{base64_encode($data['generator']->getBarcode($code, $data['generator']::TYPE_CODE_128, 1, 60))}}"></div>
                        <div class="label" style="font-size: {{$labelFontSize}}pt">{{\Illuminate\Support\Str::limit($label, 50, '...')}}</div>
                    </div>
                @endforeach
                </div>
            @endforeach
        </div>
    </body>
</html>