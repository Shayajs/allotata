#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
ANDROID_HOME="${ANDROID_HOME:-${ANDROID_SDK_ROOT:-$HOME/Android/Sdk}}"
if [[ -z "${JAVA_HOME:-}" || ! -x "${JAVA_HOME}/bin/java" ]]; then
    if [[ -x "$HOME/jdk/jdk-21/bin/java" ]]; then
        JAVA_HOME="$HOME/jdk/jdk-21"
    fi
fi
export JAVA_HOME
export ANDROID_HOME ANDROID_SDK_ROOT="$ANDROID_HOME"
export PATH="${JAVA_HOME:+$JAVA_HOME/bin:}$ANDROID_HOME/platform-tools:$ANDROID_HOME/cmdline-tools/latest/bin:$PATH"

if [[ ! -f "$ROOT/keystore/keystore.properties" ]]; then
    echo "Manque mobile/keystore/keystore.properties (copie depuis keystore.properties.example)." >&2
    exit 1
fi

if [[ ! -d "$ROOT/android" ]]; then
    echo "Projet Android absent. Depuis mobile/ : npm install && npx cap add android && npx cap sync" >&2
    exit 1
fi

MODE="${1:-all}"
case "$MODE" in
    apk|aab|all) ;;
    *)
        echo "Usage: $0 [apk|aab|all]" >&2
        exit 1
        ;;
esac

VERSION_FILE="$ROOT/version.properties"
major=1
minor=0
patch=0
versionCode=0
lastMajor=""
lastMinor=""

if [[ -f "$VERSION_FILE" ]]; then
    while IFS='=' read -r key value; do
        key="${key%%[[:space:]]*}"
        value="${value%%[[:space:]]*}"
        value="${value#"${value%%[![:space:]]*}"}"
        case "$key" in
            major) major="$value" ;;
            minor) minor="$value" ;;
            patch) patch="$value" ;;
            versionCode) versionCode="$value" ;;
            lastMajor) lastMajor="$value" ;;
            lastMinor) lastMinor="$value" ;;
        esac
    done < <(grep -E '^(major|minor|patch|versionCode|lastMajor|lastMinor)=' "$VERSION_FILE" || true)
fi

major="${ALLOTATA_VERSION_MAJOR:-$major}"
minor="${ALLOTATA_VERSION_MINOR:-$minor}"

if [[ "$major" != "$lastMajor" || "$minor" != "$lastMinor" ]]; then
    patch=1
else
    patch=$((patch + 1))
fi
versionCode=$((versionCode + 1))
versionName="${major}.${minor}.${patch}"

cat > "$VERSION_FILE" <<EOF
# Version Android Allo Tata : X.Y.Z
# Tu changes uniquement major (X) et minor (Y).
# patch (Z) et versionCode sont incrémentés à chaque compilation.

major=${major}
minor=${minor}
patch=${patch}
versionCode=${versionCode}
versionName=${versionName}
lastMajor=${major}
lastMinor=${minor}
EOF

echo "Version : ${versionName} (versionCode ${versionCode})"

cd "$ROOT"
if [[ -f "$ROOT/pocket/package.json" ]] || [[ -f "$ROOT/pocket/vite.config.js" ]]; then
    if [[ ! -d "$ROOT/node_modules/vite" ]]; then
        npm install
    fi
    VITE_POCKET_ENV=prod npm run pocket:build:prod
fi
npx cap sync android

cd "$ROOT/android"
chmod +x ./gradlew

GRADLE_TASKS=()
[[ "$MODE" == "apk" || "$MODE" == "all" ]] && GRADLE_TASKS+=(assembleRelease)
[[ "$MODE" == "aab" || "$MODE" == "all" ]] && GRADLE_TASKS+=(bundleRelease)
./gradlew "${GRADLE_TASKS[@]}" \
    -PallotataVersionName="$versionName" \
    -PallotataVersionCode="$versionCode"

mkdir -p "$ROOT/dist"
PUBLIC_DOWNLOADS="$(cd "$ROOT/.." && pwd)/public/downloads"
mkdir -p "$PUBLIC_DOWNLOADS"

if [[ "$MODE" == "apk" || "$MODE" == "all" ]]; then
    APK="$(find "$ROOT/android/app/build/outputs/apk/release" -name '*.apk' | head -n 1)"
    if [[ -z "$APK" ]]; then
        echo "APK introuvable." >&2
        exit 1
    fi
    cp "$APK" "$ROOT/dist/AlloTata.apk"
    cp "$APK" "$PUBLIC_DOWNLOADS/AlloTata.apk"
    echo "APK : $ROOT/dist/AlloTata.apk ($versionName)"
    echo "Copie site : $PUBLIC_DOWNLOADS/AlloTata.apk"
fi

if [[ "$MODE" == "aab" || "$MODE" == "all" ]]; then
    AAB="$(find "$ROOT/android/app/build/outputs/bundle/release" -name '*.aab' | head -n 1)"
    if [[ -z "$AAB" ]]; then
        echo "AAB introuvable." >&2
        exit 1
    fi
    cp "$AAB" "$ROOT/dist/AlloTata.aab"
    echo "AAB : $ROOT/dist/AlloTata.aab ($versionName)"
fi
