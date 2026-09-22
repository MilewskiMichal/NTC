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
 */

( function ( wp ) {
	'use strict';

	if ( ! wp || ! wp.blocks || ! wp.element ) {
		return;
	}

	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;

	var blockEditor = wp.blockEditor;
	var components = wp.components;

	var RichText = blockEditor.RichText;
	var InnerBlocks = blockEditor.InnerBlocks;
	var MediaUpload = blockEditor.MediaUpload;
	var MediaUploadCheck = blockEditor.MediaUploadCheck;
	var InspectorControls = blockEditor.InspectorControls;
	var useBlockProps = blockEditor.useBlockProps;

	var PanelBody = components.PanelBody;
	var TextControl = components.TextControl;
	var TextareaControl = components.TextareaControl;
	var ToggleControl = components.ToggleControl;
	var SelectControl = components.SelectControl;
	var Button = components.Button;

	var defs = window.ntcBlocks || {};
	var data = window.ntcBlocksData || { categories: {}, icons: [] };

	/* ------------------------------------------------------------ pomocnicze */

	/**
	 * Pole RichText podpięte do atrybutu bloku.
	 */
	function rich( props, attr, tag, className, placeholder ) {
		return el( RichText, {
			tagName: tag,
			className: className,
			value: props.attributes[ attr ],
			onChange: function ( value ) {
				var patch = {};
				patch[ attr ] = value;
				props.setAttributes( patch );
			},
			placeholder: placeholder,
			// Pola z projektu są jednoakapitowe - Enter ma nie tworzyć bloku.
			allowedFormats: [ 'core/bold', 'core/italic', 'core/link' ]
		} );
	}

	/**
	 * Wybór obrazka z biblioteki mediów.
	 */
	function imagePanel( props, label ) {
		var attrs = props.attributes;

		return el(
			PanelBody,
			{ title: label || 'Zdjęcie', initialOpen: false },
			el(
				MediaUploadCheck,
				null,
				el( MediaUpload, {
					allowedTypes: [ 'image' ],
					value: attrs.imageId,
					onSelect: function ( media ) {
						props.setAttributes( {
							imageId: media.id,
							imageUrl: media.url,
							imageAlt: media.alt || attrs.imageAlt || ''
						} );
					},
					render: function ( open ) {
						return el(
							Fragment,
							null,
							attrs.imageUrl
								? el( 'img', {
									src: attrs.imageUrl,
									alt: '',
									style: { width: '100%', borderRadius: '8px', marginBottom: '8px' }
								} )
								: null,
							el(
								Button,
								{ variant: 'secondary', onClick: open.open },
								attrs.imageUrl ? 'Zmień zdjęcie' : 'Wybierz zdjęcie'
							),
							attrs.imageUrl
								? el(
									Button,
									{
										variant: 'link',
										isDestructive: true,
										style: { marginLeft: '8px' },
										onClick: function () {
											props.setAttributes( { imageId: undefined, imageUrl: '', imageAlt: '' } );
										}
									},
									'Usuń'
								)
								: null
						);
					}
				} )
			),
			el( TextControl, {
				label: 'Opis alternatywny',
				help: 'Czytany przez czytniki ekranu i pokazywany, gdy zdjęcie się nie wczyta.',
				value: attrs.imageAlt || '',
				onChange: function ( value ) {
					props.setAttributes( { imageAlt: value } );
				}
			} ),
			el( SelectControl, {
				label: 'Dopasowanie',
				help: 'Logotypy i certyfikaty ustawiaj na "zmieść w całości" - inaczej zostaną przycięte.',
				value: attrs.imageFit || 'cover',
				options: [
					{ label: 'Wypełnij ramkę (zdjęcia)', value: 'cover' },
					{ label: 'Zmieść w całości (logotypy)', value: 'contain' }
				],
				onChange: function ( value ) {
					props.setAttributes( { imageFit: value } );
				}
			} ),
			el( SelectControl, {
				label: 'Punkt kadrowania',
				help: 'Która część zdjęcia ma zostać w ramce, gdy trzeba je przyciąć.',
				value: attrs.imagePos || '',
				options: ( function () {
					var opcje = [
						{ label: 'Środek', value: '' },
						{ label: 'Góra', value: 'center top' },
						{ label: 'Dół', value: 'center bottom' },
						{ label: 'Lewa strona', value: 'left center' },
						{ label: 'Prawa strona', value: 'right center' }
					];
					// Punkt ustawiony dokładniej niż na liście (np. "92% 50%")
					// musi być widoczny, inaczej pole wyglądałoby na puste.
					var znany = opcje.some( function ( o ) { return o.value === ( attrs.imagePos || '' ); } );
					if ( ! znany ) {
						opcje.push( { label: 'Ustawiony ręcznie: ' + attrs.imagePos, value: attrs.imagePos } );
					}
					return opcje;
				}() ),
				onChange: function ( value ) {
					props.setAttributes( { imagePos: value } );
				}
			} ),
			! attrs.imageUrl
				? el( 'p', { style: { color: '#757575', fontSize: '12px' } },
					'Bez wybranego zdjęcia strona pokaże grafikę zastępczą w kolorach marki.' )
				: null
		);
	}

	/**
	 * Para pól tekst + adres dla przycisku.
	 */
	function linkPanel( props, textAttr, urlAttr, title ) {
		return el(
			PanelBody,
			{ title: title || 'Przycisk', initialOpen: false },
			el( TextControl, {
				label: 'Napis na przycisku',
				help: 'Zostaw puste, żeby ukryć przycisk.',
				value: props.attributes[ textAttr ] || '',
				onChange: function ( value ) {
					var patch = {};
					patch[ textAttr ] = value;
					props.setAttributes( patch );
				}
			} ),
			el( TextControl, {
				label: 'Adres',
				help: 'Pełny adres albo kotwica, np. #oferta.',
				value: props.attributes[ urlAttr ] || '',
				onChange: function ( value ) {
					var patch = {};
					patch[ urlAttr ] = value;
					props.setAttributes( patch );
				}
			} )
		);
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
				el(
					InspectorControls,
					null,
					imagePanel( props, 'Zdjęcie w hero' ),
					linkPanel( props, 'btn1Text', 'btn1Url', 'Przycisk główny' ),
					linkPanel( props, 'btn2Text', 'btn2Url', 'Przycisk drugi' )
				),
				el(
					'div',
					useBlockProps(),
					frame( 'Hero', true, el(
						Fragment,
						null,
						rich( props, 'badge', 'p', 'ntc-edit-badge', 'Nadtytuł' ),
						rich( props, 'title', 'h1', 'ntc-edit-h1', 'Nagłówek główny' ),
						rich( props, 'sub', 'p', 'ntc-edit-sub', 'Zdanie wprowadzające' ),
						el( 'p', { className: 'ntc-edit-hint' },
							'Przyciski i zdjęcie ustawisz w panelu po prawej.' )
					) )
				)
			);
		}
	} );

	register( 'ntc/about', {
		edit: function ( props ) {
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					imagePanel( props, 'Zdjęcie sekcji' ),
					el(
						PanelBody,
						{ title: 'Liczby', initialOpen: false },
						el( TextControl, {
							label: 'Liczba na karcie', value: props.attributes.accentNum || '',
							onChange: function ( v ) { props.setAttributes( { accentNum: v } ); }
						} ),
						el( TextControl, {
							label: 'Podpis karty', value: props.attributes.accentTxt || '',
							onChange: function ( v ) { props.setAttributes( { accentTxt: v } ); }
						} ),
						el( TextControl, {
							label: 'Liczba 1', value: props.attributes.fig1Num || '',
							onChange: function ( v ) { props.setAttributes( { fig1Num: v } ); }
						} ),
						el( TextControl, {
							label: 'Podpis 1', value: props.attributes.fig1Label || '',
							onChange: function ( v ) { props.setAttributes( { fig1Label: v } ); }
						} ),
						el( TextControl, {
							label: 'Liczba 2', value: props.attributes.fig2Num || '',
							onChange: function ( v ) { props.setAttributes( { fig2Num: v } ); }
						} ),
						el( TextControl, {
							label: 'Podpis 2', value: props.attributes.fig2Label || '',
							onChange: function ( v ) { props.setAttributes( { fig2Label: v } ); }
						} )
					)
				),
				el(
					'div',
					useBlockProps(),
					frame( 'O nas', false, el(
						Fragment,
						null,
						rich( props, 'label', 'p', 'ntc-edit-eyebrow', 'Nadtytuł sekcji' ),
						rich( props, 'title', 'h2', 'ntc-edit-h2', 'Nagłówek sekcji' ),
						rich( props, 'p1', 'p', 'ntc-edit-body', 'Pierwszy akapit' ),
						rich( props, 'p2', 'p', 'ntc-edit-body', 'Drugi akapit' ),
						rich( props, 'p3', 'p', 'ntc-edit-body', 'Trzeci akapit' )
					) )
				)
			);
		}
	} );

	register( 'ntc/prose', {
		edit: function ( props ) {
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					imagePanel( props, 'Zdjęcie obok tekstu' ),
					el(
						PanelBody,
						{ title: 'Tło sekcji', initialOpen: false },
						el( ToggleControl, {
							label: 'Jasnoniebieskie tło',
							help: 'Włącz, gdy kilka bloków tekstowych stoi pod rząd i mają się od siebie odcinać.',
							checked: !! props.attributes.alt,
							onChange: function ( value ) {
								props.setAttributes( { alt: value } );
							}
						} )
					)
				),
				el(
					'div',
					useBlockProps(),
					frame( 'Blok tekstowy', false, el(
						Fragment,
						null,
						rich( props, 'label', 'p', 'ntc-edit-eyebrow', 'Nadtytuł sekcji' ),
						rich( props, 'title', 'h2', 'ntc-edit-h2', 'Nagłówek sekcji' ),
						rich( props, 'lead', 'p', 'ntc-edit-body', 'Zdanie wprowadzające' ),
						// Bez listy dozwolonych bloków - treść składa się ze
						// zwykłych nagłówków, akapitów i list WordPressa.
						el( InnerBlocks, {
							template: [ [ 'core/heading', { level: 2 } ], [ 'core/paragraph' ] ],
							renderAppender: InnerBlocks.ButtonBlockAppender
						} )
					) )
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
					rich( props, 'label', 'p', 'ntc-edit-eyebrow', 'Nadtytuł sekcji' ),
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
							onChange: function ( v ) { props.setAttributes( { icon: v } ); }
						} )
					)
				),
				el(
					'div',
					useBlockProps( { className: 'ntc-edit-card' } ),
					rich( props, 'title', 'h3', 'ntc-edit-h3', 'Tytuł kafelka' ),
					rich( props, 'text', 'p', 'ntc-edit-body', 'Opis kafelka' )
				)
			);
		}
	} );

	register( 'ntc/offer', {
		edit: function ( props ) {
			return el(
				Fragment,
				null,
				el( InspectorControls, null, linkPanel( props, 'ctaText', 'ctaUrl', 'Przycisk nad kafelkami' ) ),
				el(
					'div',
					useBlockProps(),
					frame( 'Oferta - kafelki', false, el(
						Fragment,
						null,
						rich( props, 'label', 'p', 'ntc-edit-eyebrow', 'Nadtytuł sekcji' ),
						rich( props, 'title', 'h2', 'ntc-edit-h2', 'Nagłówek sekcji' ),
						el( 'div', { className: 'ntc-edit-grid ntc-edit-grid--3' },
							el( InnerBlocks, {
								allowedBlocks: [ 'ntc/offer-card' ],
								template: [ [ 'ntc/offer-card' ], [ 'ntc/offer-card' ], [ 'ntc/offer-card' ] ],
								renderAppender: InnerBlocks.ButtonBlockAppender
							} ) )
					) )
				)
			);
		}
	} );

	register( 'ntc/offer-card', {
		edit: function ( props ) {
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					imagePanel( props, 'Zdjęcie kafelka' ),
					linkPanel( props, 'linkText', 'linkUrl', 'Odnośnik pod opisem' )
				),
				el(
					'div',
					useBlockProps( { className: 'ntc-edit-card' } ),
					props.attributes.imageUrl
						? el( 'img', { src: props.attributes.imageUrl, alt: '', className: 'ntc-edit-thumb' } )
						: el( 'div', { className: 'ntc-edit-thumb ntc-edit-thumb--empty' }, 'brak zdjęcia' ),
					rich( props, 'tag', 'p', 'ntc-edit-tag', 'Plakietka' ),
					rich( props, 'title', 'h3', 'ntc-edit-h3', 'Tytuł' ),
					rich( props, 'text', 'p', 'ntc-edit-body', 'Opis' )
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
					onChange: function ( v ) { props.setAttributes( { number: parseInt( v, 10 ) || 0 } ); }
				} ),
				el( TextControl, {
					label: 'Końcówka',
					help: 'Np. + albo %.',
					value: props.attributes.suffix || '',
					onChange: function ( v ) { props.setAttributes( { suffix: v } ); }
				} ),
				el( TextareaControl, {
					label: 'Podpis',
					help: 'Enter dzieli podpis na dwa wiersze, tak jak w projekcie.',
					value: props.attributes.label || '',
					onChange: function ( v ) { props.setAttributes( { label: v } ); }
				} )
			);
		}
	} );

	register( 'ntc/logos', {
		edit: function ( props ) {
			return el(
				'div',
				useBlockProps(),
				frame( 'Karuzela klientów', false, el(
					Fragment,
					null,
					rich( props, 'label', 'p', 'ntc-edit-eyebrow', 'Nadtytuł' ),
					el( TextareaControl, {
						label: 'Nazwy klientów',
						help: 'Jedna nazwa na wiersz. Taśma sama się zapętla.',
						rows: 8,
						value: props.attributes.names || '',
						onChange: function ( v ) { props.setAttributes( { names: v } ); }
					} )
				) )
			);
		}
	} );

	register( 'ntc/steps', {
		edit: function ( props ) {
			return el(
				Fragment,
				null,
				el( InspectorControls, null, linkPanel( props, 'ctaText', 'ctaUrl', 'Przycisk pod opisem' ) ),
				el(
					'div',
					useBlockProps(),
					frame( 'Jak działamy', false, el(
						Fragment,
						null,
						rich( props, 'label', 'p', 'ntc-edit-eyebrow', 'Nadtytuł sekcji' ),
						rich( props, 'title', 'h2', 'ntc-edit-h2', 'Nagłówek sekcji' ),
						rich( props, 'sub', 'p', 'ntc-edit-body', 'Akapit wprowadzający' ),
						el( 'div', { className: 'ntc-edit-list' },
							el( InnerBlocks, {
								allowedBlocks: [ 'ntc/step' ],
								template: [ [ 'ntc/step' ], [ 'ntc/step' ], [ 'ntc/step' ], [ 'ntc/step' ] ],
								renderAppender: InnerBlocks.ButtonBlockAppender
							} ) ),
						el( 'p', { className: 'ntc-edit-hint' },
							'Kroki numerują się same - po przestawieniu albo skasowaniu numeracja się poprawi.' )
					) )
				)
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
				el( InspectorControls, null, imagePanel( props, 'Zdjęcie w sekcji' ) ),
				el(
					'div',
					useBlockProps(),
					frame( 'Sekcja kontaktowa', true, el(
						Fragment,
						null,
						rich( props, 'label', 'p', 'ntc-edit-eyebrow', 'Nadtytuł' ),
						rich( props, 'title', 'h2', 'ntc-edit-h2', 'Nagłówek' ),
						rich( props, 'sub', 'p', 'ntc-edit-body', 'Akapit pod nagłówkiem' ),
						el( 'p', { className: 'ntc-edit-hint' },
							'Formularz i dane kontaktowe dokłada motyw. Telefon, e-mail i adres zmienisz w Dostosuj → NTC - Dane firmowe.' )
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
							onChange: function ( v ) { props.setAttributes( { showTabs: v } ); }
						} )
					)
				),
				el(
					'div',
					useBlockProps(),
					frame( 'Hero podstrony', true, el(
						Fragment,
						null,
						rich( props, 'badge', 'p', 'ntc-edit-badge', 'Nadtytuł' ),
						rich( props, 'title', 'h1', 'ntc-edit-h1', 'Nagłówek' ),
						rich( props, 'sub', 'p', 'ntc-edit-sub', 'Zdanie wprowadzające' )
					) )
				)
			);
		}
	} );

	register( 'ntc/category', {
		edit: function ( props ) {
			var cats = data.categories || {};
			var options = [ { label: '- wybierz kategorię -', value: '' } ];

			Object.keys( cats ).forEach( function ( slug ) {
				options.push( { label: cats[ slug ], value: slug } );
			} );

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Kategoria i tabela' },
						el( SelectControl, {
							label: 'Kategoria produktów',
							help: 'Tabela zaciąga produkty z tej kategorii. Produkty dodajesz w menu Produkty.',
							value: props.attributes.category || '',
							options: options,
							onChange: function ( v ) { props.setAttributes( { category: v } ); }
						} ),
						el( ToggleControl, {
							label: 'Pokaż tabelę produktów',
							checked: props.attributes.showTable !== false,
							onChange: function ( v ) { props.setAttributes( { showTable: v } ); }
						} ),
						el( ToggleControl, {
							label: 'Pokaż ramkę „Nie znalazłeś?”',
							checked: props.attributes.showNotFound !== false,
							onChange: function ( v ) { props.setAttributes( { showNotFound: v } ); }
						} ),
						el( ToggleControl, {
							label: 'Jasnoszare tło i zdjęcie po lewej',
							help: 'Włącz w co drugiej kategorii, żeby sekcje się przeplatały.',
							checked: !! props.attributes.alt,
							onChange: function ( v ) { props.setAttributes( { alt: v } ); }
						} )
					),
					imagePanel( props, 'Zdjęcie kategorii' )
				),
				el(
					'div',
					useBlockProps(),
					frame( 'Kategoria oferty', false, el(
						Fragment,
						null,
						rich( props, 'label', 'p', 'ntc-edit-eyebrow', 'Nadtytuł' ),
						rich( props, 'title', 'h2', 'ntc-edit-h2', 'Nagłówek' ),
						rich( props, 'p1', 'p', 'ntc-edit-body', 'Pierwszy akapit' ),
						rich( props, 'p2', 'p', 'ntc-edit-body', 'Drugi akapit' ),
						el( 'p', { className: 'ntc-edit-hint' },
							props.attributes.category
								? 'Tabela pokaże produkty z kategorii: ' + ( cats[ props.attributes.category ] || props.attributes.category )
								: 'Wybierz kategorię w panelu po prawej, żeby tabela miała co pokazać.' )
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
				el(
					InspectorControls,
					null,
					imagePanel( props, 'Zdjęcie sekcji' ),
					linkPanel( props, 'ctaText', 'ctaUrl', 'Przycisk do dostawcy' ),
					el(
						PanelBody,
						{ title: 'Układ', initialOpen: false },
						el( ToggleControl, {
							label: 'Ciemne tło (granat)',
							checked: !! props.attributes.dark,
							onChange: function ( v ) { props.setAttributes( { dark: v } ); }
						} )
					)
				),
				el(
					'div',
					useBlockProps(),
					frame( 'Maszyny', !! props.attributes.dark, el(
						Fragment,
						null,
						rich( props, 'label', 'p', 'ntc-edit-eyebrow', 'Nadtytuł' ),
						rich( props, 'title', 'h2', 'ntc-edit-h2', 'Nagłówek' ),
						rich( props, 'text', 'p', 'ntc-edit-body', 'Opis' )
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
					rich( props, 'badge', 'p', 'ntc-edit-badge', 'Plakietka' ),
					rich( props, 'title', 'h1', 'ntc-edit-h1', 'Nagłówek' ),
					rich( props, 'sub', 'p', 'ntc-edit-sub', 'Zdanie wprowadzające' )
				) )
			);
		}
	} );

	register( 'ntc/contact-panel', {
		edit: function ( props ) {
			return el(
				'div',
				useBlockProps(),
				frame( 'Panel kontaktowy', false, el(
					Fragment,
					null,
					rich( props, 'infoTitle', 'h2', 'ntc-edit-h2', 'Nagłówek kolumny z danymi' ),
					rich( props, 'infoSub', 'p', 'ntc-edit-body', 'Akapit pod nagłówkiem' ),
					el( 'hr' ),
					rich( props, 'formTitle', 'h2', 'ntc-edit-h2', 'Nagłówek formularza' ),
					rich( props, 'formSub', 'p', 'ntc-edit-body', 'Akapit nad formularzem' ),
					el( 'p', { className: 'ntc-edit-hint' },
						'Adres, telefon, e-mail, godziny i NIP dokłada motyw z Dostosuj → NTC - Dane firmowe.' )
				) )
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
						{ title: 'Podgląd mapy' },
						el( TextControl, {
							label: 'Podpis na mapie',
							value: props.attributes.pinLabel || '',
							onChange: function ( v ) { props.setAttributes( { pinLabel: v } ); }
						} )
					)
				),
				el(
					'div',
					useBlockProps(),
					frame( 'Dojazd', false, el(
						Fragment,
						null,
						rich( props, 'label', 'p', 'ntc-edit-eyebrow', 'Nadtytuł' ),
						rich( props, 'title', 'h2', 'ntc-edit-h2', 'Nagłówek' ),
						el( 'div', { className: 'ntc-edit-list' },
							el( InnerBlocks, {
								allowedBlocks: [ 'ntc/map-card' ],
								template: [ [ 'ntc/map-card' ], [ 'ntc/map-card' ], [ 'ntc/map-card' ] ],
								renderAppender: InnerBlocks.ButtonBlockAppender
							} ) )
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
				rich( props, 'label', 'p', 'ntc-edit-eyebrow', 'Nadtytuł kafelka' ),
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
					el( 'p', { className: 'ntc-edit-hint' },
						'Telefon i e-mail na przyciskach biorą się z Dostosuj → NTC - Dane firmowe.' )
				) )
			);
		}
	} );

	/* --------------------------------------------------------------- jakość */

	register( 'ntc/checklist', {
		edit: function ( props ) {
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					imagePanel( props, 'Zdjęcie sekcji' ),
					el(
						PanelBody,
						{ title: 'Układ', initialOpen: false },
						el( ToggleControl, {
							label: 'Jasnoniebieskie tło',
							checked: !! props.attributes.alt,
							onChange: function ( v ) { props.setAttributes( { alt: v } ); }
						} ),
						el( ToggleControl, {
							label: 'Ciemne tło (granat)',
							help: 'Ma pierwszeństwo przed jasnoniebieskim.',
							checked: !! props.attributes.dark,
							onChange: function ( v ) { props.setAttributes( { dark: v } ); }
						} ),
						el( ToggleControl, {
							label: 'Zdjęcie po lewej',
							help: 'Włącz w co drugiej sekcji, żeby układ się przeplatał.',
							checked: !! props.attributes.flip,
							onChange: function ( v ) { props.setAttributes( { flip: v } ); }
						} )
					)
				),
				el(
					'div',
					useBlockProps(),
					frame( 'Lista z ptaszkami', false, el(
						Fragment,
						null,
						rich( props, 'label', 'p', 'ntc-edit-eyebrow', 'Nadtytuł' ),
						rich( props, 'title', 'h2', 'ntc-edit-h2', 'Nagłówek' ),
						rich( props, 'intro', 'p', 'ntc-edit-body', 'Zdanie wprowadzające' ),
						el( 'div', { className: 'ntc-edit-list' },
							el( InnerBlocks, {
								allowedBlocks: [ 'ntc/check-item' ],
								template: [ [ 'ntc/check-item' ], [ 'ntc/check-item' ], [ 'ntc/check-item' ] ],
								renderAppender: InnerBlocks.ButtonBlockAppender
							} ) )
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
					rich( props, 'label', 'p', 'ntc-edit-eyebrow', 'Nadtytuł' ),
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

	register( 'ntc/cert', {
		edit: function ( props ) {
			/* Trzy pary "podpis + plik". Skan wybiera się z biblioteki mediów,
			   więc wgranie nowego PDF-u i podpięcie go to jedno okno. */
			var linie = [ 1, 2, 3 ].map( function ( n ) {
				var poleTekst = 'line' + n;
				var poleUrl = 'line' + n + 'Url';

				return el(
					PanelBody,
					{ title: 'Dokument ' + n, initialOpen: 1 === n },
					el( TextControl, {
						label: 'Podpis',
						help: 'Zostaw puste, żeby ukryć wiersz.',
						value: props.attributes[ poleTekst ] || '',
						onChange: function ( v ) {
							var patch = {};
							patch[ poleTekst ] = v;
							props.setAttributes( patch );
						}
					} ),
					el(
						MediaUploadCheck,
						null,
						el( MediaUpload, {
							allowedTypes: [ 'application/pdf', 'image' ],
							onSelect: function ( media ) {
								var patch = {};
								patch[ poleUrl ] = media.url;
								props.setAttributes( patch );
							},
							render: function ( open ) {
								return el(
									Button,
									{ variant: 'secondary', onClick: open.open },
									props.attributes[ poleUrl ] ? 'Zmień plik' : 'Wybierz plik'
								);
							}
						} )
					),
					el( TextControl, {
						label: 'Adres pliku',
						value: props.attributes[ poleUrl ] || '',
						onChange: function ( v ) {
							var patch = {};
							patch[ poleUrl ] = v;
							props.setAttributes( patch );
						}
					} )
				);
			} );

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					[ imagePanel( props, 'Godło instytucji' ) ].concat( linie )
				),
				el(
					'div',
					useBlockProps( { className: 'ntc-edit-card' } ),
					rich( props, 'issuer', 'h3', 'ntc-edit-h3', 'Nazwa instytucji' ),
					el( 'p', { className: 'ntc-edit-hint' },
						'Godło i skany dokumentów podepniesz w panelu po prawej.' )
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
							onChange: function ( v ) { props.setAttributes( { subject: v } ); }
						} )
					)
				),
				el(
					'div',
					useBlockProps(),
					frame( 'Formularz zapytania', false, el(
						Fragment,
						null,
						rich( props, 'label', 'p', 'ntc-edit-eyebrow', 'Nadtytuł' ),
						rich( props, 'title', 'h2', 'ntc-edit-h2', 'Nagłówek' ),
						rich( props, 'sub', 'p', 'ntc-edit-body', 'Zdanie pod nagłówkiem' ),
						el( 'p', { className: 'ntc-edit-hint' },
							props.attributes.subject
								? 'Formularz przyjdzie z tematem: ' + props.attributes.subject
								: 'Bez tematu formularz pokaże pustą listę do wyboru.' )
					) )
				)
			);
		}
	} );

	register( 'ntc/partner', {
		edit: function ( props ) {
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					imagePanel( props, 'Logotyp partnera' ),
					el(
						PanelBody,
						{ title: 'Odnośnik', initialOpen: false },
						el( TextControl, {
							label: 'Adres strony partnera',
							help: 'Zostaw puste, żeby logotyp nie był klikalny.',
							value: props.attributes.url || '',
							onChange: function ( v ) { props.setAttributes( { url: v } ); }
						} )
					)
				),
				el(
					'div',
					useBlockProps( { className: 'ntc-edit-row' } ),
					rich( props, 'name', 'p', 'ntc-edit-body', 'Nazwa partnera' )
				)
			);
		}
	} );

	register( 'ntc/origin-map', {
		edit: function ( props ) {
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					imagePanel( props, 'Grafika mapy' ),
					el(
						PanelBody,
						{ title: 'Kraje' },
						el( TextareaControl, {
							label: 'Lista krajów',
							help: 'Po przecinku albo w kolejnych liniach.',
							value: props.attributes.countries || '',
							onChange: function ( v ) { props.setAttributes( { countries: v } ); }
						} ),
						el( ToggleControl, {
							label: 'Pokaż listę pod mapą',
							checked: props.attributes.showList !== false,
							onChange: function ( v ) { props.setAttributes( { showList: v } ); }
						} )
					)
				),
				el(
					'div',
					useBlockProps(),
					frame( 'Mapa pochodzenia', false, el(
						Fragment,
						null,
						rich( props, 'label', 'p', 'ntc-edit-eyebrow', 'Nadtytuł' ),
						rich( props, 'title', 'h2', 'ntc-edit-h2', 'Nagłówek' ),
						rich( props, 'sub', 'p', 'ntc-edit-body', 'Zdanie pod nagłówkiem' )
					) )
				)
			);
		}
	} );

} )( window.wp );
