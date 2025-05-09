# ShrakVPN Admin Panel - Deployment Guide

Dieses Dokument enthält Anweisungen zur Bereitstellung des ShrakVPN Admin Panels auf einem Plesk-Server mit den Subdomains `admin.1upone.com` und `api.1upone.com`.

## Voraussetzungen

- Plesk-Server mit PHP 8.1+ und MySQL/MariaDB
- Domain 1upone.com mit aktivierten Subdomains admin.1upone.com und api.1upone.com
- SSL-Zertifikate für die Subdomains (Let's Encrypt kann in Plesk verwendet werden)
- FTP- oder SSH-Zugang zum Server

## Schritte zur Bereitstellung

### 1. Erstellen der Subdomains in Plesk

1. Melden Sie sich bei Plesk an
2. Navigieren Sie zu Domains > 1upone.com
3. Gehen Sie zum Abschnitt "Hosting & DNS" und dann "Subdomains"
4. Erstellen Sie zwei Subdomains:
   - `admin.1upone.com`
   - `api.1upone.com`
5. Stellen Sie für beide Subdomains dieselbe Dokumentwurzel ein: `/var/www/vhosts/1upone.com/admin/public`

### 2. SSL-Zertifikate einrichten

1. Navigieren Sie in Plesk zu den SSL/TLS-Zertifikaten für jede Subdomain
2. Erstellen Sie neue Zertifikate mit Let's Encrypt oder laden Sie vorhandene Zertifikate hoch

### 3. Hochladen der Anwendungsdateien

1. Verbinden Sie sich über SFTP oder SSH mit Ihrem Server
2. Erstellen Sie das Verzeichnis für das Admin Panel:
   ```
   mkdir -p /var/www/vhosts/1upone.com/admin
   ```
3. Laden Sie alle Projektdateien in dieses Verzeichnis hoch (ohne den `node_modules`-Ordner)
4. Kopieren Sie die Datei `.env.production` zu `.env` auf dem Server

### 4. Zugriffsrechte einstellen

```bash
cd /var/www/vhosts/1upone.com/admin
chmod -R 755 .
chmod -R 777 storage bootstrap/cache
chown -R <plesk_user>:<plesk_group> .
```

Ersetzen Sie `<plesk_user>` und `<plesk_group>` durch den tatsächlichen Plesk-Benutzer und -Gruppe (in der Regel etwas wie `example_user`).

### 5. Abhängigkeiten installieren und Anwendung initialisieren

Verbinden Sie sich über SSH mit dem Server und führen Sie folgende Befehle aus:

```bash
cd /var/www/vhosts/1upone.com/admin

# Composer-Abhängigkeiten installieren
composer install --optimize-autoloader --no-dev

# Anwendungsschlüssel generieren (falls nicht bereits in .env)
php artisan key:generate

# Cache leeren und neu erstellen
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Datenbank migrieren
php artisan migrate
```

### 6. Apache-Konfiguration anpassen

1. Kopieren Sie den Inhalt der Dateien `admin-vhost.conf` und `api-vhost.conf` in die entsprechenden Apache-Konfigurationsdateien (normalerweise unter `/etc/apache2/sites-available/` oder im Plesk-Konfigurationsverzeichnis)
2. Passen Sie die Pfade für SSL-Zertifikate entsprechend Ihrer tatsächlichen Plesk-Konfiguration an
3. Aktivieren Sie die Konfigurationen und starten Sie Apache neu:
   ```bash
   a2ensite admin.1upone.com.conf
   a2ensite api.1upone.com.conf
   systemctl restart apache2
   ```

### 7. Konfiguration der Server-API-Kommunikation

1. Stellen Sie sicher, dass der `SERVER_API_KEY` in der `.env`-Datei gesetzt ist und mit dem Wert übereinstimmt, der in den VPN-Servern verwendet wird
2. Aktualisieren Sie die Server-Einträge in der Datenbank, um den korrekten API-Endpunkt zu verwenden: `https://api.1upone.com`

### 8. Testen der Installation

1. Öffnen Sie in einem Browser `https://admin.1upone.com` und prüfen Sie, ob das Admin-Panel lädt
2. Testen Sie den API-Endpunkt mit einem Tool wie Postman: `https://api.1upone.com/api/test`

## Troubleshooting

### Fehlerbehebung bei Berechtigungsproblemen

```bash
cd /var/www/vhosts/1upone.com/admin
chmod -R 777 storage bootstrap/cache
```

### Überprüfen der Logs

```bash
tail -f /var/www/vhosts/1upone.com/admin/storage/logs/laravel.log
```

### Apache-Logs überprüfen

```bash
tail -f /var/log/apache2/admin.1upone.com-error.log
tail -f /var/log/apache2/api.1upone.com-error.log
```

## Automatische Updates

Einrichtung eines Cron-Jobs im Plesk für die regelmäßige Ausführung der geplanten Aufgaben:

```
* * * * * cd /var/www/vhosts/1upone.com/admin && php artisan schedule:run >> /dev/null 2>&1
```
