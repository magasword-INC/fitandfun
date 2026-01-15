# 🏋️ Fit&Fun - Plateforme de Gestion des Cours de Fitness

Une plateforme web moderne et complète pour la gestion de cours de fitness, d'adhérents, et d'abonnements avec intégration d'IA pour la génération d'avatars personnalisés.

## 📋 Table des matières

- [Aperçu](#aperçu)
- [Fonctionnalités](#fonctionnalités)
- [Architecture](#architecture)
- [Installation](#installation)
- [Configuration](#configuration)
- [Utilisation](#utilisation)
- [Intégration IA](#intégration-ia)
- [Base de Données](#base-de-données)
- [Structure des Fichiers](#structure-des-fichiers)
- [Sécurité](#sécurité)
- [Support](#support)

---

## 🎯 Aperçu

**Fit&Fun** est une plateforme SaaS pour les salles de fitness qui permet de :
- ✅ Gérer les adhérents et leurs profils
- ✅ Organiser et planifier les cours
- ✅ Gérer les abonnements et tarifs
- ✅ Générer des avatars IA personnalisés
- ✅ Administrer les utilisateurs avec permissions granulaires
- ✅ Consulter l'historique d'activité
- ✅ Envoyer des notifications par email

**Live :** [projet.pouximixi.fr](https://projet.pouximixi.fr)

---

## ✨ Fonctionnalités

### 👥 Gestion des Adhérents
- ✅ Inscription et authentification sécurisée
- ✅ Profil personnalisable avec avatar IA
- ✅ Historique des cours suivis
- ✅ Notes et feedbacks des cours
- ✅ Statut d'activité en temps réel

### 📅 Planning et Cours
- ✅ Calendrier interactif des cours
- ✅ Création/édition/suppression des activités
- ✅ Gestion des horaires et capacités
- ✅ Inscription/désincription aux cours
- ✅ Export en iCalendar (.ics)
- ✅ Saisie d'notes après les cours

### 💳 Abonnements
- ✅ Gestion des tarifs et packages
- ✅ Systèmes d'abonnements flexibles
- ✅ Suivi des adhésions
- ✅ Historique des transactions

### 🤖 Génération d'Avatars IA
- ✅ Stable Diffusion intégré
- ✅ Mode CPU optimisé
- ✅ Génération directe depuis le profil
- ✅ Stockage des avatars personnalisés

### 👨‍💼 Panel d'Administration
- ✅ Dashboard avec statistiques
- ✅ Gestion des utilisateurs (création, édition, suppression)
- ✅ Système de rôles : super_admin, bureau, animateur, adhérent
- ✅ Configuration des paramètres globaux
- ✅ Logs d'activité
- ✅ Connexion en tant qu'autre utilisateur (mode support)

### 🔒 Sécurité
- ✅ Authentification par session
- ✅ Protection CSRF
- ✅ Headers de sécurité HTTP
- ✅ Validation des entrées
- ✅ Chiffrage des mots de passe
- ✅ Pagination des données sensibles

---

## 🏗️ Architecture

### Stack Technologique

```
Frontend: HTML5, CSS3, JavaScript (vanilla)
Backend:  PHP 7.4+
BDD:      MySQL/MariaDB
Cache:    Varnish (optionnel)
Email:    SMTP (Rips Mail)
IA:       Stable Diffusion (Python)
Serveur:  Nginx, PHP-FPM
```

### Architecture Générale

```
┌─────────────────────────────────────────┐
│         NAVIGATEUR UTILISATEUR          │
└──────────────┬──────────────────────────┘
               │ HTTP/HTTPS
┌──────────────▼──────────────────────────┐
│      NGINX REVERSE PROXY / CACHE        │
│      (Varnish optionnel)                │
└──────────────┬──────────────────────────┘
               │
┌──────────────▼──────────────────────────┐
│      PHP-FPM (Application Web)          │
│  ├─ Routage des pages                   │
│  ├─ Sessions utilisateur                │
│  ├─ Validation des données              │
│  └─ APIs REST                           │
└──────────────┬──────────────────────────┘
       ┌───────┴────────┬──────────────┐
       │                │              │
   ┌───▼───┐       ┌────▼───┐    ┌────▼────┐
   │ MySQL │       │ SMTP   │    │ Stable   │
   │ BDD   │       │ Emails │    │Diffusion │
   └───────┘       └────────┘    └──────────┘
```

---

## 📦 Installation

### Prérequis

- **PHP** ≥ 7.4 (recommandé 8.0+)
- **MySQL/MariaDB** ≥ 5.7
- **Nginx** ou Apache
- **Composer** (optionnel, pour certains packages)
- **Python** 3.8+ (pour Stable Diffusion - optionnel)

### Étapes d'Installation

#### 1. Cloner le Répertoire

```bash
git clone https://github.com/votre-username/pouximixi-projet.git
cd pouximixi-projet
```

#### 2. Configurer les Permissions

```bash
chmod 755 htdocs/projet.pouximixi.fr
chmod 755 htdocs/projet.pouximixi.fr/uploads
chmod 755 logs
chmod 755 tmp
```

#### 3. Configuration MySQL

```bash
# Se connecter à MySQL
mysql -u root -p

# Créer la base de données
CREATE DATABASE fitandfun CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'admin'@'localhost' IDENTIFIED BY 'fitandfun21';
GRANT ALL PRIVILEGES ON fitandfun.* TO 'admin'@'localhost';
FLUSH PRIVILEGES;

# Importer le schéma initial (si disponible)
mysql -u admin -p fitandfun < schema.sql
```

#### 4. Configurer le Fichier PHP

Éditer [config.php](config.php) :

```php
<?php
// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'fitandfun'); 
define('DB_USER', 'admin');     
define('DB_PASS', 'fitandfun21'); 

// Configuration SMTP pour les emails
define('SMTP_HOST', 'mail71.lwspanel.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'noreply@rips.fr'); 
define('SMTP_PASS', 'YOUR_PASSWORD');
```

#### 5. Configuration Nginx

Créer un virtual host dans `/etc/nginx/sites-available/fit-and-fun` :

```nginx
server {
    listen 80;
    server_name projet.pouximixi.fr;
    
    root /home/pouximixi-projet/htdocs/projet.pouximixi.fr;
    index index.php;
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\. {
        deny all;
    }
}
```

Activer et tester :

```bash
sudo ln -s /etc/nginx/sites-available/fit-and-fun /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

#### 6. Tester l'Installation

```bash
# Vérifier les droits de la BDD
php htdocs/projet.pouximixi.fr/check_db.php

# Vérifier le schéma des tables
php htdocs/projet.pouximixi.fr/check_seances_schema.php
```

---

## ⚙️ Configuration

### Variables d'Environnement Importantes

| Variable | Valeur | Description |
|----------|--------|-------------|
| `DB_HOST` | localhost | Hôte MySQL |
| `DB_NAME` | fitandfun | Nom de la base |
| `DB_USER` | admin | Utilisateur MySQL |
| `DB_PASS` | fitandfun21 | Mot de passe MySQL |
| `SMTP_HOST` | mail71.lwspanel.com | Serveur SMTP |
| `SMTP_PORT` | 587 | Port SMTP |
| `SMTP_USER` | noreply@rips.fr | Email d'envoi |
| `SMTP_PASS` | *** | Mot de passe SMTP |

### Configuration des Rôles

Le système propose 4 rôles avec permissions différentes :

| Rôle | Accès |
|------|-------|
| **super_admin** | Accès complet, gestion de tous les utilisateurs |
| **bureau** | Gestion des adhérents et abonnements |
| **animateur** | Gestion des cours et saisie de notes |
| **adhérent** | Inscription aux cours, accès au profil |

---

## 🚀 Utilisation

### Accès au Site

```
🌐 URL: https://projet.pouximixi.fr
📱 Responsive: Oui (mobiles, tablettes, desktops)
```

### Première Connexion - Admin

1. Se connecter avec les identifiants par défaut (à créer)
2. Accéder au **Dashboard** (Tableau de Bord)
3. Créer les premières activités et abonnements
4. Créer les adhérents ou les laisser s'inscrire

### Pages Principales

| Page | URL | Accès |
|------|-----|-------|
| Accueil | `/` | Public |
| Connexion | `/?page=login` | Public |
| Inscription | `/?page=register` | Public |
| Planning | `/?page=planning` | Adhérent+ |
| Mon Profil | `/?page=mon_profil` | Adhérent+ |
| Abonnements | `/?page=abonnements` | Public |
| Dashboard Admin | `/?page=admin_dashboard` | Admin+ |
| Configuration | `/?page=admin_settings` | Admin+ |
| Contact | `/?page=contact` | Public |

### Exemples d'Utilisation

#### Créer un Cours

```php
// Via Admin Dashboard
1. Aller sur "Configuration" (admin_settings)
2. Ajouter une nouvelle activité
3. Définir : nom, horaires, capacité, animateur
4. Sauvegarder
```

#### Inscrire un Adhérent

```php
// Via Admin
1. Dashboard → Gérer les adhérents
2. Cliquer "Nouvel Adhérent"
3. Remplir le formulaire
4. Activer le compte
```

#### Générer un Avatar IA

```php
// Via Profil Adhérent
1. Mon Profil
2. Cliquer "Générer un Avatar IA"
3. Décrire votre avatar (prompt)
4. Patienter 1-3 minutes
5. Avatar sauvegardé automatiquement
```

---

## 🤖 Intégration IA - Stable Diffusion

### Installation CPU (Recommandée pour ce serveur)

#### Étape 1 : Lancer le Script d'Installation

```bash
/home/pouximixi-projet/ai-tools/launch_cpu_mode.sh
```

**Ce que ça fait :**
- ✅ Télécharge Stable Diffusion (~4-10 Go)
- ✅ Installe les dépendances Python
- ✅ Lance le serveur IA localement
- ⏱️ Durée : 10-30 minutes

#### Étape 2 : Vérifier le Démarrage

```bash
# Le script affichera l'URL :
🚀 Stable Diffusion tournant sur http://127.0.0.1:7860
```

#### Étape 3 : Tester via le Site

1. Aller sur "Mon Profil"
2. Cliquer "Générer un Avatar IA"
3. Entrer une description : *"Une jeune femme sportive, blonde, souriante"*
4. Cliquer "Générer"
5. Patienter 1-3 minutes

#### Étape 4 : Mode Persistant (optionnel)

Pour que l'IA continue après fermeture du terminal :

```bash
# Utiliser "screen"
screen -S stable-diffusion
/home/pouximixi-projet/ai-tools/launch_cpu_mode.sh
# Appuyer Ctrl+A puis D pour détacher

# Reprendre la session
screen -r stable-diffusion
```

### Fichiers IA

```
ai-tools/
├── launch_cpu_mode.sh           # Script de lancement
└── stable-diffusion-webui/      # Installation Stable Diffusion
    ├── webui.py                 # Point d'entrée
    ├── requirements.txt         # Dépendances Python
    └── models/                  # Modèles IA (téléchargés)
```

### API IA Utilisée

```php
// Voir : htdocs/projet.pouximixi.fr/includes/ai_avatar_gen.php

// Endpoint local
POST http://127.0.0.1:7860/api/predict

// Paramètres
{
    "prompt": "Description de l'avatar",
    "steps": 20,
    "cfg_scale": 7.5,
    "height": 512,
    "width": 512
}
```

---

## 🗄️ Base de Données

### Schéma Principal

#### Table : `users_app`

```sql
CREATE TABLE users_app (
    id_user INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    prenom VARCHAR(100),
    nom VARCHAR(100),
    telephone VARCHAR(20),
    role ENUM('super_admin', 'bureau', 'animateur', 'adhérent') DEFAULT 'adhérent',
    avatar_url VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    date_inscription TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    derniere_connexion DATETIME,
    INDEX (email, role)
);
```

#### Table : `activites`

```sql
CREATE TABLE activites (
    id_activite INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    capacite INT DEFAULT 20,
    animateur_id INT,
    is_active TINYINT(1) DEFAULT 1,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (animateur_id) REFERENCES users_app(id_user)
);
```

#### Table : `seances`

```sql
CREATE TABLE seances (
    id_seance INT PRIMARY KEY AUTO_INCREMENT,
    activite_id INT NOT NULL,
    date_seance DATE NOT NULL,
    heure_debut TIME NOT NULL,
    heure_fin TIME NOT NULL,
    lieu VARCHAR(100),
    FOREIGN KEY (activite_id) REFERENCES activites(id_activite),
    UNIQUE KEY (activite_id, date_seance, heure_debut)
);
```

#### Table : `inscriptions`

```sql
CREATE TABLE inscriptions (
    id_inscription INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    seance_id INT NOT NULL,
    date_inscription TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    statut ENUM('inscrit', 'absent', 'present') DEFAULT 'inscrit',
    FOREIGN KEY (user_id) REFERENCES users_app(id_user),
    FOREIGN KEY (seance_id) REFERENCES seances(id_seance),
    UNIQUE KEY (user_id, seance_id)
);
```

### Scripts de Maintenance

```bash
# Vérifier la base de données
php check_db.php

# Vérifier le schéma des séances
php check_seances_schema.php

# Mettre à jour les activités (cron)
php update_db_activity.php

# Mettre à jour le planning
php update_db_planning.php

# Mettre à jour les utilisateurs
php update_db_users.php

# Mettre à jour les profils
php update_db_profil.php
```

---

## 📁 Structure des Fichiers

```
pouximixi-projet/
├── README.md                           # Cette documentation
├── config.php                          # Configuration principale
├── check_db.php                        # Diagnostic BDD
├── check_seances_schema.php           # Vérification schéma
├── update_db_*.php                    # Scripts de maintenance
│
├── ai-tools/                           # Outils IA
│   ├── launch_cpu_mode.sh             # Lancement Stable Diffusion
│   └── stable-diffusion-webui/        # Installation SD
│
├── htdocs/
│   └── projet.pouximixi.fr/           # Application web
│       ├── index.php                   # Point d'entrée principal
│       ├── LOGO.png                    # Logo du site
│       ├── assets/                     # CSS, JS, images
│       │   ├── css/
│       │   ├── js/
│       │   └── images/
│       ├── includes/                   # Fichiers partagés
│       │   ├── db.php                  # Connexion MySQL
│       │   ├── functions.php           # Fonctions utilitaires
│       │   ├── mail_helper.php         # Envoi d'emails
│       │   ├── ai_avatar_gen.php       # Génération IA
│       │   ├── config_loader.php       # Chargement config
│       │   ├── header.php              # En-tête HTML
│       │   ├── footer.php              # Pied de page
│       │   └── prices.php              # Gestion tarifs
│       ├── pages/                      # Pages dynamiques
│       │   ├── accueil.php             # Accueil public
│       │   ├── login.php               # Authentification
│       │   ├── register.php            # Inscription
│       │   ├── mon_profil.php          # Profil utilisateur
│       │   ├── planning.php            # Calendrier cours
│       │   ├── abonnements.php         # Tarifs/Packages
│       │   ├── admin_dashboard.php     # Tableau de bord admin
│       │   ├── admin_settings.php      # Configuration admin
│       │   ├── contact.php             # Formulaire contact
│       │   ├── noter_seance.php        # Feedback après cours
│       │   ├── handle_*.php            # Traitements POST
│       │   ├── api_*.php               # Endpoints API
│       │   └── ...
│       └── uploads/                    # Avatars et fichiers
│
├── logs/                               # Journaux
│   ├── nginx/                          # Logs Nginx
│   ├── php/                            # Logs PHP-FPM
│   └── varnish-cache/                  # Logs Cache Varnish
│
├── backups/                            # Sauvegardes
│   └── databases/
│       └── fitandfun/
│
└── tmp/                                # Fichiers temporaires
```

---

## 🔒 Sécurité

### Mesures de Sécurité Implémentées

#### 1. **Authentification & Sessions**
```php
// Sessions sécurisées
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /?page=login");
    exit();
}
```

#### 2. **Protection CSRF**
```php
// Tokens CSRF sur tous les formulaires POST
<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
```

#### 3. **Headers de Sécurité HTTP**
```php
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
```

#### 4. **Validation des Entrées**
```php
// Utilisation de PDO avec paramètres liés (prévention SQL injection)
$stmt = $pdo->prepare("SELECT * FROM users_app WHERE email = ?");
$stmt->execute([$email]);
```

#### 5. **Échappement des Sorties**
```php
// Protection XSS
echo htmlspecialchars($user['prenom'], ENT_QUOTES, 'UTF-8');
```

#### 6. **Hachage des Mots de Passe**
```php
// Bcrypt avec salt
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
if (password_verify($password, $hash)) { /* OK */ }
```

#### 7. **Contrôle d'Accès (RBAC)**
```php
// Vérification des rôles
if (!in_array($_SESSION['user_role'], ['super_admin', 'bureau'])) {
    http_response_code(403);
    exit("Accès refusé");
}
```

#### 8. **Cache Control**
```php
// Éviter le cache des données sensibles
header("Cache-Control: no-store, no-cache, must-revalidate");
```

### Bonnes Pratiques à Suivre

- ✅ Mettre à jour PHP/MySQL régulièrement
- ✅ Changer les identifiants par défaut
- ✅ Utiliser HTTPS en production
- ✅ Faire des sauvegardes quotidiennes
- ✅ Monitorer les logs d'erreur
- ✅ Limiter les tentatives de connexion
- ✅ Activer les firewalls (Web Application Firewall)

---

## 📊 Monitoring & Logs

### Accéder aux Logs

```bash
# Logs Nginx
tail -f logs/nginx/access.log-2026-01-15
tail -f logs/nginx/error.log-2026-01-15

# Logs PHP
tail -f logs/php/error.log-2026-01-15

# Logs Varnish Cache
tail -f logs/varnish-cache/purge.log-2026-01-15
```

### Tableau de Bord Monitoring (optionnel)

Un script de monitoring peut être mis en place pour surveiller :
- 📊 Nombre d'utilisateurs actifs
- 📅 Prochains cours
- 💾 Espace disque
- 🔴 Erreurs critiques

---

## 🚢 Déploiement

### Mise en Place Continu (CI/CD)

Exemple avec GitHub Actions :

```yaml
# .github/workflows/deploy.yml
name: Deploy to Production

on:
  push:
    branches: [ main ]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Deploy to Server
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.HOST }}
          username: ${{ secrets.USER }}
          key: ${{ secrets.SSH_KEY }}
          script: |
            cd /home/pouximixi-projet
            git pull origin main
            # Redémarrer les services si nécessaire
```

### Checklist de Déploiement

- [ ] Backup de la BDD avant déploiement
- [ ] Test de la connexion à la BDD
- [ ] Vérification des permissions de fichiers
- [ ] Test des uploads (images/avatars)
- [ ] Test de la génération IA si activée
- [ ] Vérification des emails
- [ ] Test de tous les formulaires
- [ ] Vérification des logs d'erreur

---

## 🆘 Support & Dépannage

### Problèmes Courants

#### **"Erreur de Connexion à la BDD"**

```bash
# Vérifier les identifiants
php check_db.php

# Vérifier la connexion MySQL
mysql -u admin -p -h localhost
```

#### **"L'IA ne démarre pas"**

```bash
# Vérifier que le script est exécutable
chmod +x ai-tools/launch_cpu_mode.sh

# Lancer manuellement
/home/pouximixi-projet/ai-tools/launch_cpu_mode.sh

# Vérifier les logs Python
tail -f logs/ai_diffusion.log
```

#### **"Erreur 403 Forbidden"**

```bash
# Vérifier les permissions
chmod 755 htdocs/projet.pouximixi.fr
chmod 755 htdocs/projet.pouximixi.fr/uploads

# Vérifier l'utilisateur Nginx
ps aux | grep nginx
# Doit s'exécuter en tant que "www-data" ou "nginx"
```

#### **"Impossible d'envoyer des emails"**

```php
// Vérifier config.php
echo SMTP_HOST;  // mail71.lwspanel.com
echo SMTP_PORT;  // 587
echo SMTP_USER;  // noreply@rips.fr

// Tester la connexion SMTP
php -r "require 'includes/mail_helper.php'; testSmtp();"
```

### Commandes Utiles

```bash
# Redémarrer les services
sudo systemctl restart nginx
sudo systemctl restart php7.4-fpm
sudo systemctl restart mysql

# Nettoyer les caches temporaires
rm -rf tmp/*
rm -rf logs/nginx/*.gz

# Vérifier l'espace disque
df -h

# Vérifier les processus
ps aux | grep -E 'nginx|php|mysql|python'
```

---

## 📝 Licence

Ce projet est développé pour **Fit&Fun - Pouximixi**.
Tous droits réservés © 2025

---

## 👥 Équipe

- **Développement** : Équipe Pouximixi
- **Hébergement** : Cloud Server
- **Maintenance** : Support Pouximixi

---

## 📞 Contact & Support

- 📧 Email : contact@pouximixi.fr
- 🌐 Site : https://pouximixi.fr
- 📱 Téléphone : +33 (disponible sur le site)
- 💬 Support : Page Contact sur le site

---

## 🗺️ Roadmap & Futures Fonctionnalités

- [ ] Application mobile iOS/Android
- [ ] Intégration stripe pour paiements en ligne
- [ ] Système de recommandations basé sur l'IA
- [ ] Live streaming des cours
- [ ] Intégration des smartwatches
- [ ] Système de parrainage et rewards
- [ ] Gamification (badges, leaderboards)
- [ ] Intégration des réseaux sociaux

---

## 📚 Documentation Supplémentaire

Pour plus d'informations sur des sujets spécifiques :

- [Configuration Nginx](docs/nginx-setup.md) - *(À créer)*
- [Guide API REST](docs/api-guide.md) - *(À créer)*
- [Variables d'Environnement](docs/environment-variables.md) - *(À créer)*
- [Architecture PHP](docs/php-architecture.md) - *(À créer)*

---

## 🎉 Merci d'utiliser Fit&Fun !

Pour toute question, suggestion ou signalement de bug, consultez la section [Support & Dépannage](#-support--dépannage).

**Bon entraînement ! 💪**

---

*Dernière mise à jour : 15 janvier 2026*
*Version : 1.0.0*
