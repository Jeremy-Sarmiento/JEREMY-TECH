#!/usr/bin/env bash
# Smoke test HTTP mínimo. Requiere la app configurada por variables de entorno
# (DB_HOST, DB_USER, DB_PASS, DB_NAME) y el dump importado.
set -euo pipefail

base=${BASE_URL:-http://127.0.0.1:8000}
log=$(mktemp)
php -S 127.0.0.1:8000 -t public >"$log" 2>&1 &
pid=$!
trap 'kill "$pid" 2>/dev/null || true; rm -f "$log"' EXIT

for _ in $(seq 1 30); do
    if curl -fsS "$base/" -o /tmp/jt-smoke-home.html 2>/dev/null; then
        break
    fi
    sleep 1
done

if ! grep -q 'Jeremy' /tmp/jt-smoke-home.html 2>/dev/null; then
    echo "La portada no respondió:" >&2
    cat "$log" >&2
    exit 1
fi

check() {
    local url=$1 esperado=$2
    local code
    code=$(curl -s -o /dev/null -w '%{http_code}' "$url")
    if [ "$code" != "$esperado" ]; then
        echo "FAIL $url -> $code (esperado $esperado)" >&2
        exit 1
    fi
    echo "OK   $url -> $code"
}

check "$base/" 200
check "$base/?ruta=login" 200
check "$base/?ruta=catalogo" 200
check "$base/?ruta=admin" 302

code=$(curl -s -o /dev/null -w '%{http_code}' -X POST "$base/?ruta=carrito/agregar")
if [ "$code" != 403 ]; then
    echo "FAIL POST sin CSRF -> $code (esperado 403)" >&2
    exit 1
fi
echo "OK   POST sin CSRF -> 403"
