#!/usr/bin/env python3

import re
import sys
from subprocess import check_output


Version = tuple[int, int, int]
VERSION_TAG_RE = re.compile(r"v(\d+)\.(\d+)\.(\d+)$")
VERSION_BRANCH_RE = re.compile(r"(?:origin/)?branch-(\d+)\.(\d+)$")


def log(message: str) -> None:
    print(message, file=sys.stderr)


def parse_all_tags(all_tags_raw: list[str]) -> list[Version]:
    """Parse version tags from a list of git tag names.

    >>> parse_all_tags(['v1.0.0', 'v1.1.0', 'v2.0.0', 'other'])
    [(1, 0, 0), (1, 1, 0), (2, 0, 0)]

    >>> parse_all_tags(['v1.0.0'])
    [(1, 0, 0)]

    >>> parse_all_tags([])
    []
    """
    versions = []
    for tag in all_tags_raw:
        match = VERSION_TAG_RE.fullmatch(tag)
        if match is not None:
            versions.append(tuple(map(int, match.groups())))
    versions.sort()
    return versions


def parse_current_tag(current_tag_raw: str) -> Version | None:
    """Parse the current tag, returning None if it's a pre-release.

    >>> parse_current_tag('v1.2.3')
    (1, 2, 3)

    >>> parse_current_tag('v1.2.3-rc1') is None
    True
    """
    if "-" in current_tag_raw:
        return None
    match = VERSION_TAG_RE.fullmatch(current_tag_raw)
    if match is None:
        log(f"Expected a version tag, got {current_tag_raw}")
        sys.exit(1)
    return tuple(map(int, match.groups()))


def parse_all_branches(all_branches_raw: list[str]) -> list[tuple[int, int]]:
    """Parse version branch names from a list of git branches.

    >>> parse_all_branches(['origin/main', 'origin/branch-2.0', 'origin/branch-2.1'])
    [(2, 0), (2, 1)]

    >>> parse_all_branches(['origin/branch-2.1', 'origin/branch-2.10', 'origin/branch-2.2'])
    [(2, 1), (2, 2), (2, 10)]

    >>> parse_all_branches(['origin/main', 'origin/develop'])
    []
    """
    branches = []
    for b in all_branches_raw:
        match = VERSION_BRANCH_RE.fullmatch(b)
        if match is not None:
            branches.append(tuple(map(int, match.groups())))
    branches.sort()
    return branches


def parse_current_branch(current_branch_raw: str) -> tuple[int, int] | None:
    """Parse the current branch name, returning None if not a version branch.

    >>> parse_current_branch('origin/branch-2.0')
    (2, 0)

    >>> parse_current_branch('branch-2.1')
    (2, 1)

    >>> parse_current_branch('origin/main') is None
    True
    """
    match = VERSION_BRANCH_RE.fullmatch(current_branch_raw)
    if match is None:
        return None
    return tuple(map(int, match.groups()))


def get_release_tags(version: Version, versions: list[Version]) -> list[str]:
    """Get docker tags for a released version.

    If we're publishing the highest major.minor.patch version, we should
    also update the major and minor tags. If we are publishing the highest
    tag, we should also update the "latest" tag.

    >>> get_release_tags((2, 0, 0), [(1, 0, 0), (2, 0, 0)])
    ['2', '2.0', '2.0.0', 'latest']

    >>> get_release_tags((2, 1, 1), [(2, 0, 1), (2, 1, 0), (2, 1, 1)])
    ['2', '2.1', '2.1.1', 'latest']

    Pushing out a new patch release for an old minor version shouldn't
    update the major tags, or latest.

    >>> get_release_tags((2, 0, 1), [(2, 0, 1), (2, 1, 0), (2, 1, 1)])
    ['2.0', '2.0.1']

    Retroactively publishing an old patch release when we have newer
    patch versions shouldn't update the major or minor tags or latest.

    >>> get_release_tags((2, 0, 1), [(2, 0, 1), (2, 0, 2), (2, 1, 0)])
    ['2.0.1']
    """
    tags = []
    major, minor, patch = version
    if version == max(tag for tag in versions if tag[0] == major):
        tags.append(str(major))
    if version == max(tag for tag in versions if tag[:2] == (major, minor)):
        tags.append(f"{major}.{minor}")
    tags.append(f"{major}.{minor}.{patch}")
    if version >= max(versions):
        tags.append("latest")
    return tags


