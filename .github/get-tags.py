#!/usr/bin/env python3

import re
import sys
from subprocess import check_output


Version = tuple[int, int, int]
VERSION_TAG_RE = re.compile(r"v(\d+)\.(\d+)\.(\d+)$")


def get_version_tags() -> list[Version]:
    tags = check_output(["git", "tag", "--list", "v*"], text=True).splitlines()
    versions = []
    for tag in tags:
        match = VERSION_TAG_RE.fullmatch(tag)
        if match is not None:
            versions.append(tuple(map(int, match.groups())))
    return versions


def print_release_tags(version: Version, versions: list[Version]) -> None:
    major, minor, patch = version
    if version == max(tag for tag in versions if tag[0] == major):
        print(major)
    if version == max(tag for tag in versions if tag[:2] == (major, minor)):
        print(f"{major}.{minor}")
    print(f"{major}.{minor}.{patch}")


# branch eg branch-2.12
branch = check_output(["git", "branch", "--show-current"], text=True).strip()
# tag eg v2.12.0-3-g1234567
describe = check_output(["git", "describe", "--tags"], text=True).strip()
tag = describe.split("-")[0]
match = VERSION_TAG_RE.fullmatch(tag)
if match is None:
    print(f"Expected a version tag, got {tag}", file=sys.stderr)
    sys.exit(1)
version = tuple(map(int, match.groups()))
versions = get_version_tags()

if branch == "main":
    print("dev")
elif "-" not in describe:
    print_release_tags(version, versions)
elif branch.startswith("branch-2."):
    a, b = branch.split("-")[1].split(".")
    if int(b) == max(tag[1] for tag in versions if tag[0] == int(a)):
        print(a)
    print(f"{a}.{b}")
    if branch == "branch-2.12":
        print("latest")
else:
    print(
        f"Only run from main, branch-2.X, or a tag (branch={branch}, describe={describe})",
        file=sys.stderr,
    )
    sys.exit(1)
