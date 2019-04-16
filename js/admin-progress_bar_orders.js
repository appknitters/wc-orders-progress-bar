jQuery(document).ready(function ($) {
    var color = anwc_style['color'] ? 'background-color:' + anwc_style['color'] : '';
    var bgcolor = anwc_style['bgcolor'] ? 'background-color:' + anwc_style['bgcolor'] : '';
    var cols_count = getTableColsCount();
    $('table.posts tr.type-shop_order').each(function (i, el) {
        var theme = anwc_style['theme'];
        
        if (jQuery.inArray(theme, default_themes) >= 0) {
            var stages = anwc_stages;
            var stage = $(el).find('#opb_order_status').val();
            if ((stage in stages)) {
                var order_number = $(el).find('#opb_order_number').val();
                var data = _progress_bar(stages, stage);
                console.log(data);
                $(el).after('<tr id="progress_' + order_number + '" class="progressbar"><td colspan="'+cols_count+'"><div class="meter ' + theme + '" style="' + bgcolor + '" ><span style="width: ' + (data['scores'][stage] == 100 ? '' : data['scores'][stage]) + '%; ' + color + '"></span></div><div class="stage_names">' + data['html'] + '</div></td></tr>')
            }
        }
    });

});

