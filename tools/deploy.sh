#!/usr/bin/env bash
#
# Wysyła motyw albo wtyczkę na serwer przez trasę /ntc-deploy/v1/push.
#
# Wtyczka rozpakowuje paczkę do katalogu roboczego, sprawdza ją i dopiero
# wtedy podmienia katalog docelowy, robiąc po drodze kopię zapasową. Dzięki
# temu nieudane wdrożenie zostawia stronę w stanie sprzed wysyłki.
#
#   bash tools/deploy.sh                    # motyw
#   bash tools/deploy.sh --target ntc-deploy
#   bash tools/deploy.sh --dry-run          # tylko raport różnic

set -euo pipefail

KATALOG="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
CEL="ntc-andar"
NA_SUCHO="false"

while [[ $# -gt 0 ]]; do
  case "$1" in
    --target) CEL="$2"; shift 2 ;;
    --dry-run) NA_SUCHO="true"; shift ;;
    *) echo "Nieznany argument: $1" >&2; exit 2 ;;
  esac
done

if [[ ! -f "$KATALOG/tools/.env" ]]; then
  echo "Brak pliku tools/.env z dostępami." >&2
  exit 1
fi

set -a
# shellcheck disable=SC1091
. "$KATALOG/tools/.env"
set +a

for zmienna in NTC_SITE NTC_USER NTC_APP_PASS NTC_DEPLOY_KEY; do
  if [[ -z "${!zmienna:-}" ]]; then
    echo "Brak zmiennej $zmienna w tools/.env." >&2
    exit 1
  fi
done

if [[ ! -d "$KATALOG/$CEL" ]]; then
  echo "Brak katalogu $CEL w projekcie." >&2
  exit 1
fi

PACZKA="$(mktemp -d)/$CEL.zip"

# Wysyłamy sam katalog celu, bez otoczki repozytorium - wtyczka oczekuje, że w
# paczce znajdzie folder o nazwie celu z plikiem kontrolnym w środku.
( cd "$KATALOG" && zip -q -r "$PACZKA" "$CEL" -x '*.DS_Store' -x '*/.git/*' )

ROZMIAR=$(( $(wc -c < "$PACZKA") / 1024 ))
echo "Paczka $CEL: ${ROZMIAR} kB"

curl -sS -X POST \
  --user "$NTC_USER:$NTC_APP_PASS" \
  -H "X-NTC-Deploy-Key: $NTC_DEPLOY_KEY" \
  -F "target=$CEL" \
  -F "dry_run=$NA_SUCHO" \
  -F "zip=@$PACZKA" \
  --max-time 180 \
  "$NTC_SITE/wp-json/ntc-deploy/v1/push"

echo
rm -rf "$(dirname "$PACZKA")"
