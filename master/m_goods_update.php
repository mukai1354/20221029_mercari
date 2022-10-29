<?php
// 2021/08/16 issue260 商品情報更新画面で、商品非選択時「更新」を押下するとエラーが出る。 demachi
require_once ('../common/function.php');
require_once ('../db/DB.php');

get_header();

// エラー情報リセット
$_SESSION["error_status"] = E_STATUS_DEFAULT;

$owner_id = h($_SESSION['login_owner_id']);
$goods_name = "";
$login_role = h($_SESSION['login_role']);
$goods = array();
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if(!empty($_POST['owner_id'])){
        $owner_id = h($_POST['owner_id']);
    }
    $goods_name = h($_POST['goods_name']);
    try {
        $sql = "SELECT * FROM m_goods
        WHERE owner_id = :owner_id
        AND deleted_flag = false";
        $db = DB::getDB();
        if ($goods_name != "") {
            $sql .= " AND goods_name LIKE :goods_name
            ORDER BY goods_id DESC, color_size_id DESC";
            $stmt = $db->prepare($sql);
            $bind_goods_name = '%' . $goods_name . '%';
            $stmt->bindParam(':goods_name', $bind_goods_name, PDO::PARAM_STR);
        } else {
            $sql .= " ORDER BY goods_id DESC, color_size_id DESC";
            $stmt = $db->prepare($sql);
        }
        $stmt->bindParam(':owner_id', $owner_id, PDO::PARAM_STR);
        $stmt->execute();
        $goods = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $db = NULL;
        $stmt = NULL;
    } catch (PDOException $e) {
        die('エラー：' . $e->getMessage());
    }
}

try {
    // データベース接続
    $db = DB::getDB();
    $stmt = $db->prepare("SELECT owner_id, owner FROM m_staff");
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $db = NULL;
    $stmt = NULL;
} catch (PDOException $e) {
    die('エラー：' . $e->getMessage());
}
$owner = '';
foreach ($result as $varr) {
    if ($varr['owner_id'] == $owner_id) {
        $owner .= "\t\t\t\t\t<option value=\"{$varr['owner_id']}\" selected>{$varr['owner']}</option>\n";
    } else {
        $owner .= "\t\t\t\t\t<option value=\"{$varr['owner_id']}\">{$varr['owner']}</option>\n";
    }
}

?>
<main role="main">
    <!-- 以下SP -->
    <div class="d-lg-none mb-5">
        <div class="row">
            <div class="col-12">
                <h1 class="page_title">商品情報更新</h1>
                <form action="m_goods_update.php" method="post">
                    <?php if($login_role == 1): ?>
                    <?php else: ?>
                    <div class="mb-4">
                        <p class="select_owner_text">商品所有者：</p>
                        <select class="table_input" name="owner_id">
                            <?php echo $owner; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="mb-4">
                        <p class="select_owner_text">商品名（あいまい検索）：</p>
                        <input id="goods_name" class="table_input" type="text" name="goods_name" value="<?php echo $goods_name; ?>">
                    </div>
                    <div class="mb-5">
                        <input class="button" type="submit" name="search" value="検索">
                    </div>
                </form>
                <?php if(!empty($_POST['search'])): ?>
                <h1 class="page_title">商品情報更新</h1>
                <form action="m_goods_update_check.php" method="post">
                    <?php $row_index = 0; ?>
                    <?php foreach ($goods as $item): ?>
                    <table class="table mb-5 sp_table_even">
                        <tbody>
                            <input type="hidden" name="owner_id[]" value="<?php echo $item['owner_id']; ?>">
                            <input type="hidden" name="goods_id[]" value="<?php echo $item['goods_id']; ?>">
                            <input type="hidden" name="color_size_id[]" value="<?php echo $item['color_size_id']; ?>">
                            <input type="hidden" name="goods_name[]" value="<?php echo $item['goods_name']; ?>">
                            <input type="hidden" name="color[]" value="<?php echo $item['color']; ?>">
                            <input type="hidden" name="size[]" value="<?php echo $item['size']; ?>">
                            <input type="hidden" name="picture[]" value="<?php echo $item['picture']; ?>">
                            <tr class="sp_column_name">
                                <th colspan="2">対象</th>
                            </tr>
                            <tr>
                                <td class="pb-3" colspan="2"><input class="checkbox" type="checkbox" name="checkbox_index[]" value="<?php echo $row_index; ?>"></td>
                            </tr>
                            <tr class="sp_column_name">
                                <th colspan="2">商品名</th>
                            </tr>
                            <tr>
                                <td colspan="2"><?php echo $item['goods_name']; ?></td>
                            </tr>
                            <tr class="sp_column_name">
                                <th class="table_cell_half">色</th>
                                <th class="table_cell_half">サイズ</th>
                            </tr>
                            <tr>
                                <td class="table_cell_half"><?php echo $item['color']; ?></td>
                                <td class="table_cell_half"><?php echo $item['size']; ?></td>
                            </tr>
                            <tr class="sp_column_name">
                                <th colspan="2">商品画像</th>
                            </tr>
                            <tr>
                                <td class="goods_img_wrapper" colspan="2">
                                    <div>
                                        <?php if(!empty($item['picture'])): ?>
                                        <img class="goods_img" src="../common/images/<?php echo $owner_id; ?>/goods/<?php echo $item['picture'];?>" alt="<?php echo $item['goods_name']; ?>">
                                        <?php else: ?>
                                        <img class="goods_img" src="../common/images/no_image.jpg" alt="no image">
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <?php $row_index ++; ?>
                    <?php endforeach; ?>
                    <div class="d-flex bottom_button w-100">
                    <div class="col-6">
                        <input type="submit" id="update_sp" class="update button" data-action="m_goods_update_check.php" value="更新">
                    </div>
                    <div class="col-6">
                        <input type="submit" id="delete_sp" class="update button" data-action="m_goods_delete_done.php" value="削除">
                    </div>
                </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- 以下PC -->
    <div id="row justify-content-center" class="col-10 mx-auto d-none d-lg-block">
        <div class="col-auto">
            <div id="main">
                <h1 class="mt-5">商品情報更新</h1>
                <form action="" method="post" enctype="multipart/form-data">
                    <div class="mb-5">
                        <?php if($login_role == 1): ?>
                        <?php else: ?>
                        <select name="owner_id">
                            <?php echo $owner?>
                        </select>
                        <?php endif; ?>
                        <label for="goods_name">商品名:<input type="text" name="goods_name" id="goods_name" value="<?php echo $goods_name?>"></label>
                        <!-- ---------------------------------issue260 start------------------------- -->
                        <input type="submit" name="search" value="検索">
                        <!-- ---------------------------------issue260 end------------------------- -->
                    </div>
                </form>
                <!-- ---------------------------------issue260 start------------------------- -->
                <?php if(!empty($_POST['search'])): ?>
                <!-- ---------------------------------issue260 end------------------------- -->
                <form action="m_goods_update_check.php" method="post">
                    <table class="table">
                        <thead class="shipment_record_table_title">
                            <tr">
                                <th scope="col"></th>
                                <th scope="col">商品名コード</th>
                                <th scope="col">色サイズコード</th>
                                <th scope="col">商品名</th>
                                <th scope="col">色</th>
                                <th scope="col">サイズ</th>
                                <th scope="col">商品画像</th>
                            </tr>
                            <?php
