jQuery(document).ready(function ($) {
    var theme = anwc_style['theme'];
    if (jQuery.inArray(theme, default_themes) >= 0) {
        var stages = anwc_stages;//['Processing', 'Creation', 'Makeup Chair', '(Partially Shipped)', 'Shipped', 'Ready for Pickup'];
        var stage = $('#opb_order_status').val();
        var color = anwc_style['color'] ? 'background-color:' + anwc_style['color'] : '';
        var bgcolor = anwc_style['bgcolor'] ? 'background-color:' + anwc_style['bgcolor'] : '';
        if ((stage in stages)) {
            var order_number = $('#opb_order_number').val();
            var data = _progress_bar(stages, stage);
            console.log(data);
            $('.woocommerce-order-details__title').before('<p id="progress_bar"><div class="meter ' + theme + '" style="' + bgcolor + '" ><span style="width: ' + (data['scores'][stage] == 100 ? '' : data['scores'][stage]) + '%; ' + color + '"></span></div><div class="stage_names">' + data['html'] + '</div></p><br><br>');
        }
    }
});