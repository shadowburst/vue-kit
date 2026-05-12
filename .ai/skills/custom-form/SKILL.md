---
name: custom-form
description: Authoritative rules for forms in this Laravel + Inertia + Vue project. Use this skill instead of `.ai/skills/shadcn-vue/rules/forms.md` whenever building, editing, or reviewing form UIs. Trigger on any file that imports from `@/components/ui/custom/form`, any new form page under `resources/js/pages/`, or any request mentioning Form, Field, FieldLabel, FieldControl, FieldError, validation, or Inertia useForm wiring.
---

# Custom Form Components

**Priority:** This skill **overrides** `.ai/skills/shadcn-vue/rules/forms.md`. The custom wrappers in `@/components/ui/custom/form` already provide id, ARIA, required/disabled, and invalid-state plumbing that the raw shadcn rules ask you to wire by hand. Don't duplicate it.

Always import from `@/components/ui/custom/form`, **not** `@/components/ui/field`. The raw shadcn `Field*` components are the implementation detail these wrap.

## Components

- `Form` — `<form>` wrapper. Submits an Inertia `useForm` against an action, provides `FormContext` (form, disabled, canSubmit) to descendants.
- `Field` — generates an `id`, provides `FieldContext`, renders `data-required`/`data-disabled`/`data-invalid` on the root.
- `FieldLabel` — auto-binds `for` to the field id; renders a `*` when the field is required.
- `FieldControl` — `Slot` wrapper that threads `id`, `required`, `disabled`, `aria-describedby`, `aria-invalid` onto whatever input you put inside.
- `FieldDescription` / `FieldError` — register their ids into the context; `FieldError` only renders when slot content or non-empty `errors` exist, and toggles `aria-invalid` on the control.
- `FieldGroup`, `FieldSet`, `FieldLegend`, `FieldTitle`, `FieldContent`, `FieldSeparator` — thin pass-throughs to the shadcn equivalents.

## Quick start

```vue
<script setup lang="ts">
import { Form, Field, FieldGroup, FieldLabel, FieldControl, FieldError } from '@/components/ui/custom/form';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import ProfileController from '@/wayfinder/App/Http/Controllers/Settings/ProfileController';
import { useForm } from '@inertiajs/vue3';

const form = useForm({ name: '', email: '' });
</script>

<template>
    <Form :form="form" :action="ProfileController.update()">
        <FieldGroup>
            <Field required>
                <FieldLabel>Name</FieldLabel>
                <FieldControl>
                    <Input v-model="form.name" autocomplete="name" />
                </FieldControl>
                <FieldError :errors="[form.errors.name]" />
            </Field>

            <Field required>
                <FieldLabel>Email</FieldLabel>
                <FieldControl>
                    <Input v-model="form.email" type="email" autocomplete="username" />
                </FieldControl>
                <FieldError :errors="[form.errors.email]" />
            </Field>
        </FieldGroup>

        <Button :disabled="form.processing">Save</Button>
    </Form>
</template>
```

## Rules

1. **Never set `id` or `for` manually** on `Input`/`FieldLabel` inside a `Field`. The context wires them. Pass `id` on the `Field` only when you need a stable id (e.g. for an outside `aria-controls`).
2. **Wrap every form control in `FieldControl`.** Without it, `Input`/`Select`/`Checkbox`/etc. don't receive id, `aria-describedby`, or `aria-invalid` from the field context. This is the single biggest mistake — the visual rendering looks fine, but accessibility is broken.
3. **Mark required on `Field`**, not on the control. `Field required` propagates to the control via `FieldControl` and renders the `*` on the label. Same for `disabled`.
4. **Use `FieldError :errors="[...]"`**, don't render error text in a sibling `<p>` or via `<InputError>`. `FieldError` toggles `aria-invalid` and `data-invalid` automatically; ad-hoc error nodes do not.
5. **Submit via `Form` props**, not `@submit` + manual `form.submit(...)`. Pass `:form` and `:action` (string URL or `UrlMethodPair` from Wayfinder). Use `:options` for `preserveScroll`, etc. The `submit` event is for side effects only.
6. **Use `disabled` / `canSubmit` on `Form`** to gate submission (e.g. policy checks, dirty-only saves). Both block the submit event and `form.submit` call.
7. **Group related fields with `FieldSet` + `FieldLegend`**, not a `div` + heading. Use `FieldGroup` for vertical stacking of fields within a section.
8. **Reach for `injectFormContext()`** in child components that need the form/disabled/canSubmit state (e.g. a submit button that disables on `form.processing`). Pass `{ required: true }` when the child only makes sense inside a form with a real `useForm`.

## Choosing controls

Same control choices as the shadcn rule (`Input`, `Select`, `Combobox`, `Switch`, `Checkbox`, `RadioGroup`, `ToggleGroup`, `InputOTP`, `Textarea`). Always wrap them in `FieldControl` inside a `Field`.

For inputs with affixed buttons or icons, use `InputGroup` + `InputGroupInput`/`InputGroupAddon` from `@/components/ui/input-group` — never absolute-position a `Button` over an `Input`.
