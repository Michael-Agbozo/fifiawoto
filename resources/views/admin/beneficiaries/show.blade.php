<x-admin-page
    title="{{ $beneficiary->full_name }}"
    kicker="Beneficiary profile"
>
    <livewire:admin.beneficiary-show :beneficiary="$beneficiary" />
</x-admin-page>
