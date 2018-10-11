/**
 * @copyright        2018 opencart.cn - All Rights Reserved
 * @author:          Sam Chen <sam.chen@opencart.cn>
 * @created:         2018-05-07 10:52:51
 * @modified by:     Sam Chen <sam.chen@opencart.cn>
 * @modified:        2018-06-01 12:33:47
 */

function showToast(message) {
  layer.msg(message);
}

function showPrompt(callback) {
  layer.prompt({ title: i18n.text_group_name, formType: 0 }, function (name, index) {
    callback(name);
    layer.close(index);
  });
}

var _loading = null;

function showLoading() {
  _loading = layer.load(1, { shade: [0.5, '#fff'] });
}

function closeLoading() {
  layer.close(_loading);
}

function showConfirm(message, leftButton, rightButton, callback) {
  var deleteConfirmLayer = layer.confirm(message, {
    btn: [leftButton, rightButton]
  }, function () {
    layer.close(deleteConfirmLayer);
    callback();
  });
}

data['currentStep'] = !data.selected_variants.length ? 1 : 3;
data['selectedVariantsFormatted'] = [];

// 初始化 处理 用户自定义的 variant 赋值到 selectedVariantsFormatted
for (variantId in data.custom_variants) {
  var variant = data.custom_variants[variantId];
  data['selectedVariantsFormatted'].push({ variantId: variantId, values: variant });
}

// 初始化 处理 用户不允许被自定义的 variant 追加进 selectedVariantsFormatted
for (var index = 0; index < data.variants.length; index++) {
  var variants = data.variants[index];
  var formattedVariants = data.selectedVariantsFormatted;
  if ( variants.allow_rename ) continue;
  if ( formattedVariants.length ) {
    if ( typeof formattedVariants[index] == 'undefined') {
      formattedVariants.push({ variantId: variants.id, values: variants.values });
    } else {
      formattedVariants[index].values = variants.values;
    }
  } else {
    formattedVariants.push({ variantId: variants.id, values: variants.values });
  }
}

