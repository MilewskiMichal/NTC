/*
 * NTC Andar - zachowania wspólne.
 *
 * Odpowiednik logiki, która w prototypie siedziała w komponentach Reacta:
 * przyklejony pasek nawigacji, liczniki w sekcji statystyk, makieta wysyłki
 * formularza. Zero zależności - to jest cały runtime strony.
 *
 * Każdy kawałek jest progresywny: bez JS-u pasek zostaje przezroczysty,
 * liczniki pokazują docelowe wartości wpisane w HTML, a formularz zachowuje
 * się jak zwykły formularz HTML.
 */

(function () {
	'use strict';

	var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	/* ------------------------------------------------------------ nawigacja */

	function initNav() {
		var nav = document.querySelector('.nav');

		// Wariant .nav--solid (kontakt) jest biały od początku, nie ma czego przełączać.
		if (!nav || nav.classList.contains('nav--solid')) {
			return;
		}

		var ticking = false;

		function apply() {
			nav.classList.toggle('scrolled', window.scrollY > 60);
			ticking = false;
		}

		window.addEventListener('scroll', function () {
			if (!ticking) {
				ticking = true;
				window.requestAnimationFrame(apply);
			}
		}, { passive: true });

		apply();
	}

	/* ---------------------------------------------------------- menu mobilne */

	function initMenu() {
		var nav = document.querySelector('.nav');
		var toggle = nav && nav.querySelector('.nav-toggle');

		if (!toggle) {
			return;
		}

		function setOpen(open) {
			nav.classList.toggle('open', open);
			toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
		}

		toggle.addEventListener('click', function () {
			setOpen(!nav.classList.contains('open'));
		});

		// Pozycja z listą podrzędną (Oferta) na wąskim ekranie i na dotyku
		// rozwija listę zamiast od razu przechodzić dalej. Na myszy wystarcza
		// najechanie, więc tam pierwsze kliknięcie prowadzi na podstronę.
		var dotyk = window.matchMedia('(hover: none)').matches;

		nav.addEventListener('click', function (event) {
			var rodzic = event.target.closest('.nav-links .menu-item-has-children > a');
			var waski = window.innerWidth <= 600;

			if (rodzic && (waski || dotyk)) {
				var pozycja = rodzic.parentNode;

				if (!pozycja.classList.contains('open')) {
					event.preventDefault();
					pozycja.classList.add('open');
					return;
				}
			}

			// Kliknięcie w pozycję menu prowadzi dalej - panel ma się zamknąć,
			// zwłaszcza przy linkach kotwiczących, które nie przeładowują strony.
			if (event.target.closest('.nav-menu a')) {
				setOpen(false);
			}
		});

		// Klik poza menu zwija rozwinięte listy.
		document.addEventListener('click', function (event) {
			if (event.target.closest('.nav-links .menu-item-has-children')) {
				return;
			}

			nav.querySelectorAll('.nav-links .menu-item-has-children.open')
				.forEach(function (el) { el.classList.remove('open'); });
		});

		document.addEventListener('keydown', function (event) {
			if (event.key !== 'Escape') {
				return;
			}

			nav.querySelectorAll('.nav-links .menu-item-has-children.open')
				.forEach(function (el) { el.classList.remove('open'); });

			if (nav.classList.contains('open')) {
				setOpen(false);
				toggle.focus();
			}
		});

		// Powrót na szeroki ekran przy otwartym panelu zostawiłby klasę .open,
		// przez co desktopowe menu dostałoby style panelu.
		window.addEventListener('resize', function () {
			if (window.innerWidth > 600) {
				setOpen(false);
			}
		});
	}

	/* ------------------------------------------------------------- liczniki */

	function countUp(el, target, duration) {
		var startTime = null;

		function step(ts) {
			if (startTime === null) {
				startTime = ts;
			}

			var progress = Math.min((ts - startTime) / duration, 1);
			// ease-out cubic - ten sam profil co w prototypie.
			var eased = 1 - Math.pow(1 - progress, 3);

			el.textContent = String(Math.round(eased * target));

			if (progress < 1) {
				window.requestAnimationFrame(step);
			}
		}

		window.requestAnimationFrame(step);
	}

	function initStats() {
		var values = document.querySelectorAll('[data-count-to]');

		if (!values.length) {
			return;
		}

		// Bez IntersectionObserver albo przy ograniczonym ruchu zostawiamy
		// wartości docelowe, które i tak są już w HTML-u.
		if (reduceMotion || !('IntersectionObserver' in window)) {
			return;
		}

		var section = values[0].closest('.stats');

		if (!section) {
			return;
		}

		var observer = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (!entry.isIntersecting) {
					return;
				}

				observer.disconnect();

				Array.prototype.forEach.call(values, function (el) {
					countUp(el, parseInt(el.getAttribute('data-count-to'), 10) || 0, 1600);
				});
			});
		}, { threshold: 0.3 });

		observer.observe(section);
	}

	/* ------------------------------------------------------------ formularze */

	function initForms() {
		var forms = document.querySelectorAll('[data-ntc-form]');

		Array.prototype.forEach.call(forms, function (form) {
			form.addEventListener('submit', function (event) {
				event.preventDefault();

				// novalidate w HTML-u wyłącza dymki przeglądarki przy wpisywaniu,
				// ale walidację i tak chcemy - wywołujemy ją ręcznie przy wysyłce.
				if (typeof form.reportValidity === 'function' && !form.reportValidity()) {
					return;
				}

				// Makieta: nic nie leci na serwer, pokazujemy potwierdzenie.
				// Podpięcie prawdziwej wysyłki = fetch() w tym miejscu albo
				// zdjęcie preventDefault i ustawienie action/method na formularzu.
				var wrap = form.parentNode;
				var sent = wrap.querySelector('[data-ntc-sent]');
				var hideSelector = form.getAttribute('data-ntc-hide');

				if (hideSelector) {
					var extra = wrap.querySelector(hideSelector);

					if (extra) {
						extra.hidden = true;
					}
				}

				form.hidden = true;

				if (sent) {
					sent.hidden = false;
					sent.setAttribute('tabindex', '-1');
					sent.focus();
				}
			});
		});
	}

	/* ------------------------------------------------------------------ start */

	function init() {
		initNav();
		initMenu();
		initStats();
		initForms();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
