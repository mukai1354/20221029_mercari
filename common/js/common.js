// @version 1

//アップロード時にサムネイルを表示
$(document).ready(function () {
  $('.file').on('change', function(){
     var fileprop = $(this).prop('files')[0],
         find_img = $(this).parent().find('img'),
         filereader = new FileReader(),
         view_box = $(this).parent('.view_box');

    if(find_img.length){
       find_img.nextAll().remove();
       find_img.remove();
    }

    var img = '<div class="img_view"><img alt="" class="img" width="200"><p><a href="#" class="img_del">画像を削除する</a></p></div>';

    view_box.append(img);

    filereader.onload = function() {
      view_box.find('img').attr('src', filereader.result);
      img_del(view_box);
    }
    filereader.readAsDataURL(fileprop);
  });

  function img_del(target){
    target.find("a.img_del").on('click',function(){
      var self = $(this),
          parentBox = self.parent().parent().parent();
      if(window.confirm('画像を削除します。\nよろしいですか？')){
        setTimeout(function(){
          parentBox.find('input[type=file]').val('');
          parentBox.find('.img_view').remove();
        } , 0);
      }
      return false;
    });
  }

});

/**
 *
 * Zero Padding
 * @param {float} number
 * @param {int} decimals
 * @return {string}
 */
function getZeroPadding(number, decimals)
{
  var number = String(number);
  // 0埋め指定数より桁数が大きい場合は処理を中止
  if (number.length > decimals) {
    return number;
  }
  // 値の前に10を乗算し0を追加、その後指定桁数へ切り出し
  return (Math.pow(10, decimals) + number).slice(decimals * -1);
}

/**
 * master,systemフォルダ直下のPHPからのみ実行可能
 * @param file_name
 * @returns
 */
function getPicturePath(file_name)
{
	return "../common/images/" + file_name;
}

//ヒストリバック
  $(document).on('click', '.prev', function () {
        history.go(-1);
    });