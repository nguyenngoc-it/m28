<?xml version="1.0" encoding="UTF-8" ?>
<Request>
    <Product>
        <ItemId>{{$product->product_id_origin}}</ItemId>
        <Skus>
            @foreach($skus as $sku)
            <Sku>
                <SkuId>{{$sku->sku_id_origin}}</SkuId>
                <SellerSku>{{$sku->code}}</SellerSku>
                <quantity>{{$sku->getQuantitySku($type)}}</quantity>
            </Sku>
            @endforeach
        </Skus>
    </Product>
</Request>

