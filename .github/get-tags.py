#!/usr/bin/env python3

from subprocess import check_output
import sys

branch = check_output(["git", "branch", "--show-current"], text=True).strip()
describe = check_output(["git", "describe", "--tags"], text=True).strip()
tag = describe.split("-")[0][1:]
a, b, c = tag.split(".")

if branch == "main":
    print("tags=latest")
elif branch.startswith("branch-2."):
    if "-" in describe:
        print(f"tags={a},{a}.{b}")
    else:
        print(f"tags={a},{a}.{b},{a}.{b}.{c}")
else:
    print("Only run from main or branch-2.X")
    sys.exit(1)
