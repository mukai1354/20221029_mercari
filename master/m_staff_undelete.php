<?php
require_once('../common/function.php');
require_once('../db/DB.php');

get_header();

$result_deleted_staff = array();
try {
    // データベース接続
    $db = DB::getDB();
    $sql = " SELECT owner_id, owner, zip_code, address_1, address_2, tel, fax, email FROM m_staff";
    $sql .= " WHERE deleted_flag = true";
    $sql .= " ORDER BY owner_id ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $result_deleted_staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $db = null;
    $stmt = null;
} catch (PDOException $e) {
    die('エラー：' . $e->getMessage());
}
?>
<div id="contents">
    <div class="inner">
        <div id="main">
            <main role="main">
                <h1 class="my-5">商品所有者情報復活</h1>
                <form action="m_staff_undelete_done.php" method="post">
                    <table class="table">
                        <thead>
                            <tr class="shipment_record_table_title">
                                <th scope="col"></th>
                                <th scope="col">商品所有者コード</th>
                                <th scope="col">商品所有者</th>
                                <th scope="col">郵便番号</th>
                                <th scope="col">住所1</th>
                                <th scope="col">住所2</th>
                                <th scope="col">電話番号</th>
                                <th scope="col">FAX番号</th>
                                <th scope="col">メルアド</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $row_index = 0;
                            foreach($result_deleted_staff as $deleted_staff):
                                $owner_id = $deleted_staff['owner_id'];
                                $owner = $deleted_staff['owner'];
                                $zip_code = $deleted_staff['zip_code'];
                                $address_1 = $deleted_staff['address_1'];
                                $address_2 = $deleted_staff['address_2'];
                                $tel = $deleted_staff['tel'];
                                $fax = $deleted_staff['fax'];
                                $email = $deleted_staff['email'];
                            ?>
                            <tr class="tr_even">
                                <td><input class="checkbox" type="checkbox" name="checkbox_index[]"
                                        value="<?php echo $row_index;?>"></td>
                                <td><?php echo $owner_id;?></td>
                                <td><?php echo $owner;?></td>
                                <td><?php echo $zip_code;?></td>
                                <td><?php echo $address_1;?></td>
                                <td><?php echo $address_2;?></td>
                                <td><?php echo $tel;?></td>
                                <td><?php echo $fax;?></td>
                                <td><?php echo $email;?></td>
                                <input type="hidden" name="owner_id[]" value="<?php echo $owner_id;?>">
                            </tr>
                            <?php $row_index++; endforeach;?>
                        </tbody>
                    </table>
                    <input type="submit" id="undelete" value="復活">
                </form>
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
<script>
    $(document).ready(function () {
        $("#undelete").prop("disabled", true);

        $("input[type='checkbox']").on('change', function () {
            // チェックされているチェックボックスの数
            if ($(".checkbox:checked").length > 0) {
                // ボタン有効
                $("#undelete").prop("disabled", false);
            } else {
                // ボタン無効
                $("#undelete").prop("disabled", true);
            }
        });
    });
</script>
<?php

get_footer();