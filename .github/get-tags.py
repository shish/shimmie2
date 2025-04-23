#!/usr/bin/env python3

from subprocess import check_output
import sys

branch = check_output(["git", "branch", "--show-current"], text=True).strip()
describe = check_output(["git", "describe", "--tags"], text=True).strip()
tag = describe.split("-")[0][1:]
a, b, c = tag.split(".")

if branch == "main":
    print("-t latest")
    print("-t dev")
elif branch.startswith("branch-2."):
    print(f"-t {a}")
    print(f"-t {a}.{b}")
    if "-" not in describe:
        print(f"-t {a}.{b}.{c}")
else:
    print("Only run from main or branch-2.X", file=sys.stderr)
    sys.exit(1)
