<?php
require_once('../common/function.php');
require_once('../db/DB.php');

get_header();

// 一日加算した日付
date_default_timezone_set('Asia/Tokyo');
$day = date("Y-m-d", strtotime("1 day"));

$owner_id = h($_SESSION['login_owner_id']);
$goods_name = "";
$operation = "";
$selected_insert = "";
$selected_update = "";
$stock_schedule_day = $day;
$regist_stock_schedule_day = "";
$submit = "";
$row_goods_id = "";
$row_color_size_id = "";
$row_goods_name = "";
$row_color = "";
$row_size ="";
$row_stock_schedule_day = "";
$row_stock_schedule_volume = "";
$login_role = $_SESSION['login_role'];


if(isset($_POST['submit'])) {
    $submit = $_POST['submit'];
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $owner_id = getpstStrs("owner_id");
}

try {
    $db = DB::getDB();
    $stmt = $db->prepare("SELECT owner_id, owner FROM m_staff");
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $owner = '';
    foreach ($result as $varr) {
        $owner .= "\t\t\t\t\t<option value=\"" . $varr['owner_id'] . "\"";
        if ($varr['owner_id'] == $owner_id) {
            $owner .= " selected";
        }
        $owner .= ">" . $varr['owner'] . "</option>\n";
    }
    $db = null;
    $stmt = null;
} catch (PDOException $e) {
    die('エラー：' . $e->getMessage());
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $goods_name = getpstStrs("goods_name");
    $operation = getpstStrs("operation");
    $stock_schedule_day = getpstStrs("stock_schedule_day");
    $regist_stock_schedule_day = getpstStrs("regist_stock_schedule_day");
    $db = null;
    $sql = "";
    $stmt = null;
    $goods_result = null;
    if ($operation == "insert") {
        $selected_insert = " selected";
        try {
            $db = DB::getDB();
            $sql = "SELECT g.goods_id, g.color_size_id, g.goods_name, g.color, g.picture, g.size, '' as stock_schedule_volume, '' as stock_schedule_day FROM m_goods g";
            $sql .= " WHERE g.owner_id = :owner_id AND g.goods_name LIKE :goods_name";
            $sql .= " AND g.deleted_flag = FALSE";
            //「入荷予定」TBLに、「登録済み入荷予定日」で入力された年月日で、登録されていない商品を選択
            $sql .= " AND NOT EXISTS (SELECT * FROM stock_schedule st
                    WHERE g.owner_id = st.owner_id
                    AND g.goods_id = st.goods_id
                    AND g.color_size_id = st.color_size_id
                    AND st.stock_schedule_day = :stock_schedule_day)";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':stock_schedule_day', $stock_schedule_day, PDO::PARAM_STR);
        } catch (PDOException $e) {
            die('エラー：' . $e->getMessage());
        }
    } elseif ($operation == "update") {
        $selected_update = " selected";
        try {
            $db = DB::getDB();
            // 「入荷予定」TBLから、「商品名（あいまい検索）」と「登録済み入荷予定日」を条件にレコードを選択
            $sql = "SELECT g.goods_id, g.color_size_id, g.goods_name, g.color, g.size, g.picture, st.stock_schedule_volume, st.stock_schedule_day FROM m_goods g";
            $sql .= " INNER JOIN stock_schedule st ON g.owner_id = st.owner_id AND g.goods_id = st.goods_id AND g.color_size_id = st.color_size_id";
            $sql .= " WHERE g.owner_id = :owner_id AND g.goods_name LIKE :goods_name";
            $sql .= " AND g.deleted_flag = FALSE";
            if ($stock_schedule_day != "") {
                $sql .= " AND st.stock_schedule_day = :stock_schedule_day";
                $stmt = $db->prepare($sql);
                $stmt->bindParam(':stock_schedule_day', $stock_schedule_day, PDO::PARAM_STR);
            } else {
                $stmt = $db->prepare($sql);
            }
        } catch (PDOException $e) {
            die('エラー：' . $e->getMessage());
        }
    }
    $bind_goods_name = '%' . $goods_name . '%';
    $stmt->bindParam(':owner_id', $owner_id, PDO::PARAM_STR);
    $stmt->bindParam(':goods_name', $bind_goods_name, PDO::PARAM_STR);
    $stmt->execute();
    $goods_result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $db = null;
    $stmt = null;
}
?>
<main role="main">
    <!-- 以下SP -->
    <div class="d-lg-none mb-5">
        <form id="form_sp" action="./scheduled_arrival_registration_done.php" method="post">
            <div class="row mb-5">
                <div class="col-10 mx-auto">
                    <h1 class="page_title mb-4">入荷予定登録</h1>
                    <!-- 商品販売者の場合自分の商品だけをいじれるように設定 -->
                    <?php if($login_role == 1): ?>
                    <input type="hidden" name="owner_id" value="<?php echo $_SESSION['login_owner_id']; ?>">
                    <?php else: ?>
                    <div class="mb-4">
                        <p class="select_owner_text">商品所有者：</p>
                        <select class="table_input" name="owner_id">
                            <?php echo $owner;?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="mb-4">
                        <p class="select_owner_text">商品名（あいまい検索）：</p>
                        <input class="table_input" type="text" name="goods_name" id="goods_name" value="<?php echo $goods_name?>">
                    </div>
                    <div class="mb-4">
                        <p class="select_owner_text">操作内容：</p>
                        <select class="table_input" name="operation">
                            <option value="insert" <?php echo $selected_insert; ?>>新規登録</option>
                            <option value="update" <?php echo $selected_update; ?>>変更</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <p class="select_owner_text">登録済み入荷予定日：</p>
                        <input id="stock_schedule_day" class="table_input" type="date" name="stock_schedule_day" value="<?php echo $stock_schedule_day?>">
                    </div>
                    <div class="mb-5">
                        <input id="submit_sp" class="submit_sp button" type="submit" name="submit" data-action="./scheduled_arrival_registration.php" value="商品検索">
                    </div>
                </div>
            </div>
            <?php if(isset($submit)): ?>
            <?php if ($_SERVER['REQUEST_METHOD'] == "POST") :?>
            <div class="row mb-5">
                <div class="col-12">
                    <h1 class="page_title mb-4">入荷情報登録</h1>
                    <?php for ($i = 1; $i <= count($goods_result); $i ++) : $index = $i - 1; $data_row = $goods_result[$index];?>
                    <?php
                    if(!empty($data_row["goods_id"])) {
                        $row_goods_id = $data_row["goods_id"];
                    } else {
                        $row_goods_id = "no data";
                    }

                    if(!empty($data_row["color_size_id"])) {
                        $row_color_size_id = $data_row["color_size_id"];
                    } else {
                        $row_color_size_id = "no data";
                    }

                    if(!empty($data_row["goods_name"])) {
                        $row_goods_name = $data_row["goods_name"];
                    } else {
                        $row_goods_name = "no data";
                    }

                    if(!empty($data_row["color"])) {
                        $row_color = $data_row["color"];
                    } else {
                        $row_color = "no data";
                    }

                    if(!empty($data_row["size"])) {
                        $row_size = $data_row["size"];
                    } else {
                        $row_size = "no data";
                    }

                    if(!empty($data_row["stock_schedule_day"])) {
                        $row_stock_schedule_day = $data_row["stock_schedule_day"];
                    } else {
                        $row_stock_schedule_day = "no data";
                    }

                    if(!empty($data_row["stock_schedule_volume"])) {
                        $row_stock_schedule_volume = (int)$data_row["stock_schedule_volume"];
                    } else {
                        $row_stock_schedule_volume = "";
                    }

                    if(!empty($data_row["picture"])) {
                        $row_picture = $data_row["picture"];
                    } else {
                        $row_picture = "";
                    }



                    ?>
                    <table class="table sp_table_even">
                        <caption class="caption">No.&ensp;
                            <?php echo $i; ?>
                        </caption>
                        <tbody>
                            <!-- <?php if ($operation === "insert") :?>
                            <tr class="sp_column_name">
                                <th colspan="2">リピート商品</th>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <button class="button modal_search_sp" type="button">検索</button>
                                </td>
                            </tr>
                            <?php endif;?>-->
                            <!-- <tr>
                                <th class="sp_column_name" colspan="2">商品名レコード</th>
                            </tr>
                            <tr>
                                <td colspan="2"> -->
                            <input type="hidden" class="goods_id" name="goods_id[]" value="<?php echo $row_goods_id;?>">
                            <!-- <?php echo $row_goods_id;?> -->
                            <!-- </td>
                            </tr>
                            <tr>
                                <th class="sp_column_name" colspan="2">色サイズレコード</th>
                            </tr>
                            <tr>
                                <td colspan="2"> -->
                            <input type="hidden" class="color_size_id" name="color_size_id[]" value="<?php echo $row_color_size_id;?>">
                            <!-- <?php echo $row_color_size_id;?>
                                </td>
                            </tr>-->
                            <tr>
                                <th class="sp_column_name" colspan="2">商品名</th>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <input type="hidden" class="goods_name" value="<?php echo $row_goods_name;?>">
                                    <?php echo $row_goods_name;?>
                                </td>
                            </tr>
                            <tr class="sp_column_name">
                                <th class="table_cell_half">色</th>
                                <th class="table_cell_half">サイズ</th>
                            </tr>
                            <tr>
                                <td class="table_cell_half">
                                    <input type="hidden" class="color" value="<?php echo $row_color;?>">
                                    <?php echo $row_color;?>
                                </td>
                                <td class="table_cell_half">
                                    <input type="hidden" class="size" value="<?php echo $row_size;?>">
                                    <?php echo $row_size;?>
                                </td>
                            </tr>
                            <tr class="sp_column_name">
                                <th colspan="2">商品画像</th>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <div class="goods_img_wrapper">
                                        <?php if(!empty($row_picture)): ?>
                                        <img class="goods_img" src="../common/images/<?php echo $owner_id; ?>/goods/<?php echo $row_picture; ?>" alt="<?php echo $row_goods_name; ?>">
                                        <?php else: ?>
                                        <img class="goods_img" alt="no_image" src="../common/images/no_image.jpg">
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <tr class="sp_column_name">
                                <th class="table_cell_half">入荷予定日</th>
                                <th class="table_cell_half">数量</th>
                            </tr>
                            <tr>
                                <td class="table_cell_half">
                                    <input type="hidden" class="schedule_day" name="schedule_day[]" value="<?php echo $row_stock_schedule_day;?>">
                                    <?php echo $row_stock_schedule_day;?>
                                </td>
                                <td class="table_cell_half">
                                    <input class="table_input" type="number" name="quantity[]" min="0" value="<?php echo $row_stock_schedule_volume;?>">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>
            <div class="row">
                <div class="col-12 mb-4 d-flex">
                    <p class="d-inline-block select_owner_text ml-auto">入荷予定日：&ensp;</p>
                    <input class="mr-auto" type="date" name="regist_stock_schedule_day" value="<?php echo $stock_schedule_day?>">
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <input type="submit" class="submit_sp button" value="予定登録">
                </div>
            </div>
        </form>
        <input type="hidden" id="radio_goods_by_modal_sp" value="">
    </div>
    <!-- 以上SP -->
    <!-- 以下PC -->
    <div id="contents" class="d-none d-lg-block">
        <div class="inner">
            <div id="main" class="mb-5">
                <h1 class="mt-5">入荷予定登録</h1>
                <form id="form_pc" action="./scheduled_arrival_registration_done.php" method="post">
                    <!-- 商品販売者の場合自分の商品だけをいじれるように設定 -->
                    <div class="d-flex justify-content-around">
                        <?php if($login_role ==1): ?>
                        <input type="hidden" name="owner_id" value="<?php echo $_SESSION['login_owner_id']; ?>">
                        <?php else: ?>
                        <label>商品所有者：&nbsp;
                            <select name="owner_id">
                                <?php echo $owner;?>
                            </select>
                        </label>
                        <?php endif; ?>
                        <label>商品名:&nbsp;<input type="text" name="goods_name" id="goods_name" value="<?php echo $goods_name?>"></label>
                        <label>操作内容:&nbsp;
                            <select name="operation">
                                <option value="insert" <?php echo $selected_insert?>>新規登録</option>
                                <option value="update" <?php echo $selected_update?>>変更</option>
                            </select>
                        </label>
                        <label>登録済み入荷予定日：&nbsp;<input type="date" name="stock_schedule_day" id="stock_schedule_day" value="<?php echo $stock_schedule_day?>"></label>
                        <input type="submit" class="submit_pc mt-0" data-action="./scheduled_arrival_registration.php" name="submit_pc" value="商品検索">
                    </div>

                    <?php if(!empty($_POST["submit_pc"])): ?>
                    <table class="table mb-5">
                        <caption>入荷情報登録</caption>
                        <thead class="shipment_record_table_title">
                            <tr>
                                <th class="w-5rem px-2 align-middle" scope="col">No</th>
                                <?php if ($operation === "insert") :?>
                                <th class="w-6rem px2" scope="col">リピート商品</th>
                                <?php endif;?>
                                <th class="align-middle w-6rem px2" scope="col">商品名コード</th>
                                <th class="align-middle w-10rem px2" scope="col">色サイズコード</th>
                                <th class="align-middle w-30rem px2" scope="col">商品名</th>
                                <th class="align-middle w-10rem px2" scope="col">色</th>
                                <th class="align-middle w-10rem px2" scope="col">サイズ</th>
                                <th class="align-middle w-10rem px2" scope="col">入荷予定日</th>
                                <th class="align-middle w-5rem px2" scope="col">数量</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($_SERVER['REQUEST_METHOD'] == "POST") :?>
                            <?php for ($i = 1; $i <= count($goods_result); $i ++) : $index = $i - 1; $data_row = $goods_result[$index];?>
                            <?php
                    if(isset($row_goods_id)) {
                        $row_goods_id = $data_row["goods_id"];
                    } else {
                        $row_goods_id = "no data";
                    }

                    if(isset($data_row["color_size_id"])) {
                        $row_color_size_id = $data_row["color_size_id"];
                    } else {
                        $row_color_size_id = "no data";
                    }

                    if(isset($data_row["goods_name"])) {
                        $row_goods_name = $data_row["goods_name"];
                    } else {
                        $row_goods_name = "no data";
                    }

                    if(isset($data_row["color"])) {
                        $row_color = $data_row["color"];
                    } else {
                        $row_color = "no data";
                    }

                    if(isset($data_row["size"])) {
                        $row_size = $data_row["size"];
                    } else {
                        $row_size = "no data";
                    }

                    if(isset($data_row["stock_schedule_day"])) {
                        $row_stock_schedule_day = $data_row["stock_schedule_day"];
                    } else {
                        $row_stock_schedule_day = "no data";
                    }

                    if(isset($data_row["stock_schedule_volume"]) && $data_row["stock_schedule_volume"] != 0) {
                        $row_stock_schedule_volume = (int)$data_row["stock_schedule_volume"];
                    } else {
                        $row_stock_schedule_volume = "no data";
                    }

                    ?>
                            <tr class="tr-even">
                                <td class="align-middle w-5rem px-2">
                                    <?php echo $i;?>
                                </td>
                                <?php if ($operation === "insert") :?>
                                <td class="align-middle w-6rem px2" scope="col"><button type="button" class="mt-0 modal_search_pc">検索</button></td>
                                <?php endif;?>
                                <td class="align-middle w-6rem px2"><input class="w-100 m-0" type="text" class="goods_id" name="goods_id[]" readonly="readonly" value="<?php echo $row_goods_id;?>"></td>
                                <td class="align-middle w-10rem px2"><input class="w-100 m-0" type="text" class="color_size_id" name="color_size_id[]" readonly="readonly" value="<?php echo $row_color_size_id?>"></td>
                                <td class="align-middle w-30rem px2"><input class="w-100 m-0" type="text" class="goods_name" readonly="readonly" value="<?php echo $row_goods_name;?>"></td>
                                <td class="align-middle w-10rem px2"><input class="w-100 m-0" type="text" class="color" readonly="readonly" value="<?php echo $row_color;?>"></td>
                                <td class="align-middle w-10rem px2"><input class="w-100 m-0" type="text" class="size" readonly="readonly" value="<?php echo $row_size;?>"></td>
                                <td class="align-middle w-10rem px2"><input class="w-100 m-0" type="text" class="schedule_day" name="schedule_day[]" readonly="readonly" value="<?php echo $row_stock_schedule_day;?>"></td>
                                <td class="align-middle w-5rem px2"><input class="w-100 m-0" class="quantity_pc" type="number" name="quantity[]" min="0" value="<?php echo $row_stock_schedule_volume;?>"></td>
                            </tr>
                            <?php endfor; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <label class="mb-5">入荷予定日</label><input type="date" name="regist_stock_schedule_day" value="<?php echo $stock_schedule_day?>">
                    <input id="submit_pc" type="submit" class="submit_pc" value="予定登録">
                    <?php endif; ?>
                </form>
                <input type="hidden" id="radio_goods_by_modal_pc" value="">
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
<?php
get_footer();
?>
<script>
const GOODS_SPLIT = ";;;";

