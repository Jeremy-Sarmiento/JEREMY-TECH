# Ejecutar con Docker

```bash
docker compose up --build
```

La aplicación queda en <http://localhost:8080>.

## Variables de entorno

Copia `.env.example` a `.env` y define al menos `DATABASE_URL` (o `DB_*`).
Compose carga `.env` si existe; no lo incluyas en la imagen ni en Git.

## Parar y limpiar

```bash
docker compose down
docker compose down --rmi local
```
