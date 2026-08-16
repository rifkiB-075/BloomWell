/**
 * mood-utils.js
 * Fungsi normalisasi mood terpusat untuk seluruh halaman BloomWell
 * (mood meter, kalender, analisis, riwayat) supaya hasilnya SELALU konsisten.
 *
 * Sebelumnya setiap halaman punya salinan fungsi normalisasi sendiri-sendiri
 * dengan logika yang sedikit berbeda, dan ada bug batas angka:
 * nilai mood_value = 20 (Sangat Sedih) malah kepetakan jadi "Sedih"
 * karena kondisi `num >= 20` salah urutan. Ini sudah diperbaiki di sini.
 */
(function (global) {
    const VALID_MOODS = ['very-happy', 'happy', 'neutral', 'sad', 'very-sad'];

    const TEXT_MAP = {
        'very-happy': 'very-happy',
        'happy': 'happy',
        'neutral': 'neutral',
        'sad': 'sad',
        'very-sad': 'very-sad',
        'sangat bahagia': 'very-happy',
        'bahagia': 'happy',
        'netral': 'neutral',
        'sedih': 'sad',
        'sangat sedih': 'very-sad'
    };

    // Nilai skor 1-5 (mood_score dari mood_entries)
    const SCORE_1_5 = ['very-sad', 'sad', 'neutral', 'happy', 'very-happy'];

    // Nilai eksak skala 0-100 yang dipakai UI mood meter
    const EXACT_0_100 = { 100: 'very-happy', 85: 'very-happy', 72: 'happy', 50: 'neutral', 34: 'sad', 20: 'very-sad' };

    function fromNumber(num) {
        if (isNaN(num)) return null;

        if (num >= 1 && num <= 5) {
            return SCORE_1_5[num - 1];
        }

        if (Object.prototype.hasOwnProperty.call(EXACT_0_100, num)) {
            return EXACT_0_100[num];
        }

        // Fallback rentang skala 0-100.
        // PENTING: urutan dicek dari besar ke kecil, dan batas bawah pakai `>` bukan `>=`
        // supaya nilai 20 (very-sad) tidak ikut kena kondisi "sad".
        if (num >= 80) return 'very-happy';
        if (num >= 60) return 'happy';
        if (num >= 40) return 'neutral';
        if (num > 20) return 'sad';
        return 'very-sad';
    }

    /**
     * @param {*} rawMood - nilai field `mood` dari database (idealnya string spt 'happy')
     * @param {*} moodValue - nilai field `mood_value` / `mood_score` (angka)
     * @returns {string} salah satu dari VALID_MOODS
     */
    function normalizeMood(rawMood, moodValue) {
        const raw = String(rawMood ?? '').toLowerCase().trim();

        if (TEXT_MAP[raw]) return TEXT_MAP[raw];

        const num = parseInt(moodValue, 10);
        const fromValue = fromNumber(num);
        if (fromValue) return fromValue;

        // Kalau mood_value tidak ada/tidak valid, coba baca angka dari field mood itu sendiri
        // (menangani data lama yang menyimpan angka di kolom mood, mis. "0", "3", "85")
        const rawNum = parseInt(raw, 10);
        const fromRaw = fromNumber(rawNum);
        if (fromRaw) return fromRaw;

        console.warn('[mood-utils] Tidak bisa mengenali mood, fallback ke "neutral". mood="' + rawMood + '" mood_value=' + moodValue);
        return 'neutral';
    }

    global.normalizeMood = normalizeMood;
    global.VALID_MOODS = VALID_MOODS;
})(window);
