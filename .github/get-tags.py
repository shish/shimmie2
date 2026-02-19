#!/usr/bin/env python3

import sys
from subprocess import check_output

# branch eg branch-2.12
branch = check_output(["git", "branch", "--show-current"], text=True).strip()
# tag eg v2.12.0-3-g1234567
describe = check_output(["git", "describe", "--tags"], text=True).strip()
tag = describe.split("-")[0][1:]

if branch == "main":
    print("dev")
elif "-" not in describe:
    a, b, c = tag.split(".")
    print(f"{a}")
    print(f"{a}.{b}")
    print(f"{a}.{b}.{c}")
elif branch.startswith("branch-2."):
    a, b = branch.split("-")[1].split(".")
    print(f"{a}")
    print(f"{a}.{b}")
    if branch == "branch-2.12":
        print("latest")
else:
    print(
        f"Only run from main, branch-2.X, or a tag (branch={branch}, describe={describe})",
        file=sys.stderr,
    )
    sys.exit(1)
