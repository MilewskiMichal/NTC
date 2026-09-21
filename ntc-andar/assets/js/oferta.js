/*
 * NTC Andar - podstrona oferty.
 *
 * Dwie rzeczy: zakładki nad sekcjami kategorii (płynne przewijanie plus
 * podświetlanie tej, którą właśnie widać) i filtrowanie tabel po nazwie
 * substancji albo kraju pochodzenia.
 *
 * Teksty do wstrzyknięcia (liczba pozycji, komunikat o braku wyników)
 * przychodzą z PHP przez wp_localize_script jako ntcOferta - żeby nie
 * duplikować słownika w JS-ie.
 */

(function () {
	'use strict';

	var strings = window.ntcOferta || { countLabel: '', noResults: 'Brak wyników' };

	/* ------------------------------------------------------------- zakładki */

	function initTabs() {
		var bar = document.querySelector('[data-ntc-tabs]');

		if (!bar) {
			return;
		}

		var tabs = Array.prototype.slice.call(bar.querySelectorAll('.tab-btn'));

		var sections = tabs.map(function (tab) {
			return document.getElementById(tab.getAttribute('data-target'));
		});

		function setActive(index) {
			tabs.forEach(function (tab, i) {
				tab.classList.toggle('active', i === index);
			});
		}

		tabs.forEach(function (tab, index) {
			tab.addEventListener('click', function (event) {
				var section = sections[index];

				if (!section) {
					return;
				}

				event.preventDefault();
				setActive(index);

				// -80px, żeby przyklejony pasek nawigacji nie zasłonił nagłówka.
				window.scrollTo({ top: section.offsetTop - 80, behavior: 'smooth' });
			});
		});

		// Podświetlanie przy przewijaniu: bierzemy ostatnią sekcję, której
		// początek minęliśmy. Iterujemy od końca, więc pierwsze trafienie jest
		// tym właściwym.
		var ticking = false;

		function syncOnScroll() {
			for (var i = sections.length - 1; i >= 0; i--) {
				var section = sections[i];

				if (section && window.scrollY >= section.offsetTop - 200) {
					setActive(i);
					break;
				}
			}

			ticking = false;
		}

		window.addEventListener('scroll', function () {
			if (!ticking) {
				ticking = true;
				window.requestAnimationFrame(syncOnScroll);
			}
		}, { passive: true });

		syncOnScroll();
	}

	/* ---------------------------------------------------------- wyszukiwarka */

	function initTables() {
		var blocks = document.querySelectorAll('[data-ntc-table]');

		Array.prototype.forEach.call(blocks, function (block) {
			var input = block.querySelector('[data-ntc-search]');
			var count = block.querySelector('[data-ntc-count]');
			var empty = block.querySelector('[data-ntc-empty]');
			var rows = Array.prototype.slice.call(block.querySelectorAll('[data-ntc-row]'));

			// 0 = bez stronicowania, cała lista naraz (podstrona kategorii).
			var perPage = parseInt(block.getAttribute('data-per-page'), 10) || 0;
			var pager = block.querySelector('[data-ntc-pager]');
			var pagerState = block.querySelector('[data-ntc-pager-state]');
			var prevBtn = block.querySelector('[data-ntc-prev]');
			var nextBtn = block.querySelector('[data-ntc-next]');
			var scroller = block.querySelector('.table-wrap');
			var page = 1;

			if (!input) {
				return;
			}

			function matching() {
				var query = input.value.trim().toLowerCase();

				return rows.filter(function (row) {
					// data-search jest już zlowercase'owany po stronie PHP.
					return !query || row.getAttribute('data-search').indexOf(query) !== -1;
				});
			}

			function render() {
				var hits = matching();
				var pages = perPage ? Math.max(1, Math.ceil(hits.length / perPage)) : 1;

				if (page > pages) {
					page = pages;
				}

				var from = perPage ? (page - 1) * perPage : 0;
				var to = perPage ? from + perPage : hits.length;

				rows.forEach(function (row) {
					row.hidden = true;
				});

				hits.slice(from, to).forEach(function (row) {
					row.hidden = false;
				});

				if (count) {
					count.textContent = hits.length + ' ' + strings.countLabel;
				}

				if (empty) {
					empty.hidden = hits.length !== 0;
					empty.querySelector('td').textContent = strings.noResults + ' „' + input.value + '”';
				}

				if (pager) {
					// Pasek stronicowania znika, gdy wyniki i tak mieszczą się
					// na jednej stronie - inaczej wisiałby martwy pod tabelą.
					pager.hidden = hits.length <= perPage;
					prevBtn.disabled = page <= 1;
					nextBtn.disabled = page >= pages;

					if (pagerState) {
						pagerState.textContent = strings.pageLabel + ' ' + page + ' ' + strings.ofLabel + ' ' + pages;
					}
				}
			}

			function go(delta) {
				page += delta;
				render();

				// Po zmianie strony wracamy na górę listy, ale przewijamy samo
				// okno tabeli. Przewinięcie całej strony wyrzuciłoby czytającego
				// z sekcji, w której właśnie jest.
				if (scroller) {
					scroller.scrollTop = 0;
				}
			}

			input.addEventListener('input', function () {
				page = 1;
				render();
			});

			if (prevBtn) {
				prevBtn.addEventListener('click', function () {
					go(-1);
				});
			}

			if (nextBtn) {
				nextBtn.addEventListener('click', function () {
					go(1);
				});
			}

			render();
		});
	}

	function init() {
		initTabs();
		initTables();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
