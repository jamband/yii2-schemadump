-- for some primary keys and unique keys
DROP TABLE IF EXISTS "0010_pk_ai";
CREATE TABLE "0010_pk_ai" (
    id serial not null primary key
);
DROP TABLE IF EXISTS "0020_pk_not_ai";
CREATE TABLE "0020_pk_not_ai" (
    id integer not null primary key
);
DROP TABLE IF EXISTS "0030_pk_bigint_ai";
CREATE TABLE "0030_pk_bigint_ai" (
    id bigserial not null primary key
);
DROP TABLE IF EXISTS "0040_composite_pks";
CREATE TABLE "0040_composite_pks" (
    foo_id integer not null,
    bar_id integer not null,
    PRIMARY KEY (foo_id, bar_id)
);
DROP TABLE IF EXISTS "0050_uks";
CREATE TABLE "0050_uks" (
    id serial not null primary key,
    username varchar(20) not null unique,
    email varchar(255) not null unique,
    password varchar(255) not null
);

-- for some types
DROP TABLE IF EXISTS "0100_types";
CREATE TABLE "0100_types" (
    "bool" bool not null default false,
    "boolean" boolean not null default false,
    "character" character(20) not null,
    "char" char(20) not null,
    "character_varying" character varying(20) not null,
    "varchar" varchar(20) not null,
    "text" text not null,
    "binary" bytea not null,
    "real" real not null,
    "decimal" decimal(20,10) not null,
    "numeric" numeric(20,10) not null,
    "money_decimal" decimal(19,4) not null,
    "money" money not null,
    "smallint" smallint not null,
    "integer" integer not null,
    "bigint" bigint not null,
    "date" date not null,
    "time" time not null,
    "timestamp" timestamp not null
);

-- for some default values
DROP TABLE IF EXISTS "0200_default_values";
CREATE TABLE "0200_default_values" (
    "integer" smallint not null default 1,
    "string" varchar not null default 'UNKNOWN'
);

-- for some comments
DROP TABLE IF EXISTS "0300_comment";
CREATE TABLE "0300_comment" (
    username varchar(20) not null
);
comment on column "0300_comment".username is 'ユーザ名';

-- for foreign keys
DROP TABLE IF EXISTS "0400_fk_parent";
CREATE TABLE "0400_fk_parent" (
    id serial not null primary key
);
DROP TABLE IF EXISTS "0410_fk_child";
CREATE TABLE "0410_fk_child" (
    id serial not null primary key,
    parent_id integer not null references "0400_fk_parent" (id)
);
