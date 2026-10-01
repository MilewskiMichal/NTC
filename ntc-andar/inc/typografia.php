<?php
/**
 * Mikrotypografia polska.
 *
 * Polski skład nie zostawia na końcu wiersza samotnych spójników i przyimków
 * ani pojedynczego słowa w ostatnim wierszu akapitu. Ręczne wstawianie twardych
 * spacji w treści byłoby nie do utrzymania - redakcja musiałaby o tym pamiętać
 * przy każdej zmianie tekstu, a i tak łamanie zależy od szerokości okna.
 * Dlatego robi to motyw, już na gotowym HTML-u sekcji.
 *
 * @package NTC_Andar
 */

defined( 'ABSPATH' ) || exit;

/**
 * Słowa, które nie zostają na końcu wiersza.
 *
 * Jednoliterowe są obowiązkowe w polskiej typografii. Reszta to najczęstsze
 * krótkie przyimki i spójniki - te, które najbardziej rzucają się w oczy,
 * gdy zawisną na końcu wiersza.
 *
 * @return string Gotowa alternatywa do wyrażenia regularnego.
 */
function ntc_typo_slowa() {
	$slowa = array(
		'a', 'i', 'o', 'u', 'w', 'z',
		'do', 'na', 'od', 'po', 'za', 'ze', 'we', 'ku', 'by', 'że', 'aż', 'ni',
		'dla', 'lub', 'nad', 'pod', 'bez', 'oraz', 'przy',
	);

	/**
	 * Pozwala dołożyć albo odjąć słowa bez ruszania motywu.
	 *
	 * @param array $slowa Lista słów.
	 */
	$slowa = apply_filters( 'ntc_typo_slowa', $slowa );

	return implode( '|', array_map( 'preg_quote', $slowa ) );
}

/**
 * Najdłuższe słowo, które wolno dokleić do poprzedniego jako ostatnie w tekście.
 *
 * Doklejenie długiego wyrazu potrafi wypchnąć cały poprzedni wyraz do nowego
 * wiersza i zamiast jednej sieroty robią się dwie. Krótkie doklejają się bez
 * szkody dla łamania.
 */
const NTC_TYPO_MAX_SIEROTA = 12;

/** Najdłuższa para ostatnich słów (w znakach), którą wolno skleić. */
const NTC_TYPO_MAX_PARA = 20;

/**
 * Wstawia twarde spacje w pojedynczym fragmencie tekstu.
 *
 * @param string $tekst Tekst bez znaczników.
 * @param bool   $koniec Czy to ostatni fragment tekstowy elementu.
 * @return string
 */
function ntc_typo_tekst( $tekst, $koniec = false ) {
	$slowa = ntc_typo_slowa();

	// Twarda spacja po "a", "i", "w" to reguła polskiego składu. W angielskim
	// "a" i "I" to zupełnie inne słowa i sklejanie ich z następnym robiłoby
	// dziury w wierszach - zostaje tylko pilnowanie sierot na końcu akapitu.
	if ( function_exists( 'ntc_is_en' ) && ntc_is_en() ) {
		$slowa = '';
	}

	// Krótkie słowo + spacja -> krótkie słowo + twarda spacja. Wymagamy
	// początku tekstu albo białego znaku przed, żeby nie trafić w końcówkę
	// dłuższego wyrazu ("tego" nie kończy się przyimkiem "o").
	if ( '' !== $slowa ) {
		$tekst = preg_replace(
			'/(^|[\s\x{00A0}(„"\x{2018}\x{201E}])(' . $slowa . ')[ \t]+/iu',
			'$1$2' . "\xc2\xa0",
			$tekst
		);
	}

	// Zakresy liczb ("90-95%", "20% - 25%") nie rozpadają się na dwa wiersze.
	// Po dywizie stoi niewidoczny łącznik słów (U+2060), a spacje wokół
	// półpauzy w zakresie stają się twarde. Dotyczy obu języków.
	$tekst = preg_replace( '/(\d)-(?=\d)/u', '$1-' . "\u{2060}", $tekst );
	$tekst = preg_replace( '/(\d%?)[ \t]+([-\x{2013}])[ \t]+(?=\d)/u', '$1' . "\xc2\xa0" . '$2' . "\xc2\xa0", $tekst );

	// Znak porównania trzyma się liczby: "> 95%" nie zostawia ">" na końcu wiersza.
	$tekst = preg_replace( '/(&gt;|&lt;|[<>\x{2264}\x{2265}])[ \t]+(?=\d)/u', '$1' . "\xc2\xa0", $tekst );

	if ( ! $koniec ) {
		return $tekst;
	}

	// Ostatnie dwa słowa razem, żeby końcówka zdania nie spadła sama do
	// nowego wiersza. Tylko gdy ostatnie słowo jest krótkie.
	// Para nie może być za długa: dwa długie wyrazy sklejone twardą spacją
	// ("składników bioaktywnych") nie mieszczą się w kolumnie nagłówka obok
	// zdjęcia i wychodzą poza nią.
	return preg_replace_callback(
		'/(\S+)[ \t]+(\S+)\s*$/u',
		function ( $m ) {
			$dl = function ( $s ) {
				$s = wp_strip_all_tags( $s );
				return function_exists( 'mb_strlen' ) ? mb_strlen( $s ) : strlen( $s );
			};

			return ( $dl( $m[2] ) <= NTC_TYPO_MAX_SIEROTA && $dl( $m[1] ) + $dl( $m[2] ) <= NTC_TYPO_MAX_PARA )
				? $m[1] . "\xc2\xa0" . $m[2]
				: $m[0];
		},
		$tekst
	);
}

