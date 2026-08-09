# karhu-db — Code Tour

> A **reading-guide map**, and a short **reference appendix** — outside the ten-tour sequence. Read [karhu](../karhu/CODE-TOUR.md) first; this is one of its companion packages. Two classes, ~270 lines, and a clear thesis: **every query is a prepared statement, without exception.**
>
> **How to use it:** §1 why this package exists at all; §2 `Connection`; §3 `TableBase`; §4 exercises. Twenty minutes.

---

## 1. Orientation — a rewrite with a motive

The karhu tour noted that karhu descends from the older `chukwu`/`Peopsquik` front controllers. karhu-db is the same lineage, but here the ancestry is **named as a defect being fixed**. Both class docblocks say so outright: `Connection` "fixes the SQL-injection vulnerabilities in Peopsquik's `Core_DB` (`getBy`, `autoexecute`, `_createSql`)", and `TableBase` records that "the original had SQL-injection bugs in `getBy()` and `autoexecute()` via string interpolation."

**That's the whole reason to read this package.** It's small enough to audit in one sitting, and it's a worked example of retiring a class of bug by construction rather than by discipline — you can't forget to parameterise if the API never accepts a raw string in a value position.

karhu core has **no database opinion at all**. This is an opt-in package, which is why an app like istrbuddy can use SQLite through it and mishka can use PostgreSQL through the same interface.

---

## 2. `Connection` — the thin wrapper

[src/Connection.php](src/Connection.php) (158 lines). A deliberately small surface over PDO:

| Method | Shape |
|---|---|
| [`fetchAll`](src/Connection.php#L48) / [`fetchOne`](src/Connection.php#L61) / [`fetchScalar`](src/Connection.php#L73) | read: SQL + params → rows / row / single value |
| [`run`](src/Connection.php#L85) | write: SQL + params → affected-row count |
| [`insert`](src/Connection.php#L96) / [`update`](src/Connection.php#L114) / [`delete`](src/Connection.php#L139) | table + associative data → built, parameterised SQL |
| [`pdo`](src/Connection.php#L37) | the escape hatch, when you genuinely need raw PDO |

**The pattern to notice:** every method takes `(string $sql, array $params)` and funnels into `prepare()` + `execute($params)` ([:154](src/Connection.php#L154)). Values never reach the SQL string. The builder methods (`insert`/`update`/`delete`) interpolate **column names** — which come from your code — while every **value** goes through a placeholder.

**Gotcha worth internalising:** that split is the entire safety argument, and it means column names are *not* safe to take from user input. The API protects values, not identifiers. If you ever build a "sort by ?column" feature on top of this, the allowlist is yours to write.

`pdo()` is not a smell — a thin wrapper should provide an exit. What matters is that the *easy* path is the safe one.

---

## 3. `TableBase` — active record, minimal

[src/TableBase.php](src/TableBase.php) (114 lines). Subclass, set two properties, get seven methods:

```php
final class UserTable extends TableBase {
    protected string $table = 'users';
    protected string $primaryKey = 'id';
}
```

[`getAll`](src/TableBase.php#L34) · [`get`](src/TableBase.php#L44) · [`getBy`](src/TableBase.php#L58) · [`create`](src/TableBase.php#L72) · [`update`](src/TableBase.php#L83) · [`delete`](src/TableBase.php#L93) · [`count`](src/TableBase.php#L103).

`getBy(array $conditions)` ([:58](src/TableBase.php#L58)) is the one to read closely — it's the method named in the docblock as the original injection site. Compare what it does now with what an interpolating version would have done.

**Why this is "active-record base" and not an ORM:** there is no identity map, no lazy loading, no relations, no migrations, no change tracking. It's a convenience layer over `Connection` for the single-table CRUD case. mishka doesn't even use it — it writes repositories over `Connection` directly (see the [mishka tour](../mishka/CODE-TOUR.md) §6), which tells you how optional this half of the package is.

---

## 4. Active-recall exercises

1. **Write the injectable version of `getBy()`** from memory — the one the docblock says existed — then state exactly which input reaches which part of the SQL string.
2. **`insert()` interpolates column names but parameterises values.** Construct an input that is dangerous under this API, and say who is responsible for stopping it.
3. **mishka uses `Connection` but not `TableBase`.** Read one mishka repository and argue why. What would `TableBase` have cost there?
4. **`pdo()` returns the raw handle.** Give one legitimate use, and one that should have been a new method here instead.

---

*Tour covers karhu-db @ `81032ec`. A reference appendix — the ten-tour sequence ends at [koda-blast](../koda-blast/CODE-TOUR.md). Engine: [karhu](../karhu/CODE-TOUR.md). Consumers: [mishka](../mishka/CODE-TOUR.md), [istrbuddy](../istrbuddy/CODE-TOUR.md).*
