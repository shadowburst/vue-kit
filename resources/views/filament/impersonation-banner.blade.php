@php
    /** @var \App\Services\Impersonation\ImpersonationContext $ctx */
    $ctx = app(\App\Services\Impersonation\ImpersonationContext::class);
@endphp

@if ($ctx->isImpersonating())
    @php
        /** @var \App\Models\User $target */
        $target = auth()->user();
    @endphp
    <div class="flex items-center justify-between gap-4 bg-amber-500 px-4 py-2 text-sm font-medium text-white">
        <span>{{ __('admin.impersonation.banner_title', ['name' => $target->name]) }}</span>
        <form method="POST" action="{{ route('impersonate.leave') }}">
            @csrf
            <button type="submit" class="rounded border border-white/50 px-3 py-1 text-xs hover:bg-white/10">
                {{ __('admin.impersonation.leave') }}
            </button>
        </form>
    </div>
@endif
