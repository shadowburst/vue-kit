# Validation lives in FormRequests

All HTTP request validation is declared in `FormRequest::rules()`. Controllers and Actions must not call `$request->validate(...)` or `Validator::make(...)`. This keeps validation rules discoverable in one place per route, keeps controllers focused on the side effect, and makes rules reusable via traits in `app/Concerns` (see `ProfileValidationRules`, `PasswordValidationRules`).

## Consequences

- **Fortify Actions are exempt.** The `CreatesNewUsers` and `ResetsUserPasswords` contracts hand the action a plain `array $input`, not a `Request`, so a `FormRequest` cannot be resolved at the action boundary without overriding Fortify's controllers. `Validator::make($input, ...)` inside `App\Actions\Fortify\*` is the accepted pattern. Do not "fix" these to satisfy the rule — that path leads to maintaining forks of Fortify's controllers.
- One `FormRequest` per route, even when rules overlap. Shared rules belong in a trait under `app/Concerns`, not in a shared request class.
