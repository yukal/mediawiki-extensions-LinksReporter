/**
 * MediaWiki Resource Module
 *
 * @file
 * @ingroup Extensions
 * @version 1.0
 * @author Alexander Yukal <yukal@email.ua>
 * @license https://opensource.org/licenses/MIT MIT License
 */

mw.loader.using('oojs-ui-core').done(function () {
    $(function () {
        var CLS_MINUS = 'linksreporter-btn-minus';
        var CLS_PLUS  = 'linksreporter-btn-pluse';
        var CLS_ADDED = 'linksreporter-row-added';

        function updateFront(type, obj) {
            if (type == 'del') {
                $(obj).parent().removeClass(CLS_ADDED);
                $(obj).text('+').removeClass(CLS_MINUS).addClass(CLS_PLUS);
            }

            if (type == 'add') {
                $(obj).parent().addClass(CLS_ADDED);
                $(obj).text('-').removeClass(CLS_PLUS).addClass(CLS_MINUS);
            }
        }

        function processAjax(obj, type) {
            var token = mw.user.tokens.values.csrfToken;
            var aid = $(obj).data('mw-aid');

            $.ajax({
                method: 'POST',
                url: '/api.php?action=linksreporter&format=json',
                cache: false,
                data: { type, aid, token },
                // crossDomain: true,
                success: function(data, status, xhr) {
                    if (data.error) {
                        alert(data.error.code+'\n'+data.error.info);
                    } else {
                        updateFront(type, obj, data, status);
                    }
                },
                error: function(xhr, status, err) {
                    console.error(err);
                    console.error(status);
                },
            });
        }

        $(document).on('click', '.linksreporter-btn-pluse', function(e) {
            e.preventDefault();
            processAjax(this, 'add');
        });

        $(document).on('click', '.linksreporter-btn-minus', function(e) {
            e.preventDefault();
            processAjax(this, 'del');
        });

    });
});
