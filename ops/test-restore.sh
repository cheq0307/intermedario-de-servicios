#!/usr/bin/env bash
set -euo pipefail

: "${RESTORE_TEST_DATABASE:?Define RESTORE_TEST_DATABASE con prefijo plaza_local_restore_test_}"
: "${BACKUP_DATABASE:?Define BACKUP_DATABASE con la ruta .sql.gz}"
: "${BACKUP_DEFAULTS_FILE:?Define BACKUP_DEFAULTS_FILE con el archivo de credenciales MariaDB}"

case "$RESTORE_TEST_DATABASE" in plaza_local_restore_test_*) ;; *) echo "Nombre de base inseguro" >&2; exit 2;; esac
test -f "$BACKUP_DATABASE"

mariadb --defaults-extra-file="$BACKUP_DEFAULTS_FILE" -e "CREATE DATABASE IF NOT EXISTS \`$RESTORE_TEST_DATABASE\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
gzip -dc "$BACKUP_DATABASE" | mariadb --defaults-extra-file="$BACKUP_DEFAULTS_FILE" "$RESTORE_TEST_DATABASE"

for table in users orders payments migrations; do
  count=$(mariadb --defaults-extra-file="$BACKUP_DEFAULTS_FILE" --batch --skip-column-names "$RESTORE_TEST_DATABASE" -e "SELECT COUNT(*) FROM \`$table\`")
  echo "$table: $count filas"
done

echo "Restauracion completa verificada. La base de prueba se conserva para inspeccion y debe eliminarse manualmente."
