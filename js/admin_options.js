jQuery(document).ready(function($) {
  $("#color,#bgcolor").spectrum({
    showInput: true,
    preferredFormat: "hex",
    clickoutFiresChange: true,
    cancelText: "cancel"
  });
  $("#all_statuses, #selected_statuses")
    .sortable({
      connectWith: ".connected",
      update: function(event, ui) {
        $("#wc_statuses option").remove();
        var html = "";
        $("ul#selected_statuses li").each(function(i, el) {
          var title = $(el)
            .text()
            .trim();
          var key = $(el).attr("data-id");
          console.log(title);
          html +=
            '<input type="hidden" value="' +
            title +
            '" name="anwc_order_progressbar[wc_statuses][' +
            key +
            ']">';
        });
        $("#wc_statuses").html(html);
        $("#theme").change();
      }
    })
    .disableSelection();

  $("input.button-primary").after(
    '<a class="button anwc_upgrade sb"  href="https://codecanyon.net/item/woocommerce-orders-progress-bar/20612363?ref=appknitters">Buy PRO version</a>'
  );
  $(".is_page").click(function() {
    var checked = $(this).is(":checked");
    if (checked) {
      $(this)
        .parent()
        .next(".placements")
        .show("slow");
    } else {
      $(this)
        .parent()
        .next(".placements")
        .hide("slow");
    }
  });
  $(".is_page:checked")
    .parent()
    .next(".placements")
    .show("slow");
  $("form input,form select").change(function() {
    preview();
  });
  preview();
});

function preview() {
  //jQuery('#anwcpb_preview').html('');

  var stages = {};
  var color = jQuery("#color").val();
  var bgcolor = jQuery("#bgcolor").val();
  var theme = jQuery("#theme").val();
  var border = jQuery("#border").val();
  var border_styles = { corners: 0, rounded: 10, circle: 20 };
  if (jQuery.inArray(theme, default_themes) >= 0) {
    jQuery("#anwcpb_preview").html("");

    stages = get_stages_object();
    console.log(stages);
    var stage_key = Object.keys(stages)[Object.keys(stages).length - 2];
    var stage = stages[stage_key];
    console.log(stage_key);
    if (stage_key in stages) {
      var stage_scores = {};
      var stage_name_html = "";
      var x = 0;
      for (i in stages) {
        var unit = 100 / (Object.keys(stages).length - 1);
        stage_scores[stages[i]] = unit * x ? unit * x : 7;
        stage_name_html +=
          '<span class="' +
          (stage == stages[i] ? "current" : "") +
          '" style="right:' +
          (100 - unit * x) +
          '%">' +
          stages[i] +
          "</span>";
        x++;
      }
      console.log(stage_scores);
      var order_number = "#11"; //$('mark.order-number').text();
      order_number = order_number.trim();
      order_number = order_number.replace("#", "");
      //alert(stage);
      jQuery("#anwcpb_preview").append(
        "<style id='anwc_progress_bar-inline-css' type='text/css'>.meter:after {border-color:" +
          shadeColor(color, -0.3) +
          " !important;-moz-border-radius: " +
          border_styles[border] +
          "px;-webkit-border-radius: " +
          border_styles[border] +
          "px;border-radius: " +
          border_styles[border] +
          "px; } .meter span:after{background:" +
          shadeColor(color, -0.3) +
          " !important;-moz-border-radius: " +
          border_styles[border] +
          "px;-webkit-border-radius: " +
          border_styles[border] +
          "px;border-radius: " +
          border_styles[border] +
          "px; }.meter{ background:" +
          bgcolor +
          ";-moz-border-radius: " +
          border_styles[border] +
          "px;-webkit-border-radius: " +
          border_styles[border] +
          "px;border-radius: " +
          border_styles[border] +
          "px; } .meter span{-moz-border-radius: " +
          border_styles[border] +
          "px;-webkit-border-radius: " +
          border_styles[border] +
          "px;border-radius: " +
          border_styles[border] +
          "px; }</style>"
      );
      jQuery("#anwcpb_preview").append(
        '<p id="progress_bar"><div class="meter ' +
          theme +
          '"><span style="width: ' +
          (stage_scores[stage] == 100 ? "" : stage_scores[stage]) +
          "%;background-color:" +
          color +
          '"></span></div><div class="stage_names">' +
          stage_name_html +
          "</div></p><br><br>"
      );
    }
  }
}

function shadeColor(color, percent) {
  var f = parseInt(color.slice(1), 16),
    t = percent < 0 ? 0 : 255,
    p = percent < 0 ? percent * -1 : percent,
    R = f >> 16,
    G = (f >> 8) & 0x00ff,
    B = f & 0x0000ff;
  return (
    "#" +
    (
      0x1000000 +
      (Math.round((t - R) * p) + R) * 0x10000 +
      (Math.round((t - G) * p) + G) * 0x100 +
      (Math.round((t - B) * p) + B)
    )
      .toString(16)
      .slice(1)
  );
}

function get_status_key(el) {
  var key = jQuery(el).attr("name");
  key = key.replace("anwc_order_progressbar[wc_statuses]", "");
  key = key.replace("[", "");
  key = key.replace("]", "");
  return key;
}

function get_stages_object() {
  var stages = {};
  jQuery("#wc_statuses input").each(function(i, el) {
    var key = get_status_key(el);
    var status = jQuery(el).val();
    stages[key] = status;
  });
  return stages;
}
