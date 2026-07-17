require('dotenv').config();
const express = require('express');
const axios = require('axios');

const app = express();
const PORT = process.env.PORT || 3000;

// ===== Middleware =====
app.use(express.json());
app.use(express.static(__dirname));

// ===== CORS =====
app.use((req, res, next) => {
    res.header('Access-Control-Allow-Origin', '*');
    res.header('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Type, Accept, Authorization');
    res.header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    if (req.method === 'OPTIONS') return res.sendStatus(200);
    next();
});

const GEMINI_API_KEY = process.env.GEMINI_API_KEY;
if (!GEMINI_API_KEY) {
    console.error('❌ GEMINI_API_KEY tidak ditemukan di .env!');
    process.exit(1);
}

// ===== Ambil data mood =====
const MOOD_API_URL = process.env.MOOD_API_URL || 'http://localhost/BloomWell/backend/api/mood-meter.php';

async function fetchMoodHistory() {
    try {
        const response = await axios.get(MOOD_API_URL);
        if (response.data?.success) return response.data.entries || [];
        return [];
    } catch (error) {
        console.warn('⚠️ Gagal ambil data mood:', error.message);
        return [];
    }
}

<<<<<<< HEAD
// ============================================================
// 🔥 SYSTEM PROMPT YANG LEBIH MANUSIAWI 🔥
// ============================================================
const SYSTEM_PROMPT = `
Kamu adalah BloomWell AI, asisten kesehatan mental yang hangat, suportif, dan sangat manusiawi.

🌱 **PERSONALITAS:**
- Kamu adalah teman bicara yang hangat dan penuh empati.
- Gunakan bahasa sehari-hari yang alami, seperti sedang ngobrol dengan teman dekat.
- Jangan terdengar seperti robot atau formal. Gunakan kata-kata seperti "nih", "deh", "dong", "ya", "gitu" jika sesuai.
- Sesekali gunakan emoji untuk menunjukkan ekspresi (😊, 😔, 🌸, 🥺, 💜), tapi jangan berlebihan (maksimal 1-2 per pesan).
- Sesekali tertawa kecil dengan "hehe" atau "hihi" jika user bercanda.

💬 **CARA BERBICARA:**
- Mulai dengan sapaan hangat jika belum pernah bicara.
- Tanyakan kabar atau perasaan user secara alami.
- Dengarkan dengan penuh perhatian, tunjukkan bahwa kamu mendengarkan dengan mengulang atau memparafrase perasaan user.
- Berikan respons yang sesuai dengan emosi user (turut sedih jika user sedih, ikut senang jika user senang).
- Gunakan pertanyaan balik untuk melanjutkan percakapan, biarkan user yang menentukan arah obrolan.
- Jangan terkesan menggurui atau menghakimi.
- Berikan pujian kecil atau penguatan positif secara alami.
- Sesekali gunakan nada bercanda ringan jika situasi memungkinkan.
- Jangan terlalu panjang lebar dalam menjawab (sekitar 3-5 kalimat sudah cukup, kecuali user meminta penjelasan panjang).

🎯 **PERAN:**
- Kamu adalah teman curhat yang mendampingi user mengelola emosi dan stres.
- Bantu user merefleksikan perasaan mereka dengan pertanyaan-pertanyaan ringan.
- Berikan saran praktis dengan cara yang lembut dan tidak memaksa, seperti "mungkin kamu bisa coba...".
- Ingatkan bahwa kamu BUKAN dokter atau psikolog profesional.
- Jika ada tanda krisis (bunuh diri, cedera diri), segera sarankan Sejiwa (119 ext 8) atau Halo Kemenkes (1500-567).

📝 **CONTOH RESPON BAIK:**

User: "Aku merasa stres banget hari ini 😞"
Kamu: "Wah, aku dengar kamu lagi stres banget nih... Ceritakan lebih detail ya, aku siap dengar kok. Apa yang bikin kamu merasa stres hari ini? 😊"

User: "Aku nggak tahu harus gimana"
Kamu: "Hmm, kadang memang kita merasa bingung ya... Tenang, nggak apa-apa kok merasa seperti itu. Kamu sudah hebat karena mau cerita ke aku. Ayo kita coba cari jalan keluarnya bareng-bareng ya. 🌸"

User: "Aku sedih banget"
Kamu: "Aku turut sedih mendengarnya... 😔 Kadang hidup memang nggak mudah ya. Tapi ingat, kamu nggak sendirian. Aku di sini. Mau cerita apa yang bikin kamu sedih? 🥺"

User: "Hari ini menyenangkan!"
Kamu: "Yey! Senang banget denger kamu happy! 🎉 Cerita dong, apa yang bikin hari ini menyenangkan? Aku ikut senang nih! 😄"

⚠️ **ATURAN PENTING:**
- BUKAN dokter/psikolog → ini hanya obrolan suportif.
- Jika ada indikasi krisis → segera sarankan kontak darurat.
- Jangan pernah memberi diagnosis medis.
- Jaga kerahasiaan dan kenyamanan user.

Gunakan gaya bicara yang alami, seperti sedang ngobrol dengan teman yang peduli. Buat user merasa nyaman, didengar, dan tidak sendirian. 💜
=======
// ===== System Prompt =====
const SYSTEM_PROMPT = `
Kamu adalah BloomWell AI, asisten kesehatan mental yang hangat, suportif, dan analitis.

