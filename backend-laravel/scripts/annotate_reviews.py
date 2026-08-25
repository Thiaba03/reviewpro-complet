import csv
import shutil
import textwrap
from datetime import datetime
from pathlib import Path


PROJECT_ROOT = Path(__file__).resolve().parents[1]

CSV_PATH = (
    PROJECT_ROOT
    / "storage/app/ai/negative_reviews_annotation_120_unique.csv"
)

BACKUP_DIRECTORY = PROJECT_ROOT / "storage/app/ai/backups"

TOPICS = [
    "battery_charging",
    "hardware_failure",
    "screen_display",
    "software_performance",
    "connectivity",
    "usability_setup",
    "support_warranty",
    "delivery_packaging",
    "price_value",
    "audio_quality",
    "compatibility",
    "out_of_scope",
    "other",
]


def load_csv():
    with CSV_PATH.open(
        "r",
        encoding="utf-8-sig",
        newline=""
    ) as source:
        reader = csv.DictReader(source)
        return list(reader), reader.fieldnames


def save_csv(rows, fieldnames):
    temporary_path = CSV_PATH.with_suffix(".tmp")

    with temporary_path.open(
        "w",
        encoding="utf-8",
        newline=""
    ) as destination:
        writer = csv.DictWriter(
            destination,
            fieldnames=fieldnames
        )
        writer.writeheader()
        writer.writerows(rows)

    temporary_path.replace(CSV_PATH)


def create_backup():
    BACKUP_DIRECTORY.mkdir(parents=True, exist_ok=True)

    timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    backup_path = (
        BACKUP_DIRECTORY
        / f"annotations_{timestamp}.csv"
    )

    shutil.copy2(CSV_PATH, backup_path)

    return backup_path


def display_topics():
    print("\nCATÉGORIES")

    for number, topic in enumerate(TOPICS, start=1):
        print(f"{number:2}. {topic}")


def choose_primary_topic():
    while True:
        value = input(
            "\nNuméro du thème principal "
            "[s = passer, q = quitter] : "
        ).strip().lower()

        if value in {"s", "q"}:
            return value

        if value.isdigit():
            number = int(value)

            if 1 <= number <= len(TOPICS):
                return TOPICS[number - 1]

        print("Choix invalide.")


def choose_secondary_topic(primary_topic):
    while True:
        value = input(
            "Numéro du thème secondaire "
            "[Entrée = aucun] : "
        ).strip()

        if value == "":
            return ""

        if value.isdigit():
            number = int(value)

            if 1 <= number <= len(TOPICS):
                topic = TOPICS[number - 1]

                if topic == primary_topic:
                    print(
                        "Le thème secondaire doit être "
                        "différent du thème principal."
                    )
                    continue

                return topic

        print("Choix invalide.")


def display_review(row, current, total):
    print("\n" + "=" * 80)
    print(f"AVIS À ANNOTER {current}/{total}")
    print("=" * 80)
    print("ID :", row["id"])
    print("Produit :", row["product_name"])
    print("Marque :", row["brand_name"])
    print("Langue :", row["language"])
    print("Note :", row["rating"])
    print("\nTexte :")
    print(textwrap.fill(row["content"], width=80))


def main():
    if not CSV_PATH.exists():
        print("Fichier introuvable :", CSV_PATH)
        return

    rows, fieldnames = load_csv()
    backup_path = create_backup()

    pending_rows = [
        row
        for row in rows
        if not row["primary_topic"].strip()
    ]

    print("Fichier :", CSV_PATH)
    print("Sauvegarde :", backup_path)
    print(
        "Déjà annotés :",
        len(rows) - len(pending_rows)
    )
    print("Restants :", len(pending_rows))

    display_topics()

    for position, row in enumerate(
        pending_rows,
        start=1
    ):
        display_review(
            row,
            position,
            len(pending_rows)
        )

        primary_topic = choose_primary_topic()

        if primary_topic == "q":
            print("\nAnnotation interrompue.")
            break

        if primary_topic == "s":
            print("Avis passé.")
            continue

        secondary_topic = choose_secondary_topic(
            primary_topic
        )

        comment = input(
            "Commentaire facultatif : "
        ).strip()

        print("\nANNOTATION PROPOSÉE")
        print("Principal :", primary_topic)
        print(
            "Secondaire :",
            secondary_topic or "aucun"
        )
        print("Commentaire :", comment or "aucun")

        confirmation = input(
            "Enregistrer ? [o = oui, n = non, q = quitter] : "
        ).strip().lower()

        if confirmation == "q":
            print("Annotation non enregistrée.")
            print("Annotation interrompue.")
            break

        if confirmation != "o":
            print("Annotation non enregistrée.")
            continue

        row["primary_topic"] = primary_topic
        row["secondary_topic"] = secondary_topic
        row["annotation_comment"] = comment

        save_csv(rows, fieldnames)

        annotated = sum(
            bool(item["primary_topic"].strip())
            for item in rows
        )

        print("Enregistré.")
        print(
            f"Progression : {annotated}/{len(rows)}"
        )

    remaining = sum(
        not row["primary_topic"].strip()
        for row in rows
    )

    print("\nRÉSUMÉ")
    print("Annotés :", len(rows) - remaining)
    print("Restants :", remaining)


if __name__ == "__main__":
    main()