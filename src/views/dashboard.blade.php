<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GhostNotes - Developer Diary</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-[#0f172a] text-slate-200 min-h-screen font-sans">

    <div class="max-w-7xl mx-auto py-12 px-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-12">
            <div>
                <h1 class="text-4xl font-extrabold text-white tracking-tight flex items-center gap-3">
                    <span class="text-indigo-500">👻</span> GhostNotes
                </h1>
                <p class="text-slate-400 mt-2 text-lg">Your automated developer diary from hidden code tags.</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="bg-slate-800 border border-slate-700 rounded-xl px-6 py-3 shadow-sm">
                    <span class="block text-xs uppercase tracking-wider text-slate-500 font-bold">Total Notes</span>
                    <span class="text-2xl font-mono font-bold text-indigo-400">{{ count($rows) }}</span>
                </div>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-slate-800 border border-slate-700 p-6 rounded-2xl shadow-sm">
                <span class="text-slate-400 text-sm font-medium uppercase tracking-wider">Total Notes</span>
                <div class="text-3xl font-bold text-white mt-1">{{ count($rows) }}</div>
            </div>
            <div class="bg-slate-800 border border-slate-700 p-6 rounded-2xl shadow-sm border-l-4 border-l-red-500">
                <span class="text-slate-400 text-sm font-medium uppercase tracking-wider">High Priority</span>
                @php $highCount = collect($rows)->where('priority', 'HIGH')->count(); @endphp
                <div class="text-3xl font-bold text-red-400 mt-1">{{ $highCount }}</div>
            </div>
            <div class="bg-slate-800 border border-slate-700 p-6 rounded-2xl shadow-sm border-l-4 border-l-indigo-500">
                <span class="text-slate-400 text-sm font-medium uppercase tracking-wider">Authors Involved</span>
                @php $authorCount = collect($rows)->pluck('author')->unique()->count(); @endphp
                <div class="text-3xl font-bold text-indigo-400 mt-1">{{ $authorCount }}</div>
            </div>
        </div>

        <div class="mb-6 relative">
            <input type="text" id="ghostSearch" placeholder="Search by note, author or file..."
                class="w-full bg-slate-900 border border-slate-700 text-slate-200 px-12 py-4 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all">
            <span class="absolute left-4 top-4 text-slate-500 text-xl">🔍</span>
        </div>

        <div class="bg-slate-900 border border-slate-800 shadow-2xl rounded-3xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-800/50 border-b border-slate-700">
                            <th class="py-5 px-8 text-sm font-semibold uppercase tracking-wider text-slate-400">Date
                            </th>
                            <th class="py-5 px-8 text-sm font-semibold uppercase tracking-wider text-slate-400">Priority
                            </th>
                            <th class="py-5 px-8 text-sm font-semibold uppercase tracking-wider text-slate-400">Tag</th>
                            <th class="py-5 px-8 text-sm font-semibold uppercase tracking-wider text-slate-400">Author
                            </th>
                            <th class="py-5 px-8 text-sm font-semibold uppercase tracking-wider text-slate-400">Context
                            </th>
                            <th class="py-5 px-8 text-sm font-semibold uppercase tracking-wider text-slate-400">Message
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @forelse($rows as $row)
                            <tr class="group hover:bg-slate-800/30 transition-all duration-200">
                                <td class="py-5 px-8 whitespace-nowrap text-sm text-slate-400 font-mono">
                                    {{ $row['date'] }}
                                </td>
                                <td class="py-5 px-8">
                                    <span
                                        class="px-2 py-1 rounded-md text-[10px] font-bold border 
                {{ $row['priority'] == 'HIGH'
                    ? 'bg-red-500/20 text-red-400 border-red-500/50'
                    : ($row['priority'] == 'MEDIUM'
                        ? 'bg-amber-500/20 text-amber-400 border-amber-500/50'
                        : 'bg-slate-700 text-slate-400 border-slate-600') }}">
                                        {{ $row['priority'] }}
                                    </span>
                                </td>
                                <td class="py-5 px-8">
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-bold tracking-wide uppercase bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                                        {{ str_replace('**', '', $row['tag']) }}
                                    </span>
                                </td>
                                <td class="py-5 px-8 font-medium text-slate-300">
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="h-8 w-8 rounded-full bg-slate-700 flex items-center justify-center text-xs font-bold text-indigo-400 border border-slate-600 uppercase">
                                            {{ substr($row['author'], 0, 1) }}
                                        </div>
                                        {{ $row['author'] }}
                                    </div>
                                </td>
                                <td class="py-5 px-8">
                                    @if (!empty($row['link']))
                                        <a href="{{ $row['link'] }}" target="_blank"
                                            class="text-xs text-indigo-400 hover:text-indigo-300 underline font-mono">
                                            {{ $row['file'] }} 🔗
                                        </a>
                                    @else
                                        <code class="text-xs text-slate-500 font-mono">{{ $row['file'] }}</code>
                                    @endif
                                </td>
                                <td class="py-5 px-8 text-slate-300 text-sm leading-relaxed">
                                    {{ $row['message'] }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-20 text-center text-slate-500">
                                    <div class="flex flex-col items-center gap-4">
                                        <span class="text-5xl">🔭</span>
                                        <p class="text-lg">No ghost notes found in the graveyard.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div
            class="mt-8 flex justify-center items-center gap-2 text-slate-600 text-sm uppercase tracking-widest font-bold">
            <span>Built by</span>
            <a href="https://github.com/iamsabbiralam"
                class="text-slate-400 hover:text-indigo-400 transition-colors">@iamsabbiralam</a>
        </div>
    </div>

    <script>
        document.getElementById('ghostSearch').addEventListener('keyup', function() {
            let searchValue = this.value.toLowerCase();
            let rows = document.querySelectorAll('tbody tr');

            rows.forEach(row => {
                let text = row.innerText.toLowerCase();
                if (text.includes(searchValue)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    </script>
</body>

</html>
