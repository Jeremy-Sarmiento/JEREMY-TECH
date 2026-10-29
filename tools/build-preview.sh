#!/usr/bin/env bash
# Genera una vista estática de la portada para GitHub Pages.
# Uso: bash tools/build-preview.sh [_site]
# Requiere el dump importado y las mismas variables de entorno que la app.
set -euo pipefail

out=${1:-_site}
log=$(mktemp)
php -S 127.0.0.1:8099 -t public >"$log" 2>&1 &
pid=$!
trap 'kill "$pid" 2>/dev/null || true; rm -f "$log"' EXIT

for _ in $(seq 1 30); do
    if curl -fsS http://127.0.0.1:8099/ -o /tmp/jt-preview-home.html 2>/dev/null; then
        break
    fi
    sleep 1
done

if [ ! -s /tmp/jt-preview-home.html ] || grep -q 'No se pudo conectar a la base de datos' /tmp/jt-preview-home.html; then
    echo "No se pudo renderizar la portada:" >&2
    cat "$log" >&2
    exit 1
fi

rm -rf "$out"
mkdir -p "$out"
cp /tmp/jt-preview-home.html "$out/index.html"
cp -r public/css public/js public/img "$out/"
touch "$out/.nojekyll"

echo "Vista estática en $out"
