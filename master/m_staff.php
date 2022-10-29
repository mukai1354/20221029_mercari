<?php
require_once ('../common/function.php');

get_header();

// エラー情報リセット
$_SESSION["error_status"] = E_STATUS_DEFAULT;

?>
<div id="row justify-content-center">
    <div class="col-auto">
        <div id="main">
            <main role="main">
                <h1 class="my-5">商品所有者情報登録</h1>
                <div class="mx-auto w-50">
                    <form action="m_staff_check.php" method="post">
                        <table class="table">
                            <tr>
                                <th class="align-middle px-2 shipment_record_table_title"><label for="owner_id">商品所有者コード(4桁の半角小文字英数字)</label></th>
                                <td class="align-middle px-2"><input type="text" name="owner_id" id="owner_id"></td>
                            </tr>
                            <tr>
                                <th class="align-middle px-2 shipment_record_table_title"><label for="owner">商品所有者</label></th>
                                <td class="align-middle px-2"><input type="text" name="owner" id="owner"></td>
                            </tr>
                            <tr>
                                <th class="align-middle px-2 shipment_record_table_title"><label for="zip_code">郵便番号</label></th>
                                <td class="align-middle px-2"><input type="text" name="zip_code" id="zip_code"></td>
                            </tr>
                            <tr>
                                <th class="align-middle px-2 shipment_record_table_title"><label for="address_1">住所1(番地まで)</label></th>
                                <td class="align-middle px-2"><input type="text" name="address_1" id="address_1"></td>
                            </tr>
                            <tr>
                                <th class="align-middle px-2 shipment_record_table_title"><label for="address_2">住所2(建物名、部屋番号など)</label></th>
                                <td class="align-middle px-2"><input type="text" name="address_2" id="address_2"></td>
                            </tr>
                            <tr>
                                <th class="align-middle px-2 shipment_record_table_title"><label for="tel">電話番号(ハイフンなし)</label></th>
                                <td class="align-middle px-2"><input type="text" name="tel" id="tel"></td>
                            </tr>
                            <tr>
                                <th class="align-middle px-2 shipment_record_table_title"><label for="fax">FAX番号(ハイフンなし)</label></th>
                                <td class="align-middle px-2"><input type="text" name="fax" id="fax"></td>
                            </tr>
                            <tr>
                                <th class="align-middle px-2 shipment_record_table_title"><label for="email">メールアドレス</label></th>
                                <td class="align-middle px-2"><input type="text" name="email" id="email"></td>
                            </tr>
                        </table>
                        <input type="submit" name="">
                    </form>
                </div>
            </main>
        </div>
        <!-- /#main -->
        <div id="sub">
        </div>
        <!-- /#sub -->
    </div>
    <!-- /.inner-->
</div>
<!-- /#contents -->
<?php

get_footer();