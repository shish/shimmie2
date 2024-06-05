#!/bin/sh

set -e

# if we aren't being given an explicit user ID, then default
# to having the same user as the code - this means we can
# bind-mount for the devcontainer and use the host's UIDs
if [ -z "$UID" ]; then
	UID=$(ls -n index.php | cut -d ' ' -f 3)
fi
if [ -z "$GID" ]; then
	GID=$(ls -n index.php | cut -d ' ' -f 4)
fi

# if user shimmie doesn't already exist, create it
if ! id -u shimmie >/dev/null 2>&1; then
    groupadd -g $GID shimmie || true
    useradd -ms /bin/bash -u $UID -g $GID shimmie || true
fi

exec "$@"
