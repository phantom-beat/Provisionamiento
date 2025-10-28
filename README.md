# Taller de provisionamiento con Vagrant

Este repositorio contiene instrucciones para crear un entorno multi-máquina con Vagrant orientado a prácticas de provisionamiento. Está pensado para una máquina "web" (Apache + PHP) y una máquina "db" (PostgreSQL).

> Nota: Los scripts de provisioning (`provision-web.sh` y `provision-db.sh`) no están incluidos aquí. Puedes añadirlos y referenciarlos desde el `Vagrantfile` si deseas aprovisionamiento automático.

## Requisitos (host)

- VirtualBox
- Vagrant
- Git (opcional, para clonar el repositorio)

En Arch Linux:

```bash
sudo pacman -S vagrant virtualbox
```

## Estructura recomendada

```
taller-provisionamiento/
├── Vagrantfile
├── www/
│   ├── index.html
│   └── info.php
├── provision-web.sh   # opcional
└── provision-db.sh    # opcional
```

## Quickstart (pasos mínimos)

1. Clona el repositorio (opcional) y entra en la carpeta del proyecto:

```bash
git clone https://github.com/jmaquin0/vagrant-web-provisioning.git my-vagrant-project
cd my-vagrant-project
```

2. Levanta las máquinas:

```bash
vagrant up
```

3. Comprueba el estado:

```bash
vagrant status
```

## Ejemplo de Vagrantfile

Guarda esto como `Vagrantfile` (ejemplo mínimo):

```ruby
Vagrant.configure("2") do |config|
  config.vm.box = "ubuntu/focal64"

  config.vm.define "web" do |web|
    web.vm.hostname = "web"
    web.vm.network "private_network", ip: "192.168.56.10"
    # web.vm.provision "shell", path: "provision-web.sh"  # descomenta si tienes el script
  end

  config.vm.define "db" do |db|
    README corregido: este archivo fue formateado a Markdown válido y organizado en secciones.

    db.vm.network "private_network", ip: "192.168.56.11"
    # db.vm.provision "shell", path: "provision-db.sh"   # descomenta si tienes el script

## Direcciones IP (ejemplo)

- web: `192.168.56.10` (servidor web)
- db : `192.168.56.11` (servidor PostgreSQL)

## Datos de ejemplo para la base de datos

- Base de datos: `taller`
- Usuario: `webuser`
- Contraseña: `webpass`
- Puerto: `5432`

## Provisionamiento manual - Máquina `web`

1. Conéctate a la VM web:

```bash
vagrant ssh web
```

2. (Si tienes problemas con el terminal) dentro de la VM:

```bash
export TERM=xterm
```

3. Instala Apache, PHP y la extensión de PostgreSQL:

```bash
sudo apt update -y
sudo apt install -y apache2 php libapache2-mod-php php-pgsql postgresql-client
```

4. Copia los archivos del sitio desde la carpeta compartida `/vagrant/www` a `/var/www/html`:

```bash
sudo mkdir -p /var/www/html
sudo cp -r /vagrant/www/* /var/www/html/
sudo chown -R www-data:www-data /var/www/html
sudo chmod -R 755 /var/www/html
```

5. Reinicia Apache:

```bash
sudo systemctl restart apache2
sudo systemctl status apache2 --no-pager
```

6. Prueba PHP:

```bash
echo "<?php echo 'PHP OK'; ?>" | sudo tee /var/www/html/test.php >/dev/null
# Visita: http://192.168.56.10/test.php
```

## Provisionamiento manual - Máquina `db`

1. Conéctate a la VM db:

```bash
vagrant ssh db
```

```bash
export TERM=xterm
```bash
sudo apt update -y
sudo apt install -y postgresql postgresql-contrib
```

4. Verifica la versión/clúster:

```bash
sudo pg_lsclusters
```

5. Inicia el clúster si está detenido (reemplaza X por la versión detectada):

```bash
sudo pg_ctlcluster X main start
```

6. Crea el usuario y la base de datos (si no existen):

```bash
sudo -u postgres psql -c "CREATE USER webuser WITH PASSWORD 'webpass';" || true
sudo -u postgres psql -c "CREATE DATABASE taller OWNER webuser;" || true
```

7. Habilita conexiones remotas (ejemplo automático):

```bash
PG_VERSION=$(ls /etc/postgresql | head -n1)
sudo sed -i "s/^#listen_addresses.*/listen_addresses = '*'/'" /etc/postgresql/${PG_VERSION}/main/postgresql.conf
echo "host    all             all             192.168.56.0/24            md5" | sudo tee -a /etc/postgresql/${PG_VERSION}/main/pg_hba.conf
sudo systemctl restart postgresql
```

8. Comprueba que PostgreSQL escucha en `0.0.0.0:5432`:

```bash
sudo ss -lntp | grep 5432 || true
```

9. (Opcional) Crea una tabla de ejemplo y datos:

```bash
sudo -u postgres psql -d taller -c "CREATE TABLE IF NOT EXISTS public.usuarios (id SERIAL PRIMARY KEY, nombre VARCHAR(100));"
sudo -u postgres psql -d taller -c "INSERT INTO public.usuarios (nombre) SELECT 'Ana' WHERE NOT EXISTS (SELECT 1 FROM public.usuarios);"
```

10. Concede permisos a `webuser`:

```bash
sudo -u postgres psql -d taller -c "GRANT CONNECT ON DATABASE taller TO webuser;"
sudo -u postgres psql -d taller -c "GRANT USAGE ON SCHEMA public TO webuser;"
sudo -u postgres psql -d taller -c "GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO webuser;"
```

## Probar conexión Web → DB

En la VM `web` instala el cliente y prueba la conexión:

```bash
sudo apt update -y
sudo apt install -y postgresql-client
psql -h 192.168.56.11 -U webuser -d taller
# contraseña: webpass
```

Si obtienes el prompt `taller=#` la conexión es correcta.

