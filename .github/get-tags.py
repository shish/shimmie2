#!/usr/bin/env python3

from subprocess import check_output
import sys

branch = check_output(["git", "branch", "--show-current"], text=True).strip()
describe = check_output(["git", "describe", "--tags"], text=True).strip()
tag = describe.split("-")[0][1:]
a, b, c = tag.split(".")
docker_username = sys.argv[1]
docker_image = sys.argv[2] if len(sys.argv) > 2 else "shimmie2"
image_name = f"{docker_username}/{docker_image}"

if branch == "main":
    print(f"tags={image_name}:latest")
elif branch.startswith("branch-2."):
    if "-" in describe:
        print(f"tags={image_name}:{a},{image_name}:{a}.{b}")
    else:
        print(f"tags={image_name}:{a},{image_name}:{a}.{b},{image_name}:{a}.{b}.{c}")
else:
    print("Only run from main or branch-2.X")
    sys.exit(1)
