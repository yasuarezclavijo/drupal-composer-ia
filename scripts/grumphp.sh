#!/bin/bash
set -euo pipefail

if command -v ddev >/dev/null 2>&1; then
  ddev php "$@"
else
  php "$@"
fi
