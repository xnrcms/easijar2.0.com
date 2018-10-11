$(document).ready(function () {
    $('.multi-filter-wrapper .btn-more').on('click', function(event) {
        var count = $(this).data('count');
        $(this).siblings('ul').find('li:gt(' + count + ')').toggleClass('hidden');
        $(this).toggleClass('active');
    });

    $('.multi-filter-wrapper .filter-stock-section input[type=\'checkbox\']').change(function () {
        filterChanged('stock', 0, $(this).prop('checked'));
    });

    $('.multi-filter-wrapper .filter-brand-section input[type=\'checkbox\']').change(function () {
        var id = parseInt($(this).val());
        if (id > 0) {
            filterChanged('brand', id);
        }
    });

    $('.multi-filter-wrapper .filter-variant-section input[type=\'checkbox\']').change(function () {
        var id = parseInt($(this).val());
        if (id > 0) {
            filterChanged('variant', id);
        }
    });

    $('.multi-filter-wrapper .filter-option-section input[type=\'checkbox\']').change(function () {
        var id = parseInt($(this).val());
        if (id > 0) {
            filterChanged('option', id);
        }
    });

    $('.multi-filter-wrapper .filter-attribute-section input[type=\'checkbox\']').change(function () {
        var id = parseInt($(this).data('id'));
        var attribute = $(this).val();
        if (id > 0 && attribute.length > 0) {
            filterChanged('attribute', id, attribute);
        }
    });

    if ($('.multi-filter-wrapper .filter-price-section .price-slider').length) {
        var slider = $('.multi-filter-wrapper .filter-price-section .price-slider');

        slider.slider({
            range: true,
            min: slider.data('min'),
            max: slider.data('max'),
            values: [slider.data('start'), slider.data('end')],
            slide: function(event, ui) {
                $('input[name="price_range_min"]').val(ui.values[0]);
                $('input[name="price_range_max"]').val(ui.values[1]);
            },
            stop: function(event, ui) {
                filterChanged('price', 0, [ui.values[0], ui.values[1]]);
            }
        });
    }

    $(document).keyup(function (e) {
        if (e.keyCode == 13) { // Enter
            if ($('.multi-filter-wrapper .filter-price-section input[type="number"]').is(':focus')) {
                var min = parseInt($('.filter-price-section input[name="price_range_min"]').val());
                var max = parseInt($('.filter-price-section input[name="price_range_max"]').val());
                $('.multi-filter-wrapper .filter-price-section .price-slider').slider('values', [min, max]);
                filterChanged('price', 0, [min, max]);
            }
        }
    });

    if (filter.mobile) {
        $('body .btn-multi-filter').on('click', function () {
            $('.multi-filter-layer').toggleClass('active');
            $('body').toggleClass('body-overflow');
        });

        $(document).on('click', function (e) {
            if ($(e.target).hasClass('body-overflow') && $('body').hasClass('body-overflow') && $('body .multi-filter-layer.active').length) {
                $('.multi-filter-layer').toggleClass('active');
                $('body').toggleClass('body-overflow');
            }
        });
    }
});

function filterChanged(type, id, extra) {
    if (type == 'keyword') {
        filter.selected_keyword = '';
    } else if (type == 'stock') {
        filter.selected_in_stock = extra;
    } else if (type == 'brand') {
        if (filter.selected_brands.indexOf(id) >= 0) {
            filter.selected_brands.splice(filter.selected_brands.indexOf(id), 1);
        } else {
            filter.selected_brands.push(id);
            filter.selected_brands.sort(function (a, b) { return a - b; });
        }
    } else if (type == 'option') {
        if (filter.selected_options.indexOf(id) >= 0) {
            filter.selected_options.splice(filter.selected_options.indexOf(id), 1);
        } else {
            filter.selected_options.push(id);
            filter.selected_options.sort(function (a, b) { return a - b; });
        }
    } else if (type == 'variant') {
        if (filter.selected_variants.indexOf(id) >= 0) {
            filter.selected_variants.splice(filter.selected_variants.indexOf(id), 1);
        } else {
            filter.selected_variants.push(id);
            filter.selected_variants.sort(function (a, b) { return a - b; });
        }
    } else if (type == 'attribute') {
        attribute_id = id;
        attribute_text = extra;
        if (filter.selected_attributes[attribute_id]) {
            attribute_group = filter.selected_attributes[attribute_id];
            if (attribute_group.indexOf(attribute_text) >= 0) {
                attribute_group.splice(attribute_group.indexOf(attribute_text), 1);
            } else {
                attribute_group.push(attribute_text);
            }

            filter.selected_attributes[attribute_id] = attribute_group;
        } else {
            filter.selected_attributes[attribute_id] = [attribute_text];
        }
    } else if (type == 'price') {
        filter.selected_price_range = [extra[0], extra[1]];
    }

    submitFilter();
}

function submitFilter() {
    var href = filter.href;
    if (filter.selected_in_stock) {
        href += '&in_stock=1';
    }

    if (filter.selected_brands.length > 0) {
        href += '&brand=' + filter.selected_brands.join('|');
    }

    if (filter.selected_options.length > 0) {
        href += '&option=' + filter.selected_options.join('|');
    }

    if (filter.selected_variants.length > 0) {
        href += '&variant=' + filter.selected_variants.join('|');
    }

    if (filter.selected_keyword.length > 0) {
        href += '&keyword=' + filter.selected_keyword;
    }

    if (filter.selected_price_range.length > 0) {
        href += '&price=' + filter.selected_price_range[0] + '|' + filter.selected_price_range[1];
    }

    var attrs = [];
    for (var attribute_id in filter.selected_attributes) {
        var attribute_group = filter.selected_attributes[attribute_id];
        if (attribute_group.length > 0) {
            for (var i = 0; i < attribute_group.length; i++) {
                attrs.push(attribute_id + ':' + attribute_group[i]);
            }
        }
    }
    if (attrs.length > 0) {
        href += '&attr=' + attrs.join('|');
    }

    if (filter.mobile) {
        layer.open({
            type: 3,
            scrollbar: false
        });
    }

    layer.load(1, {
      shade: [0.3,'#fff']
    });

    location = href;
}

