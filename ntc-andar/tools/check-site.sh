#!/usr/bin/env bash
#
# Diagnostyka instalacji WordPressa przez REST API.
#
# Sprawdza jednym przebiegiem, czy strona odpowiada, czy REST API jest
# dostępne, czy hasło aplikacji działa, jaki motyw jest włączony i czy strony
# z blokami NTC są na miejscu. Przydaje się po wdrożeniu i przy diagnozowaniu
# "u mnie nie działa".
#
# Użycie:
#   bash tools/check-site.sh https://adres-strony.pl [użytkownik] [hasło-aplikacji]
#
# Bez loginu i hasła sprawdza tylko to, co widać publicznie.

set -uo pipefail

SITE="${1:-}"
USER="${2:-}"
PASS="${3:-}"

if [ -z "$SITE" ]; then
  echo "Podaj adres strony, np. bash tools/check-site.sh https://ntc-strona.example.pl" >&2
  exit 2
fi

SITE="${SITE%/}"
AUTH=()
[ -n "$USER" ] && [ -n "$PASS" ] && AUTH=(--user "$USER:$PASS")

ok()   { printf '  \033[32m✓\033[0m %s\n' "$1"; }
bad()  { printf '  \033[31m✗\033[0m %s\n' "$1"; }
info() { printf '    %s\n' "$1"; }

# Ustawia globalne $CODE i $BODY. Celowo nie zwraca przez echo - wywołanie w
# podstawieniu poleceń tworzy podpowłokę i przypisanie do BODY by przepadło.
CODE=""
BODY=""
fetch() {
  local raw
  raw=$(curl -sS -m 20 "${AUTH[@]}" -w $'\n%{http_code}' "$1" 2>&1)
  CODE="${raw##*$'\n'}"
  BODY="${raw%$'\n'*}"
}

# Wyciąga pole z JSON-a bez zależności od jq.
json() {
  python3 -c "
import json,sys
try:
    d = json.load(sys.stdin)
except Exception:
    sys.exit(1)
for key in sys.argv[1].split('.'):
    if isinstance(d, list):
        d = d[int(key)] if d else ''
    else:
        d = d.get(key, '') if isinstance(d, dict) else ''
print(d if not isinstance(d, (dict, list)) else json.dumps(d, ensure_ascii=False))
" "$1" 2>/dev/null
}

echo
echo "Diagnostyka: $SITE"
echo "======================================================================"

echo
echo "1. Czy strona odpowiada"
fetch "$SITE/"
case "$CODE" in
  200) ok "strona odpowiada (HTTP 200)" ;;
  30*) ok "przekierowanie (HTTP $CODE) - sprawdź, czy adres kanoniczny się zgadza" ;;
  403) bad "HTTP 403"; info "$(echo "$BODY" | head -c 160)" ;;
  000|"") bad "brak połączenia"; info "$(echo "$BODY" | head -c 160)" ;;
  *)   bad "HTTP $CODE" ;;
esac

echo
echo "2. REST API"
fetch "$SITE/wp-json/"
if [ "$CODE" = "200" ]; then
  ok "REST API działa"
  info "nazwa: $(echo "$BODY" | json name)"
  info "opis:  $(echo "$BODY" | json description)"
else
  bad "REST API niedostępne (HTTP $CODE)"
  info "częsta przyczyna: wtyczka bezpieczeństwa blokuje /wp-json/ albo brak ładnych odnośników"
fi

if [ ${#AUTH[@]} -eq 0 ]; then
  echo
  echo "Bez loginu i hasła aplikacji dalsze testy pominięte."
  echo
  exit 0
fi

echo
echo "3. Uwierzytelnienie hasłem aplikacji"
fetch "$SITE/wp-json/wp/v2/users/me?context=edit"
if [ "$CODE" = "200" ]; then
  ok "hasło aplikacji działa"
  info "użytkownik: $(echo "$BODY" | json name)"
  info "role:       $(echo "$BODY" | json roles)"
else
  bad "uwierzytelnienie odrzucone (HTTP $CODE)"
  info "$(echo "$BODY" | json message)"
  info "sprawdź: czy hasło to hasło APLIKACJI, czy serwer przepuszcza nagłówek Authorization"
  echo
  exit 1
fi

echo
echo "4. Motyw"
fetch "$SITE/wp-json/wp/v2/themes?status=active"
if [ "$CODE" = "200" ]; then
  NAME=$(echo "$BODY" | json 0.name.raw)
  VER=$(echo "$BODY" | json 0.version)
  ok "włączony motyw: ${NAME:-nieznany} ${VER}"
  case "$NAME" in
    *"NTC Andar"*) ok "to jest nasz motyw" ;;
    *) bad "to nie jest motyw NTC Andar - wgraj go i włącz" ;;
  esac
else
  bad "nie udało się odczytać motywów (HTTP $CODE)"
fi

echo
echo "5. Strony"
fetch "$SITE/wp-json/wp/v2/pages?per_page=100&status=any&context=edit"
if [ "$CODE" = "200" ]; then
  echo "$BODY" | python3 -c "
import json, sys
pages = json.load(sys.stdin)
if not pages:
    print('  \033[31m✗\033[0m nie ma żadnych stron')
else:
    print('  \033[32m✓\033[0m stron: %d' % len(pages))
    for p in pages:
        slug = p.get('slug', '')
        title = (p.get('title') or {}).get('raw', '')
        content = (p.get('content') or {}).get('raw', '')
        blocks = content.count('<!-- wp:ntc/')
        mark = 'bloki NTC: %d' % blocks if blocks else 'bez bloków NTC'
        print('      /%-18s %-28s %s' % (slug, title[:28], mark))
"
else
  bad "nie udało się odczytać stron (HTTP $CODE)"
fi

echo
echo "6. Produkty"
fetch "$SITE/wp-json/wp/v2/ntc_product?per_page=1&status=any"
if [ "$CODE" = "200" ]; then
  TOTAL=$(curl -sSI -m 20 "${AUTH[@]}" "$SITE/wp-json/wp/v2/ntc_product?per_page=1&status=any" 2>/dev/null \
    | tr -d '\r' | awk -F': ' 'tolower($1)=="x-wp-total"{print $2}')
  ok "typ treści Produkty widoczny w API, pozycji: ${TOTAL:-0}"
else
  bad "typ treści Produkty niedostępny (HTTP $CODE)"
  info "jeśli motyw jest włączony, a to nie działa - sprawdź czy show_in_rest jest włączone"
fi

echo
echo "7. Wtyczki"
fetch "$SITE/wp-json/wp/v2/plugins"
if [ "$CODE" = "200" ]; then
  echo "$BODY" | python3 -c "
import json, sys
plugins = json.load(sys.stdin)
active = [p for p in plugins if p.get('status') == 'active']
print('  \033[32m✓\033[0m aktywnych wtyczek: %d' % len(active))
for p in active:
    print('      %s %s' % (p.get('name', ''), p.get('version', '')))
if not any('polylang' in (p.get('plugin') or '').lower() for p in active):
    print('    (Polylang nieaktywny - wersja angielska nie zadziała)')
"
else
  info "lista wtyczek niedostępna (HTTP $CODE) - wymaga uprawnień administratora, przy roli Redaktor to normalne"
fi

echo
echo "======================================================================"
echo
