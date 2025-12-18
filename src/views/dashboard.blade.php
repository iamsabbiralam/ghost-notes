<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GhostNotes - Developer Diary</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        @media print {
            button, #ghostSearch, .tab-nav, footer, .export-menu { display: none !important; }
            body { background: white !important; color: black !important; }
            .bg-slate-800, .bg-slate-900 { background: transparent !important; border: 1px solid #ddd !important; color: black !important; }
            h1, p, div { color: black !important; }
            table { width: 100% !important; border-collapse: collapse !important; }
            th, td { border: 1px solid #ddd !important; color: black !important; padding: 8px !important; }
        }
    </style>
</head>

<body class="bg-[#0f172a] text-slate-200 min-h-screen font-sans">
    <div class="max-w-7xl mx-auto py-12 px-6">
        
        <div class="flex flex-col md:flex-row md:items-start justify-between gap-6 mb-12">
            <div>
                <h1 class="text-4xl font-extrabold text-white tracking-tight flex items-center gap-3">
                    <span class="text-indigo-500">👻</span> GhostNotes
                </h1>
                <p class="text-slate-400 mt-2 text-lg">Your automated developer diary from hidden code tags.</p>
            </div>
            
            <div class="flex items-center gap-4">
                <div class="relative export-menu" x-data="{ open: false }">
                    <button @click="open = !open"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl font-bold flex items-center gap-2 transition-all shadow-lg shadow-indigo-500/20">
                        📥 Export Report
                    </button>
                    <div x-show="open" @click.away="open = false" x-transition
                        class="absolute right-0 mt-2 w-56 bg-slate-800 border border-slate-700 rounded-xl shadow-2xl z-50 overflow-hidden">
                        <a href="/ghost-notes/export/csv" class="block px-4 py-3 text-sm text-slate-300 hover:bg-slate-700 border-b border-slate-700/50">📊 Export as CSV (Excel)</a>
                        <a href="/ghost-notes/export/json" class="block px-4 py-3 text-sm text-slate-300 hover:bg-slate-700 border-b border-slate-700/50">📁 Export as JSON</a>
                        <a href="/ghost-notes/export/markdown" class="block px-4 py-3 text-sm text-slate-300 hover:bg-slate-700">📝 Export as Markdown</a>
                        <button onclick="window.print()" class="w-full text-left block px-4 py-3 text-sm text-emerald-400 hover:bg-slate-700 font-bold border-t border-slate-700/50">🖨️ Print as PDF</button>
                    </div>
                </div>

                <div class="bg-slate-800 border border-slate-700 rounded-xl px-6 py-3 shadow-sm hidden md:block">
                    <span class="block text-xs uppercase tracking-wider text-slate-500 font-bold">Total Notes</span>
                    <span class="text-2xl font-mono font-bold text-indigo-400">{{ count($rows) }}</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
            <div class="bg-slate-800 border border-slate-700 p-6 rounded-2xl shadow-sm hover:border-indigo-500/50 transition-colors">
                <span class="text-slate-400 text-sm font-medium uppercase tracking-wider">Active Notes</span>
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

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div class="flex gap-2 tab-nav">
                <button onclick="switchTab('active')" id="btn-active"
                    class="py-2.5 px-6 rounded-lg bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 font-bold transition-all">
                    Active Graveyard
                </button>
                <button onclick="switchTab('resolved')" id="btn-resolved"
                    class="py-2.5 px-6 rounded-lg text-slate-500 hover:text-slate-300 font-bold transition-all">
                    Resolved Ghosts 🏆
                </button>
            </div>
            
            <div class="relative flex-1 md:max-w-md">
                <input type="text" id="ghostSearch" placeholder="Search notes, authors, files..."
                    class="w-full bg-slate-900 border border-slate-700 text-slate-200 pl-10 pr-4 py-2.5 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all text-sm">
                <span class="absolute left-3.5 top-3 text-slate-500 text-sm">🔍</span>
            </div>
        </div>

        <div id="active-table" class="bg-slate-900 border border-slate-800 shadow-2xl rounded-3xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-800/50 border-b border-slate-700">
                            <th class="py-4 px-6 text-xs font-semibold uppercase tracking-wider text-slate-400">Date</th>
                            <th class="py-4 px-6 text-xs font-semibold uppercase tracking-wider text-slate-400">Priority</th>
                            <th class="py-4 px-6 text-xs font-semibold uppercase tracking-wider text-slate-400">Tag</th>
                            <th class="py-4 px-6 text-xs font-semibold uppercase tracking-wider text-slate-400">Author</th>
                            <th class="py-4 px-6 text-xs font-semibold uppercase tracking-wider text-slate-400">Context</th>
                            <th class="py-4 px-6 text-xs font-semibold uppercase tracking-wider text-slate-400">Message</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @forelse($rows as $row)
                            <tr class="group hover:bg-slate-800/30 transition-all">
                                <td class="py-4 px-6 text-sm text-slate-400 font-mono whitespace-nowrap">{{ $row['date'] }}</td>
                                <td class="py-4 px-6">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold border {{ $row['priority'] == 'HIGH' ? 'bg-red-500/10 text-red-400 border-red-500/30' : 'bg-slate-700/50 text-slate-400 border-slate-600/50' }}">
                                        {{ $row['priority'] }}
                                    </span>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 uppercase">
                                        {{ $row['tag'] }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-sm text-slate-300">{{ $row['author'] }}</td>
                                <td class="py-4 px-6">
                                    <div class="flex flex-col gap-1">
                                        <a href="{{ $row['vscode'] }}" class="text-[10px] text-blue-400 hover:underline flex items-center gap-1">🖥️ VS Code</a>
                                        <a href="{{ $row['link'] }}" target="_blank" class="text-[10px] text-slate-500 hover:text-indigo-400 font-mono truncate max-w-[120px]">{{ $row['file'] }}</a>
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-slate-300 text-sm leading-relaxed">{{ $row['text'] }}</td>
                            </tr>
                            <tr class="bg-black/10">
                                <td colspan="6" class="px-6 py-2">
                                    <details class="group">
                                        <summary class="text-[10px] text-slate-600 group-hover:text-slate-400 cursor-pointer uppercase tracking-widest font-bold select-none">View Source</summary>
                                        <pre class="mt-2 p-4 bg-[#050a15] rounded-lg text-xs font-mono text-emerald-400/90 overflow-x-auto border border-slate-800"><code>{{ base64_decode($row['snippet']) }}</code></pre>
                                    </details>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-20 text-center text-slate-500 italic">No ghost notes found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div id="resolved-table" class="hidden bg-slate-900 border border-slate-800 shadow-2xl rounded-3xl overflow-hidden p-8 text-center">
             <h2 class="text-xl font-bold text-emerald-400 mb-6">🏆 Resolved Technical Debt</h2>
             <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-800/50 border-b border-slate-700 text-slate-400 text-[10px] uppercase tracking-wider">
                        <th class="py-4 px-6">Resolved Date</th>
                        <th class="py-4 px-6">Tag</th>
                        <th class="py-4 px-6">Author</th>
                        <th class="py-4 px-6">Message</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($history as $item)
                        <tr class="border-b border-slate-800 text-sm text-slate-300 hover:bg-slate-800/20">
                            <td class="py-4 px-6 font-mono text-xs">{{ $item['resolved_at'] }}</td>
                            <td class="py-4 px-6"><span class="text-indigo-400 font-bold">#{{ $item['tag'] }}</span></td>
                            <td class="py-4 px-6">{{ $item['author'] }}</td>
                            <td class="py-4 px-6 italic text-slate-400">{{ $item['text'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-12 text-slate-600">No resolved ghosts yet. Keep hacking!</td></tr>
                    @endforelse
                </tbody>
             </table>
        </div>

        <footer class="mt-12 flex flex-col items-center gap-2 border-t border-slate-800/50 pt-8">
            <div class="flex items-center gap-2 text-slate-600 text-xs uppercase tracking-widest font-bold">
                <span>Built by</span>
                <a href="https://github.com/iamsabbiralam" class="text-slate-400 hover:text-indigo-400 transition-colors">@iamsabbiralam</a>
            </div>
        </footer>
    </div>

    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
        // Search Functionality
        document.getElementById('ghostSearch').addEventListener('keyup', function() {
            let searchValue = this.value.toLowerCase();
            let tableRows = document.querySelectorAll('#active-table tbody tr:not(.bg-black\\/10)');
            
            tableRows.forEach(row => {
                let text = row.innerText.toLowerCase();
                let snippetRow = row.nextElementSibling;
                if (text.includes(searchValue)) {
                    row.style.display = '';
                    if(snippetRow) snippetRow.style.display = '';
                } else {
                    row.style.display = 'none';
                    if(snippetRow) snippetRow.style.display = 'none';
                }
            });
        });

        // Tab Switcher
        function switchTab(tab) {
            const activeTable = document.getElementById('active-table');
            const resolvedTable = document.getElementById('resolved-table');
            const btnActive = document.getElementById('btn-active');
            const btnResolved = document.getElementById('btn-resolved');

            if (tab === 'active') {
                activeTable.classList.remove('hidden');
                resolvedTable.classList.add('hidden');
                btnActive.className = "py-2.5 px-6 rounded-lg bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 font-bold transition-all";
                btnResolved.className = "py-2.5 px-6 rounded-lg text-slate-500 hover:text-slate-300 font-bold transition-all";
            } else {
                activeTable.classList.add('hidden');
                resolvedTable.classList.remove('hidden');
                btnResolved.className = "py-2.5 px-6 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-bold transition-all";
                btnActive.className = "py-2.5 px-6 rounded-lg text-slate-500 hover:text-slate-300 font-bold transition-all";
            }
        }
    </script>
</body>
</html>