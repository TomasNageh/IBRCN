---
## 14) OO complexity metrics (plain language)

| Name | One-line meaning | Short formula idea |
|------|------------------|---------------------|
| **WMC** | Add up how “heavy” each method is (each `if` adds complexity) | Sum of \(1 +\) number of decisions per method |
| **DIT** | Inheritance depth: how many `extends` levels above this class? | Here about **1** (no long inheritance chains) |
| **NOC** | How many classes **directly extend** this class? | Here **0** for most app classes |
| **CBO** | How many other classes does this class depend on? (`new`, types, calls) | Count distinct other types used |
| **RFC** | How many different methods can this class “trigger” (own + called) | Rough manual count |
| **LCOM** | Do methods share the same fields, or feel unrelated? | Higher value often means weaker cohesion |

**Small example table (from this project):**

| Class | WMC (approx.) | DIT | NOC |
|-------|---------------|-----|-----|
| CartService | ~24 | 1 | 0 |
| RecommendationEngine | ~8 | 1 | 0 |
| EventPublisher | ~6 | 1 | 0 |

*(More detail lives in the code under `app/`.)*

---

## 15) Unit testing (simple)

**Idea:** Test **one function at a time**: give **inputs**, check **outputs**, without running the whole website.

**We test 2 functions :**

1. `CartService::normalizeIncomingItemForAdd` — cleans one book item for the cart. Bad data returns `null`.
2. `CartService::normalizeIncomingCart` — cleans a list from the browser. If the input is not an array, result is empty `[]`.

**Where is the code?** `tests/CartServiceTest.php`  
**How to run?** From the `ibrcn` folder:

`php vendor/bin/phpunit`

**Test case sheet:** `docs/test_case_sheet.csv` — columns: number, function name, input, expected result, covered by PHPUnit (Yes/No).

---

## 16) Black-box testing (short)

**Idea:** Test from **outside** the code: boundaries of inputs (zero, empty string, smallest valid value).

Simple examples are listed in the same CSV.

---

## What to hand in

| Item | Location |
|------|----------|
| Unit tests for 1 or 2 functions | `tests/CartServiceTest.php` |
| Test case sheet | `docs/test_case_sheet.csv` |