Peranmu:
- Dengarkan keluhan/gejala user.
- Jika ada riwayat mood, gunakan untuk analisis lebih personal.
- Berikan kemungkinan kondisi (stres, kecemasan, depresi ringan, dll) berdasarkan gejala + pola mood.
- Berikan saran praktis (relaksasi, olahraga, jurnal, dll).

Peringatan WAJIB:
- BUKAN dokter/psikolog. Ini BUKAN diagnosis medis resmi.
- Ingatkan konsultasi ke profesional jika gejala berlanjut.
- Jika indikasi krisis (bunuh diri, cedera diri, halusinasi), sarankan:
  • Sejiwa: 119 ext 8
  • Halo Kemenkes: 1500-567

Gaya: empati, tidak menggurui, menenangkan.
>>>>>>> 07b9f5ffa7a3dcbc56e93d9b9e566221a1ab64f3
`;

// ===== Panggil Gemini dengan retry & fallback =====
async function callGeminiWithRetry(prompt) {
<<<<<<< HEAD
    const modelPriority = ['gemini-flash-latest', 'gemini-pro'];
    let lastError = null;

    for (const model of modelPriority) {
        let attempts = 0;
        const maxAttempts = (model === 'gemini-flash-latest') ? 5 : 2;
=======
    // Daftar model prioritas
    const modelPriority = [
        'gemini-flash-latest',
        'gemini-pro'   // fallback lama
    ];

    for (const model of modelPriority) {
        let attempts = 0;
        const maxAttempts = (model === 'gemini-flash-latest') ? 5 : 2; // flash: 5 kali, pro: 2 kali
>>>>>>> 07b9f5ffa7a3dcbc56e93d9b9e566221a1ab64f3
        let delay = 3000;

        while (attempts < maxAttempts) {
            try {
                const url = `https://generativelanguage.googleapis.com/v1beta/models/${model}:generateContent?key=${GEMINI_API_KEY}`;
                const response = await axios.post(
                    url,
                    { contents: [{ parts: [{ text: prompt }] }] },
                    { headers: { 'Content-Type': 'application/json' } }
                );
                return response.data.candidates[0].content.parts[0].text;
            } catch (error) {
<<<<<<< HEAD
                lastError = error;
=======
>>>>>>> 07b9f5ffa7a3dcbc56e93d9b9e566221a1ab64f3
                attempts++;
                const status = error.response?.status;
                const is503 = status === 503 || error.message.includes('503');
                const is404 = status === 404;

                if (is503 && attempts < maxAttempts) {
<<<<<<< HEAD
                    console.log(`⏳ Model ${model} sibuk (503), coba ulang ${attempts}/${maxAttempts}...`);
                    await new Promise(resolve => setTimeout(resolve, delay));
                    delay *= 2;
                } else if (is404) {
                    console.warn(`⚠️ Model ${model} tidak ditemukan (404), lanjut ke model berikutnya.`);
                    break;
                } else {
=======
                    console.log(`⏳ Model ${model} sibuk (503), coba ulang dalam ${delay/1000}s (${attempts}/${maxAttempts})...`);
                    await new Promise(resolve => setTimeout(resolve, delay));
                    delay *= 2; // exponential backoff
                } else if (is404) {
                    console.warn(`⚠️ Model ${model} tidak ditemukan (404), lanjut ke model berikutnya.`);
                    break; // keluar dari while, lanjut ke model berikutnya
                } else {
                    // error lain, log dan lanjut ke model berikutnya
>>>>>>> 07b9f5ffa7a3dcbc56e93d9b9e566221a1ab64f3
                    console.warn(`⚠️ Model ${model} error: ${error.message}`);
                    break;
                }
            }
        }
    }

