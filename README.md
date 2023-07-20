## Setup new tenant

* Tenant code
* M10 app
* List shipping partners (name, code)

## Setup Topship shipping partner

Thêm record vào table shipping_partners với thông tin như sau:

* provider: `topship`
* settings: `{"carrier":"vnpost","shipping_name":"Chuẩn","token":"XXX"}`
    * `token` (required): Api key
    * `carrier` (required): Mã đơn vị vận chuyển (ghn, ghtk, vtpost, etop, partner, ninjavan, dhl, ntx, vnpost)
    * `shipping_name` (optional): Tên gói vận chuyển (thường là `Nhanh` hoặc `Chuẩn`). Lấy theo thông tin ShippingService bên Topship, nếu không khai báo thì sẽ lấy theo ShippingService đầu tiên trả về từ api [GetShippingServices](https://api.sandbox.etop.vn/doc/ext/partner#operation/partner.Shipping-GetShippingServices))

Danh sách shipping_name do bên Topship cung cấp

| Tên gói vận chuyển | Đối tác vận chuyển |
| --- | --- |
| Hàng nặng | TS.GHN.KCN |
| Chuẩn | TS.NJV.CHUAN |
| Chuẩn | TS.GHTK.CHUAN |
| Chuẩn | TS.GHN.CHUAN |
| Nặng | TS.GHN.NANG |
| Chuẩn | TS.JNT |
| NTX - Nặng chuẩn | TS.NTX.NANG.CHUAN |
| Chuẩn | TS.GHN.NT.CHUAN |
| NTX - Chuẩn | TS.NTX.CHUAN |
| Nhanh | TS.VTP.NHANH |
| Chuẩn | TS.VNP.CHUAN |
