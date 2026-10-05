(function ($) {
    "use strict";

    // Spinner
    setTimeout(function () { $('#spinner').removeClass('show'); }, 1);

    // Initiate the wowjs
    new WOW().init();

    // Navbar on scrolling
    $(window).scroll(function () {
        if ($(this).scrollTop() > 300) {
            $('.navbar').fadeIn('slow').css('display', 'flex');
        } else {
            $('.navbar').fadeOut('slow').css('display', 'none');
        }
    });

    // Smooth scrolling on the navbar links
    $(".navbar-nav a").on('click', function (event) {
        if (this.hash !== "") {
            event.preventDefault();
            $('html, body').animate({ scrollTop: $(this.hash).offset().top - 45 }, 1500, 'easeInOutExpo');
            $('.navbar-nav .active').removeClass('active');
            $(this).closest('a').addClass('active');
        }
    });

    // Back to top button
    $(window).scroll(function () {
        if ($(this).scrollTop() > 300) {
            $('.back-to-top').fadeIn('slow');
        } else {
            $('.back-to-top').fadeOut('slow');
        }
    });
    $('.back-to-top').click(function () {
        $('html, body').animate({ scrollTop: 0 }, 1500, 'easeInOutExpo');
        return false;
    });

    // Every in-page "book" link scrolls to the form
    $('a[href="#contact"]').not('.navbar-nav a').on('click', function (event) {
        event.preventDefault();
        $('html, body').animate({ scrollTop: $('#contact').offset().top - 45 }, 800, 'easeInOutExpo');
    });
})(jQuery);