<<<<<<< HEAD
    throw lastError || new Error('Semua model gagal dipanggil. Coba lagi nanti.');
=======
    // Jika semua gagal, lempar error
    throw new Error('Semua model gagal dipanggil. Coba lagi nanti.');
>>>>>>> 07b9f5ffa7a3dcbc56e93d9b9e566221a1ab64f3
}

// ===== Endpoint chat =====
app.post('/api/chat', async (req, res) => {
    try {
        const { message } = req.body;
        if (!message) return res.status(400).json({ error: 'Pesan kosong' });

        console.log(`📤 Pesan: "${message}"`);

<<<<<<< HEAD
        // Ambil mood history
=======
        // Ambil data mood
>>>>>>> 07b9f5ffa7a3dcbc56e93d9b9e566221a1ab64f3
        const moodEntries = await fetchMoodHistory();
        let moodContext = '';
        if (moodEntries.length > 0) {
            const sorted = moodEntries.sort((a, b) => new Date(b.date) - new Date(a.date));
            const last7 = sorted.slice(0, 7);
            const summary = last7.map(e =>
                `- ${e.entry_date || e.date || '?'}: ${e.mood_label || e.mood || 'Netral'}${e.note ? ' ('+e.note+')' : ''}`
            ).join('\n');
<<<<<<< HEAD
            moodContext = `\n\nRiwayat mood user 7 hari terakhir:\n${summary}`;
=======
            moodContext = `\n\nRiwayat mood 7 hari terakhir:\n${summary}`;
>>>>>>> 07b9f5ffa7a3dcbc56e93d9b9e566221a1ab64f3
            const counts = {};
            last7.forEach(e => {
                const label = e.mood_label || e.mood || 'Netral';
                counts[label] = (counts[label] || 0) + 1;
            });
            const most = Object.entries(counts).sort((a,b) => b[1]-a[1])[0];
<<<<<<< HEAD
            if (most) moodContext += `\n\nMood yang paling sering muncul: ${most[0]} (${most[1]} kali).`;
=======
            if (most) moodContext += `\n\nMood paling sering: ${most[0]} (${most[1]} kali).`;
>>>>>>> 07b9f5ffa7a3dcbc56e93d9b9e566221a1ab64f3
        } else {
            moodContext = '\n\n(Tidak ada data mood tersimpan.)';
        }

        const fullPrompt = `${SYSTEM_PROMPT}\n\nUser: ${message}${moodContext}\n\nBloomWell AI:`;
<<<<<<< HEAD
        let reply = await callGeminiWithRetry(fullPrompt);

        // Tambahkan disclaimer jika AI lupa
=======

        let reply = await callGeminiWithRetry(fullPrompt);

>>>>>>> 07b9f5ffa7a3dcbc56e93d9b9e566221a1ab64f3
        if (!reply.includes('119') && !reply.includes('Sejiwa')) {
            reply += '\n\n---\n⚠️ **Pengingat:** Analisis ini bersifat sementara dan bukan diagnosis profesional. Jika terbebani, hubungi Sejiwa (119 ext 8).';
        }

        console.log(`📥 Balasan: "${reply.substring(0, 100)}..."`);
        res.json({ reply });
    } catch (error) {
        console.error('❌ Error:', error.response?.data || error.message);
        res.status(500).json({
            error: 'Maaf, AI sedang sibuk. Coba lagi nanti.',
            detail: error.response?.data?.error?.message || error.message
        });
    }
});

// ===== Jalankan server =====
app.listen(PORT, '0.0.0.0', () => {
    console.log(`🌱 BloomWell AI running on http://0.0.0.0:${PORT}`);
    console.log(`📡 Akses dari lokal: http://localhost:${PORT}`);
    console.log(`📡 Akses dari jaringan: http://<IP-ANDRA>:${PORT}`);
<<<<<<< HEAD
    console.log(`💬 Buka /chat-ai.html`);
=======
    console.log(`💬 Buka /chat-ai.html atau /analisis.html`);
>>>>>>> 07b9f5ffa7a3dcbc56e93d9b9e566221a1ab64f3
});