/**
 * Przepuszcza HTML sekcji przez mikrotypografię, ruszając wyłącznie tekst.
 *
 * @param string $html Gotowy HTML.
 * @return string
 */
function ntc_typo( $html ) {
	$html = (string) $html;

	if ( '' === trim( $html ) || false === strpos( $html, ' ' ) ) {
		return $html;
	}

	$czesci = preg_split( '/(<[^>]*>)/u', $html, -1, PREG_SPLIT_DELIM_CAPTURE );

	if ( ! $czesci ) {
		return $html;
	}

	// Wnętrze tych elementów to kod, nie tekst do składu.
	$pomijane = 0;

	// W komórkach tabel sieroty nie domykamy: kolumny są wąskie i sklejenie
	// dwóch ostatnich słów robi z całej komórki jeden nierozerwalny ciąg
	// ("zawartość 90-95%" zamiast "zawartość" i pod spodem "90-95%").
	$w_komorce = 0;

	// Ostatni fragment tekstowy w obrębie elementu dostaje regułę sieroty,
	// więc trzeba wiedzieć, gdzie kończy się tekst przed kolejnym znacznikiem.
	foreach ( $czesci as $i => $czesc ) {
		if ( '' === $czesc ) {
			continue;
		}

		if ( '<' === $czesc[0] ) {
			if ( preg_match( '#^<\s*(script|style|textarea)\b#i', $czesc ) ) {
				++$pomijane;
			} elseif ( preg_match( '#^<\s*/\s*(script|style|textarea)#i', $czesc ) && $pomijane > 0 ) {
				--$pomijane;
			} elseif ( preg_match( '#^<\s*t[dh]\b#i', $czesc ) ) {
				++$w_komorce;
			} elseif ( preg_match( '#^<\s*/\s*t[dh]\s*>#i', $czesc ) && $w_komorce > 0 ) {
				--$w_komorce;
			}

			continue;
		}

		if ( $pomijane ) {
			continue;
		}

		// Sierotę domykamy tylko wtedy, gdy zaraz potem element się zamyka -
		// w środku zdania przerwanego znacznikiem <em> tekst leci dalej.
		$nastepny = isset( $czesci[ $i + 1 ] ) ? $czesci[ $i + 1 ] : '';
		$koniec   = ! $w_komorce && ( '' === $nastepny || preg_match( '#^<\s*/#', $nastepny ) );

		$czesci[ $i ] = ntc_typo_tekst( $czesc, (bool) $koniec );
	}

	return implode( '', $czesci );
}

/**
 * Mikrotypografia na blokach motywu.
 *
 * Bloki renderują się po stronie serwera, więc jedno wpięcie w render_block
 * obsługuje nagłówki, akapity, listy i komórki tabel naraz - bez dotykania
 * trzydziestu miejsc, w których bloki składają swój HTML.
 *
 * @param string $html  Wynik renderowania bloku.
 * @param array  $blok  Definicja bloku.
 * @return string
 */
function ntc_typo_blok( $html, $blok ) {
	$nazwa = isset( $blok['blockName'] ) ? (string) $blok['blockName'] : '';

	if ( 0 !== strpos( $nazwa, 'ntc/' ) && 0 !== strpos( $nazwa, 'core/' ) ) {
		return $html;
	}

	return ntc_typo( $html );
}
add_filter( 'render_block', 'ntc_typo_blok', 20, 2 );