def get_tags_for_docker(
    all_tags_raw: list[str],
    current_tag_raw: str,
    all_branches_raw: list[str],
    current_branch_raw: str,
) -> list[str]:
    """
    Get the docker tags for the current release.

    New tagged release on an old branch, should update
    major + minor + patch

    >>> get_tags_for_docker(
    ...     ['v1.0.0', 'v1.1.0', 'v2.0.2'],
    ...     'v1.1.0',
    ...     ['main', 'branch-1.0', 'branch-1.1', 'branch-2.0'],
    ...     'branch-1.1')
    ['1', '1.1', '1.1.0']

    New non-tagged build on an old branch, should update
    major + minor

    >>> get_tags_for_docker(
    ...     ['v1.0.0', 'v1.1.0', 'v2.0.2'],
    ...     'v1.1-23-abc123',
    ...     ['main', 'branch-1.0', 'branch-1.1', 'branch-2.0'],
    ...     'branch-1.1')
    ['1', '1.1']

    New non-tagged build on an old minor, should update minor
    but not major

    >>> get_tags_for_docker(
    ...     ['v1.0.0', 'v1.1.0', 'v2.0.2'],
    ...     'v1.1-23-abc123',
    ...     ['main', 'branch-1.0', 'branch-1.1', 'branch-2.0'],
    ...     'branch-1.0')
    ['1.0']

    New tagged release on the highest branch, should update
    major + minor + patch + latest

    >>> get_tags_for_docker(
    ...     ['v1.0.0', 'v1.1.0', 'v2.0.2'],
    ...     'v2.0.2',
    ...     ['main', 'branch-1.0', 'branch-1.1', 'branch-2.0'],
    ...     'branch-2.0')
    ['2', '2.0', '2.0.2', 'latest']

    New build on the highest branch, but not a tagged release,
    should update major + minor + latest

    >>> get_tags_for_docker(
    ...     ['v1.0.0', 'v1.1.0', 'v2.0.2'],
    ...     'v2.0.2-23-abc123',
    ...     ['main', 'branch-1.0', 'branch-1.1', 'branch-2.0'],
    ...     'branch-2.0')
    ['2', '2.0', 'latest']

    New build on the main branch, should update dev

    >>> get_tags_for_docker(
    ...     ['v1.0.0', 'v1.1.0', 'v2.0.2'],
    ...     'v2.0.2-23-abc123',
    ...     ['main', 'branch-1.0', 'branch-1.1', 'branch-2.0'],
    ...     'main')
    ['dev']
    """
    all_tags = parse_all_tags(all_tags_raw)
    current_tag = parse_current_tag(current_tag_raw)

    all_branches = parse_all_branches(all_branches_raw)
    current_branch = parse_current_branch(current_branch_raw)

    tags_for_docker = []
    if current_branch_raw == "main":
        tags_for_docker.append("dev")
    elif current_tag:
        tags_for_docker.extend(get_release_tags(current_tag, all_tags))
    elif current_branch is not None:
        a, b = current_branch
        if b == max(tag[1] for tag in all_tags if tag[0] == a):
            tags_for_docker.append(str(a))
        tags_for_docker.append(f"{a}.{b}")
        if all_branches:
            latest_branch = all_branches[-1]
            if current_branch == latest_branch:
                tags_for_docker.append("latest")
    return tags_for_docker


if __name__ == "__main__":
    all_tags_raw = check_output(["git", "tag", "--list", "v*"], text=True).splitlines()
    current_tag_raw = check_output(["git", "describe", "--tags"], text=True).strip()

    all_branches_raw = check_output(["git", "branch", "-a", "--list", "--format=%(refname:short)"], text=True).splitlines()
    current_branch_raw = check_output(["git", "branch", "--show-current"], text=True).strip()

    tags_for_docker = get_tags_for_docker(
        all_tags_raw,
        current_tag_raw,
        all_branches_raw,
        current_branch_raw,
    )
    log(f"{tags_for_docker=}")

    if tags_for_docker:
        for version_tag in tags_for_docker:
            print(version_tag)
    else:
        log("No tags found")
        sys.exit(1)
