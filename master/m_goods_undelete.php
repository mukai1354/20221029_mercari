<?php
require_once('../common/function.php');
require_once('../db/DB.php');

get_header();

$result_deleted_goods = array();
try {
    $db = DB::getDB();
    $sql = " SELECT owner_id, goods_id, color_size_id, goods_name, color, size, picture FROM m_goods";
    $sql .= " WHERE deleted_flag = true";
    $sql .= " ORDER BY owner_id ASC, goods_id ASC, color_size_id ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $result_deleted_goods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $db = null;
    $stmt = null;
} catch (PDOException $e) {
    die('エラー：' . $e->getMessage());
}
?>
<div id="row justify-content-center">
    <div class="col-auto">
        <div id="main">
            <main role="main">
                <h1 class="mt-30px">商品マスタ復活</h1>
                <form action="m_goods_undelete_done.php" method="post">
                    <table class="table">
                        <caption>削除済み商品マスタ復活</caption>
                        <thead>
                            <tr class="shipment_record_table_title">
                                <th scope="col"></th>
                                <th scope="col">商品所有者コード</th>
                                <th scope="col">商品名コード</th>
                                <th scope="col">色サイズコード</th>
                                <th scope="col">商品名</th>
                                <th scope="col">色</th>
                                <th scope="col">サイズ</th>
                                <th scope="col">商品画像</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $row_index = 0;
                            foreach($result_deleted_goods as $deleted_goods):
                                $owner_id = $deleted_goods['owner_id'];
                                $goods_id = $deleted_goods['goods_id'];
                                $color_size_id = $deleted_goods['color_size_id'];
                                $goods_name = $deleted_goods['goods_name'];
                                $color = $deleted_goods['color'];
                                $size = $deleted_goods['size'];
                                if(is_null($deleted_goods['picture'])){
                                    $img_tag_for_picture = '';
                                }else{
                                    $picture_file_name = $deleted_goods['picture'];
                                    $img_src = '../common/images/' . $owner_id . '/goods/' . $picture_file_name;
                                    $img_tag_for_picture = '<img src="' . $img_src . '" class="table-td-img-product-image">';
                                }
                            ?>
                            <tr class="tr-even">
                                <td><input class="checkbox" type="checkbox" name="checkbox_index[]" value="<?php echo $row_index;?>"></td>
                                <td><?php echo $owner_id;?></td>
                                <td><?php echo $goods_id;?></td>
                                <td><?php echo $color_size_id;?></td>
                                <td><?php echo $goods_name;?></td>
                                <td><?php echo $color;?></td>
                                <td><?php echo $size;?></td>
                                <td><?php echo $img_tag_for_picture;?></td>
                                <input type="hidden" name="owner_id[]" value="<?php echo $owner_id;?>">
                                <input type="hidden" name="goods_id[]" value="<?php echo $goods_id;?>">
                                <input type="hidden" name="color_size_id[]" value="<?php echo $color_size_id;?>">
                            </tr>
                            <?php $row_index++; endforeach;?>
                        </tbody>
                    </table>
                    <input type="submit" id="undelete" value="復活">
                </form>
            </main>
        </div>
        <!-- /#main -->
        <div id="sub"></div>
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