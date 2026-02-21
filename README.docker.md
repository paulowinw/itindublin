# Local development with Docker

This project includes a Docker Compose setup to run WordPress locally using the repository's `wp-content` directory so you can develop plugins/themes against a real site.

Quick start

1. Copy `.env.example` to `.env` and update values:

```bash
cp .env.example .env
# edit .env and set secure passwords
```

2. Start containers:

```bash
docker compose up -d
```

3. Open the site in your browser:

- WordPress: http://localhost:8000
- phpMyAdmin: http://localhost:8080

Notes
- The project's `wp-content` is mounted into the container at `/var/www/html/wp-content` so plugins and themes in the repo are available in the running site.
- Default DB user/password in `.env.example` are placeholders — change them before using in any shared environment.

To stop and remove containers:

```bash
docker compose down
```
