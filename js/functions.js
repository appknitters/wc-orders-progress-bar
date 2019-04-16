
// Contains common functions for progress bar.

var default_themes = ['', 'stripes', 'glow', 'shine'];

//returns progress bar 

function _progress_bar(stages, stage) {
    
        var stage_scores = {};
        var stage_name_html = '';
        var x = 0;
        for (i in stages) {
            var unit = 100 / (Object.keys(stages).length - 1);
            stage_scores[i] = (unit * x) ? unit * x : 7;
            stage_name_html += '<span class="' + (stage == i ? 'current' : '') + '" style="right:' + (100 - (unit * x)) + '%">' + stages[i] + '</span>';
            x++;
        }
        console.log(stage_scores);

        var data = {};
        data['html'] = stage_name_html;
        data['scores'] = stage_scores;
        //alert(stage);
    return data;
}

function getTableColsCount() {
    var colCount = 0;
    jQuery('tbody tr:nth-child(1) td').each(function (i, el) {
        if (!jQuery(el).hasClass('hidden')) {
            colCount++;
        }

    });

    return colCount;
}