"""Build an installable Wave plugin ZIP without application or repository files."""
import json
from pathlib import Path
from zipfile import ZipFile, ZIP_DEFLATED

root = Path(__file__).resolve().parents[1]
version = json.loads((root / "version.json").read_text())["version"]
output = root / "dist" / f"notification-composer-{version}.zip"
output.parent.mkdir(exist_ok=True)
allowed = ["NotificationComposerPlugin.php", "FilamentPlugin.php", "version.json", "plugin.jpg", "README.md", "LICENSE.md", "CHANGELOG.md", "config", "database", "resources", "routes", "src"]
with ZipFile(output, "w", ZIP_DEFLATED) as archive:
    for entry in allowed:
        path = root / entry
        for file in sorted(path.rglob("*")) if path.is_dir() else [path]:
            if file.is_file():
                archive.write(file, "resources/plugins/notification-composer/" + file.relative_to(root).as_posix())
print(output)
