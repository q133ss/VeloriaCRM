(function ($) {
    "use strict";

    // Spinner
    setTimeout(function () { $('#spinner').removeClass('show'); }, 1);

    // Initiate the wowjs
    new WOW().init();

    // Sticky Navbar
    $(window).scroll(function () {
        if ($(this).scrollTop() > 300) {
            $('.sticky-top').addClass('bg-primary shadow-sm').css('top', '0px');
        } else {
            $('.sticky-top').removeClass('bg-primary shadow-sm').css('top', '-150px');
        }
    });

    // Back to top button
    $(window).scroll(function () {
        if ($(this).scrollTop() > 100) {
            $('.back-to-top').fadeIn('slow');
        } else {
            $('.back-to-top').fadeOut('slow');
        }
    });
    $('.back-to-top').click(function () {
        $('html, body').animate({scrollTop: 0}, 1500, 'easeInOutExpo');
        return false;
    });

    // Anchor links scroll smoothly
    $('a[href^="#"]').not('.back-to-top').on('click', function (event) {
        var id = this.hash;
        if (id && $(id).length) {
            event.preventDefault();
            $('html, body').animate({scrollTop: $(id).offset().top - 60}, 700, 'easeInOutExpo');
        }
    });

    // Countdown to the end of the offer (data-end holds the deadline)
    var box = document.getElementById('cdt');
    if (box) {
        var end = new Date(box.getAttribute('data-end')).getTime();
        var tick = function () {
            var left = Math.max(0, Math.floor((end - Date.now()) / 1000));
            $('#cdt-days').text(Math.floor(left / 86400));
            $('#cdt-hours').text(Math.floor(left % 86400 / 3600));
            $('#cdt-minutes').text(Math.floor(left % 3600 / 60));
            $('#cdt-seconds').text(left % 60);
            if (left > 0) { setTimeout(tick, 1000); }
        };
        tick();
    }
})(jQuery);
