  <div class="row mt-5">
    <div class="footer col-12">
    <div id="page_top"><a href="#header"><img class="go_to_top" src="/mercari/common/images/go_to_top.png" alt="go_to_top"></a></div>
    <div id="page_bottom"><a href="#footer"><img class="go_to_bottom" src="/mercari/common/images/go_to_top.png" alt="go_to_bottom"></a></div>
    <footer id="footer" class="text-center">
      <address>&#169;2020&#160;Dream &#160;Net&#160;Systems&#160;LLC.</address>

      <!-- ユーザー定義ファイル読み込み(JS) -->
      <script src="<?php
echo get_js_url('/')?>common.js?p=(new Date()).getTime()" ></script>
    </footer>
    </div>
    <!-- /.footer .col-12 -->
  </div>
  <!-- /.row -->
</div>
<!-- /.container-fluid -->

<script>
$(function(){
    let scroll = $(window).scrollTop();
    let navHeight = $("#nav").height();
    let navOffset = $("#nav").offset().top;
    let buttonOffset = $("#button_wrapper").offset().top;
    let buttonOffsetResult = buttonOffset - navHeight - 10;
    if(navOffset < scroll) {
      $("#nav").css({
        "position":"fixed",
        "top":"0",
        "left":"0"
      });
    } else {
      $("#nav").css({
        "position":"",
        "top":"",
        "left":""
      });
    }

    if(buttonOffsetResult < scroll) {
      $("#button_wrapper").css({
        "position":"fixed",
        "top": buttonOffsetResult,
        "left":"0"
      });
    } else {
      $("#button_wrapper").css({
        "position":"",
        "top":"",
        "left":""
      });
    }
});

//TOPへ戻るボタン
jQuery(function() {
  var appear = false;
  var pagetop = $('#page_top');
  $(window).scroll(function () {
    if ($(this).scrollTop() > 100) {  //100pxスクロールしたら
      if (appear == false) {
        appear = true;
        pagetop.stop().animate({
          'right': '0px' //右から0pxの位置に
        }, 300); //0.3秒かけて現れる
      }
    } else {
      if (appear) {
        appear = false;
        pagetop.stop().animate({
          'right': '-50px' //右から-50pxの位置に
        }, 300); //0.3秒かけて隠れる
      }
    }
  });
  pagetop.click(function () {
    $('body, html').animate({ scrollTop: 0 }, 500); //0.5秒かけてトップへ戻る
    return false;
  });
});

//下に移動ボタン
jQuery(function() {
  var appear = false;
  var pagebottom = $('#page_bottom');
  $(window).scroll(function () {
    if ($(this).scrollTop() > 100) {  //100pxスクロールしたら
      if (appear == false) {
        appear = true;
        pagebottom.stop().animate({
          'right': '0px' //右から0pxの位置に
        }, 300); //0.3秒かけて現れる
      }
    } else {
      if (appear) {
        appear = false;
        pagebottom.stop().animate({
          'right': '-50px' //右から-50pxの位置に
        }, 300); //0.3秒かけて隠れる
      }
    }
  });
});

</script>
</body>
</html>