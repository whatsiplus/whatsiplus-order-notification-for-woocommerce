var pointerPosition = 0;
var plugins = whatsiplusPlugins.plugins;

jQuery(function ($) {
    for (let [option_id, plugin_keywords] of Object.entries(plugins)) {
        // create div element for each plugins
        var $div = $('<div />').appendTo('body');
        $div.attr('id', `keyword-modal-${option_id}`);
        $div.attr('class', "modal");
        $div.attr('style', "display: none;");

        $(`#whatsiplus-open-keyword-${option_id}-\\[dummy\\]`).click(function (e) {
            const type = $(e.target).attr('data-attr-type');
            const target = $(e.target).attr('data-attr-target');

            pointerPosition = document.getElementById(target).selectionStart;

            const buildTable = function (keywords) {
                const chunkedKeywords = array_chunk(keywords, 3);

                let tableCode = '';
                chunkedKeywords.forEach(function (row, rowIndex) {
                    if (rowIndex === 0) {
                        tableCode += '<table class="widefat fixed striped"><tbody>';
                    }

                    tableCode += '<tr>';
                    row.forEach(function (col) {
                        tableCode += `<td class="column"><button class="button-link" onclick="whatsiplus_bind_text_to_field_automation('${target}', '[${col}]')">[${col}]</button></td>`;
                    });
                    tableCode += '</tr>';

                    if (rowIndex === chunkedKeywords.length - 1) {
                        tableCode += '</tbody></table>';
                    }
                });

                return tableCode;
            };

            $(`#keyword-modal-${option_id}`).off();
            $(`#keyword-modal-${option_id}`).on($.modal.AFTER_CLOSE, function () {
                document.getElementById(target).focus();
                document.getElementById(target).setSelectionRange(pointerPosition, pointerPosition);
            });

            let mainTable = '';
            for (let [key, value] of Object.entries(plugin_keywords)) {
                mainTable += `<h3>${capitalize_first_letter(key.replaceAll('_', ' '))}</h3>`;
                mainTable += buildTable(value);
            }

            mainTable += '<div style="margin-top: 10px"><small>*Press on keyword to add to message template</small></div>';

            $(`#keyword-modal-${option_id}`).html(mainTable);
            $(`#keyword-modal-${option_id}`).modal();
        });
    }
});

function capitalize_first_letter (str) {
    return (str + '').replace(/^([a-z])|\s+([a-z])/g, function ($1) {
        return $1.toUpperCase();
    });
}

function whatsiplus_bind_text_to_field_automation(target, keyword) {
    const startStr = document.getElementById(target).value.substring(0, pointerPosition);
    const endStr = document.getElementById(target).value.substring(pointerPosition);
    document.getElementById(target).value = startStr + keyword + endStr;
    pointerPosition += keyword.length;
}

function array_chunk(array, size) {
    const chunked_arr = [];
    let index = 0;
    while (index < array.length) {
        chunked_arr.push(array.slice(index, size + index));
        index += size;
    }
    return chunked_arr;
}