Accede desde el host a:

```
http://192.168.56.10/info.php
```

## Comandos útiles (diagnóstico)

- Ver logs de Apache (web): `sudo tail -n 50 /var/log/apache2/error.log`
- Ver logs de PostgreSQL (db): `sudo tail -n 50 /var/log/postgresql/postgresql-*-main.log`
- Ver puertos escuchando: `sudo ss -lntp`
- Reiniciar Apache: `sudo systemctl restart apache2`
- Reiniciar PostgreSQL: `sudo systemctl restart postgresql`

## Detener / limpiar

- Apagar VMs: `vagrant halt`
- Reiniciar sin reprovisionar: `vagrant reload --no-provision`
- Eliminar VMs: `vagrant destroy -f`

## Notas

- Asegúrate de tener los archivos `index.html` e `info.php` en `/vagrant/www` antes de copiarlos a `/var/www/html`.
- Si ves errores del tipo "Error opening terminal: xterm-kitty", exporta `TERM=xterm`.
- Usa `PG_VERSION=$(ls /etc/postgresql | head -n1)` para detectar la versión de PostgreSQL en la VM.
- No incluyas contraseñas o datos sensibles en repositorios públicos.

## Créditos

- Autor: Phantom Beat
- Año: 2025

---

Fin.
# Taller de provisionamiento con Vagrant

Este repositorio contiene instrucciones para crear un entorno multi-máquina con Vagrant orientado a prácticas de provisionamiento. Está pensado para una máquina "web" (Apache + PHP) y una máquina "db" (PostgreSQL).

> Nota: Los scripts de provisioning (`provision-web.sh` y `provision-db.sh`) no están incluidos aquí. Puedes añadirlos y referenciarlos desde el `Vagrantfile` si deseas aprovisionamiento automático.

## Requisitos (host)

- VirtualBox
- Vagrant
- Git (opcional, para clonar el repositorio)

En Arch Linux:

```bash
sudo pacman -S vagrant virtualbox
```

## Estructura recomendada

```
taller-provisionamiento/
├── Vagrantfile
├── www/
│   ├── index.html
│   └── info.php
├── provision-web.sh   # opcional
└── provision-db.sh    # opcional
```

## Quickstart (pasos mínimos)

1. Clona el repositorio (opcional) y entra en la carpeta del proyecto:

2. Levanta las máquinas:

```bash
3. Comprueba el estado:

```bash

Guarda esto como `Vagrantfile` (ejemplo mínimo):

  config.vm.box = "ubuntu/focal64"
  config.vm.define "web" do |web|
    web.vm.hostname = "web"
    web.vm.network "private_network", ip: "192.168.56.10"
    # web.vm.provision "shell", path: "provision-web.sh"  # descomenta si tienes el script
  end

  config.vm.define "db" do |db|
    db.vm.hostname = "db"
    db.vm.network "private_network", ip: "192.168.56.11"
    # db.vm.provision "shell", path: "provision-db.sh"   # descomenta si tienes el script
  end
