#!/bin/bash
# Silly Release Script for a simple "git pull" repository with submodules.
# This script is supposed to be executed from Toolforge.
# Author: [[User:Valerio Bozzolan]] (August 2026)
# License: CC Zero

set -e

CURRENT_DIR=$(dirname -- "${BASH_SOURCE[0]}")
PARENT_DIR="$CURRENT_DIR/../"

REPOSITORY_PATH=${REPOSITORY_PATH:-"$PARENT_DIR"}

repository_commit_url_base=https://gitlab.wikimedia.org/valeriobozzolan/itwiki-deletions-info-bot/-/commit/

# Backup uncommitted local changes.
cd "$REPOSITORY_PATH"
git stash save > /dev/null || true

# Backup uncommitted local changes here too.
cd includes/boz-mw
git stash save > /dev/null || true
cd - > /dev/null

before_commit=$(git rev-parse HEAD)

git pull > /dev/null
git submodule update --init --recursive

current_commit_hash=$(git rev-parse HEAD)

# No changes? Stop here.
if [[ "$before_commit" = "$current_commit_hash" ]]; then
	exit 0
fi

dologmsg "deletionbot: released! Up to commit $repository_commit_url_base$current_commit_hash"

echo "Released! I've also updated the Server Admin Log: https://wikitech.wikimedia.org/wiki/Nova_Resource:Tools.itwiki/SAL"
