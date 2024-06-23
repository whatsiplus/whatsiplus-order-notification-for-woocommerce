(function($) {
    var keywordArr = <?php echo json_encode($keywordArr); ?>;

    $(document).ready(function() {
        var $div = $('<div />').appendTo('body');
        $div.attr('id', 'keyword-modal');
        $div.addClass('modal');
        $div.css('display', 'none');

        $('#whatsiplus-open-keyword\\[dummy\\]').click(function(e) {
            const target = $(e.target).attr('data-attr-target');
            pointerPosition = $(target).prop('selectionStart');

            const buildTable = function(keywords) {
                const chunkedKeywords = keywords.array_chunk(3);
                let tableCode = '';

                chunkedKeywords.forEach(function(row, rowIndex) {
                    if (rowIndex === 0) {
                        tableCode += '<table class="widefat fixed striped"><tbody>';
                    }

                    tableCode += '<tr>';
                    row.forEach(function(col) {
                        tableCode += '<td class="column"><button class="button-link" onclick="whatsiplus_bind_text_to_field(\'' + target + '\', \'[' + col + ']\')">[' + col + ']</button></td>';
                    });
                    tableCode += '</tr>';

                    if (rowIndex === chunkedKeywords.length - 1) {
                        tableCode += '</tbody></table>';
                    }
                });

                return tableCode;
            };

            $('#keyword-modal').off();
            $('#keyword-modal').on('hidden.bs.modal', function() {
                document.getElementById(target).focus();
                document.getElementById(target).setSelectionRange(pointerPosition, pointerPosition);
            });

            let mainTable = '';
            for (let [key, value] of Object.entries(keywordArr)) {
                mainTable += '<h3>' + key + '</h3>';
                mainTable += buildTable(value);
            }

            mainTable += '<div style="margin-top: 10px"><small>*برای افزودن به قالب پیام بر روی کلیدواژه کلیک کنید</small></div>';

            $('#keyword-modal').html(mainTable);
            $('#keyword-modal').modal();
        });

        $('.button-link').click(function(e) {
            console.log("Click");
            console.log(e);
        });
    });
})(jQuery);
