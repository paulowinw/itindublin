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


### If you get errors while updating your wordpress
---

## Guide: Fixing WordPress Container Permission Issues

If your WordPress Docker container is asking for FTP credentials or failing to update plugins, it’s usually because the file ownership on your **host** doesn't match the user inside the **container**.

### 1. Identify the Correct User

First, verify that your WordPress container is indeed using `www-data`. Run this command (replace `your_container_name` with your actual container name):

```bash
docker exec -it your_container_name id -u www-data

```

> [!NOTE]
> If the command returns **33**, that is the UID (User ID) we need to grant permissions to.

### 2. Fix Permissions on the Host

Navigate to the directory on your  host where your WordPress files are stored. Run the following command to give the container's web server ownership of the files:

```bash
sudo chown -R 33:33 /path/to/your/wordpress/html

```

> [!IMPORTANT]
> Change `/path/to/your/wordpress/html` to the actual path on your machine (e.g., `./wp-data` or `/home/user/wordpress`).

### 3. Set Proper Directory and File Modes

To ensure WordPress can create the upgrade folder and manage plugins safely, apply the standard WordPress permission set:

**Set directories to 755:**

```bash
find /path/to/your/wordpress/html -type d -exec chmod 755 {} \;

```

**Set files to 644:**

```bash
find /path/to/your/wordpress/html -type f -exec chmod 644 {} \;

```

---

Would you like me to help you create a **docker-compose** snippet that automates these volume permissions in the future?