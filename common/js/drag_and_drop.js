/**
 * @version 1
 */
var preview_field_tag; // FileReader の onload メソッド内に、プレビュー対象のタグを渡すために利用

$(function () {

  $('.icon_clear_button').show();  // icon_clear_buttonを表示させます

  // クリックで画像を選択する場合
  $('.drop_area').on('click', function () {
    $(this).parent().find('.input_file').click();
  });

  $('.input_file').on('change', function () {
    // 画像が複数選択されていた場合
    if (this.files.length > 1) {
      alert('アップロードできる画像は1つだけです');
      $(this).parent().find('.input_file').val('');
      return;
    }
    handleFiles(this);
  });

  //ドラッグしている要素がドロップ領域に入ったとき・領域にある間
  $('.drop_area').on('dragenter dragover', function (event) {
    event.stopPropagation();
    event.preventDefault();
    $(this).parent().find('.drop_area').css('border', '1px solid .333');  // 枠を実線にする
  });

  //ドラッグしている要素がドロップ領域から外れたとき
  $('.drop_area').on('dragleave', function (event) {
    event.stopPropagation();
    event.preventDefault();
    $(this).parent().find('.drop_area').css('border', '1px dashed .aaa');  // 枠を点線に戻す
  });

  //ドラッグしている要素がドロップされたとき
  $('.drop_area').on('drop', function (event) {
    event.preventDefault();

    $(this).parent().find('.input_file')[0].files = event.originalEvent.dataTransfer.files;

    // 画像が複数選択されていた場合
    if ($(this).parent().find('.input_file')[0].files.length > 1) {
      alert('アップロードできる画像は1つだけです');
      $(this).parent().find('.input_file').val('');
      return;
    }
    handleFiles($(this).parent().find('.input_file')[0]);
  });

  //アイコン画像を消去するボタン
  $('.icon_clear_button').on('click', function () {
    $(this).parent().find('.preview_field').empty();  // 表示していた画像を消去
    $(this).parent().find('.input_file').val('');  // inputの中身を消去
    $(this).parent().find('.drop_area').show();  // drop_areaをいちばん前面に表示
    $(this).parent().find('.icon_clear_button').hide();  // icon_clear_buttonを非表示
    $(this).parent().find('.drop_area').css('border', '1px dashed .aaa');  // 枠を点線に変更
  });

  //drop_area以外でファイルがドロップされた場合、ファイルが開いてしまうのを防ぐ
  $(document).on('dragenter', function (event) {
    event.stopPropagation();
    event.preventDefault();
  });
  $(document).on('dragover', function (event) {
    event.stopPropagation();
    event.preventDefault();
  });
  $(document).on('drop', function (event) {
    event.stopPropagation();
    event.preventDefault();
  });
});

//選択された画像ファイルの操作
function handleFiles(input_file_tag) {
    var file = input_file_tag.files[0];
    var imageType = 'image.*';

    // ファイルが画像が確認する
    if (! file.type.match(imageType)) {
      alert('画像を選択してください');
      $(input_file_tag).parent().find('.input_file').val('');
      $(input_file_tag).parent().find('.drop_area').css('border', '1px dashed .aaa');
      return;
    }

    $(input_file_tag).parent().find('.drop_area').hide();  // いちばん上のdrop_areaを非表示にします
    $(input_file_tag).parent().find('.icon_clear_button').show();  // icon_clear_buttonを表示させます

    var img = document.createElement('img');  // <img>をつくります
    var reader = new FileReader();
    preview_field_tag = $(input_file_tag).parent().find('.preview_field');
    reader.onload = function (event) {  // 読み込みが完了したら
      img.src = reader.result;  // readAsDataURLの読み込み結果がresult
      $(preview_field_tag).append(img);  // preview_filedに画像を表示
    }
    reader.readAsDataURL(file); // ファイル読み込みを非同期でバックグラウンドで開始
  }
