<?php
require_once('../common/function.php');
require_once('../db/DB.php');

get_header();

// エラー情報リセット
$_SESSION["error_status"] = E_STATUS_DEFAULT;

$posted_owner_id = '';
if (isset($_POST['owner_id'])) {
    $posted_owner_id = $_POST['owner_id'];
}

$input_goods_name = "";
if (isset($_POST['input_goods_name'])) {
    $input_goods_name = $_POST['input_goods_name'];
}

$bind_goods_name = "";
$result_m_goods_having_max_color_size_id_in_each_goods_id = array();
if ($input_goods_name !== "") {
    $bind_goods_name = '%' . $input_goods_name . '%';
} else {
    $bind_goods_name = '';
}

try {
    $db = DB::getDB();
    // 各商品名コードの中で、最大の色サイズコードを持つSKUのレコードを取り出す
    $sql = " SELECT A.goods_id, A.color_size_id, A.goods_name, A.color, A.size, A.picture";
    $sql .= " FROM m_goods AS A";
    $sql .= " INNER JOIN (SELECT owner_id, goods_id, MAX(color_size_id) AS max_color_size_id FROM m_goods GROUP BY owner_id, goods_id) AS B";
    $sql .= " ON A.owner_id = B.owner_id";
    $sql .= " AND A.goods_id = B.goods_id";
    $sql .= " AND A.color_size_id = B.max_color_size_id";
    $sql .= " WHERE A.owner_id = :owner_id";
    $sql .= " AND A.deleted_flag = FALSE";
    $sql .= " AND A.goods_name collate utf8_unicode_ci LIKE :goods_name"; // 全角半角を区別しないようにする
    $sql .= " ORDER BY A.goods_id ASC";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':owner_id', $posted_owner_id, PDO::PARAM_STR);
    $stmt->bindParam(':goods_name', $bind_goods_name, PDO::PARAM_STR);
    $stmt->execute();
    $result_m_goods_having_max_color_size_id_in_each_goods_id = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $db = null;
    $stmt = null;
} catch (PDOException $e) {
    die('エラー：' . $e->getMessage());
}
?>
<!-- 以下SP -->
<main>
    <div class="d-lg-none">
        <div class="row">
            <div class="col-12">
                <h1 class="page_title">既存商品検索</h1>
                <form action="m_goods_modal.php" method="post" class="mb-5">
                    <input type="hidden" name="owner_id" value="<?php echo $posted_owner_id;?>">
                    <div class="d-flex mb-3 justify-content-around">
                        <label>商品名:&ensp;<input type="text" name="input_goods_name" id="input_goods_name_sp" class="m-1" value="<?php echo $input_goods_name?>"></label>
                    </div>
                    <div class="row">
                        <div class="col">
                            <input class="button" type="submit" value="表示">
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="row mb-5">
            <div class="col-12">
                <?php
            foreach ($result_m_goods_having_max_color_size_id_in_each_goods_id as $data_row):
                $owner_id = $posted_owner_id;
                $goods_id = $data_row['goods_id'];
                $color_size_id = $data_row['color_size_id'];
                $goods_name = $data_row['goods_name'];
                $color = $data_row['color'];
                $size = $data_row['size'];
                if (is_null($data_row['picture'])) {
                    $img_tag_for_picture = '<img src="../common/images/no_image.jpg" class="goods_img">';
                } else {
                    $picture_file_name = $data_row['picture'];
                    $img_src = '../common/images/' . $owner_id . '/goods/' . $picture_file_name;
                    $img_tag_for_picture = '<img src="' . $img_src . '" class="goods_img">';
                }
                ?>
                <table class="m-goods-modal-table-sp table">
                    <tbody>
                        <tr class="sp_column_name">
                            <th colspan="2">商品名</th>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <?php echo $goods_name; ?>
                            </td>
                        </tr>
                        <tr class="sp_column_name">
                            <th class="table_cell_half">色</th>
                            <th class="table_cell_half">サイズ</th>
                        </tr>
                        <tr>
                            <td class="table_cell_half">
                                <?php echo $color; ?>
                            </td>
                            <td class="table_cell_half">
                                <?php echo $size; ?>
                            </td>
                        </tr>
                        <tr class="sp_column_name">
                            <th colspan="2">商品画像</th>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <div class="goods_img_wrapper">
                                    <?php echo $img_tag_for_picture; ?>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <button class="button copy_button" type="button" name="copy_button" value="copy_button">この商品をコピーする</button>
                            </td>
                        </tr>
                    </tbody>
                    <input type="hidden" class="goods_id" value="<?php echo $goods_id;?>">
                    <input type="hidden" class="color_size_id" value="<?php echo $color_size_id;?>">
                    <input type="hidden" class="goods_name" value="<?php echo $goods_name;?>">
                </table>
                <?php endforeach; ?>
                <button id="close_sp" class="button" type="button">閉じる</button>
            </div>
        </div>
    </div>
    <!-- 以上SP -->
    <!-- 以下PC -->
    <div class="col-10 mx-auto d-none d-lg-block">
        <h1 class="mt-30px">既存商品検索</h1>
        <div class="mt-4 mb-5">
            <form class="d-flex justify-content-around" action="m_goods_modal.php" method="post">
                <div class="d-flex">
                    <input type="hidden" name="owner_id" value="<?php echo $posted_owner_id;?>">
                    <label class="mr-3">商品名:&ensp;<input type="text" name="input_goods_name" id="input_goods_name" value="<?php echo $input_goods_name?>"></label>
                    <input class="mt-0" type="submit" name="submit" value="表示">
                </div>
            </form>
        </div>
        <div class="row justify-content-center">
            <div id="col-auto">
            <?php if(!empty($_POST["submit"])): ?>
                <table class="table">
                    <thead class="shipment_record_table_title">
                        <tr>
                            <th>商品名</th>
                            <th>色</th>
                            <th>サイズ</th>
                            <th>画像</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            foreach ($result_m_goods_having_max_color_size_id_in_each_goods_id as $data_row):
                            $owner_id = $posted_owner_id;
                            $goods_id = $data_row['goods_id'];
                            $color_size_id = $data_row['color_size_id'];
                            $goods_name = $data_row['goods_name'];
                            $color = $data_row['color'];
                            $size = $data_row['size'];
                            if (is_null($data_row['picture'])) {
                                $img_tag_for_picture = '';
                            } else {
                                $picture_file_name = $data_row['picture'];
                                $img_src = '../common/images/' . $owner_id . '/goods/' . $picture_file_name;
                                $img_tag_for_picture = '<img src="' . $img_src . '" class="table-td-img-product-image">';
                            }
                        ?>

                        <tr class="m-goods-modal-table-tbody-tr">
                            <td>
                                <?php echo $goods_name;?>
                            </td>
                            <td>
                                <?php echo $color;?>
                            </td>
                            <td>
                                <?php echo $size;?>
                            </td>
                            <td>
                                <?php echo $img_tag_for_picture;?>
                            </td>
                            <input type="hidden" class="goods_id" value="<?php echo $goods_id;?>">
                            <input type="hidden" class="color_size_id" value="<?php echo $color_size_id;?>">
                            <input type="hidden" class="goods_name" value="<?php echo $goods_name;?>">
                        </tr>
                        <?php endforeach;?>
                    </tbody>
                </table>
                <?php endif; ?>
                <button type="button" id="close">閉じる</button>
            </div>
        </div>
    </div>
    <!-- 以上PC -->
