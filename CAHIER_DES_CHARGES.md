# 📋 CAHIER DES CHARGES - Fit&Fun

**Plateforme de Gestion des Cours de Fitness**

---

## 📑 Table des matières

- [1. Informations Générales](#1--informations-générales)
- [2. Contexte & Problématique](#2--contexte--problématique)
- [3. Objectifs du Projet](#3--objectifs-du-projet)
- [4. Périmètre Fonctionnel](#4--périmètre-fonctionnel)
- [5. Exigences Détaillées](#5--exigences-détaillées)
- [6. Exigences Techniques](#6--exigences-techniques)
- [7. Exigences Non-Fonctionnelles](#7--exigences-non-fonctionnelles)
- [8. Architecture & Infrastructure](#8--architecture--infrastructure)
- [9. Sécurité & Conformité](#9--sécurité--conformité)
- [10. Livrables](#10--livrables)
- [11. Planning & Timeline](#11--planning--timeline)
- [12. Ressources](#12--ressources)
- [13. Risques & Mitigation](#13--risques--mitigation)
- [14. Critères d'Acceptation](#14--critères-dacceptation)
- [15. Contraintes & Limitations](#15--contraintes--limitations)
- [16. Budget & Coûts](#16--budget--coûts)
- [17. Support & Maintenance](#17--support--maintenance)

---

## 1️⃣ Informations Générales

### Identification du Projet
- **Nom du Projet** : Fit&Fun
- **Acronyme** : FF
- **Type** : Plateforme SaaS de Gestion de Fitness
- **Client** : Pouximixi (Salles de Fitness)
- **Responsable Projet** : Pouximixi
- **Date de Démarrage** : Janvier 2026
- **Durée Estimée** : Phase 1 (4 mois)

### Parties Prenantes
| Rôle | Nom/Entité | Responsabilité |
|------|-----------|-----------------|
| **Client** | Pouximixi | Validation, feedback |
| **Project Manager** | Équipe Pouximixi | Coordination |
| **Développeur** | Équipe technique | Développement & maintenance |
| **Administrateur Système** | Équipe technique | Infrastructure & sécurité |
| **Support utilisateur** | Support Pouximixi | Assistance utilisateurs |

---

## 2️⃣ Contexte & Problématique

### Situation Actuelle
- ❌ Gestion manuelle des cours (papier ou tableurs)
- ❌ Pas d'historique d'adhérence
- ❌ Difficulté à gérer les abonnements
- ❌ Communication lente avec les adhérents
- ❌ Impossibilité de générer des statistiques

### Besoins Identifiés
- ✅ Plateforme centrale pour gérer adhérents & cours
- ✅ Planification et gestion automatisée des cours
- ✅ Gestion des abonnements et tarifications
- ✅ Communication directe avec les adhérents
- ✅ Analytics et reportings

### Opportunités
- 🎯 Augmenter la rétention des adhérents
- 🎯 Réduire les frais administratifs
- 🎯 Offrir une meilleure expérience utilisateur
- 🎯 Monétiser davantage grâce aux données

---

## 3️⃣ Objectifs du Projet

### Objectif Principal
**Créer une plateforme web SaaS complète permettant la gestion intégrale des cours de fitness, des adhérents et des abonnements avec une expérience utilisateur optimale.**

### Objectifs Secondaires

#### 🎯 Objectif 1 : Gestion Efficace des Adhérents
- Enregistrement et authentification sécurisés
- Profils personnalisés avec avatars IA
- Historique d'activité
- Notifications par email
- **KPI** : 100% des adhérents avec profil créé en 3 mois

#### 🎯 Objectif 2 : Planning Automatisé
- Création flexible des cours
- Calendrier interactif
- Gestion des capacités
- Export calendrier (iCal)
- **KPI** : Réduction de 80% du temps de planification

#### 🎯 Objectif 3 : Gestion des Abonnements
- Packages d'abonnements variés
- Gestion des tarifs
- Suivi des paiements
- Analytics revenue
- **KPI** : Augmentation de 25% du taux d'abonnement

#### 🎯 Objectif 4 : Intelligence Artificielle
- Génération d'avatars personnalisés
- Intégration Stable Diffusion
- Recommandations basées sur l'IA
- **KPI** : 50% des adhérents génèrent un avatar

#### 🎯 Objectif 5 : Administration Robuste
- Dashboard complet
- Gestion des rôles & permissions
- Gestion des utilisateurs
- Logs et auditing
- **KPI** : 0 downtime en production

---

## 4️⃣ Périmètre Fonctionnel

### ✅ INCLUS dans le Projet

#### 4.1.1 Gestion des Utilisateurs
- [x] Inscription et authentification
- [x] Gestion des profils
- [x] Système de rôles (4 niveaux)
- [x] Réinitialisation de mot de passe
- [x] Upload d'avatar IA
- [x] Historique de connexion

#### 4.1.2 Gestion des Cours/Activités
- [x] Création, édition, suppression de cours
- [x] Gestion des horaires
- [x] Gestion des capacités
- [x] Assignation des animateurs
- [x] Description et détails des cours
- [x] Statut actif/inactif

#### 4.1.3 Planning & Séances
- [x] Calendrier interactif des séances
- [x] CRUD des séances
- [x] Visualisation par jour/semaine/mois
- [x] Export en iCalendar (.ics)
- [x] Notifications de cours à venir

#### 4.1.4 Inscriptions & Participation
- [x] Inscription aux cours
- [x] Désincription
- [x] Gestion des listes de présence
- [x] Historique des participation
- [x] Statut (inscrit, présent, absent)

#### 4.1.5 Abonnements & Tarification
- [x] Création de packages d'abonnement
- [x] Gestion des tarifs
- [x] Suivi des adhésions actives
- [x] Historique des abonnements
- [x] Paiements (intégration future)

#### 4.1.6 Système d'Évaluation
- [x] Saisie de notes post-séance
- [x] Feedback des adhérents
- [x] Évaluation des animateurs
- [x] Commentaires libres

#### 4.1.7 Communication
- [x] Envoi d'emails automatiques
- [x] Notifications de cours
- [x] Confirmation d'inscription
- [x] Rappels avant séance
- [x] Formulaire de contact

#### 4.1.8 Admin Dashboard
- [x] Vue d'ensemble statistiques
- [x] Gestion complète des utilisateurs
- [x] Gestion des cours et séances
- [x] Configuration des abonnements
- [x] Logs d'activité
- [x] Mode support (login as)

#### 4.1.9 Intégration IA
- [x] Intégration Stable Diffusion
- [x] Génération d'avatars
- [x] Upload des images générées
- [x] Gestion des modèles
- [x] Mode CPU (serveur)

#### 4.1.10 Sécurité & Conformité
- [x] Authentification sécurisée
- [x] Protection CSRF
- [x] Validation des entrées
- [x] Hachage des mots de passe
- [x] Logs d'accès et auditing
- [x] Headers de sécurité HTTP

### ❌ EXCLUS du Projet (Hors Périmètre)

| Fonctionnalité | Raison | Phase |
|---|---|---|
| Paiement en ligne (Stripe) | À intégrer en Phase 2 | Phase 2 |
| Application Mobile iOS/Android | Développement ultérieur | Phase 3 |
| Live Streaming des cours | Infrastructure complexe | Phase 3 |
| Intégration smartwatches | Fonctionnalité avancée | Phase 3 |
| Gamification (badges/leaderboards) | Feature nice-to-have | Phase 2 |
| Système de recommandation IA avancé | Données insuffisantes au départ | Phase 2 |
| Intégration réseaux sociaux (login) | Phase ultérieure | Phase 2 |
| Support multilingue complet | Seulement français Phase 1 | Phase 2 |
| Intégration CRM (Salesforce) | Données insuffisantes | Phase 3 |

---

## 5️⃣ Exigences Détaillées

### 5.1 Exigences Fonctionnelles - Adhérents

#### EF-AD-001 : Inscription et Authentification
```
Description: Un utilisateur non authentifié doit pouvoir s'inscrire
Critères:
  - Formulaire d'inscription simplifié (email, password, nom, prénom)
  - Validation des données (email unique, password fort)
  - Création automatique du profil
  - Email de confirmation envoyé
  - Authentification par email/password
  - Session persistante (cookie sécurisé)
Priorité: CRITIQUE
```

#### EF-AD-002 : Profil Utilisateur
```
Description: Chaque adhérent doit avoir un profil personnalisé
Critères:
  - Affichage des données personnelles
  - Édition du profil (nom, email, téléphone)
  - Upload/génération d'avatar
  - Historique des cours suivis
  - Statut d'adhésion
  - Changement de mot de passe
Priorité: CRITIQUE
```

#### EF-AD-003 : Visualisation Planning
```
Description: L'adhérent doit visualiser tous les cours disponibles
Critères:
  - Calendrier interactif (vue jour/semaine/mois)
  - Filtrage par type de cours
  - Affichage horaires, animateur, lieu, capacité
  - Indication places disponibles
  - Inscription directe depuis le planning
Priorité: CRITIQUE
```

#### EF-AD-004 : Gestion Inscriptions
```
Description: L'adhérent peut s'inscrire/désinscrire aux cours
Critères:
  - Bouton "S'inscrire" si places disponibles
  - Confirmation d'inscription
  - Email de confirmation
  - Possibilité de désinscrire (avec délai?)
  - Liste "Mes cours" avec ses inscriptions
  - Rappels 24h avant le cours
Priorité: CRITIQUE
```

#### EF-AD-005 : Historique & Statistiques Personnelles
```
Description: L'adhérent voit son historique d'activité
Critères:
  - Nombre total de séances suivies
  - Taux de présence
  - Cours favoris
  - Progression (semaines, mois)
  - Export des statistiques (PDF)
Priorité: IMPORTANTE
```

### 5.2 Exigences Fonctionnelles - Admin/Bureau

#### EF-BU-001 : Gestion des Adhérents
```
Description: L'admin doit pouvoir gérer tous les adhérents
Critères:
  - Créer un nouvel adhérent (admin)
  - Éditer les infos d'un adhérent
  - Activer/désactiver un compte
  - Voir la liste complète avec filtres/recherche
  - Réinitialiser le mot de passe
  - Supprimer un adhérent (soft delete)
Priorité: CRITIQUE
```

#### EF-BU-002 : Gestion des Cours
```
Description: L'admin crée et gère les cours disponibles
Critères:
  - Créer un nouveau cours (nom, description, capacité)
  - Éditer les propriétés
  - Désactiver temporairement
  - Assigner des animateurs
  - Voir le nombre d'inscrits par cours
  - Historique des modifications
Priorité: CRITIQUE
```

#### EF-BU-003 : Gestion des Séances
```
Description: L'admin crée les séances pour chaque cours
Critères:
  - Créer une séance (date, heure début/fin, lieu)
  - Éditer une séance
  - Voir les inscrits
  - Annoter la présence
  - Supprimer une séance
  - Bulk actions (créer plusieurs séances)
Priorité: CRITIQUE
```

#### EF-BU-004 : Gestion des Abonnements
```
Description: L'admin configure les packages d'abonnement
Critères:
  - Créer un package (nom, prix, durée, avantages)
  - Éditer les prix
  - Activer/désactiver un package
  - Voir les adhérents par package
  - Exporter les données (CSV)
  - Suivi des renouvellements
Priorité: IMPORTANTE
```

#### EF-BU-005 : Dashboard Admin
```
Description: L'admin a une vue d'ensemble du système
Critères:
  - Nombre total d'adhérents
  - Nombre de cours/séances aujourd'hui
  - Taux de remplissage moyen
  - Revenue (si paiements)
  - Utilisateurs actifs (en ligne)
  - Alertes importantes (erreurs, maintenance)
Priorité: IMPORTANTE
```

### 5.3 Exigences Fonctionnelles - Animateur

#### EF-AN-001 : Saisie de Notes
```
Description: L'animateur saisit des notes après chaque cours
Critères:
  - Formulaire post-séance accessible
  - Notas sur l'atmosphère, difficulté
  - Feedback global
  - Enregistrement des présences
  - Sauvegarde automatique
Priorité: IMPORTANTE
```

#### EF-AN-002 : Visualisation du Planning
```
Description: L'animateur voit son planning personnel
Critères:
  - Ses cours uniquement
  - Listes des inscrits par séance
  - Détails du cours
  - Historique des retours
Priorité: IMPORTANTE
```

---

## 6️⃣ Exigences Techniques

### 6.1 Stack Technologique

#### Frontend
```
HTML5, CSS3, JavaScript (vanilla)
- Bootstrap ou Tailwind CSS (pour le responsive)
- Charts.js pour les statistiques
- FullCalendar pour le planning
- Pas de framework front-end lourd (Vue/React)
```

#### Backend
```
PHP 8.0+
- PDO pour l'accès BDD
- Sessions natives PHP
- Pas de framework (architecture MVC custom)
- Composer pour les dépendances
```

#### Base de Données
```
MySQL 8.0 ou MariaDB 10.5+
- InnoDB pour les transactions
- UTF-8mb4 pour les caractères
- Prepared statements obligatoires
```

#### Infrastructure
```
Serveur: Ubuntu 24.04 LTS
Web: Nginx + PHP-FPM 8.1
Cache: Varnish (optionnel)
DNS: Cloudflare
Reverse Proxy: Caddy (Debian)
VPN: WireGuard
Panel: CloudPanel
```

### 6.2 Standards & Conventions

#### Nommage des Fichiers
```
- Controllers: (nom_page).php
- Templates: pages/(nom_page).php
- Includes: includes/(nom).php
- Assets: assets/(type)/(nom)
- CamelCase pour les classes
- snake_case pour les fonctions
```

#### Versions Minimales
```
PHP: 7.4+ (recommandé 8.1+)
MySQL: 5.7+
Nginx: 1.18+
Navigateurs: IE11+ (ou modern browsers)
```

---

## 7️⃣ Exigences Non-Fonctionnelles

### 7.1 Performance

| Métrique | Cible | Tolérance |
|----------|-------|-----------|
| Temps réponse pages statiques | < 200ms | < 500ms |
| Temps réponse pages dynamiques | < 500ms | < 1000ms |
| Chargement calendrier | < 800ms | < 2s |
| Upload avatar IA | < 3s (non-blocking) | < 5s |
| Nombre utilisateurs simultanés | 100 | 50 minimum |
| Capacité BDD | 10,000 adhérents min | Scalabilité à 100k |

### 7.2 Disponibilité & Fiabilité

```
Uptime: 99.5% (3h downtime/mois autorisé)
RTO (Recovery Time Objective): 30 minutes max
RPO (Recovery Point Objective): 24 heures (backups quotidiens)
SLA: 99% pendant heures de bureau (9h-18h)
```

### 7.3 Sécurité

```
Authentification: Session PHP sécurisée
Chiffrement: HTTPS/TLS 1.2+ obligatoire
Mots de passe: Bcrypt (cost: 12)
CSRF: Tokens sur tous les formulaires
SQL Injection: Prepared statements obligatoires
XSS: Échappement HTML sur outputs
Audit: Logs de tous les accès admin
Rate Limiting: 50 req/min/IP
```

### 7.4 Scalabilité

```
Utilisateurs: Escalade jusqu'à 50,000 adhérents
Cours: Jusqu'à 1,000 cours actifs
Séances: Jusqu'à 10,000 séances/mois
Données: Historique 5 ans conservé
Architecture: Prête pour clustering MySQL (réplication)
```

### 7.5 Usabilité

```
Accessibilité: WCAG 2.1 Level AA minimum
Interface: Responsive design (mobile, tablette, desktop)
Navigation: Hiérarchie logique, max 3 clics avant action
Temps d'apprentissage: < 30 min pour utilisateur moyen
Taux d'erreur: < 5% de mauvaises saisies
```

---

## 8️⃣ Architecture & Infrastructure

### 8.1 Architecture Système

```
┌─────────────────────────┐
│   Utilisateurs Final    │
│  (Navigateur Web)       │
└────────────┬────────────┘
             │ HTTPS
┌────────────▼────────────┐
│ CLOUDFLARE (CDN/DNS)    │
└────────────┬────────────┘
             │
┌────────────▼────────────┐
│ CADDY REVERSE PROXY     │
│ (Debian)                │
└────────────┬────────────┘
             │
┌────────────▼────────────┐
│ NGINX + PHP-FPM         │
│ (Ubuntu 192.168.1.105)  │
├─ Varnish Cache         │
├─ Session Storage       │
└────────────┬────────────┘
       ┌─────┴──────┐
       │             │
   ┌───▼───┐    ┌────▼────┐
   │ MySQL │    │ Python   │
   │ (BDD) │    │ (SD-AI)  │
   └───────┘    └──────────┘
```

### 8.2 Environnements

```
Development:
  - Local ou VM développement
  - Données test
  - Logs verbose
  - Erreurs affichées

Staging:
  - Copie de production
  - Données sûres (anonymisées)
  - Tests avant release
  - Performance testing

Production:
  - Données réelles
  - Erreurs loggées (pas affichées)
  - Backups quotidiens
  - Monitoring 24/7
```

---

## 9️⃣ Sécurité & Conformité

### 9.1 Exigences de Sécurité

#### Authentification
- [x] Hachage bcrypt pour les mots de passe
- [x] Sessions PHP avec token CSRF
- [x] Timeout session après 30 min d'inactivité
- [x] Pas de stockage de passwords en clair

#### Autorisation
- [x] Système de rôles granulaire
- [x] Vérification des permissions à chaque action
- [x] Soft deletes pour l'audit
- [x] Logs de toutes les modifications sensibles

#### Data Protection
- [x] Données sensibles loggées (passwords, emails)
- [x] Données en transit : HTTPS/TLS 1.2+
- [x] Données au repos : permissions fichiers restrictives
- [x] Backups chiffrés (optionnel)

#### Input Validation
- [x] Validation côté serveur obligatoire
- [x] Escaped output sur tous les affichages
- [x] Prepared statements pour toutes les requêtes
- [x] Liste blanche des caractères acceptés

### 9.2 Conformité RGPD

```
Données Personnelles Collectées:
  - Identité (nom, prénom)
  - Contact (email, téléphone)
  - Données d'activité (cours suivis)
  - Images (avatars)

Obligations RGPD:
  - [ ] Mentions légales visible
  - [ ] Politique de confidentialité complète
  - [ ] Consentement explicite (cases à cocher)
  - [ ] Droit d'accès aux données (export)
  - [ ] Droit à l'oubli (suppression compte)
  - [ ] DPA si prestataires externes
  - [ ] Audit de conformité annuel

Rétention Données:
  - Profil utilisateur: Pendant adhésion + 2 ans
  - Logs: 90 jours
  - Backups: 30 jours
```

---

## 🔟 Livrables

### Phase 1 : MVP (4 mois)

#### Fonctionnel
- [x] Application web complète fonctionnelle
- [x] 90% des fonctionnalités Core (voir 5️⃣)
- [x] Base de données normalisée
- [x] Tests de sécurité de base

#### Documentation
- [x] README.md complet (Infrastructure & Code)
- [x] Cahier des charges (ce document)
- [x] Guide utilisateur (adhérent + admin)
- [x] Documentation technique (architecture PHP)
- [x] Guide d'installation
- [x] Guide de sécurité

#### Déploiement
- [x] Infrastructure production
- [x] Certificats SSL/TLS
- [x] Domaine Cloudflare configuré
- [x] Backups automatiques
- [x] Monitoring basique

#### Support
- [x] Procédures de maintenance
- [x] FAQ
- [x] Logs accessibles
- [x] Plan de disaster recovery

### Phase 2 : Améliorations (3 mois)

- [ ] Paiement en ligne (Stripe)
- [ ] Gamification (badges)
- [ ] Recommandations IA avancées
- [ ] Support multilingue
- [ ] Email templates avancés
- [ ] API REST publique

### Phase 3 : Scaling (6 mois)

- [ ] Application mobile iOS/Android
- [ ] Live streaming des cours
- [ ] Intégration smartwatches
- [ ] CRM avancé
- [ ] Machine Learning pour analytics

---

## 1️⃣1️⃣ Planning & Timeline

### Découpage Phases

#### Semaine 1-2 : Setup Infrastructure
```
- Serveur CloudPanel provisionné ✅
- Domaine + Cloudflare configuré ✅
- Caddy reverse proxy en place ✅
- VPN WireGuard opérationnel ✅
- Bases de données créées ✅
```

#### Semaine 3-4 : Foundation PHP
```
- Structure MVC mise en place
- Authentification implémentée
- Gestion des rôles
- Sessions sécurisées
- Templates de base
```

#### Semaine 5-8 : Core Features
```
- Gestion adhérents (CRUD)
- Gestion cours/séances
- Planning interactif
- Inscriptions
- Notifications email
```

#### Semaine 9-12 : Admin & IA
```
- Dashboard admin complet
- Gestion abonnements
- Intégration Stable Diffusion
- Système d'évaluation
- Logs & auditing
```

#### Semaine 13-16 : QA & Production
```
- Tests et bugfixes
- Load testing
- Sécurité audit
- Documentation finalisée
- Go-live production
```

### Gantt Timeline

```
Janvier:   |████████| Infrastructure + Setup
Février:   |████████| Development Core
Mars:      |████████| Admin + IA + Testing
Avril:     |████████| QA + Production Launch
Juin+:     |████████| Phase 2 features
```

---

## 1️⃣2️⃣ Ressources

### Équipe Requise

| Rôle | Niveau | FTE | Durée |
|------|--------|-----|-------|
| **Product Manager** | Senior | 0.5 | 4 mois |
| **Backend Developer** | Senior | 1.0 | 4 mois |
| **Frontend Developer** | Mid | 1.0 | 4 mois |
| **DevOps/SysAdmin** | Mid | 0.5 | 4 mois |
| **QA Tester** | Junior | 0.5 | 2 mois |
| **Technical Writer** | Junior | 0.3 | 2 mois |

**Total : 3.8 FTE pendant 4 mois**

### Infrastructure Requise

```
Serveur:
  - VM CloudPanel: 10 cores, 8GB RAM, 700GB SSD
  - Caddy Reverse Proxy: 2 cores, 2GB RAM
  - Coût mensuel: ~50€

Services:
  - Cloudflare: Gratuit (plan Free)
  - Domaine: 12€/an
  - Email SMTP: Inclus (Rips)
  - Coût annuel: ~60€
```

### Outils & Logiciels

```
Développement:
  - VS Code (gratuit)
  - Git/GitHub (gratuit)
  - Postman (gratuit)
  
Déploiement:
  - CloudPanel (gratuit)
  - Let's Encrypt (gratuit)
  
Monitoring:
  - NetData (gratuit)
  - Fail2ban (gratuit)
```

---

## 1️⃣3️⃣ Risques & Mitigation

### Risques Techniques

| Risque | Probabilité | Impact | Mitigation |
|--------|-------------|--------|-----------|
| Performance MySQL | Moyen | Fort | Indexes, monitoring, replication ready |
| Downtime Stable Diffusion | Moyen | Moyen | Mode dégradé, fallback image default |
| Fuite données | Faible | Critique | Audit sécurité, chiffrage, backups |
| Surcharge serveur | Moyen | Moyen | Load balancing, Varnish cache |

### Risques Projet

| Risque | Probabilité | Impact | Mitigation |
|--------|-------------|--------|-----------|
| Dérive des specs | Moyen | Fort | Freeze des features, review hebdo |
| Manque de ressources | Faible | Fort | Cross-training, dépannage rapide |
| Adoption utilisateurs | Moyen | Moyen | Formation, support excellent |
| Changements clients | Moyen | Moyen | Scope gate, phase 2 pour extras |

---

## 1️⃣4️⃣ Critères d'Acceptation

### Acceptation Technique

- [x] 100% des endpoints critiques testés
- [x] Performance < 500ms en moyenne
- [x] Zéro erreur critique en logs
- [x] Uptime > 99% durant test
- [x] Sécurité audit passed
- [x] Backup & restore vérifiés

### Acceptation Fonctionnelle

- [x] Tous les cas d'usage Core testés
- [x] Pas de bugs "Bloquants"
- [x] Interface responsive testée
- [x] Documentation à jour
- [x] Users peuvent accomplir 95% des tâches

### Acceptation Client

- [x] Signoff du Product Manager
- [x] Entraînement staff complété
- [x] Feedback positif (score > 4/5)
- [x] Plan support défini
- [x] Roadmap Phase 2 approuvée

---

## 1️⃣5️⃣ Contraintes & Limitations

### Techniques

```
Limitations Connues:
- Pas d'intégration paiement Phase 1
- IA CPU-only (pas de GPU)
- Session locale seulement (pas de distributed)
- Base de données MySQL simple (pas de clustering)
- Pas de APIs publiques
```

### Métier

```
- Pas de multilingue (FR seulement)
- Pas de multi-locale (FR seulement)
- Pas d'intégration externes (Slack, etc.)
- Pas de webhooks
- Pas d'import de données existantes (manual)
```

### Budget

```
Budget Fixe Phase 1: ~20,000€ (estimation)
- Développement: 15,000€
- Infrastructure setup: 2,000€
- Documentation & QA: 2,000€
- Support: 1,000€

Coûts Récurrents:
- Infrastructure: 600€/an
- Maintenance: 2000€/an (post-launch)
```

---

## 1️⃣6️⃣ Budget & Coûts

### Budget Développement

| Tâche | Jours | Coût (150€/j) | Total |
|-------|-------|---------------|-------|
| Design & Architecture | 10 | 1,500 | 1,500 |
| Development Core | 40 | 6,000 | 6,000 |
| Admin & IA | 20 | 3,000 | 3,000 |
| Testing & QA | 10 | 1,500 | 1,500 |
| Déploiement & Docs | 8 | 1,200 | 1,200 |
| **TOTAL** | **88** | | **13,200** |

### Budget Infrastructure (1ère année)

| Item | Coût |
|------|------|
| Serveur CloudPanel (12 mois) | 600€ |
| Domaine (.fr) | 12€ |
| Certificats SSL (Let's Encrypt) | 0€ |
| Cloudflare (Free) | 0€ |
| Email SMTP | 0€ |
| Backups/Stockage | 0€ |
| **TOTAL** | **612€** |

### ROI Estimé

```
Investissement Total Phase 1:
  Développement: 13,200€
  Infrastructure: 612€
  ─────────────────────
  TOTAL: 13,812€

Bénéfices Estimés (1ère année):
  - 200 adhérents × 50€/mois = 120,000€ revenue
  - Réduction coûts admin: 5,000€
  - Réduction turnover (retention +10%): 10,000€
  ─────────────────────
  TOTAL: 135,000€

ROI: 135,000 / 13,812 = 9.8x
Payback Period: ~2 mois
```

---

## 1️⃣7️⃣ Support & Maintenance

### Support Utilisateurs

```
Heures Support: 09:00-18:00 (jours ouvrables)
Canaux: Email, Formulaire contact, Téléphone
Temps Réponse:
  - Critique (downtime): < 1 heure
  - Bloquant: < 4 heures
  - Normal: < 24 heures

FAQ & Docs:
  - Documentation en ligne accessible
  - Vidéos tutoriels
  - FAQ mise à jour
```

### Maintenance Préventive

```
Quotidienne:
  - Vérification des erreurs
  - Monitoring performance
  
Hebdomadaire:
  - Backups vérifiés
  - Logs archivés
  - Updates sécurité
  
Mensuelle:
  - Audit sécurité
  - Performance analysis
  - Capacity planning
```

### Update & Patches

```
Sécurité:
  - Critique: Immédiat
  - Haute: < 24h
  - Normal: < 1 semaine

Fonctionnalités:
  - Release tous les mois
  - Changelog communiqué
  - 1 semaine avant production

Backward Compatibility:
  - Maintenu 2 versions
  - Migration path clair
```

---

## Signatures

**Approuvé par :**

| Rôle | Nom | Date | Signature |
|------|-----|------|-----------|
| Product Manager | \_\_\_\_\_\_\_ | \_\_\_\_\_\_ | \_\_\_\_\_\_\_\_ |
| Technical Lead | \_\_\_\_\_\_\_ | \_\_\_\_\_\_ | \_\_\_\_\_\_\_\_ |
| Client | Pouximixi | \_\_\_\_\_\_ | \_\_\_\_\_\_\_\_ |

---

## 📚 Appendices

### A. Glossaire
```
Adhérent: Utilisateur final du système (membre)
Animateur: Instructeur/Coach de cours
Bureau: Admin opérationnel (staff)
Super Admin: Administrateur système complet
Séance: Instance d'un cours (date+heure spécifique)
Activité: Type de cours (Yoga, HIIT, etc.)
MVP: Minimum Viable Product (Phase 1)
SLA: Service Level Agreement (uptime)
RPO: Recovery Point Objective (données)
RTO: Recovery Time Objective (service)
```

### B. Références
```
OWASP Top 10: https://owasp.org/www-project-top-ten/
RGPD Guide: https://www.cnil.fr/
PHP Best Practices: https://www.php-fig.org/
MySQL Documentation: https://dev.mysql.com/
```

### C. Templates de Rapports
```
- Weekly Status Report
- Bug Report Template
- Feature Request Template
- Performance Report
```

---

**Document Version**: 1.0  
**Date**: 15 janvier 2026  
**Auteur**: Équipe Pouximixi  
**Statut**: ✅ APPROUVÉ
