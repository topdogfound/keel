#!/usr/bin/env bash
# ./keel new <vendor/name> [dir] [--port-base N] [--yes]
#
# Start a new project from this template, in a new directory. The template's
# committed files are copied to <dir>, which is then re-keyed: composer package,
# app/database names, optional host ports, demo content stripped, fresh git
# history. The template checkout itself is never modified.
set -euo pipefail

die() { echo "✖ $*" >&2; exit 1; }

usage="Usage: ./keel new <vendor/name> [dir] [--port-base N] [--yes]"

TEMPLATE="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
: "${BOOTSTRAP_IMAGE:?Run this through ./keel new, not directly.}"

TARGET="" DIR="" PORT_BASE="" YES=0
while [ $# -gt 0 ]; do
    case "$1" in
        --yes|-y)      YES=1 ;;
        --port-base)   [ $# -ge 2 ] || die "--port-base needs a value. $usage"
                       PORT_BASE="$2"; shift ;;
        --port-base=*) PORT_BASE="${1#*=}" ;;
        -*)            die "Unknown flag: $1. $usage" ;;
        *)             if   [ -z "$TARGET" ]; then TARGET="$1"
                       elif [ -z "$DIR" ];    then DIR="$1"
                       else die "Too many arguments. $usage"; fi ;;
    esac
    shift
done

[ -n "$TARGET" ] || die "$usage"
[[ "$TARGET" =~ ^[a-z0-9]([a-z0-9._-]*)/[a-z0-9]([a-z0-9._-]*)$ ]] \
    || die "Expected a composer package name like 'acme/widgets', got '$TARGET'"

NAME="${TARGET##*/}"
# acme-widgets -> Acme Widgets, for APP_NAME
TITLE="$(echo "$NAME" | sed -E 's/[-_]+/ /g; s/\b(.)/\U\1/g')"
# acme-widgets -> acme_widgets: hyphens and dots need quoting in Postgres
DB_NAME="${NAME//[-.]/_}"
[[ "$DB_NAME" =~ ^[0-9] ]] && DB_NAME="db_$DB_NAME"

# Default destination is a sibling of the template, named after the package.
# A relative one is taken relative to where ./keel was run, not the template.
if [ -z "$DIR" ]; then
    DEST="$(dirname "$TEMPLATE")/$NAME"
