from pathlib import Path

from pyspark.sql import SparkSession


INPUT_PATH = Path(
    "storage/app/bigdata/reviews_export.csv"
).resolve()

OUTPUT_ROOT = Path(
    "storage/app/bigdata/spark_outputs"
).resolve()


spark = (
    SparkSession.builder
    .appName("ReviewProBigDataAnalysis")
    .master("local[*]")
    .config("spark.sql.shuffle.partitions", "4")
    .getOrCreate()
)

spark.sparkContext.setLogLevel("ERROR")

print("Version Spark :", spark.version)
print("Fichier source :", INPUT_PATH)

reviews = (
    spark.read
    .option("header", True)
    .option("inferSchema", True)
    .option("multiLine", True)
    .option("escape", '"')
    .csv(str(INPUT_PATH))
)

reviews.createOrReplaceTempView("reviews")

total = reviews.count()
print("Nombre total de lignes :", total)

print("\nRÉPARTITION PAR SOURCE")
source_summary = spark.sql("""
    SELECT
        source,
        COUNT(*) AS total,
        ROUND(AVG(note), 2) AS note_moyenne
    FROM reviews
    GROUP BY source
    ORDER BY total DESC
""")
source_summary.show(truncate=False)

print("\nRÉPARTITION PAR SENTIMENT")
sentiment_summary = spark.sql("""
    SELECT
        sentiment,
        COUNT(*) AS total,
        ROUND(AVG(note), 2) AS note_moyenne
    FROM reviews
    WHERE sentiment IS NOT NULL
    GROUP BY sentiment
    ORDER BY total DESC
""")
sentiment_summary.show(truncate=False)

print("\nPRODUITS RECEVANT LE PLUS DE PLAINTES")
top_products = spark.sql("""
    SELECT
        brand_name,
        product_name,
        COUNT(*) AS avis_negatifs
    FROM reviews
    WHERE sentiment = 'negative'
      AND product_name IS NOT NULL
    GROUP BY brand_name, product_name
    ORDER BY avis_negatifs DESC
    LIMIT 10
""")
top_products.show(truncate=False)

print("\nCONTRÔLES DE QUALITÉ")
quality = spark.sql("""
    SELECT
        COUNT(*) AS lignes,
        SUM(CASE
            WHEN content IS NULL OR TRIM(content) = ''
            THEN 1 ELSE 0
        END) AS contenus_vides,
        SUM(CASE
            WHEN note IS NOT NULL
             AND (note < 1 OR note > 5)
            THEN 1 ELSE 0
        END) AS notes_invalides,
        SUM(CASE
            WHEN sentiment IS NOT NULL
             AND sentiment NOT IN (
                'positive',
                'neutral',
                'negative'
             )
            THEN 1 ELSE 0
        END) AS sentiments_invalides
    FROM reviews
""")
quality.show(truncate=False)

OUTPUT_ROOT.mkdir(parents=True, exist_ok=True)

reviews.write.mode("overwrite").parquet(
    str(OUTPUT_ROOT / "reviews_parquet")
)

source_summary.coalesce(1).write.mode("overwrite").option(
    "header",
    True,
).csv(str(OUTPUT_ROOT / "source_summary"))

sentiment_summary.coalesce(1).write.mode("overwrite").option(
    "header",
    True,
).csv(str(OUTPUT_ROOT / "sentiment_summary"))

top_products.coalesce(1).write.mode("overwrite").option(
    "header",
    True,
).csv(str(OUTPUT_ROOT / "top_complaint_products"))

parquet_count = spark.read.parquet(
    str(OUTPUT_ROOT / "reviews_parquet")
).count()

print("\nLignes relues depuis Parquet :", parquet_count)
print("Résultats enregistrés dans :", OUTPUT_ROOT)

spark.stop()