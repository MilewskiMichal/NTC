/*
 * NTC Andar - pasek wersji językowej nad treścią w edytorze.
 *
 * Tekst i odnośniki składa PHP (inc/kokpit.php), tutaj tylko pokazujemy je
 * jako stały komunikat edytora. Zamknąć się go nie da celowo: informacja,
 * którą wersję językową się poprawia, ma być widoczna przez całą edycję.
 */

( function ( wp, dane ) {
	'use strict';

	if ( ! wp || ! wp.data || ! wp.domReady || ! dane ) {
		return;
	}

	wp.domReady( function () {
		wp.data.dispatch( 'core/notices' ).createNotice( dane.typ || 'info', dane.tekst, {
			id: 'ntc-jezyk',
			isDismissible: false,
			actions: ( dane.akcje || [] ).map( function ( akcja ) {
				return { label: akcja.label, url: akcja.url };
			} )
		} );
	} );
} )( window.wp, window.ntcJezyk );
