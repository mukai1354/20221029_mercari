<?php
require_once ('../common/function.php');
require_once ('../db/DB.php');

const GOODS_INFO_SPLIT = ":::";

const GOODS_SPLIT = ";;;";

get_header();

try {
    $db = DB::getDB();
    $stmt = $db->prepare("SELECT owner_id, owner FROM m_staff");
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $db = NULL;
    $stmt = NULL;
} catch (PDOException $e) {
    die('エラー：' . $e->getMessage());
}
$owner_id = h($_SESSION['login_owner_id']);
$owner = "";
foreach ($result as $varr) {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $owner_id = h($_POST['owner_id']);
    }
    if ($owner_id == $varr['owner_id']) {
        $owner .= "\t\t\t\t\t<option value=\"{$varr['owner_id']}\" selected>{$varr['owner']}</option>\n";
    } else {
        $owner .= "\t\t\t\t\t<option value=\"{$varr['owner_id']}\">{$varr['owner']}</option>\n";
    }
}

$owner_id = "";
$input_goods_name = "";
$selected_goods_name = "";
$goods = array();
$goods_info = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $owner_id = h($_POST['owner_id']);
    $input_goods_name = h($_POST['input_goods_name']);
    $selected_goods_name = h($_POST['selected_goods_name']);


    try {
        $sql = "SELECT * FROM m_goods WHERE owner_id = :owner_id ";
        $db = DB::getDB();
        if ($input_goods_name != "") {
            $sql .= " AND goods_name LIKE :goods_name";
            $stmt = $db->prepare($sql);
            $bind_goods_name = '%' . $input_goods_name . '%';
            $stmt->bindParam(':goods_name', $bind_goods_name, PDO::PARAM_STR);
        } else {
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

    $goods_name = "";
    $unique_goods_name = getUniqueArray($goods, 'goods_name');
    foreach ($unique_goods_name as $varr) {
        if ($selected_goods_name == $varr['goods_id']) {
            $goods_name .= "\t\t\t\t\t<option value=\"{$varr['goods_id']}\" selected>{$varr['goods_name']}</option>\n";
        } else {
            $goods_name .= "\t\t\t\t\t<option value=\"{$varr['goods_id']}\">{$varr['goods_name']}</option>\n";
        }
    }
    foreach ($goods as $varr) {
        $goods_info .= $varr['owner_id'] . GOODS_SPLIT . $varr['goods_id'] . GOODS_SPLIT . $varr['color_size_id'] . GOODS_SPLIT . $varr['goods_name'] . GOODS_SPLIT . $varr['color'] . GOODS_SPLIT . $varr['size'] . GOODS_SPLIT . $varr['picture'] . GOODS_INFO_SPLIT;
    }
}

?>
<div id="contents">
  <div class="row justify-content-center">
    <div id="col-auto">
      <main class="mb-5" role="main">
        <h1 class="mt-5">リピート商品検索</h1><br>
        <form action="" method="post">
            <div class="mx-auto d-flex justify-content-between">
                <label class="mr-5">
                        商品所有者：&nbsp;<select class="" name="owner_id"><?php echo $owner?></select>
                </label>
                <label class="mr-5">キーワード：&nbsp;<input type="text" name="input_goods_name" id="input_goods_name" value="<?php echo $input_goods_name; ?>"></label>
                <input type="submit" class="submit mt-0" name="search" data-action="scheduled_arrival_registration_modal.php" value="検索">
            </div>

            <?php if(!empty(($_POST["search"]))): ?>
            <label>
                <select name="selected_goods_name">
                    <?php echo $goods_name; ?>
                </select>
            </label>
          <input type="hidden" name="goods_info" id="goods_info" value="<?php
    echo $goods_info?>">
          <br><br>
          <table class="table">
            <thead class="shipment_record_table_title">
              <tr>
                <th></th>
                <th>商品名</th>
                <th>色</th>
                <th>サイズ</th>
                <th>画像</th>
              </tr>
            </thead>
            <tbody>
            </tbody>
          </table>
          <br>
          <input type="submit" id="close" value="閉じる">
          <input type="submit" id="repeat" value="この商品をリピートする">
          <?php endif; ?>
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
<script type="text/javascript" src="../common/js/common.js"></script>
<script>
  const GOODS_INFO_SPLIT = ":::";
  const GOODS_SPLIT = ";;;";

  $(document).ready(function () {
    var radio_goods;

    if (!window.opener || window.opener.closed) {
      return;
    }
    $('#close').click(function () {
      window.close();
    });

    $('#repeat').click(function () {
      window.opener.$('#radio_goods_by_modal').val(radio_goods).change();
      window.close();
      return false;
    });

    $('select[name="selected_goods_name"]').change(function () {
      if ($(this).val() != "") {
        $("table tbody").empty();
        var selected_goods_id = $(this).val();
        var goods_info = $("#goods_info").val().split(GOODS_INFO_SPLIT);
        $.each(goods_info, function (index, value) {
          if (selected_goods_id == value.split(GOODS_SPLIT)[1]) {
            var img = "";
            if (value.split(GOODS_SPLIT)[6] != "") {
              img_src = "../common/images/" + value.split(GOODS_SPLIT)[0] +
                "/goods/" + value.split(GOODS_SPLIT)[6];
              img = "<img src=\"" +
                img_src +
                "\" class=\"table-td-img-product-image\">";
            }
            radio_goods = value.split(GOODS_SPLIT)[1] + GOODS_SPLIT + value.split(
                GOODS_SPLIT)[2] + GOODS_SPLIT + value.split(GOODS_SPLIT)[3] +
              GOODS_SPLIT + value.split(GOODS_SPLIT)[4] + GOODS_SPLIT + value.split(
                GOODS_SPLIT)[5];
            $("table tbody").append("<tr>");
            $("table tbody").append(
              "<td><input type=\"radio\" name=\"radio_goods\" value=\"" +
              radio_goods + "\"></td>");
            $("table tbody").append("<td>" + value.split(GOODS_SPLIT)[3] + "</td>");
            $("table tbody").append("<td>" + value.split(GOODS_SPLIT)[4] + "</td>");
            $("table tbody").append("<td>" + value.split(GOODS_SPLIT)[5] + "</td>");
            $("table tbody").append("<td>" + img + "</td>");
            $("table tbody").append("</tr>");
          }
        })
      }
    });

    $(document).on("click", "input[name=\"radio_goods\"]", function () {
      radio_goods = $(this).val();
    });

    $('input.submit').on('click', function () {
      $(this).parents('form').attr('action', $(this).data('action'));
      $(this).parents('form').submit();
    });
  });
</script>
<?php
get_footer();
?>