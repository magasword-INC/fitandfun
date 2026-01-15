# 🏋️ Fit&Fun - Plateforme de Gestion des Cours de Fitness

Une plateforme web moderne et complète pour la gestion de cours de fitness, d'adhérents, et d'abonnements avec intégration d'IA pour la génération d'avatars personnalisés.

## 📋 Table des matières

- [Aperçu](#aperçu)
- [Fonctionnalités](#fonctionnalités)
- [Architecture](#architecture)
  - [Stack Technologique](#stack-technologique)
  - [Architecture Réseau Complète](#-architecture-réseau-complète)
  - [Configuration Réseau Détaillée](#-configuration-réseau-détaillée)
  - [Flux de Requête Complet](#-flux-de-requête-complet)
- [Installation](#installation)
- [Configuration](#configuration)
- [Utilisation](#utilisation)
- [Intégration IA](#intégration-ia)
- [Base de Données](#base-de-données)
- [Structure des Fichiers](#structure-des-fichiers)
- [Sécurité](#sécurité)
- [Monitoring & Logs](#-monitoring--logs)
- [Déploiement](#-déploiement)
- [Support & Dépannage](#-support--dépannage)
- [Procédures d'Administration](#-procédures-dadministration--maintenance)
- [Infos de Connexion Rapide](#-infos-de-connexion-rapide)
- [Contact & Support](#-contact--support)

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
Frontend:       HTML5, CSS3, JavaScript (vanilla)
Backend:        PHP 7.4+
BDD:            MySQL/MariaDB
Cache:          Varnish (optionnel)
Email:          SMTP (Rips Mail)
IA:             Stable Diffusion (Python)
Serveur Web:    Nginx, PHP-FPM
Reverse Proxy:  Caddy (Debian)
Orchestration:  CloudPanel (Ubuntu)
VPN:            WireGuard
DNS/CDN:        Cloudflare
OS:             Ubuntu 24.04 LTS, Debian
```

### 🌍 Architecture Réseau Complète

#### **Diagramme Global**

```
┌─────────────────────────────────────────────────────────────────┐
│                      INTERNET PUBLIC                            │
│              IP: 149.232.200.168 (Box Free)                     │
└────────────────────────┬────────────────────────────────────────┘
                         │ HTTPS (Port 443)
┌────────────────────────▼────────────────────────────────────────┐
│                    CLOUDFLARE (DNS/CDN)                         │
│  Domain: projet.pouximixi.fr                                    │
│  ├─ SSL/TLS Encryption                                          │
│  ├─ DDoS Protection                                             │
│  ├─ Caching                                                     │
│  └─ Load Balancing                                              │
└────────────────────────┬────────────────────────────────────────┘
                         │ Reverse Proxy
┌────────────────────────▼────────────────────────────────────────┐
│          REVERSE PROXY CADDY (Debian)                           │
│  ├─ Auto HTTPS (Let's Encrypt)                                  │
│  ├─ Compression (Gzip, Brotli)                                  │
│  ├─ Load Balancing                                              │
│  ├─ WAF Rules                                                   │
│  └─ Access Logs                                                 │
└────────────────────────┬────────────────────────────────────────┘
                         │ LAN 192.168.1.0/24
┌────────────────────────▼────────────────────────────────────────┐
│   VM CLOUDPANEL (Ubuntu 24.04 LTS - 192.168.1.105)             │
│                                                                  │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │            CLOUDPANEL DASHBOARD                          │  │
│  │  ├─ Virtual Hosts Management                             │  │
│  │  ├─ SSL Certificates (Auto-renew)                        │  │
│  │  ├─ Database Management                                  │  │
│  │  ├─ File Manager                                         │  │
│  │  ├─ Backup & Restore                                     │  │
│  │  └─ System Monitoring                                    │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                  │
│  ┌────────────────────┐  ┌───────────────┐  ┌──────────────┐   │
│  │  NGINX             │  │  PHP-FPM      │  │  MySQL       │   │
│  │  ├─ SSL/TLS        │  │  ├─ Sessions  │  │  ├─ fitandfun│   │
│  │  ├─ Compression    │  │  ├─ Routing   │  │  └─ Users    │   │
│  │  ├─ Varnish Cache  │  │  ├─ Security  │  │              │   │
│  │  └─ Logging        │  │  └─ APIs      │  │  Port: 3306  │   │
│  └────────────────────┘  └───────────────┘  └──────────────┘   │
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │          APPLICATION (Fit&Fun)                          │   │
│  │  ├─ htdocs/projet.pouximixi.fr                          │   │
│  │  ├─ uploads/ (Avatars IA)                               │   │
│  │  ├─ logs/ (Nginx, PHP, Varnish)                         │   │
│  │  └─ ai-tools/ (Stable Diffusion)                        │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │       SERVICES & BACKGROUND                             │   │
│  │  ├─ Python (Stable Diffusion sur 127.0.0.1:7860)        │   │
│  │  ├─ Cron Jobs (email, updates)                          │   │
│  │  └─ System Monitoring (netdata, logs)                   │   │
│  └─────────────────────────────────────────────────────────┘   │
└────────────────────────┬────────────────────────────────────────┘
                         │ SSH
                         │ WireGuard VPN
┌────────────────────────▼────────────────────────────────────────┐
│     ADMINISTRATEUR (MobaXterm + WireGuard)                       │
│                                                                  │
│  ├─ WireGuard VPN Client                                        │
│  │  └─ IP VPN: 10.0.0.x                                        │
│  │                                                              │
│  ├─ MobaXterm SSH Sessions                                      │
│  │  ├─ Caddy Server (Debian Reverse Proxy)                      │
│  │  ├─ CloudPanel (Ubuntu 192.168.1.105)                        │
│  │  └─ Terminal Tabs (logs, monitoring)                         │
│  │                                                              │
│  └─ File Transfer (SFTP via MobaXterm)                          │
└────────────────────────────────────────────────────────────────┘
```

#### **Architecture Détaillée par Couches**

```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🌐 COUCHE 1 : INTERNET & CDN (Cloudflare)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  Domaine:           projet.pouximixi.fr
  Provider DNS:      Cloudflare
  IP Publique:       149.232.200.168 (Free Box)
  SSL/TLS:           Cloudflare Full
  Protection:        DDoS, Bot Management
  Caching:           Cloudflare Cache
  
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🔀 COUCHE 2 : REVERSE PROXY (Caddy - Debian)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  OS:                Debian GNU/Linux
  Reverse Proxy:     Caddy Server
  HTTPS:             Let's Encrypt (Auto-renewal)
  Compression:       Gzip, Brotli
  Upstream:          http://192.168.1.105:80
  Logs:              /var/log/caddy/
  Config:            /etc/caddy/Caddyfile
  
  Fonctionnalités Caddy:
    ✅ Reverse Proxy (répartition de charge)
    ✅ HTTPS automatique
    ✅ Compression des réponses
    ✅ Rate limiting
    ✅ Request logging
    ✅ Health checks
    ✅ Plugins disponibles

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🖥️ COUCHE 3 : APPLICATION (CloudPanel - Ubuntu 192.168.1.105)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  OS:                Ubuntu 24.04 LTS
  Panel:             CloudPanel 2.x
  Web Server:        Nginx
  App Server:        PHP-FPM 8.0+
  Database:          MySQL 8.0
  Cache:             Varnish (optionnel)
  Monitoring:        NetData, Logs
  
  Ressources:
    CPU:             10 cores
    RAM:             8 GB
    Stockage:        700 GB (SSD)
    
  Services Internes:
    • Nginx (Port 80, 443 → CloudPanel)
    • PHP-FPM (Port 9000)
    • MySQL (Port 3306, local only)
    • Redis (optionnel, Port 6379)
    • Varnish (Port 6081 → 80)
    • Python/Stable Diffusion (Port 7860)
    
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📡 COUCHE 4 : ACCÈS ADMINISTRATEUR (VPN WireGuard + SSH)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  VPN:               WireGuard
  Port VPN:          51820 (UDP)
  Client:            MobaXterm (Windows/Linux/Mac)
  SSH Port:          22 (via VPN)
  
  Authentification:
    ✅ WireGuard Keys (cryptographie asymétrique)
    ✅ SSH Keys (Ed25519)
    ✅ Session Management (MobaXterm)
```

---

## 🔐 Configuration Réseau Détaillée

### **1. Cloudflare - Gestion DNS & CDN**

#### Configuration DNS

```
Type     Name                         Content
─────────────────────────────────────────────────
A        projet.pouximixi.fr          149.232.200.168 (Free Box)
CNAME    www                          projet.pouximixi.fr
MX       @                            mail71.lwspanel.com (Priority 10)
TXT      @                            Verification records
```

#### Configuration SSL/TLS

```
Mode SSL/TLS:        Full (Strict recommended)
Always Use HTTPS:    ✅ Enabled
HSTS:               ✅ Enabled (max-age: 12 months)
Minimum TLS:        1.2
Certificate:        Auto-managed by Cloudflare
Edge Certificates:  ✅ Universal SSL
```

#### Règles Cloudflare

```
Firewall Rules:
  1. Bloquer trafic non-EU (optionnel)
  2. Rate limiting: 50 req/min par IP
  3. Bloquer robots malveillants
  4. Whitelist IPs internes (admin)

Page Rules:
  • /admin/* → Cache Everything + Security High
  • /api/* → No Cache
  • /uploads/* → Cache 1 month
```

---

### **2. Reverse Proxy Caddy (Debian)**

#### Configuration Caddyfile

```caddy
# /etc/caddy/Caddyfile
projet.pouximixi.fr {
    # Log all requests
    log {
        output file /var/log/caddy/access.log
        format json
    }
    
    # Upstream to CloudPanel VM
    reverse_proxy localhost:192.168.1.105 {
        header_uri -Authorization
        health_uri /
        health_interval 10s
        health_timeout 5s
    }
    
    # Compression
    encode gzip
    encode brotli
    
    # Security headers
    header Strict-Transport-Security "max-age=31536000; includeSubDomains"
    header X-Content-Type-Options "nosniff"
    header X-Frame-Options "SAMEORIGIN"
    header X-XSS-Protection "1; mode=block"
    
    # Rate limiting
    rate_limit {
        zones {
            limit_by_ip {
                key {remote_ip}
                window 1m
                limit 100
            }
        }
    }
    
    # Redirect HTTP to HTTPS
    @http {
        protocol http
    }
    handle @http {
        redir https://{host}{uri} permanent
    }
}
```

#### Commandes Caddy

```bash
# Vérifier la config
sudo caddy validate --config /etc/caddy/Caddyfile

# Recharger (sans downtime)
sudo caddy reload --config /etc/caddy/Caddyfile

# Redémarrer
sudo systemctl restart caddy

# Logs
sudo journalctl -u caddy -f
sudo tail -f /var/log/caddy/access.log
```

---

### **3. CloudPanel - Gestion VM (Ubuntu 192.168.1.105)**

#### Accès CloudPanel Dashboard

```
URL:              https://192.168.1.105:8443/
Authentification: SSH Key + Password
Port:             8443 (HTTPS)
```

#### Structure des Vhosts CloudPanel

```
/root/
├── .cloudpanel/                    # Config CloudPanel
│   ├── config.json
│   ├── vhosts/
│   │   └── projet.pouximixi.fr.json
│   └── certs/
│
├── projects/
│   └── projet-pouximixi/           # VirtualHost
│       ├── htdocs/                 # Web Root
│       │   └── projet.pouximixi.fr/
│       ├── logs/
│       ├── backups/
│       └── tmp/
```

#### Gestion des Services CloudPanel

```bash
# Redémarrer Nginx
sudo systemctl restart nginx

# Redémarrer PHP-FPM
sudo systemctl restart php8.1-fpm

# Redémarrer MySQL
sudo systemctl restart mysql

# Vérifier tous les services
sudo systemctl status nginx php8.1-fpm mysql
```

---

### **4. VPN WireGuard - Accès Administrateur Sécurisé**

#### Configuration WireGuard Server

```ini
# /etc/wireguard/wg0.conf
[Interface]
Address = 10.0.0.1/24
ListenPort = 51820
PrivateKey = [SERVER_PRIVATE_KEY]
PostUp = iptables -A FORWARD -i %i -j ACCEPT; iptables -A FORWARD -o %i -j ACCEPT; iptables -t nat -A POSTROUTING -o eth0 -j MASQUERADE
PostDown = iptables -D FORWARD -i %i -j ACCEPT; iptables -D FORWARD -o %i -j ACCEPT; iptables -t nat -D POSTROUTING -o eth0 -j MASQUERADE

# Peer 1 : Admin MobaXterm
[Peer]
PublicKey = [ADMIN_PUBLIC_KEY]
AllowedIPs = 10.0.0.2/32
```

#### Configuration WireGuard Client (MobaXterm)

```ini
# wg0-client.conf (à importer dans WireGuard GUI)
[Interface]
PrivateKey = [CLIENT_PRIVATE_KEY]
Address = 10.0.0.2/24
DNS = 8.8.8.8, 8.8.4.4

[Peer]
PublicKey = [SERVER_PUBLIC_KEY]
Endpoint = 149.232.200.168:51820
AllowedIPs = 10.0.0.0/24, 192.168.1.0/24
PersistentKeepalive = 25
```

#### Gestion WireGuard Server

```bash
# Démarrer le tunnel
sudo wg-quick up wg0

# Vérifier le statut
sudo wg show

# Voir les pairs connectés
sudo wg show wg0 peers

# Arrêter
sudo wg-quick down wg0

# Logs
sudo journalctl -u wg-quick@wg0 -f
```

---

### **5. SSH avec MobaXterm - Accès Terminal**

#### Configuration SSH Session MobaXterm

```
Session Name:      CloudPanel Ubuntu
Protocol:          SSH
Host:              192.168.1.105
Port:              22
Username:          root

Authentication:
  Method:          Public Key
  Key File:        ~/.ssh/id_ed25519

Network Setting:   Use SSH Gateway
  Gateway Host:    Via WireGuard VPN
```

#### Paires de Clés SSH

```bash
# Générer une clé SSH (ED25519 - moderne & sécurisé)
ssh-keygen -t ed25519 -C "admin@fitandfun" -f ~/.ssh/id_ed25519

# Copier la clé publique sur le serveur
ssh-copy-id -i ~/.ssh/id_ed25519.pub root@192.168.1.105

# Tester la connexion
ssh -i ~/.ssh/id_ed25519 root@192.168.1.105
```

#### Sessions MobaXterm Recommandées

```
Session 1 : CloudPanel SSH
  ├─ Monitoring (top, netdata)
  ├─ Logs (Nginx, PHP, MySQL)
  └─ Gestion des services

Session 2 : Caddy Reverse Proxy SSH
  ├─ Configuration Caddyfile
  ├─ Logs d'accès
  └─ Tests SSL/TLS

Session 3 : File Manager (SFTP)
  └─ uploads/, backups/, logs/
```

---

### **6. Free Box - Configuration Réseau Local**

#### Données de Connexion

```
Box Free:
  IP Publique:        149.232.200.168
  Gateway:            192.168.1.1
  DHCP Range:         192.168.1.100 - 192.168.1.254
  
Serveur Interne:
  Hostname:           cloudpanel
  IP Interne:         192.168.1.105
  MAC Address:        [À configurer pour IP fixe]
  Port Forwarding:    80 → 192.168.1.105:80
                      443 → 192.168.1.105:443
                      51820 → 192.168.1.105:51820 (WireGuard)
```

#### Configuration Port Forwarding Box Free

```
Protocole | Port Externe | Port Interne | IP Interne
────────────────────────────────────────────────────
TCP       | 80           | 80           | 192.168.1.105
TCP       | 443          | 443          | 192.168.1.105
UDP       | 51820        | 51820        | 192.168.1.105 (WireGuard)
```

---

## 🌐 Flux de Requête Complet

### **1. Utilisateur Accède au Site**

```
Navigateur
  ↓ HTTPS requête
Cloudflare (DNS résolvé + CDN cache)
  ↓ Forward to origin
Free Box (149.232.200.168)
  ↓ Port forwarding 443
Caddy Reverse Proxy (Debian)
  ↓ HTTP reverse proxy
CloudPanel VM (192.168.1.105:80)
  ↓ Nginx + Varnish cache
PHP-FPM
  ↓ Route dynamique
Application Fit&Fun
  ↓ Requête BDD/IA/SMTP
MySQL / Python / Mail Server
  ↓ Réponse
Navigateur (HTML+CSS+JS)
```

### **2. Administrateur Accès en SSH**

```
MobaXterm (Windows/Linux/Mac)
  ↓ Établir tunnel VPN WireGuard
VPN Client (10.0.0.2/24)
  ↓ Chiffrement VPN
Internet → Free Box
  ↓ Port 51820
Serveur WireGuard (10.0.0.1/24)
  ↓ SSH sur port 22
CloudPanel SSH (192.168.1.105)
  ↓ Authentification clé publique
Terminal administrateur
  ↓ Commandes système
Serveurs, logs, bases de données
```

---

## 📊 Schéma de Sécurité en Couches

```
┌─────────────────────────────────────────────────────┐
│ 🌐 COUCHE 1: CLOUDFLARE (Filtrage Public)          │
│   ├─ DDoS Protection                               │
│   ├─ Bot Management                                │
│   ├─ Firewall Rules                                │
│   └─ Rate Limiting (50 req/min)                     │
└──────────────┬──────────────────────────────────────┘
               ↓ HTTPS/TLS 1.2+
┌──────────────────────────────────────────────────────┐
│ 🔀 COUCHE 2: CADDY (Reverse Proxy Sécurisé)        │
│   ├─ Let's Encrypt SSL/TLS                          │
│   ├─ HSTS Headers                                   │
│   ├─ Security Headers (X-Frame-Options, etc)        │
│   ├─ Rate Limiting                                  │
│   └─ Access Logging                                 │
└──────────────┬──────────────────────────────────────┘
               ↓ Proxy HTTP Interne (192.168.1.105)
┌──────────────────────────────────────────────────────┐
│ 🖥️ COUCHE 3: CLOUDPANEL VM (Application)            │
│   ├─ Nginx SSL/TLS (backup)                         │
│   ├─ Varnish Cache                                  │
│   ├─ PHP Session Security                           │
│   ├─ SQL Injection Prevention (Prepared Statements) │
│   ├─ CSRF Protection (Tokens)                       │
│   └─ Input Validation & Sanitization                │
└──────────────┬──────────────────────────────────────┘
               ↓ Localhost
┌──────────────────────────────────────────────────────┐
│ 🗄️ COUCHE 4: BASE DE DONNÉES & SERVICES            │
│   ├─ MySQL (Only localhost access)                  │
│   ├─ Prepared Queries (PDO)                         │
│   ├─ Python/SD (localhost:7860)                     │
│   └─ SMTP (SSL/TLS 587)                             │
└──────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────┐
│ 🔐 COUCHE ADMIN: VPN + SSH (Accès Administrateur)   │
│   ├─ WireGuard VPN (Chiffré)                        │
│   ├─ SSH Keys (ED25519)                             │
│   ├─ Port Knocking (optionnel)                      │
│   └─ 2FA (optionnel)                                │
└──────────────────────────────────────────────────────┘
```

---

## ☄️ Architecture Générale Simplifiée

```
┌─────────────────────────────────────────┐
│         UTILISATEURS (Internet)         │
│         projet.pouximixi.fr             │
└──────────────┬──────────────────────────┘
               │ 149.232.200.168
┌──────────────▼──────────────────────────┐
│      CLOUDFLARE (DNS/CDN/SSL)           │
└──────────────┬──────────────────────────┘
               │ HTTPS
┌──────────────▼──────────────────────────┐
│   CADDY REVERSE PROXY (Debian)          │
└──────────────┬──────────────────────────┘
               │ 192.168.1.105
┌──────────────▼──────────────────────────┐
│    CLOUDPANEL (Ubuntu - Nginx - PHP)    │
│  ├─ Application Fit&Fun                 │
│  ├─ MySQL Database                      │
│  └─ Python/Stable Diffusion             │
└──────────────┬──────────────────────────┘
               │ WireGuard VPN
┌──────────────▼──────────────────────────┐
│  ADMINISTRATEUR (MobaXterm SSH)         │
└──────────────────────────────────────────┘
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

## �‍💻 Procédures d'Administration & Maintenance

### **Accès Administrateur via VPN + SSH**

#### Étape 1 : Connecter le VPN WireGuard

```bash
# 1. Importer le fichier de configuration WireGuard
# Dans MobaXterm → Tools → Network → WireGuard
#    Importer: /home/utilisateur/.wg/fitandfun.conf

# 2. Activer la connexion VPN
# Cliquer sur "Connect" dans l'interface WireGuard

# 3. Vérifier la connexion
# Accès maintenant à 192.168.1.0/24
```

#### Étape 2 : Connecter SSH MobaXterm

```
1. Ouvrir MobaXterm
2. Session → New Session → SSH
3. Remplir les informations :
   - Host : 192.168.1.105
   - User : root
   - Port : 22
   - Key : ~/.ssh/id_ed25519
4. Cliquer "OK"
```

### **Dashboard Monitoring**

#### CloudPanel Dashboard

```
URL: https://192.168.1.105:8443/
Accès: SSH Key + Password
Sections:
  • Dashboard: Statistiques système
  • Virtual Hosts: Gestion des domaines
  • Domains: Certificats SSL
  • Databases: Gestion MySQL
  • File Manager: Gestion des fichiers
  • Backups: Sauvegardes
  • Logs: Journaux d'erreurs
  • Settings: Configuration
```

#### Monitoring NetData (optionnel)

```
URL: http://192.168.1.105:19999/
Métrics:
  • CPU, RAM, Disque
  • Processus
  • Réseau
  • MySQL Performance
  • Nginx Stats
```

### **Maintenance Système Régulière**

#### Quotidienne (Daily)

```bash
# 1. Vérifier les erreurs critiques
tail -50 /var/log/nginx/error.log
tail -50 /var/log/php/error.log-$(date +%Y-%m-%d)

# 2. Vérifier l'espace disque
df -h
du -sh /home/pouximixi-projet/*

# 3. Vérifier les services
systemctl status nginx
systemctl status php8.1-fpm
systemctl status mysql
```

#### Hebdomadaire (Weekly)

```bash
# 1. Backup de la base de données
mysqldump -u admin -p fitandfun > /home/pouximixi-projet/backups/fitandfun-$(date +%Y%m%d).sql

# 2. Nettoyer les anciens logs
find /home/pouximixi-projet/logs -name "*.log*" -mtime +30 -delete

# 3. Mettre à jour les packages
sudo apt update && sudo apt upgrade -y

# 4. Vérifier l'intégrité de la BDD
php /home/pouximixi-projet/check_db.php
```

#### Mensuelle (Monthly)

```bash
# 1. Renouvellement des certificats SSL (auto via Caddy)
sudo caddy reload --config /etc/caddy/Caddyfile

# 2. Audit de sécurité
sudo fail2ban-client status
sudo iptables -L -n

# 3. Vérifier les backdoors
rkhunter --check --skip-keypress

# 4. Analyser les logs Cloudflare
# Connexion Cloudflare Dashboard
```

### **Gestion des Sauvegardes**

#### Stratégie Backup

```
Fréquence:   Quotidienne à 02:00 AM
Rétention:   30 jours (rotation)
Stockage:    /home/pouximixi-projet/backups/databases/
```

#### Script de Backup Automatique

```bash
#!/bin/bash
# /usr/local/bin/backup_fitandfun.sh

BACKUP_DIR="/home/pouximixi-projet/backups/databases"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="fitandfun"
DB_USER="admin"

# Créer le backup
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/fitandfun_$DATE.sql

# Compresser
gzip $BACKUP_DIR/fitandfun_$DATE.sql

# Supprimer les backups > 30 jours
find $BACKUP_DIR -name "*.sql.gz" -mtime +30 -delete

echo "Backup completed: fitandfun_$DATE.sql.gz"
```

Ajouter au crontab :
```bash
crontab -e
# Ajouter la ligne :
0 2 * * * /usr/local/bin/backup_fitandfun.sh >> /var/log/backup.log 2>&1
```

#### Restaurer une Sauvegarde

```bash
# 1. Lister les backups disponibles
ls -la /home/pouximixi-projet/backups/databases/

# 2. Décompresser le backup
gunzip fitandfun_20260115_020000.sql.gz

# 3. Restaurer la base
mysql -u admin -p fitandfun < fitandfun_20260115_020000.sql

# 4. Vérifier la restauration
php /home/pouximixi-projet/check_db.php
```

### **Mise à Jour Application**

#### Étapes de Mise à Jour

```bash
# 1. Créer un backup AVANT la mise à jour
mysqldump -u admin -p fitandfun > backups/pre-update-$(date +%Y%m%d).sql

# 2. Mettre en maintenance (optionnel)
# Créer /htdocs/projet.pouximixi.fr/maintenance.html

# 3. Puller les nouveaux fichiers
cd /home/pouximixi-projet/htdocs/projet.pouximixi.fr
git pull origin main

# 4. Exécuter les migrations (si applicables)
php /home/pouximixi-projet/update_db_*.php

# 5. Vider les caches
rm -rf /home/pouximixi-projet/tmp/*
sudo systemctl restart php8.1-fpm
sudo systemctl restart nginx

# 6. Vérifier les logs
tail -f /var/log/nginx/error.log
```

### **Dépannage Rapide**

#### La page blanche s'affiche

```bash
# 1. Vérifier les logs PHP
tail -50 /var/log/php/error.log-$(date +%Y-%m-%d)

# 2. Vérifier la BDD
php /home/pouximixi-projet/check_db.php

# 3. Vérifier les permissions
ls -la /home/pouximixi-projet/htdocs/projet.pouximixi.fr/

# 4. Redémarrer PHP
sudo systemctl restart php8.1-fpm
```

#### Lenteurs / Haute CPU

```bash
# 1. Voir les processus
top -bn1 | head -20

# 2. Vérifier MySQL
mysql -u admin -p
> SHOW PROCESSLIST;

# 3. Vérifier Nginx
netstat -antp | grep nginx

# 4. Redémarrer les services
sudo systemctl restart nginx php8.1-fpm mysql
```

#### Erreur "Cannot write to uploads"

```bash
# 1. Vérifier les permissions
ls -la /home/pouximixi-projet/htdocs/projet.pouximixi.fr/uploads/

# 2. Corriger les permissions
sudo chown www-data:www-data /home/pouximixi-projet/htdocs/projet.pouximixi.fr/uploads -R
sudo chmod 755 /home/pouximixi-projet/htdocs/projet.pouximixi.fr/uploads

# 3. Vérifier l'espace disque
df -h
```

---

## �📞 Contact & Support

- 📧 Email : contact@pouximixi.fr
- 🌐 Site : https://pouximixi.fr
- 📱 Téléphone : +33 (disponible sur le site)
- 💬 Support : Page Contact sur le site

---

## � Infos de Connexion Rapide

> ⚠️ **CONFIDENTIEL** - À conserver en sécurité

### **Accès Application**

| Service | URL | Identifiants |
|---------|-----|--------------|
| **Site Public** | https://projet.pouximixi.fr | Public |
| **Admin Panel** | https://projet.pouximixi.fr/?page=admin_dashboard | À créer |
| **CloudPanel** | https://192.168.1.105:8443/ | SSH Key + Password |
| **NetData** | http://192.168.1.105:19999/ | Public (IPs autorisées) |

### **Serveurs & Infrastructure**

| Service | Host | Port | Utilisateur |
|---------|------|------|-------------|
| **VM CloudPanel** | 192.168.1.105 | 22 (SSH) | root |
| **MySQL** | 192.168.1.105 | 3306 | admin |
| **PHP-FPM** | 192.168.1.105 | 9000 | www-data |
| **Nginx** | 192.168.1.105 | 80, 443 | www-data |
| **Caddy Proxy** | Debian Reverse Proxy | 80, 443 | caddy |
| **Stable Diffusion** | 127.0.0.1 | 7860 | python |

### **Bases de Données**

```
BDD: fitandfun
User: admin
Pass: fitandfun21
Host: localhost (seulement depuis 192.168.1.105)

Tables principales:
  • users_app
  • activites
  • seances
  • inscriptions
  • abonnements
```

### **Accès VPN & SSH**

```
VPN:           WireGuard
Port VPN:      51820 (UDP)
VPN Subnet:    10.0.0.0/24
Admin IP:      10.0.0.2

SSH Protocol:  ED25519 Keys
SSH Port:      22
SSH Gateway:   Via WireGuard

MobaXterm:
  Sessions sauvegardées pour:
    1. CloudPanel SSH (192.168.1.105)
    2. Caddy SSH (Debian)
    3. File Manager SFTP
```

### **Cloudflare & Domaine**

```
Domaine:       projet.pouximixi.fr
Provider:      Cloudflare
IP Publique:   149.232.200.168 (Free Box)
Nameservers:   Cloudflare DNS
SSL Mode:      Full (Strict)
```

### **Emails & Services**

```
SMTP Server:   mail71.lwspanel.com
SMTP Port:     587 (TLS)
SMTP User:     noreply@rips.fr
SMTP Pass:     CuF2*ERx4wybCqf (masqué en production)
```

### **Fichiers Importants à Connaître**

```
Config Application:      /home/pouximixi-projet/config.php
Code Principal:          /home/pouximixi-projet/htdocs/projet.pouximixi.fr/
Logs Nginx:             /home/pouximixi-projet/logs/nginx/
Logs PHP:               /home/pouximixi-projet/logs/php/
Backups:                /home/pouximixi-projet/backups/
IA/Stable Diffusion:    /home/pouximixi-projet/ai-tools/
Uploads (Avatars):      /home/pouximixi-projet/htdocs/projet.pouximixi.fr/uploads/

Configuration Caddy:     /etc/caddy/Caddyfile
Configuration WireGuard: /etc/wireguard/wg0.conf
```

### **Commandes d'Urgence**

```bash
# Redémarrer tout rapidement
sudo systemctl restart nginx php8.1-fpm mysql caddy

# Afficher les erreurs en temps réel
tail -f /var/log/nginx/error.log &
tail -f /var/log/php/error.log-$(date +%Y-%m-%d) &
tail -f /home/pouximixi-projet/logs/nginx/error.log-$(date +%Y-%m-%d)

# Vérifier le status global
systemctl status nginx php8.1-fpm mysql caddy

# Nettoyer les caches en urgence
rm -rf /home/pouximixi-projet/tmp/*
redis-cli FLUSHALL  # Si Redis utilisé

# Redémarrer l'IA Stable Diffusion
pkill -f "stable-diffusion" && /home/pouximixi-projet/ai-tools/launch_cpu_mode.sh
```

---

## �🗺️ Roadmap & Futures Fonctionnalités

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
