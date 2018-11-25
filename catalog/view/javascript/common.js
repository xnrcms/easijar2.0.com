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

var show_load = function() {
  layer.load(2, {shade: [0.1,'#fff'] });
}

var hide_load = function() {
  layer.closeAll('loading');
}

var cart_ajax_load_html = function() {
  $('#cart').load('index.php?route=common/cart/info');
}

// ajax 默认全局设置
$.ajaxSetup({
  cache: false,
  beforeSend: function() { show_load(); },
  complete: function() { hide_load(); },
  error: function(xhr, ajaxOptions, thrownError) {
    alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
  }
});


$(document).ready(function() {
  // $("img.lazy").unveil(10, function() {
  //   $(this).on('load',function() {
  //     this.style.opacity = 1;
  //   });
  // });
  (function() {
    if ($(window).width() > 992) {
      var
        _menuBox = $('#menu'),
        _headerBottom = $('header').offset().top + $('header').outerHeight(),
        _menuBoxH = _menuBox.outerHeight();

      $(window).scroll(function() {
        /*if ($(this).scrollTop() > 36) { 
            $("#search").addClass("fixed-search")
        } else {
            $("#search").removeClass("fixed-search")
        }*/
        /*if ( $(this).scrollTop() > _headerBottom ) {
          _menuBox.addClass('menu-fixed');
          if ( $('#menu').length > 0 ) return;
          _menuBox.before('<div class="menu-old" style="height:' + _menuBoxH + 'px; width:100%;"></div>');
        } else {
          _menuBox.removeClass('menu-fixed');
          $('.menu-old').remove();
        }*/
      });
    }
  }());

  // $(window).scroll(function() {
  //   var shortcut = $('.fixed-shortcut-wrapper');
  //   if ( !shortcut.length ) return;
  //   $(this).scrollTop() > 200 ? shortcut.show(240) : shortcut.hide(240);
  // });

  $('.scroll-top').on('click', function(event) {
    event.preventDefault();
    $('html, body').animate({ scrollTop: 0} , 'fast');
  });

  $(".mobile-nav-icon, .mobile-search").on('click', function () {
    $('.side-menu').addClass('active');
    $('body').addClass('body-overflow');
  });

  $(document).on('click', function (e) {
    var target = $(e.target);
    if (target.closest(".mobile-nav-icon, .mobile-search, .side-menu.active").length == 0) {
      $('.side-menu').removeClass('active');
      $('body').removeClass('body-overflow');
    }
  });

  $('.side-menu li .toggle-button').click(function(event) {
    $(this).parent().siblings().removeClass('open active').children('.dropdown-menu').slideUp();
    $(this).parent().toggleClass('active').find('.dropdown-menu').slideToggle("fast");
  });

  // Highlight any found errors
  $('.text-danger').each(function() {
    var element = $(this).parent().parent();

    if (element.hasClass('form-group')) {
      element.addClass('has-error');
    }
  });

  // Currency
  $('#form-currency .currency-select').on('click', function(e) {
    e.preventDefault();

    $('#form-currency input[name=\'code\']').val($(this).attr('name'));

    $('#form-currency').submit();
  });

  // Language
  $('#form-language .language-select').on('click', function(e) {
    e.preventDefault();

    $('#form-language input[name=\'code\']').val($(this).attr('name'));

    $('#form-language').submit();
  });

  /* Search */
  $('#search span').on('click', function() {
    var url = $('base').attr('href') + 'index.php?route=product/search';

    var value = $(this).siblings('input').val();

    if (value) {
      url += '&search=' + encodeURIComponent(value);
    }

    location = url;
  });

  $('#search input[name=\'search\']').on('keydown', function(e) {
    if (e.keyCode == 13) {
      $(this).siblings('span').trigger('click');
    }
  });

  // Menu
  $('#menu .dropdown-menu').each(function() {
    var menu = $('#menu').offset();
    var dropdown = $(this).parent().offset();

    var i = (dropdown.left + $(this).outerWidth()) - (menu.left + $('#menu').outerWidth());

    if (i > 0) {
      $(this).css('margin-left', '-' + (i + 10) + 'px');
    }
  });

  // Checkout
  $(document).on('keydown', '#collapse-checkout-option input[name=\'email\'], #collapse-checkout-option input[name=\'password\']', function(e) {
    if (e.keyCode == 13) {
      $('#collapse-checkout-option #button-login').trigger('click');
    }
  });

  // tooltips on hover
  $('[data-toggle=\'tooltip\']').tooltip({container: 'body'});

  // Makes tooltips work on ajax generated content
  $(document).ajaxStop(function() {
    $('[data-toggle=\'tooltip\']').tooltip({container: 'body'});
  });

  $('.more-review').on('click', function(e) {
    e.preventDefault();
    $("html, body").stop().animate({ scrollTop: $('.nav-tabs').offset().top }, 400);
    $('a[href=\'#tab-review\']').trigger('click');
  })

  $(document).on('click', '#review .pagination a', function(e) {
    e.preventDefault();
    $('#review').load(this.href);
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
        $('#cart > button').button('loading');
      },
      complete: function() {
        $('#cart > button').button('reset');
      },
      success: function(json) {
        $('.alert-dismissible, .text-danger').remove();

        if (json['redirect']) {
          location = json['redirect'];
        }

        if (json['success']) {
          layer.msg(json['success']);
          $('#cart').load('index.php?route=common/cart/info');
        }
      },
      error: function(xhr, ajaxOptions, thrownError) {
        alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
      }
    });
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

var wishlist = {
  'add': function(product_id) {
    $.ajax({
      url: 'index.php?route=account/wishlist/add',
      type: 'post',
      data: 'product_id=' + product_id,
      dataType: 'json',
      success: function(json) {
        $('.alert-dismissible').remove();

        if (json['redirect']) {
          location = json['redirect'];
        }

        if (json['success']) {
          layer.msg(json['success']);
        }

        $('#wishlist-total span').html(json['total']);
        $('#wishlist-total').attr('title', json['total']);
      },
      error: function(xhr, ajaxOptions, thrownError) {
        alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
      }
    });
  },
  'remove': function() {

  }
}

var compare = {
  'add': function(product_id) {
    $.ajax({
      url: 'index.php?route=product/compare/add',
      type: 'post',
      data: 'product_id=' + product_id,
      dataType: 'json',
      success: function(json) {
        $('.alert-dismissible').remove();

        if (json['success']) {
          layer.msg(json['success']);

          $('#compare-total').html(json['total']);
        }
      },
      error: function(xhr, ajaxOptions, thrownError) {
        alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
      }
    });
  },
  'remove': function() {

  }
}

/* Agree to Terms */
$(document).on('click', '.agree', function(e) {
  e.preventDefault();

  var $element = $(this);

  layer.open({
    type: 2,
    title: $element.text(),
    skin: 'agree-to-terms',
    content: [$element.attr('href')],
  });
});

/* Autocomplete */
(function($) {
  $.fn.autocomplete = function(option) {
    return this.each(function() {
      this.timer = null;
      this.items = new Array();

      $.extend(this, option);

      $(this).attr('autocomplete', 'off');

      // Focus
      $(this).on('focus', function() {
        this.request();
      });

      // Blur
      $(this).on('blur', function() {
        setTimeout(function(object) {
          object.hide();
        }, 200, this);
      });

      // Keydown
      $(this).on('keydown', function(event) {
        switch(event.keyCode) {
          case 27: // escape
            this.hide();
            break;
          default:
            this.request();
            break;
        }
      });

      // Click
      this.click = function(event) {
        event.preventDefault();

        value = $(event.target).parent().attr('data-value');

        if (value && this.items[value]) {
          this.select(this.items[value]);
        }
      }

      // Show
      this.show = function() {
        var pos = $(this).position();

        $(this).siblings('ul.dropdown-menu').css({
          top: pos.top + $(this).outerHeight(),
          left: pos.left
        });

        $(this).siblings('ul.dropdown-menu').show();
      }

      // Hide
      this.hide = function() {
        $(this).siblings('ul.dropdown-menu').hide();
      }

      // Request
      this.request = function() {
        clearTimeout(this.timer);

        this.timer = setTimeout(function(object) {
          object.source($(object).val(), $.proxy(object.response, object));
        }, 200, this);
      }

      // Response
      this.response = function(json) {
        html = '';

        if (json.length) {
          for (i = 0; i < json.length; i++) {
            this.items[json[i]['value']] = json[i];
          }

          for (i = 0; i < json.length; i++) {
            if (!json[i]['category']) {
              html += '<li data-value="' + json[i]['value'] + '"><a href="#">' + json[i]['label'] + '</a></li>';
            }
          }

          // Get all the ones with a categories
          var category = new Array();

          for (i = 0; i < json.length; i++) {
            if (json[i]['category']) {
              if (!category[json[i]['category']]) {
                category[json[i]['category']] = new Array();
                category[json[i]['category']]['name'] = json[i]['category'];
                category[json[i]['category']]['item'] = new Array();
              }

              category[json[i]['category']]['item'].push(json[i]);
            }
          }

          for (i in category) {
            html += '<li class="dropdown-header">' + category[i]['name'] + '</li>';

            for (j = 0; j < category[i]['item'].length; j++) {
              html += '<li data-value="' + category[i]['item'][j]['value'] + '"><a href="#">&nbsp;&nbsp;&nbsp;' + category[i]['item'][j]['label'] + '</a></li>';
            }
          }
        }

        if (html) {
          this.show();
        } else {
          this.hide();
        }

        $(this).siblings('ul.dropdown-menu').html(html);
      }

      $(this).after('<ul class="dropdown-menu"></ul>');
      $(this).siblings('ul.dropdown-menu').delegate('a', 'click', $.proxy(this.click, this));

    });
  }
})(window.jQuery);

// 商品详情加购物车
;(function($) {
  var ProductInfoToCart = function(element, options) {
    var defaults = {
      data: [],
    };

    this.element = element;
    this.settings = $.extend({}, defaults, options);
    this.init();
  };

  ProductInfoToCart.prototype = {
    init: function() {
      var self = this, settings = this.settings;

      $(this.element).on('click', function(e) {
        self.ajax(settings);
      })
    },
    ajax: function(settings) {
      if ( !settings.data.length ) return;
      $.ajax({
        url: 'index.php?route=checkout/cart/add',
        type: 'post',
        data: $(settings.data.join(',')),
        dataType: 'json',
        success: function(json) {
          $('.alert-dismissible, .text-danger').remove();
          $('.form-group').removeClass('has-error');

          if (json['error']) {
            if (json['error']['option']) {
              for (i in json['error']['option']) {
                var element = $('#input-option' + i.replace('_', '-'));

                if (element.parent().hasClass('input-group')) {
                  element.parent().after('<div class="text-danger">' + json['error']['option'][i] + '</div>');
                } else {
                  element.after('<div class="text-danger">' + json['error']['option'][i] + '</div>');
                }
              }
            }

            $('.text-danger').parent().addClass('has-error');

            if (json['error']['flash_count']) {
              layer.msg(json['error']['flash_count'], {icon: 2});
            }
          }

          if (json['success']) {
            cart_ajax_load_html();
            layer.msg(json['success']);
          }
        },
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

// 商品详情立即购买
;(function($) {
  var ProductInfoToCart2 = function(element, options) {
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

  ProductInfoToCart2.prototype = {
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
        url: 'index.php?route=checkout/cart/add&buy_type=1',
        type: 'post',
        data: $(settings.data.join(',')),
        dataType: 'json',
        success: function(json) {
          if (json['error']) {
            layer.msg(json['error'], {icon: 2},function(){
              if (json['redirect']) { location.href = json['redirect']; }
            });
            return;
          }

          if (json['success']) {
            if ( settings.cartSuccessFn ) { settings.cartSuccessFn($element);}
            return;
          }
        },
      });
    },
  }

  $.fn.productInfoToCart2 = function (options) {
    this.each(function(index, el) {
      new ProductInfoToCart2(this, options);
    });

    return this;
  };
})(jQuery);

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

;(function($) {
  $.fn.unveil = function(threshold, callback) {

    var $w = $(window),
        th = threshold || 0,
        retina = window.devicePixelRatio > 1,
        attrib = retina? "data-src-retina" : "data-src",
        images = this,
        loaded;

    this.one("unveil", function() {
      var source = this.getAttribute(attrib);
      source = source || this.getAttribute("data-src");
      if (source) {
        this.setAttribute("src", source);
        if (typeof callback === "function") callback.call(this);
      }
    });

    function unveil() {
      var inview = images.filter(function() {
        var $e = $(this);
        if ($e.is(":hidden")) return;

        var wt = $w.scrollTop(),
            wb = wt + $w.height(),
            et = $e.offset().top,
            eb = et + $e.height();

        return eb >= wt - th && et <= wb + th;
      });

      loaded = inview.trigger("unveil");
      images = images.not(loaded);
    }

    $w.on("scroll.unveil resize.unveil lookup.unveil", unveil);

    unveil();

    return this;

  };

})(window.jQuery || window.Zepto);