$(document).ready(function() {
    var child;
    var $pushed_goods_id;
    var $pushed_color_size_id;
    var $pushed_goods_name;
    var $pushed_color;
    var $pushed_size;

    function winOpen(url, width, height) {
        child = window.open("./scheduled_arrival_registration_modal.php");
    }

    //以下SP
    $('button.modal_search_sp').on('click', function() {
        if (!child || child.closed) {
            $pushed_goods_id = $(this).closest("tr").find(".goods_id");
            $pushed_color_size_id = $(this).closest("tr").find(".color_size_id");
            $pushed_goods_name = $(this).closest("tr").find(".goods_name");
            $pushed_color = $(this).closest("tr").find(".color")
            $pushed_size = $(this).closest("tr").find(".size")
            winOpen($('form#form_sp').attr('action'), 200, 150);
        }
        return false;
    });
    $('#radio_goods_by_modal_sp').change(function() {
        var goods_id = $("#radio_goods_by_modal_sp").val().split(GOODS_SPLIT)[0];
        var color_size_id = $("#radio_goods_by_modal_sp").val().split(GOODS_SPLIT)[1];
        var goods_name = $("#radio_goods_by_modal_sp").val().split(GOODS_SPLIT)[2];
        var color = $("#radio_goods_by_modal_sp").val().split(GOODS_SPLIT)[3];
        var size = $("#radio_goods_by_modal_sp").val().split(GOODS_SPLIT)[4];
        $pushed_goods_id.val(goods_id);
        $pushed_color_size_id.val(color_size_id);
        $pushed_goods_name.val(goods_name);
        $pushed_color.val(color);
        $pushed_size.val(size);
    });
    $('input.submit_sp').on('click', function() {
        $(this).closest('form#form_sp').attr('action', $(this).data('action'));
        $(this).closest('form#form_sp').submit();
    });


    //以下PC
    $('button.modal_search_pc').on('click', function() {
        if (!child || child.closed) {
            $pushed_goods_id = $(this).closest("tr").find(".goods_id");
            $pushed_color_size_id = $(this).closest("tr").find(".color_size_id");
            $pushed_goods_name = $(this).closest("tr").find(".goods_name");
            $pushed_color = $(this).closest("tr").find(".color")
            $pushed_size = $(this).closest("tr").find(".size")
            winOpen($('form#form_pc').attr('action'), 200, 150);
        }
        return false;
    });
    $('#radio_goods_by_modal_pc').change(function() {
        var goods_id = $("#radio_goods_by_modal_pc").val().split(GOODS_SPLIT)[0];
        var color_size_id = $("#radio_goods_by_modal_pc").val().split(GOODS_SPLIT)[1];
        var goods_name = $("#radio_goods_by_modal_pc").val().split(GOODS_SPLIT)[2];
        var color = $("#radio_goods_by_modal_pc").val().split(GOODS_SPLIT)[3];
        var size = $("#radio_goods_by_modal_pc").val().split(GOODS_SPLIT)[4];
        $pushed_goods_id.val(goods_id);
        $pushed_color_size_id.val(color_size_id);
        $pushed_goods_name.val(goods_name);
        $pushed_color.val(color);
        $pushed_size.val(size);
    });
    $('input.submit_pc').on('click', function() {
        $(this).parents('form#form_pc').attr('action', $(this).data('action'));
        $(this).parents('form#form_pc').submit();
    });
});
</script>