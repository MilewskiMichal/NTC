# Skrypty treści

Narzędzia, którymi wgrywane są treści na stronę. Trzymamy je w repozytorium,
bo katalog roboczy sesji jest kasowany razem z kontenerem.

| Plik | Do czego |
|---|---|
| `api.py` | warstwa nad REST API WordPressa i wsadem wtyczki `ntc-deploy` |
| `bloki.py` | dzielenie treści Gutenberga na bloki najwyższego poziomu |
| `docx.py` | wyciąganie tekstu z plików Word |
| `blog_konwert.py` | zamiana artykułu z Worda na bloki Gutenberga |
| `wpisy.py` | wgrywanie artykułów na bloga |
| `polityka.py` | tekst Polityki Jakości na podstronie Jakość |
| `api_tekst.py` | dłuższy tekst na podzakładce Substancje czynne (API) |

Dostępy biorą się z `tools/.env` (wzór w `tools/.env.przyklad`).

Uruchamianie z katalogu projektu, na przykład:

    python3 -c "import sys; sys.path.insert(0,'tools/tresc'); import wpisy"

## Wersje angielskie

Tekst angielski stron i wpisów siedzi w osobnych wpisach typu `ntc_en`
("Wersje EN" w kokpicie), powiązanych z polskim oryginałem polem `_ntc_en_id`.
Motyw podmienia treść, tytuł i zajawkę, gdy adres ma `?lang=en`
(`ntc-andar/inc/tlumaczenia.php`).

Pierwsze wersje zbudował `en_wgraj.py`: bierze kopię polskich treści z
`en/zrodla.json`, tłumaczy każdy tekst według słowników `en/s*.py` i zakłada
albo aktualizuje wersję EN. `--na-sucho` wypisuje tylko teksty bez tłumaczenia.
Po zmianach klienta w wersjach EN edytuje się je już w edytorze, nie tu -
ponowne uruchomienie nadpisze ręczne poprawki.
