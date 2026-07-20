#!/usr/bin/env bash
#
# deploy-backend.sh — Deploiement du backend Be-Market en PRODUCTION.
#
# DIFFERENCE MAJEURE avec setup-backend.sh (qui est reserve au DEV) :
#   - JAMAIS de migrate:fresh (on ne detruit jamais la base de prod)
#   - JAMAIS de seed de donnees de demo ni de compte de test
#   - Migrations INCREMENTALES uniquement (migrate --force)
#   - Optimisations de prod (config/route/view cache)
#   - Verifications de securite (APP_ENV, APP_DEBUG)
#
# Prerequis cote serveur (a faire UNE fois, manuellement et de facon securisee) :
#   - .env de production correctement rempli (voir verifications ci-dessous)
#   - Cles Passport generees et SAUVEGARDEES : php artisan passport:keys
#   - Personal Access Client cree UNE fois : php artisan passport:client --personal
#     (son secret ne doit PAS etre regenere a chaque deploiement)
#   - L'admin cree une fois puis mot de passe change (ne pas re-seeder en prod)
#
# Usage :
#   ./deploy-backend.sh
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

GREEN='\033[0;32m'; YELLOW='\033[1;33m'; RED='\033[0;31m'; BLUE='\033[0;34m'; NC='\033[0m'
info()  { echo -e "${BLUE}==>${NC} $*"; }
ok()    { echo -e "${GREEN}OK ${NC} $*"; }
warn()  { echo -e "${YELLOW}!! ${NC} $*"; }
err()   { echo -e "${RED}ERREUR${NC} $*" >&2; }

[[ -f artisan ]] || { err "Lance ce script depuis le dossier be-market-back."; exit 1; }

# =========================================================================
# 0. Verifications de securite AVANT tout
# =========================================================================
info "Verifications de l'environnement de production..."

APP_ENV="$(php artisan tinker --execute="echo config('app.env');" 2>/dev/null | tr -d '[:space:]' || echo "")"
APP_DEBUG="$(php artisan tinker --execute="echo config('app.debug') ? 'true':'false';" 2>/dev/null | tr -d '[:space:]' || echo "")"

if [[ "$APP_ENV" != *production* ]]; then
  err "APP_ENV n'est pas 'production' (valeur: '$APP_ENV')."
  err "Configure APP_ENV=production dans le .env du serveur avant de deployer."
  exit 1
fi
ok "APP_ENV = production."

if [[ "$APP_DEBUG" == *true* ]]; then
  err "APP_DEBUG=true en production : fuite d'informations sensibles."
  err "Mets APP_DEBUG=false dans le .env."
  exit 1
fi
ok "APP_DEBUG = false."

# Cles Passport : elles DOIVENT deja exister (ne jamais les regenerer en prod,
# sinon tous les tokens existants sont invalides).
if [[ ! -f storage/oauth-private.key || ! -f storage/oauth-public.key ]]; then
  err "Cles Passport manquantes. Genere-les UNE fois et sauvegarde-les :"
  err "    php artisan passport:keys"
  exit 1
fi
ok "Cles Passport presentes."

# =========================================================================
# 1. Dependances (sans dev, optimise)
# =========================================================================
if command -v composer >/dev/null 2>&1; then
  info "Installation des dependances (production)..."
  composer install --no-dev --optimize-autoloader --no-interaction
  ok "Dependances installees."
else
  warn "composer introuvable dans le PATH : etape ignoree."
fi

# =========================================================================
# 2. Migrations INCREMENTALES (jamais fresh)
# =========================================================================
info "Application des migrations (incrementales)..."
php artisan migrate --force
ok "Migrations a jour."

# =========================================================================
# 3. Verifier le Personal Access Client (SANS le recreer automatiquement)
# =========================================================================
info "Verification du Personal Access Client..."
HAS_PAC="$(php artisan tinker --execute="echo \Laravel\Passport\Client::where('provider','users')->get()->first(fn(\$c)=>in_array('personal_access', (array) \$c->grant_types)) ? 'yes':'no';" 2>/dev/null | tr -d '[:space:]' || echo "no")"
if [[ "$HAS_PAC" == *yes* ]]; then
  ok "Personal Access Client present."
else
  err "Personal Access Client absent. Cree-le UNE fois manuellement (et garde son secret) :"
  err "    php artisan passport:client --personal --name=\"Personal Access Client\" --provider=users"
  exit 1
fi

# =========================================================================
# 4. Optimisations de production (caches)
# =========================================================================
info "Optimisation (cache config / routes / vues)..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
ok "Caches de production generes."

# =========================================================================
# 5. Rappels manuels (NON automatises volontairement)
# =========================================================================
echo
ok "Deploiement backend termine."
echo
warn "Non automatise volontairement (a faire a la main si necessaire) :"
echo "   - Creation de l'admin : php artisan db:seed --class=AdminSeeder"
echo "     puis CHANGER son mot de passe (ne pas laisser celui du .env)."
echo "   - Redemarrer le worker de queue / php-fpm / le serveur web selon ton infra."
echo "   - AUCUN seed de donnees de demo n'est lance en production."
echo
