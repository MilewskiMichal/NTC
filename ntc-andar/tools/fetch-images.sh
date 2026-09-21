#!/usr/bin/env bash
#
# Pobiera zdjęcia z prototypu do assets/img/.
#
# Prototyp z Claude Design korzystał ze zdjęć hostowanych na Unsplashu. Ten
# skrypt ściąga dokładnie te same kadry i zapisuje je pod nazwami, których
# oczekuje motyw - po uruchomieniu ntc_img() przestaje pokazywać placeholdery
# i zaczyna serwować fotografie.
#
# UWAGA: to są zdjęcia stockowe wstawione w prototypie jako wypełniacz. Przed
# publikacją warto podmienić je na własne materiały (te same nazwy plików,
# dowolne z rozszerzeń jpg/jpeg/png/webp/avif) i sprawdzić licencję Unsplasha
# dla tych, które zostaną.
#
# Użycie:  bash tools/fetch-images.sh

set -euo pipefail

cd "$(dirname "$0")/.."
mkdir -p assets/img

UA='Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120 Safari/537.36'

# slug|adres źródłowy (zgodny z prototypem, łącznie z parametrami kadrowania)
IMAGES=(
  "hero|https://images.unsplash.com/photo-1614935151651-0bea6508db6b?w=700&h=700&fit=crop&q=85"
  "about|https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=800&h=600&fit=crop&q=80"
  "contact|https://images.unsplash.com/photo-1560250097-0b93528c311a?w=500&h=500&fit=crop&q=80"

  "offer-api|https://images.unsplash.com/photo-1532187863486-abf9dbad1b69?w=600&h=300&fit=crop&q=80"
  "offer-probiotics|https://images.unsplash.com/photo-1559757175-0eb30cd8c063?w=600&h=300&fit=crop&q=80"
  "offer-lactoferrin|https://images.unsplash.com/photo-1559757148-5c350d0d3c56?w=600&h=300&fit=crop&q=80"
  "offer-machines|https://images.unsplash.com/photo-1565043589221-1a6fd9ae45c7?w=600&h=300&fit=crop&q=80"
  "offer-components|https://images.unsplash.com/photo-1571781926291-c477ebfd024b?w=600&h=300&fit=crop&q=80"
  "offer-docs|https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=600&h=300&fit=crop&q=80"

  "cat-api|https://images.unsplash.com/photo-1532187863486-abf9dbad1b69?w=700&h=525&fit=crop&q=80"
  "cat-probiotyki|https://images.unsplash.com/photo-1559757148-5c350d0d3c56?w=700&h=525&fit=crop&q=80"
  "cat-laktoferyna|https://images.unsplash.com/photo-1628352081506-83c43123ed6d?w=700&h=525&fit=crop&q=80"
  "cat-maszyny|https://images.unsplash.com/photo-1565043589221-1a6fd9ae45c7?w=700&h=525&fit=crop&q=80"
)

failed=0

for entry in "${IMAGES[@]}"; do
  slug="${entry%%|*}"
  url="${entry#*|}"
  out="assets/img/${slug}.jpg"

  printf '%-20s ' "$slug"

  if curl -fsSL -A "$UA" "$url" -o "$out.tmp"; then
    # Odsiewamy odpowiedzi, które przeszły z kodem 200, ale nie są obrazkiem
    # (strony błędu proxy, komunikaty o blokadzie) - inaczej zapiszemy HTML
    # pod nazwą .jpg i motyw pokaże zbite zdjęcie.
    if [ "$(head -c 2 "$out.tmp" | od -An -tx1 | tr -d ' \n')" = "ffd8" ]; then
      mv "$out.tmp" "$out"
      printf 'OK  (%s)\n' "$(du -h "$out" | cut -f1)"
    else
      rm -f "$out.tmp"
      printf 'BŁĄD - odpowiedź nie jest plikiem JPEG\n'
      failed=$((failed + 1))
    fi
  else
    rm -f "$out.tmp"
    printf 'BŁĄD - nie udało się pobrać\n'
    failed=$((failed + 1))
  fi
done

echo
if [ "$failed" -gt 0 ]; then
  echo "Nie pobrano $failed z ${#IMAGES[@]} zdjęć. Te miejsca zostaną z placeholderami."
  exit 1
fi

echo "Pobrano wszystkie ${#IMAGES[@]} zdjęć do assets/img/."
