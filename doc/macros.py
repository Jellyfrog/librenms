"""Macros for the LibreNMS documentation (Zensical macros plugin)."""

import re
from pathlib import Path

OS_DETECTION_DIR = (
    Path(__file__).resolve().parent.parent / "resources/definitions/os_detection"
)


def define_env(env):
    @env.macro
    def supported_vendors():
        """Markdown list of OS display names, grouped by first letter."""
        names = {}
        for file in OS_DETECTION_DIR.glob("*.yaml"):
            for line in file.read_text(encoding="utf-8").splitlines():
                if match := re.match(r"^text: *[\"']?([^\"']+)", line):
                    name = match.group(1).strip()
                    names.setdefault(name.casefold(), name)

        lines = []
        letter = None
        for name in sorted(names.values(), key=str.casefold):
            if name[0].lower() != letter:
                letter = name[0].lower()
                lines += ["", f"### {letter.upper()}"]
            lines.append(f"* {name}")

        return "\n".join(lines)
