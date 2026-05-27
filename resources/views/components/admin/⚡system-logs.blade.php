<?php

use Illuminate\Support\Facades\File;
use Livewire\Component;

new class extends Component
{
    public string $tab = 'app';

    public int $tailLines = 200;

    public function switchTab(string $tab): void
    {
        $this->tab = in_array($tab, ['app', 'mail'], true) ? $tab : 'app';
    }

    public function logFiles(): array
    {
        $dir = storage_path('logs');
        if (! is_dir($dir)) {
            return [];
        }

        return collect(File::files($dir))
            ->filter(fn ($f) => str_ends_with($f->getFilename(), '.log'))
            ->sortByDesc(fn ($f) => $f->getMTime())
            ->map(fn ($f) => [
                'name' => $f->getFilename(),
                'size_kb' => round($f->getSize() / 1024, 1),
                'modified' => date('M j, Y · g:i A', $f->getMTime()),
            ])
            ->values()
            ->all();
    }

    public function tail(): string
    {
        $path = storage_path('logs/laravel.log');
        if (! file_exists($path)) {
            return 'No laravel.log file yet — once activity happens, lines will appear here.';
        }

        $lines = $this->tailFile($path, $this->tailLines);

        return implode('', $lines) ?: 'Log file is empty.';
    }

    public function mailEntries(): array
    {
        $path = storage_path('logs/laravel.log');
        if (! file_exists($path)) {
            return [];
        }

        // Read only the last ~2 MB of the log file so a huge file doesn't OOM the process.
        $maxBytes = 2 * 1024 * 1024;
        $size = filesize($path) ?: 0;
        $f = fopen($path, 'r');
        if (! $f) {
            return [];
        }
        if ($size > $maxBytes) {
            fseek($f, $size - $maxBytes);
            // Discard the partial line at the start.
            fgets($f);
        }
        $content = (string) stream_get_contents($f);
        fclose($f);

        // Capture each "local.INFO: Message ID" block — Laravel's log mailer writes
        // a header then the rendered MIME body until the next log line.
        $entries = [];
        $pattern = '/\[(?P<time>[^\]]+)\][^\n]*?local\.INFO: (?P<head>.*?)(?=\n\[\d{4}-\d{2}-\d{2}|\z)/s';

        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $body = $m['head'];
                if (! str_contains($body, 'From:') || ! str_contains($body, 'To:')) {
                    continue;
                }
                preg_match('/Subject: (?P<subject>[^\r\n]+)/', $body, $sub);
                preg_match('/To: (?P<to>[^\r\n]+)/', $body, $to);
                preg_match('/From: (?P<from>[^\r\n]+)/', $body, $from);

                $entries[] = [
                    'time' => $m['time'],
                    'subject' => trim($sub['subject'] ?? '—'),
                    'to' => trim($to['to'] ?? '—'),
                    'from' => trim($from['from'] ?? '—'),
                    'body' => $body,
                ];
            }
        }

        return array_slice(array_reverse($entries), 0, 50);
    }

    /**
     * Efficient tail without loading the whole file.
     *
     * @return array<int, string>
     */
    protected function tailFile(string $path, int $lines): array
    {
        $f = fopen($path, 'r');
        if (! $f) {
            return [];
        }

        $buffer = '';
        $chunk = 4096;
        $position = filesize($path);
        $read = [];

        while ($position > 0 && count(explode("\n", $buffer)) <= $lines + 1) {
            $size = (int) min($chunk, $position);
            $position -= $size;
            fseek($f, $position);
            $buffer = fread($f, $size).$buffer;
        }
        fclose($f);

        $all = explode("\n", $buffer);
        $tail = array_slice($all, -($lines + 1));

        return array_map(fn ($l) => $l."\n", $tail);
    }
}; ?>

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-3xl border border-cream-300 bg-white p-5 shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
        <div>
            <p class="font-sans text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">Log location</p>
            <p class="mt-1 font-mono text-sm text-ink-900">{{ storage_path('logs/laravel.log') }}</p>
            <p class="mt-1 text-xs text-ink-500">Mailer is set to <code class="rounded bg-cream-100 px-1.5">{{ config('mail.default') }}</code>. In log mode, every email lands here as a rendered MIME message.</p>
        </div>
        <div class="text-right text-xs text-ink-500">
            <p>Available log files</p>
            <ul class="mt-1 space-y-0.5">
                @forelse ($this->logFiles() as $file)
                    <li class="font-mono">{{ $file['name'] }} <span class="text-ink-500/70">· {{ $file['size_kb'] }} KB · {{ $file['modified'] }}</span></li>
                @empty
                    <li>None yet.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="flex flex-wrap gap-1 border-b border-cream-200">
        @foreach ([
            ['app',  'Application log'],
            ['mail', 'Sent emails'],
        ] as [$key, $label])
            <button
                type="button"
                wire:click="switchTab('{{ $key }}')"
                @class([
                    'font-sans -mb-px inline-flex items-center gap-2 border-b-2 px-4 py-3 text-sm font-semibold uppercase tracking-[0.18em] transition',
                    'border-brand-900 text-brand-900' => $tab === $key,
                    'border-transparent text-ink-500 hover:text-ink-900' => $tab !== $key,
                ])
            >{{ $label }}</button>
        @endforeach
    </div>

    @if ($tab === 'app')
        <div class="rounded-3xl border border-cream-300 bg-ink-900 p-5 shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
            <div class="mb-3 flex items-center justify-between">
                <p class="font-sans text-xs font-semibold uppercase tracking-[0.18em] text-cream-200">Tail · last {{ $tailLines }} lines</p>
                <button type="button" wire:click="$refresh" class="rounded-full border border-cream-300/30 px-3 py-1 text-xs font-semibold text-cream-200 transition hover:border-cream-300 hover:text-white">Refresh</button>
            </div>
            <pre class="max-h-[60vh] overflow-auto whitespace-pre-wrap font-mono text-[11px] leading-relaxed text-cream-200">{{ $this->tail() }}</pre>
        </div>
    @else
        @php $entries = $this->mailEntries(); @endphp
        <div class="space-y-3">
            @forelse ($entries as $i => $entry)
                <article x-data="{ open: false }" class="rounded-3xl border border-cream-300 bg-white shadow-[0_8px_30px_-15px_rgba(0,4,78,0.08)]">
                    <button type="button" @click="open = ! open" class="flex w-full items-center justify-between gap-3 px-5 py-4 text-left">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-ink-900">{{ $entry['subject'] }}</p>
                            <p class="mt-0.5 truncate text-xs text-ink-500">To {{ $entry['to'] }} · {{ $entry['time'] }}</p>
                        </div>
                        <svg class="size-4 text-ink-500 transition" :class="open ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" x-cloak class="border-t border-cream-200 bg-cream-50 px-5 py-4">
                        <pre class="max-h-[40vh] overflow-auto whitespace-pre-wrap font-mono text-[11px] leading-relaxed text-ink-700">{{ $entry['body'] }}</pre>
                    </div>
                </article>
            @empty
                <div class="rounded-3xl border border-dashed border-cream-300 bg-white p-10 text-center text-sm text-ink-500">
                    No emails have been sent yet. When the log mailer fires, entries appear here.
                </div>
            @endforelse
        </div>
    @endif
</div>