var app = new Vue({
  el: '#product-variant-app',
  data: data,
  created: function () {
    this.validateProducts();
    this.parentId = this.products[0].product_id;
  },
  computed: {
    addProductButtonEnabled: function () {
      console.log(data);
      return this.products.length < this.maxVariantPossibilities;
    },

    // 选中的 variant type 能够最多结合多少个产品
    maxVariantPossibilities: function () {
      var count = 1;
      for (let index = 0; index < this.selected_variants.length; index++) {
        var variantId = this.selected_variants[index];
        count *= this.getVariantValues(variantId).length;
      }
      return count;
    },

    // 已选中的 variant 的名称
    selectedVariantNames: function () {
      var names = [];
      for (let index = 0; index < this.selected_variants.length; index++) {
        var variantId = this.selected_variants[index];
        names.push(this.getVariantTypeName(variantId));
      }
      return names;
    },

    // 判断 FormattedVariants 是否都有value 值
    isAllFormattedVariantsHaveValues: function () {
      for (let index = 0; index < this.selectedVariantsFormatted.length; index++) {
        var variant = this.selectedVariantsFormatted[index];
        if (!variant.values.length) {
          return false;
        }
      }

      return true;
    }
  },
  watch: {
    products: {
      handler: function () {
        this.validateProducts();
      },
      deep: true
    },
    selected_variants: {
      handler: function (curVal, oldVal) {
        this.selectedVariantsFormatted = [];
        for (let index = 0; index < this.selected_variants.length; index++) {
          var sdVariants = this.selected_variants[index];
          for (var i = 0; i < this.variants.length; i++) {
            if ( sdVariants == this.variants[i].id ) { // 判断 variants id 是否相等
              if (this.variants[i].allow_rename) { // 判断 variants 是否允许用户自定义
                if ( typeof this.custom_variants[sdVariants] != 'undefined' ) { // 判断用户是否自定义过，如果保存过，则 push custom_variants 对应的值，没有则空数组
                  this.selectedVariantsFormatted.push({ variantId: sdVariants, values: this.custom_variants[sdVariants] });
                } else {
                  this.selectedVariantsFormatted.push({ variantId: sdVariants, values: [] });
                }
              } else {
                this.selectedVariantsFormatted.push({ variantId: sdVariants, values: this.variants[i].values });
              }
            }
          }
        }
      },
      deep: true
    },

  },
  methods: {
    addVariantValueToSelectVariant: function(value, variantId) {
      for (let index = 0; index < this.selectedVariantsFormatted.length; index++) {
        var variant = this.selectedVariantsFormatted[index];
        if (variant.variantId == variantId) {
          for (var i = 0; i < variant.values.length; i++) {
            var _value = variant.values[i];
            if (_value.variant_value_id == value.variant_value_id) {
              variant.values.splice(i, 1);
              return;
            }
          }
          variant.values.push(Object.assign({}, value));
          return;
        }
      }

      this.selectedVariantsFormatted.push({ variantId: variantId, values: [Object.assign({}, value)] });
    },

    getVariantTypeName: function (variantId) {
      for (let index = 0; index < this.variants.length; index++) {
        var variant = this.variants[index];
        if (variant.id == variantId) {
          return variant.name;
        }
      }
    },

    getVariantValues: function (variantId) {
      for (let index = 0; index < this.variants.length; index++) {
        var variant = this.variants[index];
        if (variant.id == variantId) {
          return variant.values;
        }
      }
      return [];
    },

    getFormattedVariantValues: function (variantId) {
      for (let index = 0; index < this.selectedVariantsFormatted.length; index++) {
        var variant = this.selectedVariantsFormatted[index];
        if (variant.variantId == variantId) {
          return variant.values;
        }
      }
      return [];
    },

    isVariantSelected: function (variant_value_id, variantId) {
      var _values = this.getFormattedVariantValues(variantId);
      if ( typeof _values == 'undefined' || !_values.length ) return false;
      for (var i = 0; i < _values.length; i++) {
        if (_values[i].variant_value_id == variant_value_id) {
          return true;
        }
      }
    },

    // 检查商品表单输入正确性
    validateProducts: function () {
      for (var index = 0; index < this.products.length; index++) {
        var product = this.products[index];
        var selectedVariants = product.variants || {};
        var validated = true;
        var error = [];

        if (Object.keys(selectedVariants).length < this.selected_variants.length) {
          validated = false;
          error.push(i18n.error_variant_required);
        }

        if (product.sku == '') {
          validated = false;
          error.push(i18n.error_sku);
        }

        if (parseFloat(product.price) < 0) {
          validated = false;
          error.push(i18n.error_price);
        }

        if (parseInt(product.quantity) < 0) {
          validated = false;
          error.push(i18n.error_quantity);
        }

        this.products[index].validated = validated;
        this.products[index].error = error.join('/');
      }
    },

    imageSrc: function (image) {
      return 'index.php?route=seller/product/thumb&image=' + image;
    },

    // 检查是否有重复的 variant 组合
    validateDuplicatedVariant: function () {
      var segments = [];
      for (var index = 0; index < this.products.length; index++) {
        var segment = [];
        for (var variantValue in this.products[index].variants) {
          segment.push(variantValue + ':' + this.products[index].variants[variantValue]);
        }
        segments.push(segment.join('|'));
      }

      for (let index = 0; index < segments.length; index++) {
        var segment = segments[index];

        var count = 0;
        for (let index = 0; index < segments.length; index++) {
          var compareSegment = segments[index];
          if (segment === compareSegment) {
            count++;
          }
        }

        if (count > 1) {
          return false;
        }
      }

      return true;
    },

    // 检查是否有重复的 sku
    validateDuplicatedSku: function () {
      for (var i = 0; i < this.products.length - 1; i++) {
        var product = this.products[i];

        for (var j = i + 1; j < this.products.length; j++) {
          if (this.products[j].sku == product.sku) {
            return false;
          }
        }
      }

      return true;
    },

    typeSelectButtonClicked: function () {
      if (this.selected_variants.length < 1) {
        showToast(i18n.error_no_variants_selected);
        return;
      }

      for (let index = 0; index < this.products.length; index++) {
        for (var variantValue in this.products[index].variants) {
          if (this.selected_variants.indexOf(parseInt(variantValue)) === -1) {
            delete this.products[index].variants[variantValue];
          }
        }
      }

      this.validateProducts();
      ( this.isCurrentStep() ) ? this.currentStep = 2 : this.currentStep = 3;
    },

    // 点击上一步或下一步 跳转
    isCurrentStep: function () {
      var blotterAllow = 0;
      for (let index = 0; index < this.selected_variants.length; index++) {
        var sdVariants = this.selected_variants[index];
        for (var i = 0; i < this.variants.length; i++) {
          if ( this.variants[i].id == sdVariants ) {
            blotterAllow += this.variants[i].allow_rename;
          }
        }
      }
      return blotterAllow;
    },

    // 在第二步中只显示允许用户自定义的 variant 列
    isVariantList: function (variantId) {
      for (var i = 0; i < this.variants.length; i++) {
        if ( this.variants[i].id == variantId && this.variants[i].allow_rename ) {
          return true;
        }
      }
    },

    deleteButtonClicked: function (row) {
      // 删除主商品
      if (row == 0) {
        showConfirm(i18n.help_delete_master, i18n.button_confirm_delete, i18n.button_cancel, function () {
          app.parentProduct = app.products[row];
          app.products.splice(row, 1);
        });
      } else { // 删除子商品
        if (app.products[row].product_id < 1) {
          app.products.splice(row, 1);
          return;
        }

        showConfirm(i18n.text_confirm_delete, i18n.button_confirm_delete, i18n.button_cancel, function () {
          app.products.splice(row, 1);
        });
      }
    },

    deleteAllButtonClicked: function () {
      showConfirm(i18n.text_confirm_delete_all, i18n.button_confirm_delete, i18n.button_cancel, function () {
        app.parentProduct = app.products[0];
        app.products = [];
      });
    },

    addProductButtonClicked: function () {
      if (this.products.length == 0) { // 创建主商品
        var product = this.parentProduct;
      } else { // 复制主商品
        var product = JSON.parse(JSON.stringify(this.products[0]));
        product.product_id = 0;
        product.sku = '';
        product.status = 0;
      }

      this.products.push(product);
    },

    setAsParentProductButtonClicked: function (row) {
      showConfirm(i18n.text_confirm_make_master, i18n.button_make_master, i18n.button_cancel, function () {
        var oldParentProduct = JSON.parse(JSON.stringify(app.products[0]));
        oldParentProduct.product_id = app.products[row].product_id;

        // 交换主商品
        var newParentProduct = JSON.parse(JSON.stringify(app.products[row]));
        newParentProduct.product_id = app.products[0].product_id;
        app.products.splice(0, 1, newParentProduct);

        // 交换子商品
        app.products.splice(row, 1, oldParentProduct);
      });
    },

    saveProductButtonClicked: function () {
      // validate product
      for (var index = 0; index < this.products.length; index++) {
        if (this.products[index].validated == false) {
          showToast(i18n.error_form_error);
          return;
        }
      }

      // 检查是否有重复的 sku
      if (!this.validateDuplicatedSku()) {
        showToast(i18n.error_sku_duplicated);
        return;
      }

      // 检查是否有重复的 variant 组合
      if (!this.validateDuplicatedVariant()) {
        showToast(i18n.error_variant_duplicated);
        return;
      }

      showConfirm(i18n.text_confirm_save, i18n.button_save_changes, i18n.button_cancel, function () {
        var data = { products: app.products, selected: app.selected_variants, custom_variants: app.selectedVariantsFormatted };

        $.ajax({
          url: 'index.php?route=seller/product/variant_save&product_id=' + app.parentId,
          method: 'post',
          dataType: 'json',
          data: data,
          beforeSend: function () {
            showLoading();
          },
          success: function (json) {
            if (json['status'] !== 1) {
              showToast(json['message']);
              return;
            }

            showToast(i18n.text_save_success);
            var data = json['data'];

            app.products = data['products'];
          },
          error: function (xhr, errorType, error) {
            showToast(i18n.error_network);
          },
          complete: function () {
            closeLoading();
          }
        })
      });
    },

    checkAllVariantButtonClicked: function () {
      if (this.selected_variants.length == this.variants.length) {
        this.selected_variants = [];
        return;
      }

      this.selected_variants = [];
      for (let index = 0; index < this.variants.length; index++) {
        var group = this.variants[index];
        this.selected_variants.push(group.id);
      }
    },

    saveAsGroupButtonClicked: function () {
      showPrompt(function (name) {
        var variantIds = app.selected_variants;
        var name = name;

        $.ajax({
          url: 'index.php?route=catalog/variant/addGroup',
          method: 'post',
          dataType: 'json',
          data: { name: name, variant_ids: variantIds },
          beforeSend: function () {
            showLoading();
          },
          success: function (json) {
            if (json['status'] !== 1) {
              showToast(json['message']);
              return;
            }

            showToast(i18n.text_save_success);
            var data = json['data'];
            app.variant_groups.push({ group_id: data.group_id, name: name, variants: variantIds });
          },
          error: function (xhr, errorType, error) {
            showToast(i18n.error_network);
          },
          complete: function () {
            closeLoading();
          }
        });
      });
    },

    variantGroupButtonclicked: function (variants) {
      this.selected_variants = variants;
    },

    removeVariantGroupButtonclicked: function (groupId, row) {
      showConfirm(i18n.text_confirm_delete_group, i18n.button_confirm_delete, i18n.button_cancel, function () {
        $.ajax({
          url: 'index.php?route=seller/product/deleteGroup',
          method: 'post',
          dataType: 'json',
          data: { group_id: groupId },
          beforeSend: function () {
            showLoading();
          },
          success: function (json) {
            if (json['status'] !== 1) {
              showToast(json['message']);
              return;
            }

            showToast(i18n.text_delete_success);
            app.variant_groups.splice(row, 1);
          },
          error: function (xhr, errorType, error) {
            showToast(i18n.error_network);
          },
          complete: function () {
            closeLoading();
          }
        });
      });
    }
  }
})
