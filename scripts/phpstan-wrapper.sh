#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

# Define default paths to analyze
declare -a paths=(
  "web/modules/custom"
  "web/themes/custom"
  "web/profiles/custom"
)

# Filter to only existing paths
declare -a existing_paths=()
for path in "${paths[@]}"; do
  if [[ -d "$path" ]]; then
    # Check if directory has any PHP files
    if find "$path" -name "*.php" -o -name "*.module" -o -name "*.theme" -o -name "*.install" 2>/dev/null | grep -q .; then
      existing_paths+=("$path")
    fi
  fi
done

# If no PHP files found, exit gracefully
if [[ ${#existing_paths[@]} -eq 0 ]]; then
  echo "✓ No custom PHP code found to analyze (web/modules/custom, web/themes/custom, web/profiles/custom)"
  exit 0
fi

# Run PHPStan on existing paths only
vendor/bin/phpstan analyse "${existing_paths[@]}" "$@"
