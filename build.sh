#!/bin/bash

# Core Forms Build Script
# Creates a clean zip file for distribution

set -e

# Configuration
PLUGIN_SLUG="core-forms"
VERSION=$(grep -m1 "Version:" core-forms.php | awk -F: '{print $2}' | tr -d ' ')
OUTPUT_DIR="/Users/gauravtiwari/Development/Antigravity"
BUILD_DIR="/tmp/${PLUGIN_SLUG}-build"
ZIP_NAME="${PLUGIN_SLUG}-${VERSION}.zip"

echo "🚀 Building ${PLUGIN_SLUG} v${VERSION}..."

# Clean up any previous build
rm -rf "${BUILD_DIR}"
mkdir -p "${BUILD_DIR}/${PLUGIN_SLUG}"

echo "📁 Copying files..."

# Copy all files except excluded ones
rsync -av --progress . "${BUILD_DIR}/${PLUGIN_SLUG}/" \
    --exclude='.git' \
    --exclude='.github' \
    --exclude='.gitignore' \
    --exclude='.DS_Store' \
    --exclude='node_modules' \
    --exclude='package.json' \
    --exclude='package-lock.json' \
    --exclude='composer.json' \
    --exclude='composer.lock' \
    --exclude='phpcs.xml' \
    --exclude='phpunit.xml' \
    --exclude='.phpcs.xml' \
    --exclude='.phpunit.xml' \
    --exclude='tests' \
    --exclude='*.log' \
    --exclude='*.map' \
    --exclude='.editorconfig' \
    --exclude='.eslintrc' \
    --exclude='.eslintrc.js' \
    --exclude='.stylelintrc' \
    --exclude='.prettierrc' \
    --exclude='Gruntfile.js' \
    --exclude='gulpfile.js' \
    --exclude='webpack.config.js' \
    --exclude='tsconfig.json' \
    --exclude='babel.config.js' \
    --exclude='.babelrc' \
    --exclude='build.sh' \
    --exclude='CLAUDE.md' \
    --exclude='INSTRUCTIONS.md' \
    --exclude='.claude' \
    --exclude='.agent' \
    --exclude='*.scss' \
    --exclude='*.ts' \
    --exclude='src/scss' \
    --exclude='assets/src' \
    --exclude='assets/*.map' \
    --exclude='vendor/bin' \
    --exclude='vendor/composer/installers' \
    --exclude='*.zip'

echo "🗜️  Creating zip file..."

# Navigate to build directory and create zip
cd "${BUILD_DIR}"
zip -r "${ZIP_NAME}" "${PLUGIN_SLUG}" -x "*.DS_Store" -x "*__MACOSX*"

# Move zip to output directory
mv "${ZIP_NAME}" "${OUTPUT_DIR}/"

# Clean up build directory
rm -rf "${BUILD_DIR}"

echo ""
echo "✅ Build complete!"
echo "📦 Output: ${OUTPUT_DIR}/${ZIP_NAME}"
echo ""

# Show file size
ls -lh "${OUTPUT_DIR}/${ZIP_NAME}"
