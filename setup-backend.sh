#!/usr/bin/env bash
#
# setup-backend.sh — Remise en etat du backend Be-Market apres un reset de base.
#
# Ce script automatise toutes les etapes necessaires pour que l'API fonctionne :
#   1. (optionnel) migrate:fresh  -> recree une base propre
#   2. Sanctum                     -> authentication API par cookies de session
#   3. config:clear               -> prise en compte du .env a jour
#   4. Seeders                     -> categories, specs, admin (+ demo local)
#
# Par defaut, TOUS les seeders sont lances (DatabaseSeeder) :
#   - CategorySeeder        (categories)
#   - CategorySpecsSeeder   (specs des categories)
#   - AdminSeeder           (compte super-admin, via .env ADMIN_SEED_*)
#   - + compte demo "test@example.com" (uniquement en environnement local)
#
# Usage :
#   ./setup-backend.sh                # migrate + setup Sanctum + TOUS les seeders
#   ./setup-backend.sh --fresh        # migrate:fresh (EFFACE TOUT) puis setup + TOUS les seeders
#   ./setup-backend.sh --admin-only   # comme ci-dessus mais seed uniquement l'admin
#   ./setup-backend.sh --no-seed      # setup sans lancer aucun seeder
#
# A lancer depuis n'importe ou : le script se place tout seul dans son dossier.

set -euo pipefail

# --- Se placer dans le dossier du backend (dossier du script) ---
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# --- Couleurs ---
GREEN='\033[0;32m'; YELLOW='\033[1;33m'; RED='\033[0;31m'; BLUE='\033[0;34m'; NC='\033[0m'
info()  { echo -e "${BLUE}==>${NC} $*"; }
ok()    { echo -e "${GREEN}OK ${NC} $*"; }
warn()  { echo -e "${YELLOW}!! ${NC} $*"; }
err()   { echo -e "${RED}ERREUR${NC} $*" >&2; }

# --- Verifier qu'on est bien dans un projet Laravel ---
if [[ ! -f artisan ]]; then
  err "Fichier 'artisan' introuvable. Lance ce script depuis le dossier be-market-back."
  exit 1
fi

# --- Parser les options ---
DO_FRESH=false
SEED_MODE="all"   # all | admin | none
for arg in "$@"; do
  case "$arg" in
    --fresh)      DO_FRESH=true ;;
    --admin-only) SEED_MODE="admin" ;;
    --no-seed)    SEED_MODE="none" ;;
    --seed)       SEED_MODE="all" ;;   # retro-compat : seed complet
    -h|--help)
      grep '^#' "$0" | sed 's/^# \{0,1\}//'; exit 0 ;;
    *) warn "Option inconnue ignoree : $arg" ;;
  esac
done

echo
info "Backend : $SCRIPT_DIR"
echo

# =========================================================================
# 1. Reset de la base (optionnel)
# =========================================================================
if [[ "$DO_FRESH" == true ]]; then
  warn "migrate:fresh va EFFACER toutes les donnees de la base."
  read -r -p "    Continuer ? [o/N] " confirm
  if [[ "${confirm,,}" != "o" && "${confirm,,}" != "oui" ]]; then
    err "Annule par l'utilisateur."
    exit 1
  fi
  info "Reset de la base (migrate:fresh)..."
  php artisan migrate:fresh
  ok "Base recreee."
else
  info "Application des migrations en attente (migrate)..."
  php artisan migrate --force
  ok "Migrations a jour."
fi

# =========================================================================
# 2. Cles Sanctum (sanctum/csrf-cookie est disponible automatiquement)
# =========================================================================
ok "Sanctum est configure (pas de cles necessaires)."

# =========================================================================
# 3. Verification du cookie Sanctum
# =========================================================================
info "Verification de Sanctum..."
php artisan route:list --name=sanctum >/dev/null 2>&1 && ok "Sanctum actif." || warn "Sanctum pourrait ne pas etre configure correctement."

# =========================================================================
# 4. Nettoyage du cache de config (prise en compte du .env)
# =========================================================================
info "Nettoyage du cache de configuration..."
php artisan config:clear >/dev/null
ok "Cache de configuration vide."

# =========================================================================
# 5. Seed
# =========================================================================
case "$SEED_MODE" in
  all)
    info "Seed complet (tous les seeders via DatabaseSeeder)..."
    info "  - CategorySeeder, CategorySpecsSeeder, AdminSeeder (+ demo si local)"
    php artisan db:seed --force
    ok "Seed complet termine."
    ;;
  admin)
    info "Seed de l'admin uniquement (AdminSeeder)..."
    php artisan db:seed --class=AdminSeeder --force
    ok "Admin seede (identifiants dans .env : ADMIN_SEED_EMAIL / ADMIN_SEED_PASSWORD)."
    ;;
  none)
    warn "Aucun seeder lance (--no-seed)."
    ;;
esac

echo
ok "Backend pret."
echo
info "Rappel : lance le serveur dans un terminal DEDIE :"
echo "        php artisan serve"
echo
