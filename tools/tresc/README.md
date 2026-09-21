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
