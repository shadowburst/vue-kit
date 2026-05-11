declare module '@inertiajs/core' {
    // eslint-disable-next-line @typescript-eslint/no-empty-object-type
    interface PageProps extends App.Data.Shared.SharedData {}
}

// Wayfinder's route-actions generator still emits `Response = Inertia.Pages.*`
// aliases in types.d.ts even with inertia.component disabled (ADR-0017 D9).
// These stubs satisfy the type-checker; page props now flow via App.Data.* classes.
declare namespace Inertia {
    namespace Pages {
        namespace Teams {
            type Index = Record<string, unknown>
            type Create = Record<string, unknown>
        }
        type Dashboard = Record<string, unknown>
        namespace Settings {
            type Profile = Record<string, unknown>
            type Security = Record<string, unknown>
            type Appearance = Record<string, unknown>
            type Language = Record<string, unknown>
            namespace Team {
                type Billing = Record<string, unknown>
            }
        }
    }
}
