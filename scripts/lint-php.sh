#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

ensure_standards() {
  local drupal_sniff="vendor/drupal/coder/coder_sniffer"

  # Only set if the path exists
  if [[ -d "$drupal_sniff" ]]; then
    vendor/bin/phpcs --config-set installed_paths "$drupal_sniff" >/dev/null 2>&1 || true
  fi
}

lint_default() {
  # Build paths array dynamically to avoid glob expansion errors
  local paths=("web/modules/custom" "web/themes/custom" "web/profiles/custom")
  local found_paths=()

  # Add multisite paths if they exist
  if [[ -d "web/sites" ]]; then
    for site_dir in web/sites/*/; do
      [[ -d "${site_dir}modules/custom" ]] && paths+=("${site_dir}modules/custom")
      [[ -d "${site_dir}themes/custom" ]] && paths+=("${site_dir}themes/custom")
    done
  fi

  # Filter to directories that exist (skip file check for speed)
  for path in "${paths[@]}"; do
    if [[ -d "$path" ]] && [[ "$(find "$path" -type f \( -name "*.php" -o -name "*.inc" -o -name "*.module" -o -name "*.install" \) 2>/dev/null | wc -l)" -gt 0 ]]; then
      found_paths+=("$path")
    fi
  done

  # If no PHP files found, exit gracefully
  if [[ ${#found_paths[@]} -eq 0 ]]; then
    echo "✓ No custom PHP files found to validate (web/modules/custom, web/themes/custom, web/profiles/custom)"
    return 0
  fi

  # Validate found paths
  vendor/bin/phpcs --standard=./phpcs.xml --report=full --colors "${found_paths[@]}"
}

ensure_standards

if [[ $# -gt 0 ]]; then
  vendor/bin/phpcs --standard=./phpcs.xml --report=full --colors "$@"
else
  lint_default
fi
