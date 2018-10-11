/**
 * @copyright        2017 opencart.cn - All Rights Reserved
 * @author:          Sam Chen <sam.chen@opencart.cn>
 * @created:         2017-09-05 12:11:09
 * @modified by:     Sam Chen <sam.chen@opencart.cn>
 * @modified:        2018-07-25 10:21:19
 */

function getURLVar(key) {
  var value = [];
  var query = String(document.location).split('?');
  if (query[1]) {
    var part = query[1].split('&');
    for (i = 0; i < part.length; i++) {
      var data = part[i].split('=');
      if (data[0] && data[1]) {
        value[data[0]] = data[1];
      }
    }

    if (value[key]) {
      return value[key];
    } else {
      return '';
    }
  }
}

// 通用消息提示框
function showAlert(content) {
  layer.open({
    skin: 'layer-alert',
    content: content,
    btn: ['OK'],
  });
}

function showToast(content) {
  layer.msg(content);
}

// 背景透明渐变
function bgcOpacity() {
  function opacityInner() {
    var scrollTop_val = $(window).scrollTop() / 150;

    if ( scrollTop_val > 1 ) {
      items.css({opacity: 1});
    } else {
      items.css({opacity: scrollTop_val});
    }
  }
  $(window).scroll( function() {
    opacityInner();
  });
}

$(document).ready(function() {
  $("img.lazy").unveil(10, function() {
    $(this).on('load',function() {
      this.style.opacity = 1;
    });
  });

  $(document).on('click', '.side-open', function(event) {
    $(this).next('.side-menu').fadeIn(0).children('.side-inner').addClass('active');
  });

  $(document).on('click', '.side-menu', function(e) {
    if ( $(e.target).closest('.side-inner').length === 0 ) {
      $('.side-menu .side-inner').removeClass('active');
      setTimeout("$('.side-menu').fadeOut(50)", 220);
    }
  });

  // 关闭首页弹窗
  $(document).on('click', '.home-popup .close-popup', function(event) {
    event.preventDefault();
    $(this).parents('.home-popup').remove();
  });

  // 购物车删除弹出
  $('.checkout-cart .edit').click(function(event) {
    $(this).toggleClass('active');
    $('.product-item .cart-quantity-wrapper').toggleClass('edit-remove');
  });

  jQuery(window).scroll(function(){
    if (jQuery(this).scrollTop() > 100) {
      jQuery('.go-top, .btn-meiqia').fadeIn();
    } else {
      jQuery('.go-top, .btn-meiqia').fadeOut();
    }
  });
  // scroll-to-top animate
  jQuery('.go-top').click(function(){
    jQuery("html, body").animate({ scrollTop: 0 }, 400);
      return false;
  });

  // Language
  $(document).delegate('#language a', 'click', function(e){
    e.preventDefault();
    $('#language input[name=\'code\']').attr('value', $(this).attr('href'));
    $('#language').submit();
  })

  // Currency
  $(document).delegate('#currency a', 'click', function(e){
    e.preventDefault();
    $('#currency input[name=\'code\']').attr('value', $(this).attr('href'));
    $('#currency').submit();
  })

  /* Search */
  $('#search input[name=\'search\']').parent().find('input[type="button"]').on('click', function() {
    url = $('base').attr('href') + 'index.php?route=product/search';
    var value = $('#search input[name=\'search\']').val();
    if (value) {
      url += '&search=' + encodeURIComponent(value);
    }
    location = url;
  });

  // Append * to required form input
  $('.form-group').each(function () {
    if ($(this).hasClass('required')) {
      $(this).find('input').attr('placeholder', function(i, val) {
        return val + '*';
      });
    }
  });
});

