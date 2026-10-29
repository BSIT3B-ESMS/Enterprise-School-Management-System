#!/usr/bin/env bash
# Pull the latest changes from each original repository into its apps/ folder.
# Usage: scripts/sync.sh [ems|tap-pms|admission|document-management]   (no argument = all)
set -euo pipefail
cd "$(git rev-parse --show-toplevel)"

declare -A REPOS=(
  [ems]="https://github.com/yumulniccolo/EMS.git main"
  [tap-pms]="https://github.com/JD-Slayin/TAP-PMS.git main"
  [admission]="https://github.com/hanimehub06-eng/Admission_System.git main"
  [document-management]="https://github.com/AAAAA13-sys/school-document-management.git master"
)

if [[ -n "$(git status --porcelain)" ]]; then
  echo "Working tree is not clean; commit or stash first." >&2
  exit 1
fi

targets=("${@:-${!REPOS[@]}}")
for app in "${targets[@]}"; do
  [[ -n "${REPOS[$app]:-}" ]] || { echo "Unknown app: $app" >&2; exit 1; }
  read -r url branch <<<"${REPOS[$app]}"
  echo "==> Syncing apps/$app from $url ($branch)"
  git subtree pull --prefix="apps/$app" "$url" "$branch" -m "Sync apps/$app from upstream $branch"
done
