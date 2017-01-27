-- for some primary keys and unique keys
DROP TABLE IF EXISTS "0010_pk_ai";
CREATE TABLE "0010_pk_ai" (
    "id" INTEGER NOT NULL PRIMARY KEY
);
DROP TABLE IF EXISTS "0020_composite_pks";
CREATE TABLE "0020_composite_pks" (
  "foo_id" INTEGER NOT NULL,
  "bar_id"  INTEGER NOT NULL,
  PRIMARY KEY ("foo_id", "bar_id")
);
DROP TABLE IF EXISTS "0030_uks";
CREATE TABLE "0030_uks" (
    "id" INTEGER NOT NULL PRIMARY KEY,
    "username" TEXT NOT NULL UNIQUE,
    "email" TEXT NOT NULL UNIQUE,
    "password" TEXT NOT NULL
);

-- for some types
DROP TABLE IF EXISTS "0100_types";
CREATE TABLE "0100_types" (
    "integer" INTEGER NOT NULL,
    "real" REAL NOT NULL,
    "text" TEXT NOT NULL,
    "blob" BLOB NOT NULL
);

-- for some default values
DROP TABLE IF EXISTS "0200_default_values";
CREATE TABLE "0200_default_values" (
    "integer" INTEGER NOT NULL DEFAULT 1,
    "string" TEXT NOT NULL DEFAULT "UNKNOWN"
);

-- for foreign keys
DROP TABLE IF EXISTS "0300_fk_parent";
CREATE TABLE "0300_fk_parent" (
    "id" INTEGER NOT NULL PRIMARY KEY
);
DROP TABLE IF EXISTS "0310_fk_child";
CREATE TABLE "0310_fk_child" (
    "id" INTEGER NOT NULL PRIMARY KEY,
    "parent_id" INTEGER NOT NULL,
    FOREIGN KEY ("parent_id") REFERENCES "0300_fk_parent" ("id")
);
