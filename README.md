# MyCLAP

![MyCLAP Logo](/public/static/myclap/myclap.png)

Site web de VOD du CLAP en Laravel : https://my.le-clap.fr

Cette V2 remise au goût du jour en 2026 du projet original de [Jean-Baptiste Caplan](https://github.com/jnbptstcpln/myclap)
a été développée par [David Marembert](https://github.com/D0gmaDev).

Le site est hébergé par l'[Association Rézoléo](https://github.com/rezoleo).

## Description

Ce site permet à travers différentes sections de :

- Uploder et publier des vidéos
- Classer les vidéos par catégorie
- Construire des playlists
- Permettre aux centraliens de s'authentifier avec leur compte CLA
- Définir la politique d'accès des vidéos et playlists :
  - **Publique** : n'importe qui peut y accéder
  - **Non répertoriée** : seules les personnes disposant du lien peuvent y accéder
  - **Centraliens** : tous les centraliens connectés via CLA peuvent y accéder
  - **Privée** : seuls les membres du CLAP autorisés peuvent y accéder
- Voir les statistiques de visionnage

## Installation

Après avoir récupéré le code depuis le repo GitHub :

```bash
composer install
npm install
npm run build
```

Copier le fichier `.env.example` en `.env` et configurer les variables d'environnement (base de données, Auth CLA, etc.).

Générer la clé d'application :

```bash
php artisan key:generate
```

Lancer les migrations :

```bash
php artisan migrate
```

Créer les liens symboliques pour le stockage :

```bash
php artisan storage:link
```

## Configuration Nginx

Exemple de configuration Nginx (il est très important que les fichiers statiques soient servis directement par Nginx) :

```nginx
server {
    listen 80;
    server_name localhost;

    root /var/www/myclap-v2/public;
    index index.php index.html index.htm;

    # Cache et service direct pour les fichiers statiques
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        try_files $uri =404;
        expires 30d;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # X-Accel-Redirect: location interne pour servir les fichiers depuis storage
    location ^~ /internal-storage/ {
        internal;
        alias /var/www/myclap-v2/storage/app/private/;
    }

    # Gestion des requêtes Laravel
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Gestion des fichiers PHP
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Sécurité : bloquer l'accès aux fichiers cachés
    location ~ /\.(?!well-known).* {
        deny all;
    }

    error_page 500 502 503 504 /50x.html;
    location = /50x.html {
        root /usr/share/nginx/html;
    }
}
```

Vérifier que l'utilisateur du serveur web ait les permissions en écriture sur les dossiers `storage` et `bootstrap/cache`.

## Configuration CLA Auth

Dans le fichier `.env`, configurer les paramètres pour l'authentification CLA :

```
CLA_AUTH_HOST=https://centralelilleassos.fr
CLA_AUTH_IDENTIFIER=myclap
```

## Schéma de la base de données

```mermaid
erDiagram

    clap_user {
        bigint id PK
        string username UK
        string first_name
        string last_name
        string school_email
        int promo
        bool alumni
        timestamp created_on
        timestamp logged_on
        string remember_token
    }

    category {
        bigint id PK
        string slug UK
        string label
        text description
        string created_by FK
        timestamp created_on
    }

    playlist {
        bigint id PK
        string name
        text description
        tinyint type
        string slug UK
        tinyint access
        uint position
        timestamp created_on
        timestamp modified_on
        string modified_by FK
    }

    video {
        bigint id PK
        string name
        string token UK
        date created_on
        text description
        tinyint access
        string thumbnail_identifier
        string file_identifier
        bigint file_size
        int duration
        tinyint upload_status
        string uploaded_by FK
        timestamp uploaded_on
        int views
        int reactions
    }

    video_category {
        string video_token PK,FK
        string category_slug PK,FK
    }

    playlist_video {
        string playlist_slug PK,FK
        string video_token PK,FK
        int position
    }

    video_reaction {
        bigint id PK
        string video_token FK
        string username FK
        timestamp created_on
    }

    video_upload {
        bigint id PK
        string video_token FK
        string file_name
        bigint file_size
        string file_identifier
        string created_by FK
        timestamp created_on
    }

    video_view {
        bigint id PK
        string video_token FK
        string php_sid
        string playback_sid
        string username FK
        bool count_as_view
        int watch_time
        string view_source
        string device_type
        string browser
        string os
        timestamp created_on
        timestamp updated_on
    }

    user_permission {
        bigint id PK
        string username FK
        string identifier
        string created_by FK
        timestamp created_on
    }

    clap_user ||--o{ category : creates

    clap_user ||--o{ playlist : modifies

    clap_user ||--o{ video : uploads

    clap_user ||--o{ video_upload : creates

    clap_user ||--o{ user_permission : owns
    clap_user ||--o{ user_permission : grants

    clap_user o|--o{ video_view : watches

    clap_user ||--o{ video_reaction : reacts

    category ||--o{ video_category : contains
    video ||--o{ video_category : classified_as

    playlist ||--o{ playlist_video : contains
    video ||--o{ playlist_video : appears_in

    video ||--o{ video_upload : has_uploads

    video ||--o{ video_view : has_views

    video ||--o{ video_reaction : has_reactions
```
