/**
 * @copyright        2018 opencart.cn - All Rights Reserved
 * @author:          Sam Chen <sam.chen@opencart.cn>
 * @created:         2018-05-09 14:50:12
 * @modified by:     Sam Chen <sam.chen@opencart.cn>
 * @modified:        2018-06-01 10:24:36
 */

(function ($) {
  var _options = {};
  var _parent = null;

  /**
   * 计算 variant 按钮可点击状态
   * @public
   * @param {Array} [keys] - [variantId:variantValueId]
   */
  function isVariantComboFound(keys) {
    var totalFinds = 0;
    $.each(_options.skus, function (key, url) {
      var finds = 0;
      for (var index = 0; index < keys.length; index++) {
        var segment = '|' + keys[index] + '|';
        if (key.indexOf(segment) !== -1) {
          finds++;
        }
      }
      if (finds >= keys.length) {
        totalFinds++;
      }
    });

    return totalFinds > 0;
  }

  /**
   * 获取商品链接
   * @public
   * @param {Array} [keys] - [variantId:variantValueId]
   */
  function getSkuUrl(keys) {
    var result = '';
    $.each(_options.skus, function (key, url) {
      var finds = 0;
      for (var index = 0; index < keys.length; index++) {
        var segment = '|' + keys[index] + '|';
        if (key.indexOf(segment) !== -1) {
          finds++;
        }
      }

      if (finds >= keys.length && key != _options.key) {
        result = url;
        return false;
      }
    });
    return result;
  }

  /**
   * 更新 variant 按钮点击状态
   */
  function updateVariantButtonState() {
    // 所有选中的 variant 按钮
    var selectedVariantElements = _parent.find('.' + _options.state.selected);

    // 所有选中的 variant 值
    var selectedVariants = [];
    selectedVariantElements.each(function () {
      selectedVariants.push($(this).data('variant'));
    });

    // 更新 variant 按钮选中状态
    _parent.find(_options.button).not(selectedVariantElements).each(function () {
      var currentVariantGroupSelectedElements = $(this).siblings('.' + _options.state.selected);
      var variantCandidates = selectedVariants.slice();

      if (currentVariantGroupSelectedElements.length) {
        var currentVariantGroupSelectedVariant = currentVariantGroupSelectedElements.data('variant');
        var index = variantCandidates.indexOf(currentVariantGroupSelectedVariant);
        if (index !== -1) {
          variantCandidates.splice(index, 1);
        }
      }
      variantCandidates.push($(this).data('variant'));

      if (!isVariantComboFound(variantCandidates)) {
        $(this).addClass(_options.state.disabled).removeClass(_options.state.selected);
      } else {
        $(this).removeClass(_options.state.disabled);
      }
    });

    return selectedVariants;
  }

  /**
   * 更新加入购物车按钮可点击状态
   */
  function enableActionButtons(enabled = false) {
    $(_options.action_buttons).each(function (index, button) {
      $(button).attr('disabled', (enabled ? false : 'disabled'));
    })
  }

  $.fn.ProductVariant = function(options) {
    _options = $.extend({
      button: '.btn-variant',
      state: {
        selected: 'selected',
        disabled: 'disabled',
        disabled_selected: 'disabled-selected'
      },
      action_buttons: [],
      key: '', // 当前商品的 variant key
      variant_group_count: 0, // variant group 总数
      skus: {}, // 所有 SKU
    }, options);

    _parent = $(this);

    // 初始化 variant 按钮状态（所有未选中的 variant 按钮状态都为 disabled）
    updateVariantButtonState();

    // variant 按钮点击事件
    _parent.find(_options.button).click(function () {
      var _this = $(this);

      if (_this.hasClass(_options.state.disabled_selected)) {
        _this.removeClass(_options.state.disabled_selected);
        return;
      }

      // disabled 状态
      if (_this.hasClass(_options.state.disabled)) {
        _this.removeClass(_options.state.disabled);
        _parent.find(_options.button).removeClass(_options.state.disabled_selected);
        _parent.find(_options.button).removeClass(_options.state.selected);
        var keys = [_this.data('variant')];
        if (isVariantComboFound(keys)) {
          _this.addClass(_options.state.selected);
        } else {
          _this.addClass(_options.state.disabled_selected);
        }
      } else {
        // 选中当前按钮，并反选其它按钮
        _this.toggleClass(_options.state.selected);
        // _this.siblings().removeClass(_options.state.disabled_selected);
        _parent.find(_options.button).removeClass(_options.state.disabled_selected);
      }

      _this.siblings().removeClass(_options.state.selected);
      var selectedVariants = updateVariantButtonState();

      // 按钮未全选时，禁用所有 action button
      if (selectedVariants.length != _options.variant_group_count) {
        enableActionButtons(false);
        return;
      }

      // 跳转至新商品链接
      enableActionButtons(true);
      var url = getSkuUrl(selectedVariants);
      if (url) {
        location = url;
      }
    });

    return this;
  };
}(jQuery));
