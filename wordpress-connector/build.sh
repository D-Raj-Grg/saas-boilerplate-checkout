#!/bin/bash

# SureCRM Plugin Build Script
# This script creates a production-ready ZIP file for distribution

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Plugin details
PLUGIN_SLUG="surecrm"
PLUGIN_NAME="SureCRM"
DIST_DIR="dist"

echo -e "${GREEN}Building ${PLUGIN_NAME} Plugin...${NC}"

# Check if we're in the plugin directory
if [ ! -f "surecrm.php" ]; then
    echo -e "${RED}Error: surecrm.php not found. Please run this script from the plugin root directory.${NC}"
    exit 1
fi

# Parse arguments
BUILD_ASSETS=false
for arg in "$@"; do
    case $arg in
        --build-assets)
            BUILD_ASSETS=true
            shift
            ;;
        --help)
            echo "Usage: ./build.sh [options]"
            echo "Options:"
            echo "  --build-assets    Build JavaScript and CSS assets before packaging"
            echo "  --help           Show this help message"
            exit 0
            ;;
    esac
done

# Create dist directory
mkdir -p "$DIST_DIR"

# Build production assets if requested
if [ "$BUILD_ASSETS" = true ]; then
    echo -e "${YELLOW}Building production assets...${NC}"
    if [ -f "package.json" ] && command -v npm &> /dev/null; then
        echo "Installing dependencies..."
        npm install
        
        echo "Building assets..."
        npm run build
    else
        echo -e "${RED}Cannot build assets: package.json not found or npm not installed${NC}"
        exit 1
    fi
fi

# Get version from main plugin file
VERSION=$(grep -i "Version:" surecrm.php | sed 's/.*Version: *//' | sed 's/ *$//')
VERSION=${VERSION:-"1.0.0"}
echo -e "${GREEN}Version: $VERSION${NC}"

# Create temporary directory for build
echo -e "${YELLOW}Preparing files...${NC}"
TEMP_DIR=$(mktemp -d)
BUILD_DIR="$TEMP_DIR/$PLUGIN_SLUG"
mkdir -p "$BUILD_DIR"

# Copy only essential files and directories
echo -e "${YELLOW}Copying plugin files...${NC}"
cp -R includes "$BUILD_DIR/"
cp -R assets "$BUILD_DIR/"
[ -d "languages" ] && cp -R languages "$BUILD_DIR/"
cp surecrm.php "$BUILD_DIR/"
[ -f "uninstall.php" ] && cp uninstall.php "$BUILD_DIR/"
[ -f "readme.txt" ] && cp readme.txt "$BUILD_DIR/"
[ -f "README.md" ] && cp README.md "$BUILD_DIR/"

# Clean up development files
echo -e "${YELLOW}Cleaning up...${NC}"

# Remove development files that might have been copied
rm -rf "$BUILD_DIR/src"
rm -rf "$BUILD_DIR/tests"
rm -rf "$BUILD_DIR/node_modules"
rm -f "$BUILD_DIR/package.json"
rm -f "$BUILD_DIR/package-lock.json"
rm -f "$BUILD_DIR/composer.json"
rm -f "$BUILD_DIR/composer.lock"
rm -f "$BUILD_DIR/webpack.config.js"
rm -f "$BUILD_DIR/tailwind.config.js"
rm -f "$BUILD_DIR/postcss.config.js"
rm -f "$BUILD_DIR/build.sh"
rm -f "$BUILD_DIR/.distignore"
rm -rf "$BUILD_DIR/.git"
rm -rf "$BUILD_DIR/.github"
rm -f "$BUILD_DIR/.gitignore"

# Remove map files (optional - comment out if you want to keep for debugging)
find "$BUILD_DIR" -name "*.map" -type f -delete 2>/dev/null || true

# Create ZIP file
ZIP_NAME="${PLUGIN_SLUG}-v${VERSION}.zip"
echo -e "${YELLOW}Creating ZIP file...${NC}"
cd "$TEMP_DIR"
zip -r "$ZIP_NAME" "$PLUGIN_SLUG" -q

# Move ZIP to dist directory
mv "$ZIP_NAME" "$OLDPWD/$DIST_DIR/"
cp "$OLDPWD/$DIST_DIR/$ZIP_NAME" "$OLDPWD/$DIST_DIR/${PLUGIN_SLUG}.zip"

# Clean up temporary directory
cd "$OLDPWD"
rm -rf "$TEMP_DIR"

# Calculate file size
if [ -f "$DIST_DIR/$ZIP_NAME" ]; then
    SIZE=$(du -h "$DIST_DIR/$ZIP_NAME" | cut -f1)
    echo -e "${GREEN}✓ Build complete!${NC}"
    echo -e "${GREEN}✓ Plugin ZIP created: $DIST_DIR/$ZIP_NAME (Size: $SIZE)${NC}"
    echo -e "${GREEN}✓ Latest version: $DIST_DIR/${PLUGIN_SLUG}.zip${NC}"
    echo ""
    echo -e "${GREEN}The plugin is ready for distribution!${NC}"
    echo ""
    echo "To build with fresh assets, run: ${YELLOW}./build.sh --build-assets${NC}"
else
    echo -e "${RED}✗ Build failed! ZIP file not created.${NC}"
    exit 1
fi