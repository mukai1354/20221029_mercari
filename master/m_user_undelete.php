<?php
require_once('../common/function.php');
require_once('../db/DB.php');

get_header();

$result_deleted_users = array();
try {
    // データベース接続
    $db = DB::getDB();
    $sql = " SELECT user_id, user_name, role, owner_id FROM m_user";
    $sql .= " WHERE deleted_flag = true";
    $sql .= " ORDER BY user_id ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $result_deleted_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                <h1 class="my-5">ユーザー情報復活</h1>
                <form action="m_user_undelete_done.php" method="post">
                    <table class="table">
                        <thead>
                            <tr class="shipment_record_table_title">
                                <th scope="col"></th>
                                <th scope="col">ユーザーID</th>
                                <th scope="col">ユーザー名</th>
                                <th scope="col">役割</th>
                                <th scope="col">商品所有者コード</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $row_index = 0;
                            foreach($result_deleted_users as $deleted_user):
                                $user_id = $deleted_user['user_id'];
                                $user_name = $deleted_user['user_name'];
                                $role = $deleted_user['role'];
                                $owner_id = $deleted_user['owner_id'];
                            ?>
                            <tr class="tr_even">
                                <td><input class="checkbox" type="checkbox" name="checkbox_index[]"
                                        value="<?php echo $row_index;?>"></td>
                                <td><?php echo $user_id;?></td>
                                <td><?php echo $user_name;?></td>
                                <td><?php echo $role;?></td>
                                <td><?php echo $owner_id;?></td>
                                <input type="hidden" name="user_id[]" value="<?php echo $user_id;?>">
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