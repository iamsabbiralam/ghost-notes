<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GhostNotes - Developer Diary</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        @media print {

            button,
            #ghostSearch,
            .tabs-header,
            footer,
            .export-btn {
                display: none !important;
            }

            body {
                background: white !important;
                color: black !important;
            }

            .bg-slate-800,
            .bg-slate-900 {
                background: transparent !important;
                border: 1px solid #ddd !important;
            }

            table {
                width: 100% !important;
                border-collapse: collapse !important;
            }

            th,
            td {
                border: 1px solid #ddd !important;
                padding: 8px !important;
                color: black !important;
            }
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
                <p class="text-slate-400 mt-2 text-lg">Automated developer diary from hidden code tags.</p>
            </div>

            <div class="flex items-center gap-4">
                <div class="relative export-btn" x-data="{ open: false }">
                    <button @click="open = !open"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl font-bold flex items-center gap-2 transition-all">
                        📥 Export Report
                    </button>
                    <div x-show="open" @click.away="open = false"
                        class="absolute right-0 mt-2 w-52 bg-slate-800 border border-slate-700 rounded-xl shadow-2xl z-50 overflow-hidden">
                        <a href="{{ url('ghost-notes/export/csv') }}"
                            class="block px-4 py-3 text-sm text-slate-300 hover:bg-slate-700 border-b border-slate-700/50">📊
                            Export as CSV (Excel)</a>
                        <a href="{{ url('ghost-notes/export/json') }}"
                            class="block px-4 py-3 text-sm text-slate-300 hover:bg-slate-700 border-b border-slate-700/50">📁
                            Export as JSON</a>
                        <a href="{{ url('ghost-notes/export/markdown') }}"
                            class="block px-4 py-3 text-sm text-slate-300 hover:bg-slate-700">📝 Export as Markdown</a>
                        <button onclick="window.print()"
                            class="w-full text-left block px-4 py-3 text-sm text-emerald-400 hover:bg-slate-700 font-bold border-t border-slate-700">🖨️
                            Print as PDF</button>
                    </div>
                </div>

                <div class="bg-slate-800 border border-slate-700 rounded-xl px-6 py-3 shadow-sm">
                    <span
                        class="block text-xs uppercase tracking-wider text-slate-500 font-bold text-center">Active</span>
                    <span class="text-2xl font-mono font-bold text-indigo-400">{{ count($rows) }}</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-slate-800 border border-slate-700 p-6 rounded-2xl">
                <span class="text-slate-400 text-sm font-medium uppercase">High Priority</span>
                @php $highCount = collect($rows)->where('priority', 'HIGH')->count(); @endphp
                <div class="text-3xl font-bold text-red-400 mt-1">{{ $highCount }}</div>
            </div>
            <div class="bg-slate-800 border border-slate-700 p-6 rounded-2xl">
                <span class="text-slate-400 text-sm font-medium uppercase">Resolved Tasks</span>
                <div class="text-3xl font-bold text-emerald-400 mt-1">{{ count($history) }}</div>
            </div>
            <div class="bg-slate-800 border border-slate-700 p-6 rounded-2xl">
                <span class="text-slate-400 text-sm font-medium uppercase">Authors</span>
                @php $authorCount = collect($rows)->pluck('author')->unique()->count(); @endphp
                <div class="text-3xl font-bold text-indigo-400 mt-1">{{ $authorCount }}</div>
            </div>
        </div>

        <div class="flex gap-4 mb-8 border-b border-slate-800 tabs-header">
            <button onclick="switchTab('active')" id="btn-active"
                class="pb-4 px-6 text-indigo-400 border-b-2 border-indigo-500 font-bold transition-all">Active
                Graveyard</button>
            <button onclick="switchTab('resolved')" id="btn-resolved"
                class="pb-4 px-6 text-slate-500 hover:text-slate-300 font-bold transition-all">Resolved Ghosts
                🏆</button>
        </div>

        <div class="mb-6 relative">
            <input type="text" id="ghostSearch" placeholder="Search by note, author or file..."
                class="w-full bg-slate-900 border border-slate-700 text-slate-200 px-12 py-4 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <span class="absolute left-4 top-4 text-slate-500 text-xl">🔍</span>
        </div>

        <div id="active-table" class="bg-slate-900 border border-slate-800 shadow-2xl rounded-3xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-800/50 border-b border-slate-700">
                            <th class="py-5 px-8 text-xs font-bold uppercase tracking-wider text-slate-400">
                                Date/Priority</th>
                            <th class="py-5 px-8 text-xs font-bold uppercase tracking-wider text-slate-400">Author &
                                File</th>
                            <th class="py-5 px-8 text-xs font-bold uppercase tracking-wider text-slate-400">Message</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @forelse($rows as $row)
                            <tr class="group hover:bg-slate-800/30 transition-all duration-200">
                                <td class="py-5 px-8">
                                    <div class="text-sm font-mono text-slate-500">{{ $row['date'] }}</div>
                                    <span
                                        class="mt-1 inline-block px-2 py-0.5 rounded text-[10px] font-bold border {{ $row['priority'] == 'HIGH' ? 'bg-red-500/20 text-red-400 border-red-500/50' : 'bg-slate-700 text-slate-400 border-slate-600' }}">
                                        {{ $row['priority'] }}
                                    </span>
                                </td>
                                <td class="py-5 px-8">
                                    <div class="text-slate-300 font-semibold">{{ $row['author'] }}</div>
                                    <a href="{{ $row['vscode'] }}"
                                        class="text-[11px] text-indigo-400 hover:underline">🖥️ Open Local</a>
                                </td>
                                <td class="py-5 px-8">
                                    <div class="text-indigo-400 font-bold text-xs uppercase mb-1">{{ $row['tag'] }}
                                    </div>
                                    <div class="text-slate-300 text-sm">{{ $row['text'] }}</div>
                                    <details class="mt-3 group">
                                        <summary class="text-[10px] text-slate-500 cursor-pointer uppercase font-bold">
                                            Source Code</summary>
                                        <pre class="mt-2 p-3 bg-black/40 rounded text-[11px] text-emerald-400 border border-slate-800 overflow-x-auto"><code>{{ base64_decode($row['snippet']) }}</code></pre>
                                    </details>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-20 text-center text-slate-500">No active notes found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div id="resolved-table" class="hidden">
            <div class="bg-slate-900 border border-slate-800 shadow-2xl rounded-3xl overflow-hidden p-8">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr
                            class="bg-slate-800/50 border-b border-slate-700 text-slate-400 text-xs uppercase font-bold">
                            <th class="py-4 px-6">Resolved Date</th>
                            <th class="py-4 px-6">Author</th>
                            <th class="py-4 px-6">Note Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @forelse ($history as $item)
                            <tr>
                                <td class="py-4 px-6 text-sm text-slate-500">{{ $item['resolved_at'] }}</td>
                                <td class="py-4 px-6 text-slate-300">{{ $item['author'] }}</td>
                                <td class="py-4 px-6 italic text-slate-400">
                                    <span class="text-emerald-500 font-bold mr-2">#{{ $item['tag'] }}</span>
                                    {{ $item['text'] }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-20 text-center text-slate-500">No resolved tasks yet. 🎉
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            const activeTab = document.getElementById('active-table');
            const resolvedTab = document.getElementById('resolved-table');
            const btnActive = document.getElementById('btn-active');
            const btnResolved = document.getElementById('btn-resolved');

            if (tab === 'active') {
                activeTab.classList.remove('hidden');
                resolvedTab.classList.add('hidden');
                btnActive.className = "pb-4 px-6 text-indigo-400 border-b-2 border-indigo-500 font-bold transition-all";
                btnResolved.className = "pb-4 px-6 text-slate-500 hover:text-slate-300 font-bold transition-all";
            } else {
                activeTab.classList.add('hidden');
                resolvedTab.classList.remove('hidden');
                btnResolved.className = "pb-4 px-6 text-emerald-400 border-b-2 border-emerald-500 font-bold transition-all";
                btnActive.className = "pb-4 px-6 text-slate-500 hover:text-slate-300 font-bold transition-all";
            }
        }

        document.getElementById('ghostSearch').addEventListener('keyup', function() {
            let val = this.value.toLowerCase();
            document.querySelectorAll('tbody tr').forEach(row => {
                row.style.display = row.innerText.toLowerCase().includes(val) ? '' : 'none';
            });
        });
    </script>
</body>

</html>
