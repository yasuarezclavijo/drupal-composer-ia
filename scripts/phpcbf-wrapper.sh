#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

# Define paths to fix
declare -a paths=(
  "web/modules/custom"
  "web/themes/custom"
  "web/profiles/custom"
)

# Add multisite paths if they exist
if [[ -d "web/sites" ]]; then
  for site_dir in web/sites/*/; do
    [[ -d "${site_dir}modules/custom" ]] && paths+=("${site_dir}modules/custom")
    [[ -d "${site_dir}themes/custom" ]] && paths+=("${site_dir}themes/custom")
  done
fi

# Filter to only existing paths with PHP files
declare -a existing_paths=()
for path in "${paths[@]}"; do
  if [[ -d "$path" ]]; then
    # Check if directory has any PHP files
    if find "$path" -name "*.php" -o -name "*.inc" -o -name "*.module" -o -name "*.install" 2>/dev/null | grep -q .; then
      existing_paths+=("$path")
    fi
  fi
done

# If no PHP files found, exit gracefully
if [[ ${#existing_paths[@]} -eq 0 ]]; then
  echo "✓ No custom PHP files found to fix (web/modules/custom, web/themes/custom, web/profiles/custom)"
  exit 0
fi

# Run PHPCBF on existing paths only
vendor/bin/phpcbf --standard=./phpcs.xml --report=full --colors "${existing_paths[@]}" "$@"
