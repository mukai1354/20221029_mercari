<?php
require_once ('../common/function.php');
require_once ('../db/DB.php');

$day = getMonday(); // 初期値

$owner_id = '';
$stok_schedule_day = $day;

// POSTを変数に代入
$owner_id = getpstStrs('owner_id');
$stok_schedule_day = getpstStrs('stok_schedule_day');
$goods_name = getpstStrs('goods_name');
$color = getpstStrs('color');
$size = getpstStrs('size');
$size = getpstStrs('size');

get_header();

// エラー情報リセット
$_SESSION["error_status"] = E_STATUS_DEFAULT;

try {
    // データベース接続
    $db = DB::getDB();
    // 商品所有者情報を取得(選択値)
    $stmt = $db->prepare("SELECT owner_id, owner FROM m_staff WHERE deleted_flag = false");
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $owner = '';
    foreach ($result as $varr) {
        $owner .= "\t\t\t\t\t<option value=\"{$varr['owner_id']}\")" . ">{$varr['owner']}</option>\n";
    }
    $result = array();
    //
    $sql = "SELECT
		T2.goods_id,
		T2.color_size_id,
		goods_name,
		color,
		size,
		stok_schedule_volume,
		stock_results_flag
		FROM
		m_staff T1
		INNER JOIN
		stock_schedule T2
		using(owner_id)
		INNER JOIN
		m_goods T3
		using(goods_id, color_size_id)
		WHERE stok_schedule_day = :stok_schedule_day
		AND T2.owner_id = :owner_id
        AND T1.deleted_flag = false";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':stok_schedule_day', $stok_schedule_day, PDO::PARAM_STR);
    $stmt->bindParam(':owner_id', $owner_id, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // print_r($result);
    if (! empty($result[0])) {
        $tbody = '';
        $i = 0;
        foreach ($result as $varr) {
            $tbody .= "						<tr>
							<td>{$i}</td>
							<td><input type=\"text\" name=\"goods_name[]\">{$varr['goods_name']}</td>
							<td><input type=\"text\" name=\"color[]\">{$varr['color']}</td>
							<td><input type=\"text\" name=\"size[]\">{$varr['size']}</td>
							<td><input type=\"number\" name=\"volume[]\">{$varr['stok_schedule_volume']}</td>
						</tr>
";
            $i ++;
            if ($i > 20) {
                break;
            }
        }
        while ($i <= 20) {
            $tbody .= "						<tr>
							<td>{$i}</td>
							<td><input type=\"text\" name=\"goods_name[]\" value=\"\"></td>
							<td><input type=\"text\" name=\"color[]\" value=\"\"></td>
							<td><input type=\"text\" name=\"size[]\" value=\"\"></td>
							<td><input type=\"number\" name=\"volume[]\" value=\"\"></td>
						</tr>
";
            $i ++;
        }
    } else {
        //
        $sql = "SELECT
			goods_id,
			color_size_id,
			goods_name,
			color,
			size
			FROM
			m_goods
			WHERE owner_id = :owner_id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':owner_id', $owner_id, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $tbody = '';
        $i = 1;
        foreach ($result as $varr) {
            $tbody .= "						<tr>
							<td>{$i}</td>
							<td><input type=\"text\" name=\"goods_name[]\" value=\"{$varr['goods_name']}\"></td>
							<td><input type=\"text\" name=\"color[]\" value=\"{$varr['color']}\"></td>
							<td><input type=\"text\" name=\"size[]\" value=\"{$varr['size']}\"></td>
							<td><input type=\"number\" name=\"volume[]\" value=\"\"></td>
						</tr>
";
            $i ++;
            if ($i >= 20) {
                break;
            }
        }
        while ($i <= 20) {
            $tbody .= "						<tr>
							<td>{$i}</td>
							<td><input type=\"text\" name=\"goods_name[]\" value=\"\"></td>
							<td><input type=\"text\" name=\"color[]\" value=\"\"></td>
							<td><input type=\"text\" name=\"size[]\" value=\"\"></td>
							<td><input type=\"number\" name=\"volume[]\" value=\"\"></td>
						</tr>
";
            $i ++;
        }
    }
    $db = NULL;
    $stmt = NULL;
} catch (PDOException $e) {
    die('エラー：' . $e->getMessage());
}
// print_r($result);

?>
	<div id="contents">
		<div class="inner">
			<div id="main">
			<main role="main">
				<h1>入荷予定登録</h1>
				<form action="" method="post">
				<select name="owner_id">
					<?php

    echo $owner;
    ?>
				</select>
				<input type="date" name="stok_schedule_day" value="<?php

    echo $stok_schedule_day?>">
				<button type="submit" name="action" value="disp">商品表示</button>
				<table>
					<caption>入荷情報登録</caption>
					<thead>
						<tr>
							<th scope="col">No</th>
							<th scope="col">商品名</th>
							<th scope="col">色</th>
							<th scope="col">サイズ</th>
							<th scope="col">数量</th>
						</tr>
					</thead>
					<tbody>
						<?php

    echo $tbody;
    ?>
					</tbody>
				</table>
				<button type="submit" name="action" value="submit">登録</button>
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
<?php

get_footer();