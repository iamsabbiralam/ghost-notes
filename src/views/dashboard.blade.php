<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GhostNotes - Developer Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #1e293b;
        }

        ::-webkit-scrollbar-thumb {
            background: #475569;
            border-radius: 4px;
        }

        @media print {

            button,
            #ghostSearch,
            .sidebar,
            footer,
            .export-menu {
                display: none !important;
            }

            body {
                background: white !important;
                color: black !important;
            }

            .main-content {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
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

            .note-card {
                border: 1px solid #ddd !important;
                margin-bottom: 15px !important;
                page-break-inside: avoid;
            }
        }
    </style>
</head>

<body class="bg-[#0f172a] text-slate-200 min-h-screen font-sans" x-data="{
    currentTab: 'active',
    selectedFile: 'all',
    searchQuery: '',
    notes: {{ json_encode($rows) }},
    history: {{ json_encode($history) }},

    get filteredNotes() {
        return this.notes.filter(note => {
            $fileMatch = this.selectedFile === 'all' || note.file === this.selectedFile;
            $searchMatch = !this.searchQuery ||
                note.text.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                note.author.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                note.file.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                note.type.toLowerCase().includes(this.searchQuery.toLowerCase());
            return $fileMatch && $searchMatch;
        });
    },

    get uniqueFiles() {
        let files = {};
        this.notes.forEach(note => {
            files[note.file] = (files[note.file] || 0) + 1;
        });
        return files;
    }
}">

    <div class="flex flex-col h-screen overflow-hidden">

        <header
            class="bg-slate-900/80 backdrop-blur border-b border-slate-800 px-8 py-4 flex items-center justify-between z-10 shrink-0">
            <div class="flex items-center gap-6">
                <h1 class="text-2xl font-extrabold text-white tracking-tight flex items-center gap-2">
                    <span class="text-indigo-500">👻</span> GhostNotes <span
                        class="text-xs bg-indigo-500/10 text-indigo-400 px-2 py-0.5 rounded border border-indigo-500/20 font-mono">v1.1.0</span>
                </h1>
                <div class="relative flex items-center">
                    <input type="text" x-model="searchQuery" placeholder="Search notes, tags, authors..."
                        class="w-64 md:w-80 bg-slate-800 border border-slate-700 text-slate-200 pl-9 pr-4 py-1.5 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all text-xs">
                    <span class="absolute left-3 text-slate-500 text-xs">🔍</span>
                </div>
            </div>

            <div class="flex items-center gap-4 export-menu">
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-2 transition-all shadow-lg shadow-indigo-500/20">
                        📥 Export Report
                    </button>
                    <div x-show="open" @click.away="open = false" x-transition
                        class="absolute right-0 mt-2 w-52 bg-slate-800 border border-slate-700 rounded-xl shadow-2xl z-50 overflow-hidden text-xs">
                        <a href="/ghost-notes/export/csv"
                            class="block px-4 py-2.5 text-slate-300 hover:bg-slate-700 border-b border-slate-700/50">📊
                            Export as CSV</a>
                        <a href="/ghost-notes/export/json"
                            class="block px-4 py-2.5 text-slate-300 hover:bg-slate-700 border-b border-slate-700/50">📁
                            Export as JSON</a>
                        <a href="/ghost-notes/export/markdown"
                            class="block px-4 py-2.5 text-slate-300 hover:bg-slate-700">📝 Export as Markdown</a>
                        <button onclick="window.print()"
                            class="w-full text-left block px-4 py-2.5 text-emerald-400 hover:bg-slate-700 font-bold border-t border-slate-700/50">🖨️
                            Print PDF Dashboard</button>
                    </div>
                </div>
            </div>
        </header>

        <div class="flex flex-1 overflow-hidden">

            <aside class="w-80 bg-slate-900 border-r border-slate-800 flex flex-col sidebar shrink-0"
                x-show="currentTab === 'active'">
                <div class="p-4 border-b border-slate-800/60 bg-slate-900/50">
                    <span class="text-[10px] font-bold uppercase tracking-widest text-slate-500">File Navigator</span>
                </div>
                <div class="flex-1 overflow-y-auto p-2 space-y-1">
                    <button @click="selectedFile = 'all'"
                        :class="selectedFile === 'all' ? 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20' :
                            'text-slate-400 hover:bg-slate-800/50'"
                        class="w-full text-left px-3 py-2 rounded-xl text-xs font-medium flex items-center justify-between transition-colors">
                        <span class="flex items-center gap-2">📂 All Tracked Files</span>
                        <span class="bg-slate-800 px-2 py-0.5 rounded-full text-[10px] font-mono text-slate-400"
                            x-text="notes.length"></span>
                    </button>

                    <div class="h-px bg-slate-800 my-2"></div>

                    <template x-for="(count, file) in uniqueFiles" :key="file">
                        <button @click="selectedFile = file"
                            :class="selectedFile === file ?
                                'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 font-semibold' :
                                'text-slate-400 hover:bg-slate-800/30'"
                            class="w-full text-left px-3 py-2.5 rounded-xl text-xs flex items-center justify-between transition-colors group border border-transparent">
                            <span class="truncate pr-2 font-mono text-[11px]" x-text="file"></span>
                            <span
                                class="bg-slate-800 group-hover:bg-slate-700 px-1.5 py-0.5 rounded text-[10px] font-mono text-slate-400"
                                x-text="count"></span>
                        </button>
                    </template>
                </div>

                <div class="p-3 border-t border-slate-800 bg-slate-950/50 flex gap-2">
                    <button @click="currentTab = 'active'; selectedFile = 'all'"
                        :class="currentTab === 'active' ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-400'"
                        class="flex-1 py-2 text-center rounded-lg text-xs font-bold transition-all">
                        Active Notes
                    </button>
                    <button @click="currentTab = 'resolved'"
                        :class="currentTab === 'resolved' ? 'bg-emerald-600 text-white' : 'bg-slate-800 text-slate-400'"
                        class="flex-1 py-2 text-center rounded-lg text-xs font-bold transition-all">
                        Graveyard 🏆
                    </button>
                </div>
            </aside>

            <main class="flex-1 overflow-y-auto bg-[#0b1222] p-8 main-content">

                <div x-show="currentTab === 'active'">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400 flex items-center gap-2">
                            <span>📌 Container:</span>
                            <span class="text-indigo-400 font-mono"
                                x-text="selectedFile === 'all' ? 'All Files' : selectedFile"></span>
                        </h2>
                        <span class="text-xs text-slate-500 font-medium">Showing <span x-text="filteredNotes.length"
                                class="text-slate-300 font-mono"></span> entries</span>
                    </div>

                    <div class="grid grid-cols-1 gap-4">
                        <template x-for="note in filteredNotes" :key="note.snippet + Math.random()">
                            <div
                                class="bg-slate-900 border border-slate-800 rounded-2xl shadow-xl overflow-hidden hover:border-slate-700 transition-all note-card">
                                <div
                                    class="px-6 py-4 bg-slate-900/50 border-b border-slate-800/60 flex flex-wrap items-center justify-between gap-3 text-xs">
                                    <div class="flex items-center gap-2">
                                        <span
                                            :class="{
                                                'bg-red-500/10 text-red-400 border-red-500/20': note
                                                    .priority === 'HIGH',
                                                'bg-amber-500/10 text-amber-400 border-amber-500/20': note
                                                    .priority === 'MEDIUM',
                                                'bg-slate-700/50 text-slate-400 border-slate-600/50': note
                                                    .priority === 'NORMAL'
                                            }"
                                            class="px-2 py-0.5 rounded text-[10px] font-extrabold border uppercase"
                                            x-text="note.priority"></span>

                                        <span
                                            :class="{
                                                'bg-red-500/10 text-red-400 border-red-500/20': note
                                                    .type === 'FIX',
                                                'bg-emerald-500/10 text-emerald-400 border-emerald-500/20': note
                                                    .type === 'FEATURE',
                                                'bg-rose-500/10 text-rose-400 border-rose-500/20': note
                                                    .type === 'BREAKING',
                                                'bg-amber-500/10 text-amber-400 border-amber-500/20': note
                                                    .type === 'TODO',
                                                'bg-cyan-500/10 text-cyan-400 border-cyan-500/20': note
                                                    .type === 'NOTE',
                                                'bg-slate-500/10 text-slate-400 border-slate-500/20': note
                                                    .type === 'GENERAL'
                                            }"
                                            class="px-2 py-0.5 rounded text-[10px] font-extrabold border uppercase"
                                            x-text="note.type"></span>

                                        <span
                                            class="bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 px-2 py-0.5 rounded text-[10px] font-bold uppercase"
                                            x-text="'#' + note.tag"></span>
                                    </div>

                                    <div class="flex items-center gap-4 text-slate-400">
                                        <span class="flex items-center gap-1">👤 <span
                                                class="text-slate-300 font-medium" x-text="note.author"></span></span>
                                        <span class="font-mono text-slate-500" x-text="note.date"></span>
                                    </div>
                                </div>

                                <div class="p-6">
                                    <p class="text-slate-200 text-sm font-medium leading-relaxed mb-4"
                                        x-text="note.text"></p>

                                    <div class="flex items-center gap-3 text-[11px] border-t border-slate-800/60 pt-4">
                                        <a :href="note.vscode"
                                            class="text-blue-400 hover:underline flex items-center gap-1 font-medium">🖥️
                                            Open in VS Code</a>
                                        <template x-if="note.link">
                                            <a :href="note.link" target="_blank"
                                                class="text-slate-500 hover:text-indigo-400 font-mono truncate max-w-sm"
                                                x-text="'📄 ' + note.file"></a>
                                        </template>
                                        <template x-if="!note.link">
                                            <span class="text-slate-600 font-mono" x-text="'📄 ' + note.file"></span>
                                        </template>
                                    </div>

                                    <details class="group mt-4">
                                        <summary
                                            class="text-[10px] text-slate-600 group-hover:text-slate-400 cursor-pointer uppercase tracking-wider font-bold select-none list-none flex items-center gap-1">
                                            <span>▶</span> View Source Code Context
                                        </summary>
                                        <pre
                                            class="mt-2 p-4 bg-[#050a15] rounded-xl text-xs font-mono text-emerald-400/90 overflow-x-auto border border-slate-800"><code x-text="atob(note.snippet)"></code></pre>
                                    </details>
                                </div>
                            </div>
                        </template>

                        <div x-show="filteredNotes.length === 0"
                            class="py-20 text-center text-slate-500 italic text-sm">
                            No ghost notes found for this selection. 🎉
                        </div>
                    </div>
                </div>

                <div x-show="currentTab === 'resolved'"
                    class="bg-slate-900 border border-slate-800 shadow-2xl rounded-3xl p-8 max-w-4xl mx-auto">
                    <div class="text-center mb-8">
                        <h2 class="text-xl font-bold text-emerald-400 flex items-center justify-center gap-2">🏆
                            Resolved Technical Debt</h2>
                        <p class="text-xs text-slate-500 mt-1">History of successfully cleared code notes & tags</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr
                                    class="bg-slate-800/50 border-b border-slate-700 text-slate-400 uppercase tracking-wider">
                                    <th class="py-4 px-6 font-semibold">Resolved Date</th>
                                    <th class="py-4 px-6 font-semibold">Tag</th>
                                    <th class="py-4 px-6 font-semibold">Author</th>
                                    <th class="py-4 px-6 font-semibold">Message</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800 text-slate-300">
                                <template x-for="item in history" :key="item.resolved_at + Math.random()">
                                    <tr class="hover:bg-slate-800/20 transition-all">
                                        <td class="py-4 px-6 font-mono text-slate-400" x-text="item.resolved_at"></td>
                                        <td class="py-4 px-6"><span class="text-indigo-400 font-bold"
                                                x-text="'#' + item.tag"></span></td>
                                        <td class="py-4 px-6 font-medium" x-text="item.author"></td>
                                        <td class="py-4 px-6 italic text-slate-400" x-text="item.text"></td>
                                    </tr>
                                </template>
                                <tr x-show="history.length === 0">
                                    <td colspan="4" class="py-12 text-center text-slate-600 italic">No resolved
                                        ghosts yet. Keep hacking!</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</body>

</html>
