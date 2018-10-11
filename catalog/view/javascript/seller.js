/**
 * Created by stiffer on 2018/6/13.
 */

$(document).on('click', 'a[data-toggle=\'image\']', function(e) {
  var $element = $(this);
  var $popover = $element.data('bs.popover'); // element has bs popover?
  var $button = '<button type="button" id="button-image" class="btn btn-primary"><i class="fa fa-pencil"></i></button> <button type="button" id="button-clear" class="btn btn-danger"><i class="fa fa-trash-o"></i></button>';
  if($element.hasClass('product-img')){
    $button += ' <button type="button" onMouseOver="$(this).tooltip(\'show\')" id="button-main" data-toggle="tooltip" data-placement="top" data-original-title="' + text_main_image + '" class="btn btn-success"><i class="fa fa-laptop"></i></button>';
  }
  e.preventDefault();

  // destroy all image popovers
  $('a[data-toggle="image"]').popover('destroy');

  // remove flickering (do not re-add popover when clicking for removal)
  if ($popover) {
    return;
  }

  $element.popover({
    html: true,
    placement: 'right',
    trigger: 'manual',
    content: function() {
      return $button;
    }
  });

  $element.popover('show');

  $('#button-image').on('click', function() {
    var act = $element.attr('btn-act');
    CKFinder.popup( {
      chooseFiles: true,
      width: 800,
      height: 600,
      onInit: function( finder ) {
        finder.on( 'files:choose', function( evt ) {
          var files = evt.data.files.toArray();
          var files_array = new Array()
          for(var i = 0; i < files.length; i++){
            files_array[i] = files[i].getUrl();
          }
          if(files_array.length > 0){
            $.ajax({
              url: 'index.php?route=seller/product/ckfinder&restore=1&target=' + $element.parent().find('input').attr('id') + '&thumb=' + $element.attr('id'),
              data : 'files=' + files_array,
              type : 'post',
              dataType : 'json',
              success : function(json){
                if(json['code'] == 1){
                  if(act == 'main_img'){
                    var main_img = json['result'][0];
                    var new_main_src = main_img['thumb'];
                    var new_main = '<a href="" id="thumb-image" data-toggle="image" class="img-thumbnail" btn-act="' + act + '">';
                    new_main += '<img src="' + new_main_src + '" alt="" title="" data-placeholder="' + $element.attr('data-placeholder') + '" />';
                    new_main += '<input type="hidden" name="image" value="' + main_img['image'] + '" id="input-image" />';
                    new_main += '</a>';
                    $('#thumb-image').parent('td').html(new_main);
                    if(json['result'].length > 1){
                      var image_row = $('#images').find('tbody tr').length;
                      for(var i = 1; i < json['result'].length; i++){
                        html  = '<tr id="image-row' + image_row + '">';
                        html += '  <td class="text-left"><a href="" id="thumb-image' + image_row + '"data-toggle="image" class="img-thumbnail product-img"><img src="' + json['result'][i]['thumb'] + '" alt="" title="" data-placeholder="' + placeholder + '" /></a><input type="hidden" name="product_image[' + image_row + '][image]" value="' + json['result'][i]['image'] + '" id="input-image' + image_row + '" /></td>';
                        html += '  <td class="text-right"><input type="text" name="product_image[' + image_row + '][sort_order]" value="" placeholder="' + text_sort + '" class="form-control" /></td>';
                        html += '  <td class="text-left"><button type="button" onclick="$(\'#image-row' + image_row  + '\').remove();" data-toggle="tooltip" title="' + text_delete + '" class="btn btn-danger"><i class="fa fa-minus-circle"></i></button></td>';
                        html += '</tr>';

                        $('#images tbody').append(html);
                        image_row++;
                      }
                    }
                  }else{
                    var image_row = $('#images').find('tbody tr').length;
                    var sub_img = json['result'][0];
                    var new_sub_src = sub_img['thumb'];
                    $element.find('img').attr('src', new_sub_src);
                    $element.next('input').val(sub_img['image']);
                    $element.next('input')[0].dispatchEvent(new Event('input'));
                    for(var i = 1; i < json['result'].length; i++){
                      html  = '<tr id="image-row' + image_row + '">';
                      html += '  <td class="text-left"><a href="" id="thumb-image' + image_row + '"data-toggle="image" class="img-thumbnail product-img"><img src="' + json['result'][i]['thumb'] + '" alt="" title="" data-placeholder="' + placeholder + '" /></a><input type="hidden" name="product_image[' + image_row + '][image]" value="' + json['result'][i]['image'] + '" id="input-image' + image_row + '" /></td>';
                      html += '  <td class="text-right"><input type="text" name="product_image[' + image_row + '][sort_order]" value="" placeholder="' + text_sort + '" class="form-control" /></td>';
                      html += '  <td class="text-left"><button type="button" onclick="$(\'#image-row' + image_row  + '\').remove();" data-toggle="tooltip" title="' + text_delete + '" class="btn btn-danger"><i class="fa fa-minus-circle"></i></button></td>';
                      html += '</tr>';

                      $('#images tbody').append(html);
                      image_row++;
                    }
                  }
                }
              }
            });
          }
        });
        finder.on( 'file:choose:resizedImage', function( evt ) {
          var file = evt.data;
          $.ajax({
            url: 'index.php?route=common/filemanager/ckfinder&user_token=' + getURLVar('user_token') + '&restore=1&target=' + $element.parent().find('input').attr('id') + '&thumb=' + $element.attr('id'),
            data : 'files=' + file['resizedUrl'],
            type : 'post',
            dataType : 'json',
            success : function(json){
              if(json['code'] == 1){
                if(act == 'main_img'){
                  var main_img = json['result'][0];
                  var new_main_src = main_img['thumb'];
                  var new_main = '<a href="" id="thumb-image" data-toggle="image" class="img-thumbnail" btn-act="' + act + '">';
                  new_main += '<img src="' + new_main_src + '" alt="" title="" data-placeholder="' + $element.attr('data-placeholder') + '" />';
                  new_main += '<input type="hidden" name="image" value="' + main_img['image'] + '" id="input-image" />';
                  new_main += '</a>';
                  $('#thumb-image').parent('td').html(new_main);
                  if(json['result'].length > 1){
                    var image_row = $('#images').find('tbody tr').length;
                    for(var i = 1; i < json['result'].length; i++){
                      html  = '<tr id="image-row' + image_row + '">';
                      html += '  <td class="text-left"><a href="" id="thumb-image' + image_row + '"data-toggle="image" class="img-thumbnail"><img src="' + json['result'][i]['thumb'] + '" alt="" title="" data-placeholder="' + placeholder + '" /></a><input type="hidden" name="product_image[' + image_row + '][image]" value="' + json['result'][i]['image'] + '" id="input-image' + image_row + '" /></td>';
                      html += '  <td class="text-right"><input type="text" name="product_image[' + image_row + '][sort_order]" value="" placeholder="排序" class="form-control" /></td>';
                      html += '  <td class="text-left"><button type="button" onclick="$(\'#image-row' + image_row  + '\').remove();" data-toggle="tooltip" title="删除" class="btn btn-danger"><i class="fa fa-minus-circle"></i></button></td>';
                      html += '</tr>';

                      $('#images tbody').append(html);
                      image_row++;
                    }
                  }
                }else{
                  var image_row = $('#images').find('tbody tr').length;
                  var sub_img = json['result'][0];
                  var new_sub_src = sub_img['thumb'];
                  $element.find('img').attr('src', new_sub_src);
                  $element.next('input').val(sub_img['image']);
                  for(var i = 1; i < json['result'].length; i++){
                    html  = '<tr id="image-row' + image_row + '">';
                    html += '  <td class="text-left"><a href="" id="thumb-image' + image_row + '"data-toggle="image" class="img-thumbnail"><img src="' + json['result'][i]['thumb'] + '" alt="" title="" data-placeholder="' + placeholder + '" /></a><input type="hidden" name="product_image[' + image_row + '][image]" value="' + json['result'][i]['image'] + '" id="input-image' + image_row + '" /></td>';
                    html += '  <td class="text-right"><input type="text" name="product_image[' + image_row + '][sort_order]" value="" placeholder="排序" class="form-control" /></td>';
                    html += '  <td class="text-left"><button type="button" onclick="$(\'#image-row' + image_row  + '\').remove();" data-toggle="tooltip" title="删除" class="btn btn-danger"><i class="fa fa-minus-circle"></i></button></td>';
                    html += '</tr>';

                    $('#images tbody').append(html);
                    image_row++;
                  }
                }
              }
            }
          });
        });
      }
    });
    $element.popover('destroy');
  });

  $('#button-main').on('click', function(){
    if($element.attr('id') != 'thumb-image'){
      var placeholder = $('#thumb-image').find('img').attr('data-placeholder');
      var new_current = '<td class="text-left">';
      new_current += '<a href="" id="' + $element.attr('id') + '" data-toggle="image" class="img-thumbnail product-img" btn-act="' + $element.attr('btn-act') + '">';
      new_current += '<img src="' + $('#thumb-image').find('img').attr('src') + '" alt="" title="" data-placeholder="' + placeholder + '" />';
      new_current += '</a>';
      var h_value = $('#thumb-image').find('img').attr('src').split('cache/');
      new_current += '<input type="hidden" name="' + $element.parent('td').find('input[type="hidden"]').attr('name') + '" value="' + h_value[1].replace('-100x100', '') + '" id="' + $element.parent('td').find('input[type="hidden"]').attr('id') + '" />';
      new_current += '</td>';
      new_current += '<td class="text-right">';
      //new_current += '<input type="text" name="' + $element.parent('td').parent('tr').find('.form-control').attr('name') + '" value="' + $element.parent('td').parent('tr').find('.form-control').attr('value') + '" placeholder="' + $element.parent('td').parent('tr').find('.form-control').attr('placeholder') + '" class="form-control" />';
      new_current += $element.parent('td').parent('tr').find('td').eq(1).html();
      new_current += '</td>';
      new_current += '<td class="text-left">';
      new_current += $element.parent('td').parent('tr').find('td:last-child').html();
      new_current += '</td>';
      $element.parent('td').parent('tr').html(new_current);
      $element.popover('destroy');

      var new_main_src = $element.find('img').attr('src');
      var new_main = '<a href="" id="thumb-image" data-toggle="image" class="img-thumbnail" btn-act="' + $element.attr('btn-act') + '">';
      new_main += '<img src="' + new_main_src + '" alt="" title="" data-placeholder="' + placeholder + '" />';
      new_main += '<input type="hidden" name="image" value="' + $element.parent('td').find('input[type="hidden"]').val() + '" id="input-image" />';
      new_main += '</a>';
      $('#thumb-image').parent('td').html(new_main);
    }
  });

  $('#button-clear').on('click', function() {
    $element.find('img').attr('src', $element.find('img').attr('data-placeholder'));

    $element.parent().find('input').val('');

    $element.popover('destroy');
  });
});