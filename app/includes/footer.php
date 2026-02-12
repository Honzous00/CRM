</main>

<footer class="bg-gray-800 text-white text-center p-4 mt-8 border-t border-gray-700">
    <div class="container mx-auto text-sm opacity-75">
        <p>&copy; <?php echo date("Y"); ?> Můj Klientský Systém. Všechna práva vyhrazena.</p>
    </div>
</footer>

<style>
    @keyframes pulse-blue {
        0% {
            box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.4);
        }

        /* blue-600 */
        70% {
            box-shadow: 0 0 0 10px rgba(37, 99, 235, 0);
        }

        100% {
            box-shadow: 0 0 0 0 rgba(37, 99, 235, 0);
        }
    }

    .animate-pulse-blue {
        animation: pulse-blue 2s infinite;
    }

    .glass-effect {
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(10px);
    }
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<script>
    window.crmLogs = [];
    const originalLog = console.log;
    const originalError = console.error;
    console.log = (...args) => {
        window.crmLogs.push(`[LOG] ${new Date().toLocaleTimeString()}: ${args.join(' ')}`);
        originalLog.apply(console, args);
    };
    console.error = (...args) => {
        window.crmLogs.push(`[ERROR] ${new Date().toLocaleTimeString()}: ${args.join(' ')}`);
        originalError.apply(console, args);
    };
</script>

<div id="support-widget" class="fixed bottom-6 right-6 z-50">
    <div class="flex items-center space-x-3 justify-end">
        <span id="support-label"
            class="bg-gray-900 text-white text-xs px-3 py-1 rounded-lg shadow-lg opacity-0 transition-opacity duration-300 pointer-events-none">Potřebuješ
            pomoc?</span>
        <button onclick="toggleSupport()" onmouseover="document.getElementById('support-label').style.opacity='1'"
            onmouseout="document.getElementById('support-label').style.opacity='0'"
            class="bg-blue-600 hover:bg-blue-700 text-white w-14 h-14 rounded-full shadow-2xl transition-all transform hover:scale-110 flex items-center justify-center animate-pulse-blue">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
            </svg>
        </button>
    </div>

    <div id="supportModal"
        class="hidden absolute bottom-20 right-0 w-80 glass-effect rounded-2xl shadow-2xl border border-gray-200 overflow-hidden shadow-[0_20px_50px_rgba(0,0,0,0.3)]">
        <div class="bg-blue-600 p-4 text-white">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="font-bold text-lg leading-tight text-white">Zákaznická linka</h3>
                    <p class="text-xs opacity-80">Napiš mi, co se děje.</p>
                </div>
                <button onclick="toggleSupport()"
                    class="hover:bg-blue-500 rounded-full p-1 transition-colors text-xl leading-none">&times;</button>
            </div>
        </div>

        <div id="support-form-content" class="p-5">
            <label class="block text-xs font-bold text-gray-400 uppercase mb-1 tracking-wider">Popis problému</label>
            <textarea id="supportMessage"
                class="w-full border-2 border-gray-100 rounded-xl p-3 text-sm focus:border-blue-500 outline-none transition-colors resize-none"
                rows="3" placeholder="Co nefunguje?"></textarea>

            <div
                class="mt-4 bg-gray-50 p-3 rounded-xl border border-dashed border-gray-300 hover:bg-gray-100 transition-colors">
                <label class="cursor-pointer flex flex-col items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-400" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span class="text-[11px] text-gray-500 mt-1 font-medium">Přidat fotku</span>
                    <input type="file" id="supportFile" accept="image/*" class="hidden">
                </label>
                <div id="file-name" class="text-[10px] text-blue-600 mt-1 text-center font-bold truncate"></div>
            </div>

            <button id="sendBtn" onclick="sendToDiscord()"
                class="w-full mt-4 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl transition-all shadow-lg active:scale-95 flex items-center justify-center space-x-2">
                <span>Odeslat hlášení</span>
            </button>
        </div>

        <div id="support-success-content" class="hidden p-10 text-center animate-in fade-in slide-in-from-bottom-4">
            <div
                class="bg-green-100 text-green-600 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h4 class="font-bold text-gray-800 text-lg">Odesláno!</h4>
            <p class="text-sm text-gray-500 mt-2">Kouknu na to hned,<br>jak budu moct.</p>
            <button onclick="resetSupport()" class="mt-6 text-blue-600 text-xs font-bold hover:underline">Poslat další
                zprávu</button>
        </div>
    </div>
</div>

<script>
    $('#supportFile').on('change', function() {
        const fileName = $(this).val().split('\\').pop();
        $('#file-name').text(fileName ? 'Vybráno: ' + fileName : '');
    });

    function toggleSupport() {
        $('#supportModal').toggleClass('hidden');
    }

    function resetSupport() {
        $('#support-success-content').addClass('hidden');
        $('#support-form-content').removeClass('hidden');
        $('#supportMessage').val('');
        $('#supportFile').val('');
        $('#file-name').text('');
    }

    async function sendToDiscord() {
        const btn = document.getElementById('sendBtn');
        const message = document.getElementById('supportMessage').value;
        const fileInput = document.getElementById('supportFile');
        const webhookUrl = ' '; // Sem vlož svůj Discord Webhook URL

        btn.innerHTML = '<span>Odesílám...</span>';
        btn.disabled = true;

        try {
            // --- 1. KROK: Poslání hlavní karty (to nejdůležitější) ---
            const ticketEmbed = {
                embeds: [{
                    title: "🆘 Nový Support Ticket",
                    description: `**Zpráva od uživatele:**\n>>> ${message || "_Bez popisu_"}`,
                    color: 2580715, // Modrá
                    fields: [{
                            name: "📍 Stránka",
                            value: `[Odkaz na web](${window.location.href})`,
                            inline: true
                        },
                        {
                            name: "🕒 Odesláno",
                            value: new Date().toLocaleTimeString(),
                            inline: true
                        }
                    ],
                    footer: {
                        text: "Relatio Support System"
                    },
                    timestamp: new Date()
                }]
            };

            // Odešleme první zprávu
            await fetch(webhookUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(ticketEmbed)
            });

            // --- 2. KROK: Poslání technických příloh (Screenshot a Logy) ---
            const formData = new FormData();

            // Screenshot obrazovky
            const canvas = await html2canvas(document.body);
            const screenshotBlob = await new Promise(res => canvas.toBlob(res, 'image/png'));
            formData.append('file1', screenshotBlob, 'screenshot.png');

            // Diagnostické logy
            const logText = `PROHLÍŽEČ: ${navigator.userAgent}\n` + (window.crmLogs.join('\n') || "Žádné logy.");
            formData.append('file2', new Blob([logText], {
                type: 'text/plain'
            }), 'diagnostika.txt');

            // Pokud mamka nahrála vlastní fotku
            if (fileInput.files[0]) {
                formData.append('file3', fileInput.files[0]);
            }

            // Druhá zpráva s textem "Přílohy k ticketu"
            formData.append('payload_json', JSON.stringify({
                content: "📎 **Technické přílohy a diagnostika:**"
            }));

            await fetch(webhookUrl, {
                method: 'POST',
                body: formData
            });

            // ÚSPĚCH
            $('#support-form-content').addClass('hidden');
            $('#support-success-content').removeClass('hidden');

        } catch (err) {
            alert('Něco se nepovedlo. Zkus to prosím znovu.');
        } finally {
            btn.innerHTML = 'Odeslat hlášení';
            btn.disabled = false;
        }
    }
</script>

<script src="/public/js/main.js"></script>
</body>

</html>