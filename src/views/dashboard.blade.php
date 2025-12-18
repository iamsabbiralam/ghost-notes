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

        @media print {

            button,
            #ghostSearch,
            .flex.gap-4.mb-8,
            footer,
            .absolute {
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
                color: black !important;
            }

            h1,
            p,
            div {
                color: black !important;
            }

            table {
                width: 100% !important;
                border-collapse: collapse !important;
            }

            th,
            td {
                border: 1px solid #ddd !important;
                color: black !important;
                padding: 8px !important;
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
                <p class="text-slate-400 mt-2 text-lg">Your automated developer diary from hidden code tags.</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="bg-slate-800 border border-slate-700 rounded-xl px-6 py-3 shadow-sm">
                    <span class="block text-xs uppercase tracking-wider text-slate-500 font-bold">Total Notes</span>
                    <span class="text-2xl font-mono font-bold text-indigo-400">{{ count($rows) }}</span>
                </div>
            </div>
            <div class="relative inline-block text-left" x-data="{ open: false }">
                <button @click="open = !open"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl font-bold flex items-center gap-2 transition-all shadow-lg shadow-indigo-500/20">
                    📥 Export Report
                </button>
                <div x-show="open" @click.away="open = false"
                    class="absolute right-0 mt-2 w-48 bg-slate-800 border border-slate-700 rounded-xl shadow-xl z-50 overflow-hidden">
                    <a href="/ghost-notes/export/csv"
                        class="block px-4 py-3 text-sm text-slate-300 hover:bg-slate-700 border-b border-slate-700/50">📊
                        Export as CSV (Excel)</a>
                    <a href="/ghost-notes/export/json"
                        class="block px-4 py-3 text-sm text-slate-300 hover:bg-slate-700 border-b border-slate-700/50">📁
                        Export as JSON</a>
                    <a href="/ghost-notes/export/markdown"
                        class="block px-4 py-3 text-sm text-slate-300 hover:bg-slate-700">📝 Export as Markdown</a>
                    <button onclick="window.print()"
                        class="w-full text-left block px-4 py-3 text-sm text-emerald-400 hover:bg-slate-700 font-bold">🖨️
                        Print as PDF</button>
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
        <div class="flex gap-4 mb-8 border-b border-slate-800">
            <button onclick="switchTab('active')" id="btn-active"
                class="pb-4 px-6 text-indigo-400 border-b-2 border-indigo-500 font-bold transition-all">Active
                Graveyard</button>
            <button onclick="switchTab('resolved')" id="btn-resolved"
                class="pb-4 px-6 text-slate-500 hover:text-slate-300 font-bold transition-all">Resolved Ghosts
                🏆</button>
        </div>
        <div class="mb-6 relative">
            <input type="text" id="ghostSearch" placeholder="Search by note, author or file..."
                class="w-full bg-slate-900 border border-slate-700 text-slate-200 px-12 py-4 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all">
            <span class="absolute left-4 top-4 text-slate-500 text-xl">🔍</span>
        </div>

        <div id="active-table" class="bg-slate-900 border border-slate-800 shadow-2xl rounded-3xl overflow-hidden">
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
                                <td class="py-5 px-8 text-sm text-slate-400 font-mono">{{ $row['date'] }}</td>
                                <td class="py-5 px-8">
                                    <span
                                        class="px-2 py-1 rounded-md text-[10px] font-bold border 
                {{ $row['priority'] == 'HIGH' ? 'bg-red-500/20 text-red-400 border-red-500/50' : 'bg-slate-700 text-slate-400 border-slate-600' }}">
                                        {{ $row['priority'] }}
                                    </span>
                                </td>
                                <td class="py-5 px-8">
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-bold tracking-wide uppercase bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                                        {{ $row['tag'] }}
                                    </span>
                                </td>
                                <td class="py-5 px-8 text-slate-300">{{ $row['author'] }}</td>
                                <td class="py-5 px-8 flex items-center gap-3">
                                    <a href="{{ $row['vscode'] }}"
                                        class="text-blue-400 hover:scale-125 transition-transform">
                                        🖥️ Open in VS Code
                                    </a>
                                    <a href="{{ $row['link'] }}" target="_blank"
                                        class="text-xs text-indigo-400 underline font-mono truncate max-w-[150px]">
                                        {{ $row['file'] }}
                                    </a>
                                </td>
                                <td class="py-5 px-8 text-slate-300 text-sm">{{ $row['text'] }}</td>
                            </tr>
                            <tr class="bg-black/20">
                                <td colspan="6" class="px-8 py-2">
                                    <details class="group cursor-pointer">
                                        <summary
                                            class="text-[10px] text-slate-500 group-hover:text-slate-300 transition-colors uppercase tracking-widest font-bold">
                                            View Source Code</summary>
                                        <pre
                                            class="mt-3 p-4 bg-[#050a15] rounded-lg text-xs font-mono text-emerald-400 overflow-x-auto border border-slate-800 shadow-inner"><code>{{ base64_decode($row['snippet']) }}</code></pre>
                                    </details>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-20 text-center text-slate-500">
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
        <div id="resolved-table" class="hidden">
            <div class="bg-slate-900 border border-slate-800 shadow-2xl rounded-3xl overflow-hidden p-8 text-center">
                <h2 class="text-xl font-bold text-emerald-400 mb-4">Resolved Technical Debt</h2>
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-800/50 border-b border-slate-700 text-slate-400 text-xs uppercase">
                            <th class="py-4 px-6">Resolved Date</th>
                            <th class="py-4 px-6">Tag</th>
                            <th class="py-4 px-6">Author</th>
                            <th class="py-4 px-6">Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($history as $item)
                            <tr class="border-b border-slate-800 text-sm text-slate-300">
                                <td class="py-4 px-6">{{ $item['resolved_at'] }}</td>
                                <td class="py-4 px-6 font-bold text-indigo-400">{{ $item['tag'] }}</td>
                                <td class="py-4 px-6">{{ $item['author'] }}</td>
                                <td class="py-4 px-6 italic text-slate-400">{{ $item['text'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-20 text-center text-slate-500">
                                    <div class="flex flex-col items-center gap-4"></div>
                                    <span class="text-5xl">🎉</span>
                                    <p class="text-lg">No resolved technical debt found.</p>
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

    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
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
    </script>
</body>

</html>
