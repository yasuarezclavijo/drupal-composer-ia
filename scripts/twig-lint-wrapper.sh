#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

# Define paths to check for Twig files
declare -a paths=(
  "web/modules/custom"
  "web/themes/custom"
  "web/sites/*/modules/custom"
  "web/sites/*/themes/custom"
)

# Filter to only existing paths with Twig files
declare -a existing_paths=()
for path in "${paths[@]}"; do
  # Expand glob patterns
  for expanded_path in $path; do
    if [[ -d "$expanded_path" ]]; then
      # Check if directory has any Twig files
      if find "$expanded_path" -name "*.twig" 2>/dev/null | grep -q .; then
        existing_paths+=("$expanded_path")
      fi
    fi
  done
done

# If no Twig files found, exit gracefully
if [[ ${#existing_paths[@]} -eq 0 ]]; then
  echo "✓ No custom Twig templates found to validate"
  exit 0
fi

# Run TwigCS on existing paths only
vendor/bin/twigcs --display=severity "${existing_paths[@]}" "$@"