elif [[ "$DIR" = /* ]]; then
    DEST="$DIR"
else
    DEST="${KEEL_CALLER_PWD:-$PWD}/$DIR"
fi
DEST="$(realpath -m "$DEST")"

# --- Preflight: everything that can fail is checked before anything is written.

case "$DEST/" in
    "$TEMPLATE/"*) die "The destination must be outside the template: $DEST" ;;
esac
if [ -e "$DEST" ]; then
    [ -d "$DEST" ] && [ -z "$(ls -A "$DEST")" ] \
        || die "$DEST already exists and is not an empty directory."
fi

# Host ports default to the template's own block, 8765-8771.
if [ -n "$PORT_BASE" ]; then
    [[ "$PORT_BASE" =~ ^[0-9]+$ ]] && [ "$PORT_BASE" -ge 1024 ] && [ "$PORT_BASE" -le 65529 ] \
        || die "--port-base must be a number from 1024 to 65529 (it takes seven ports), got '$PORT_BASE'"
fi
APP_PORT="${PORT_BASE:-8765}"

# The initial commit needs an identity. Read it through the template so a
# repo-local one counts too, and pass it explicitly to the new repo's commit.
GIT_NAME="$(git -C "$TEMPLATE" config user.name || true)"
GIT_EMAIL="$(git -C "$TEMPLATE" config user.email || true)"
[ -n "$GIT_NAME" ] && [ -n "$GIT_EMAIL" ] \
    || die "Set git user.name and user.email first; the new project starts with a commit."

docker info >/dev/null 2>&1 || die "Docker isn't running. It's needed to update composer.json."

echo "New project from the Keel template:"
echo "  from       $TEMPLATE  (left untouched)"
echo "  into       $DEST"
echo "  package    $TARGET"
echo "  app name   $TITLE"
echo "  database   $DB_NAME"
echo "  ports      $APP_PORT-$((APP_PORT + 6))  (app on http://localhost:$APP_PORT)"
if [ -n "$(git -C "$TEMPLATE" status --porcelain)" ]; then
    echo
    echo "! The template has uncommitted changes. Only committed files are copied."
fi
echo
if [ "$YES" = 0 ]; then
    [ -t 0 ] || die "No terminal to confirm on. Re-run with --yes."
    read -rp "Proceed? [y/N] " reply
    [[ "$reply" =~ ^[Yy]$ ]] || { echo "Aborted."; exit 1; }
fi

# --- Copy. From here on a failure removes whatever this run created.

created=0 done=0
if [ ! -d "$DEST" ]; then mkdir -p "$DEST"; created=1; fi
cleanup() {
    [ "$done" = 1 ] && return
    echo "✖ Failed; removing the partial project at $DEST" >&2
    if [ "$created" = 1 ]; then rm -rf "$DEST"; else find "$DEST" -mindepth 1 -delete; fi
}
trap cleanup EXIT

# Committed files only: no .env, vendor/ or node_modules/, and .gitattributes
# export-ignore entries (the template's README and CHANGELOG) are left out.
git -C "$TEMPLATE" archive --format=tar HEAD | tar -x -C "$DEST"
cd "$DEST"

# --- composer package identity, in the same throwaway container ./keel uses
# for its bootstrap install, so the host needs no PHP (or Python).
composer_run() {
    docker run --rm \
        -u "$(id -u):$(id -g)" \
        -e HOME=/tmp -e COMPOSER_HOME=/tmp/composer \
        -v "$DEST":/var/www/html -w /var/www/html \
        "$BOOTSTRAP_IMAGE" composer "$@"
}
composer_run config name "$TARGET"
composer_run config description "A Laravel application."
# The package name is part of composer.lock's content-hash. Refresh the hash
# only -- no versions change -- or every install warns the lock is stale.
composer_run update --lock --no-install --no-scripts --no-plugins --no-audit \
    --ignore-platform-reqs --quiet

# --- app, database and tooling names
sed -i "s/^APP_NAME=.*/APP_NAME=\"$TITLE\"/"      .env.example
sed -i "s/^DB_DATABASE=.*/DB_DATABASE=$DB_NAME/"  .env.example
sed -i -E "s/(POSTGRES_DB|DB_DATABASE): keel$/\1: $DB_NAME/; s/-d keel\"/-d $DB_NAME\"/" \
    .github/workflows/tests.yml
sed -i "s/\"name\": \"Keel\"/\"name\": \"$TITLE\"/" .devcontainer/devcontainer.json
sed -i "s/(Keel)/($TITLE)/"                        .vscode/launch.json
sed -i "s|toHaveTitle(/Keel/)|toHaveTitle(/$TITLE/)|" tests/Browser/auth.spec.ts

# --- host ports. Two passes through placeholders, so a base that overlaps the
# old block (e.g. 8766) can't renumber a port twice.
if [ -n "$PORT_BASE" ]; then
    to_tmp="" from_tmp=""
    for i in 0 1 2 3 4 5 6; do
        to_tmp+="s/\\b$((8765 + i))\\b/@PORT$i@/g;"
        from_tmp+="s/@PORT$i@/$((PORT_BASE + i))/g;"
    done
    # Every 8765-8771 in .env.example is the host-port block and its comments.
    # The devcontainer only mirrors Vite's, which is the same inside and out.
    sed -i -E "$to_tmp$from_tmp" .env.example .devcontainer/devcontainer.json
fi

# --- strip template-only content
# docs/ is deliberately kept. A new project inherits the permission scoping,
# the tenant isolation and the bootstrap quirks, so it needs the reasoning too --
# stripping the ADRs would hand someone the non-obvious code with none of the
# explanation, which is the exact failure they exist to prevent.
rm -f CHANGELOG.md
rm -f database/seeders/DemoSeeder.php
if [ -f database/seeders/DatabaseSeeder.php ]; then
    # Only the demo call goes. RolesAndPermissionsSeeder is infrastructure:
    # without it a new project starts with no roles and no way into /admin.
    # BrowserTestUserSeeder stays too: ./keel e2e signs in as its users.
    # Its preceding blank line goes too, or Pint flags the gap before the brace.
    sed -i -z 's/\n\n[^\n]*DemoSeeder::class[^\n]*//' database/seeders/DatabaseSeeder.php
fi
# CONTRIBUTING.md stays (the workflow applies unchanged) but not the template's clone URL
sed -i "s|git clone https://github.com/[^ ]*/keel.git && cd keel|git clone <repo-url> \&\& cd $NAME|" \
    CONTRIBUTING.md

# README becomes the project's, not the template's
cat > README.md <<README
# $TITLE

## Requirements

Docker. That's it.

## Getting started

\`\`\`bash
./keel setup
\`\`\`

Then open http://localhost:$APP_PORT. Run \`./keel help\` for everything else,
or \`./keel doctor\` if something looks wrong.
README

git init -q -b main
git add -A
git -c user.name="$GIT_NAME" -c user.email="$GIT_EMAIL" \
    commit -q -m "Initial commit from Keel template"

done=1
echo
echo "✔ '$TITLE' is ready at $DEST"
echo "  Next: cd $DEST && ./keel setup"