// Cart add remove functions
var cart = {
  'add': function(product_id, quantity) {
    $.ajax({
      url: 'index.php?route=checkout/cart/add',
      type: 'post',
      data: 'product_id=' + product_id + '&quantity=' + (typeof(quantity) != 'undefined' ? quantity : 1),
      dataType: 'json',
      beforeSend: function() {

        layer.load(2, {shade: [0.2,'#fff']});

        $('#cart > button').button('loading');
      },
      success: function(json) {
        layer.closeAll();
        if (json['redirect']) {
          location = json['redirect'];
        }

        if (json['success']) {
          showToast(json['success']);
        }
      }
    });
  },
  'update': function(key, quantity) {
    $.ajax({
      url: 'index.php?route=checkout/cart/edit',
      type: 'post',
      data: 'key=' + key + '&quantity=' + (typeof(quantity) != 'undefined' ? quantity : 1),
      dataType: 'json',
      beforeSend: function() {
        $('#cart > button').button('loading');
      },
      success: function(json) {
        $('#cart > button').button('reset');
        $('#cart-total').html(json['total']);
        if (getURLVar('route') == 'checkout/cart' || getURLVar('route') == 'checkout/checkout') {
          location = 'index.php?route=checkout/cart';
        } else {
          $('#cart > ul').load('index.php?route=common/cart/info ul li');
        }
      }
    });
  },
  'remove': function(key) {
    layer.load(2, {shade: [0.2,'#fff']});
    $.ajax({
      url: 'index.php?route=checkout/cart/remove',
      type: 'post',
      data: 'key=' + key,
      dataType: 'json',
      beforeSend: function() {
        $('#cart > button').button('loading');
      },
      success: function(json) {
        $('#cart > button').button('reset');
        $('#cart-total').html(json['total']);
        if (getURLVar('route') == 'checkout/cart' || getURLVar('route') == 'checkout/checkout') {
          location = 'index.php?route=checkout/cart';
        } else {
          $('#cart > ul').load('index.php?route=common/cart/info ul li');
        }
      }
    });
  }
};

var voucher = {
  'remove': function(key) {
    $.ajax({
      url: 'index.php?route=checkout/cart/remove',
      type: 'post',
      data: 'key=' + key,
      dataType: 'json',
      beforeSend: function() {
        $('#cart > button').button('loading');
      },
      complete: function() {
        $('#cart > button').button('reset');
      },
      success: function(json) {
        if (getURLVar('route') == 'checkout/cart' || getURLVar('route') == 'checkout/checkout') {
          location = 'index.php?route=checkout/cart';
        } else {
          $('#cart').load('index.php?route=common/cart/info');
        }
      },
      error: function(xhr, ajaxOptions, thrownError) {
        alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
      }
    });
  }
}

var recharge = {
  'add': function() {

  },
  'remove': function(key) {
    $.ajax({
      url: 'index.php?route=checkout/cart/remove',
      type: 'post',
      data: 'key=' + key,
      dataType: 'json',
      beforeSend: function() {
        $('#cart > button').button('loading');
      },
      complete: function() {
        $('#cart > button').button('reset');
      },
      success: function(json) {
        // Need to set timeout otherwise it wont update the total
        setTimeout(function () {
          $('#cart > button').html('<span id="cart-total"><i class="fa fa-shopping-cart"></i> ' + json['total'] + '</span>');
        }, 100);

        if (getURLVar('route') == 'checkout/cart' || getURLVar('route') == 'checkout/checkout') {
          location = 'index.php?route=checkout/cart';
        } else {
          $('#cart > ul').load('index.php?route=common/cart/info ul li');
        }
      },
          error: function(xhr, ajaxOptions, thrownError) {
              alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
          }
    });
  }
};

var wishlist = {
  'add': function(product_id) {
    $.ajax({
      url: 'index.php?route=account/wishlist/add',
      type: 'post',
      data: 'product_id=' + product_id,
      dataType: 'json',
      success: function(json) {
        if (json['success']) {
          showToast('<img src="catalog/view/theme/mobile/image/success.png" style="width: 50px;">');
          $('#button-add-to-wishlist i').addClass('active');
        }
        if (json['info']) {
          showToast(json['info']);
        }
      }
    });
  },
  'remove': function(product_id) {
    $.ajax({
      url: 'index.php?route=account/wishlist/remove',
      type: 'post',
      data: 'product_id=' + product_id,
      dataType: 'json',
      success: function(json) {
        if (json['success']) {
          showToast(json['success']);
          $('#button-add-to-wishlist i').removeClass('active');
        }
        if (json['info']) {
          showToast(json['info']);
        }
      }
    });
  }
};

