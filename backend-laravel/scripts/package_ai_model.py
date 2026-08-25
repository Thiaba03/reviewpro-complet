import hashlib
import json
import shutil
import tarfile
from datetime import datetime, timezone
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
MODEL_DIR = ROOT / "storage/app/ai/models"
MODEL_PATH = MODEL_DIR / "review_topic_macro_svm.joblib"
METADATA_PATH = MODEL_DIR / "review_topic_macro_svm.metadata.json"
SERVICE_DIR = ROOT / "ai_service"
RUNTIME_REQUIREMENTS = ROOT / "requirements-ai-runtime.txt"
DIST_DIR = ROOT / "dist"
PACKAGE_NAME = "reviewpro-ai-model"
PACKAGE_DIR = DIST_DIR / PACKAGE_NAME
ARCHIVE_PATH = DIST_DIR / f"{PACKAGE_NAME}.tar.gz"


def sha256(path: Path) -> str:
    digest = hashlib.sha256()
    with path.open("rb") as file:
        for chunk in iter(lambda: file.read(1024 * 1024), b""):
            digest.update(chunk)
    return digest.hexdigest()


def require(path: Path):
    if not path.exists():
        raise FileNotFoundError(f"Fichier requis absent : {path}")


def main():
    for path in (MODEL_PATH, METADATA_PATH, SERVICE_DIR, RUNTIME_REQUIREMENTS):
        require(path)

    metadata = json.loads(METADATA_PATH.read_text(encoding="utf-8"))
    if metadata.get("model_name") != "review_topic_macro_svm":
        raise ValueError("Nom du modèle inattendu dans les métadonnées")

    DIST_DIR.mkdir(exist_ok=True)
    if PACKAGE_DIR.exists():
        shutil.rmtree(PACKAGE_DIR)
    if ARCHIVE_PATH.exists():
        ARCHIVE_PATH.unlink()

    (PACKAGE_DIR / "models").mkdir(parents=True)
    shutil.copytree(SERVICE_DIR, PACKAGE_DIR / "ai_service", ignore=shutil.ignore_patterns("__pycache__", "*.pyc"))
    shutil.copy2(MODEL_PATH, PACKAGE_DIR / "models" / MODEL_PATH.name)
    shutil.copy2(METADATA_PATH, PACKAGE_DIR / "models" / METADATA_PATH.name)
    shutil.copy2(RUNTIME_REQUIREMENTS, PACKAGE_DIR / RUNTIME_REQUIREMENTS.name)

    manifest = {
        "package_name": PACKAGE_NAME,
        "created_at": datetime.now(timezone.utc).isoformat(),
        "model_name": metadata["model_name"],
        "model_trained_at": metadata.get("trained_at"),
        "training_dataset_sha256": metadata.get("training_dataset_sha256"),
        "model_sha256": sha256(MODEL_PATH),
        "metadata_sha256": sha256(METADATA_PATH),
        "classes": metadata["classes"],
        "validation_accuracy": metadata["validation_accuracy"],
        "validation_macro_f1": metadata["validation_macro_f1"],
        "automatic_threshold": metadata["decision_policy"]["automatic_threshold"],
    }
    (PACKAGE_DIR / "manifest.json").write_text(
        json.dumps(manifest, indent=2, ensure_ascii=False) + "\n",
        encoding="utf-8",
    )

    with tarfile.open(ARCHIVE_PATH, "w:gz") as archive:
        archive.add(PACKAGE_DIR, arcname=PACKAGE_NAME)

    print("Paquet créé :", ARCHIVE_PATH)
    print("Empreinte du paquet :", sha256(ARCHIVE_PATH))
    print("Modèle :", manifest["model_name"])
    print("Accuracy validée :", manifest["validation_accuracy"])


if __name__ == "__main__":
    main()
