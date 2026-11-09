# Big Integers (Version 3)

In JavaScript, all numbers are 64-bit floating point. Integers exceeding `Number.MAX_SAFE_INTEGER` (`9,007,199,254,740,991` or `2^53 - 1`) lose precision and truncate when parsed with `JSON.parse`.

In PHP (on standard 64-bit architectures), integers can reach `PHP_INT_MAX` (`9,223,372,036,854,775,807`).

---

## Enabling BigInt Preservation

Enable `preserveBigIntegers` globally or per response:

```php
use Inertia\Protocol\InertiaProtocol;

// Globally:
InertiaProtocol::preserveBigIntegers();

// Or per-response:
$response = InertiaProtocol::render('Transactions/Show', [
    'transaction_id' => 9223372036854775807,
])->preserveBigIntegers();
```

---

## How It Works

1. During prop evaluation, the engine recursively scans for integers where `$value > 9007199254740991` or `$value < -9007199254740991`.
2. Out-of-bounds integers are wrapped into special marker arrays:
   ```json
   {
     "transaction_id": {
       "$bigint": "9223372036854775807"
     }
   }
   ```
3. Sets `"preserveBigIntegers": true` in the Page Object.
4. The Inertia client detects this flag and transforms each `{"$bigint": "<digits>"}` object into a native JavaScript `BigInt` value.
