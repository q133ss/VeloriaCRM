(function ($) {
    "use strict";

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
            $('html, body').animate({scrollTop: $(id).offset().top - 10}, 700, 'easeInOutExpo');
        }
    });
})(jQuery);
