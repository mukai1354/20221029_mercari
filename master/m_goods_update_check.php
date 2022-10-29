<?php
require_once('../common/function.php');
require_once('../db/DB.php');

get_header();

// エラー情報リセット
$_SESSION["error_status"] = E_STATUS_DEFAULT;

// m_goods_update.phpからのPOSTを変数に代入
$row_index = h($_POST['checkbox_index'])[0];
$owner_id = getpstStrs('owner_id')[$row_index];
$goods_id = getpstStrs('goods_id')[$row_index];
$color_size_id = getpstStrs('color_size_id')[$row_index];
$goods_name = getpstStrs('goods_name')[$row_index];
$color = getpstStrs('color')[$row_index];
$size = getpstStrs('size')[$row_index];
$picture = getpstStrs('picture')[$row_index];

?>
<link href="../common/css/drag_and_drop.css" rel="stylesheet">
<script type="text/javascript" src="../common/js/drag_and_drop.js"></script>
<main role="main">
    <!-- 以下SP -->
    <div class="d-lg-none">
        <div class="row">
            <div class="col-12">
                <h1 class="page_title">商品マスタ</h1>
                <form action="m_goods_update_done.php" method="post" enctype="multipart/form-data">
                    <table class="table">
                        <input type="hidden" name="owner_id" value="<?php echo $owner_id?>">
                        <input type="hidden" name="goods_id" value="<?php echo $goods_id?>">
                        <input type="hidden" name="color_size_id" value="<?php echo $color_size_id; ?>">
                        <caption>商品情報更新</caption>
                        <tbody>
                            <tr class="sp_column_name">
                                <th colspan="2">商品名</th>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <input class="table_input" type="text" name="goods_name" id="goods_name" value="<?php echo $goods_name?>" required>
                                </td>
                            </tr>
                            <tr class="sp_column_name">
                                <th class="table_cell_half">色</th>
                                <th class="table_cell_half">サイズ</th>
                            </tr>
                            <tr>
                                <td class="table_cell_half">
                                    <input class="table_input" type="text" name="color" id="color" value="<?php echo $color?>">
                                </td>
                                <td class="table_cell_half">
                                    <input class="table_input" type="text" name="size" id="size" value="<?php echo $size?>">
                                </td>
                            </tr>
                            <tr class="sp_column_name">
                                <th colspan="2">商品画像</th>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <div class="user-icon-dnd-wrapper">
                                        <input type="file" name="picture" class="input_file" accept="image/*">
                                        <div class="preview_field">
                                            <img class="goods_img" src="<?php echo get_img_url('/' . $owner_id . '/goods/' . $picture)?>" alt="" class="img" width="200">
                                        </div>
                                        <div class="drop_area">drag and drop<br>or<br>click here.</div>
                                        <div class="icon_clear_button"></div>
                                        <input type="hidden" id="icon_clear" name="icon_clear" value="false">
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <input type="hidden" name="owner_id" value="<?php echo $owner_id?>">
                    <input type="hidden" name="saved_image" value="<?php echo $picture?>">
                    <button type="submit" id="submit-btn" class="button" name="action" value="seach">更新</button>
                </form>
            </div>
        </div>
    </div>
    <!-- 以上SP -->
    <!-- 以下PC -->
    <div id="row justify-content-center" class="d-none d-lg-block">
        <div class="col-auto">
            <div id="main">
                <h1>商品マスタ</h1>
                <form action="m_goods_update_done.php" method="post" enctype="multipart/form-data">
                    <table class="table">
                        <caption>商品情報更新</caption>
                        <tr>
                            <th scope="col">商品所有者コード</th>
                            <td><label>
                                    <?php echo $owner_id?></label></td>
                            <input type="hidden" name="owner_id" value="<?php echo $owner_id?>">
                        </tr>
                        <tr>
                            <th scope="col">商品名コード</th>
                            <td><label>
                                    <?php echo $goods_id?></label></td>
                            <input type="hidden" name="goods_id" value="<?php echo $goods_id?>">
                        </tr>
                        <tr>
                            <th scope="col">色サイズコード</th>
                            <td><label>
                                    <?php echo $color_size_id; ?></label></td>
                            <input type="hidden" name="color_size_id" value="<?php echo $color_size_id; ?>">
                        </tr>
                        <tr>
                            <th scope="col">商品名</th>
                            <td><input type="text" name="goods_name" id="goods_name" value="<?php echo $goods_name?>">
                            </td>
                        </tr>
                        <tr>
                            <th scope="col">色</th>
                            <td><input type="text" name="color" id="color" value="<?php echo $color?>"></td>
                        </tr>
                        <tr>
                            <th scope="col">サイズ</th>
                            </td>
                            <td><input type="text" name="size" id="size" value="<?php echo $size?>">
                        </tr>
                        <tr>
                            <th scope="col">商品画像</th>
                            <td>
                                <div class="user-icon-dnd-wrapper">
                                    <input type="file" name="picture" class="input_file" accept="image/*">
                                    <div class="preview_field"><img src="<?php echo get_img_url('/' . $owner_id . '/goods/' . $picture)?>" alt="" class="img" width="200"></div>
                                    <div class="drop_area">drag and drop<br>or<br>click here.</div><br>
                                    <div class="icon_clear_button">X</div>
                                    <input type="hidden" id="icon_clear" name="icon_clear" value="false">
                                </div>
                            </td>
                        </tr>
                    </table>
                    <input type="hidden" name="owner_id" value="<?php echo $owner_id?>">
                    <input type="hidden" name="saved_image" value="<?php echo $picture?>">
                    <button type="submit" id="submit-btn" name="action" value="seach">更新</button>
                </form>
            </div>
            <!-- /#main -->
            <div id="sub">
            </div>
            <!-- /#sub -->
        </div>
        <!-- /.inner-->
    </div>
    <!-- /#contents -->
    <!-- 以上PC -->
</main>
<script>
$(document).ready(function() {
    $('div.icon_clear_button').on('click', function() {
        $("#icon_clear").val("true");
    });
});
</script>
<?php

get_footer();