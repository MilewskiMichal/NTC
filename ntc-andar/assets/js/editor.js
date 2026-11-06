/*
 * NTC Andar - bloki w edytorze.
 *
 * Napisane bez JSX i bez kroku budowania: zamiast <Tag/> jest el(Tag, ...).
 * Dzięki temu motyw nie ciągnie za sobą node_modules i wgrywa się na serwer
 * przez FTP tak samo jak reszta plików. Kosztem jest gadatliwość zapisu.
 *
 * Atrybuty bloków przychodzą z PHP w window.ntcBlocks (patrz inc/blocks.php),
 * więc lista pól istnieje tylko raz i nie może się rozjechać między PHP a JS.
 *
 * Bloki są dynamiczne - save() zwraca null, a HTML składa PHP przy wyświetlaniu.
 *
 * Zasada układu: wszystko, co widać na stronie (teksty, zdjęcia, przyciski),
 * edytuje się w samym bloku. Panel po prawej trzyma tylko ustawienia - tło,
 * kadrowanie, kategorię tabeli. Redaktor nie musi wiedzieć, że panel istnieje,
 * żeby podmienić zdjęcie albo napis na przycisku.
 */

( function ( wp ) {
	'use strict';

	if ( ! wp || ! wp.blocks || ! wp.element ) {
		return;
	}

	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var useState = wp.element.useState;

	var blockEditor = wp.blockEditor;
	var components = wp.components;

	var RichText = blockEditor.RichText;
	var InnerBlocks = blockEditor.InnerBlocks;
	var MediaUpload = blockEditor.MediaUpload;
	var MediaUploadCheck = blockEditor.MediaUploadCheck;
	var MediaReplaceFlow = blockEditor.MediaReplaceFlow;
	var BlockControls = blockEditor.BlockControls;
	var InspectorControls = blockEditor.InspectorControls;
	var useBlockProps = blockEditor.useBlockProps;
	// W starszych wersjach WordPressa ten sam komponent miał przedrostek
	// "eksperymentalny".
	var LinkControl = blockEditor.LinkControl || blockEditor.__experimentalLinkControl;

	var PanelBody = components.PanelBody;
	var TextControl = components.TextControl;
	var TextareaControl = components.TextareaControl;
	var ToggleControl = components.ToggleControl;
	var SelectControl = components.SelectControl;
	var FocalPointPicker = components.FocalPointPicker;
	var Popover = components.Popover;
	var Button = components.Button;

	var useSelect = wp.data && wp.data.useSelect;

	var defs = window.ntcBlocks || {};
	var data = window.ntcBlocksData || { categories: {}, icons: [] };

	/* ------------------------------------------------------------ pomocnicze */

	function patch( props, attr, value ) {
		var zmiana = {};
		zmiana[ attr ] = value;
		props.setAttributes( zmiana );
	}

	/** Element z listą dzieci bez ostrzeżeń Reacta o kluczach. */
	function box( className, dzieci ) {
		return el.apply( null, [ 'div', { className: className } ].concat( dzieci ) );
	}

	/**
	 * Pole RichText podpięte do atrybutu bloku.
	 *
	 * Treści wgrywane hurtem dzielą wiersze znakiem nowej linii, a edytor
	 * pokazałby je jako spację. Zamiana na <br> przy wyświetlaniu zostawia
	 * podział widoczny, a PHP rozumie oba zapisy (ntc_linie).
	 */
	function rich( props, attr, tag, className, placeholder, formats ) {
		var value = props.attributes[ attr ];

		return el( RichText, {
			tagName: tag,
			className: className,
			value: 'string' === typeof value ? value.replace( /\n/g, '<br>' ) : value,
			onChange: function ( nowa ) {
				patch( props, attr, nowa );
			},
			placeholder: placeholder,
			allowedFormats: formats || [ 'core/bold', 'core/italic', 'core/link' ]
		} );
	}

	/** Nadtytuł, plakietka - krótkie napisy bez pogrubień i odnośników. */
	function napis( props, attr, className, placeholder ) {
		return rich( props, attr, 'p', className || 'ntc-edit-eyebrow', placeholder || 'Nadtytuł sekcji', [] );
	}

	/** Zwykłe pole tekstowe w bloku - dla liczb i krótkich podpisów. */
	function pole( props, attr, placeholder, className ) {
		var value = props.attributes[ attr ] || '';

		return el( 'input', {
			type: 'text',
			className: 'ntc-edit-input' + ( className ? ' ' + className : '' ),
			value: value,
			placeholder: placeholder,
			'aria-label': placeholder,
			size: Math.max( value.length, placeholder.length, 4 ) + 1,
			onChange: function ( e ) {
				patch( props, attr, e.target.value );
			}
		} );
	}

	/** Ramka sekcji w edytorze, z podpisem, żeby było wiadomo co się edytuje. */
	function frame( label, dark, children ) {
		return el(
			'div',
			{
				className: 'ntc-edit-frame' + ( dark ? ' ntc-edit-frame--dark' : '' )
			},
			el( 'span', { className: 'ntc-edit-label' }, label ),
			children
		);
	}

	/** Tekst i zdjęcie obok siebie, po tej samej stronie co na stronie. */
	function dwieKolumny( tekst, media, zdjecieNaLewo ) {
		return el(
			'div',
			{ className: 'ntc-edit-split' + ( zdjecieNaLewo ? ' ntc-edit-split--lewo' : '' ) },
			box( 'ntc-edit-split__tekst', tekst ),
			box( 'ntc-edit-split__media', [].concat( media ) )
		);
	}

	function wskazowka( tekst ) {
		return el( 'p', { className: 'ntc-edit-hint' }, tekst );
	}

	/* ---------------------------------------------------------------- zdjęcia */

	function ustawZdjecie( props, media ) {
		props.setAttributes( {
			imageId: media.id,
			imageUrl: media.url,
			imageAlt: media.alt || props.attributes.imageAlt || ''
		} );
	}

	function usunZdjecie( props ) {
		props.setAttributes( { imageId: undefined, imageUrl: '', imageAlt: '' } );
	}

	// Te same proporcje co kształty kadru na stronie (ntc_img_ksztalty), żeby
	// punkt kadrowania dało się ocenić bez otwierania podglądu.
	var PROPORCJE = { pion: '5 / 6', wysoki: '2 / 3', kwadrat: '1 / 1', poziom: '4 / 3' };

	/**
	 * Zdjęcie w samym bloku: podgląd i przyciski wyboru.
	 *
	 * Opcje: nazwa (zdjęcie, logotyp, godło), pusto (napis bez zdjęcia),
	 * zastepcze (strona pokazuje wtedy grafikę zastępczą), ksztalt i proporcje
	 * (kadr podglądu), zmiesc (logotypy), male (kafelki), opcjonalne i dodaj
	 * (blok bez zdjęcia pokazuje tylko mały przycisk).
	 */
	function zdjecieWBloku( props, opcje ) {
		opcje = opcje || {};

		var a = props.attributes;
		var nazwa = opcje.nazwa || 'zdjęcie';
		var ksztalt = a.imageShape || opcje.ksztalt || '';
		var zmiesc = !! opcje.zmiesc || 'contain' === a.imageFit;
		var proporcje = PROPORCJE[ ksztalt ] || opcje.proporcje || '4 / 5';
		var styl = {};

		if ( zmiesc ) {
			styl.objectFit = 'contain';
		} else if ( 'naturalny' !== ksztalt ) {
			styl.aspectRatio = proporcje;
			styl.objectFit = 'cover';
			styl.objectPosition = a.imagePos || 'center';
		}

		var klasa = 'ntc-edit-media' + ( opcje.male ? ' ntc-edit-media--male' : '' ) + ( opcje.zmiesc ? ' ntc-edit-media--logo' : '' );

		var podglad = function ( otworz ) {
			if ( a.imageUrl ) {
				return el( 'img', {
					src: a.imageUrl,
					alt: '',
					style: styl,
					className: 'ntc-edit-media__img',
					title: 'Kliknij, żeby zmienić ' + nazwa,
					onClick: otworz
				} );
			}

			return el(
				'button',
				{
					type: 'button',
					className: 'ntc-edit-media__pusto',
					style: zmiesc ? null : { aspectRatio: proporcje },
					onClick: otworz
				},
				opcje.pusto || ( opcje.zastepcze ? 'Bez zdjęcia - na stronie widać grafikę zastępczą w kolorach marki' : 'Brak zdjęcia' )
			);
		};

		return el(
			MediaUploadCheck,
			{ fallback: a.imageUrl ? el( 'img', { src: a.imageUrl, alt: '', style: styl, className: 'ntc-edit-media__img' } ) : null },
			el( MediaUpload, {
				allowedTypes: [ 'image' ],
				value: a.imageId,
				onSelect: function ( media ) {
					ustawZdjecie( props, media );
				},
				render: function ( okno ) {
					if ( ! a.imageUrl && opcje.opcjonalne ) {
						return el(
							Button,
							{ variant: 'secondary', icon: 'format-image', className: 'ntc-edit-media__dodaj', onClick: okno.open },
							opcje.dodaj || 'Dodaj zdjęcie'
						);
					}

					return el(
						'div',
						{ className: klasa },
						podglad( okno.open ),
						el(
							'div',
							{ className: 'ntc-edit-media__akcje' },
							el( Button, { variant: 'secondary', size: 'small', onClick: okno.open }, ( a.imageUrl ? 'Zmień ' : 'Wybierz ' ) + nazwa ),
							a.imageUrl
								? el( Button, {
									variant: 'tertiary',
									size: 'small',
									isDestructive: true,
									onClick: function () {
										usunZdjecie( props );
									}
								}, 'Usuń' )
								: null
						)
					);
				}
			} )
		);
	}

	/** Ten sam wybór zdjęcia na pasku narzędzi bloku, tak jak w blokach WordPressa. */
	function zdjecieWPasku( props, nazwa ) {
		if ( ! BlockControls || ! MediaReplaceFlow ) {
			return null;
		}

		var a = props.attributes;

		return el(
			BlockControls,
			{ group: 'other' },
			el( MediaReplaceFlow, {
				mediaId: a.imageId,
				mediaURL: a.imageUrl,
				allowedTypes: [ 'image' ],
				accept: 'image/*',
				name: ( a.imageUrl ? 'Zmień ' : 'Dodaj ' ) + ( nazwa || 'zdjęcie' ),
				onSelect: function ( media ) {
					ustawZdjecie( props, media );
				},
				onReset: a.imageUrl ? function () {
					usunZdjecie( props );
				} : undefined
			} )
		);
	}

	/**
	 * Punkt kadrowania z zapisu CSS ("center top", "92% 50%") na ułamki 0-1,
	 * których używa FocalPointPicker.
	 */
	function punktZPozycji( pozycja ) {
		var punkt = { x: 0.5, y: 0.5 };
		var slowa = { left: [ 'x', 0 ], right: [ 'x', 1 ], top: [ 'y', 0 ], bottom: [ 'y', 1 ], center: [ '', 0.5 ] };
		var osie = [ 'x', 'y' ];

		String( pozycja || '' ).trim().toLowerCase().split( /\s+/ ).slice( 0, 2 ).forEach( function ( czesc, i ) {
			if ( /^-?\d+(\.\d+)?%$/.test( czesc ) ) {
				punkt[ osie[ i ] ] = Math.min( 1, Math.max( 0, parseFloat( czesc ) / 100 ) );
			} else if ( slowa[ czesc ] ) {
				punkt[ slowa[ czesc ][ 0 ] || osie[ i ] ] = slowa[ czesc ][ 1 ];
			}
		} );

		return punkt;
	}

	function pozycjaZPunktu( punkt ) {
		var x = Math.round( punkt.x * 100 );
		var y = Math.round( punkt.y * 100 );

		// Środek to brak ustawienia - zapis zostaje czysty.
		return ( 50 === x && 50 === y ) ? '' : x + '% ' + y + '%';
	}

	/**
	 * Panel boczny zdjęcia: opis alternatywny i kadrowanie.
	 *
	 * Opcje kadr.pozycja / kadr.ksztalt / kadr.dopasowanie włączają tylko te
	 * ustawienia, które dany blok naprawdę uwzględnia - pole, które nic nie
	 * zmienia na stronie, byłoby gorsze niż jego brak.
	 */
	function zdjeciePanel( props, opcje ) {
		opcje = opcje || {};

		var a = props.attributes;
		var kadr = opcje.kadr || {};
		var dzieci = [
			el( TextControl, {
				label: 'Opis alternatywny',
				help: 'Krótko, co widać na zdjęciu. Czytają go czytniki ekranu i wyszukiwarki, pokazuje się też, gdy zdjęcie się nie wczyta.',
				value: a.imageAlt || '',
				onChange: function ( value ) {
					patch( props, 'imageAlt', value );
				}
			} )
		];

		if ( kadr.pozycja && a.imageUrl && FocalPointPicker && 'contain' !== a.imageFit ) {
			dzieci.push(
				el( FocalPointPicker, {
					label: 'Punkt kadrowania',
					help: 'Kliknij albo przeciągnij kółko na to, co ma zostać w ramce, gdy zdjęcie trzeba przyciąć.',
					url: a.imageUrl,
					value: punktZPozycji( a.imagePos ),
					onChange: function ( punkt ) {
						patch( props, 'imagePos', pozycjaZPunktu( punkt ) );
					}
				} )
			);

			if ( a.imagePos ) {
				dzieci.push(
					el( Button, {
						variant: 'link',
						className: 'ntc-edit-reset',
						onClick: function () {
							patch( props, 'imagePos', '' );
						}
					}, 'Wróć do środka zdjęcia' )
				);
			}
		}

		if ( kadr.ksztalt ) {
			dzieci.push(
				el( SelectControl, {
					label: 'Kształt zdjęcia',
					help: 'Stały kształt sprawia, że zdjęcia w kolejnych sekcjach mają podobną wielkość niezależnie od długości tekstu.',
					value: a.imageShape || '',
					options: [
						{ label: 'Domyślny dla bloku', value: '' },
						{ label: 'Pionowy prostokąt', value: 'pion' },
						{ label: 'Wysoki i wąski', value: 'wysoki' },
						{ label: 'Kwadrat', value: 'kwadrat' },
						{ label: 'Poziomy', value: 'poziom' },
						{ label: 'Całe zdjęcie, bez przycinania', value: 'naturalny' },
						{ label: 'Do wysokości tekstu obok', value: 'wypelnij' }
					],
					onChange: function ( value ) {
						patch( props, 'imageShape', value );
					}
				} )
			);
		}

		if ( kadr.dopasowanie ) {
			dzieci.push(
				el( SelectControl, {
					label: 'Dopasowanie',
					help: 'Logotypy i certyfikaty ustawiaj na "zmieść w całości" - inaczej zostaną przycięte.',
					value: a.imageFit || 'cover',
					options: [
						{ label: 'Wypełnij ramkę (zdjęcia)', value: 'cover' },
						{ label: 'Zmieść w całości (logotypy)', value: 'contain' }
					],
					onChange: function ( value ) {
						patch( props, 'imageFit', value );
					}
				} )
			);
		}

		return el.apply( null, [ PanelBody, { title: opcje.tytul || 'Zdjęcie - opis i kadrowanie', initialOpen: false } ].concat( dzieci ) );
	}

	/* --------------------------------------------------------------- przyciski */

	/** Adres w wersji do pokazania: ścieżka zamiast pełnego adresu, nazwa pliku zamiast ścieżki. */
	function skrocAdres( url ) {
		var adres = String( url || '' );
		var tutaj = window.location.protocol + '//' + window.location.host;
		var plik;

		if ( ! adres || '#' === adres.charAt( 0 ) ) {
			return adres;
		}

		plik = adres.match( /\/wp-content\/uploads\/.*\/([^\/?#]+)$/ );

		if ( plik ) {
			return 'plik ' + decodeURIComponent( plik[ 1 ] );
		}

		adres = 0 === adres.indexOf( tutaj ) ? ( adres.slice( tutaj.length ) || '/' ) : adres.replace( /^https?:\/\//, '' );

		return adres.length > 46 ? adres.slice( 0, 44 ) + '…' : adres;
	}

	/**
	 * Przycisk albo odnośnik edytowany w miejscu.
	 *
	 * Napis wpisuje się w kształt przycisku, a adres wybiera z listy stron
	 * (wyszukiwarka po tytule) albo z biblioteki mediów - bez przepisywania
	 * adresów ręcznie.
	 */
	function Przycisk( p ) {
		var props = p.props;
		var o = p.opcje || {};
		var otwarte = useState( false );
		var kotwica = useState( null );
		var tekst = props.attributes[ p.tekst ] || '';
		var adres = props.attributes[ p.adres ] || '';
		var placeholder = o.placeholder || 'Napis na przycisku';
		var opisAdresu;

		if ( adres ) {
			opisAdresu = skrocAdres( adres );
		} else if ( o.domyslny ) {
			opisAdresu = 'prowadzi do: ' + o.domyslny;
		} else {
			opisAdresu = 'ustaw, dokąd prowadzi';
		}

		function zamknij() {
			otwarte[ 1 ]( false );
		}

		return el(
			'div',
			{ className: 'ntc-edit-cta ntc-edit-cta--' + ( o.styl || 'pelny' ) + ( tekst ? '' : ' is-empty' ) },
			el(
				'span',
				{ className: 'ntc-edit-cta__przycisk' },
				el( 'input', {
					type: 'text',
					className: 'ntc-edit-cta__tekst',
					value: tekst,
					placeholder: placeholder,
					'aria-label': placeholder,
					title: 'Puste pole = ' + ( 'link' === o.styl ? 'odnośnik' : 'przycisk' ) + ' nie pokaże się na stronie',
					size: Math.max( tekst.length || placeholder.length, 4 ) + 1,
					onChange: function ( e ) {
						patch( props, p.tekst, e.target.value );
					}
				} ),
				o.strzalka ? el( 'span', { className: 'ntc-edit-cta__strzalka', 'aria-hidden': 'true' }, '→' ) : null
			),
			// Bez napisu przycisku i tak nie ma na stronie, więc nie ma też
			// czego podpinać - adres pojawia się po wpisaniu napisu.
			tekst ? el(
				Button,
				{
					ref: kotwica[ 1 ],
					className: 'ntc-edit-cta__adres' + ( adres ? '' : ' is-empty' ),
					icon: 'admin-links',
					size: 'small',
					variant: 'tertiary',
					'aria-expanded': otwarte[ 0 ],
					onClick: function () {
						otwarte[ 1 ]( ! otwarte[ 0 ] );
					}
				},
				opisAdresu
			) : null,
			tekst && otwarte[ 0 ] ? el(
				Popover,
				{ anchor: kotwica[ 0 ], placement: 'bottom-start', onClose: zamknij, focusOnMount: 'firstElement', shift: true },
				el(
					'div',
					{ className: 'ntc-edit-cta__okno' },
					el( 'p', { className: 'ntc-edit-cta__naglowek' }, 'Dokąd prowadzi ' + ( 'link' === o.styl ? 'odnośnik' : 'przycisk' ) ),
					LinkControl
						? el( LinkControl, {
							value: { url: adres },
							settings: [],
							forceIsEditingLink: ! adres,
							onChange: function ( wybor ) {
								patch( props, p.adres, ( wybor && wybor.url ) || '' );
							},
							onRemove: function () {
								patch( props, p.adres, '' );
							}
						} )
						: el( TextControl, {
							label: 'Adres',
							value: adres,
							onChange: function ( value ) {
								patch( props, p.adres, value );
							}
						} ),
					el(
						MediaUploadCheck,
						null,
						el( MediaUpload, {
							onSelect: function ( media ) {
								patch( props, p.adres, media.url );
								zamknij();
							},
							render: function ( okno ) {
								return el( Button, { variant: 'link', className: 'ntc-edit-cta__plik', onClick: okno.open },
									'Albo wybierz plik z biblioteki mediów (np. PDF)' );
							}
						} )
					),
					el( 'p', { className: 'ntc-edit-cta__podpowiedz' },
						o.podpowiedz || 'Wpisz nazwę strony, żeby ją wyszukać, albo wklej pełny adres. Kotwicę sekcji wpisz z krzyżykiem, np. #oferta.' )
				)
			) : null
		);
	}

	function przycisk( props, tekst, adres, opcje ) {
		return el( Przycisk, { props: props, tekst: tekst, adres: adres, opcje: opcje } );
	}

	/**
	 * Rejestruje blok, doklejając atrybuty z PHP.
	 */
	function register( name, settings ) {
		var def = defs[ name ];

		if ( ! def ) {
			return;
		}

		wp.blocks.registerBlockType( name, {
			apiVersion: 3,
			title: def.title,
			icon: def.icon,
			category: 'ntc-andar',
			parent: def.parent || undefined,
			attributes: def.attributes || {},
			supports: { html: false, anchor: true },
			edit: settings.edit,
			save: function () {
				return null;
			}
		} );
	}

	/* ------------------------------------------------------- strona główna */

	register( 'ntc/hero', {
		edit: function ( props ) {
			return el(
				Fragment,
				null,
				zdjecieWPasku( props ),
				el( InspectorControls, null, zdjeciePanel( props, { kadr: { pozycja: true } } ) ),
				el(
					'div',
					useBlockProps(),
					frame( 'Hero', true, dwieKolumny(
						[
							napis( props, 'badge', 'ntc-edit-badge', 'Nadtytuł' ),
							rich( props, 'title', 'h1', 'ntc-edit-h1', 'Nagłówek główny' ),
							rich( props, 'sub', 'p', 'ntc-edit-sub', 'Zdanie wprowadzające' ),
							el(
								'div',
								{ className: 'ntc-edit-cta-row' },
								przycisk( props, 'btn1Text', 'btn1Url', { domyslny: '#oferta' } ),
								przycisk( props, 'btn2Text', 'btn2Url', { styl: 'obrys', domyslny: '#quality' } )
							)
						],
						zdjecieWBloku( props, { zastepcze: true, proporcje: '1 / 1' } )
					) )
				)
			);
		}
	} );

	/** Liczba z podpisem w sekcji "O nas". */
	function liczba( props, num, label ) {
		return el(
			'div',
			{ className: 'ntc-edit-liczba' },
			pole( props, num, 'Liczba', 'ntc-edit-liczba__num' ),
			pole( props, label, 'Podpis pod liczbą', 'ntc-edit-liczba__podpis' )
		);
	}

	register( 'ntc/about', {
		edit: function ( props ) {
			return el(
				Fragment,
				null,
				zdjecieWPasku( props ),
				el( InspectorControls, null, zdjeciePanel( props ) ),
				el(
					'div',
					useBlockProps(),
					frame( 'O nas', false, dwieKolumny(
						[
							napis( props, 'label' ),
							rich( props, 'title', 'h2', 'ntc-edit-h2', 'Nagłówek sekcji' ),
							rich( props, 'p1', 'p', 'ntc-edit-body', 'Pierwszy akapit' ),
							rich( props, 'p2', 'p', 'ntc-edit-body', 'Drugi akapit' ),
							rich( props, 'p3', 'p', 'ntc-edit-body', 'Trzeci akapit' ),
							el( 'div', { className: 'ntc-edit-liczby' }, liczba( props, 'fig1Num', 'fig1Label' ), liczba( props, 'fig2Num', 'fig2Label' ) ),
							wskazowka( 'Liczba zostawiona pusta znika ze strony razem z podpisem.' )
						],
						[
							zdjecieWBloku( props, { zastepcze: true, proporcje: '4 / 5' } ),
							el(
								'div',
								{ className: 'ntc-edit-karta' },
								el( 'span', { className: 'ntc-edit-karta__opis' }, 'Karta na zdjęciu' ),
								liczba( props, 'accentNum', 'accentTxt' )
							)
						],
						true
					) )
				)
			);
		}
	} );

	register( 'ntc/prose', {
		edit: function ( props ) {
			var a = props.attributes;
			var tekst = [
				napis( props, 'label' ),
				rich( props, 'title', 'h2', 'ntc-edit-h2', 'Nagłówek sekcji' ),
				rich( props, 'lead', 'p', 'ntc-edit-body', 'Zdanie wprowadzające' ),
				// Bez listy dozwolonych bloków - treść składa się ze
				// zwykłych nagłówków, akapitów i list WordPressa.
				el( InnerBlocks, {
					template: [ [ 'core/heading', { level: 2 } ], [ 'core/paragraph' ] ],
					renderAppender: InnerBlocks.ButtonBlockAppender
				} )
			];
			var media = zdjecieWBloku( props, { opcjonalne: true, dodaj: 'Dodaj zdjęcie obok tekstu' } );

			return el(
				Fragment,
				null,
				zdjecieWPasku( props ),
				el(
					InspectorControls,
					null,
					a.imageUrl ? zdjeciePanel( props, { kadr: { pozycja: true, ksztalt: true, dopasowanie: true } } ) : null,
					el(
						PanelBody,
						{ title: 'Układ sekcji', initialOpen: false },
						el( ToggleControl, {
							label: 'Jasnoniebieskie tło',
							help: 'Włącz, gdy kilka bloków tekstowych stoi pod rząd i mają się od siebie odcinać.',
							checked: !! a.alt,
							onChange: function ( value ) {
								patch( props, 'alt', value );
							}
						} ),
						el( ToggleControl, {
							label: 'Zdjęcie po lewej stronie',
							checked: 'left' === a.imageSide,
							onChange: function ( value ) {
								patch( props, 'imageSide', value ? 'left' : '' );
							}
						} ),
						el( ToggleControl, {
							label: 'Ciąg dalszy sekcji powyżej',
							help: 'Bez odstępu od poprzedniego bloku - np. drugie zdjęcie w tej samej sekcji. Ustaw to samo tło co wyżej.',
							checked: !! a.continued,
							onChange: function ( value ) {
								patch( props, 'continued', value );
							}
						} )
					)
				),
				el(
					'div',
					useBlockProps(),
					frame( 'Blok tekstowy', false, a.imageUrl
						? dwieKolumny( tekst, media, 'left' === a.imageSide )
						: box( 'ntc-edit-pelny', tekst.concat( [ el( 'div', { className: 'ntc-edit-dodaj-zdjecie' }, media ) ] ) )
					)
				)
			);
		}
	} );

	register( 'ntc/features', {
		edit: function ( props ) {
			return el(
				'div',
				useBlockProps(),
				frame( 'Co nas wyróżnia', true, el(
					Fragment,
					null,
					napis( props, 'label' ),
					rich( props, 'title', 'h2', 'ntc-edit-h2', 'Nagłówek sekcji' ),
					rich( props, 'intro', 'p', 'ntc-edit-body', 'Akapit obok nagłówka' ),
					el( 'div', { className: 'ntc-edit-grid' },
						el( InnerBlocks, {
							allowedBlocks: [ 'ntc/feature' ],
							template: [ [ 'ntc/feature' ], [ 'ntc/feature' ], [ 'ntc/feature' ], [ 'ntc/feature' ] ],
							renderAppender: InnerBlocks.ButtonBlockAppender
						} ) )
				) )
			);
		}
	} );

	register( 'ntc/feature', {
		edit: function ( props ) {
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Ikona' },
						el( SelectControl, {
							label: 'Symbol',
							value: props.attributes.icon || 'flask',
							options: ( data.icons || [] ).map( function ( icon ) {
								return { label: icon.label, value: icon.value };
							} ),
							onChange: function ( v ) { patch( props, 'icon', v ); }
						} )
					)
				),
				el(
					'div',
					useBlockProps( { className: 'ntc-edit-card' } ),
					rich( props, 'title', 'h3', 'ntc-edit-h3', 'Tytuł kafelka' ),
					rich( props, 'text', 'p', 'ntc-edit-body', 'Opis kafelka' ),
					wskazowka( 'Enter w opisie zaczyna nowy punkt wyliczenia.' )
				)
			);
		}
	} );

	register( 'ntc/offer', {
		edit: function ( props ) {
			return el(
				'div',
				useBlockProps(),
				frame( 'Oferta - kafelki', false, el(
					Fragment,
					null,
					napis( props, 'label' ),
					rich( props, 'title', 'h2', 'ntc-edit-h2', 'Nagłówek sekcji' ),
					przycisk( props, 'ctaText', 'ctaUrl', { domyslny: 'strona Kontakt', placeholder: 'Przycisk nad kafelkami' } ),
					el( 'div', { className: 'ntc-edit-grid ntc-edit-grid--3' },
						el( InnerBlocks, {
							allowedBlocks: [ 'ntc/offer-card' ],
							template: [ [ 'ntc/offer-card' ], [ 'ntc/offer-card' ], [ 'ntc/offer-card' ] ],
							renderAppender: InnerBlocks.ButtonBlockAppender
						} ) )
				) )
			);
		}
	} );

	register( 'ntc/offer-card', {
		edit: function ( props ) {
			return el(
				Fragment,
				null,
				zdjecieWPasku( props ),
				el( InspectorControls, null, zdjeciePanel( props, { kadr: { pozycja: true } } ) ),
				el(
					'div',
					useBlockProps( { className: 'ntc-edit-card' } ),
					zdjecieWBloku( props, { male: true, zastepcze: true, proporcje: '16 / 10' } ),
					napis( props, 'tag', 'ntc-edit-tag', 'Plakietka' ),
					rich( props, 'title', 'h3', 'ntc-edit-h3', 'Tytuł' ),
					rich( props, 'text', 'p', 'ntc-edit-body', 'Opis' ),
					el(
						'div',
						{ className: 'ntc-edit-linki' },
						przycisk( props, 'linkText', 'linkUrl', { styl: 'link', strzalka: true, domyslny: 'strona Kontakt', placeholder: 'Odnośnik pod opisem' } ),
						// Kafelek dwóch kategorii (np. laktoferyna i colostrum)
						// dostaje drugi odnośnik obok pierwszego.
						przycisk( props, 'link2Text', 'link2Url', { styl: 'link', strzalka: true, domyslny: 'strona Kontakt', placeholder: 'Drugi odnośnik (opcjonalnie)' } )
					)
				)
			);
		}
	} );

	register( 'ntc/stats', {
		edit: function ( props ) {
			return el(
				'div',
				useBlockProps(),
				frame( 'Statystyki', true,
					el( 'div', { className: 'ntc-edit-grid ntc-edit-grid--4' },
						el( InnerBlocks, {
							allowedBlocks: [ 'ntc/stat' ],
							template: [ [ 'ntc/stat' ], [ 'ntc/stat' ], [ 'ntc/stat' ], [ 'ntc/stat' ] ],
							renderAppender: InnerBlocks.ButtonBlockAppender
						} ) )
				)
			);
		}
	} );

	register( 'ntc/stat', {
		edit: function ( props ) {
			return el(
				'div',
				useBlockProps( { className: 'ntc-edit-card ntc-edit-card--center' } ),
				el( TextControl, {
					label: 'Liczba',
					type: 'number',
					value: props.attributes.number,
					onChange: function ( v ) { patch( props, 'number', parseInt( v, 10 ) || 0 ); }
				} ),
				el( TextControl, {
					label: 'Końcówka',
					help: 'Np. + albo %.',
					value: props.attributes.suffix || '',
					onChange: function ( v ) { patch( props, 'suffix', v ); }
				} ),
				el( TextareaControl, {
					label: 'Podpis',
					help: 'Enter dzieli podpis na dwa wiersze, tak jak w projekcie.',
					value: props.attributes.label || '',
					onChange: function ( v ) { patch( props, 'label', v ); }
				} )
			);
		}
	} );

	register( 'ntc/logos', {
		edit: function ( props ) {
			// Logotypy to bloki potomne. Lista nazw działa tylko jako zapas,
			// zanim pojawi się pierwszy logotyp - wtedy strona ją pomija.
			var ileLogo = useSelect
				? useSelect( function ( select ) {
					return select( 'core/block-editor' ).getBlockCount( props.clientId );
				}, [ props.clientId ] )
				: 0;

			return el(
				'div',
				useBlockProps(),
				frame( 'Karuzela partnerów', false, el(
					Fragment,
					null,
					napis( props, 'label', '', 'Nadtytuł' ),
					el( 'div', { className: 'ntc-edit-grid ntc-edit-grid--loga' },
						el( InnerBlocks, {
							allowedBlocks: [ 'ntc/partner' ],
							renderAppender: InnerBlocks.ButtonBlockAppender
						} ) ),
					ileLogo
						? wskazowka( 'Logotypy przesuwają się na stronie w pętli. Kolejność zmienisz strzałkami na pasku nad zaznaczonym logotypem, nowy dodasz przyciskiem +.' )
						: el( TextareaControl, {
							label: 'Nazwy partnerów bez logotypów',
							help: 'Jedna nazwa na wiersz. Znikną z taśmy, gdy dodasz przyciskiem + pierwszy logotyp.',
							rows: 6,
							value: props.attributes.names || '',
							onChange: function ( v ) { patch( props, 'names', v ); }
						} )
				) )
			);
		}
	} );

	register( 'ntc/partner', {
		edit: function ( props ) {
			return el(
				Fragment,
				null,
				zdjecieWPasku( props, 'logotyp' ),
				el( InspectorControls, null, zdjeciePanel( props, { tytul: 'Logotyp - opis' } ) ),
				el(
					'div',
					useBlockProps( { className: 'ntc-edit-logo' } ),
					zdjecieWBloku( props, { nazwa: 'logotyp', zmiesc: true, male: true, pusto: 'Brak logotypu - na stronie widać samą nazwę' } ),
					rich( props, 'name', 'p', 'ntc-edit-logo__nazwa', 'Nazwa partnera', [] ),
					pole( props, 'url', 'Strona partnera (opcjonalnie)', 'ntc-edit-logo__adres' )
				)
			);
		}
	} );

	register( 'ntc/steps', {
		edit: function ( props ) {
			return el(
				'div',
				useBlockProps(),
				frame( 'Jak działamy', false, el(
					Fragment,
					null,
					napis( props, 'label' ),
					rich( props, 'title', 'h2', 'ntc-edit-h2', 'Nagłówek sekcji' ),
					rich( props, 'sub', 'p', 'ntc-edit-body', 'Akapit wprowadzający' ),
					przycisk( props, 'ctaText', 'ctaUrl', { domyslny: 'strona Kontakt', placeholder: 'Przycisk pod opisem' } ),
					el( 'div', { className: 'ntc-edit-list' },
						el( InnerBlocks, {
							allowedBlocks: [ 'ntc/step' ],
							template: [ [ 'ntc/step' ], [ 'ntc/step' ], [ 'ntc/step' ], [ 'ntc/step' ] ],
							renderAppender: InnerBlocks.ButtonBlockAppender
						} ) ),
					wskazowka( 'Kroki numerują się same - po przestawieniu albo skasowaniu numeracja się poprawi.' )
				) )
			);
		}
	} );

	register( 'ntc/step', {
		edit: function ( props ) {
			return el(
				'div',
				useBlockProps( { className: 'ntc-edit-row' } ),
				rich( props, 'title', 'h3', 'ntc-edit-h3', 'Nazwa kroku' ),
				rich( props, 'text', 'p', 'ntc-edit-body', 'Opis kroku' )
			);
		}
	} );

	register( 'ntc/contact', {
		edit: function ( props ) {
			return el(
				Fragment,
				null,
				zdjecieWPasku( props ),
				el( InspectorControls, null, zdjeciePanel( props, { kadr: { pozycja: true } } ) ),
				el(
					'div',
					useBlockProps(),
					frame( 'Sekcja kontaktowa', true, dwieKolumny(
						[
							napis( props, 'label', '', 'Nadtytuł' ),
							rich( props, 'title', 'h2', 'ntc-edit-h2', 'Nagłówek' ),
							rich( props, 'sub', 'p', 'ntc-edit-body', 'Akapit pod nagłówkiem' ),
							wskazowka( 'Formularz i dane kontaktowe dokłada motyw. Telefon, e-mail i adres zmienisz w Wygląd → Dostosuj → NTC - Dane firmowe.' )
						],
						zdjecieWBloku( props, { zastepcze: true, proporcje: '1 / 1' } ),
						true
					) )
				)
			);
		}
	} );

	/* --------------------------------------------------------------- oferta */

	register( 'ntc/page-hero', {
		edit: function ( props ) {
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Zakładki' },
						el( ToggleControl, {
							label: 'Pokaż pasek zakładek',
							help: 'Zakładki budują się z kategorii produktów i przewijają do sekcji na tej stronie.',
							checked: !! props.attributes.showTabs,
							onChange: function ( v ) { patch( props, 'showTabs', v ); }
						} )
					)
				),
				el(
					'div',
					useBlockProps(),
					frame( 'Hero podstrony', true, el(
						Fragment,
						null,
						napis( props, 'badge', 'ntc-edit-badge', 'Nadtytuł' ),
						rich( props, 'title', 'h1', 'ntc-edit-h1', 'Nagłówek' ),
						rich( props, 'sub', 'p', 'ntc-edit-sub', 'Zdanie wprowadzające' ),
						przycisk( props, 'ctaText', 'ctaUrl', {
							placeholder: 'Przycisk pod opisem (opcjonalnie)',
							podpowiedz: 'Np. katalog w PDF: wybierz plik z biblioteki mediów. Przycisk pokaże się, gdy ma i napis, i adres.'
						} )
					) )
				)
			);
		}
	} );

	register( 'ntc/category', {
		edit: function ( props ) {
			var a = props.attributes;
			var cats = data.categories || {};
			var options = [ { label: '- wybierz kategorię -', value: '' } ];

			Object.keys( cats ).forEach( function ( slug ) {
				options.push( { label: cats[ slug ], value: slug } );
			} );

			return el(
				Fragment,
				null,
				zdjecieWPasku( props ),
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Kategoria i tabela' },
						el( SelectControl, {
							label: 'Kategoria produktów',
							help: 'Tabela zaciąga produkty z tej kategorii. Produkty dodajesz w menu Produkty.',
							value: a.category || '',
							options: options,
							onChange: function ( v ) { patch( props, 'category', v ); }
						} ),
						el( ToggleControl, {
							label: 'Pokaż tabelę produktów',
							checked: a.showTable !== false,
							onChange: function ( v ) { patch( props, 'showTable', v ); }
						} ),
						el( ToggleControl, {
							label: 'Pokaż ramkę „Nie znalazłeś?”',
							checked: a.showNotFound !== false,
							onChange: function ( v ) { patch( props, 'showNotFound', v ); }
						} ),
						el( ToggleControl, {
							label: 'Jasnoszare tło i zdjęcie po lewej',
							help: 'Włącz w co drugiej kategorii, żeby sekcje się przeplatały.',
							checked: !! a.alt,
							onChange: function ( v ) { patch( props, 'alt', v ); }
						} )
					),
					el(
						PanelBody,
						{ title: 'Tabela - kolumny i strony', initialOpen: false },
						el( TextControl, {
							label: 'Pozycji na jednej stronie tabeli',
							help: 'Gdy produktów jest więcej, pod tabelą pojawia się przełącznik stron. 0 albo puste - wszystkie naraz.',
							type: 'number',
							min: 0,
							value: a.perPage || '',
							onChange: function ( v ) { patch( props, 'perPage', parseInt( v, 10 ) || 0 ); }
						} ),
						el( TextControl, {
							label: 'Kolumny tabeli',
							help: 'Po przecinku, w kolejności od lewej. Do wyboru: name (nazwa), group (grupa), cas, form (forma), maker (wytwórca), origin (pochodzenie), use (zastosowanie), docs (dokumentacja), collection (kolekcja), postbiotic. Puste - name, cas, form, docs, origin.',
							value: a.columns || '',
							onChange: function ( v ) { patch( props, 'columns', v ); }
						} ),
						el( TextareaControl, {
							label: 'Własne nagłówki kolumn',
							help: 'W każdej linii: kolumna = nagłówek, np. group = Rodzaj. Bez wpisu kolumna ma nagłówek domyślny.',
							rows: 4,
							value: a.colLabels || '',
							onChange: function ( v ) { patch( props, 'colLabels', v ); }
						} )
					),
					zdjeciePanel( props, { kadr: { pozycja: true, ksztalt: true, dopasowanie: true } } )
				),
				el(
					'div',
					useBlockProps(),
					frame( 'Kategoria oferty', false, dwieKolumny(
						[
							napis( props, 'label', '', 'Nadtytuł' ),
							rich( props, 'title', 'h2', 'ntc-edit-h2', 'Nagłówek' ),
							rich( props, 'p1', 'p', 'ntc-edit-body', 'Pierwszy akapit' ),
							rich( props, 'p2', 'p', 'ntc-edit-body', 'Drugi akapit' ),
							przycisk( props, 'ctaText', 'ctaUrl', {
								strzalka: true,
								placeholder: 'Przycisk pod opisem (opcjonalnie)',
								podpowiedz: 'Przycisk pokaże się, gdy ma i napis, i adres.'
							} ),
							wskazowka( a.category
								? 'Pod tekstem strona pokaże tabelę produktów z kategorii: ' + ( cats[ a.category ] || a.category ) + '.'
								: 'Wybierz kategorię w panelu po prawej, żeby tabela miała co pokazać.' )
						],
						zdjecieWBloku( props, { zastepcze: true, ksztalt: 'pion' } ),
						!! a.alt
					) )
				)
			);
		}
	} );

	register( 'ntc/machines', {
		edit: function ( props ) {
			return el(
				Fragment,
				null,
				zdjecieWPasku( props ),
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Układ', initialOpen: false },
						el( ToggleControl, {
							label: 'Ciemne tło (granat)',
							checked: !! props.attributes.dark,
							onChange: function ( v ) { patch( props, 'dark', v ); }
						} )
					),
					zdjeciePanel( props, { kadr: { pozycja: true, ksztalt: true, dopasowanie: true } } )
				),
				el(
					'div',
					useBlockProps(),
					frame( 'Maszyny', !! props.attributes.dark, dwieKolumny(
						[
							napis( props, 'label', '', 'Nadtytuł' ),
							rich( props, 'title', 'h2', 'ntc-edit-h2', 'Nagłówek' ),
							rich( props, 'text', 'p', 'ntc-edit-body', 'Opis - Enter zaczyna nowy akapit' ),
							przycisk( props, 'ctaText', 'ctaUrl', { strzalka: true, placeholder: 'Przycisk do dostawcy' } ),
							rich( props, 'note', 'p', 'ntc-edit-body ntc-edit-notka', 'Notka pod przyciskiem (opcjonalnie)' )
						],
						zdjecieWBloku( props, { zastepcze: true, proporcje: '4 / 5' } )
					) )
				)
			);
		}
	} );

	/* -------------------------------------------------------------- kontakt */

	register( 'ntc/lp-hero', {
		edit: function ( props ) {
			return el(
				'div',
				useBlockProps(),
				frame( 'Hero wyśrodkowany', true, el(
					Fragment,
					null,
					napis( props, 'badge', 'ntc-edit-badge', 'Plakietka' ),
					rich( props, 'title', 'h1', 'ntc-edit-h1', 'Nagłówek' ),
					rich( props, 'sub', 'p', 'ntc-edit-sub', 'Zdanie wprowadzające' )
				) )
			);
		}
	} );

	register( 'ntc/contact-panel', {
		edit: function ( props ) {
			return el(
				Fragment,
				null,
				zdjecieWPasku( props ),
				el(
					InspectorControls,
					null,
					props.attributes.imageUrl ? zdjeciePanel( props, { kadr: { pozycja: true, ksztalt: true, dopasowanie: true } } ) : null
				),
				el(
					'div',
					useBlockProps(),
					frame( 'Panel kontaktowy', false, el(
						'div',
						{ className: 'ntc-edit-split ntc-edit-split--rowno' },
						box( 'ntc-edit-split__tekst', [
							rich( props, 'infoTitle', 'h2', 'ntc-edit-h2', 'Nagłówek kolumny z danymi' ),
							rich( props, 'infoSub', 'p', 'ntc-edit-body', 'Akapit pod nagłówkiem' ),
							wskazowka( 'Tu strona pokaże adres, telefony, e-mail, godziny i NIP z Wygląd → Dostosuj → NTC - Dane firmowe. Pod nimi zdjęcie:' ),
							zdjecieWBloku( props, { opcjonalne: true, dodaj: 'Dodaj zdjęcie pod danymi kontaktowymi', proporcje: '4 / 3' } )
						] ),
						box( 'ntc-edit-split__tekst', [
							rich( props, 'formTitle', 'h2', 'ntc-edit-h2', 'Nagłówek formularza' ),
							rich( props, 'formSub', 'p', 'ntc-edit-body', 'Akapit nad formularzem' ),
							wskazowka( 'Pod spodem strona pokaże formularz kontaktowy.' )
						] )
					) )
				)
			);
		}
	} );

	register( 'ntc/map', {
		edit: function ( props ) {
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Mapa' },
						el( TextControl, {
							label: 'Podpis na mapie',
							value: props.attributes.pinLabel || '',
							onChange: function ( v ) { patch( props, 'pinLabel', v ); }
						} ),
						el( TextControl, {
							label: 'Adres pokazany na mapie',
							help: 'Puste - adres z Dostosuj → NTC - Dane firmowe.',
							value: props.attributes.query || '',
							onChange: function ( v ) { patch( props, 'query', v ); }
						} ),
						el( TextControl, {
							label: 'Własny odnośnik do mapy (zaawansowane)',
							help: 'Adres z Map Google → Udostępnij → Umieść mapę (sam adres z src). Ma pierwszeństwo przed polem wyżej.',
							value: props.attributes.embedUrl || '',
							onChange: function ( v ) { patch( props, 'embedUrl', v ); }
						} )
					)
				),
				el(
					'div',
					useBlockProps(),
					frame( 'Dojazd', false, el(
						Fragment,
						null,
						napis( props, 'label', '', 'Nadtytuł' ),
						rich( props, 'title', 'h2', 'ntc-edit-h2', 'Nagłówek' ),
						el( 'div', { className: 'ntc-edit-list' },
							el( InnerBlocks, {
								allowedBlocks: [ 'ntc/map-card' ],
								template: [ [ 'ntc/map-card' ], [ 'ntc/map-card' ], [ 'ntc/map-card' ] ],
								renderAppender: InnerBlocks.ButtonBlockAppender
							} ) ),
						wskazowka( 'Mapa przy kafelkach pokazuje adres firmy. Inny adres ustawisz w panelu po prawej.' )
					) )
				)
			);
		}
	} );

	register( 'ntc/map-card', {
		edit: function ( props ) {
			return el(
				'div',
				useBlockProps( { className: 'ntc-edit-card' } ),
				napis( props, 'label', '', 'Nadtytuł kafelka' ),
				rich( props, 'title', 'h3', 'ntc-edit-h3', 'Tytuł' ),
				rich( props, 'text', 'p', 'ntc-edit-body', 'Opis' )
			);
		}
	} );

	register( 'ntc/quick-cta', {
		edit: function ( props ) {
			return el(
				'div',
				useBlockProps(),
				frame( 'Pasek CTA', true, el(
					Fragment,
					null,
					rich( props, 'head', 'h2', 'ntc-edit-h2', 'Nagłówek paska' ),
					rich( props, 'sub', 'p', 'ntc-edit-body', 'Zdanie pod nagłówkiem' ),
					wskazowka( 'Telefon i e-mail na przyciskach biorą się z Wygląd → Dostosuj → NTC - Dane firmowe.' )
				) )
			);
		}
	} );

	/* --------------------------------------------------------------- jakość */

	register( 'ntc/checklist', {
		edit: function ( props ) {
			var a = props.attributes;

			return el(
				Fragment,
				null,
				zdjecieWPasku( props ),
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Układ', initialOpen: false },
						el( ToggleControl, {
							label: 'Jasnoniebieskie tło',
							checked: !! a.alt,
							onChange: function ( v ) { patch( props, 'alt', v ); }
						} ),
						el( ToggleControl, {
							label: 'Ciemne tło (granat)',
							help: 'Ma pierwszeństwo przed jasnoniebieskim.',
							checked: !! a.dark,
							onChange: function ( v ) { patch( props, 'dark', v ); }
						} ),
						el( ToggleControl, {
							label: 'Zdjęcie po lewej',
							help: 'Włącz w co drugiej sekcji, żeby układ się przeplatał.',
							checked: !! a.flip,
							onChange: function ( v ) { patch( props, 'flip', v ); }
						} )
					),
					zdjeciePanel( props, { kadr: { pozycja: true, ksztalt: true, dopasowanie: true } } )
				),
				el(
					'div',
					useBlockProps(),
					frame( 'Lista z ptaszkami', !! a.dark, dwieKolumny(
						[
							napis( props, 'label', '', 'Nadtytuł' ),
							rich( props, 'title', 'h2', 'ntc-edit-h2', 'Nagłówek' ),
							rich( props, 'intro', 'p', 'ntc-edit-body', 'Zdanie wprowadzające' ),
							el( 'div', { className: 'ntc-edit-list' },
								el( InnerBlocks, {
									allowedBlocks: [ 'ntc/check-item' ],
									template: [ [ 'ntc/check-item' ], [ 'ntc/check-item' ], [ 'ntc/check-item' ] ],
									renderAppender: InnerBlocks.ButtonBlockAppender
								} ) )
						],
						zdjecieWBloku( props, { zastepcze: true, proporcje: '4 / 5' } ),
						!! a.flip
					) )
				)
			);
		}
	} );

	register( 'ntc/check-item', {
		edit: function ( props ) {
			return el(
				'div',
				useBlockProps( { className: 'ntc-edit-row' } ),
				rich( props, 'text', 'p', 'ntc-edit-body', 'Punkt listy' )
			);
		}
	} );

	register( 'ntc/quote-band', {
		edit: function ( props ) {
			return el(
				'div',
				useBlockProps(),
				frame( 'Pas z cytatem', true,
					rich( props, 'text', 'p', 'ntc-edit-h3', 'Jedno zdanie na środku pasa' )
				)
			);
		}
	} );

	register( 'ntc/certs', {
		edit: function ( props ) {
			return el(
				'div',
				useBlockProps(),
				frame( 'Certyfikaty', false, el(
					Fragment,
					null,
					napis( props, 'label', '', 'Nadtytuł' ),
					rich( props, 'title', 'h2', 'ntc-edit-h2', 'Nagłówek' ),
					rich( props, 'p1', 'p', 'ntc-edit-body', 'Pierwszy akapit' ),
					rich( props, 'p2', 'p', 'ntc-edit-body', 'Drugi akapit' ),
					el( 'div', { className: 'ntc-edit-grid' },
						el( InnerBlocks, {
							allowedBlocks: [ 'ntc/cert' ],
							template: [ [ 'ntc/cert' ], [ 'ntc/cert' ], [ 'ntc/cert' ] ],
							renderAppender: InnerBlocks.ButtonBlockAppender
						} ) )
				) )
			);
		}
	} );

	/** Wiersz dokumentu pod godłem: podpis i plik PDF. */
	function dokument( props, n ) {
		var poleTekst = 'line' + n;
		var poleUrl = poleTekst + 'Url';
		var plik = props.attributes[ poleUrl ] || '';

		return el(
			'div',
			{ className: 'ntc-edit-dokument' },
			pole( props, poleTekst, 'Dokument ' + n + ' - podpis (puste = bez wiersza)', 'ntc-edit-dokument__podpis' ),
			el(
				MediaUploadCheck,
				null,
				el( MediaUpload, {
					allowedTypes: [ 'application/pdf', 'image' ],
					onSelect: function ( media ) {
						patch( props, poleUrl, media.url );
					},
					render: function ( okno ) {
						return el( Button, { variant: 'secondary', size: 'small', icon: 'media-document', onClick: okno.open },
							plik ? 'Zmień plik' : 'Wybierz plik' );
					}
				} )
			),
			plik
				? el( 'span', { className: 'ntc-edit-dokument__plik', title: plik }, skrocAdres( plik ) )
				: el( 'span', { className: 'ntc-edit-dokument__plik is-empty' }, 'bez pliku - sam napis' ),
			plik
				? el( Button, {
					variant: 'tertiary',
					size: 'small',
					isDestructive: true,
					onClick: function () {
						patch( props, poleUrl, '' );
					}
				}, 'Odepnij' )
				: null
		);
	}

	register( 'ntc/cert', {
		edit: function ( props ) {
			/* Trzy pary "podpis + plik". Skan wybiera się z biblioteki mediów,
			   więc wgranie nowego PDF-u i podpięcie go to jedno okno. */
			var adresy = [ 1, 2, 3 ].map( function ( n ) {
				var poleUrl = 'line' + n + 'Url';

				return el( TextControl, {
					label: 'Dokument ' + n + ' - adres pliku',
					value: props.attributes[ poleUrl ] || '',
					onChange: function ( v ) { patch( props, poleUrl, v ); }
				} );
			} );

			return el(
				Fragment,
				null,
				zdjecieWPasku( props, 'godło' ),
				el(
					InspectorControls,
					null,
					zdjeciePanel( props, { tytul: 'Godło - opis' } ),
					el.apply( null, [ PanelBody, { title: 'Dokumenty - adresy ręcznie', initialOpen: false } ].concat( adresy ) )
				),
				el(
					'div',
					useBlockProps( { className: 'ntc-edit-card' } ),
					zdjecieWBloku( props, { nazwa: 'godło', zmiesc: true, male: true, pusto: 'Bez godła - na stronie widać samą nazwę instytucji' } ),
					rich( props, 'issuer', 'h3', 'ntc-edit-h3', 'Nazwa instytucji' ),
					dokument( props, 1 ),
					dokument( props, 2 ),
					dokument( props, 3 )
				)
			);
		}
	} );

	register( 'ntc/product-form', {
		edit: function ( props ) {
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Temat zapytania' },
						el( TextControl, {
							label: 'Temat wybrany z góry',
							help: 'Wpisz dokładnie tak, jak brzmi pozycja na liście tematów formularza.',
							value: props.attributes.subject || '',
							onChange: function ( v ) { patch( props, 'subject', v ); }
						} )
					),
					el(
						PanelBody,
						{ title: 'Tekst pod nagłówkiem', initialOpen: false },
						el( ToggleControl, {
							label: 'Każde zdanie w osobnym wierszu',
							help: 'Wyłącz, gdy pierwsze zdanie jest za długie na jeden wiersz - tekst popłynie zwykłym akapitem.',
							checked: false !== props.attributes.splitSentences,
							onChange: function ( v ) { patch( props, 'splitSentences', v ); }
						} )
					)
				),
				el(
					'div',
					useBlockProps(),
					frame( 'Formularz zapytania', false, el(
						Fragment,
						null,
						napis( props, 'label', '', 'Nadtytuł' ),
						rich( props, 'title', 'h2', 'ntc-edit-h2', 'Nagłówek' ),
						rich( props, 'sub', 'p', 'ntc-edit-body', 'Zdanie pod nagłówkiem' ),
						wskazowka( props.attributes.subject
							? 'Formularz przyjdzie z tematem: ' + props.attributes.subject
							: 'Bez tematu formularz pokaże pustą listę do wyboru.' )
					) )
				)
			);
		}
	} );

	register( 'ntc/origin-map', {
		edit: function ( props ) {
			return el(
				Fragment,
				null,
				zdjecieWPasku( props, 'grafikę' ),
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Kraje' },
						el( TextareaControl, {
							label: 'Lista krajów',
							help: 'Po przecinku albo w kolejnych liniach.',
							value: props.attributes.countries || '',
							onChange: function ( v ) { patch( props, 'countries', v ); }
						} ),
						el( ToggleControl, {
							label: 'Pokaż listę pod mapą',
							checked: props.attributes.showList !== false,
							onChange: function ( v ) { patch( props, 'showList', v ); }
						} )
					),
					zdjeciePanel( props, { tytul: 'Grafika mapy - opis' } )
				),
				el(
					'div',
					useBlockProps(),
					frame( 'Mapa pochodzenia', false, dwieKolumny(
						[
							napis( props, 'label', '', 'Nadtytuł' ),
							rich( props, 'title', 'h2', 'ntc-edit-h2', 'Nagłówek' ),
							rich( props, 'sub', 'p', 'ntc-edit-body', 'Zdanie pod nagłówkiem' )
						],
						zdjecieWBloku( props, { nazwa: 'grafikę', zmiesc: true, pusto: 'Brak grafiki mapy' } )
					) )
				)
			);
		}
	} );

} )( window.wp );
