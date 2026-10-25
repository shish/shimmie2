#!/usr/bin/env python3

import re
import sys
from subprocess import check_output


Version = tuple[int, int, int]
VERSION_TAG_RE = re.compile(r"v(\d+)\.(\d+)\.(\d+)$")


def log(message: str) -> None:
    print(message, file=sys.stderr)


def parse_all_tags(all_tags_raw: list[str]) -> list[Version]:
    versions = []
    for tag in all_tags_raw:
        match = VERSION_TAG_RE.fullmatch(tag)
        if match is not None:
            versions.append(tuple(map(int, match.groups())))
    versions.sort()
    return versions


def parse_current_tag(current_tag_raw: str) -> Version | None:
    if "-" in current_tag_raw:
        return None
    match = VERSION_TAG_RE.fullmatch(current_tag_raw)
    if match is None:
        log(f"Expected a version tag, got {current_tag_raw}")
        sys.exit(1)
    return tuple(map(int, match.groups()))


def parse_all_branches(all_branches_raw: list[str]) -> list[str]:
    branches = []
    for b in all_branches_raw:
        if b.startswith("origin/branch-2."):
            parts = b.split("-")[-1].split(".")
            branches.append(tuple(map(int, parts)))
    branches.sort()
    return branches


def parse_current_branch(current_branch_raw: str) -> Version | None:
    if not current_branch_raw.startswith("origin/branch-2."):
        return None
    parts = current_branch_raw.split("/")[-1].split(".")
    return tuple(map(int, parts))


def get_release_tags(version: Version, versions: list[Version]) -> list[str]:
    tags = []
    major, minor, patch = version
    if version == max(tag for tag in versions if tag[0] == major):
        tags.append(major)
    if version == max(tag for tag in versions if tag[:2] == (major, minor)):
        tags.append(f"{major}.{minor}")
    tags.append(f"{major}.{minor}.{patch}")
    return tags


if __name__ == "__main__":
    all_tags_raw = check_output(["git", "tag", "--list", "v*"], text=True).splitlines()
    current_tag_raw = check_output(["git", "describe", "--tags"], text=True).strip()

    all_branches_raw = check_output(["git", "branch", "-a", "--list", "--format=%(refname:short)"], text=True).splitlines()
    current_branch_raw = check_output(["git", "branch", "--show-current"], text=True).strip()

    all_tags = parse_all_tags(all_tags_raw)
    current_tag = parse_current_tag(current_tag_raw)

    all_branches = parse_all_branches(all_branches_raw)
    current_branch = parse_current_branch(current_branch_raw)

    log(f"all_tags={", ".join([".".join(str(x) for x in p) for p in all_tags])}")
    log(f"{current_tag=}")
    log(f"all_branches={", ".join([".".join(str(x) for x in p) for p in all_branches])}")
    log(f"{current_branch=}")

    tags_for_docker = []
    if current_branch_raw == "main":
        tags_for_docker.append("dev")
    elif "-" not in current_tag_raw:
        tags_for_docker.extend(get_release_tags(current_tag, all_tags))
    elif current_branch_raw.startswith("branch-2."):
        a, b = current_branch_raw.split("-")[1].split(".")
        if int(b) == max(tag[1] for tag in versions if tag[0] == int(a)):
            tags_for_docker.append(a)
        tags_for_docker.append(f"{a}.{b}")
        latest_branch = branches[-1]
        if current_branch_raw == f"branch-{latest_branch[0]}.{latest_branch[1]}":
            tags_for_docker.append("latest")

    log(f"{tags_for_docker=}")

    if tags_for_docker:
        for version_tag in tags_for_docker:
            print(version_tag)
    else:
        log("No tags found")
        sys.exit(1)
