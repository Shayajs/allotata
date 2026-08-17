#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
KEYSTORE="$ROOT/keystore/allotata-upload.jks"
PROPS="$ROOT/keystore/keystore.properties"

if [[ -f "$KEYSTORE" ]]; then
    echo "Keystore déjà présent : $KEYSTORE"
    exit 0
fi

PASSWORD="$(python3 - <<'PY'
import secrets
print(secrets.token_urlsafe(24))
PY
)"

keytool -genkeypair -v \
    -keystore "$KEYSTORE" \
    -storetype JKS \
    -keyalg RSA \
    -keysize 2048 \
    -validity 10000 \
    -alias allotata-upload \
    -storepass "$PASSWORD" \
    -keypass "$PASSWORD" \
    -dname "CN=Allo Tata, OU=Mobile, O=Allo Tata, L=Paris, ST=IDF, C=FR"

cat > "$PROPS" <<EOF
storePassword=$PASSWORD
keyPassword=$PASSWORD
keyAlias=allotata-upload
storeFile=allotata-upload.jks
EOF

chmod 600 "$KEYSTORE" "$PROPS"

echo "Keystore créé. SHA-256 :"
keytool -list -v -keystore "$KEYSTORE" -storepass "$PASSWORD" -alias allotata-upload | sed -n 's/^[[:space:]]*SHA256:[[:space:]]*//p'
echo "Mot de passe enregistré dans $PROPS (gitignored). Sauvegarde-le hors du repo."
