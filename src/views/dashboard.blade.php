<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GhostNotes - Developer Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

    // 🚀 পিএইচপি কনফিগ থেকে কাস্টম ট্যাগ ম্যাপ নিয়ে আসা
    customTags: {{ json_encode($customTags) }},

    // 🎨 ডায়নামিক কালার স্টাইল জেনারেট করার মেথড
    getTagStyle(tag) {
        if (!tag) return '';
        const lowerTag = tag.toLowerCase().replace('@', '');
        if (this.customTags && this.customTags[lowerTag]) {
            const config = this.customTags[lowerTag];
            return `background-color: ${config.bg_color}20; color: ${config.bg_color}; border-color: ${config.bg_color}30;`;
        }
        return 'background-color: rgba(99, 102, 241, 0.1); color: #818cf8; border-color: rgba(99, 102, 241, 0.2);';
    },

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
    },

    resolveTask(note) {
        if (!confirm('Are you sure you want to resolve this debt? This will clear the comment line from your local code!')) return;

        const parts = note.vscode.split(':');
        const line = parts[parts.length - 1];

        fetch('/ghost-notes/resolve', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    file: note.file,
                    line: line
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    this.notes = data.active_notes;
                    this.history = data.history_notes;
                    alert(data.message);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => {
                console.error(err);
                alert('Something went wrong!');
            });
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

                <div x-show="currentTab === 'active'" class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div class="bg-slate-900 border border-slate-800 p-6 rounded-3xl shadow-xl">
                        <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-4">👤 Top Debt Creators
                            (By Author)</h3>
                        <div class="relative h-64">
                            <canvas id="authorDebtChart"></canvas>
                        </div>
                    </div>

                    <div class="bg-slate-900 border border-slate-800 p-6 rounded-3xl shadow-xl">
                        <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-4">📅 Weekly Activity
                            Trend</h3>
                        <div class="relative h-64">
                            <canvas id="weeklyActivityChart"></canvas>
                        </div>
                    </div>
                </div>

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

                                        <span :style="getTagStyle(note.tag)"
                                            class="px-2 py-0.5 rounded text-[10px] font-extrabold border uppercase tracking-wider"
                                            x-text="'#' + note.tag"></span>
                                    </div>
                                    <template x-if="note.due_date">
                                        <div
                                            class="flex items-center gap-1.5 ml-2 bg-slate-800/40 px-2 py-1 rounded-xl border border-slate-700/30">
                                            <span class="text-slate-500">📅</span>
                                            <span class="font-mono text-[11px] text-slate-300"
                                                x-text="note.due_date"></span>

                                            <template x-if="note.due_status === 'OVERDUE'">
                                                <span
                                                    class="bg-red-600/20 text-red-400 border border-red-500/30 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase animate-pulse">
                                                    ⚠️ Overdue by <span x-text="note.days_left"></span> days
                                                </span>
                                            </template>

                                            <template x-if="note.due_status === 'TODAY'">
                                                <span
                                                    class="bg-amber-600/20 text-amber-400 border border-amber-500/30 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase animate-pulse">
                                                    ⏰ Due Today!
                                                </span>
                                            </template>

                                            <template x-if="note.due_status === 'PENDING'">
                                                <span
                                                    :class="{
                                                        'bg-emerald-500/10 text-emerald-400 border-emerald-500/20': note
                                                            .days_left > 3,
                                                        'bg-rose-500/10 text-rose-400 border-rose-500/20': note
                                                            .days_left <= 3
                                                    }"
                                                    class="px-1.5 py-0.5 rounded text-[9px] font-bold border uppercase">
                                                    ⏳ <span x-text="note.days_left"></span> days left
                                                </span>
                                            </template>
                                        </div>
                                    </template>

                                    <div class="flex items-center gap-4 text-slate-400">
                                        <span class="flex items-center gap-1">👤 <span
                                                class="text-slate-300 font-medium" x-text="note.author"></span></span>
                                        <span class="font-mono text-slate-500" x-text="note.date"></span>
                                    </div>
                                </div>

                                <div class="p-6">
                                    <p class="text-slate-200 text-sm font-medium leading-relaxed mb-4"
                                        x-text="note.text"></p>

                                    <div
                                        class="flex items-center text-[11px] border-t border-slate-800/60 pt-4 justify-between w-full">
                                        <div class="flex items-center gap-3">
                                            <a :href="note.vscode"
                                                class="text-blue-400 hover:underline flex items-center gap-1 font-medium">🖥️
                                                Open in VS Code</a>
                                            <template x-if="note.link">
                                                <a :href="note.link" target="_blank"
                                                    class="text-slate-500 hover:text-indigo-400 font-mono truncate max-w-xs md:max-w-sm"
                                                    x-text="'📄 ' + note.file"></a>
                                            </template>
                                            <template x-if="!note.link">
                                                <span class="text-slate-600 font-mono"
                                                    x-text="'📄 ' + note.file"></span>
                                            </template>
                                        </div>

                                        <button @click="resolveTask(note)"
                                            class="bg-emerald-500/10 hover:bg-emerald-500 text-emerald-400 hover:text-slate-950 border border-emerald-500/20 px-3 py-1 rounded-xl text-[10px] font-bold flex items-center gap-1 transition-all shadow-sm">
                                            Check Resolve 🏆
                                        </button>
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
    <script>
        // --- ১. Author Debt Chart Data ---
        const authorData = @json($authorCounts);
        const authorLabels = Object.keys(authorData);
        const authorValues = Object.values(authorData);

        new Chart(document.getElementById('authorDebtChart'), {
            type: 'bar',
            data: {
                labels: authorLabels,
                datasets: [{
                    label: 'Pending Tags',
                    data: authorValues,
                    backgroundColor: 'rgba(99, 102, 241, 0.2)',
                    borderColor: 'rgba(99, 102, 241, 1)',
                    borderWidth: 2,
                    borderRadius: 8,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#1e293b'
                        },
                        ticks: {
                            color: '#94a3b8'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#94a3b8'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // --- ২. Weekly Activity Chart Data ---
        const weeklyStats = @json($weeklyStats);
        const dateLabels = Object.keys(weeklyStats);
        const addedTasks = dateLabels.map(date => weeklyStats[date].added);
        const resolvedTasks = dateLabels.map(date => weeklyStats[date].resolved);

        new Chart(document.getElementById('weeklyActivityChart'), {
            type: 'line',
            data: {
                labels: dateLabels,
                datasets: [{
                        label: 'New Tags Added',
                        data: addedTasks,
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Tags Resolved 🏆',
                        data: resolvedTasks,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.3,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#1e293b'
                        },
                        ticks: {
                            color: '#94a3b8'
                        }
                    },
                    x: {
                        grid: {
                            color: '#1e293b'
                        },
                        ticks: {
                            color: '#94a3b8'
                        }
                    }
                },
                plugins: {
                    legend: {
                        labels: {
                            color: '#94a3b8',
                            font: {
                                family: 'Inter'
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>

</html>
