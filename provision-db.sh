#!/bin/bash
set -euo pipefail

# Variables (ajusta si quieres)
DB_NAME="taller"
DB_USER="webuser"
DB_PASS="webpass"
DB_NETWORK_CIDR="192.168.56.0/24"   # red privada donde están tus VMs
PG_LISTEN_ADDRESSES="*"

# --- 1) Actualizar e instalar PostgreSQL ---
echo ">>> Updating package lists and installing postgresql..."
if ! command -v psql >/dev/null 2>&1 ; then
  sudo apt-get update -y
  sudo DEBIAN_FRONTEND=noninteractive apt-get install -y postgresql postgresql-contrib
else
  echo ">>> postgresql already installed"
fi

# --- 2) Asegurar que PostgreSQL esté corriendo ---
echo ">>> Enabling and starting postgresql service..."
sudo systemctl enable postgresql --now

# --- 3) Configurar PostgreSQL para escuchar en interfaces (si no está hecho) ---
PG_CONF="/etc/postgresql/$(ls /etc/postgresql)/main/postgresql.conf"
# find path robustly
PG_CONF_PATH="$(sudo bash -lc "ls -d /etc/postgresql/*/main 2>/dev/null || true")"
if [ -n "$PG_CONF_PATH" ]; then
  PG_CONF="$PG_CONF_PATH/postgresql.conf"
fi

if sudo grep -Eiq "^[#\s]*listen_addresses\s*=\s*'$PG_LISTEN_ADDRESSES'" "$PG_CONF"; then
  echo ">>> listen_addresses already set to '$PG_LISTEN_ADDRESSES' in $PG_CONF"
else
  echo ">>> Setting listen_addresses = '$PG_LISTEN_ADDRESSES' in $PG_CONF"
  sudo sed -ri "s/^[#\s]*listen_addresses\s*=.*/listen_addresses = '$PG_LISTEN_ADDRESSES'/" "$PG_CONF" || \
    echo "listen_addresses = '$PG_LISTEN_ADDRESSES'" | sudo tee -a "$PG_CONF" >/dev/null
fi

# --- 4) Permitir conexiones desde la red privada en pg_hba.conf (idempotente) ---
PG_HBA="$(sudo bash -lc "ls -d /etc/postgresql/*/main 2>/dev/null")/pg_hba.conf"
HBA_RULE="host    all             all             ${DB_NETWORK_CIDR}            md5"

if sudo grep -Fq "$HBA_RULE" "$PG_HBA" 2>/dev/null; then
  echo ">>> pg_hba already contains rule for $DB_NETWORK_CIDR"
else
  echo ">>> Adding pg_hba rule to allow connections from $DB_NETWORK_CIDR"
  echo "$HBA_RULE" | sudo tee -a "$PG_HBA" >/dev/null
fi

# --- 5) Reiniciar PostgreSQL para aplicar cambios ---
echo ">>> Restarting postgresql..."
sudo systemctl restart postgresql

# --- 6) Crear base de datos si no existe ---
echo ">>> Checking/creating database '$DB_NAME'..."
DB_EXISTS=$(sudo -u postgres psql -tAc "SELECT 1 FROM pg_database WHERE datname='${DB_NAME}'")
if [ "$DB_EXISTS" = "1" ]; then
  echo ">>> Database '${DB_NAME}' already exists"
else
  sudo -u postgres psql -c "CREATE DATABASE ${DB_NAME};"
  echo ">>> Database '${DB_NAME}' created"
fi

# --- 7) Crear usuario/rol si no existe y asignar contraseña ---
echo ">>> Checking/creating role '$DB_USER'..."
ROLE_EXISTS=$(sudo -u postgres psql -tAc "SELECT 1 FROM pg_roles WHERE rolname='${DB_USER}'")
if [ "$ROLE_EXISTS" = "1" ]; then
  echo ">>> Role '${DB_USER}' already exists, altering password..."
  sudo -u postgres psql -c "ALTER ROLE ${DB_USER} WITH PASSWORD '${DB_PASS}';"
else
  sudo -u postgres psql -c "CREATE ROLE ${DB_USER} WITH LOGIN PASSWORD '${DB_PASS}';"
  echo ">>> Role '${DB_USER}' created"
fi

# --- 8) Conceder permisos sobre la base de datos ---
echo ">>> Granting privileges on database '${DB_NAME}' to '${DB_USER}'..."
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE ${DB_NAME} TO ${DB_USER};"

# --- 9) Crear tabla y datos de ejemplo (si no existen) ---
echo ">>> Creating table 'usuarios' and inserting sample data if needed..."
TABLE_EXISTS=$(sudo -u postgres psql -d "${DB_NAME}" -tAc "SELECT to_regclass('public.usuarios')")
if [ "$TABLE_EXISTS" = "usuarios" ]; then
  echo ">>> Table 'usuarios' already exists"
else
  sudo -u postgres psql -d "${DB_NAME}" -c "
    CREATE TABLE public.usuarios (
      id SERIAL PRIMARY KEY,
      nombre VARCHAR(100) NOT NULL
    );
  "
  sudo -u postgres psql -d "${DB_NAME}" -c "
    INSERT INTO public.usuarios (nombre) VALUES
      ('Ana'),
      ('Luis'),
      ('Carlos');
  "
  echo ">>> Table 'usuarios' created and sample data inserted"
fi

echo ">>> provision-db.sh completed successfully."
echo ">>> DB connection example: psql -h <DB_HOST_IP> -U ${DB_USER} -d ${DB_NAME} (password: ${DB_PASS})"
