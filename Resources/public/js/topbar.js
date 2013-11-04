(function () {
    'use strict';

    var resizeWindow;

    function responsiveTopBar() {
        $('#top_bar').removeClass('break');

        if ($('#top_bar .navbar-collapse').outerHeight() > 55) {
            $('#top_bar').addClass('break');
        }
    }

    $(window).on('resize', function () {
        clearTimeout(resizeWindow);
        resizeWindow = setTimeout(responsiveTopBar, 200);
    });

    $(document).ready(function () {
        responsiveTopBar();
    });

}());
