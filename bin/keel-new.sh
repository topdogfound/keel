#!/usr/bin/env bash
# ./keel new <vendor/name> — turn this template into a fresh project.
# Renames the composer package, resets identity, strips demo content and
# re-initialises git history. Without this, every new project starts by
# hand-editing the same dozen files.
set -euo pipefail

TARGET="${1:?Usage: ./keel new <vendor/name>}"
[[ "$TARGET" =~ ^[a-z0-9]([a-z0-9._-]*)/[a-z0-9]([a-z0-9._-]*)$ ]] \
    || { echo "Expected a composer package name like 'acme/widgets', got '$TARGET'" >&2; exit 1; }

VENDOR="${TARGET%%/*}"
NAME="${TARGET##*/}"
# acme-widgets -> AcmeWidgets, for the PHP namespace
STUDLY="$(echo "$NAME" | sed -E 's/[-_]+([a-z0-9])/\U\1/g; s/^([a-z])/\U\1/')"
TITLE="$(echo "$NAME" | sed -E 's/[-_]+/ /g; s/\b(.)/\U\1/g')"

echo "Re-keying this template:"
echo "  package    keel            -> $TARGET"
echo "  app name   Keel            -> $TITLE"
echo "  namespace  App\\            -> App\\   (unchanged; Laravel convention)"
echo
read -rp "Proceed? This rewrites files and deletes git history. [y/N] " reply
[[ "$reply" =~ ^[Yy]$ ]] || { echo "Aborted."; exit 1; }

# composer package identity
python3 - "$TARGET" <<'PY'
import json, sys
p = 'composer.json'
d = json.load(open(p))
d['name'] = sys.argv[1]
d['description'] = 'A Laravel application.'
json.dump(d, open(p, 'w'), indent=4)
open(p, 'a').write('\n')
PY

# app identity
sed -i "s/^APP_NAME=.*/APP_NAME=\"$TITLE\"/" .env.example
[ -f .env ] && sed -i "s/^APP_NAME=.*/APP_NAME=\"$TITLE\"/" .env

# strip template-only content
rm -rf docs/adr CHANGELOG.md
rm -f database/seeders/DemoSeeder.php
if [ -f database/seeders/DatabaseSeeder.php ]; then
    # Only the demo call goes. RolesAndPermissionsSeeder is infrastructure:
    # without it a new project starts with no roles and no way into /admin.
    sed -i '/DemoSeeder::class/d' database/seeders/DatabaseSeeder.php
fi

# README becomes the project's, not the template's
cat > README.md <<README
# $TITLE

## Requirements

Docker. That's it.

## Getting started

\`\`\`bash
./keel setup
\`\`\`

Then open http://localhost. Run \`./keel help\` for everything else,
or \`./keel doctor\` if something looks wrong.
README

rm -rf .git
git init -q -b main
git add -A
git commit -q -m "Initial commit from Keel template"

echo
echo "Done. '$TITLE' is ready — run ./keel setup to bring it up."
