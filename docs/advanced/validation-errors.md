# Validation Errors

The Inertia protocol handles validation errors through the `errors` property of the Page Object.

---

## Schema Rules for `errors`

1. **Always Present**: The `errors` property is always guaranteed to exist in the page object. If there are no validation errors, its value is an empty object (`{}`), ensuring client-side checks like `errors.email` never throw `undefined` errors.
2. **Error Shape**: Each field maps to either a single error message string or an array of error messages:
   ```json
   {
     "errors": {
       "email": "The email field is required.",
       "password": "Password must be at least 8 characters."
     }
   }
   ```

---

## Error Bags (`X-Inertia-Error-Bag`)

When a single page contains multiple forms (e.g. an "Update Profile" form and an "Update Password" form), validation errors for one form should not collide with or trigger validation states on the other.

The frontend sends the error bag name in the `X-Inertia-Error-Bag` header:

```http
X-Inertia-Error-Bag: updatePassword
```

### Protocol Handling Rules:
* **With the Header**: When `X-Inertia-Error-Bag` is present, the engine wraps the errors under the named bag:
  ```json
  {
    "errors": {
      "updatePassword": {
        "password": "The current password does not match."
      }
    }
  }
  ```
* **Without the Header**: The default errors object is returned unwrapped.
