#!/bin/bash
# Legacy alias — runs the full Content Creators module repair.
# Run: curl -fsSL https://raw.githubusercontent.com/Kwameboat/dreamland/main/deploy/cpanel/fix-creator-view.sh | bash
exec bash -c "$(curl -fsSL "${DREAMLAND_GITHUB_RAW:-https://raw.githubusercontent.com/Kwameboat/dreamland/main}/deploy/cpanel/fix-content-creators.sh")"
