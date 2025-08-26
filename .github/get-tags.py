#!/usr/bin/env python3

from subprocess import check_output
import sys

branch = check_output(["git", "branch", "--show-current"], text=True).strip()
describe = check_output(["git", "describe", "--tags"], text=True).strip()
tag = describe.split("-")[0][1:]
a, b, c = tag.split(".")

if branch == "main":
    print("latest")
    print("dev")
elif "-" not in describe:
    print(f"{a}")
    print(f"{a}.{b}")
    print(f"{a}.{b}.{c}")
elif branch.startswith("branch-2."):
    print(f"{a}")
    print(f"{a}.{b}")
else:
    print(f"Only run from main, branch-2.X, or a tag (branch={branch}, describe={describe})", file=sys.stderr)
    sys.exit(1)
