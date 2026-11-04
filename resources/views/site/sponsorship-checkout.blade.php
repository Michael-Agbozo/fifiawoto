<x-layouts::site :title="'Sponsor '.$child->public_display_name" :noindex="true">
    <livewire:site.sponsorship-checkout :profile="$child->public_sponsorship_id" />
</x-layouts::site>
