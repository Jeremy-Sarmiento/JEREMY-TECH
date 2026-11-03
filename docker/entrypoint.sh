#!/bin/sh
# Adapta Apache al puerto que Render asigna (PORT) y arranca en primer plano.
set -e

puerto="${PORT:-8080}"

sed -i "s/^Listen 80$/Listen ${puerto}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${puerto}>/" /etc/apache2/sites-available/000-default.conf

exec apache2-foreground