$item_html = "";
$row_index = 0;
foreach ($goods as $item) {
    $item_html .= "<tr class=\"tr-even\">\n
                    <td><input class=\"checkbox\" type=\"checkbox\" name=\"checkbox_index[]\" value=\"{$row_index}\"></td>\n
                    <td>{$item['goods_id']}</td>\n
                    <td>{$item['color_size_id']}</td>\n
                    <td>{$item['goods_name']}</td>\n
                    <td>{$item['color']}</td>\n
                    <td>{$item['size']}</td>\n
                    <td>";
    if ($item['picture'] != "") {
        $item_image_path = get_img_url('/' . $owner_id . '/goods/' . $item['picture']);
        $item_html .= "<div class=\"view_box\"><img src=\"" . $item_image_path . "\"></div>";
    }
    $item_html .= "</td><input type=\"hidden\" name=\"owner_id[]\" value=\"{$item['owner_id']}\">
                    <input type=\"hidden\" name=\"goods_id[]\" value=\"{$item['goods_id']}\">
                    <input type=\"hidden\" name=\"color_size_id[]\" value=\"{$item['color_size_id']}\">
                    <input type=\"hidden\" name=\"goods_name[]\" value=\"{$item['goods_name']}\">
                    <input type=\"hidden\" name=\"color[]\" value=\"{$item['color']}\">
                    <input type=\"hidden\" name=\"size[]\" value=\"{$item['size']}\">
                    <input type=\"hidden\" name=\"picture[]\" value=\"{$item['picture']}\">
                  </tr>";
    $row_index ++;
}
echo $item_html?>
                        </thead>
                    </table>
                    <input type="submit" id="update" class="update" data-action="m_goods_update_check.php" value="更新"> <input type="submit" id="delete" class="update" value="削除">
                </form>
                <!-- ---------------------------------issue260 start------------------------- -->
                <?php endif; ?>
                <!-- ---------------------------------issue260 end------------------------- -->
            </div>
            <!-- /#main -->
            <div id="sub"></div>
            <!-- /#sub -->
        </div>
        <!-- /.inner-->
    </div>
    <!-- /#contents -->
    <!-- 以上PC -->
</main>
<script>
$(document).ready(function() {
    $('input:checkbox').change(function() {
        cnt = $('input:checkbox:checked').length;
    }).trigger('change');

    $('input#update').on('click', function() {
        if (cnt == 0) {
            alert("商品を選択してください。");
            return false;
        } else if (cnt > 1) {
            alert("更新は一つずつ行ってください。");
            return false;
        } else {
            $(this).parents('form').attr('action', $(this).data('action'));
            $(this).parents('form').submit();
        }
    });

    $('input#delete').on('click', function() {
        if (cnt == 0) {
            alert("商品を選択してください。");
            return false;
        } else {
            $(this).parents('form').attr('action', $(this).data('action'));
            $(this).parents('form').submit();
        }
    });

    $('input#update_sp').on('click', function() {
        if (cnt == 0) {
            alert("商品を選択してください。");
            return false;
        } else if (cnt > 1) {
            alert("更新は一つずつ行ってください。");
            return false;
        } else {
            $(this).parents('form').attr('action', $(this).data('action'));
            $(this).parents('form').submit();
        }
    });

    $('input#delete_sp').on('click', function() {
        if (cnt == 0) {
            alert("商品を選択してください。");
            return false;
        } else {
          if(!confirm('警告!!\n商品を削除します。\nよろしいですか？')){
                console.log("no");
                return false;
          }else{
                $(this).parents('form').attr('action', $(this).data('action'));
                $(this).parents('form').submit();
          }
        }
    });
});
</script>
<?php

get_footer();