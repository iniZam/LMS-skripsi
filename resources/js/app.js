// Small progressive-enhancement helpers. No framework needed for this app.

document.addEventListener('DOMContentLoaded', () => {
    // Confirm before any destructive form submission.
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const message = form.getAttribute('data-confirm') || 'Apakah Anda yakin?';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });

    // Mobile sidebar toggle.
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.getElementById('app-sidebar');
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('-translate-x-full');
        });
    }

    // Auto-hide flash banners after a few seconds.
    document.querySelectorAll('[data-flash]').forEach((el) => {
        setTimeout(() => {
            el.style.transition = 'opacity 0.5s ease';
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 500);
        }, 5000);
    });

    // Toggle assignment fields in the meeting form based on the checkbox.
    const hasAssignment = document.getElementById('has_assignment');
    const assignmentFields = document.getElementById('assignment-fields');
    if (hasAssignment && assignmentFields) {
        const sync = () => assignmentFields.classList.toggle('hidden', !hasAssignment.checked);
        hasAssignment.addEventListener('change', sync);
        sync();
    }
});


// ==========================================
    // Logika AI Tutor Chatbot
    // ==========================================
    const chatForm = document.getElementById('ai-chat-form');
    const chatInput = document.getElementById('ai-chat-input');
    const chatHistory = document.getElementById('ai-chat-history');
    const submitBtn = document.getElementById('ai-chat-submit');

    if (chatForm && chatInput && chatHistory) {
        chatForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const question = chatInput.value.trim();
            if (!question) return;
            
            const meetingId = chatForm.dataset.meetingId;
            const csrfToken = chatForm.querySelector('input[name="_token"]').value;

            // 1. Tampilkan pesan siswa di layar
            appendMessage('Siswa', question, true);
            chatInput.value = ''; // Kosongkan input
            
            // 2. Tampilkan indikator proses
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            submitBtn.textContent = 'Memikirkan...';

            try {
                // 3. Kirim data ke backend Laravel melalui AJAX (Fetch API)
                const response = await fetch(`/meetings/${meetingId}/ask-ai`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ question })
                });

                const data = await response.json();
                
                // 4. Tampilkan jawaban dari AI jika sukses
                if (response.ok) {
                    appendMessage('AI', data.answer, false);
                } else {
                    appendMessage('Sistem', 'Maaf, terjadi kesalahan saat menghubungi asisten AI.', false, true);
                }
            } catch (error) {
                appendMessage('Sistem', 'Gagal terhubung ke server. Periksa koneksi internet Anda.', false, true);
            } finally {
                // Kembalikan tombol ke keadaan semula
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                submitBtn.textContent = 'Kirim';
            }
        });

        // Fungsi pembantu untuk membuat dan menyisipkan elemen gelembung obrolan (chat bubble)
        function appendMessage(sender, text, isUser, isError = false) {
            const wrapper = document.createElement('div');
            wrapper.className = `flex items-start gap-3 ${isUser ? 'flex-row-reverse' : ''}`;
            
            const avatar = document.createElement('div');
            // Warna avatar: Hitam untuk Siswa, Abu-abu untuk AI, Merah untuk Sistem (Error)
            const avatarColors = isUser ? 'bg-ink-900 text-white' : (isError ? 'bg-rose-100 text-rose-500' : 'bg-ink-100 text-ink-600');
            avatar.className = `w-8 h-8 shrink-0 rounded-full flex items-center justify-center text-xs font-bold font-display ${avatarColors}`;
            avatar.textContent = sender.charAt(0);

            const bubble = document.createElement('div');
            // Desain gelembung: arah ekor gelembung menyesuaikan pengirim
            const bubbleColors = isUser 
                ? 'bg-ink-900 text-white rounded-2xl rounded-tr-none' 
                : (isError ? 'bg-rose-50 text-rose-600 border border-rose-100 rounded-2xl rounded-tl-none' : 'bg-white border border-ink-100 text-ink-700 rounded-2xl rounded-tl-none');
            bubble.className = `px-4 py-2.5 text-sm shadow-sm max-w-[85%] ${bubbleColors}`;
            
            // Menggunakan textContent untuk melindungi dari potensi eksploitasi XSS jika AI/Siswa mengirim script
            bubble.textContent = text; 

            wrapper.appendChild(avatar);
            wrapper.appendChild(bubble);
            
            chatHistory.appendChild(wrapper);
            
            // Gulir layar (scroll) secara otomatis ke pesan paling bawah
            chatHistory.scrollTop = chatHistory.scrollHeight; 
        }
    }