/* Product question */
(function ($) {
  var _options = {};

  $.fn.ProductQuestion = function(options) {
    _options = $.extend({
      productId: 0,
      nameInputFieldId: '#input-question-name',
      questionInputFieldId: '#input-question-question',
      submitButtonId: '#button-question',
      emptyLabel: {
        name: 'Please enter your name.',
        question: 'Please enter your question.'
      }
    }, options);

    _parent = $(this);

    _parent.find(_options.submitButtonId).click(function () {
      var _this = $(this);

      if (_options.productId < 1) {
        return;
      }

      name = $(_options.nameInputFieldId).val(),
      question = $(_options.questionInputFieldId).val();

      if( name == '' ) {
        layer.msg(_options.emptyLabel.name);
        _parent.find(_options.nameInputFieldId).focus();
        return;
      }

      if( question == '' ) {
        layer.msg(_options.emptyLabel.question);
        _parent.find(_options.questionInputFieldId).focus();
        return;
      }

      $.ajax({
        type:'post',
        url:'index.php?route=product/askquestion/add',
        data:{ product_id: _options.productId, name: name, question: question },
        beforeSend:function() {
          layer.load(2, {shade: [0.5,'#fff']});
        },
        success:function(result) {
          layer.closeAll();
          layer.alert(result);
          _parent.find(_options.questionInputFieldId).val('');
        }
      });
    });

    return this;
  };
}(jQuery));

// 商品详情加购物车
;(function($) {
  var ProductInfoToCart = function(element, options) {
    var defaults = {
      data: [],
      messages: {
        error_msg: 'Failed. please check the page',
        success_msg: 'Add to cart'
      },
      cartSuccessFn: $.noop
    };

    this.element = element;
    this.settings = $.extend({}, defaults, options);
    this.init();
  };

  ProductInfoToCart.prototype = {
    init: function() {
      var self = this,
          settings = self.settings,
          $element = $(self.element);

      $element.on('click', function(e) {
        self.ajax(settings, $element);
      })
    },
    ajax: function(settings, $element) {
      if ( !settings.data.length ) return;
      $.ajax({
        url: 'index.php?route=checkout/cart/add',
        type: 'post',
        data: $(settings.data.join(',')),
        dataType: 'json',
        beforeSend: function() {
          $element.button('loading');
        },
        complete: function() {
          $element.button('reset');
        },
        success: function(json) {
          $('.right_list').removeClass('has_error');
          $('.text-danger').remove();

          if (json['error']) {
            $('html, body').animate({scrollTop: $("#options").parent().offset().top}, 0);

            if (json['error']['option']) {
              showToast(settings.messages.error_msg);
              for (i in json['error']['option']) {
                var element = $('#option_item_' + i + ' .right_list');
                element.addClass('has_error');
                element.append('<div class="text-danger">' + json['error']['option'][i] + '</div>');
              }
            }

            if (json['error']['flash_count']) {
              showToast(json['error']['flash_count']);
            }

            if (json['error']['recurring']) {
              $('select[name=\'recurring_id\']').after('<div class="text-danger">' + json['error']['recurring'] + '</div>');
            }
          }

          if (json['success']) {
            $('.cart-total').show();

            if ( settings.cartSuccessFn ) {
              layer.msg(settings.messages.success_msg, {time: 500, anim: 5}, function() {
                settings.cartSuccessFn($element);
              });
            } else {
              showToast(settings.messages.success_msg);
            }
          }
        }
      });
    },
  }

  $.fn.productInfoToCart = function (options) {
    this.each(function(index, el) {
      new ProductInfoToCart(this, options);
    });

    return this;
  };
})(jQuery);