end
```

## Direcciones IP (ejemplo)

- web: `192.168.56.10` (servidor web)
- db : `192.168.56.11` (servidor PostgreSQL)

## Datos de ejemplo para la base de datos

- Base de datos: `taller`
- Usuario: `webuser`
- Contraseña: `webpass`
- Puerto: `5432`

## Provisionamiento manual - Máquina `web`

1. Conéctate a la VM web:

```bash
vagrant ssh web
```

2. (Si tienes problemas con el terminal) dentro de la VM:

```bash
export TERM=xterm
```

3. Instala Apache, PHP y la extensión de PostgreSQL:

```bash
sudo apt update -y
sudo apt install -y apache2 php libapache2-mod-php php-pgsql postgresql-client
```

4. Copia los archivos del sitio desde la carpeta compartida `/vagrant/www` a `/var/www/html`:

```bash
sudo mkdir -p /var/www/html
sudo cp -r /vagrant/www/* /var/www/html/
sudo chown -R www-data:www-data /var/www/html
sudo chmod -R 755 /var/www/html
```

5. Reinicia Apache:

```bash
sudo systemctl restart apache2
sudo systemctl status apache2 --no-pager
```

6. Prueba PHP:

```bash
echo "<?php echo 'PHP OK'; ?>" | sudo tee /var/www/html/test.php >/dev/null
# Visita: http://192.168.56.10/test.php
```

## Provisionamiento manual - Máquina `db`

1. Conéctate a la VM db:

```bash
vagrant ssh db
```

2. Ajusta el TERM si hace falta:

```bash
export TERM=xterm
```

3. Instala PostgreSQL:

```bash
sudo apt update -y
sudo apt install -y postgresql postgresql-contrib
```

4. Verifica la versión/clúster:

```bash
sudo pg_lsclusters
```

5. Inicia el clúster si está detenido (reemplaza X por la versión detectada):

```bash
sudo pg_ctlcluster X main start
```

6. Crea el usuario y la base de datos (si no existen):

```bash
sudo -u postgres psql -c "CREATE USER webuser WITH PASSWORD 'webpass';" || true
sudo -u postgres psql -c "CREATE DATABASE taller OWNER webuser;" || true
```

7. Habilita conexiones remotas (ejemplo automático):

```bash
PG_VERSION=$(ls /etc/postgresql | head -n1)
sudo sed -i "s/^#listen_addresses.*/listen_addresses = '*'/'" /etc/postgresql/${PG_VERSION}/main/postgresql.conf
echo "host    all             all             192.168.56.0/24            md5" | sudo tee -a /etc/postgresql/${PG_VERSION}/main/pg_hba.conf
sudo systemctl restart postgresql
```

8. Comprueba que PostgreSQL escucha en `0.0.0.0:5432`:

```bash
sudo ss -lntp | grep 5432 || true
```

9. (Opcional) Crea una tabla de ejemplo y datos:

```bash
sudo -u postgres psql -d taller -c "CREATE TABLE IF NOT EXISTS public.usuarios (id SERIAL PRIMARY KEY, nombre VARCHAR(100));"
sudo -u postgres psql -d taller -c "INSERT INTO public.usuarios (nombre) SELECT 'Ana' WHERE NOT EXISTS (SELECT 1 FROM public.usuarios);"
```

10. Concede permisos a `webuser`:

```bash
sudo -u postgres psql -d taller -c "GRANT CONNECT ON DATABASE taller TO webuser;"
sudo -u postgres psql -d taller -c "GRANT USAGE ON SCHEMA public TO webuser;"
sudo -u postgres psql -d taller -c "GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO webuser;"
```

## Probar conexión Web → DB

En la VM `web` instala el cliente y prueba la conexión:

```bash
sudo apt update -y
sudo apt install -y postgresql-client
psql -h 192.168.56.11 -U webuser -d taller
# contraseña: webpass
```

Si obtienes el prompt `taller=#` la conexión es correcta.

Accede desde el host a:

```
http://192.168.56.10/info.php
```

## Comandos útiles (diagnóstico)

- Ver logs de Apache (web): `sudo tail -n 50 /var/log/apache2/error.log`
- Ver logs de PostgreSQL (db): `sudo tail -n 50 /var/log/postgresql/postgresql-*-main.log`
- Ver puertos escuchando: `sudo ss -lntp`
- Reiniciar Apache: `sudo systemctl restart apache2`
- Reiniciar PostgreSQL: `sudo systemctl restart postgresql`

## Detener / limpiar

- Apagar VMs: `vagrant halt`
- Reiniciar sin reprovisionar: `vagrant reload --no-provision`
- Eliminar VMs: `vagrant destroy -f`

## Notas

- Asegúrate de tener los archivos `index.html` e `info.php` en `/vagrant/www` antes de copiarlos a `/var/www/html`.
- Si ves errores del tipo "Error opening terminal: xterm-kitty", exporta `TERM=xterm`.
- Usa `PG_VERSION=$(ls /etc/postgresql | head -n1)` para detectar la versión de PostgreSQL en la VM.
- No incluyas contraseñas o datos sensibles en repositorios públicos.

## Créditos

- Autor: Phantom Beat
- Año: 2025

---

Fin.
