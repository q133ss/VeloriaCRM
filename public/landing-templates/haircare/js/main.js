/*
 * Pretty by Colorlib (CC BY 3.0). Trimmed from the original main.js: the parallax,
 * carousel, popup, counter and date-picker parts are gone because the page does
 * not use them; what is left is the full-height hero, the loader, the reveal on
 * scroll, the sticky navbar and the smooth scroll to sections.
 */
(function ($) {
	"use strict";

	var fullHeight = function () {
		$('.js-fullheight').css('height', $(window).height());
		$(window).resize(function () {
			$('.js-fullheight').css('height', $(window).height());
		});
	};
	fullHeight();

	// loader
	setTimeout(function () {
		$('#ftco-loader').removeClass('show');
	}, 1);

	// scroll: the navbar sticks after the hero
	$(window).scroll(function () {
		var st = $(this).scrollTop(),
			navbar = $('.ftco_navbar');

		if (st > 750) {
			if (!navbar.hasClass('scrolled')) navbar.addClass('scrolled');
		}
		if (st < 750) {
			if (navbar.hasClass('scrolled')) navbar.removeClass('scrolled sleep');
		}
		if (st > 800) {
			if (!navbar.hasClass('awake')) navbar.addClass('awake');
		}
		if (st < 800) {
			if (navbar.hasClass('awake')) {
				navbar.removeClass('awake');
				navbar.addClass('sleep');
			}
		}
	});

	// reveal on scroll
	$('.ftco-animate').waypoint(function (direction) {
		if (direction === 'down' && !$(this.element).hasClass('ftco-animated')) {
			$(this.element).addClass('item-animate');
			setTimeout(function () {
				$('body .ftco-animate.item-animate').each(function (k) {
					var el = $(this);
					setTimeout(function () {
						var effect = el.data('animate-effect');
						if (effect === 'fadeIn') {
							el.addClass('fadeIn ftco-animated');
						} else if (effect === 'fadeInLeft') {
							el.addClass('fadeInLeft ftco-animated');
						} else if (effect === 'fadeInRight') {
							el.addClass('fadeInRight ftco-animated');
						} else {
							el.addClass('fadeInUp ftco-animated');
						}
						el.removeClass('item-animate');
					}, k * 50);
				});
			}, 100);
		}
	}, { offset: '95%' });

	// smooth scroll to a section, closing the mobile menu
	$(".smoothscroll[href^='#'], #ftco-nav ul li a[href^='#']").on('click', function (e) {
		e.preventDefault();

		var hash = this.hash,
			navToggler = $('.navbar-toggler');

		$('html, body').animate({ scrollTop: $(hash).offset().top }, 700);

		if (navToggler.is(':visible') && $('#ftco-nav').hasClass('show')) {
			navToggler.click();
		}
	});
})(jQuery);