</main>
<script type="text/javascript" src="../common/js/common.js"></script>
<script>
$(document).ready(function() {
    if (!window.opener || window.opener.closed) {
        return;
    }
    $('#close').click(function() {
        window.close();
    });

    $('#close_sp').click(function() {
        window.close();
    });

    //PC
    $('.m-goods-modal-table-tbody-tr').click(function() {
        let inserted_goods_id = $(this).children('input.goods_id').val();
        let inserted_color_size_id = $(this).children('input.color_size_id').val();
        let inserted_goods_name = $(this).children('input.goods_name').val();
        window.opener.$('#inserted_goods_id_by_modal').val(inserted_goods_id);
        window.opener.$('#inserted_color_size_id_by_modal').val(inserted_color_size_id);
        window.opener.$('#inserted_goods_name_by_modal').val(inserted_goods_name);
        window.opener.$('#inserted_goods_name_by_modal').change();
        window.close();
        return false;
    });

    //SP
    $('.copy_button').click(function() {
        let inserted_goods_id = $(this).parents('.m-goods-modal-table-sp').find('input.goods_id').val();
        let inserted_color_size_id = $(this).parents('.m-goods-modal-table-sp').find('input.color_size_id').val();
        let inserted_goods_name = $(this).parents('.m-goods-modal-table-sp').find('input.goods_name').val();
        window.opener.$('#inserted_goods_id_by_modal_sp').val(inserted_goods_id);
        window.opener.$('#inserted_color_size_id_by_modal_sp').val(inserted_color_size_id);
        window.opener.$('#inserted_goods_name_by_modal_sp').val(inserted_goods_name);
        window.opener.$('#inserted_goods_name_by_modal_sp').change();
        window.close();
        return false;
    });
});
</script>
<?php
get_footer();