#!/bin/sh

set -eu

docker_dir=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
app_env="$docker_dir/app.env"
db_env="$docker_dir/db.env"

if [ -e "$app_env" ] || [ -e "$db_env" ]; then
    echo "Docker environment files already exist; leaving them unchanged."
    exit 0
fi

umask 077
db_password=$(openssl rand -hex 32)
root_password=$(openssl rand -hex 32)
app_key="base64:$(openssl rand -base64 32 | tr -d '\n')"

cat > "$db_env" <<EOF
MYSQL_DATABASE=mtalent
MYSQL_USER=mtalent_app
MYSQL_PASSWORD=$db_password
MYSQL_ROOT_PASSWORD=$root_password
EOF

cat > "$app_env" <<EOF
APP_NAME=MTalent
APP_ENV=local
APP_KEY=$app_key
APP_DEBUG=true
APP_URL=http://127.0.0.1:8765
LOG_CHANNEL=stderr
LOG_LEVEL=debug
DB_CONNECTION=mysql
DB_HOST=database
DB_PORT=3306
DB_DATABASE=mtalent
DB_USERNAME=mtalent_app
DB_PASSWORD=$db_password
SESSION_DRIVER=file
SESSION_LIFETIME=120
CACHE_STORE=file
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=local
MAIL_MAILER=log
MAIL_FROM_ADDRESS=hello@example.test
MAIL_FROM_NAME=MTalent
EOF

chmod 600 "$app_env" "$db_env"
echo "Created local Docker environment files."
