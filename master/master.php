<?php
require_once ('../common/function.php');

get_header();
?>
	<div id="contents">
		<div class="inner">
			<div id="main">
			<main role="main">
				<h1>各マスタ登録</h1>
<ul>
	<li><a href="<?php

echo get_home_url('/master/m_staff.php')?>">商品所有者マスタ登録</a></li>
	<li><a href="<?php

echo get_home_url('/master/m_staff_update.php')?>">商品所有者マスタ更新</a></li>
	<li><a href="<?php echo get_home_url('/master/m_staff_undelete.php')?>">商品所有者マスタ復活</a></li>
	<li><a href="<?php

echo get_home_url('/master/m_user.php')?>">ユーザーマスタ登録</a></li>
	<li><a href="<?php

echo get_home_url('/master/m_user_update.php')?>">ユーザーマスタ更新</a></li>
	<li><a href="<?php

echo get_home_url('/master/m_goods.php')?>">商品マスタ登録</a></li>
	<li><a href="<?php

echo get_home_url('/master/m_goods_update.php')?>">商品マスタ更新</a></li>
</ul>
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